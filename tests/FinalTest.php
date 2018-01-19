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

class FinalTest extends \PHPUnit_Framework_TestCase {
  private ?vec<ScannedClass> $classes;

  protected function setUp(): void {
    $parser = FileParser::FromFile(__DIR__.'/data/finals.php');
    $this->classes = $parser->getClasses();
  }

  public function testClassIsFinal(): void {
    $this->assertEquals(
      vec[true, false],
      $this->classes?->map($x ==> $x->isFinal()),
      'isFinal',
    );
  }

  public function testMethodsAreFinal(): void {
    $class = $this->classes ? $this->classes[1] : null;
    $this->assertEquals(
      vec[true, false],
      $class?->getMethods()?->map($x ==> $x->isFinal()),
      'isFinal',
    );
  }
}
