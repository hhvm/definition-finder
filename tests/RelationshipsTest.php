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

use type Facebook\DefinitionFinder\LegacyFileParser;
use namespace HH\Lib\{C, Vec};

class RelationshipsTest extends \PHPUnit_Framework_TestCase {
  public function testClassExtends(): void {
    $data = '<?hh class Foo extends Bar {}';
    $def = LegacyFileParser::FromData($data)->getClass('Foo');
    $this->assertSame('Bar', $def->getParentClassName());
    $this->assertEmpty($def->getInterfaceNames());
  }

  public function testClassImplements(): void {
    $data = '<?hh class Foo implements Bar, Baz {}';
    $def = LegacyFileParser::FromData($data)->getClass('Foo');
    $this->assertEquals(vec['Bar', 'Baz'], $def->getInterfaceNames());
    $this->assertNull($def->getParentClassName());
  }

  public function testInterfaceExtends(): void {
    $data = '<?hh interface Foo extends Bar, Baz {}';
    $def = LegacyFileParser::FromData($data)->getInterface('Foo');
    $this->assertEquals(vec['Bar', 'Baz'], $def->getInterfaceNames());
    $this->assertNull($def->getParentClassName());
  }

  public function testClassExtendsAndImplements(): void {
    $data = '<?hh class Foo extends Bar implements Herp, Derp {}';
    $def = LegacyFileParser::FromData($data)->getClass('Foo');
    $this->assertSame('Bar', $def->getParentClassName());
    $this->assertEquals(vec['Herp', 'Derp'], $def->getInterfaceNames());
  }

  public function testClassExtendsGeneric(): void {
    $data = '<?hh class Foo extends Bar<Baz> {}';
    $def = LegacyFileParser::FromData($data)->getClass('Foo');
    $this->assertSame('Bar', $def->getParentClassName());
    $this->assertSame('Bar<Baz>', $def->getParentClassInfo()?->getTypeText());
  }

  public function testClassImplementsGenerics(): void {
    $data = '<?hh class Foo implements KeyedIterable<Tk,Tv> {}';
    $def = LegacyFileParser::FromData($data)->getClass('Foo');
    $this->assertEquals(vec['KeyedIterable'], $def->getInterfaceNames());
    $this->assertEquals(
      vec['KeyedIterable<Tk,Tv>'],
      Vec\map($def->getInterfaceInfo(), $x ==> $x->getTypeText()),
    );
  }

  public function testClassImplementsNestedGenerics(): void {
    $data = '<?hh class VectorIterable<Tv> implements Iterable<vec<Tv>> {}';
    $def = LegacyFileParser::FromData($data)->getClass('VectorIterable');
    $this->assertEquals(vec['Iterable'], $def->getInterfaceNames());
    $this->assertEquals(
      vec[vec['Vector']],
      Vec\map(
        $def->getInterfaceInfo(),
        $x ==> Vec\map($x->getGenericTypes(), $y ==> $y->getTypeName()),
      ),
    );
    $this->assertEquals(
      vec['Iterable<vec<Tv>>'],
      Vec\map($def->getInterfaceInfo(), $x ==> $x->getTypeText()),
    );
  }

  public function testTraitImplements(): void {
    $data = '<?hh interface IFoo {}; trait TFoo implements IFoo {}';
    $def = LegacyFileParser::FromData($data)->getTrait('TFoo');
    $this->assertEquals(vec['IFoo'], $def->getInterfaceNames());
  }

  public function testUsesTraits(): void {
    $data = '<?hh class Foo { use Herp; use Derp; }';
    $def = LegacyFileParser::FromData($data)->getClass('Foo');
    $this->assertEquals(vec['Herp', 'Derp'], $def->getTraitNames());
  }

  public function testUsesMultipleTraitsInSingleStatement(): void {
    $data = '<?hh class Foo { use Herp, Derp; }';
    $def = LegacyFileParser::FromData($data)->getClass('Foo');
    $this->assertEquals(vec['Herp', 'Derp'], $def->getTraitNames());
  }

  public function testUseTraitWithConflictResolution(): void {
    $data = "<?php\n".
      "class MyClass {\n".
      "  use Foo, Bar {\n".
      "    Foo::herp insteadof Bar;\n".
      "    Bar::herp as derp;\n".
      "}";
    $def = LegacyFileParser::FromData($data)->getClass('MyClass');
    $this->assertEquals(vec['Foo', 'Bar'], $def->getTraitNames());
  }

  public function testUsesTraitsInNamespace(): void {
    $data =
      "<?hh\n"."namespace MyNamespace;".'class Foo { use Herp; use Derp; }';
    $def = LegacyFileParser::FromData($data)->getClass('MyNamespace\\Foo');
    $this->assertEquals(
      vec['MyNamespace\\Herp', 'MyNamespace\\Derp'],
      $def->getTraitNames(),
    );
  }

  public function testUsesGenericTrait(): void {
    $data = '<?hh class Foo { use Herp<string>; }';
    $def = LegacyFileParser::FromData($data)->getClass('Foo');
    $traits = $def->getTraitGenerics();
    $this->assertEquals(1, C\count($traits));
    $this->assertEquals('Herp', C\first_key($traits));
    $this->assertEquals(
      vec['string'],
      Vec\map(C\first($traits) ?? vec[], $a ==> $a->getTypeText()),
    );
  }
}
