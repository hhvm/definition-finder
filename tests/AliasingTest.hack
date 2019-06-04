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
use function Facebook\FBExpect\expect;
use namespace HH\Lib\Vec;

final class AliasingTest extends \Facebook\HackTest\HackTest {
  public async function testSimpleUse(): Awaitable<void> {
    $code = "<?hh\n".
      "namespace MyNamespace;\n".
      "use MyOtherNamespace\\Foo;\n".
      'class Bar extends Foo {}';
    $def = (await FileParser::fromDataAsync($code))->getClass(
      'MyNamespace\\Bar',
    );
    expect($def->getParentClassName())->toBeSame("MyOtherNamespace\\Foo");
  }

  public async function testMultiUse(): Awaitable<void> {
    $code = "<?hh\n".
      "use Foo\\Bar, Herp\\Derp;\n".
      'class MyClass extends Bar implements Derp {}';
    $def = (await FileParser::fromDataAsync($code))->getClass('MyClass');
    expect($def->getParentClassName())->toBeSame("Foo\\Bar");
    expect($def->getInterfaceNames())->toBeSame(vec["Herp\\Derp"]);
  }

  public async function testUseWithClassAlias(): Awaitable<void> {
    $code = "<?hh\n".
      "namespace MyNamespace;\n".
      "use MyOtherNamespace\\Foo as SuperClass;\n".
      'class Bar extends SuperClass {}';
    $def = (await FileParser::fromDataAsync($code))->getClass(
      'MyNamespace\\Bar',
    );
    expect($def->getParentClassName())->toBeSame("MyOtherNamespace\\Foo");
  }

  public async function testMultiUseWithClassAlias(): Awaitable<void> {
    $code = "<?hh\n".
      "use Foo\\Bar as Baz, Herp\\Derp;\n".
      'class MyClass extends Baz implements Derp {}';
    $def = (await FileParser::fromDataAsync($code))->getClass('MyClass');
    expect($def->getParentClassName())->toBeSame("Foo\\Bar");
    expect($def->getInterfaceNames())->toBeSame(vec["Herp\\Derp"]);
  }

  public async function testUseWithNSAlias(): Awaitable<void> {
    $code = "<?hh\n".
      "namespace MyNamespace;\n".
      "use MyOtherNamespace as OtherNS;\n".
      "class Bar extends OtherNS\\Foo{}";
    $def = (await FileParser::fromDataAsync($code))->getClass(
      'MyNamespace\\Bar',
    );
    expect($def->getParentClassName())->toBeSame("MyOtherNamespace\\Foo");
  }

  public async function testSimpleGroupUse(): Awaitable<void> {
    $code = "<?hh\n".
      "namespace MyNamespace;\n".
      "use MyOtherNamespace\\{Foo, Bar};\n".
      "class MyClass implements Foo, Bar{}";
    $def = (await FileParser::fromDataAsync($code))->getClass(
      'MyNamespace\\MyClass',
    );
    expect($def->getInterfaceNames())->toBeSame(
      vec['MyOtherNamespace\\Foo', 'MyOtherNamespace\\Bar'],
    );
  }

  public async function testGroupUseWithTrailingComma(): Awaitable<void> {
    // Not allowed by typechecker, but allowed by HHVM
    $code = "<?hh\n".
      "namespace MyNamespace;\n".
      "use MyOtherNamespace\\{Foo, Bar,};\n".
      "class MyClass implements Foo, Bar{}";
    $def = (await FileParser::fromDataAsync($code))->getClass(
      'MyNamespace\\MyClass',
    );
    expect($def->getInterfaceNames())->toBeSame(
      vec['MyOtherNamespace\\Foo', 'MyOtherNamespace\\Bar'],
    );
  }

  public async function testGroupUseWithAlias(): Awaitable<void> {
    $code = "<?hh\n".
      "namespace MyNamespace;\n".
      "use MyOtherNamespace\\{Foo as Herp, Bar as Derp};\n".
      "class MyClass implements Herp, Derp {}";
    $def = (await FileParser::fromDataAsync($code))->getClass(
      'MyNamespace\\MyClass',
    );
    expect($def->getInterfaceNames())->toBeSame(
      vec['MyOtherNamespace\\Foo', 'MyOtherNamespace\\Bar'],
    );
  }

  public async function testGroupUseWithSubNamespace(): Awaitable<void> {
    $code = "<?hh\n".
      "namespace MyNamespace;\n".
      "use MyOtherNamespace\\{Foo, Bar\Baz};\n".
      "function my_func(Foo \$foo, Bar \$bar, Baz \$baz) {}";
    $def = (await FileParser::fromDataAsync($code))->getFunction(
      'MyNamespace\\my_func',
    );
    expect(
      Vec\map($def->getParameters(), $p ==> $p->getTypehint()?->getTypeName()),
    )->toBeSame(
      vec[
        "MyOtherNamespace\\Foo",
        "MyNamespace\\Bar",
        "MyOtherNamespace\\Bar\\Baz",
      ],
    );
  }

  public async function testFunctionReturnsAlias(): Awaitable<void> {
    $code = "<?hh\n".
      "namespace MyNamespace;\n".
      "use MyOtherNamespace\\Foo;\n".
      "function my_func(): Foo {}";
    $def = (await FileParser::fromDataAsync($code))->getFunction(
      'MyNamespace\\my_func',
    );
    expect($def->getReturnType()?->getTypeName())->toBeSame(
      "MyOtherNamespace\\Foo",
    );
  }

  public async function testFunctionUseIsNotTypeAlias(): Awaitable<void> {
    $code = "<?hh\n".
      "namespace MyNamespace;\n".
      "use function MyOtherNamespace\\Foo;\n".
      "function my_func(): Foo {}";
    $def = (await FileParser::fromDataAsync($code))->getFunction(
      'MyNamespace\\my_func',
    );
    expect($def->getReturnType()?->getTypeName())->toBeSame("MyNamespace\\Foo");
  }

  public async function testConstUseIsNotTypeAlias(): Awaitable<void> {
    $code = "<?hh\n".
      "namespace MyNamespace;\n".
      "use const MyOtherNamespace\\Foo;\n".
      "function my_func(): Foo {}";
    $def = (await FileParser::fromDataAsync($code))->getFunction(
      'MyNamespace\\my_func',
    );
    expect($def->getReturnType()?->getTypeName())->toBeSame("MyNamespace\\Foo");
  }

  public async function testFunctionAndConstGroupUseIsNotTypeAlias(
  ): Awaitable<void> {
    $code = "<?hh\n".
      "namespace MyNamespace;\n".
      "use MyOtherNamespace\\{function Foo, const Bar, Baz};\n".
      "function my_func(Foo \$foo, Bar \$bar, Baz \$baz) {}";
    $def = (await FileParser::fromDataAsync($code))->getFunction(
      'MyNamespace\\my_func',
    );
    expect(
      Vec\map($def->getParameters(), $p ==> $p->getTypehint()?->getTypeName()),
    )->toBeSame(
      vec[
        "MyNamespace\\Foo",
        "MyNamespace\\Bar",
        "MyOtherNamespace\\Baz",
      ],
    );
  }

  public async function testUseNamespace(): Awaitable<void> {
    $def = (
      await FileParser::fromFileAsync(__DIR__.'/data/alias_use_namespace.php')
    )
      ->getFunction('main');
    expect(
      Vec\map($def->getParameters(), $p ==> $p->getTypehint()?->getTypeName()),
    )->toBeSame(vec["Bar\\Derp", "Foo\\Derp"]);
  }

  public async function testGroupUseNamespace(): Awaitable<void> {
    $code = "<?hh\n".
      "use namespace Prefixes\{Foo, Herp};\n".
      "function my_func(Foo\Bar \$_, Herp\Derp \$_): Awaitable<void> {}";
    $def = (await FileParser::fromDataAsync($code))->getFunction('my_func');
    expect(
      Vec\map($def->getParameters(), $p ==> $p->getTypehint()?->getTypeName()),
    )->toBeSame(vec["Prefixes\\Foo\\Bar", "Prefixes\\Herp\\Derp"]);
  }

  public async function testUseConflictingHSLNamespace(): Awaitable<void> {
    $code = "<?hh\n".
      "use namespace HH\Lib\{Dict, Keyset, Vec};".
      "function my_func(Dict\A \$_, Keyset\A \$_, Vec\A \$_): Awaitable<void> {}";
    $def = (await FileParser::fromDataAsync($code))->getFunction('my_func');
    expect(
      Vec\map($def->getParameters(), $p ==> $p->getTypehint()?->getTypeName()),
    )->toBeSame(
      vec[
        "HH\\Lib\\Dict\\A",
        "HH\\Lib\\Keyset\\A",
        "HH\\Lib\\Vec\\A",
      ],
    );
  }

  public async function testUseType(): Awaitable<void> {
    $code = "<?hh\n".
      "use type Foo\\Bar;\n".
      "function my_func(Bar \$_): Awaitable<void> {}";
    $def = (await FileParser::fromDataAsync($code))->getFunction('my_func');
    expect(
      Vec\map($def->getParameters(), $p ==> $p->getTypehint()?->getTypeName()),
    )->toBeSame(vec["Foo\\Bar"]);
  }

  public async function testGroupUseType(): Awaitable<void> {
    $code = "<?hh\n".
      "use type Foo\\{Bar, Baz};\n".
      "function my_func(Bar \$_, Baz \$_): Awaitable<void> {}";
    $def = (await FileParser::fromDataAsync($code))->getFunction('my_func');
    expect(
      Vec\map($def->getParameters(), $p ==> $p->getTypehint()?->getTypeName()),
    )->toBeSame(vec["Foo\\Bar", "Foo\\Baz"]);
  }
}
