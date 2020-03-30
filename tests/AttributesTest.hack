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
  ScannedDefinition,
  ScannedFunction,
};

use namespace HH\Lib\Vec;
use type Facebook\HackTest\DataProvider;
use function Facebook\FBExpect\expect;

class AttributesTest extends \Facebook\HackTest\HackTest {
  private vec<ScannedClassish> $classes = vec[];
  private vec<ScannedFunction> $functions = vec[];

  <<__Override>>
  public async function beforeEachTestAsync(): Awaitable<void> {
    $parser = await FileParser::fromFileAsync(__DIR__.'/data/attributes.php');
    $this->classes = $parser->getClasses();
    $this->functions = $parser->getFunctions();
  }

  public async function testSingleSimpleAttribute(): Awaitable<void> {
    $class = $this->findClass('ClassWithSimpleAttribute');
    expect($class->getAttributes())->toBeSame(dict['Foo' => vec[]]);
  }

  public async function testMultipleSimpleAttributes(): Awaitable<void> {
    $class = $this->findClass('ClassWithSimpleAttributes');
    expect($class->getAttributes())->toBeSame(
      dict['Foo' => vec[], 'Bar' => vec[]],
    );
  }

  public async function testWithSingleStringAttribute(): Awaitable<void> {
    $class = $this->findClass('ClassWithStringAttribute');
    expect($class->getAttributes())->toBeSame(dict['Herp' => vec['derp']]);
  }

  public async function testWithFormattedAttributes(): Awaitable<void> {
    $class = $this->findClass('ClassWithFormattedAttributes');
    expect($class->getAttributes())->toBeSame(
      dict['Foo' => vec[], 'Bar' => vec['herp', 'derp']],
    );
  }

  public async function testWithFormattedArrayAttribute(): Awaitable<void> {
    $class = $this->findClass('ClassWithFormattedArrayAttribute');
    expect($class->getAttributes())->toBeSame(dict['Bar' => vec[vec['herp']]]);
  }

  public async function testWithSingleIntAttribute(): Awaitable<void> {
    $class = $this->findClass('ClassWithIntAttribute');
    expect($class->getAttributes())->toBeSame(dict['Herp' => vec[123]]);
    // Check it's an int, not a string
    expect($class->getAttributes()['Herp'][0])->toBeSame(123);
  }

  public async function testFunctionHasAttributes(): Awaitable<void> {
    $func = $this->findScanned($this->functions, 'function_after_classes');
    expect($func->getAttributes())->toBeSame(dict['FunctionFoo' => vec[]]);
  }

  public async function testFunctionContainingBitShift(): Awaitable<void> {
    $data = '<?hh function foo() { 1 << 3; }';
    $parser = (await FileParser::fromDataAsync($data));
    $fun = $parser->getFunction('foo');
    expect($fun->getAttributes())->toBeEmpty();
  }

  public async function testPseudmainContainingBitShift(): Awaitable<void> {
    $data = '<?hh print 1 << 3;';
    await FileParser::fromDataAsync($data);
  }

  public async function testFunctionAttrsDontPolluteClass(): Awaitable<void> {
    $class = $this->findClass('ClassAfterFunction');
    expect($class->getAttributes())->toBeSame(dict['ClassFoo' => vec[]]);
  }

  public async function testParameterHasAttribute(): Awaitable<void> {
    $data = '<?hh function foo(<<Bar>> $baz) {}';
    $parser = (await FileParser::fromDataAsync($data));
    $fun = $parser->getFunction('foo');
    $params = $fun->getParameters();
    expect(Vec\map($params, $x ==> $x->getName()))->toBeSame(vec['baz']);

    expect(Vec\map($params, $x ==> $x->getAttributes()))->toBeSame(
      vec[dict['Bar' => vec[]]],
    );
  }

  public function attributeExpressions(): varray<(string, mixed)> {
    return varray[
      tuple("'herp'.'derp'", 'herpderp'),
      tuple("Foo\\Bar::class", "Foo\\Bar"),
      tuple('true', true),
      tuple('false', false),
      tuple('null', null),
      tuple('INF', \INF),
      tuple('+123', 123),
      tuple('-123', -123),
      tuple('array()', varray[]),
      tuple('array(123)', varray[123]),
      tuple('array(123,)', varray[123]),
      tuple('array(123,456)', varray[123, 456]),
      tuple('array(123,456,)', varray[123, 456]),
      tuple('1.23', 1.23),
      tuple('array(123,456)', varray[123, 456]),
      tuple('array(123 , 456)', varray[123, 456]),
      tuple('array(123 => 456)', darray[123 => 456]),
      tuple('shape()', varray[]),
      tuple(
        'shape("foo" => "bar", "herp" => 123)',
        shape('foo' => 'bar', 'herp' => 123),
      ),
      tuple('vec[123]', vec[123]),
      tuple("vec['foo']", vec['foo']),
      tuple('keyset[123]', keyset[123]),
      tuple("dict[123 => '456']", dict[123 => '456']),
      tuple("\n<<<EOF\nHello!\nEOF\n", 'Hello!'),
      tuple("\n<<<'EOF'\nHello!\nEOF\n", 'Hello!'),
      tuple('010', 8),
      tuple('0x10', 16),
      tuple('0b10', 2),
    ];
  }

  <<DataProvider('attributeExpressions')>>
  public async function testAttributeExpression(
    string $source,
    mixed $expected,
  ): Awaitable<void> {
    $data = '<?hh <<MyAttr('.$source.')>> function foo(){}';
    $parser = await FileParser::fromDataAsync($data, $source);
    $fun = $parser->getFunction('foo');
    expect($fun->getAttributes())
      ->toBeSame(dict['MyAttr' => vec[$expected]]);
  }

  private function findScanned<T as ScannedDefinition>(
    vec<T> $container,
    string $name,
  ): T {
    foreach ($container as $scanned) {
      if ($scanned->getName() === "Facebook\\DefinitionFinder\\Test\\".$name) {
        return $scanned;
      }
    }
    invariant_violation('Could not find scanned %s', $name);
  }

  private function findClass(string $name): ScannedClassish {
    return $this->findScanned($this->classes, $name);
  }
}
