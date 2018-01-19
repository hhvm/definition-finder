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

use Facebook\DefinitionFinder\FileParser;
use Facebook\DefinitionFinder\ScannedClass;

class AbstractClassContentsTest extends \PHPUnit_Framework_TestCase {
  private ?ScannedClass $class;
  private ?vec<ScannedClass> $classes;

  protected function setUp(): void {
    $parser = FileParser::FromFile(__DIR__.'/data/abstract_class_contents.php');
    $this->classes = $parser->getClasses();
  }

  public function testClassIsAbstract(): void {
    $this->assertEquals(
      vec[true, false],
      $this->classes?->map($x ==> $x->isAbstract()),
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
      $class?->getMethods()?->map($x ==> $x->isAbstract()),
      'isAbstract',
    );
  }
}
