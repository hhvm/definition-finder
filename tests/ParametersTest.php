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
  ScannedMethod,
  ScannedTypehint,
};
use namespace HH\Lib\{C, Vec};
use function Facebook\FBExpect\expect;

class ParameterTest extends \PHPUnit_Framework_TestCase {
  public function testWithoutTypes(): void {
    $data = '<?hh function foo($bar, $baz) {}';

    $parser = FileParser::fromData($data);
    $function = $parser->getFunction('foo');

    $params = $function->getParameters();

    $this->assertSame(2, \count($params));
    $this->assertSame('bar', $params[0]->getName());
    $this->assertSame('baz', $params[1]->getName());
    $this->assertNull($params[0]->getTypehint());
    $this->assertNull($params[1]->getTypehint());
  }

  public function testWithSimpleType(): void {
    $data = '<?hh function foo(string $bar) {}';

    $parser = FileParser::fromData($data);
    $function = $parser->getFunction('foo');

    $params = $function->getParameters();
    $this->assertSame(1, \count($params));
    $param = $params[0];
    $this->assertSame('bar', $param->getName());
    $typehint = $param->getTypehint();
    $this->assertSame('string', $typehint?->getTypeName());
    $this->assertEmpty($typehint?->getGenericTypes());
  }

  public function testWithDefault(): void {
    $data = '<?hh function foo($bar, $baz = "herp") {}';
    $parser = FileParser::fromData($data);
    $function = $parser->getFunction('foo');

    $params = $function->getParameters();
    $this->assertEquals(
      vec['bar', 'baz'],
      Vec\map($params, $x ==> $x->getName()),
    );
    $this->assertEquals(
      vec[null, null],
      Vec\map($params, $x ==> $x->getTypehint()),
    );
    $this->assertEquals(
      vec[false, true],
      Vec\map($params, $x ==> $x->isOptional()),
    );
    $this->assertEquals(
      vec['"herp"'],
      $params
        |> Vec\filter($$, $x ==> $x->isOptional() && $x->getName() === 'baz')
        |> Vec\map($$, $x ==> $x->getDefaultString()),
    );
  }

  public function getUnusualDefaults(): array<(string, string)> {
    return [
      tuple('true ? "herp" : "derp"', 'true?"herp":"derp"'),
      tuple('(FOO === true)? "herp" : "derp")', '(FOO===true)?"herp":"derp"'),
      tuple('["herp", "derp"]', '["herp","derp"]'),
    ];
  }

  /**
   * @dataProvider getUnusualDefaults
   */
  public function testWithUnusualDefault(string $in, string $expected): void {
    $data = '<?hh function foo($bar, $baz = '.$in.') {}';
    $parser = FileParser::fromData($data);
    $function = $parser->getFunction('foo');
    $this->assertEquals(
      vec['bar', 'baz'],
      Vec\map($function->getParameters(), $p ==> $p->getName()),
    );
    $this->assertEquals(
      vec[null, $expected],
      Vec\map(
        $function->getParameters(),
        $p ==> $p->isOptional() ? $p->getDefaultString() : null,
      ),
    );
  }

  public function getInOutExamples(): array<(string, ?string, bool)> {
    return [
      tuple('<?hh function foo(string $bar): void {}', 'string', false),
      tuple('<?hh function foo(inout $bar): void {}', null, true),
      tuple('<?hh function foo(inout string $bar): void {}', 'string', true),
    ];
  }

  /**
   * @dataProvider getInOutExamples
   */
  public function testInOut(
    string $code,
    ?string $type,
    bool $inout,
  ): void {
    $parser = FileParser::fromData($code);
    $function = $parser->getFunction('foo');

    $param = C\firstx($function->getParameters());
    expect($param->getName())->toBeSame('bar');
    expect($param->getTypehint()?->getTypeText())->toBeSame($type);
    expect($param->isInOut())->toBeSame($inout);
  }

  public function testWithTypeAndDefault(): void {
    $data = '<?hh function foo(string $bar = "baz") {}';
    $parser = FileParser::fromData($data);
    $function = $parser->getFunction('foo');

    $params = $function->getParameters();
    $this->assertEquals(
      vec['bar'],
      Vec\map($function->getParameters(), $x ==> $x->getName()),
    );
    $this->assertEquals(
      vec[new ScannedTypehint('string', 'string', vec[], false)],
      Vec\map($function->getParameters(), $x ==> $x->getTypehint()),
    );
    $this->assertEquals(
      vec['"baz"'],
      Vec\map($params, $x ==> $x->getDefaultString()),
    );
  }

  public function testWithRootNamespacedType(): void {
    $data = '<?hh function foo(\Iterator $bar) {}';
    $parser = FileParser::fromData($data);
    $function = $parser->getFunction('foo');

    $params = $function->getParameters();
    $this->assertEquals(
      vec['bar'],
      Vec\map($function->getParameters(), $x ==> $x->getName()),
    );
    $this->assertSame(
      'Iterator',
      $function->getParameters()[0]->getTypehint()?->getTypeName(),
    );
  }

  public function testWithNamespacedType(): void {
    $data = '<?hh function foo(\Foo\Bar $bar) {}';
    $parser = FileParser::fromData($data);
    $function = $parser->getFunction('foo');

    $params = $function->getParameters();
    $this->assertEquals(
      vec['bar'],
      Vec\map($function->getParameters(), $x ==> $x->getName()),
    );
    $this->assertSame(
      'Foo\\Bar',
      $function->getParameters()[0]->getTypehint()?->getTypeName(),
    );
  }

  public function testWithLegacyCallableType(): void {
    $data = '<?hh function foo(callable $bar) {}';
    $parser = FileParser::fromData($data);
    $function = $parser->getFunction('foo');

    $params = $function->getParameters();
    $this->assertEquals(
      vec['bar'],
      Vec\map($function->getParameters(), $x ==> $x->getName()),
    );
    $this->assertSame(
      'callable',
      $function->getParameters()[0]->getTypehint()?->getTypeName(),
    );
  }

  public function testWithByRefParam(): void {
    $data = '<?hh function foo(&$bar, $baz) {}';
    $parser = FileParser::fromData($data);
    $function = $parser->getFunction('foo');

    $params = $function->getParameters();
    $this->assertEquals(
      vec['bar', 'baz'],
      Vec\map($params, $x ==> $x->getName()),
    );
    $this->assertEquals(
      vec[true, false],
      Vec\map($params, $x ==> $x->isPassedByReference()),
    );
  }

  public function testWithTypedByRefParam(): void {
    $data = '<?hh function foo(string &$bar) {}';
    $parser = FileParser::fromData($data);
    $function = $parser->getFunction('foo');

    $params = $function->getParameters();
    $this->assertEquals(vec['bar'], Vec\map($params, $x ==> $x->getName()));
    $this->assertEquals(
      'string',
      $params[0]->getTypehint()?->getTypeText(),
    );
    $this->assertEquals(
      vec[true],
      Vec\map($params, $x ==> $x->isPassedByReference()),
    );
  }

  public function testWithArrayParam(): void {
    $data = '<?hh function foo(array $bar) {}';
    $parser = FileParser::fromData($data);
    $function = $parser->getFunction('foo');

    $this->assertEquals(
      vec['array'],
      Vec\map($function->getParameters(), $x ==> $x->getTypehint()?->getTypeName()),
    );
  }

  public function testWithCommentedParam(): void {
    $data = '<?hh function foo(/* foo */ $bar) {}';
    $parser = FileParser::fromData($data);
    $function = $parser->getFunction('foo');

    $this->assertEquals(
      vec['bar'],
      Vec\map($function->getParameters(), $x ==> $x->getName()),
    );
    $this->assertNull($function->getParameters()[0]->getTypehint());
  }

  public function testWithUntypedVariadicParam(): void {
    $data = '<?hh function foo(string $bar, ...$baz) {}';

    $parser = FileParser::fromData($data);
    $function = $parser->getFunction('foo');
    $params = $function->getParameters();

    $this->assertEquals(
      vec['bar', 'baz'],
      Vec\map($params, $x ==> $x->getName()),
    );

    $this->assertEquals(
      vec[false, true],
      Vec\map($params, $x ==> $x->isVariadic()),
    );

    $this->assertEquals(
      vec[
        new ScannedTypehint('string', 'string', vec[], false),
        null,
      ],
      Vec\map($params, $x ==> $x->getTypehint()),
    );
  }

  public function testWithTypedVariadicParam(): void {
    /* HH_FIXME[4106] HHVM_VERSION not defined */
    /* HH_FIXME[2049] HHVM_VERSION not defined */
    if (!\version_compare(HHVM_VERSION, '3.11.0', '>=')) {
      $this->markTestSkipped('Typed variadics only supported in 3.11+');
    }
    $data = '<?hh function foo(array<mixed> ...$bar) {}';

    $parser = FileParser::fromData($data);
    $function = $parser->getFunction('foo');
    $params = $function->getParameters();

    $this->assertEquals(vec['bar'], Vec\map($params, $x ==> $x->getName()));

    $this->assertEquals(vec[true], Vec\map($params, $x ==> $x->isVariadic()));

    $this->assertEquals(
      vec[
        new ScannedTypehint(
          'array',
          'array',
          vec[new ScannedTypehint('mixed', 'mixed', vec[], false)],
          false,
        ),
      ],
      Vec\map($params, $x ==> $x->getTypehint()),
    );
  }

  public function testWithHackCallableTypehint(): void {
    $data = '<?hh function foo((function(int): string) $bar) {}';
    $parser = FileParser::fromData($data);
    $type = $parser->getFunction('foo')->getParameters()[0]->getTypehint();

    $this->assertSame('callable', $type?->getTypeName());
    $this->assertSame('(function(int):string)', $type?->getTypeText());
  }

  public function testEmptyShapeTypehint(): void {
    $data = '<?hh function foo(shape() $bar) {}';
    $parser = FileParser::fromData($data);
    $type = $parser->getFunction('foo')->getParameters()[0]->getTypehint();

    $this->assertSame('shape', $type?->getTypeName());
    $this->assertSame('shape()', $type?->getTypeText());
  }

  public function testNonNullableTypehint(): void {
    $data = '<?hh function foo(Herp $derp) {}';
    $parser = FileParser::fromData($data);
    $fun = $parser->getFunction('foo');
    $this->assertEquals(
      vec['Herp'],
      Vec\map($fun->getParameters(), $p ==> $p->getTypehint()?->getTypeName()),
    );
    $this->assertEquals(
      vec[false],
      Vec\map($fun->getParameters(), $p ==> $p->getTypehint()?->isNullable()),
    );
  }

  public function testNullableTypehint(): void {
    $data = '<?hh function foo(?Herp $derp) {}';
    $parser = FileParser::fromData($data);
    $fun = $parser->getFunction('foo');
    $this->assertEquals(
      vec['Herp'],
      Vec\map($fun->getParameters(), $p ==> $p->getTypehint()?->getTypeName()),
    );
    $this->assertEquals(
      vec[true],
      Vec\map($fun->getParameters(), $p ==> $p->getTypehint()?->isNullable()),
    );
  }
}
