<?hh // strict
/*
 *  Copyright (c) 2015-present, Facebook, Inc.
 *  All rights reserved.
 *
 *  This source code is licensed under the MIT license found in the
 *  LICENSE file in the root directory of this source tree.
 *
 */

namespace Facebook\DefinitionFinder\Test;

use type Facebook\DefinitionFinder\{
  FileParser,
  RelationshipToken,
};
use namespace HH\Lib\{C, Vec};

class GenericsTest extends \PHPUnit_Framework_TestCase {
  public function testClassHasGenerics(): void {
    $data = '<?hh class Foo<Tk, Tv> {}';
    $parser = FileParser::FromData($data);
    $class = $parser->getClass('Foo');

    $this->assertEquals(
      vec['Tk', 'Tv'],
      Vec\map($class->getGenericTypes(), $x ==> $x->getName()),
    );

    $this->assertEquals(
      vec[0, 0],
      Vec\map($class->getGenericTypes(), $x ==> C\count($x->getConstraints())),
    );
  }

  public function testFunctionHasGenerics(): void {
    $data = '<?hh function foo<Tk, Tv>(){}';
    $parser = FileParser::FromData($data);
    $function = $parser->getFunction('foo');

    $this->assertEquals(
      vec['Tk', 'Tv'],
      Vec\map($function->getGenericTypes(), $x ==> $x->getName()),
    );

    $this->assertEquals(
      vec[0, 0],
      Vec\map(
        $function->getGenericTypes(),
        $x ==> C\count($x->getConstraints()),
      ),
    );
  }

  public function testConstrainedGenerics(): void {
    $data = '<?hh class Foo<T1 as Bar, T2 super Baz> {}';
    $parser = FileParser::FromData($data);
    $class = $parser->getClass('Foo');

    $this->assertEquals(
      vec['Bar', 'Baz'],
      Vec\map($class->getGenericTypes(), $x ==> $x->getConstraints()[0]['type']),
    );
    $this->assertEquals(
      vec[RelationshipToken::SUBTYPE, RelationshipToken::SUPERTYPE],
      Vec\map(
        $class->getGenericTypes(),
        $x ==> $x->getConstraints()[0]['relationship'],
      ),
    );
  }

  public function testGenericsWithMultipleConstraints(): void {
    $data = '<?hh class Foo<T super Herp as Derp> {}';
    $parser = FileParser::FromData($data);
    $constraints =
      $parser->getClass('Foo')->getGenericTypes()[0]->getConstraints();
    $this->assertEquals(
      vec[
        shape('type' => 'Herp', 'relationship' => RelationshipToken::SUPERTYPE),
        shape('type' => 'Derp', 'relationship' => RelationshipToken::SUBTYPE),
      ],
      $constraints,
    );
  }

  public function testNamespacedConstrainedGenerics(): void {
    $data = '<?hh class Foo<T as \Bar\Baz> {}';
    $parser = FileParser::FromData($data);
    $class = $parser->getClass('Foo');

    $this->assertEquals(
      vec['Bar\Baz'],
      Vec\map($class->getGenericTypes(), $x ==> $x->getConstraints()[0]['type']),
    );
  }

  public function testVariance(): void {
    $data = '<?hh class Foo<-Ta, Tb, +Tc> {}';
    $parser = FileParser::FromData($data);
    $class = $parser->getClass('Foo');
    $generics = $class->getGenericTypes();

    $this->assertEquals(
      vec['Ta', 'Tb', 'Tc'],
      Vec\map($generics, $x ==> $x->getName()),
    );
    $this->assertEquals(
      vec[true, false, false],
      Vec\map($generics, $x ==> $x->isContravariant()),
    );
    $this->assertEquals(
      vec[false, true, false],
      Vec\map($generics, $x ==> $x->isInvariant()),
    );
    $this->assertEquals(
      vec[false, false, true],
      Vec\map($generics, $x ==> $x->isCovariant()),
    );
  }

  public function testVectorLikeArrayParam(): void {
    $data = '<?hh function foo(array<SomeClass> $param): void {}';
    $parser = FileParser::FromData($data);
    $function = $parser->getFunction('foo');
  }

  public function testVectorLikeArrayOfPrimitivesParam(): void {
    $data = '<?hh function foo(array<string> $param): void {}';
    $parser = FileParser::FromData($data);
    $function = $parser->getFunction('foo');
  }

  public function testMapLikeArrayParam(): void {
    $data = '<?hh function foo(array<string, PharFileInfo> $list): void {}';
    $parser = FileParser::FromData($data);
    $function = $parser->getFunction('foo');
  }

  public function testInlineShapeConstraint(): void {
    $data = '<?hh function foo<T as shape()>(): void {}';
    $parser = FileParser::FromData($data);
    $function = $parser->getFunction('foo');
    $generics = $function->getGenericTypes();
    $this->assertSame('shape()', $generics[0]->getConstraints()[0]['type']);
  }

  public function testGenericWithTrailingComma(): void {
    /* HH_FIXME[4106] HHVM_VERSION not defined */
    /* HH_FIXME[2049] HHVM_VERSION not defined */
    if (!\version_compare(HHVM_VERSION, '3.12.0', '>=')) {
      $this->markTestSkipped('only supported on 3.12+');
    }
    $data = '<?hh function foo(ImmMap<string,string,> $bar): void {}';
    $parser = FileParser::FromData($data);
    $function = $parser->getFunction('foo');
    $param_types = Vec\map(
      $function->getParameters(),
      $param ==> $param->getTypehint()?->getTypeText(),
    );
    $this->assertEquals(vec['ImmMap<string,string>'], $param_types);
  }
}
