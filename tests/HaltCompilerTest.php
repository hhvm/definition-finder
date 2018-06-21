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

use Facebook\DefinitionFinder\LegacyFileParser;

final class HaltCompilerTest extends \PHPUnit_Framework_TestCase {
  public function testDoesNotRaiseErrorAfterHaltCompiler(): void {
    $code = '<?hh function foo(){}; __halt_compiler(); function bar(;';
    $parser = LegacyFileParser::FromData($code);
    $this->assertEquals(vec['foo'], $parser->getFunctionNames());
  }

  public function testDoesNotParseDefinitionsAfterHaltCompiler(): void {
    $code =
      '<?hh function foo(){}; __halt_compiler(); function bar(): void {};';
    $parser = LegacyFileParser::FromData($code);
    $this->assertEquals(vec['foo'], $parser->getFunctionNames());
  }
}
