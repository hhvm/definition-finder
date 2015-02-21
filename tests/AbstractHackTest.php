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

abstract class AbstractHackTest extends PHPUnit_Framework_TestCase {
  private ?Facebook\DefinitionFinder\FileParser $parser;

  abstract protected function getFilename(): string;
  abstract protected function getPrefix(): string;

  protected function setUp(): void {
    $this->parser = \Facebook\DefinitionFinder\FileParser::fromFile(
      __DIR__.'/data/'.$this->getFilename(),
    );
  }

  public function testClasses(): void {
    $this->assertEquals(
      Vector {
        $this->getPrefix().'GenericClass',
        $this->getPrefix().'AbstractFinalClass',
        $this->getPrefix().'xhp_foo',
        $this->getPrefix().'xhp_foo__bar',
      },
      $this->parser?->getClasses(),
    );
  }

  public function testTypes(): void {
    $this->assertEquals(
      Vector {
        $this->getPrefix().'MyType',
        $this->getPrefix().'MyGenericType',
      },
      $this->parser?->getTypes(),
    );
  }

  public function testNewtypes(): void {
    $this->assertEquals(
      Vector {
        $this->getPrefix().'MyNewtype',
        $this->getPrefix().'MyGenericNewtype',
      },
      $this->parser?->getNewtypes(),
    );
  }

  public function testEnums(): void {
    $this->assertEquals(
      Vector {
        $this->getPrefix().'MyEnum',
      },
      $this->parser?->getEnums(),
    );
  }
}
