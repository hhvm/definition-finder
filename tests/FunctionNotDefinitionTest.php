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

use \Facebook\DefinitionFinder\FileParser;

/**
 * 'function' is a valid keyword in several contexts other than when definining
 * a function; make sure they're not considered a function.
 */
final class FunctionNotDefinitionTest extends PHPUnit_Framework_TestCase {
  public function testActuallyAFunction(): void {
    $p = FileParser::FromData('<?hh function foo();');
    $this->assertEquals(vec['foo'], $p->getFunctionNames());
  }

  public function testFunctionTypeAlias(): void {
    $p = FileParser::FromData('<?hh newtype Foo = function(int): void;');
    $this->assertEquals(vec[], $p->getFunctionNames());
    $this->assertEquals(vec['Foo'], $p->getNewtypeNames());

    // Add extra whitespace
    $p = FileParser::FromData('<?hh newtype Foo = function (int): void;');
    $this->assertEquals(vec[], $p->getFunctionNames());
    $this->assertEquals(vec['Foo'], $p->getNewtypeNames());
  }

  public function testFunctionReturnType(): void {
    $p = FileParser::FromData(<<<EOF
<?hh
function foo(\$bar): (function():void) { return \$bar; }
EOF
    );
    $this->assertEquals(vec['foo'], $p->getFunctionNames());
    $rt = $p->getFunction('foo')->getReturnType();

    $this->assertSame('callable', $rt?->getTypeName());
    $this->assertSame('(function():void)', $rt?->getTypeText());
  }

  public function testReturnsGenericCallable(): void {
    $code = '<?hh function foo(): (function():vec<string>) { }';
    $p = FileParser::FromData($code);
    $this->assertEquals(vec['foo'], $p->getFunctionNames());

    $rt = $p->getFunction('foo')->getReturnType();
    $this->assertSame('callable', $rt?->getTypeName());
    $this->assertSame('(function():vec<string>)', $rt?->getTypeText());
  }

  public function testAsParameterType(): void {
    $p = FileParser::FromData(
      '<?hh function foo((function():void) $callback) { }',
    );
    $this->assertEquals(vec['foo'], $p->getFunctionNames());
  }

  public function testUsingAnonymousFunctions(): void {
    $p = FileParser::FromData(<<<EOF
<?hh
function foo() {
  \$x = function() { return 'bar'; };
  return \$x();
}
EOF
    );
    $this->assertEquals(vec['foo'], $p->getFunctionNames());
  }

  public function testAsParameter(): void {
    $p = FileParser::FromData(<<<EOF
<?php
spl_autoload_register(function(\$class) { });
function foo() { }
EOF
    );
    $this->assertEquals(vec['foo'], $p->getFunctionNames());
  }

  public function testAsRVal(): void {
    $p = FileParser::FromData('<?php $f = function(){};');
    $this->assertEmpty($p->getFunctionNames());
  }
}
