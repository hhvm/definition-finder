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

namespace Facebook\DefinitionFinder\Tests;

use Facebook\DefinitionFinder\FileParser;
use Facebook\DefinitionFinder\SourceType;

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
    $parser = FileParser::FromData($code);
    $this->assertSame($expected, $parser->getClass('Foo')->getSourceType());
  }

  public function testPlainTextFileParses(): void {
    $parser = FileParser::FromData('foo');
    $this->assertEmpty($parser->getClasses());
    $this->assertEmpty($parser->getFunctions());
    $this->assertEmpty($parser->getTypes());
    $this->assertEmpty($parser->getNewtypes());
    $this->assertEmpty($parser->getConstants());
  }
}
