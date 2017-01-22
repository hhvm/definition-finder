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

use \Facebook\DefinitionFinder\FileParser;

/**
 * `namespace\foo` means 'foo in the current namespace - see
 * http://php.net/manual/en/language.namespaces.nsconstants.php example 4
 */
final class RelativeNamespaceTest extends PHPUnit_Framework_TestCase {
  public function testFunctionBodyUsesRelativeNamespace(): void {
    $code = '<?php function foo() { namespace\bar(); } function baz() {}';
    $fp = FileParser::FromData($code);
    $this->assertEquals(
      array('foo', 'baz'),
      $fp->getFunctionNames()->toArray(),
    );

    $this->assertEquals(
      array('', ''),
      $fp->getFunctions()->map($f ==> $f->getNamespaceName())->toArray(),
    );
  }

  public function testPseudomainUsesRelativeNamespace(): void {
    $code = '<?php namespace\foo(); function bar() {}';
    $fp = FileParser::FromData($code);
    $this->assertEquals(
      array('bar'),
      $fp->getFunctionNames()->toArray(),
    );

    $this->assertEquals(
      array(''),
      $fp->getFunctions()->map($f ==> $f->getNamespaceName())->toArray(),
    );
  }
}
