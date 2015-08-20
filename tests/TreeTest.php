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

namespace Facebook\DefinitionFinder\Tests;

use Facebook\DefinitionFinder\TreeParser;

class TreeTest extends \PHPUnit_Framework_TestCase {
  public function testTreeDefs(): void {
    $parser = TreeParser::FromPath(__DIR__.'/data/');
    // From multiple files
    $classes = $parser->getClassNames();
    $this->assertContains(
      "SingleNamespace\\SimpleClass",
      $classes,
    );
    $this->assertContains(
      "Namespaces\\AreNestedNow\\SimpleClass",
      $classes,
    );
  }
}
