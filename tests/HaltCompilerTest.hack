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

final class HaltCompilerTest extends \Facebook\HackTest\HackTest {
  public async function testDoesNotRaiseErrorAfterHaltCompiler(): Awaitable<void> {
    $code = '<?hh function foo(){}; __halt_compiler(); function bar(;';
    $parser = await FileParser::fromDataAsync($code);
    expect($parser->getFunctionNames())->toBeSame(vec['foo']);
  }

  public async function testDoesNotParseDefinitionsAfterHaltCompiler(): Awaitable<void> {
    $code =
      '<?hh function foo(){}; __halt_compiler(); function bar(): Awaitable<void> {};';
    $parser = await FileParser::fromDataAsync($code);
    expect($parser->getFunctionNames())->toBeSame(vec['foo']);
  }
}
