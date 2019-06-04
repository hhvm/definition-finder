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

final class StyleIssuesTest extends \Facebook\HackTest\HackTest {
  public async function testFunctionWithWhitespaceBeforeParamsList(
  ): Awaitable<void> {
    $data = '<?hh function foo ($bar) {};';
    $parser = await FileParser::fromDataAsync($data);
    $fun = $parser->getFunction('foo');
    expect(Vec\map($fun->getParameters(), $x ==> $x->getName()))->toBeSame(
      vec['bar'],
    );
  }

  public async function testFunctionWithWhitespaceBeforeReturnType(
  ): Awaitable<void> {
    $data = '<?hh function foo() : void {}';
    $parser = await FileParser::fromDataAsync($data);
    $fun = $parser->getFunction('foo');
    expect($fun->getReturnType()?->getTypeText())->toBeSame('void');
  }

  public async function testWhitespaceBetweenAttributes(): Awaitable<void> {
    $data = '<?hh <<Herp, Derp>> function foo() {}';
    $parser = await FileParser::fromDataAsync($data);
    $fun = $parser->getFunction('foo');
    expect(Vec\keys($fun->getAttributes()))->toBeSame(vec['Herp', 'Derp']);
  }

  public async function testWhitespaceBetweenAttributesWithValue(
  ): Awaitable<void> {
    $data = '<?hh <<Herp("herpderp"), Derp>> function foo() {}';
    $parser = await FileParser::fromDataAsync($data);
    $fun = $parser->getFunction('foo');
    expect($fun->getAttributes())->toBeSame(
      dict['Herp' => vec['herpderp'], 'Derp' => vec[]],
    );
  }

  public async function testWhitespaceBetweenAttributeValues(
  ): Awaitable<void> {
    $data = '<?hh <<Foo("herp", "derp")>> function herp() {}';
    $parser = await FileParser::fromDataAsync($data);
    $fun = $parser->getFunction('herp');
    expect($fun->getAttributes())->toBeSame(dict['Foo' => vec['herp', 'derp']]);
  }

  public async function testWhitespaceBetweenConcatenatedAttributeParts(
  ): Awaitable<void> {
    $data = '<?hh <<Foo("herp". "derp")>> function herp() {}';
    $parser = await FileParser::fromDataAsync($data);
    $fun = $parser->getFunction('herp');
    expect($fun->getAttributes())->toBeSame(dict['Foo' => vec['herpderp']]);
  }

  public async function testTrailingCommaInAsyncReturnTuple(): Awaitable<void> {
    $data = '<?hh async function herp(): Awaitable<(string, string, )> {}';
    $parser = await FileParser::fromDataAsync($data);
    expect($parser->getFunctionNames())->toContain('herp');
  }
}
