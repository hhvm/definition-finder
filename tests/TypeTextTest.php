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

namespace Facebook\DefinitionFinder\Tests;

use Facebook\DefinitionFinder\FileParser;

final class TypeTextTest extends \PHPUnit_Framework_TestCase {
  public function provideTypesInNamespace(): array<(string, string, string)> {
    return [
      // Unusual syntax
      tuple('shape("foo" => string)', 'shape', 'shape("foo"=>string)'),
      tuple('(string, string)', 'tuple', '(string,string)'),
      tuple('(string, string,)', 'tuple', '(string,string)'),
      tuple('(function(){})', 'callable', '(function(){})'),

      // Autoimports
      tuple('void', 'void', 'void'),
      tuple('dict<int, string>', 'dict', 'dict<int,string>'),
      tuple('Vector<string>', 'Vector', 'Vector<string>'),
      tuple('callable', 'callable', 'callable'),

      // Namespacing
      tuple('\\Foo', 'Foo', 'Foo'),
      tuple('Foo', 'MyNamespace\\Foo', 'MyNamespace\\Foo'),
    ];
  }

  /** @dataProvider provideTypesInNamespace*/ 
  public function testNamespacedType(
    string $input,
    string $name,
    string $text,
  ): void {
    $code =
      "<?hh \n".
      "namespace MyNamespace;\n".
      "function main(".$input." \$_): void {}\n";
    $def = FileParser::FromData($code)->getFunction('MyNamespace\\main');
    $type = $def->getParameters()->at(0)->getTypehint();
    $this->assertNotNull($type);
    $this->assertSame($name, $type?->getTypeName(), 'type name differs');
    $this->assertSame($text, $type?->getTypeText(), 'type text differs');
  }
}
