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
use function Facebook\FBExpect\expect;

class NamingTest extends \PHPUnit_Framework_TestCase {
  public function testFunctionCalledSelect(): void {
    // 'select' is a T_SELECT, not a T_STRING
    $data = '<?hh function select() {}';

    // Check that it parses
    $parser = LegacyFileParser::FromData($data);
    $this->assertNotNull($parser->getFunction('select'));
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

  /** @dataProvider specialNameProvider */
  public function testSpecialReturnType(string $type): void {
    $data = '<?hh function foo(): '.$type.' {}';
    $parser = LegacyFileParser::FromData($data);
    $func = $parser->getFunction('foo');
    $this->assertSame($type, $func->getReturnType()?->getTypeName());
  }

  /** @dataProvider specialNameProvider */
  public function testSpecialNameAsFuncName(string $type): void {
    $data = '<?hh function '.$type.'(): void {}';
    $parser = LegacyFileParser::FromData($data);
    $func = $parser->getFunction($type);
    $this->assertSame('void', $func->getReturnType()?->getTypeName());
    $this->assertSame($type, $func->getName());
  }

  /** @dataProvider specialNameProvider */
  public function testSpecialNameAsClassName(string $type): void {
    $data = '<?hh class '.$type.' { }';
    $parser = LegacyFileParser::FromData($data);
    $class = $parser->getClass($type);
    $this->assertNotNull($class);
  }

  /** @dataProvider specialNameProvider */
  public function testSpecialNameAsNamespaceName(string $type): void {
    $data = '<?hh namespace '.$type.' { class Foo {} }';
    $parser = LegacyFileParser::FromData($data);
    $class = $parser->getClass($type."\\Foo");
    $this->assertNotNull($class);
  }

  /** @dataProvider specialNameProvider */
  public function testSpecialNameAsUsedName(string $type): void {
    $data = '<?hh use Foo\\'.$type.'; class Herp extends '.$type.' { }';
    $parser = LegacyFileParser::FromData($data);
    $class = $parser->getClass('Herp');
    $this->assertNotNull($class);
  }

  /** @dataProvider specialNameProvider */
  public function testSpecialNameAsUsedAsName(string $type): void {
    $data = '<?hh use Foo\\Bar as '.$type.'; class Herp extends '.$type.' { }';
    $parser = LegacyFileParser::FromData($data);
    $class = $parser->getClass('Herp');
    $this->assertNotNull($class);
  }

  /**
  * @dataProvider specialNameProvider
  */
  public function testSpecialNameAsUsedAsConstName(string $type): void {
    $data = '<?hh const '.$type.' = FOO;';
    $parser = LegacyFileParser::FromData($data);
    $constants = $parser->getConstantNames();
    expect($constants)->toContain($type);
  }

  /**
  * @dataProvider specialNameProvider
  */
  public function testSpecialNameAsUsedAsClassConstName(string $type): void {
    $data = '<?hh class Foo { const int '.$type.' = FOO; }';
    $parser = LegacyFileParser::FromData($data);
    $constant = C\firstx($parser->getClass('Foo')->getConstants());
    expect($constant->getName())->toBeSame($type);
    expect($constant->getTypehint()?->getTypeText())->toBeSame('int');
  }

  /**
  * @dataProvider specialNameProvider
  */
  public function testSpecialNameAsUsedAsClassConstDefault(string $type): void {
    $data = '<?hh class Foo { const int BAR = Baz::'.$type.'; }';
    $parser = LegacyFileParser::FromData($data);
    $constant = C\firstx($parser->getClass('Foo')->getConstants());
    expect($constant->getName())->toBeSame('BAR');
    expect($constant->getTypehint()?->getTypeName())->toBeSame('int');
    expect($constant->getValue())->toBeSame('Baz::'.$type);
  }

  public function magicConstantsProvider(
  ): array<(string, string, string)> {
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
   * @dataProvider magicConstantsProvider
   */
  public function testMagicConstantDefinition(
    string $type,
    string $name,
    string $value,
  ): void {
    $code = "<?hh // decl\nconst ".$type.' '.$name.' = '.$value.';';
    $const = LegacyFileParser::FromData($code)->getConstants()
      |> C\find($$, $c ==> $c->getName() === $name);
    $const = expect($const)->toNotBeNull(
      'const %s was not defined',
      $name,
    );
    expect($const->getTypehint()?->getTypeText())
      ->toBeSame($type);
    expect($const->getValue())->toBeSame($value);
  }

  public function testConstantCalledOn(): void {
    $data = '<?hh class Foo { const ON = 0; }';

    $this->assertEquals(
      vec['ON'],
      LegacyFileParser::FromData($data)
        ->getClass('Foo')
        ->getConstants()
        |> Vec\map($$, $x ==> $x->getName()),
    );
  }

  public function testClassMagicConstant(): void {
    $data = "<?hh Foo::class;\nclass Foo{}";

    // This could throw because the ; comes after the keyword class
    $this->assertEquals(
      'Foo',
      LegacyFileParser::FromData($data)->getClass('Foo')->getName(),
    );
  }

  public function testClassConstant(): void {
    $data = "<?hh Herp::DERP;\nclass Foo{}";

    $this->assertEquals(
      'Foo',
      LegacyFileParser::FromData($data)->getClass('Foo')->getName(),
    );
  }

  /** The noramlization blacklist shouldn't apply to things we define */
  public function testNamespacedClassCalledCollection(): void {
    $data = '<?php namespace Foo\Bar; class Collection {}';

    $this->assertEquals(
      vec['Foo\Bar\Collection'],
      LegacyFileParser::FromData($data)->getClassNames(),
    );
  }

  public function testNamespaceResolutionDependingOnSourceType(): void {
    $php = "<?php namespace Foo; class MyClass extends Collection {}";
    $hack = "<?hh namespace Foo; class MyClass extends Collection {}";

    $php_class = LegacyFileParser::FromData($php)->getClass("Foo\\MyClass");
    $hack_class = LegacyFileParser::FromData($hack)->getClass("Foo\\MyClass");

    $this->assertSame("Foo\\Collection", $php_class->getParentClassName());
    $this->assertSame('Collection', $hack_class->getParentClassName());
  }

  public function testScalarParameterInNamespace(): void {
    // This is correct for PHP7, not for PHP5 though. If you're using Hack,
    // you're more likely to be using scalar typehints than not.
    $php = '<?php namespace Foo; function myfunc(): string {}';
    $hack = '<?hh namespace Foo; function myfunc(): string {}';

    $php_func = LegacyFileParser::FromData($php)->getFunction("Foo\\myfunc");
    $hack_func = LegacyFileParser::FromData($hack)->getFunction("Foo\\myfunc");

    $this->assertEquals('string', $php_func->getReturnType()?->getTypeName());
    $this->assertEquals('string', $hack_func->getReturnType()?->getTypeName());
  }

  public function testReturnsThisInNamespace(): void {
    $code = "<?hh\n".
      "namespace Foo;\n".
      "class MyClass {\n".
      "  function foo(): this { }\n".
      "}";
    $parser = LegacyFileParser::FromData($code);
    $class = $parser->getClass("Foo\\MyClass");
    $method = $class->getMethods()[0];
    $this->assertSame('this', $method->getReturnType()?->getTypeName());
  }

  public function testReturnsClassGenericInNamespace(): void {
    $code = "<?hh\n".
      "namespace Foo;\n".
      "class MyClass<T> {\n".
      "  function foo(): T { }\n".
      "}";
    $parser = LegacyFileParser::FromData($code);
    $class = $parser->getClass("Foo\\MyClass");
    $method = $class->getMethods()[0];
    $this->assertSame('T', $method->getReturnType()?->getTypeName());
  }

  public function testReturnsNullableClassGenericInNamespace(): void {
    $code = "<?hh\n".
      "namespace Foo;\n".
      "class MyClass<T> {\n".
      "  function foo(): ?T { }\n".
      "}";
    $parser = LegacyFileParser::FromData($code);
    $class = $parser->getClass("Foo\\MyClass");
    $method = $class->getMethods()[0];
    $this->assertSame('T', $method->getReturnType()?->getTypeName());
    $this->assertTrue($method->getReturnType()?->isNullable());
  }

  public function testReturnsMethodGenericInNamespace(): void {
    $code = "<?hh\n".
      "namespace Foo;\n".
      "class MyClass {\n".
      "  function foo<T>(): T { }\n".
      "}";
    $parser = LegacyFileParser::FromData($code);
    $class = $parser->getClass("Foo\\MyClass");
    $method = $class->getMethods()[0];
    $this->assertSame('T', $method->getReturnType()?->getTypeName());
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
    $parser = LegacyFileParser::FromData($code);
    $class = $parser->getClass("Foo\\MyClass");
    $method = $class->getMethods()[0];
    $this->assertSame(
      'TClassGeneric',
      $method->getReturnType()?->getTypeName(),
    );
    $this->assertSame(
      'TFunctionGeneric',
      $method->getParameters()[0]->getTypehint()?->getTypeName(),
    );
  }

  public function testTakesMethodGenericInNamespace(): void {
    $code = "<?hh\n".
      "namespace Foo;\n".
      "class MyClass {\n".
      "  function foo<T>(T \$bar): void { }\n".
      "}";
    $parser = LegacyFileParser::FromData($code);
    $class = $parser->getClass("Foo\\MyClass");
    $method = $class->getMethods()[0];
    $this->assertSame(
      'T',
      $method->getParameters()[0]->getTypehint()?->getTypeName(),
    );
  }
}
