<?hh // strict
/*
 *  Copyright (c) 2015-present, Facebook, Inc.
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
    $code = "<?hh\n".
      "namespace MyNamespace;\n".
      "use MyOtherNamespace\\Foo;\n".
      'class Bar extends Foo {}';
    $def = FileParser::FromData($code)->getClass('MyNamespace\\Bar');
    $this->assertSame("MyOtherNamespace\\Foo", $def->getParentClassName());
  }

  public function testMultiUse(): void {
    $code = "<?hh\n".
      "use Foo\\Bar, Herp\\Derp;\n".
      'class MyClass extends Bar implements Derp {}';
    $def = FileParser::FromData($code)->getClass('MyClass');
    $this->assertSame("Foo\\Bar", $def->getParentClassName());
    $this->assertEquals(Vector { "Herp\\Derp" }, $def->getInterfaceNames());
  }

  public function testUseWithClassAlias(): void {
    $code = "<?hh\n".
      "namespace MyNamespace;\n".
      "use MyOtherNamespace\\Foo as SuperClass;\n".
      'class Bar extends SuperClass {}';
    $def = FileParser::FromData($code)->getClass('MyNamespace\\Bar');
    $this->assertSame("MyOtherNamespace\\Foo", $def->getParentClassName());
  }

  public function testMultiUseWithClassAlias(): void {
    $code = "<?hh\n".
      "use Foo\\Bar as Baz, Herp\\Derp;\n".
      'class MyClass extends Baz implements Derp {}';
    $def = FileParser::FromData($code)->getClass('MyClass');
    $this->assertSame("Foo\\Bar", $def->getParentClassName());
    $this->assertEquals(Vector { "Herp\\Derp" }, $def->getInterfaceNames());
  }

  public function testUseWithNSAlias(): void {
    $code = "<?hh\n".
      "namespace MyNamespace;\n".
      "use MyOtherNamespace as OtherNS;\n".
      "class Bar extends OtherNS\\Foo{}";
    $def = FileParser::FromData($code)->getClass('MyNamespace\\Bar');
    $this->assertSame("MyOtherNamespace\\Foo", $def->getParentClassName());
  }

  public function testSimpleGroupUse(): void {
    $code = "<?hh\n".
      "namespace MyNamespace;\n".
      "use MyOtherNamespace\\{Foo, Bar};\n".
      "class MyClass implements Foo, Bar{}";
    $def = FileParser::FromData($code)->getClass('MyNamespace\\MyClass');
    $this->assertEquals(
      Vector { 'MyOtherNamespace\\Foo', 'MyOtherNamespace\\Bar' },
      $def->getInterfaceNames(),
    );
  }

  public function testGroupUseWithTrailingComma(): void {
    // Not allowed by typechecker, but allowed by HHVM
    $code = "<?hh\n".
      "namespace MyNamespace;\n".
      "use MyOtherNamespace\\{Foo, Bar,};\n".
      "class MyClass implements Foo, Bar{}";
    $def = FileParser::FromData($code)->getClass('MyNamespace\\MyClass');
    $this->assertEquals(
      Vector { 'MyOtherNamespace\\Foo', 'MyOtherNamespace\\Bar' },
      $def->getInterfaceNames(),
    );
  }

  public function testGroupUseWithAlias(): void {
    $code = "<?hh\n".
      "namespace MyNamespace;\n".
      "use MyOtherNamespace\\{Foo as Herp, Bar as Derp};\n".
      "class MyClass implements Herp, Derp {}";
    $def = FileParser::FromData($code)->getClass('MyNamespace\\MyClass');
    $this->assertEquals(
      Vector { 'MyOtherNamespace\\Foo', 'MyOtherNamespace\\Bar' },
      $def->getInterfaceNames(),
    );
  }

  public function testGroupUseWithSubNamespace(): void {
    $code = "<?hh\n".
      "namespace MyNamespace;\n".
      "use MyOtherNamespace\\{Foo, Bar\Baz};\n".
      "function my_func(Foo \$foo, Bar \$bar, Baz \$baz) {}";
    $def = FileParser::FromData($code)->getFunction('MyNamespace\\my_func');
    $this->assertEquals(
      Vector {
        "MyOtherNamespace\\Foo",
        "MyNamespace\\Bar",
        "MyOtherNamespace\\Bar\\Baz",
      },
      $def->getParameters()->map($p ==> $p->getTypehint()?->getTypeName()),
    );
  }

  public function testFunctionReturnsAlias(): void {
    $code = "<?hh\n".
      "namespace MyNamespace;\n".
      "use MyOtherNamespace\\Foo;\n".
      "function my_func(): Foo {}";
    $def = FileParser::FromData($code)->getFunction('MyNamespace\\my_func');
    $this->assertSame(
      "MyOtherNamespace\\Foo",
      $def->getReturnType()?->getTypeName(),
    );
  }

  public function testFunctionUseIsNotTypeAlias(): void {
    $code = "<?hh\n".
      "namespace MyNamespace;\n".
      "use function MyOtherNamespace\\Foo;\n".
      "function my_func(): Foo {}";
    $def = FileParser::FromData($code)->getFunction('MyNamespace\\my_func');
    $this->assertSame(
      "MyNamespace\\Foo",
      $def->getReturnType()?->getTypeName(),
    );
  }

  public function testConstUseIsNotTypeAlias(): void {
    $code = "<?hh\n".
      "namespace MyNamespace;\n".
      "use const MyOtherNamespace\\Foo;\n".
      "function my_func(): Foo {}";
    $def = FileParser::FromData($code)->getFunction('MyNamespace\\my_func');
    $this->assertSame(
      "MyNamespace\\Foo",
      $def->getReturnType()?->getTypeName(),
    );
  }

  public function testFunctionAndConstGroupUseIsNotTypeAlias(): void {
    $code = "<?hh\n".
      "namespace MyNamespace;\n".
      "use MyOtherNamespace\\{function Foo, const Bar, Baz};\n".
      "function my_func(Foo \$foo, Bar \$bar, Baz \$baz) {}";
    $def = FileParser::FromData($code)->getFunction('MyNamespace\\my_func');
    $this->assertEquals(
      Vector {
        "MyNamespace\\Foo",
        "MyNamespace\\Bar",
        "MyOtherNamespace\\Baz",
      },
      $def->getParameters()->map($p ==> $p->getTypehint()?->getTypeName()),
    );
  }

  public function testUseNamespace(): void {
    $def = FileParser::FromFile(__DIR__.'/data/alias_use_namespace.php')
      ->getFunction('main');
    $this->assertEquals(
      Vector { "Bar\\Derp", "Foo\\Derp" },
      $def->getParameters()->map($p ==> $p->getTypehint()?->getTypeName()),
    );
  }

  public function testGroupUseNamespace(): void {
    $code = "<?hh\n".
      "use namespace Prefixes\{Foo, Herp};\n".
      "function my_func(Foo\Bar \$_, Herp\Derp \$_): void {}";
    $def = FileParser::FromData($code)->getFunction('my_func');
    $this->assertEquals(
      Vector { "Prefixes\\Foo\\Bar", "Prefixes\\Herp\\Derp" },
      $def->getParameters()->map($p ==> $p->getTypehint()?->getTypeName()),
    );
  }
}
