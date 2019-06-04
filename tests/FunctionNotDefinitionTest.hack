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
final class FunctionNotDefinitionTest extends Facebook\HackTest\HackTest {
  public async function testActuallyAFunction(): Awaitable<void> {
    $p = await FileParser::fromDataAsync('<?hh function foo();');
    expect($p->getFunctionNames())->toBeSame(vec['foo']);
  }

  public async function testFunctionTypeAlias(): Awaitable<void> {
    $p = await FileParser::fromDataAsync(
      '<?hh newtype Foo = (function(int): Awaitable<void>);',
    );
    expect($p->getFunctionNames())->toBeSame(vec[]);
    expect($p->getNewtypeNames())->toBeSame(vec['Foo']);

    // Add extra whitespace
    $p = await FileParser::fromDataAsync(
      '<?hh newtype Foo = (function (int): Awaitable<void>);',
    );
    expect($p->getFunctionNames())->toBeSame(vec[]);
    expect($p->getNewtypeNames())->toBeSame(vec['Foo']);
  }

  public async function testFunctionReturnType(): Awaitable<void> {
    $p = await FileParser::fromDataAsync(<<<EOF
<?hh
function foo(\$bar): (function():void) { return \$bar; }
EOF
    );
    expect($p->getFunctionNames())->toBeSame(vec['foo']);
    $rt = $p->getFunction('foo')->getReturnType();

    expect($rt?->getTypeName())->toBeSame('callable');
    expect($rt?->getTypeText())->toBeSame('(function():void)');
  }

  public async function testReturnsGenericCallable(): Awaitable<void> {
    $code = '<?hh function foo(): (function():vec<string>) { }';
    $p = (await FileParser::fromDataAsync($code));
    expect($p->getFunctionNames())->toBeSame(vec['foo']);

    $rt = $p->getFunction('foo')->getReturnType();
    expect($rt?->getTypeName())->toBeSame('callable');
    expect($rt?->getTypeText())->toBeSame('(function():vec<string>)');
  }

  public async function testAsParameterType(): Awaitable<void> {
    $p = await FileParser::fromDataAsync(
      '<?hh function foo((function():void) $callback) { }',
    );
    expect($p->getFunctionNames())->toBeSame(vec['foo']);
  }

  public async function testUsingAnonymousFunctions(): Awaitable<void> {
    $p = await FileParser::fromDataAsync(<<<EOF
<?hh
function foo() {
  \$x = function() { return 'bar'; };
  return \$x();
}
EOF
    );
    expect($p->getFunctionNames())->toBeSame(vec['foo']);
  }

  public async function testAsParameter(): Awaitable<void> {
    $p = await FileParser::fromDataAsync(<<<EOF
<?php
spl_autoload_register(function(\$class) { });
function foo() { }
EOF
    );
    expect($p->getFunctionNames())->toBeSame(vec['foo']);
  }

  public async function testAsRVal(): Awaitable<void> {
    $p = await FileParser::fromDataAsync('<?php $f = function(){};');
    expect($p->getFunctionNames())->toBeEmpty();
  }
}
