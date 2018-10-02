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

use function Facebook\FBExpect\expect;
use type Facebook\DefinitionFinder\{FileParser, ScannedClassish};
use namespace HH\Lib\Vec;

class AbstractClassContentsTest extends \Facebook\HackTest\HackTest {
  private ?ScannedClassish $class;
  private ?vec<ScannedClassish> $classes;

  <<__Override>>
  public async function beforeEachTestAsync(): Awaitable<void> {
    $parser = FileParser::fromFile(__DIR__.'/data/abstract_class_contents.php');
    $this->classes = $parser->getClasses();
  }

  public function testClassIsAbstract(): void {
    expect(Vec\map($this->classes ?? vec[], $x ==> $x->isAbstract()))->toBeSame(
      vec[true, false],
      'isAbstract',
    );
  }

  public function testMethodsAreAbstract(): void {
    $class = $this->classes[0] ?? null;
    expect($class?->getName())->toBeSame(
      'Facebook\\DefinitionFinder\\Test\\AbstractClassWithContents',
    );
    expect(Vec\map($class?->getMethods() ?? vec[], $x ==> $x->isAbstract()))
      ->toBeSame(vec[false, true], 'isAbstract');
  }
}
