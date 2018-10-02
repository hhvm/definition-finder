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
use type Facebook\DefinitionFinder\FileParser;

class VariableDefinesTest extends \Facebook\HackTest\HackTest {
  public function testVariableDefine(): void {
    $data = '<?php define($foo, $bar)';
    $parser = FileParser::fromData($data);
    expect($parser->getConstants())->toBeEmpty();
  }

  public function testExpressionDefine(): void {
    $data = '<?php define("foo"."bar", $baz)';
    $parser = FileParser::fromData($data);
    $c = $parser->getConstant('foobar');
    expect($c->getValue()->getAST()->getCode())->toBeSame('$baz');
    expect($c->getValue()->hasStaticValue())->toBeFalse();
  }
}
