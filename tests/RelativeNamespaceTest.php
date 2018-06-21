<?hh // strict
/*
 *  Copyright (c) 2015-present, Facebook, Inc.
 *  All rights reserved.
 *
 *  This source code is licensed under the MIT license found in the
 *  LICENSE file in the root directory of this source tree.
 *
 */

use type \Facebook\DefinitionFinder\FileParser;
use namespace HH\Lib\Vec;

/**
 * `namespace\foo` means 'foo in the current namespace - see
 * http://php.net/manual/en/language.namespaces.nsconstants.php example 4
 */
final class RelativeNamespaceTest extends PHPUnit_Framework_TestCase {
  public function testFunctionBodyUsesRelativeNamespace(): void {
    $code = '<?php function foo() { namespace\bar(); } function baz() {}';
    $fp = FileParser::fromData($code);
    $this->assertEquals(
      vec['foo', 'baz'],
      $fp->getFunctionNames(),
    );

    $this->assertEquals(
      vec[],
      Vec\map($fp->getFunctions(), $f ==> $f->getNamespaceName()),
    );
  }

  public function testPseudomainUsesRelativeNamespace(): void {
    $code = '<?php namespace\foo(); function bar() {}';
    $fp = FileParser::fromData($code);
    $this->assertEquals(vec['bar'], $fp->getFunctionNames());

    $this->assertEquals(
      vec[''],
      Vec\map($fp->getFunctions(), $f ==> $f->getNamespaceName()),
    );
  }
}
