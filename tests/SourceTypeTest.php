<?hh // strict
/*
 *  Copyright (c) 2015-present, Facebook, Inc.
 *  All rights reserved.
 *
 *  This source code is licensed under the MIT license found in the
 *  LICENSE file in the root directory of this source tree.
 *
 */

namespace Facebook\DefinitionFinder\Tests;

use type Facebook\DefinitionFinder\FileParser;
use function Facebook\FBExpect\expect;
use type Facebook\DefinitionFinder\SourceType;

class SourceTypeTest extends \PHPUnit_Framework_TestCase {

  public function getExamples(): array<(string, SourceType)> {
    return [
      tuple('<?hh', SourceType::HACK_PARTIAL),
      tuple('<?hh // foo', SourceType::HACK_PARTIAL),
      tuple("<?hh\n// strict", SourceType::HACK_PARTIAL),
      tuple("<?hh // strict", SourceType::HACK_STRICT),
      tuple("<?hh //strict", SourceType::HACK_STRICT),
      tuple("<?hh // decl", SourceType::HACK_DECL),
      tuple('<?php', SourceType::PHP),
      tuple('<?', SourceType::PHP),
    ];
  }

  /**
   * @dataProvider getExamples
   */
  public function testHasExpectedType(
    string $prefix,
    SourceType $expected,
  ): void {
    $code = $prefix."\nclass Foo {}";
    $parser = FileParser::fromData($code);
    expect($parser->getClass('Foo')->getSourceType())->toBeSame($expected);
  }

  public function testPlainTextFileParses(): void {
    $parser = FileParser::fromData('foo');
    expect($parser->getClasses())->toBeEmpty();
    expect($parser->getFunctions())->toBeEmpty();
    expect($parser->getTypes())->toBeEmpty();
    expect($parser->getNewtypes())->toBeEmpty();
    expect($parser->getConstants())->toBeEmpty();
  }
}
