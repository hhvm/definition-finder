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
use type Facebook\DefinitionFinder\FileParser;

final class MultiNamespacePHPTest extends PHPUnit_Framework_TestCase {
  private ?FileParser $parser;

  <<__Override>>
  protected function setUp(): void {
    $this->parser =
      FileParser::fromFile(__DIR__.'/data/multi_namespace_php.php');
  }

  public function testClasses(): void {
    expect($this->parser?->getClassNames())->toBeSame(
      vec['Foo\\Bar', 'Herp\\Derp', 'EmptyNamespace'],
    );
  }
}
