<?hh // strict
/*
 *  Copyright (c) 2015, Facebook, Inc.
 *  All rights reserved.
 *
 *  This source code is licensed under the BSD-style license found in the
 *  LICENSE file in the root directory of this source tree. An additional grant
 *  of patent rights can be found in the PATENTS file in the same directory.
 *
 */

namespace Facebook\DefinitionFinder\Test;
use Facebook\DefinitionFinder\FileParser;

class RelationshipsTest extends \PHPUnit_Framework_TestCase {
  public function testClassExtends(): void {
    $data = '<?hh class Foo extends Bar {}';
    $def = FileParser::FromData($data)->getClass('Foo');
    $this->assertSame(
      'Bar',
      $def->getParentClassName(),
    );
    $this->assertEmpty($def->getInterfaceNames());
  }

  public function testClassImplements(): void {
    $data = '<?hh class Foo implements Bar, Baz {}';
    $def = FileParser::FromData($data)->getClass('Foo');
    $this->assertEquals(
      Vector { 'Bar', 'Baz' },
      $def->getInterfaceNames(),
    );
    $this->assertNull($def->getParentClassName());
  }

  public function testInterfaceExtends(): void {
    $data = '<?hh interface Foo extends Bar, Baz {}';
    $def = FileParser::FromData($data)->getInterface('Foo');
    $this->assertEquals(
      Vector { 'Bar', 'Baz' },
      $def->getInterfaceNames(),
    );
    $this->assertNull($def->getParentClassName());
  }

  public function testClassExtendsAndImplements(): void {
    $data = '<?hh class Foo extends Bar implements Herp, Derp {}';
    $def = FileParser::FromData($data)->getClass('Foo');
    $this->assertSame('Bar', $def->getParentClassName());
    $this->assertEquals(
      Vector { 'Herp', 'Derp' },
      $def->getInterfaceNames(),
    );
  }

  public function testClassExtendsGeneric(): void {
    $data = '<?hh class Foo extends Bar<Baz> {}';
    $def = FileParser::FromData($data)->getClass('Foo');
    $this->assertSame('Bar', $def->getParentClassName());
    $this->assertSame(
      'Bar<Baz>',
      $def->getParentClassInfo()?->getTypeText(),
    );
  }

  public function testClassImplementsGenerics(): void {
    $data = '<?hh class Foo implements KeyedIterable<Tk,Tv> {}';
    $def = FileParser::FromData($data)->getClass('Foo');
    $this->assertEquals(Vector { 'KeyedIterable' }, $def->getInterfaceNames());
    $this->assertEquals(
      Vector { 'KeyedIterable<Tk,Tv>' },
      $def->getInterfaceInfo()->map($x ==> $x->getTypeText()),
    );
  }

  public function testClassImplementsNestedGenerics(): void {
    $data = '<?hh class VectorIterable<Tv> implements Iterable<Vector<Tv>> {}';
    $def = FileParser::FromData($data)->getClass('VectorIterable');
    $this->assertEquals(Vector { 'Iterable' }, $def->getInterfaceNames());
    $this->assertEquals(
      Vector { Vector { 'Vector' } },
      $def->getInterfaceInfo()->map($x ==> $x->getGenericTypes()->map($y ==> $y->getTypeName())),
    );
    $this->assertEquals(
      Vector { 'Iterable<Vector<Tv>>' },
      $def->getInterfaceInfo()->map($x ==> $x->getTypeText()),
    );
  }

  public function testTraitImplements(): void {
    $data = '<?hh interface IFoo {}; trait TFoo implements IFoo {}';
    $def = FileParser::FromData($data)->getTrait('TFoo');
    $this->assertEquals(
      Vector { 'IFoo' },
      $def->getInterfaceNames(),
    );
  }

  public function testUsesTraits(): void {
    $data = '<?hh class Foo { use Herp; use Derp; }';
    $def = FileParser::FromData($data)->getClass('Foo');
    $this->assertEquals(
      Vector { 'Herp', 'Derp' },
      $def->getTraitNames(),
    );
  }

  public function testUsesMultipleTraitsInSingleStatement(): void {
    $data = '<?hh class Foo { use Herp, Derp; }';
    $def = FileParser::FromData($data)->getClass('Foo');
    $this->assertEquals(
      Vector { 'Herp', 'Derp' },
      $def->getTraitNames(),
    );
  }

  public function testUseTraitWithConflictResolution(): void {
    $data =
      "<?php\n".
      "class MyClass {\n".
      "  use Foo, Bar {\n".
      "    Foo::herp insteadof Bar;\n".
      "    Bar::herp as derp;\n".
      "}";
    $def = FileParser::FromData($data)->getClass('MyClass');
    $this->assertEquals(
      Vector { 'Foo', 'Bar' },
      $def->getTraitNames(),
    );
  }

  public function testUsesTraitsInNamespace(): void {
    $data =
      "<?hh\n".
      "namespace MyNamespace;".
      'class Foo { use Herp; use Derp; }';
    $def = FileParser::FromData($data)->getClass('MyNamespace\\Foo');
    $this->assertEquals(
      Vector { 'MyNamespace\\Herp', 'MyNamespace\\Derp' },
      $def->getTraitNames(),
    );
  }

  public function testUsesGenericTrait(): void {
    $data = '<?hh class Foo { use Herp<string>; }';
    $def = FileParser::FromData($data)->getClass('Foo');
    $traits = $def->getTraitGenerics();
    $this->assertCount(1, $traits);
    $this->assertEquals( 'Herp', $traits->firstKey());
    $this->assertEquals(
      Vector { 'string' },
      $traits->firstValue()?->map($a ==> $a->getTypeText()),
    );
  }
}
