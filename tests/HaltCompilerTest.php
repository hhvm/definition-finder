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

namespace Facebook\DefinitionFinder\Test;

use Facebook\DefinitionFinder\FileParser;

final class HaltCompilerTest extends \PHPUnit_Framework_TestCase {
  public function testDoesNotRaiseErrorAfterHaltCompiler(): void {
    $code = '<?hh function foo(){}; __halt_compiler(); function bar(;';
    $parser = FileParser::FromData($code);
    $this->assertEquals(vec['foo'], $parser->getFunctionNames());
  }

  public function testDoesNotParseDefinitionsAfterHaltCompiler(): void {
    $code =
      '<?hh function foo(){}; __halt_compiler(); function bar(): void {};';
    $parser = FileParser::FromData($code);
    $this->assertEquals(vec['foo'], $parser->getFunctionNames());
  }
}
