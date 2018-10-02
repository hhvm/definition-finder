<?hh // strict
/*
 *  Copyright (c) 2015-present, Facebook, Inc.
 *  All rights reserved.
 *
 *  This source code is licensed under the MIT license found in the
 *  LICENSE file in the root directory of this source tree.
 *
 */

use function Facebook\FBExpect\expect;
use type Facebook\DefinitionFinder\FileParser;
use namespace HH\Lib\Vec;

/**
 * `namespace\foo` means 'foo in the current namespace - see
 * http://php.net/manual/en/language.namespaces.nsconstants.php example 4
 */
final class RelativeNamespaceTest extends Facebook\HackTest\HackTest {
  public function testFunctionBodyUsesRelativeNamespace(): void {
    $code = '<?php function foo() { namespace\bar(); } function baz() {}';
    $fp = FileParser::fromData($code);
    expect($fp->getFunctionNames())->toBeSame(vec['foo', 'baz']);

    expect(Vec\map($fp->getFunctions(), $f ==> $f->getNamespaceName()))
      ->toBeSame(vec['', '']);
  }

  public function testPseudomainUsesRelativeNamespace(): void {
    $code = '<?php namespace\foo(); function bar() {}';
    $fp = FileParser::fromData($code);
    expect($fp->getFunctionNames())->toBeSame(vec['bar']);

    expect(Vec\map($fp->getFunctions(), $f ==> $f->getNamespaceName()))
      ->toBeSame(vec['']);
  }
}
