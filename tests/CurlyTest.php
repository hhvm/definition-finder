<?hh // strict
/*
 *  Copyright (c) 2015, Facebook, Inc.
 *  All rights reserved.
 *
 *  This source code is licensed under the BSD-style license found in the
 *  LICENSE file in the root directory of this source tree. An additional grant
 *  of patent rights can be found in the PATENTS file in the same directory.
 *
 */

use \Facebook\DefinitionFinder\FileParser;

// Usually, '{' becomes '{' - however, when used for
// string interpolation, you get a T_CURLY_OPEN.
//
// Interestingly enough, the matching '}' is still just '}' -
// there is no such thing as T_CURLY_CLOSE.
//
// This test makes sure that this doesn't get confused.
final class CurlyTest extends PHPUnit_Framework_TestCase {
  const string DATA_FILE = __DIR__.'/data/curly_then_function.php';

  public function testDefinitions(): void {
    $p = FileParser::FromFile(self::DATA_FILE);
    $this->assertEquals(Vector { 'Foo' }, $p->getClassNames());
    $this->assertEquals(Vector { 'my_func' }, $p->getFunctionNames());
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
