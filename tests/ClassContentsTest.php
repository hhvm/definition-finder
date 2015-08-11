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

namespace Facebook\DefinitionFinder\Test;

use Facebook\DefinitionFinder\FileParser;
use Facebook\DefinitionFinder\ScannedClass;

class ClassContentsTest extends \PHPUnit_Framework_TestCase {
  private ?ScannedClass $class;

  protected function setUp(): void {
    $parser = FileParser::FromFile(
      __DIR__.'/data/class_contents.php'
    );
    $this->class = $parser->getClasses()[0];
    $this->assertSame(
      'Facebook\\DefinitionFinder\\Test\\ClassWithContents',
      $this->class->getName(),
    );
  }

  public function testMethodNames(): void {
    $this->assertEquals(
      Vector {
        'publicMethod',
        'protectedMethod',
        'privateMethod',
        'PublicStaticMethod',
      },
      $this->class?->getMethods()?->map($x ==> $x->getName()),
    );
  }

  public function testMethodVisibility(): void {
    $this->markTestIncomplete();
  }
  
  public function testMethodsAreStatic(): void {
    $this->markTestIncomplete();
  }
}
