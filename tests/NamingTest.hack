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
use type Facebook\HackTest\DataProvider;
use namespace HH\Lib\{C, Vec};
use function Facebook\FBExpect\expect;

class NamingTest extends \Facebook\HackTest\HackTest {
  public async function testFunctionCalledSelect(): Awaitable<void> {
    // 'select' is a T_SELECT, not a T_STRING
    $data = '<?hh function select() {}';

    // Check that it parses
    $parser = await FileParser::fromDataAsync($data);
    expect($parser->getFunction('select'))->toNotBeNull();
  }

  /** Things that are valid names, but have a weird token type */
  public function specialNameProvider(): array<array<string>> {
    return [
      ['dict'], // HHVM >= 3.13
      ['vec'], // HHVM >= 3.14
      ['keyset'], // HHVM >= 3.15
      ['Category'],
      ['Super'],
      ['Attribute'],
      ['varray'], // HHVM >= 3.19
      ['darray'], // HHVM >= 3.19
    ];
  }

  <<DataProvider('specialNameProvider')>>
  public async function testSpecialReturnType(string $type): Awaitable<void> {
    $data = '<?hh function foo(): '.$type.' {}';
    $parser = await FileParser::fromDataAsync($data);
    $func = $parser->getFunction('foo');
    expect($func->getReturnType()?->getTypeName())->toBeSame($type);
  }

  <<DataProvider('specialNameProvider')>>
  public async function testSpecialNameAsFuncName(
    string $type,
  ): Awaitable<void> {
    $data = '<?hh function '.$type.'(): void {}';
    $parser = await FileParser::fromDataAsync($data);
    $func = $parser->getFunction($type);
    expect($func->getReturnType()?->getTypeName())->toBeSame('void');
    expect($func->getName())->toBeSame($type);
  }

  <<DataProvider('specialNameProvider')>>
  public async function testSpecialNameAsClassName(
    string $type,
  ): Awaitable<void> {
    $data = '<?hh class '.$type.' { }';
    $parser = await FileParser::fromDataAsync($data);
    $class = $parser->getClass($type);
    expect($class)->toNotBeNull();
  }

  <<DataProvider('specialNameProvider')>>
  public async function testSpecialNameAsNamespaceName(
    string $type,
  ): Awaitable<void> {
    $data = '<?hh namespace '.$type.' { class Foo {} }';
    $parser = await FileParser::fromDataAsync($data);
    $class = $parser->getClass($type."\\Foo");
    expect($class)->toNotBeNull();
  }

  <<DataProvider('specialNameProvider')>>
  public async function testSpecialNameAsUsedName(
    string $type,
  ): Awaitable<void> {
    $data = '<?hh use Foo\\'.$type.'; class Herp extends '.$type.' { }';
    $parser = await FileParser::fromDataAsync($data);
    $class = $parser->getClass('Herp');
    expect($class)->toNotBeNull();
  }

  <<DataProvider('specialNameProvider')>>
  public async function testSpecialNameAsUsedAsName(
    string $type,
  ): Awaitable<void> {
    $data = '<?hh use Foo\\Bar as '.$type.'; class Herp extends '.$type.' { }';
    $parser = await FileParser::fromDataAsync($data);
    $class = $parser->getClass('Herp');
    expect($class)->toNotBeNull();
  }

  <<DataProvider('specialNameProvider')>>
  public async function testSpecialNameAsUsedAsConstName(
    string $type,
  ): Awaitable<void> {
    $data = '<?hh const '.$type.' = FOO;';
    $parser = await FileParser::fromDataAsync($data);
    $constants = $parser->getConstantNames();
    expect($constants)->toContain($type);
  }

  <<DataProvider('specialNameProvider')>>
  public async function testSpecialNameAsUsedAsClassConstName(
    string $type,
  ): Awaitable<void> {
    $data = '<?hh class Foo { const int '.$type.' = FOO; }';
    $parser = await FileParser::fromDataAsync($data);
    $constant = C\firstx($parser->getClass('Foo')->getConstants());
    expect($constant->getName())->toBeSame($type);
    expect($constant->getTypehint()?->getTypeText())->toBeSame('int');
  }

  <<DataProvider('specialNameProvider')>>
  public async function testSpecialNameAsUsedAsClassConstDefault(
    string $type,
  ): Awaitable<void> {
    $data = '<?hh class Foo { const int BAR = Baz::'.$type.'; }';
    $parser = await FileParser::fromDataAsync($data);
    $constant = C\firstx($parser->getClass('Foo')->getConstants());
    expect($constant->getName())->toBeSame('BAR');
    expect($constant->getTypehint()?->getTypeName())->toBeSame('int');
    expect($constant->getValue()->getAST()->getCode())->toBeSame('Baz::'.$type);
  }

  public function magicConstantsProvider(): array<(string, string, string)> {
    return [
      tuple('int', '__LINE__', '0'),
      tuple('string', '__CLASS__', "''"),
      tuple('string', '__TRAIT__', "''"),
      tuple('string', '__FILE__', "''"),
      tuple('string', '__DIR__', "''"),
      tuple('string', '__FUNCTION__', "''"),
      tuple('string', '__METHOD__', "''"),
      tuple('string', '__NAMESPACE__', "''"),
      tuple('string', '__COMPILER_FRONTEND__', "''"),
    ];
  }

  /** We need to be able to understand these definitions in the main HHI
   * files.
   *
   */
  <<DataProvider('magicConstantsProvider')>>
  public async function testMagicConstantDefinition(
    string $type,
    string $name,
    string $value,
  ): Awaitable<void> {
    $code = "<?hh // decl\nconst ".$type.' '.$name.' = '.$value.';';
    $const = (await FileParser::fromDataAsync($code))->getConstants()
      |> C\find($$, $c ==> $c->getName() === $name);
    $const = expect($const)->toNotBeNull('const %s was not defined', $name);
    expect($const->getTypehint()?->getTypeText())
      ->toBeSame($type);
    expect(\var_export($const->getValue()->getStaticValue(), true))->toBeSame(
      $value,
    );
  }

  public async function testConstantCalledOn(): Awaitable<void> {
    $data = '<?hh class Foo { const ON = 0; }';

    expect(
      (await FileParser::fromDataAsync($data))
        ->getClass('Foo')
        ->getConstants()
        |> Vec\map($$, $x ==> $x->getName()),
    )->toBeSame(vec['ON']);
  }

  public async function testClassMagicConstant(): Awaitable<void> {
    $data = "<?hh Foo::class;\nclass Foo{}";

    // This could throw because the ; comes after the keyword class
    expect((await FileParser::fromDataAsync($data))->getClass('Foo')->getName())
      ->toBeSame('Foo');
  }

  public async function testClassConstant(): Awaitable<void> {
    $data = "<?hh Herp::DERP;\nclass Foo{}";

    expect((await FileParser::fromDataAsync($data))->getClass('Foo')->getName())
      ->toBeSame('Foo');
  }

  /** The noramlization blacklist shouldn't apply to things we define */
  public async function testNamespacedClassCalledCollection(): Awaitable<void> {
    $data = '<?hh namespace Foo\Bar; class Collection {}';

    expect((await FileParser::fromDataAsync($data))->getClassNames())->toBeSame(
      vec['Foo\Bar\Collection'],
    );
  }

  public async function testNamespaceResolutionDependingOnSourceType(
  ): Awaitable<void> {
    $php = "<?php namespace Foo; class MyClass extends Collection {}";
    $hack = "<?hh namespace Foo; class MyClass extends Collection {}";

    $php_class = (await FileParser::fromDataAsync($php))->getClass(
      "Foo\\MyClass",
    );
    $hack_class = (await FileParser::fromDataAsync($hack))->getClass(
      "Foo\\MyClass",
    );

    // We used to distinguish between PHP and Hack files here, but not anymore,
    // since HHVM no longer officially supports PHP.
    expect($php_class->getParentClassName())->toBeSame("HH\\Collection");
    expect($hack_class->getParentClassName())->toBeSame('HH\\Collection');
  }

  public async function testScalarParameterInNamespace(): Awaitable<void> {
    // This is correct for PHP7, not for PHP5 though. If you're using Hack,
    // you're more likely to be using scalar typehints than not.
    $php = '<?hh namespace Foo; function myfunc(): string {}';
    $hack = '<?hh namespace Foo; function myfunc(): string {}';

    $php_func = (await FileParser::fromDataAsync($php))->getFunction(
      "Foo\\myfunc",
    );
    $hack_func = (await FileParser::fromDataAsync($hack))->getFunction(
      "Foo\\myfunc",
    );

    expect($php_func->getReturnType()?->getTypeName())->toBeSame('string');
    expect($hack_func->getReturnType()?->getTypeName())->toBeSame('string');
  }

  public async function testReturnsThisInNamespace(): Awaitable<void> {
    $code = "<?hh\n".
      "namespace Foo;\n".
      "class MyClass {\n".
      "  function foo(): this { }\n".
      "}";
    $parser = await FileParser::fromDataAsync($code);
    $class = $parser->getClass("Foo\\MyClass");
    $method = $class->getMethods()[0];
    expect($method->getReturnType()?->getTypeName())->toBeSame('this');
  }

  public async function testReturnsClassGenericInNamespace(): Awaitable<void> {
    $code = "<?hh\n".
      "namespace Foo;\n".
      "class MyClass<T> {\n".
      "  function foo(): T { }\n".
      "}";
    $parser = await FileParser::fromDataAsync($code);
    $class = $parser->getClass("Foo\\MyClass");
    $method = $class->getMethods()[0];
    expect($method->getReturnType()?->getTypeName())->toBeSame('T');
  }

  public async function testReturnsNullableClassGenericInNamespace(
  ): Awaitable<void> {
    $code = "<?hh\n".
      "namespace Foo;\n".
      "class MyClass<T> {\n".
      "  function foo(): ?T { }\n".
      "}";
    $parser = await FileParser::fromDataAsync($code);
    $class = $parser->getClass("Foo\\MyClass");
    $method = $class->getMethods()[0];
    expect($method->getReturnType()?->getTypeName())->toBeSame('T');
    expect($method->getReturnType()?->isNullable())->toBeTrue();
  }

  public async function testReturnsMethodGenericInNamespace(): Awaitable<void> {
    $code = "<?hh\n".
      "namespace Foo;\n".
      "class MyClass {\n".
      "  function foo<T>(): T { }\n".
      "}";
    $parser = await FileParser::fromDataAsync($code);
    $class = $parser->getClass("Foo\\MyClass");
    $method = $class->getMethods()[0];
    expect($method->getReturnType()?->getTypeName())->toBeSame('T');
  }

  /**
   * Make sure that method generics are added to class generics, instead of
   * replacing them.
   */
  public async function testClassGenericsInMethodWithGenerics(
  ): Awaitable<void> {
    $code = "<?hh\n".
      "namespace Foo;\n".
      "class MyClass<TClassGeneric> {\n".
      "  function foo<TFunctionGeneric>(\n".
      "    TFunctionGeneric \$p,\n".
      "  ): TClassGeneric {}\n".
      "}\n";
    $parser = await FileParser::fromDataAsync($code);
    $class = $parser->getClass("Foo\\MyClass");
    $method = $class->getMethods()[0];
    expect($method->getReturnType()?->getTypeName())->toBeSame('TClassGeneric');
    expect($method->getParameters()[0]->getTypehint()?->getTypeName())
      ->toBeSame('TFunctionGeneric');
  }

  public async function testTakesMethodGenericInNamespace(): Awaitable<void> {
    $code = "<?hh\n".
      "namespace Foo;\n".
      "class MyClass {\n".
      "  function foo<T>(T \$bar): Awaitable<void> { }\n".
      "}";
    $parser = await FileParser::fromDataAsync($code);
    $class = $parser->getClass("Foo\\MyClass");
    $method = $class->getMethods()[0];
    expect($method->getParameters()[0]->getTypehint()?->getTypeName())
      ->toBeSame('T');
  }
}
