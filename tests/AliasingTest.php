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

use type Facebook\DefinitionFinder\FileParser;
use namespace HH\Lib\Vec;

final class AliasingTest extends \PHPUnit_Framework_TestCase {
  public function testSimpleUse(): void {
    $code = "<?hh\n".
      "namespace MyNamespace;\n".
      "use MyOtherNamespace\\Foo;\n".
      'class Bar extends Foo {}';
    $def = FileParser::fromData($code)->getClass('MyNamespace\\Bar');
    $this->assertSame("MyOtherNamespace\\Foo", $def->getParentClassName());
  }

  public function testMultiUse(): void {
    $code = "<?hh\n".
      "use Foo\\Bar, Herp\\Derp;\n".
      'class MyClass extends Bar implements Derp {}';
    $def = FileParser::fromData($code)->getClass('MyClass');
    $this->assertSame("Foo\\Bar", $def->getParentClassName());
    $this->assertEquals(vec["Herp\\Derp"], $def->getInterfaceNames());
  }

  public function testUseWithClassAlias(): void {
    $code = "<?hh\n".
      "namespace MyNamespace;\n".
      "use MyOtherNamespace\\Foo as SuperClass;\n".
      'class Bar extends SuperClass {}';
    $def = FileParser::fromData($code)->getClass('MyNamespace\\Bar');
    $this->assertSame("MyOtherNamespace\\Foo", $def->getParentClassName());
  }

  public function testMultiUseWithClassAlias(): void {
    $code = "<?hh\n".
      "use Foo\\Bar as Baz, Herp\\Derp;\n".
      'class MyClass extends Baz implements Derp {}';
    $def = FileParser::fromData($code)->getClass('MyClass');
    $this->assertSame("Foo\\Bar", $def->getParentClassName());
    $this->assertEquals(vec["Herp\\Derp"], $def->getInterfaceNames());
  }

  public function testUseWithNSAlias(): void {
    $code = "<?hh\n".
      "namespace MyNamespace;\n".
      "use MyOtherNamespace as OtherNS;\n".
      "class Bar extends OtherNS\\Foo{}";
    $def = FileParser::fromData($code)->getClass('MyNamespace\\Bar');
    $this->assertSame("MyOtherNamespace\\Foo", $def->getParentClassName());
  }

  public function testSimpleGroupUse(): void {
    $code = "<?hh\n".
      "namespace MyNamespace;\n".
      "use MyOtherNamespace\\{Foo, Bar};\n".
      "class MyClass implements Foo, Bar{}";
    $def = FileParser::fromData($code)->getClass('MyNamespace\\MyClass');
    $this->assertEquals(
      vec['MyOtherNamespace\\Foo', 'MyOtherNamespace\\Bar'],
      $def->getInterfaceNames(),
    );
  }

  public function testGroupUseWithTrailingComma(): void {
    // Not allowed by typechecker, but allowed by HHVM
    $code = "<?hh\n".
      "namespace MyNamespace;\n".
      "use MyOtherNamespace\\{Foo, Bar,};\n".
      "class MyClass implements Foo, Bar{}";
    $def = FileParser::fromData($code)->getClass('MyNamespace\\MyClass');
    $this->assertEquals(
      vec['MyOtherNamespace\\Foo', 'MyOtherNamespace\\Bar'],
      $def->getInterfaceNames(),
    );
  }

  public function testGroupUseWithAlias(): void {
    $code = "<?hh\n".
      "namespace MyNamespace;\n".
      "use MyOtherNamespace\\{Foo as Herp, Bar as Derp};\n".
      "class MyClass implements Herp, Derp {}";
    $def = FileParser::fromData($code)->getClass('MyNamespace\\MyClass');
    $this->assertEquals(
      vec['MyOtherNamespace\\Foo', 'MyOtherNamespace\\Bar'],
      $def->getInterfaceNames(),
    );
  }

  public function testGroupUseWithSubNamespace(): void {
    $code = "<?hh\n".
      "namespace MyNamespace;\n".
      "use MyOtherNamespace\\{Foo, Bar\Baz};\n".
      "function my_func(Foo \$foo, Bar \$bar, Baz \$baz) {}";
    $def = FileParser::fromData($code)->getFunction('MyNamespace\\my_func');
    $this->assertEquals(
      vec[
        "MyOtherNamespace\\Foo",
        "MyNamespace\\Bar",
        "MyOtherNamespace\\Bar\\Baz",
      ],
      Vec\map($def->getParameters(), $p ==> $p->getTypehint()?->getTypeName()),
    );
  }

  public function testFunctionReturnsAlias(): void {
    $code = "<?hh\n".
      "namespace MyNamespace;\n".
      "use MyOtherNamespace\\Foo;\n".
      "function my_func(): Foo {}";
    $def = FileParser::fromData($code)->getFunction('MyNamespace\\my_func');
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
    $def = FileParser::fromData($code)->getFunction('MyNamespace\\my_func');
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
    $def = FileParser::fromData($code)->getFunction('MyNamespace\\my_func');
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
    $def = FileParser::fromData($code)->getFunction('MyNamespace\\my_func');
    $this->assertEquals(
      vec[
        "MyNamespace\\Foo",
        "MyNamespace\\Bar",
        "MyOtherNamespace\\Baz",
      ],
      Vec\map($def->getParameters(), $p ==> $p->getTypehint()?->getTypeName()),
    );
  }

  public function testUseNamespace(): void {
    $def = FileParser::fromFile(__DIR__.'/data/alias_use_namespace.php')
      ->getFunction('main');
    $this->assertEquals(
      vec["Bar\\Derp", "Foo\\Derp"],
      Vec\map($def->getParameters(), $p ==> $p->getTypehint()?->getTypeName()),
    );
  }

  public function testGroupUseNamespace(): void {
    $code = "<?hh\n".
      "use namespace Prefixes\{Foo, Herp};\n".
      "function my_func(Foo\Bar \$_, Herp\Derp \$_): void {}";
    $def = FileParser::fromData($code)->getFunction('my_func');
    $this->assertEquals(
      vec["Prefixes\\Foo\\Bar", "Prefixes\\Herp\\Derp"],
      Vec\map($def->getParameters(), $p ==> $p->getTypehint()?->getTypeName()),
    );
  }

  public function testUseConflictingHSLNamespace(): void {
    $code = "<?hh\n".
      "use namespace HH\Lib\{Dict, Keyset, Vec};".
      "function my_func(Dict\A \$_, Keyset\A \$_, Vec\A \$_): void {}";
    $def = FileParser::fromData($code)->getFunction('my_func');
    $this->assertEquals(
      vec[
        "HH\\Lib\\Dict\\A",
        "HH\\Lib\\Keyset\\A",
        "HH\\Lib\\Vec\\A",
      ],
      Vec\map($def->getParameters(), $p ==> $p->getTypehint()?->getTypeName()),
    );
  }

  public function testUseType(): void {
    $code = "<?hh\n".
      "use type Foo\\Bar;\n".
      "function my_func(Bar \$_): void {}";
    $def = FileParser::fromData($code)->getFunction('my_func');
    $this->assertEquals(
      vec["Foo\\Bar"],
      Vec\map($def->getParameters(), $p ==> $p->getTypehint()?->getTypeName()),
    );
  }

  public function testGroupUseType(): void {
    $code = "<?hh\n".
      "use type Foo\\{Bar, Baz};\n".
      "function my_func(Bar \$_, Baz \$_): void {}";
    $def = FileParser::fromData($code)->getFunction('my_func');
    $this->assertEquals(
      vec["Foo\\Bar", "Foo\\Baz"],
      Vec\map($def->getParameters(), $p ==> $p->getTypehint()?->getTypeName()),
    );
  }
}
