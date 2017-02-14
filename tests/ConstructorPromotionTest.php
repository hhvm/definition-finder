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
use Facebook\DefinitionFinder\ScannedMethod;

class ConstructorPromotionTest extends \PHPUnit_Framework_TestCase {
  private ?ScannedClass $class;
  public function setUp(): void {
    $data = '<?hh

class Foo {
  public function __construct(
    public string $foo,
    <<HerpDerp>>
    private mixed $bar,
    /** baz comment */
    protected int $baz,
  ) {}
}
';

    $parser = FileParser::FromData($data);
    $this->class = $parser->getClass('Foo');
  }

  public function testFoundMethods(): void {
    $meths = $this->class?->getMethods();
    $this->assertSame(1, count($meths));
  }

  public function testConstructorParameters(): void {
    $meths = $this->class?->getMethods();
    $constructors = $meths?->filter($x ==> $x->getName() === '__construct');
    $constructor = $constructors?->get(0);
    $this->assertNotNull($constructor, 'did not find constructor');
    assert($constructor instanceof ScannedMethod);


    $params = $constructor->getParameters();
    $this->assertEquals(
      Vector { 'foo', 'bar', 'baz' },
      $params->map($x ==> $x->getName()),
    );
    $this->assertEquals(
      Vector { 'string', 'mixed', 'int' },
      $params->map($x ==> $x->getTypehint()?->getTypeName()),
    );
  }

  public function testClassProperties(): void {
    $props = $this->class?->getProperties();

    $this->assertEquals(
      Vector { 'foo', 'bar', 'baz' },
      $props?->map($x ==> $x->getName()),
    );

    $this->assertEquals(
      Vector { true, false, false },
      $props?->map($x ==> $x->isPublic()),
    );

    $this->assertEquals(
      Vector { 'string', 'mixed', 'int' },
      $props?->map($x ==> $x->getTypehint()?->getTypeName()),
    );

    $this->assertEquals(
      Vector {
        Map {},
        Map { 'HerpDerp' => Vector {} },
        Map {},
      },
      $props?->map($x ==> $x->getAttributes()),
    );

    $this->assertEquals(
      Vector { null, null, '/** baz comment */' },
      $props?->map($x ==> $x->getDocComment()),
    );
  }
}
