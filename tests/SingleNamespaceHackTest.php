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

use Facebook\DefinitionFinder\FileParser;

final class SingleNamespaceHackTest extends \AbstractHackTest {
  protected function getFilename(): string {
    return 'single_namespace_hack.php';
  }

  protected function getPrefix(): string {
    return 'SingleNamespace\\';
  }

  public function testConsistentNames(): void {
    $data =
      "<?hh\n".
      "class Herp extends Foo\Bar {}\n".
      "class Derp extends \Foo\Bar {}\n";

    $parser = FileParser::FromData($data);
    $herp = $parser->getClass('Herp');
    $derp = $parser->getClass('Derp');

    $this->assertSame(
      'Foo\Bar',
      $herp->getParentClassName(),
    );
    $this->assertSame(
      $herp->getParentClassName(),
      $derp->getParentClassName(),
    );
  }
}
