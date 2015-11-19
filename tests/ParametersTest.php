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
use Facebook\DefinitionFinder\ScannedMethod;
use Facebook\DefinitionFinder\ScannedTypehint;

class ParameterTest extends \PHPUnit_Framework_TestCase {
  public function testWithoutTypes(): void {
    $data = '<?hh function foo($bar, $baz) {}';

    $parser = FileParser::FromData($data);
    $function = $parser->getFunction('foo');

    $params = $function->getParameters();

    $this->assertSame(2, count($params));
    $this->assertSame('bar', $params[0]->getName());
    $this->assertSame('baz', $params[1]->getName());
    $this->assertNull($params[0]->getTypehint());
    $this->assertNull($params[1]->getTypehint());
  }

  public function testWithSimpleType(): void {
    $data = '<?hh function foo(string $bar) {}';

    $parser = FileParser::FromData($data);
    $function = $parser->getFunction('foo');

    $params = $function->getParameters();
    $this->assertSame(1, count($params));
    $param = $params[0];
    $this->assertSame('bar', $param->getName());
    $typehint = $param->getTypehint();
    $this->assertSame('string', $typehint?->getTypeName());
    $this->assertEmpty($typehint?->getGenericTypes());
  }

  public function testWithGenericType(): void {
    $data = '<?hh function foo(Vector<string> $bar) {}';

    $parser = FileParser::FromData($data);
    $function = $parser->getFunction('foo');

    $params = $function->getParameters();
    $this->assertSame(1, count($params));
    $param = $params[0];
    $this->assertSame('bar', $param->getName());
    $typehint = $param->getTypehint();
    $this->assertSame('Vector', $typehint?->getTypeName());
    $this->assertEquals(
      Vector { 'string' },
      $typehint?->getGenericTypes()?->map($x ==> $x->getTypeName()),
    );
    $this->assertEquals(
      Vector { Vector { } },
      $typehint?->getGenericTypes()?->map($x ==> $x->getGenericTypes()),
    );
  }

  public function testWithDefault(): void {
    $data = '<?hh function foo($bar, $baz = "herp") {}';
    $parser = FileParser::FromData($data);
    $function = $parser->getFunction('foo');

    $params = $function->getParameters();
    $this->assertEquals(
      Vector { 'bar', 'baz' },
      $params->map($x ==> $x->getName()),
    );
    $this->assertEquals(
      Vector { null, null },
      $params->map($x ==> $x->getTypehint()),
    );
    $this->assertEquals(
      Vector { false, true },
      $params->map($x ==> $x->isOptional()),
    );
    $this->assertEquals(
      Vector { '"herp"' },
      $params
        ->filter($x ==> $x->isOptional() && $x->getName() === 'baz')
        ->map($x ==> $x->getDefaultString()),
    );
  }

  public function testWithTypeAndDefault(): void {
    $data = '<?hh function foo(string $bar = "baz") {}';
    $parser = FileParser::FromData($data);
    $function = $parser->getFunction('foo');

    $params = $function->getParameters();
    $this->assertEquals(
      Vector { 'bar' },
      $function->getParameters()->map($x ==> $x->getName()),
    );
    $this->assertEquals(
      Vector { new ScannedTypehint('string', Vector { }, false) },
      $function->getParameters()->map($x ==> $x->getTypehint()),
    );
    $this->assertEquals(
      Vector { '"baz"' },
      $params->map($x ==> $x->getDefaultString()),
    );
  }

  public function testWithRootNamespacedType(): void {
    $data = '<?hh function foo(\Iterator $bar) {}';
    $parser = FileParser::FromData($data);
    $function = $parser->getFunction('foo');

    $params = $function->getParameters();
    $this->assertEquals(
      Vector { 'bar' },
      $function->getParameters()->map($x ==> $x->getName()),
    );
    $this->assertEquals(
      Vector { new ScannedTypehint('\Iterator', Vector { }, false) },
      $function->getParameters()->map($x ==> $x->getTypehint()),
    );
  }

  public function testWithNamespacedType(): void {
    $data = '<?hh function foo(\Foo\Bar $bar) {}';
    $parser = FileParser::FromData($data);
    $function = $parser->getFunction('foo');

    $params = $function->getParameters();
    $this->assertEquals(
      Vector { 'bar' },
      $function->getParameters()->map($x ==> $x->getName()),
    );
    $this->assertEquals(
      Vector { new ScannedTypehint('\Foo\Bar', Vector { }, false) },
      $function->getParameters()->map($x ==> $x->getTypehint()),
    );
  }

  public function testWithLegacyCallableType(): void {
    $data = '<?hh function foo(callable $bar) {}';
    $parser = FileParser::FromData($data);
    $function = $parser->getFunction('foo');

    $params = $function->getParameters();
    $this->assertEquals(
      Vector { 'bar' },
      $function->getParameters()->map($x ==> $x->getName()),
    );
    $this->assertEquals(
      Vector { new ScannedTypehint('callable', Vector { }, false) },
      $function->getParameters()->map($x ==> $x->getTypehint()),
    );
  }

  public function testWithByRefParam(): void {
    $data = '<?hh function foo(&$bar, $baz) {}';
    $parser = FileParser::FromData($data);
    $function = $parser->getFunction('foo');

    $params = $function->getParameters();
    $this->assertEquals(
      Vector { 'bar', 'baz' },
      $params->map($x ==> $x->getName()),
    );
    $this->assertEquals(
      Vector { true, false },
      $params->map($x ==> $x->isPassedByReference()),
    );
  }

  public function testWithTypedByRefParam(): void {
    $data = '<?hh function foo(string &$bar) {}';
    $parser = FileParser::FromData($data);
    $function = $parser->getFunction('foo');

    $params = $function->getParameters();
    $this->assertEquals(
      Vector { 'bar' },
      $params->map($x ==> $x->getName()),
    );
    $this->assertEquals(
      Vector { new ScannedTypehint('string', Vector { }, false) },
      $params->map($x ==> $x->getTypehint()),
    );
    $this->assertEquals(
      Vector { true },
      $params->map($x ==> $x->isPassedByReference()),
    );
  }

  public function testWithArrayParam(): void {
    $data = '<?hh function foo(array $bar) {}';
    $parser = FileParser::FromData($data);
    $function = $parser->getFunction('foo');

    $this->assertEquals(
      Vector { new ScannedTypehint('array', Vector { }, false) },
      $function->getParameters()->map($x ==> $x->getTypehint()),
    );
  }

  public function testWithCommentedParam(): void {
    $data = '<?hh function foo(/* foo */ $bar) {}';
    $parser = FileParser::FromData($data);
    $function = $parser->getFunction('foo');

    $this->assertEquals(
      Vector { 'bar' },
      $function->getParameters()->map($x ==> $x->getName()),
    );
  }

  public function testWithVariadicParam(): void {
    $data = '<?hh function foo(string $bar, ...$baz) {}';

    $parser = FileParser::FromData($data);
    $function = $parser->getFunction('foo');
    $params = $function->getParameters();

    $this->assertEquals(
      Vector { 'bar', 'baz' },
      $params->map($x ==> $x->getName()),
    );

    $this->assertEquals(
      Vector { false, true },
      $params->map($x ==> $x->isVariadic()),
    );

    $this->assertEquals(
      Vector {
        new ScannedTypehint('string', Vector { }, false),
        null,
      },
      $params->map($x ==> $x->getTypehint()),
    );
  }

  public function testWithHackCallableTypehint(): void {
    $data = '<?hh function foo((function(int): string) $bar) {}';
    $parser = FileParser::FromData($data);
    $fun = $parser->getFunction('foo');
    $this->assertEquals(
      Vector { '(function(int):string)' },
      $fun->getParameters()->map($p ==> $p->getTypehint()?->getTypeName()),
    );
  }

  public function testEmptyShapeTypehint(): void {
    $data = '<?hh function foo(shape() $bar) {}';
    $parser = FileParser::FromData($data);
    $fun = $parser->getFunction('foo');
    $this->assertEquals(
      Vector { 'shape()' },
      $fun->getParameters()->map($p ==> $p->getTypehint()?->getTypeName()),
    );
  }

  public function testNonNullableTypehint(): void {
    $data = '<?hh function foo(Herp $derp) {}';
    $parser = FileParser::FromData($data);
    $fun = $parser->getFunction('foo');
    $this->assertEquals(
      Vector { 'Herp' },
      $fun->getParameters()->map($p ==> $p->getTypehint()?->getTypeName()),
    );
    $this->assertEquals(
      Vector { false },
      $fun->getParameters()->map($p ==> $p->getTypehint()?->isNullable()),
    );
  }

  public function testNullableTypehint(): void {
    $data = '<?hh function foo(?Herp $derp) {}';
    $parser = FileParser::FromData($data);
    $fun = $parser->getFunction('foo');
    $this->assertEquals(
      Vector { 'Herp' },
      $fun->getParameters()->map($p ==> $p->getTypehint()?->getTypeName()),
    );
    $this->assertEquals(
      Vector { true },
      $fun->getParameters()->map($p ==> $p->getTypehint()?->isNullable()),
    );
  }
}
