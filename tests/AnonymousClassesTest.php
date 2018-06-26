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

use type Facebook\DefinitionFinder\FileParser;
use namespace HH\Lib\Vec;

final class AnonymousClassesTest extends \PHPUnit_Framework_TestCase {
  public function testParsesInFunction(): void {
    $parser =
      FileParser::fromData('<?php function foo() { return new class {}; }');
    expect($parser->getFunctionNames())->toBeSame(vec['foo']);
  }

  public function testParsesInMethod(): void {
    $parser = FileParser::fromData(
      '<?php class Foo { function bar() { return new class {}; } }',
    );
    $class = $parser->getClass('Foo');
    expect(Vec\map($class->getMethods(), $method ==> $method->getName()))
      ->toBeSame(vec['bar']);
  }

  public function testMethodsNotPropagatedToContainer(): void {
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
    $parser = FileParser::fromData($code);
    $class = $parser->getClass('Foo');
    expect(Vec\map($class->getMethods(), $method ==> $method->getName()))
      ->toBeSame(vec['bar']);
  }
}
