/*
 *  Copyright (c) 2015-present, Facebook, Inc.
 *  All rights reserved.
 *
 *  This source code is licensed under the MIT license found in the
 *  LICENSE file in the root directory of this source tree.
 *
 */

namespace Facebook\DefinitionFinder\Test;

use type Facebook\DefinitionFinder\FileParser;
use function Facebook\FBExpect\expect;
use namespace HH\Lib\C;

final class XHPTest extends \Facebook\HackTest\HackTest {
  public async function testXHPRootClass(): Awaitable<void> {
    $data = '<?hh class :foo:bar {}';

    $parser = await FileParser::fromDataAsync($data);
    expect($parser->getClassNames())->toContain('xhp_foo__bar');
  }

  public async function testNullableXHPReturn(): Awaitable<void> {
    $data = '<?hh function foo(): ?:foo:bar {}';
    $parser = await FileParser::fromDataAsync($data);
    $function = $parser->getFunction('foo');
    $ret = $function->getReturnType();
    $ret = expect($ret)->toNotBeNull();
    expect($ret->getTypeName())->toBeSame('xhp_foo__bar');
    expect($ret->isNullable())->toBeTrue();
  }

  public async function testXHPClassWithParent(): Awaitable<void> {
    $data = '<?hh class :foo:bar extends :herp:derp {}';

    $parser = await FileParser::fromDataAsync($data);
    expect($parser->getClassNames())->toContain('xhp_foo__bar');

    expect($parser->getClass('xhp_foo__bar')->getParentClassName())->toBeSame(
      'xhp_herp__derp',
    );
  }

  public async function testXHPEnumAttributeParses(): Awaitable<void> {
    // XHP Attributes are not reported, but shouldn't cause parse errors
    $data =
      '<?hh class :foo:bar { attribute enum { "herp", "derp" } myattr @required; }';

    $parser = await FileParser::fromDataAsync($data);
    expect($parser->getClassNames())->toContain('xhp_foo__bar');
  }

  public async function testXHPEnumAttributesParse(): Awaitable<void> {
    // StatementConsumer was getting confused by the brace
    $data = <<<EOF
<?hh class :example {
  attribute
    enum { "foo", "bar" } myattr @required,
    enum { "herp", "derp" } myattr2 @required;
}
EOF;

    $parser = await FileParser::fromDataAsync($data);
    expect($parser->getClassNames())->toContain('xhp_example');
  }

  public async function testXHPClassNamesAreCorrect(): Awaitable<void> {
    // must be the same namespace as this test, otherwise the ::class literal
    // below resolves to a differently namespaced name (except on older versions
    // of HHVM which don't handle namespaced XHP classes correctly)
    $parser = await FileParser::fromDataAsync('
      namespace Facebook\DefinitionFinder\Test {
        class :foo:bar:baz:herp-derp {}
      }
    ');
    expect(C\onlyx($parser->getClassNames()))->toContainSubstring(
      :foo:bar:baz:herp-derp::class,
    );
  }
}

// This is here so that we can use a ::class literal above without FIXMEs.
final class :foo:bar:baz:herp-derp {}
