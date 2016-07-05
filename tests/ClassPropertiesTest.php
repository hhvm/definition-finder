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

class ClassPropertiesTest extends \PHPUnit_Framework_TestCase {
  private ?\ConstVector<ScannedClass> $classes;

  protected function setUp(): void {
    $parser = FileParser::FromFile(
      __DIR__.'/data/class_properties.php'
    );
    $this->classes = $parser->getClasses();
  }

  public function testPropertyNames(): void {
    $class = $this->classes ? $this->classes[0] : null;
    $this->assertSame(
      'Facebook\\DefinitionFinder\\Test\\ClassWithProperties',
      $class?->getName(),
    );
    $this->assertEquals(
      Vector { 'foo', 'bar', 'herp' },
      $class?->getProperties()?->map($x ==> $x->getName()),
    );
    $class = $this->classes ? $this->classes[1] : null;
    $this->assertSame(
      'Facebook\\DefinitionFinder\\Test2\\ClassWithProperties',
      $class?->getName()
    );
    $this->assertEquals(
      Vector { 'foobar' },
      $class?->getProperties()?->map($x ==> $x->getName()),
    );
  }

  public function testPropertyVisibility(): void {
    $class = $this->classes ? $this->classes[0] : null;
    $this->assertSame(
      'Facebook\\DefinitionFinder\\Test\\ClassWithProperties',
      $class?->getName(),
    );
    $this->assertEquals(
      Vector { false, false, true },
      $class?->getProperties()?->map($x ==> $x->isPublic()),
      'isPublic'
    );
    $this->assertEquals(
      Vector { false, true, false },
      $class?->getProperties()?->map($x ==> $x->isProtected()),
      'isProtected'
    );
    $this->assertEquals(
      Vector { true, false, false },
      $class?->getProperties()?->map($x ==> $x->isPrivate()),
      'isPrivate'
    );
    $class = $this->classes ? $this->classes[1] : null;
    $this->assertSame(
      'Facebook\\DefinitionFinder\\Test2\\ClassWithProperties',
      $class?->getName()
    );
    $this->assertEquals(
      Vector { true },
      $class?->getProperties()?->map($x ==> $x->isPublic()),
      'isPublic'
    );
  }

  public function testPropertyTypes(): void {
    $class = $this->classes ? $this->classes[0] : null;
    $this->assertSame(
      'Facebook\\DefinitionFinder\\Test\\ClassWithProperties',
      $class?->getName(),
    );
    $this->assertEquals(
      Vector { 'bool', 'int', 'string' },
      $class?->getProperties()?->map(
        $x ==> $x->getTypehint()?->getTypeName()
      ),
    );
    $class = $this->classes ? $this->classes[1] : null;
    $this->assertSame(
      'Facebook\\DefinitionFinder\\Test2\\ClassWithProperties',
      $class?->getName()
    );
    $this->assertEquals(
      Vector { 'bool' },
      $class?->getProperties()?->map($x ==> $x->getTypehint()?->getTypeName()),
    );
  }
}
