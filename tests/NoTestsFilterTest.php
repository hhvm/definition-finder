<?hh
/*
 *  Copyright (c) 2015, Facebook, Inc.
 *  All rights reserved.
 *
 *  This source code is licensed under the BSD-style license found in the
 *  LICENSE file in the root directory of this source tree. An additional grant
 *  of patent rights can be found in the PATENTS file in the same directory.
 *
 */

use Facebook\DefinitionFinder\NoTestsFilter;
use Facebook\DefinitionFinder\TreeDefinitions;
use Facebook\DefinitionFinder\TreeWalker;

class NoTestsFilterTest extends PHPUnit_Framework_TestCase {
  private static function GetUnfilteredDefinitions(): TreeDefinitions {
    return new TreeWalker(realpath(__DIR__.'/../'));
  }

  private static function GetFilteredDefinitions(): TreeDefinitions {
    return NoTestsFilter::Filter(self::GetUnfilteredDefinitions());
  }

  public function testStillContainsLibrary(): void {
    $this->assertContains(
      'Facebook\DefinitionFinder\FileParser',
      self::GetFilteredDefinitions()->getClasses()->keys(),
    );
  }

  public function testDoesNotContainTestClasses(): void {
    $this->assertContains(
      __CLASS__,
      self::GetUnfilteredDefinitions()->getClasses()->keys(),
    );
    $this->assertNotContains(
      __CLASS__,
      self::GetFilteredDefinitions()->getClasses()->keys(),
    );
  }
}
