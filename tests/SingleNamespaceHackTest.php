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

use function Facebook\FBExpect\expect;
use type Facebook\DefinitionFinder\FileParser;

final class SingleNamespaceHackTest extends \AbstractHackTest {
  <<__Override>>
  protected function getFilename(): string {
    return 'single_namespace_hack.php';
  }

  <<__Override>>
  protected function getPrefix(): string {
    return 'SingleNamespace\\';
  }

  public function testConsistentNames(): void {
    $data = "<?hh\n".
      "class Herp extends Foo\Bar {}\n".
      "class Derp extends \Foo\Bar {}\n";

    $parser = FileParser::fromData($data);
    $herp = $parser->getClass('Herp');
    $derp = $parser->getClass('Derp');

    expect($herp->getParentClassName())->toBeSame("Foo\\Bar");
    expect($derp->getParentClassName())->toBeSame($herp->getParentClassName());
  }

  <<__Override>>
  protected function getSuffixForRootDefinitions(): string {
    return '_FROM_SINGLE_NS';
  }
}
