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

use function Facebook\FBExpect\expect;
use type Facebook\DefinitionFinder\{FileParser, ScannedClassish, ScannedMethod};
use namespace HH\Lib\Vec;

class ConstructorPromotionTest extends \PHPUnit_Framework_TestCase {
  private ?ScannedClassish $class;
  <<__Override>>
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

    $parser = FileParser::fromData($data);
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
    expect(Vec\map($params, $x ==> $x->getName()))->toBeSame(
      vec['foo', 'bar', 'baz'],
    );
    expect(Vec\map($params, $x ==> $x->getTypehint()?->getTypeName()))
      ->toBeSame(vec['string', 'mixed', 'int']);
  }

  public function testClassProperties(): void {
    $props = $this->class?->getProperties();

    expect(Vec\map($props ?? vec[], $x ==> $x->getName()))->toBeSame(
      vec['foo', 'bar', 'baz'],
    );

    expect(Vec\map($props ?? vec[], $x ==> $x->isPublic()))->toBeSame(
      vec[true, false, false],
    );

    expect(Vec\map($props ?? vec[], $x ==> $x->getTypehint()?->getTypeName()))
      ->toBeSame(vec['string', 'mixed', 'int']);

    expect(Vec\map($props ?? vec[], $x ==> $x->getAttributes()))->toBeSame(
      vec[dict[], dict['HerpDerp' => vec[]], dict[]],
    );

    expect(Vec\map($props ?? vec[], $x ==> $x->getDocComment()))->toBeSame(
      vec[null, null, '/** baz comment */'],
    );
  }
}
