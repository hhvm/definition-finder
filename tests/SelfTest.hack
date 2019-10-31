/*
 *  Copyright (c) 2015-present, Facebook, Inc.
 *  All rights reserved.
 *
 *  This source code is licensed under the MIT license found in the
 *  LICENSE file in the root directory of this source tree.
 *
 */

namespace Facebook\DefinitionFinder\Test;

use type Facebook\DefinitionFinder\FileParser;
use type Facebook\HackTest\DataProvider;
use function Facebook\FBExpect\expect;

class SelfTest extends \Facebook\HackTest\HackTest {

  public function filenameProvider(): varray<array<string>> {
    return \array_map(
      $filename ==> varray[\basename($filename), $filename],
      \glob(\dirname(__DIR__).'/src/**/*.hack'),
    );
  }

  /**
   *
   * Bogus first argument to make test failure messages more useful
   */
  <<DataProvider('filenameProvider')>>
  public async function testSelf(string $_, string $filename): Awaitable<void> {
    $parser = await FileParser::fromFileAsync($filename);
    expect($parser)->toNotBeNull();
  }
}
