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
use type Facebook\DefinitionFinder\{FileParser, ScannedClassish};
use namespace HH\Lib\Vec;

final class FinalTest extends \Facebook\HackTest\HackTest {
  private ?vec<ScannedClassish> $classes;

  <<__Override>>
  public async function beforeEachTestAsync(): Awaitable<void> {
    $parser = await FileParser::fromFileAsync(__DIR__.'/data/finals.php');
    $this->classes = $parser->getClasses();
  }

  public function testClassIsFinal(): void {
    expect(Vec\map($this->classes ?? vec[], $x ==> $x->isFinal()))->toBeSame(
      vec[true, false],
      'isFinal',
    );
  }

  public function testMethodsAreFinal(): void {
    $class = $this->classes[1] ?? null;
    expect(Vec\map($class?->getMethods() ?? vec[], $x ==> $x->isFinal()))
      ->toBeSame(vec[true, false], 'isFinal');
  }
}
