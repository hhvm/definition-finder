<?hh // strict
/*
 *  Copyright (c) 2015, Facebook, Inc.
 *  All rights reserved.
 *
 *  This source code is licensed under the BSD-style license found in the
 *  LICENSE file in the root directory of this source tree. An additional grant
 *  of patent rights can be found in the PATENTS file in the same directory.
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
}
