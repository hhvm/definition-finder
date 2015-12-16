<?hh // strict
/*
 *  Copyright (c) 2015, Facebook, Inc.
 *  All rights reserved.
 *
 *  This source code is licensed under the BSD-style license found in the
 *  LICENSE file in the root directory of this source tree. An additional grant
 *  of patent rights can be found in the PATENTS file in the same directory.
 *
 */

namespace Facebook\DefinitionFinder\Test;

use Facebook\DefinitionFinder\FileParser;
use Facebook\DefinitionFinder\ScannedClass;

class XHPTest extends \PHPUnit_Framework_TestCase {
  public function testXHPRootClass(): void {
    $data = '<?hh class :foo:bar {}';

    $parser = FileParser::FromData($data);
    $this->assertContains(
      'xhp_foo__bar',
      $parser->getClassNames(),
    );
  }

  public function testXHPClasssWithParent(): void {
    $data = '<?hh class :foo:bar extends :herp:derp {}';

    $parser = FileParser::FromData($data);
    $this->assertContains(
      'xhp_foo__bar',
      $parser->getClassNames(),
    );

    $this->assertSame(
      'xhp_herp__derp',
      $parser->getClass('xhp_foo__bar')->getParentClassName(),
    );
  }

  public function testXHPEnumAttributeParses(): void {
    // XHP Attributes are not reported, but shouldn't cause parse errors
    $data = '<?hh class :foo:bar { attribute enum { "herp", "derp" } myattr @required; }';

    $parser = FileParser::FromData($data);
    $this->assertContains(
      'xhp_foo__bar',
      $parser->getClassNames(),
    );
  }
}
