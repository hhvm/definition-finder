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
use Facebook\DefinitionFinder\ScannedTypehint;

class StyleIssuesTest extends \PHPUnit_Framework_TestCase {
  public function testFunctionWithWhitespaceBeforeParamsList(): void {
    $data = '<?hh function foo ($bar) {};';
    $parser = FileParser::FromData($data);
    $fun = $parser->getFunction('foo');
    $this->assertEquals(
      Vector { 'bar' },
      $fun->getParameters()->map($x ==> $x->getName()),
    );
  }

  public function testFunctionWithWhitespaceBeforeReturnType(): void {
    $data = '<?hh function foo() : void {}';
    $parser = FileParser::FromData($data);
    $fun = $parser->getFunction('foo');
    $this->assertEquals(
      new ScannedTypehint('void', Vector { }),
      $fun->getReturnType(),
    );
  }

  public function testWhitespaceBetweenAttributes(): void {
    $data = '<?hh <<Herp, Derp>> function foo() {}';
    $parser = FileParser::FromData($data);
    $fun = $parser->getFunction('foo');
    $this->assertEquals(
      Vector {'Herp', 'Derp'},
      $fun->getAttributes()->keys(),
    );
  }

  public function testWhitespaceBetweenAttributesWithValue(): void {
    $data = '<?hh <<Herp("herpderp"), Derp>> function foo() {}';
    $parser = FileParser::FromData($data);
    $fun = $parser->getFunction('foo');
    $this->assertEquals(
      Map { 'Herp' => Vector { 'herpderp' }, 'Derp' => Vector {} },
      $fun->getAttributes(),
    );
  }

  public function testWhitespaceBetweenAttributeValues(): void {
    $data = '<?hh <<Foo("herp", "derp")>> function herp() {}';
    $parser = FileParser::FromData($data);
    $fun = $parser->getFunction('herp');
    $this->assertEquals(
      Map { 'Foo' => Vector { 'herp', 'derp' } },
      $fun->getAttributes(),
    );
  }
}
