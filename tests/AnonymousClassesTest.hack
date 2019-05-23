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
use function Facebook\FBExpect\expect;
use namespace HH\Lib\Vec;

final class AnonymousClassesTest extends \Facebook\HackTest\HackTest {
  public async function testParsesInFunction(): Awaitable<void> {
    $parser = await FileParser::fromDataAsync(
      '<?php function foo() { return new class {}; }',
    );
    expect($parser->getFunctionNames())->toBeSame(vec['foo']);
  }

  public async function testParsesInMethod(): Awaitable<void> {
    $parser = await FileParser::fromDataAsync(
      '<?php class Foo { function bar() { return new class {}; } }',
    );
    $class = $parser->getClass('Foo');
    expect(Vec\map($class->getMethods(), $method ==> $method->getName()))
      ->toBeSame(vec['bar']);
  }

  public async function testMethodsNotPropagatedToContainer(): Awaitable<void> {
    $code = <<<EOF
<?php
class Foo {
  function bar() {
    return new class {
      function baz() {
      }
    };
  }
}
EOF;
    $parser = (await FileParser::fromDataAsync($code));
    $class = $parser->getClass('Foo');
    expect(Vec\map($class->getMethods(), $method ==> $method->getName()))
      ->toBeSame(vec['bar']);
  }
}
