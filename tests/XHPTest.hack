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
    $data = '<?hh xhp class foo:bar {}';

    $parser = await FileParser::fromDataAsync($data);
    expect($parser->getClassNames())->toContain('foo\\bar');
  }

  public async function testXHPClassWithParent(): Awaitable<void> {
    $data = '<?hh xhp class foo:bar extends herp\\derp {}';

    $parser = await FileParser::fromDataAsync($data);
    expect($parser->getClassNames())->toContain('foo\\bar');

    expect($parser->getClass('foo\\bar')->getParentClassName())->toBeSame(
      'herp\\derp',
    );
  }

  public async function testXHPEnumAttributeParses(): Awaitable<void> {
    // XHP Attributes are not reported, but shouldn't cause parse errors
    $data =
      '<?hh xhp class foo:bar { attribute enum { "herp", "derp" } myattr @required; }';

    $parser = await FileParser::fromDataAsync($data);
    expect($parser->getClassNames())->toContain('foo\\bar');
  }

  public async function testXHPEnumAttributesParse(): Awaitable<void> {
    // StatementConsumer was getting confused by the brace
    $data = <<<EOF
<?hh xhp class example {
  attribute
    enum { "foo", "bar" } myattr @required,
    enum { "herp", "derp" } myattr2 @required;
}
EOF;

    $parser = await FileParser::fromDataAsync($data);
    expect($parser->getClassNames())->toContain('example');
  }

  public async function testXHPClassNamesAreCorrect(): Awaitable<void> {
    $parser = await FileParser::fromFileAsync(__DIR__.'/data/xhp.hack');
    expect(C\onlyx($parser->getClassNames()))->toContainSubstring(
      \facebook_definition_finder_test_xhp_class_for_classname()
    );
  }
}
