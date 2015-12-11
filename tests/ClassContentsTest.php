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

  public function testNamespaceName(): void {
    $this->assertEquals(
      'Facebook\DefinitionFinder\Test',
      $this->class?->getNamespaceName(),
    );
    $this->assertEquals(
      Vector {'', '', '', ''},
      $this->class?->getMethods()?->map($x ==> $x->getNamespaceName()),
    );
  }

  public function testShortName(): void {
    $this->assertEquals(
      'ClassWithContents',
      $this->class?->getShortName(),
    );
    $this->assertEquals(
      Vector {
        'publicMethod',
        'protectedMethod',
        'privateMethod',
        'PublicStaticMethod',
      },
      $this->class?->getMethods()?->map($x ==> $x->getShortName()),
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
    $this->assertEquals(
      Vector {true, false, false, true},
      $this->class?->getMethods()?->map($x ==> $x->isPublic()),
      'isPublic',
    );
    $this->assertEquals(
      Vector {false, true, false, false},
      $this->class?->getMethods()?->map($x ==> $x->isProtected()),
      'isProtected',
    );
    $this->assertEquals(
      Vector {false, false, true, false},
      $this->class?->getMethods()?->map($x ==> $x->isPrivate()),
      'isPrivate',
    );
  }

  public function testHackConstants(): void {
    $constants = $this->class?->getConstants();
    $this->assertEquals(
      Vector { 'FOO' },
      $constants?->map($x ==> $x->getName()),
    );
    $this->assertEquals(
      Vector { 'string' },
      $constants?->map($x ==> $x->getTypehint()?->getTypeName()),
    );
    $this->assertEquals(
      Vector { '/** FooDoc */' },
      $constants?->map($x ==> $x->getDocComment()),
    );
  }

  public function testPHPConstants(): void {
    $parser = FileParser::FromFile(__DIR__.'/data/php_class_contents.php');
    $class = $parser->getClass('Foo');
    $constants = $class->getConstants();

    $this->assertEquals(
      Vector { 'FOO' },
      $constants->map($x ==> $x->getName()),
    );
    $this->assertEquals(
      Vector { null },
      $constants->map($x ==> $x->getTypehint()),
    );
    $this->assertEquals(
      Vector { '/** FooDoc */' },
      $constants->map($x ==> $x->getDocComment()),
    );
  }

  /** Omitting public/protected/private is permitted in PHP */
  public function testDefaultMethodVisibility(): void {
    $parser = FileParser::FromFile(__DIR__.'/data/php_class_contents.php');
    $funcs = $parser->getClass('Foo')->getMethods();

    $this->assertEquals(
      Vector {
        'defaultVisibility',
        'privateVisibility',
        'alsoDefaultVisibility',
      },
      $funcs->map($x ==> $x->getName()),
    );
    $this->assertEquals(
      Vector { true, false, true },
      $funcs->map($x ==> $x->isPublic()),
    );
  }

  public function testMethodsAreStatic(): void {
    $this->assertEquals(
      Vector { false, false, false, true },
      $this->class?->getMethods()?->map($x ==> $x->isStatic()),
      'isPublic',
    );
  }

  public function testPropertyNames(): void {
    $this->assertEquals(
      Vector { 'foo', 'herp' },
      $this->class?->getProperties()?->map($x ==> $x->getName()),
    );
  }

  public function testPropertyVisibility(): void {
    $this->assertEquals(
      Vector { false, true },
      $this->class?->getProperties()?->map($x ==> $x->isPublic()),
    );
  }

  public function testPropertyTypes(): void {
    $this->assertEquals(
      Vector { 'bool', 'string' },
      $this->class?->getProperties()?->map(
        $x ==> $x->getTypehint()?->getTypeName()
      ),
    );
  }

  public function testTypelessProperty(): void {
    $parser = FileParser::FromFile(__DIR__.'/data/php_class_contents.php');
    $props = $parser->getClass('Foo')->getProperties();

    $this->assertEquals(
      Vector { 'untypedProperty' },
      $props->map($x ==> $x->getName()),
    );
    $this->assertEquals(
      Vector { null },
      $props->map($x ==> $x->getTypehint()),
    );
  }

  public function staticPropertyProvider(): array<array<mixed>> {
    return [
      ['default', '<?php class Foo { static $bar; }', null],
      ['public static', '<?php class Foo { public static $bar; }', null],
      ['static public', '<?php class Foo { static public $bar; }', null],
      [
        'public static string',
        '<?hh class Foo { public static string $bar; }',
        'string',
      ],
      [
        'static public string',
        '<?hh class Foo { static public string $bar; }',
        'string',
      ],
    ];
  }

  /** @dataProvider staticPropertyProvider */
  public function testStaticProperty(
    string $_,
    string $code,
    ?string $type,
  ): void {
    $parser = FileParser::FromData($code);
    $class = $parser->getClass('Foo');
    $props = $class->getProperties();

    $this->assertEquals(
      Vector { 'bar' },
      $props->map($x ==> $x->getName()),
    );

    $this->assertEquals(
      Vector { $type },
      $props->map($x ==> $x->getTypehint()?->getTypeName()),
    );


    $this->assertEquals(
      Vector { true },
      $props->map($x ==> $x->isStatic()),
    );
  }
}
