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

use type Facebook\DefinitionFinder\{
  FileParser,
  ScannedClassish,
};
use function Facebook\FBExpect\expect;
use namespace HH\Lib\C;

final class XHPTest extends \PHPUnit_Framework_TestCase {
  public function testXHPRootClass(): void {
    $data = '<?hh class :foo:bar {}';

    $parser = FileParser::FromData($data);
    expect($parser->getClassNames())->toContain('xhp_foo__bar');
  }

  public function testNullableXHPReturn(): void {
    $data = '<?hh function foo(): ?:foo:bar {}';
    $parser = FileParser::FromData($data);
    $function = $parser->getFunction('foo');
    $ret = $function->getReturnType();
    $ret = expect($ret)->toNotBeNull();
    $this->assertSame('xhp_foo__bar', $ret->getTypeName());
    $this->assertTrue($ret->isNullable());
  }

  public function testXHPClassWithParent(): void {
    $data = '<?hh class :foo:bar extends :herp:derp {}';

    $parser = FileParser::FromData($data);
    expect($parser->getClassNames())->toContain('xhp_foo__bar');

    $this->assertSame(
      'xhp_herp__derp',
      $parser->getClass('xhp_foo__bar')->getParentClassName(),
    );
  }

  public function testXHPEnumAttributeParses(): void {
    // XHP Attributes are not reported, but shouldn't cause parse errors
    $data =
      '<?hh class :foo:bar { attribute enum { "herp", "derp" } myattr @required; }';

    $parser = FileParser::FromData($data);
    expect($parser->getClassNames())->toContain('xhp_foo__bar');
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
    expect($parser->getClassNames())->toContain('xhp_example');
  }

  public function testXHPClassNamesAreCorrect(): void {
    $parser = FileParser::FromData('<?hh class :foo:bar:baz:herp-derp {}');

    $this->assertContains(
      /* UNSAFE_EXPR */ :foo:bar:baz:herp-derp::class,
      C\onlyx($parser->getClassNames()),
    );
  }
}
