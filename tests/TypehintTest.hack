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
use type Facebook\DefinitionFinder\{FileParser, TypeTextOptions};
use function Facebook\FBExpect\expect;

final class TypehintTest extends \Facebook\HackTest\HackTest {
  public function provideTypesInNamespace(): array<(string, string, string)> {
    return [
      // Unusual syntax
      tuple('shape("foo" => string)', 'shape', 'shape("foo" => string)'),
      tuple('(string, string)', 'tuple', '(string, string)'),
      tuple('(string, string,)', 'tuple', '(string, string)'),
      tuple('(function(): void)', 'callable', '(function(): void)'),
      tuple('(function(string,): int)', 'callable', '(function(string): int)'),
      tuple(
        '(function(a,b): int)',
        'callable',
        '(function(MyNamespace\\a, MyNamespace\\b): int)',
      ),

      // Shape with a namespaced field
      tuple(
        'shape("foo" => string, "bar" => Baz)',
        'shape',
        'shape("foo" => string, "bar" => MyNamespace\\Baz)',
      ),

      // Function with an inout param
      tuple(
        '(function(inout Foo): Bar)',
        'callable',
        '(function(inout MyNamespace\\Foo): MyNamespace\\Bar)',
      ),

      // Autoimports
      tuple('void', 'void', 'void'),
      tuple('dict<int, string>', 'dict', 'dict<int, string>'),
      tuple('Vector<string>', 'HH\\Vector', 'HH\\Vector<string>'),
      tuple('Vector<string>', Vector::class, Vector::class.'<string>'),
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
      tuple('?dict<int, string>', 'dict', '?dict<int, string>'),
      tuple('?shape("foo" => string)', 'shape', '?shape("foo" => string)'),
      tuple('?(string, string)', 'tuple', '?(string, string)'),
      tuple('?(function(): void)', 'callable', '?(function(): void)'),
      tuple(
        '?(function(string,): int)',
        'callable',
        '?(function(string): int)',
      ),
      tuple(
        '?(function(a,b): int)',
        'callable',
        '?(function(MyNamespace\\a, MyNamespace\\b): int)',
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
      tuple('(function():?string)', false, 'callable', '(function(): ?string)'),
      tuple(
        '?(function():?string)',
        true,
        'callable',
        '?(function(): ?string)',
      ),
      tuple(
        'shape("foo" => ?string)',
        false,
        'shape',
        'shape("foo" => ?string)',
      ),
      tuple(
        '?shape("foo" => ?string)',
        true,
        'shape',
        '?shape("foo" => ?string)',
      ),
      tuple('(?string, string)', false, 'tuple', '(?string, string)'),
      tuple('?(?string, string)', true, 'tuple', '?(?string, string)'),
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

  public function provideRelativeToNamespaceExamples(
  ): vec<(string, string, int, string)> {
    return vec[
      tuple('Vector<int>', '', 0, 'HH\\Vector<int>'),
      tuple('Vector<int>', '', 0, Vector::class.'<int>'),
      tuple(
        'Vector<int>',
        '',
        TypeTextOptions::STRIP_AUTOIMPORTED_NAMESPACE,
        'Vector<int>',
      ),
      tuple(
        'Vector<int>',
        'Foo',
        TypeTextOptions::STRIP_AUTOIMPORTED_NAMESPACE,
        'Vector<int>',
      ),
      tuple('callable', 'Foo', 0, 'callable'),
      tuple(
        'Herp\\Derp',
        '',
        TypeTextOptions::STRIP_AUTOIMPORTED_NAMESPACE,
        'Foo\\Bar\\Herp\\Derp',
      ),
      tuple(
        'Herp\\Derp',
        'Foo',
        TypeTextOptions::STRIP_AUTOIMPORTED_NAMESPACE,
        'Bar\\Herp\\Derp',
      ),
      tuple(
        'Herp\\Derp',
        'Foo\\Bar',
        TypeTextOptions::STRIP_AUTOIMPORTED_NAMESPACE,
        'Herp\\Derp',
      ),
      tuple(
        'Herp\\Derp',
        'Foo\\Bar\\Herp',
        TypeTextOptions::STRIP_AUTOIMPORTED_NAMESPACE,
        'Derp',
      ),
      tuple(
        '\\Herp\\Derp',
        '',
        TypeTextOptions::STRIP_AUTOIMPORTED_NAMESPACE,
        'Herp\\Derp',
      ),
      tuple(
        '\\Herp\\Derp',
        'Foo',
        TypeTextOptions::STRIP_AUTOIMPORTED_NAMESPACE,
        '\\Herp\\Derp',
      ),
      tuple(
        '\\Herp\\Derp',
        'Herp',
        TypeTextOptions::STRIP_AUTOIMPORTED_NAMESPACE,
        'Derp',
      ),
    ];
  }

  <<DataProvider('provideRelativeToNamespaceExamples')>>
  public async function testRelativeToNamespace(
    string $type,
    string $relative_to_namespace,
    int $options,
    string $expected,
  ): Awaitable<void> {
    // Provided typehint is nested inside a function inside a shape inside a
    // generic type, to verify that all rules are correctly applied recursively.
    $prefix = 'vec<shape(\'field\' => (function(): ';
    $suffix = '))>';
    $code = '
      namespace Foo\\Bar;
      function main('.$prefix.$type.$suffix.' $_): void {}
    ';
    $def = (await FileParser::fromDataAsync($code))
      ->getFunction('Foo\\Bar\\main');
    $type = $def->getParameters()[0]->getTypehint();
    expect($type?->getTypeText($relative_to_namespace, $options))
      ->toBeSame($prefix.$expected.$suffix);
  }
}
