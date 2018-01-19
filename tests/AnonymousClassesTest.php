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

use type Facebook\DefinitionFinder\FileParser;
use namespace HH\Lib\Vec;

final class AnonymousClassesTest extends \PHPUnit_Framework_TestCase {
  public function testParsesInFunction(): void {
    $parser =
      FileParser::FromData('<?php function foo() { return new class {}; }');
    $this->assertEquals(vec['foo'], $parser->getFunctionNames());
  }

  public function testParsesInMethod(): void {
    $parser = FileParser::FromData(
      '<?php class Foo { function bar() { return new class {}; } }',
    );
    $class = $parser->getClass('Foo');
    $this->assertEquals(
      vec['bar'],
      Vec\map($class->getMethods(), $method ==> $method->getName()),
    );
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
    $parser = FileParser::FromData($code);
    $class = $parser->getClass('Foo');
    $this->assertEquals(
      vec['bar'],
      Vec\map($class->getMethods(), $method ==> $method->getName()),
    );
  }
}
