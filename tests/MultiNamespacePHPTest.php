<?hh // strict
/*
 *  Copyright (c) 2015-present, Facebook, Inc.
 *  All rights reserved.
 *
 *  This source code is licensed under the MIT license found in the
 *  LICENSE file in the root directory of this source tree.
 *
 */

use type Facebook\DefinitionFinder\LegacyFileParser;

final class MultiNamespacePHPTest extends PHPUnit_Framework_TestCase {
  private ?LegacyFileParser $parser;

  <<__Override>>
  protected function setUp(): void {
    $this->parser = LegacyFileParser::FromFile(
      __DIR__.'/data/multi_namespace_php.php',
    );
  }

  public function testClasses(): void {
    $this->assertEquals(
      vec['Foo\\Bar', 'Herp\\Derp', 'EmptyNamespace'],
      $this->parser?->getClassNames(),
    );
  }
}
