/*
 *  Copyright (c) 2015-present, Facebook, Inc.
 *  All rights reserved.
 *
 *  This source code is licensed under the MIT license found in the
 *  LICENSE file in the root directory of this source tree.
 *
 */

namespace Facebook\DefinitionFinder\Test;

use function Facebook\FBExpect\expect;
use type Facebook\DefinitionFinder\{FileParser, RelationshipToken};
use namespace HH\Lib\{C, Vec};

class GenericsTest extends \Facebook\HackTest\HackTest {
  public function testClassHasGenerics(): void {
    $data = '<?hh class Foo<Tk, Tv> {}';
    $parser = FileParser::fromData($data);
    $class = $parser->getClass('Foo');

    expect(Vec\map($class->getGenericTypes(), $x ==> $x->getName()))->toBeSame(
      vec['Tk', 'Tv'],
    );

    expect(
      Vec\map($class->getGenericTypes(), $x ==> C\count($x->getConstraints())),
    )->toBeSame(vec[0, 0]);
  }

  public function testFunctionHasGenerics(): void {
    $data = '<?hh function foo<Tk, Tv>(){}';
    $parser = FileParser::fromData($data);
    $function = $parser->getFunction('foo');

    expect(Vec\map($function->getGenericTypes(), $x ==> $x->getName()))
      ->toBeSame(vec['Tk', 'Tv']);

    expect(
      Vec\map(
        $function->getGenericTypes(),
        $x ==> C\count($x->getConstraints()),
      ),
    )->toBeSame(vec[0, 0]);
  }

  public function testConstrainedGenerics(): void {
    $data = '<?hh class Foo<T1 as Bar, T2 super Baz> {}';
    $parser = FileParser::fromData($data);
    $class = $parser->getClass('Foo');

    expect(
      Vec\map(
        $class->getGenericTypes(),
        $x ==> $x->getConstraints()[0]['type']->getTypeText(),
      ),
    )->toBeSame(vec['Bar', 'Baz']);
    expect(
      Vec\map(
        $class->getGenericTypes(),
        $x ==> $x->getConstraints()[0]['relationship'],
      ),
    )->toBeSame(vec[RelationshipToken::SUBTYPE, RelationshipToken::SUPERTYPE]);
  }

  public function testGenericsWithMultipleConstraints(): void {
    $data = '<?hh class Foo<T super Herp as Derp> {}';
    $parser = FileParser::fromData($data);
    $constraints = Vec\map(
      $parser->getClass('Foo')->getGenericTypes()[0]->getConstraints(),
      $c ==> {
        $c['type'] = $c['type']->getTypeText();
        return $c;
      },
    );
    expect($constraints)->toBeSame(
      vec[
        shape('type' => 'Herp', 'relationship' => RelationshipToken::SUPERTYPE),
        shape('type' => 'Derp', 'relationship' => RelationshipToken::SUBTYPE),
      ],
    );
  }

  public function testNamespacedConstrainedGenerics(): void {
    $data = '<?hh class Foo<T as \Bar\Baz> {}';
    $parser = FileParser::fromData($data);
    $class = $parser->getClass('Foo');

    expect(
      Vec\map(
        $class->getGenericTypes(),
        $x ==> $x->getConstraints()[0]['type']->getTypeText(),
      ),
    )->toBeSame(vec['Bar\Baz']);
  }

  public function testVariance(): void {
    $data = '<?hh class Foo<-Ta, Tb, +Tc> {}';
    $parser = FileParser::fromData($data);
    $class = $parser->getClass('Foo');
    $generics = $class->getGenericTypes();

    expect(Vec\map($generics, $x ==> $x->getName()))->toBeSame(
      vec['Ta', 'Tb', 'Tc'],
    );
    expect(Vec\map($generics, $x ==> $x->isContravariant()))->toBeSame(
      vec[true, false, false],
    );
    expect(Vec\map($generics, $x ==> $x->isInvariant()))->toBeSame(
      vec[false, true, false],
    );
    expect(Vec\map($generics, $x ==> $x->isCovariant()))->toBeSame(
      vec[false, false, true],
    );
  }

  public function testVectorLikeArrayParam(): void {
    $data = '<?hh function foo(array<SomeClass> $param): void {}';
    $parser = FileParser::fromData($data);
    $function = $parser->getFunction('foo');
  }

  public function testVectorLikeArrayOfPrimitivesParam(): void {
    $data = '<?hh function foo(array<string> $param): void {}';
    $parser = FileParser::fromData($data);
    $function = $parser->getFunction('foo');
  }

  public function testMapLikeArrayParam(): void {
    $data = '<?hh function foo(array<string, PharFileInfo> $list): void {}';
    $parser = FileParser::fromData($data);
    $function = $parser->getFunction('foo');
  }

  public function testInlineShapeConstraint(): void {
    $data = '<?hh function foo<T as shape()>(): void {}';
    $parser = FileParser::fromData($data);
    $function = $parser->getFunction('foo');
    $generics = $function->getGenericTypes();
    expect($generics[0]->getConstraints()[0]['type']->getTypeText())->toBeSame(
      'shape()',
    );
  }

  public function testGenericWithTrailingComma(): void {
    $data = '<?hh function foo(ImmMap<string,string,> $bar): void {}';
    $parser = FileParser::fromData($data);
    $function = $parser->getFunction('foo');
    $param_types = Vec\map(
      $function->getParameters(),
      $param ==> $param->getTypehint()?->getTypeText(),
    );
    expect($param_types)->toBeSame(vec['ImmMap<string,string>']);
  }
}
