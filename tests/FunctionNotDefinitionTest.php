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

/**
 * 'function' is a valid keyword in several contexts other than when definining
 * a function; make sure they're not considered a function.
 */
final class FunctionNotDefinitionTest extends PHPUnit_Framework_TestCase {
  public function testActuallyAFunction(): void {
    $p = FileParser::fromData('<?hh function foo();');
    expect($p->getFunctionNames())->toBeSame(vec['foo']);
  }

  public function testFunctionTypeAlias(): void {
    $p = FileParser::fromData('<?hh newtype Foo = (function(int): void);');
    expect($p->getFunctionNames())->toBeSame(vec[]);
    expect($p->getNewtypeNames())->toBeSame(vec['Foo']);

    // Add extra whitespace
    $p = FileParser::fromData('<?hh newtype Foo = (function (int): void);');
    expect($p->getFunctionNames())->toBeSame(vec[]);
    expect($p->getNewtypeNames())->toBeSame(vec['Foo']);
  }

  public function testFunctionReturnType(): void {
    $p = FileParser::fromData(<<<EOF
<?hh
function foo(\$bar): (function():void) { return \$bar; }
EOF
    );
    expect($p->getFunctionNames())->toBeSame(vec['foo']);
    $rt = $p->getFunction('foo')->getReturnType();

    expect($rt?->getTypeName())->toBeSame('callable');
    expect($rt?->getTypeText())->toBeSame('(function():void)');
  }

  public function testReturnsGenericCallable(): void {
    $code = '<?hh function foo(): (function():vec<string>) { }';
    $p = FileParser::fromData($code);
    expect($p->getFunctionNames())->toBeSame(vec['foo']);

    $rt = $p->getFunction('foo')->getReturnType();
    expect($rt?->getTypeName())->toBeSame('callable');
    expect($rt?->getTypeText())->toBeSame('(function():vec<string>)');
  }

  public function testAsParameterType(): void {
    $p = FileParser::fromData(
      '<?hh function foo((function():void) $callback) { }',
    );
    expect($p->getFunctionNames())->toBeSame(vec['foo']);
  }

  public function testUsingAnonymousFunctions(): void {
    $p = FileParser::fromData(<<<EOF
<?hh
function foo() {
  \$x = function() { return 'bar'; };
  return \$x();
}
EOF
    );
    expect($p->getFunctionNames())->toBeSame(vec['foo']);
  }

  public function testAsParameter(): void {
    $p = FileParser::fromData(<<<EOF
<?php
spl_autoload_register(function(\$class) { });
function foo() { }
EOF
    );
    expect($p->getFunctionNames())->toBeSame(vec['foo']);
  }

  public function testAsRVal(): void {
    $p = FileParser::fromData('<?php $f = function(){};');
    expect($p->getFunctionNames())->toBeEmpty();
  }
}
