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

namespace Facebook\DefinitionFinder\Test;

use type Facebook\DefinitionFinder\{
  FileParser,
  ScannedTypehint,
};
use namespace HH\Lib\Vec;
use function Facebook\FBExpect\expect;

final class StyleIssuesTest extends \PHPUnit_Framework_TestCase {
  public function testFunctionWithWhitespaceBeforeParamsList(): void {
    $data = '<?hh function foo ($bar) {};';
    $parser = FileParser::FromData($data);
    $fun = $parser->getFunction('foo');
    $this->assertEquals(
      vec['bar'],
      Vec\map($fun->getParameters(), $x ==> $x->getName()),
    );
  }

  public function testFunctionWithWhitespaceBeforeReturnType(): void {
    $data = '<?hh function foo() : void {}';
    $parser = FileParser::FromData($data);
    $fun = $parser->getFunction('foo');
    $this->assertEquals(
      new ScannedTypehint('void', 'void', vec[], false),
      $fun->getReturnType(),
    );
  }

  public function testWhitespaceBetweenAttributes(): void {
    $data = '<?hh <<Herp, Derp>> function foo() {}';
    $parser = FileParser::FromData($data);
    $fun = $parser->getFunction('foo');
    $this->assertEquals(
      vec['Herp', 'Derp'],
      Vec\keys($fun->getAttributes()),
    );
  }

  public function testWhitespaceBetweenAttributesWithValue(): void {
    $data = '<?hh <<Herp("herpderp"), Derp>> function foo() {}';
    $parser = FileParser::FromData($data);
    $fun = $parser->getFunction('foo');
    $this->assertEquals(
      dict['Herp' => vec['herpderp'], 'Derp' => vec[]],
      $fun->getAttributes(),
    );
  }

  public function testWhitespaceBetweenAttributeValues(): void {
    $data = '<?hh <<Foo("herp", "derp")>> function herp() {}';
    $parser = FileParser::FromData($data);
    $fun = $parser->getFunction('herp');
    $this->assertEquals(
      dict['Foo' => vec['herp', 'derp']],
      $fun->getAttributes(),
    );
  }

  public function testWhitespaceBetweenConcatenatedAttributeParts(): void {
    $data = '<?hh <<Foo("herp". "derp")>> function herp() {}';
    $parser = FileParser::FromData($data);
    $fun = $parser->getFunction('herp');
    $this->assertEquals(
      dict['Foo' => vec['herpderp']],
      $fun->getAttributes(),
    );
  }

  public function testTrailingCommaInAsyncReturnTuple(): void {
    $data = '<?hh async function herp(): Awaitable<(string, string, )> {}';
    $parser = FileParser::FromData($data);
    expect($parser->getFunctionNames())->toContain('herp');
  }
}
