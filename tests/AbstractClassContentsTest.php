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

namespace Facebook\DefinitionFinder\Test;

use type Facebook\DefinitionFinder\{
  FileParser,
  ScannedClassish,
};
use namespace HH\Lib\Vec;

class AbstractClassContentsTest extends \PHPUnit_Framework_TestCase {
  private ?ScannedClassish $class;
  private ?vec<ScannedClassish> $classes;

  <<__Override>>
  protected function setUp(): void {
    $parser = FileParser::FromFile(__DIR__.'/data/abstract_class_contents.php');
    $this->classes = $parser->getClasses();
  }

  public function testClassIsAbstract(): void {
    $this->assertEquals(
      vec[true, false],
      Vec\map($this->classes ?? vec[], $x ==> $x->isAbstract()),
      'isAbstract',
    );
  }

  public function testMethodsAreAbstract(): void {
    $class = $this->classes ? $this->classes[0] : null;
    $this->assertSame(
      'Facebook\\DefinitionFinder\\Test\\AbstractClassWithContents',
      $class?->getName(),
    );
    $this->assertEquals(
      vec[false, true],
      Vec\map($class?->getMethods() ?? vec[], $x ==> $x->isAbstract()),
      'isAbstract',
    );
  }
}
