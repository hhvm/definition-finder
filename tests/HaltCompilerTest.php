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

use function Facebook\FBExpect\expect;
use type Facebook\DefinitionFinder\FileParser;

final class HaltCompilerTest extends \PHPUnit_Framework_TestCase {
  public function testDoesNotRaiseErrorAfterHaltCompiler(): void {
    $code = '<?hh function foo(){}; __halt_compiler(); function bar(;';
    $parser = FileParser::fromData($code);
    expect($parser->getFunctionNames())->toBeSame(vec['foo']);
  }

  public function testDoesNotParseDefinitionsAfterHaltCompiler(): void {
    $code =
      '<?hh function foo(){}; __halt_compiler(); function bar(): void {};';
    $parser = FileParser::fromData($code);
    expect($parser->getFunctionNames())->toBeSame(vec['foo']);
  }
}
