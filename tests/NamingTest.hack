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
  public function testFunctionCalledSelect(): void {
    // 'select' is a T_SELECT, not a T_STRING
    $data = '<?hh function select() {}';

    // Check that it parses
    $parser = FileParser::fromData($data);
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
      ['inout'], // HHVM >= 3.21
    ];
  }

  <<DataProvider('specialNameProvider')>>
  public function testSpecialReturnType(string $type): void {
    $data = '<?php function foo(): '.$type.' {}';
    $parser = FileParser::fromData($data);
    $func = $parser->getFunction('foo');
    expect($func->getReturnType()?->getTypeName())->toBeSame($type);
  }

  <<DataProvider('specialNameProvider')>>
  public function testSpecialNameAsFuncName(string $type): void {
    $data = '<?php function '.$type.'(): void {}';
    $parser = FileParser::fromData($data);
    $func = $parser->getFunction($type);
    expect($func->getReturnType()?->getTypeName())->toBeSame('void');
    expect($func->getName())->toBeSame($type);
  }

  <<DataProvider('specialNameProvider')>>
  public function testSpecialNameAsClassName(string $type): void {
    $data = '<?php class '.$type.' { }';
    $parser = FileParser::fromData($data);
    $class = $parser->getClass($type);
    expect($class)->toNotBeNull();
  }

  <<DataProvider('specialNameProvider')>>
  public function testSpecialNameAsNamespaceName(string $type): void {
    $data = '<?php namespace '.$type.' { class Foo {} }';
    $parser = FileParser::fromData($data);
    $class = $parser->getClass($type."\\Foo");
    expect($class)->toNotBeNull();
  }

  <<DataProvider('specialNameProvider')>>
  public function testSpecialNameAsUsedName(string $type): void {
    $data = '<?php use Foo\\'.$type.'; class Herp extends '.$type.' { }';
    $parser = FileParser::fromData($data);
    $class = $parser->getClass('Herp');
    expect($class)->toNotBeNull();
  }

  <<DataProvider('specialNameProvider')>>
  public function testSpecialNameAsUsedAsName(string $type): void {
    $data = '<?php use Foo\\Bar as '.$type.'; class Herp extends '.$type.' { }';
    $parser = FileParser::fromData($data);
    $class = $parser->getClass('Herp');
    expect($class)->toNotBeNull();
  }

  <<DataProvider('specialNameProvider')>>
  public function testSpecialNameAsUsedAsConstName(string $type): void {
    $data = '<?php const '.$type.' = FOO;';
    $parser = FileParser::fromData($data);
    $constants = $parser->getConstantNames();
    expect($constants)->toContain($type);
  }

  <<DataProvider('specialNameProvider')>>
  public function testSpecialNameAsUsedAsClassConstName(string $type): void {
    $data = '<?php class Foo { const int '.$type.' = FOO; }';
    $parser = FileParser::fromData($data);
    $constant = C\firstx($parser->getClass('Foo')->getConstants());
    expect($constant->getName())->toBeSame($type);
    expect($constant->getTypehint()?->getTypeText())->toBeSame('int');
  }

  <<DataProvider('specialNameProvider')>>
  public function testSpecialNameAsUsedAsClassConstDefault(string $type): void {
    $data = '<?php class Foo { const int BAR = Baz::'.$type.'; }';
    $parser = FileParser::fromData($data);
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
  public function testMagicConstantDefinition(
    string $type,
    string $name,
    string $value,
  ): void {
    $code = "<?hh // decl\nconst ".$type.' '.$name.' = '.$value.';';
    $const = FileParser::fromData($code)->getConstants()
      |> C\find($$, $c ==> $c->getName() === $name);
    $const = expect($const)->toNotBeNull('const %s was not defined', $name);
    expect($const->getTypehint()?->getTypeText())
      ->toBeSame($type);
    expect(\var_export($const->getValue()->getStaticValue(), true))->toBeSame(
      $value,
    );
  }

  public function testConstantCalledOn(): void {
    $data = '<?hh class Foo { const ON = 0; }';

    expect(
      FileParser::fromData($data)
        ->getClass('Foo')
        ->getConstants()
        |> Vec\map($$, $x ==> $x->getName()),
    )->toBeSame(vec['ON']);
  }

  public function testClassMagicConstant(): void {
    $data = "<?hh Foo::class;\nclass Foo{}";

    // This could throw because the ; comes after the keyword class
    expect(FileParser::fromData($data)->getClass('Foo')->getName())->toBeSame(
      'Foo',
    );
  }

  public function testClassConstant(): void {
    $data = "<?hh Herp::DERP;\nclass Foo{}";

    expect(FileParser::fromData($data)->getClass('Foo')->getName())->toBeSame(
      'Foo',
    );
  }

  /** The noramlization blacklist shouldn't apply to things we define */
  public function testNamespacedClassCalledCollection(): void {
    $data = '<?php namespace Foo\Bar; class Collection {}';

    expect(FileParser::fromData($data)->getClassNames())->toBeSame(
      vec['Foo\Bar\Collection'],
    );
  }

  public function testNamespaceResolutionDependingOnSourceType(): void {
    $php = "<?php namespace Foo; class MyClass extends Collection {}";
    $hack = "<?hh namespace Foo; class MyClass extends Collection {}";

    $php_class = FileParser::fromData($php)->getClass("Foo\\MyClass");
    $hack_class = FileParser::fromData($hack)->getClass("Foo\\MyClass");

    expect($php_class->getParentClassName())->toBeSame("Foo\\Collection");
    expect($hack_class->getParentClassName())->toBeSame('Collection');
  }

  public function testScalarParameterInNamespace(): void {
    // This is correct for PHP7, not for PHP5 though. If you're using Hack,
    // you're more likely to be using scalar typehints than not.
    $php = '<?php namespace Foo; function myfunc(): string {}';
    $hack = '<?hh namespace Foo; function myfunc(): string {}';

    $php_func = FileParser::fromData($php)->getFunction("Foo\\myfunc");
    $hack_func = FileParser::fromData($hack)->getFunction("Foo\\myfunc");

    expect($php_func->getReturnType()?->getTypeName())->toBeSame('string');
    expect($hack_func->getReturnType()?->getTypeName())->toBeSame('string');
  }

  public function testReturnsThisInNamespace(): void {
    $code = "<?hh\n".
      "namespace Foo;\n".
      "class MyClass {\n".
      "  function foo(): this { }\n".
      "}";
    $parser = FileParser::fromData($code);
    $class = $parser->getClass("Foo\\MyClass");
    $method = $class->getMethods()[0];
    expect($method->getReturnType()?->getTypeName())->toBeSame('this');
  }

  public function testReturnsClassGenericInNamespace(): void {
    $code = "<?hh\n".
      "namespace Foo;\n".
      "class MyClass<T> {\n".
      "  function foo(): T { }\n".
      "}";
    $parser = FileParser::fromData($code);
    $class = $parser->getClass("Foo\\MyClass");
    $method = $class->getMethods()[0];
    expect($method->getReturnType()?->getTypeName())->toBeSame('T');
  }

  public function testReturnsNullableClassGenericInNamespace(): void {
    $code = "<?hh\n".
      "namespace Foo;\n".
      "class MyClass<T> {\n".
      "  function foo(): ?T { }\n".
      "}";
    $parser = FileParser::fromData($code);
    $class = $parser->getClass("Foo\\MyClass");
    $method = $class->getMethods()[0];
    expect($method->getReturnType()?->getTypeName())->toBeSame('T');
    expect($method->getReturnType()?->isNullable())->toBeTrue();
  }

  public function testReturnsMethodGenericInNamespace(): void {
    $code = "<?hh\n".
      "namespace Foo;\n".
      "class MyClass {\n".
      "  function foo<T>(): T { }\n".
      "}";
    $parser = FileParser::fromData($code);
    $class = $parser->getClass("Foo\\MyClass");
    $method = $class->getMethods()[0];
    expect($method->getReturnType()?->getTypeName())->toBeSame('T');
  }

  /**
   * Make sure that method generics are added to class generics, instead of
   * replacing them.
   */
  public function testClassGenericsInMethodWithGenerics(): void {
    $code = "<?hh\n".
      "namespace Foo;\n".
      "class MyClass<TClassGeneric> {\n".
      "  function foo<TFunctionGeneric>(\n".
      "    TFunctionGeneric \$p,\n".
      "  ): TClassGeneric {}";
    $parser = FileParser::fromData($code);
    $class = $parser->getClass("Foo\\MyClass");
    $method = $class->getMethods()[0];
    expect($method->getReturnType()?->getTypeName())->toBeSame('TClassGeneric');
    expect($method->getParameters()[0]->getTypehint()?->getTypeName())
      ->toBeSame('TFunctionGeneric');
  }

  public function testTakesMethodGenericInNamespace(): void {
    $code = "<?hh\n".
      "namespace Foo;\n".
      "class MyClass {\n".
      "  function foo<T>(T \$bar): void { }\n".
      "}";
    $parser = FileParser::fromData($code);
    $class = $parser->getClass("Foo\\MyClass");
    $method = $class->getMethods()[0];
    expect($method->getParameters()[0]->getTypehint()?->getTypeName())
      ->toBeSame('T');
  }
}
