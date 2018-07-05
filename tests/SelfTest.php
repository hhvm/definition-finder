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

use type Facebook\DefinitionFinder\FileParser;

class SelfTest extends \PHPUnit_Framework_TestCase {

  public function filenameProvider(): array<array<string>> {
    return \array_map(
      $filename ==> [\basename($filename), $filename],
      \glob(\dirname(__DIR__).'/src/**/*.php'),
    );
  }

  /**
   * @dataProvider filenameProvider
   *
   * Bogus first argument to make test failure messages more useful
   */
  public function testSelf(string $_, string $filename): void {
    $parser = FileParser::fromFile($filename);
    $this->assertNotNull($parser);
  }
}
