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

final class AliasingTest extends \PHPUnit_Framework_TestCase {
  public function testSimpleUse(): void {
    $code =
      "<?hh\n".
      "namespace MyNamespace;\n".
      "use MyOtherNamespace\\Foo;\n".
      'class Bar extends Foo {}';
    $def = FileParser::FromData($code)->getClass('MyNamespace\\Bar');
    $this->assertSame(
      "MyOtherNamespace\\Foo",
      $def->getParentClassName(),
    );
  }

  public function testUseWithClassAlias(): void {
    $code =
      "<?hh\n".
      "namespace MyNamespace;\n".
      "use MyOtherNamespace\\Foo as SuperClass;\n".
      'class Bar extends SuperClass {}';
    $def = FileParser::FromData($code)->getClass('MyNamespace\\Bar');
    $this->assertSame(
      "MyOtherNamespace\\Foo",
      $def->getParentClassName(),
    );
  }

  public function testUseWithNSAlias(): void {
    $code =
      "<?hh\n".
      "namespace MyNamespace;\n".
      "use MyOtherNamespace as OtherNS;\n".
      "class Bar extends OtherNS\\Foo{}";
    $def = FileParser::FromData($code)->getClass('MyNamespace\\Bar');
    $this->assertSame(
      "MyOtherNamespace\\Foo",
      $def->getParentClassName(),
    );
  }

  public function testSimpleGroupUse(): void {
    $code =
      "<?hh\n".
      "namespace MyNamespace;\n".
      "use MyOtherNamespace\\{Foo, Bar};\n".
      "class MyClass implements Foo, Bar{}";
    $def = FileParser::FromData($code)->getClass('MyNamespace\\MyClass');
    $this->assertEquals(
      Vector { 'MyOtherNamespace\\Foo', 'MyOtherNamespace\\Bar' },
      $def->getInterfaceNames(),
    );
  }
}
