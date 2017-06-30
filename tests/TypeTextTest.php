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
  public function provideSpecialTypes(): array<(string, string, string)> {
    return [
      tuple('shape("foo" => string)', 'shape', 'shape("foo"=>string)'),
      tuple('(string, string)', 'tuple', '(string,string)'),
      tuple('(string, string,)', 'tuple', '(string,string)'),
      tuple('(function(){})', 'callable', '(function(){})'),
      tuple('void', 'void', 'void'),
      tuple('dict<int, string>', 'dict', 'dict<int,string>'),
    ];
  }

  /** @dataProvider provideSpecialTypes */ 
  public function testSpecialTypeInNamespace(
    string $input,
    string $name,
    string $text,
  ): void {
    $code =
      "<?hh \n".
      "namespace Foo;\n".
      "function main(".$input." \$_): void {}\n";
    $def = FileParser::FromData($code)->getFunction('Foo\\main');
    $type = $def->getParameters()->at(0)->getTypehint();
    $this->assertNotNull($type);
    $this->assertSame($name, $type?->getTypeName(), 'type name differs');
    $this->assertSame($text, $type?->getTypeText(), 'type text differs');
  }
}
