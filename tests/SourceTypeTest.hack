/*
 *  Copyright (c) 2015-present, Facebook, Inc.
 *  All rights reserved.
 *
 *  This source code is licensed under the MIT license found in the
 *  LICENSE file in the root directory of this source tree.
 *
 */

namespace Facebook\DefinitionFinder\Tests;

use function Facebook\FBExpect\expect;
use type Facebook\DefinitionFinder\{FileParser, SourceType};
use type Facebook\HackTest\DataProvider;

class SourceTypeTest extends \Facebook\HackTest\HackTest {

  public function getExamples(): vec<(string, SourceType)> {
    return vec[
      tuple('<?hh', SourceType::HACK_PARTIAL),
      tuple('<?hh // foo', SourceType::HACK_PARTIAL),
      tuple("<?hh\n// strict", SourceType::HACK_PARTIAL),
      tuple('<?hh // strict', SourceType::HACK_STRICT),
      tuple('<?hh //strict', SourceType::HACK_STRICT),
      tuple('<?hh // decl', SourceType::HACK_DECL),
    ];
  }

  <<DataProvider('getExamples')>>
  public async function testHasExpectedType(
    string $prefix,
    SourceType $expected,
  ): Awaitable<void> {
    $code = $prefix."\nclass Foo {}";
    $parser = await FileParser::fromDataAsync($code);
    expect($parser->getClass('Foo')->getSourceType())->toBeSame($expected);
  }

  public async function testPlainTextFileParses(): Awaitable<void> {
    $parser = await FileParser::fromDataAsync('foo');
    expect($parser->getClasses())->toBeEmpty();
    expect($parser->getFunctions())->toBeEmpty();
    expect($parser->getTypes())->toBeEmpty();
    expect($parser->getNewtypes())->toBeEmpty();
    expect($parser->getConstants())->toBeEmpty();
  }
}
