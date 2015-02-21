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

class SimpleNoNamespacePHPTest extends PHPUnit_Framework_TestCase {
  private ?Facebook\DefinitionFinder\FileParser $parser;

  protected function setUp(): void {
    $this->parser = \Facebook\DefinitionFinder\FileParser::fromFile(
      __DIR__.'/data/no_namespace_php.php'
    );
  }

  public function testClasses(): void {
    $this->assertEquals(
      Vector {
        'SimpleClass',
        'SimpleAbstractClass',
        'SimpleFinalClass',
      },
      $this->parser?->getClasses(),
    );
  }

  public function testInterfaces(): void {
    $this->assertEquals(
      Vector { 'SimpleInterface' },
      $this->parser?->getInterfaces(),
    );
  }

  public function testTraits(): void {
    $this->assertEquals(
      Vector { 'SimpleTrait' },
      $this->parser?->getTraits(),
    );
  }
}
