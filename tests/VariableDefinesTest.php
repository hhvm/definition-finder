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

use Facebook\DefinitionFinder\FileParser;

class VariableDefinesTest extends \PHPUnit_Framework_TestCase {
  public function testVariableDefine(): void {
    $data = '<?php define($foo, $bar)';
    $parser = FileParser::FromData($data);
    $this->assertEmpty($parser->getConstants());
  }

  public function testExpressionDefine(): void {
    $data = '<?php define("foo"."bar", $baz)';
    $parser = FileParser::FromData($data);
    $this->assertEmpty($parser->getConstants());
  }
}
