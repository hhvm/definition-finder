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

use type Facebook\DefinitionFinder\{
  FileParser,
  ScannedClass,
  ScannedMethod,
};
use namespace HH\Lib\Vec;

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
    $this->assertSame(1, \count($meths));
  }

  public function testConstructorParameters(): void {
    $meths = $this->class?->getMethods() ?? vec[];
    $constructors = Vec\filter($meths, $x ==> $x->getName() === '__construct');
    $constructor = $constructors[0] ?? null;
    $this->assertNotNull($constructor, 'did not find constructor');
    assert($constructor instanceof ScannedMethod);


    $params = $constructor->getParameters();
    $this->assertEquals(
      vec['foo', 'bar', 'baz'],
      Vec\map($params, $x ==> $x->getName()),
    );
    $this->assertEquals(
      vec['string', 'mixed', 'int'],
      Vec\map($params, $x ==> $x->getTypehint()?->getTypeName()),
    );
  }

  public function testClassProperties(): void {
    $props = $this->class?->getProperties();

    $this->assertEquals(
      vec['foo', 'bar', 'baz'],
      Vec\map($props?? vec[], $x ==> $x->getName()),
    );

    $this->assertEquals(
      vec[true, false, false],
      Vec\map($props?? vec[], $x ==> $x->isPublic()),
    );

    $this->assertEquals(
      vec['string', 'mixed', 'int'],
      Vec\map($props?? vec[], $x ==> $x->getTypehint()?->getTypeName()),
    );

    $this->assertEquals(
      vec[Map {}, dict['HerpDerp' => vec[]], Map {} ],
      Vec\map($props?? vec[], $x ==> $x->getAttributes()),
    );

    $this->assertEquals(
      vec[null, null, '/** baz comment */'],
      Vec\map($props?? vec[], $x ==> $x->getDocComment()),
    );
  }
}
