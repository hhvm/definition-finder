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

abstract class AbstractPHPTest extends PHPUnit_Framework_TestCase {
  private ?Facebook\DefinitionFinder\FileParser $parser;

  abstract protected function getFilename(): string;
  abstract protected function getPrefix(): string;

  protected function setUp(): void {
    $this->parser = \Facebook\DefinitionFinder\FileParser::FromFile(
      __DIR__.'/data/'.$this->getFilename(),
    );
  }

  public function testClasses(): void {
    $this->assertEquals(
      Vector {
        $this->getPrefix().'SimpleClass',
        $this->getPrefix().'SimpleAbstractClass',
        $this->getPrefix().'SimpleFinalClass',
      },
      $this->parser?->getClassNames(),
    );
  }

  public function testInterfaces(): void {
    $this->assertEquals(
      Vector { $this->getPrefix().'SimpleInterface' },
      $this->parser?->getInterfaceNames(),
    );
  }

  public function testTraits(): void {
    $this->assertEquals(
      Vector { $this->getPrefix().'SimpleTrait' },
      $this->parser?->getTraitNames(),
    );
  }
}
