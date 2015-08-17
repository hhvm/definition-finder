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
    $data = '<?hh class Foo implements Iterable<Tk,Tv> {}';
    $def = FileParser::FromData($data)->getClass('Foo');
    $this->assertEquals(Vector { 'Iterable' }, $def->getInterfaceNames());
    $this->assertEquals(
      Vector { 'Iterable<Tk,Tv>' },
      $def->getInterfaceInfo()->map($x ==> $x->getTypeText()),
    );
  }
}
