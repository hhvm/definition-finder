/*
 *  Copyright (c) 2015-present, Facebook, Inc.
 *  All rights reserved.
 *
 *  This source code is licensed under the MIT license found in the
 *  LICENSE file in the root directory of this source tree.
 *
 */

namespace Facebook\DefinitionFinder\Tests;

use type Facebook\HackTest\DataProvider;
use type Facebook\DefinitionFinder\FileParser;
use function Facebook\FBExpect\expect;

final class TypehintTest extends \Facebook\HackTest\HackTest {
  public function provideTypesInNamespace(): array<(string, string, string)> {
    return [
      // Unusual syntax
      tuple('shape("foo" => string)', 'shape', 'shape("foo"=>string)'),
      tuple('(string, string)', 'tuple', '(string,string)'),
      tuple('(string, string,)', 'tuple', '(string,string)'),
      tuple('(function(): void)', 'callable', '(function():void)'),
      tuple('(function(string,): int)', 'callable', '(function(string):int)'),
      tuple(
        '(function(a,b): int)',
        'callable',
        '(function(MyNamespace\\a,MyNamespace\\b):int)',
      ),

      // Shape with a namespaced field
      tuple(
        'shape("foo" => string, "bar" => Baz)',
        'shape',
        'shape("foo"=>string,"bar"=>MyNamespace\\Baz)',
      ),

      // Function with an inout param
      tuple(
        '(function(inout Foo): Bar)',
        'callable',
        '(function(inout MyNamespace\\Foo):MyNamespace\\Bar)',
      ),

      // Autoimports
      tuple('void', 'void', 'void'),
      tuple('dict<int, string>', 'dict', 'dict<int,string>'),
      tuple('Vector<string>', 'Vector', 'Vector<string>'),
      tuple('callable', 'callable', 'callable'),

      // Special
      tuple('classname<T>', 'classname', 'classname<MyNamespace\T>'),
      tuple(
        'keyset<classname<T>>',
        'keyset',
        'keyset<classname<MyNamespace\\T>>',
      ),
      tuple('vec<classname<T>>', 'vec', 'vec<classname<MyNamespace\\T>>'),

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
      tuple(
        '?(function(a,b): int)',
        'callable',
        '?(function(MyNamespace\\a,MyNamespace\\b):int)',
      ),
    ];
  }

  <<DataProvider('provideTypesInNamespace')>>
  public async function testNamespacedType(
    string $input,
    string $name,
    string $text,
  ): Awaitable<void> {
    $code = "<?hh \n".
      "namespace MyNamespace;\n".
      "function main(".
      $input.
      " \$_): Awaitable<void> {}\n";
    $def = (await FileParser::fromDataAsync($code))->getFunction('MyNamespace\\main');
    $type = $def->getParameters()[0]->getTypehint();
    expect($type)->toNotBeNull();
    expect($type?->getTypeName())->toBeSame($name, 'type name differs');
    expect($type?->getTypeText())->toBeSame($text, 'type text differs');
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

  <<DataProvider('provideNullableExamples')>>
  public async function testNullables(
    string $input,
    bool $nullable,
    string $name,
    string $text,
  ): Awaitable<void> {
    $code = "<?hh \n"."function main(".$input." \$_): Awaitable<void> {}\n";
    $def = (await FileParser::fromDataAsync($code))->getFunction('main');
    $type = $def->getParameters()[0]->getTypehint();
    expect($type)->toNotBeNull();
    expect($type?->isNullable())->toBeSame($nullable, 'nullability differs');
    expect($type?->getTypeName())->toBeSame($name, 'type name differs');
    expect($type?->getTypeText())->toBeSame($text, 'type text differs');
  }
}
