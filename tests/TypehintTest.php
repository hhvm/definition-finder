<?hh // strict
/*
 *  Copyright (c) 2015-present, Facebook, Inc.
 *  All rights reserved.
 *
 *  This source code is licensed under the MIT license found in the
 *  LICENSE file in the root directory of this source tree.
 *
 */

namespace Facebook\DefinitionFinder\Tests;

use type Facebook\DefinitionFinder\FileParser;

final class TypeHintTest extends \PHPUnit_Framework_TestCase {
  public function provideTypesInNamespace(): array<(string, string, string)> {
    return [
      // Unusual syntax
      tuple('shape("foo" => string)', 'shape', 'shape("foo"=>string)'),
      tuple('(string, string)', 'tuple', '(string,string)'),
      tuple('(string, string,)', 'tuple', '(string,string)'),
      tuple('(function(): void)', 'callable', '(function():void)'),
      tuple('(function(string,): int)', 'callable', '(function(string):int)'),
      tuple('(function(a,b): int)', 'callable', '(function(a,b):int)'),

      // Autoimports
      tuple('void', 'void', 'void'),
      tuple('dict<int, string>', 'dict', 'dict<int,string>'),
      tuple('Vector<string>', 'Vector', 'Vector<string>'),
      tuple('callable', 'callable', 'callable'),

      // Namespacing
      tuple('\\Foo', 'Foo', 'Foo'),
      tuple('Foo', 'MyNamespace\\Foo', 'MyNamespace\\Foo'),

      // Nullables
      tuple('?Foo', 'MyNamespace\\Foo', '?MyNamespace\\Foo'),
      tuple('?dict<int, string>', 'dict', '?dict<int,string>'),
      tuple('?shape("foo" => string)', 'shape', '?shape("foo"=>string)'),
      tuple('?(string, string)', 'tuple', '?(string,string)'),
      tuple('?(function(): void)', 'callable', '?(function():void)'),
      tuple('?(function(string,): int)', 'callable', '?(function(string):int)'),
      tuple('?(function(a,b): int)', 'callable', '?(function(a,b):int)'),
    ];
  }

  /** @dataProvider provideTypesInNamespace*/ public function testNamespacedType(
    string $input,
    string $name,
    string $text,
  ): void {
    $code = "<?hh \n".
      "namespace MyNamespace;\n".
      "function main(".
      $input.
      " \$_): void {}\n";
    $def = FileParser::fromData($code)->getFunction('MyNamespace\\main');
    $type = $def->getParameters()[0]->getTypehint();
    $this->assertNotNull($type);
    $this->assertSame($name, $type?->getTypeName(), 'type name differs');
    $this->assertSame($text, $type?->getTypeText(), 'type text differs');
  }

  public function provideNullableExamples(
  ): array<(string, bool, string, string)> {
    return [
      tuple('Foo', false, 'Foo', 'Foo'),
      tuple('?Foo', true, 'Foo', '?Foo'),
      tuple('(function():?string)', false, 'callable', '(function():?string)'),
      tuple('?(function():?string)', true, 'callable', '?(function():?string)'),
      tuple('shape("foo" => ?string)', false, 'shape', 'shape("foo"=>?string)'),
      tuple(
        '?shape("foo" => ?string)',
        true,
        'shape',
        '?shape("foo"=>?string)',
      ),
      tuple('(?string, string)', false, 'tuple', '(?string,string)'),
      tuple('?(?string, string)', true, 'tuple', '?(?string,string)'),
    ];
  }

  /** @dataProvider provideNullableExamples */
  public function testNullables(
    string $input,
    bool $nullable,
    string $name,
    string $text,
  ): void {
    $code = "<?hh \n"."function main(".$input." \$_): void {}\n";
    $def = FileParser::fromData($code)->getFunction('main');
    $type = $def->getParameters()[0]->getTypehint();
    $this->assertNotNull($type);
    $this->assertSame($nullable, $type?->isNullable(), 'nullability differs');
    $this->assertSame($name, $type?->getTypeName(), 'type name differs');
    $this->assertSame($text, $type?->getTypeText(), 'type text differs');
  }
}
