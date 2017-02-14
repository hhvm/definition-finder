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

  public function testNullableXHPReturn(): void {
    $data = '<?hh function foo(): ?:foo:bar {}';
    $parser = FileParser::FromData($data);
    $function = $parser->getFunction('foo');
    $ret = $function->getReturnType();
    $this->assertNotNull($ret);
    assert($ret !== null); // typechecker
    $this->assertSame('xhp_foo__bar', $ret->getTypeName());
    $this->assertTrue($ret->isNullable());
  }

  public function testXHPClassWithParent(): void {
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

  public function testXHPEnumAttributesParse(): void {
    // StatementConsumer was getting confused by the brace
    $data = <<<EOF
<?hh class :example {
  attribute
    enum { "foo", "bar" } myattr @required,
    enum { "herp", "derp" } myattr2 @required;
}
EOF;

    $parser = FileParser::FromData($data);
    $this->assertContains(
      'xhp_example',
      $parser->getClassNames(),
    );
  }

  public function testXHPClassNamesAreCorrect(): void {
    $parser = FileParser::FromData('<?hh class :foo:bar:baz:herp-derp {}');

    $this->assertContains(
      /* UNSAFE_EXPR */ :foo:bar:baz:herp-derp::class,
      $parser->getClassNames()->get(0)
    );
  }
}
