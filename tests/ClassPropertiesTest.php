<?hh // strict
/*
 *  Copyright (c) 2015-present, Facebook, Inc.
 *  All rights reserved.
 *
 *  This source code is licensed under the MIT license found in the
 *  LICENSE file in the root directory of this source tree.
 *
 */

namespace Facebook\DefinitionFinder\Test;

use Facebook\DefinitionFinder\FileParser;
use Facebook\DefinitionFinder\ScannedClassish;
use namespace HH\Lib\Vec;

class ClassPropertiesTest extends \PHPUnit_Framework_TestCase {
  private ?vec<ScannedClassish> $classes;

  protected function setUp(): void {
    $parser = FileParser::FromFile(__DIR__.'/data/class_properties.php');
    $this->classes = $parser->getClasses();
  }

  public function testPropertyNames(): void {
    $class = $this->classes ? $this->classes[0] : null;
    $this->assertSame(
      'Facebook\\DefinitionFinder\\Test\\ClassWithProperties',
      $class?->getName(),
    );
    $this->assertEquals(
      vec['foo', 'bar', 'herp'],
      Vec\map($class?->getProperties()?? vec[], $x ==> $x->getName()),
    );
    $class = $this->classes ? $this->classes[1] : null;
    $this->assertSame(
      'Facebook\\DefinitionFinder\\Test2\\ClassWithProperties',
      $class?->getName(),
    );
    $this->assertEquals(
      vec['foobar'],
      Vec\map($class?->getProperties()?? vec[], $x ==> $x->getName()),
    );
  }

  public function testPropertyVisibility(): void {
    $class = $this->classes ? $this->classes[0] : null;
    $this->assertSame(
      'Facebook\\DefinitionFinder\\Test\\ClassWithProperties',
      $class?->getName(),
    );
    $this->assertEquals(
      vec[false, false, true],
      Vec\map($class?->getProperties()?? vec[], $x ==> $x->isPublic()),
      'isPublic',
    );
    $this->assertEquals(
      vec[false, true, false],
      Vec\map($class?->getProperties()?? vec[], $x ==> $x->isProtected()),
      'isProtected',
    );
    $this->assertEquals(
      vec[true, false, false],
      Vec\map($class?->getProperties()?? vec[], $x ==> $x->isPrivate()),
      'isPrivate',
    );
    $class = $this->classes ? $this->classes[1] : null;
    $this->assertSame(
      'Facebook\\DefinitionFinder\\Test2\\ClassWithProperties',
      $class?->getName(),
    );
    $this->assertEquals(
      vec[true],
      Vec\map($class?->getProperties()?? vec[], $x ==> $x->isPublic()),
      'isPublic',
    );
  }

  public function testPropertyTypes(): void {
    $class = $this->classes ? $this->classes[0] : null;
    $this->assertSame(
      'Facebook\\DefinitionFinder\\Test\\ClassWithProperties',
      $class?->getName(),
    );
    $this->assertEquals(
      vec['bool', 'int', 'string'],
      Vec\map($class?->getProperties()?? vec[], $x ==> $x->getTypehint()?->getTypeName()),
    );
    $class = $this->classes ? $this->classes[1] : null;
    $this->assertSame(
      'Facebook\\DefinitionFinder\\Test2\\ClassWithProperties',
      $class?->getName(),
    );
    $this->assertEquals(
      vec['bool'],
      Vec\map($class?->getProperties()?? vec[], $x ==> $x->getTypehint()?->getTypeName()),
    );
  }
}
