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
  ScannedDefinition,
  ScannedClassish,
  ScannedFunction,
};

use namespace HH\Lib\Vec;
use function Facebook\FBExpect\expect;

class AttributesTest extends \PHPUnit_Framework_TestCase {
  private vec<ScannedClassish> $classes = vec[];
  private vec<ScannedFunction> $functions = vec[];

  <<__Override>>
  protected function setUp(): void {
    $parser = FileParser::fromFile(__DIR__.'/data/attributes.php');
    $this->classes = $parser->getClasses();
    $this->functions = $parser->getFunctions();
  }

  public function testSingleSimpleAttribute(): void {
    $class = $this->findClass('ClassWithSimpleAttribute');
    $this->assertEquals(dict["Foo" => vec[]], $class->getAttributes());
  }

  public function testMultipleSimpleAttributes(): void {
    $class = $this->findClass('ClassWithSimpleAttributes');
    $this->assertEquals(
      dict["Foo" => vec[], "Bar" => vec[]],
      $class->getAttributes(),
    );
  }

  public function testWithSingleStringAttribute(): void {
    $class = $this->findClass('ClassWithStringAttribute');
    $this->assertEquals(
      dict['Herp' => vec['derp']],
      $class->getAttributes(),
    );
  }

  public function testWithFormattedAttributes(): void {
    $class = $this->findClass('ClassWithFormattedAttributes');
    $this->assertEquals(
      dict['Foo' => vec[], 'Bar' => vec['herp', 'derp']],
      $class->getAttributes(),
    );
  }

  public function testWithFormattedArrayAttribute(): void {
    $class = $this->findClass('ClassWithFormattedArrayAttribute');
    $this->assertEquals(
      dict['Bar' => vec[['herp']]],
      $class->getAttributes(),
    );
  }

  public function testWithSingleIntAttribute(): void {
    $class = $this->findClass('ClassWithIntAttribute');
    $this->assertEquals(
      dict['Herp' => vec[123]],
      $class->getAttributes(),
    );
    // Check it's an int, not a string
    $this->assertSame(123, $class->getAttributes()['Herp'][0]);
  }

  public function testFunctionHasAttributes(): void {
    $func = $this->findScanned($this->functions, 'function_after_classes');
    $this->assertEquals(
      dict['FunctionFoo' => vec[]],
      $func->getAttributes(),
    );
  }

  public function testFunctionContainingBitShift(): void {
    $data = '<?hh function foo() { 1 << 3; }';
    $parser = FileParser::fromData($data);
    $fun = $parser->getFunction('foo');
    $this->assertEmpty($fun->getAttributes());
  }

  public function testPseudmainContainingBitShift(): void {
    $data = '<?hh print 1 << 3;';
    $parser = FileParser::fromData($data);
  }

  public function testFunctionAttrsDontPolluteClass(): void {
    $class = $this->findClass('ClassAfterFunction');
    $this->assertEquals(
      dict['ClassFoo' => vec[]],
      $class->getAttributes(),
    );
  }

  public function testParameterHasAttribute(): void {
    $data = '<?hh function foo(<<Bar>> $baz) {}';
    $parser = FileParser::fromData($data);
    $fun = $parser->getFunction('foo');
    $params = $fun->getParameters();
    $this->assertEquals(vec['baz'], Vec\map($params, $x ==> $x->getName()));

    $this->assertEquals(
      vec[dict['Bar' => vec[]] ],
      Vec\map($params, $x ==> $x->getAttributes()),
    );
  }

  public function attributeExpressions(): array<(string, mixed)> {
    return array(
      tuple("'herp'.'derp'", 'herpderp'),
      tuple("Foo\\Bar::class", "Foo\\Bar"),
      tuple("true", true),
      tuple("false", false),
      tuple("null", null),
      tuple("INF", \INF),
      tuple("+123", 123),
      tuple("-123", -123),
      tuple('array()', []),
      tuple('[]', []),
      tuple('array(123)', [123]),
      tuple('array(123,)', [123]),
      tuple('array(123,456)', [123, 456]),
      tuple('array(123,456,)', [123, 456]),
      tuple('[123,456]', [123, 456]),
      tuple('[123 , 456]', [123, 456]),
      tuple('[123 => 456]', [123 => 456]),
      tuple('shape()', []),
      tuple(
        'shape("foo" => "bar", "herp" => 123)',
        shape('foo' => 'bar', 'herp' => 123),
      ),
      tuple('vec[123]', vec[123]),
      tuple("vec['foo']", vec['foo']),
      tuple('keyset[123]', keyset[123]),
      tuple("dict[123 => '456']", dict[123 => '456']),
    );
  }

  /**
   * @dataProvider attributeExpressions
   */
  public function testAttributeExpression(
    string $source,
    mixed $expected,
  ): void {
    $data = '<?hh <<MyAttr('.$source.')>> function foo(){}';
    $parser = FileParser::fromData($data, $source);
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
