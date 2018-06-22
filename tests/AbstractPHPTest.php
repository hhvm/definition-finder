<?hh // strict
/*
 *  Copyright (c) 2015-present, Facebook, Inc.
 *  All rights reserved.
 *
 *  This source code is licensed under the MIT license found in the
 *  LICENSE file in the root directory of this source tree.
 *
 */

use type Facebook\DefinitionFinder\FileParser;
use function Facebook\FBExpect\expect;

abstract class AbstractPHPTest extends PHPUnit_Framework_TestCase {
  private ?FileParser $parser;

  abstract protected function getFilename(): string;
  abstract protected function getPrefix(): string;

  <<__Override>>
  protected function setUp(): void {
    $this->parser = FileParser::fromFile(
      __DIR__.'/data/'.$this->getFilename(),
    );
  }

  public function testClasses(): void {
    expect(
      $this->parser?->getClassNames(),
    )->toBeSame(
      vec[
        $this->getPrefix().'SimpleClass',
        $this->getPrefix().'SimpleAbstractClass',
        $this->getPrefix().'SimpleFinalClass',
      ],
    );
  }

  public function testInterfaces(): void {
    expect(
      $this->parser?->getInterfaceNames(),
    )->toBeSame(
      vec[$this->getPrefix().'SimpleInterface'],
    );
  }

  public function testTraits(): void {
    expect(
      $this->parser?->getTraitNames(),
    )->toBeSame(
      vec[$this->getPrefix().'SimpleTrait'],
    );
  }
}
