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
  public async function testClassHasGenerics(): Awaitable<void> {
    $data = '<?hh class Foo<Tk, Tv> {}';
    $parser = await FileParser::fromDataAsync($data);
    $class = $parser->getClass('Foo');

    expect(Vec\map($class->getGenericTypes(), $x ==> $x->getName()))->toBeSame(
      vec['Tk', 'Tv'],
    );

    expect(
      Vec\map($class->getGenericTypes(), $x ==> C\count($x->getConstraints())),
    )->toBeSame(vec[0, 0]);
  }

  public async function testFunctionHasGenerics(): Awaitable<void> {
    $data = '<?hh function foo<Tk, Tv>(){}';
    $parser = await FileParser::fromDataAsync($data);
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

  public async function testConstrainedGenerics(): Awaitable<void> {
    $data = '<?hh class Foo<T1 as Bar, T2 super Baz> {}';
    $parser = await FileParser::fromDataAsync($data);
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

  public async function testGenericsWithMultipleConstraints(): Awaitable<void> {
    $data = '<?hh class Foo<T super Herp as Derp> {}';
    $parser = await FileParser::fromDataAsync($data);
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

  public async function testNamespacedConstrainedGenerics(): Awaitable<void> {
    $data = '<?hh class Foo<T as \Bar\Baz> {}';
    $parser = await FileParser::fromDataAsync($data);
    $class = $parser->getClass('Foo');

    expect(
      Vec\map(
        $class->getGenericTypes(),
        $x ==> $x->getConstraints()[0]['type']->getTypeText(),
      ),
    )->toBeSame(vec['Bar\Baz']);
  }

  public async function testVariance(): Awaitable<void> {
    $data = '<?hh class Foo<-Ta, Tb, +Tc> {}';
    $parser = await FileParser::fromDataAsync($data);
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

  public async function testVectorLikeArrayParam(): Awaitable<void> {
    $data = '<?hh function foo(array<SomeClass> $param): Awaitable<void> {}';
    $parser = await FileParser::fromDataAsync($data);
    $parser->getFunction('foo');
  }

  public async function testVectorLikeArrayOfPrimitivesParam(): Awaitable<void> {
    $data = '<?hh function foo(array<string> $param): Awaitable<void> {}';
    $parser = await FileParser::fromDataAsync($data);
    $parser->getFunction('foo');
  }

  public async function testMapLikeArrayParam(): Awaitable<void> {
    $data = '<?hh function foo(array<string, PharFileInfo> $list): Awaitable<void> {}';
    $parser = await FileParser::fromDataAsync($data);
    $parser->getFunction('foo');
  }

  public async function testInlineShapeConstraint(): Awaitable<void> {
    $data = '<?hh function foo<T as shape()>(): Awaitable<void> {}';
    $parser = await FileParser::fromDataAsync($data);
    $function = $parser->getFunction('foo');
    $generics = $function->getGenericTypes();
    expect($generics[0]->getConstraints()[0]['type']->getTypeText())->toBeSame(
      'shape()',
    );
  }

  public async function testGenericWithTrailingComma(): Awaitable<void> {
    $data = '<?hh function foo(ImmMap<string,string,> $bar): Awaitable<void> {}';
    $parser = await FileParser::fromDataAsync($data);
    $function = $parser->getFunction('foo');
    $param_types = Vec\map(
      $function->getParameters(),
      $param ==> $param->getTypehint()?->getTypeText(),
    );
    expect($param_types)->toBeSame(vec['HH\\ImmMap<string,string>']);
    expect($param_types)->toBeSame(vec[ImmMap::class.'<string,string>']);
  }
}
