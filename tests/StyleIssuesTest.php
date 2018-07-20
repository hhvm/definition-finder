<?hh // strict
/*
 *  Copyright (c) 2015-present, Facebook, Inc.
 *  All rights reserved.
 *
 *  This source code is licensed under the MIT license found in the
 *  LICENSE file in the root directory of this source tree.
 *
 */

namespace Facebook\DefinitionFinder\Test;

use type Facebook\DefinitionFinder\FileParser;
use namespace HH\Lib\Vec;
use function Facebook\FBExpect\expect;

final class StyleIssuesTest extends \PHPUnit_Framework_TestCase {
  public function testFunctionWithWhitespaceBeforeParamsList(): void {
    $data = '<?hh function foo ($bar) {};';
    $parser = FileParser::fromData($data);
    $fun = $parser->getFunction('foo');
    expect(Vec\map($fun->getParameters(), $x ==> $x->getName()))->toBeSame(
      vec['bar'],
    );
  }

  public function testFunctionWithWhitespaceBeforeReturnType(): void {
    $data = '<?hh function foo() : void {}';
    $parser = FileParser::fromData($data);
    $fun = $parser->getFunction('foo');
    expect($fun->getReturnType()?->getTypeText())->toBeSame('void');
  }

  public function testWhitespaceBetweenAttributes(): void {
    $data = '<?hh <<Herp, Derp>> function foo() {}';
    $parser = FileParser::fromData($data);
    $fun = $parser->getFunction('foo');
    expect(Vec\keys($fun->getAttributes()))->toBeSame(vec['Herp', 'Derp']);
  }

  public function testWhitespaceBetweenAttributesWithValue(): void {
    $data = '<?hh <<Herp("herpderp"), Derp>> function foo() {}';
    $parser = FileParser::fromData($data);
    $fun = $parser->getFunction('foo');
    expect($fun->getAttributes())->toBeSame(
      dict['Herp' => vec['herpderp'], 'Derp' => vec[]],
    );
  }

  public function testWhitespaceBetweenAttributeValues(): void {
    $data = '<?hh <<Foo("herp", "derp")>> function herp() {}';
    $parser = FileParser::fromData($data);
    $fun = $parser->getFunction('herp');
    expect($fun->getAttributes())->toBeSame(dict['Foo' => vec['herp', 'derp']]);
  }

  public function testWhitespaceBetweenConcatenatedAttributeParts(): void {
    $data = '<?hh <<Foo("herp". "derp")>> function herp() {}';
    $parser = FileParser::fromData($data);
    $fun = $parser->getFunction('herp');
    expect($fun->getAttributes())->toBeSame(dict['Foo' => vec['herpderp']]);
  }

  public function testTrailingCommaInAsyncReturnTuple(): void {
    $data = '<?hh async function herp(): Awaitable<(string, string, )> {}';
    $parser = FileParser::fromData($data);
    expect($parser->getFunctionNames())->toContain('herp');
  }
}
