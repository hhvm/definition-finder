<?hh // strict
/*
 *  Copyright (c) 2015-present, Facebook, Inc.
 *  All rights reserved.
 *
 *  This source code is licensed under the MIT license found in the
 *  LICENSE file in the root directory of this source tree.
 *
 */

use function Facebook\FBExpect\expect;
use type \Facebook\DefinitionFinder\FileParser;

// Usually, '{' becomes '{' - however, when used for
// string interpolation, you get a T_CURLY_OPEN for "{$foo}" or
// T_DOLLAR_OPEN_CURLY_BRACES for "${foo}".
//
// Interestingly enough, the matching '}' is still just '}' -
// there is no such thing as T_CURLY_CLOSE or T_DOLLAR_CLOSE_CURLY_BRACES.
//
// This test makes sure that this doesn't get confused.
final class CurlyTest extends PHPUnit_Framework_TestCase {
  const string DATA_FILE = __DIR__.'/data/curly_then_function.php';

  public function testDefinitions(): void {
    $p = FileParser::fromFile(self::DATA_FILE);
    expect($p->getClassNames())->toBeSame(vec['Foo']);
    expect($p->getFunctionNames())->toBeSame(vec['my_func']);
  }

  // Actually testing the tokenizer hasn't changed
  public function testContainsTCurlyOpen(): void {
    $matched = false;
    $tokens = token_get_all(file_get_contents(self::DATA_FILE));
    foreach ($tokens as $token) {
      if (is_array($token) && $token[0] === T_CURLY_OPEN) {
        $matched = true;
        break;
      }
    }
    $this->assertTrue($matched, 'no T_CURLY_OPEN in data file');
  }

  // Actually testing the tokenizer hasn't changed
  public function testContainsTDollarOpenCurlyBraces(): void {
    $matched = false;
    $tokens = token_get_all(file_get_contents(self::DATA_FILE));
    foreach ($tokens as $token) {
      if (is_array($token) && $token[0] === T_DOLLAR_OPEN_CURLY_BRACES) {
        $matched = true;
        break;
      }
    }
    $this->assertTrue($matched, 'no T_DOLLAR_OPEN_CURLY_BRACES in data file');
  }

  // Actually testing the tokenizer hasn't changed
  public function testDoesNotContainTCurlyClose(): void {
    $tokens = token_get_all(file_get_contents(self::DATA_FILE));
    foreach ($tokens as $token) {
      if (!is_array($token)) {
        continue;
      }
      $this->assertTrue(
        $token[1] !== '}',
        sprintf(
          'Got a token of type %d (%s) containing "}"',
          $token[0],
          token_name($token[0]),
        ),
      );
    }
  }
}
