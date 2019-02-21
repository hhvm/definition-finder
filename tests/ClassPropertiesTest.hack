/*
 *  Copyright (c) 2015-present, Facebook, Inc.
 *  All rights reserved.
 *
 *  This source code is licensed under the MIT license found in the
 *  LICENSE file in the root directory of this source tree.
 *
 */

namespace Facebook\DefinitionFinder\Test;

use type Facebook\DefinitionFinder\FileParser;
use type Facebook\DefinitionFinder\ScannedClassish;
use function Facebook\FBExpect\expect;
use namespace HH\Lib\Vec;

class ClassPropertiesTest extends \Facebook\HackTest\HackTest {
  private ?vec<ScannedClassish> $classes;

  <<__Override>>
  public async function beforeEachTestAsync(): Awaitable<void> {
    $parser = FileParser::fromFile(__DIR__.'/data/class_properties.php');
    $this->classes = $parser->getClasses();
  }

  public function testPropertyNames(): void {
    $class = $this->classes[0] ?? null;
    expect($class?->getName())->toBeSame(
      'Facebook\\DefinitionFinder\\Test\\ClassWithProperties',
    );
    expect(Vec\map($class?->getProperties() ?? vec[], $x ==> $x->getName()))
      ->toBeSame(vec['foo', 'bar', 'herp']);
    $class = $this->classes[1] ?? null;
    expect($class?->getName())->toBeSame(
      'Facebook\\DefinitionFinder\\Test2\\ClassWithProperties',
    );
    expect(Vec\map($class?->getProperties() ?? vec[], $x ==> $x->getName()))
      ->toBeSame(vec['foobar']);
  }

  public function testPropertyVisibility(): void {
    $class = $this->classes[0] ?? null;
    expect($class?->getName())->toBeSame(
      'Facebook\\DefinitionFinder\\Test\\ClassWithProperties',
    );
    expect(Vec\map($class?->getProperties() ?? vec[], $x ==> $x->isPublic()))
      ->toBeSame(vec[false, false, true], 'isPublic');
    expect(Vec\map($class?->getProperties() ?? vec[], $x ==> $x->isProtected()))
      ->toBeSame(vec[false, true, false], 'isProtected');
    expect(Vec\map($class?->getProperties() ?? vec[], $x ==> $x->isPrivate()))
      ->toBeSame(vec[true, false, false], 'isPrivate');
    $class = $this->classes[1] ?? null;
    expect($class?->getName())->toBeSame(
      'Facebook\\DefinitionFinder\\Test2\\ClassWithProperties',
    );
    expect(Vec\map($class?->getProperties() ?? vec[], $x ==> $x->isPublic()))
      ->toBeSame(vec[true], 'isPublic');
  }

  public function testPropertyTypes(): void {
    $class = $this->classes[0] ?? null;
    expect($class?->getName())->toBeSame(
      'Facebook\\DefinitionFinder\\Test\\ClassWithProperties',
    );
    expect(
      Vec\map(
        $class?->getProperties() ?? vec[],
        $x ==> $x->getTypehint()?->getTypeName(),
      ),
    )->toBeSame(vec['bool', 'int', 'string']);
    $class = $this->classes[1] ?? null;
    expect($class?->getName())->toBeSame(
      'Facebook\\DefinitionFinder\\Test2\\ClassWithProperties',
    );
    expect(
      Vec\map(
        $class?->getProperties() ?? vec[],
        $x ==> $x->getTypehint()?->getTypeName(),
      ),
    )->toBeSame(vec['bool']);
  }
}
