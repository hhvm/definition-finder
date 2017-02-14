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

final class MultiNamespacePHPTest extends PHPUnit_Framework_TestCase {
  private ?Facebook\DefinitionFinder\FileParser $parser;

  protected function setUp(): void {
    $this->parser = \Facebook\DefinitionFinder\FileParser::FromFile(
      __DIR__.'/data/multi_namespace_php.php',
    );
  }

  public function testClasses(): void {
    $this->assertEquals(
      Vector {
        'Foo\\Bar',
        'Herp\\Derp',
        'EmptyNamespace',
      },
      $this->parser?->getClassNames(),
    );
  }
}
