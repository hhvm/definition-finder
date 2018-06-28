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
use function Facebook\DefinitionFinder\{ast_without_trivia, nullthrows};
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
    expect(Vec\map($params, $x ==> $x->getName()))->toBeSame(vec['bar', 'baz']);
    expect(Vec\map($params, $x ==> $x->getTypehint()))->toBeSame(
      vec[null, null],
    );
    expect(Vec\map($params, $x ==> $x->isOptional()))->toBeSame(
      vec[false, true],
    );
    expect(Vec\map($params, $x ==> $x->hasDefault()))->toBeSame(
      vec[false, true],
    );
    expect(
      $params
        |> Vec\filter($$, $x ==> $x->isOptional() && $x->getName() === 'baz')
        |> Vec\map($$, $x ==> $x->getDefault()?->getAST()?->getCode()),
    )->toBeSame(vec['"herp"']);
    expect(C\lastx($params)->getDefault()?->getStaticValue())->toBeSame('herp');
  }

  public function testWithNullDefault(): void {
    $data = '<?hh function foo($bar, $baz = null) {}';
    $parameters = FileParser::fromData($data)->getFunction('foo')->getParameters();
    list($bar, $baz) = $parameters;
    expect($bar->getDefault())->toBeNull();
    $default = expect($baz->getDefault())->toNotBeNull();
    expect($default->hasStaticValue())->toBeTrue();
    expect($default->getStaticValue())->toBeNull();
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
    expect(Vec\map($function->getParameters(), $p ==> $p->getName()))->toBeSame(
      vec['bar', 'baz'],
    );
    expect(
      Vec\map(
        $function->getParameters(),
        $p ==> $p->isOptional()
          ? ast_without_trivia(nullthrows($p->getDefault()?->getAST()))
            ->getCode()
          : null,
      ),
    )->toBeSame(vec[null, $expected]);
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
  public function testInOut(string $code, ?string $type, bool $inout): void {
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
    expect(Vec\map($function->getParameters(), $x ==> $x->getName()))->toBeSame(
      vec['bar'],
    );
    expect(
      Vec\map(
        $function->getParameters(),
        $x ==> $x->getTypehint()?->getTypeText(),
      ),
    )->toBeSame(vec['string']);
    expect(Vec\map($params, $x ==> $x->getDefault()?->getAST()?->getCode()))
      ->toBeSame(vec['"baz"']);
  }

  public function testWithRootNamespacedType(): void {
    $data = '<?hh function foo(\Iterator $bar) {}';
    $parser = FileParser::fromData($data);
    $function = $parser->getFunction('foo');

    $params = $function->getParameters();
    expect(Vec\map($function->getParameters(), $x ==> $x->getName()))->toBeSame(
      vec['bar'],
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
    expect(Vec\map($function->getParameters(), $x ==> $x->getName()))->toBeSame(
      vec['bar'],
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
    expect(Vec\map($function->getParameters(), $x ==> $x->getName()))->toBeSame(
      vec['bar'],
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
    expect(Vec\map($params, $x ==> $x->getName()))->toBeSame(vec['bar', 'baz']);
    expect(Vec\map($params, $x ==> $x->isPassedByReference()))->toBeSame(
      vec[true, false],
    );
  }

  public function testWithTypedByRefParam(): void {
    $data = '<?hh function foo(string &$bar) {}';
    $parser = FileParser::fromData($data);
    $function = $parser->getFunction('foo');

    $params = $function->getParameters();
    expect(Vec\map($params, $x ==> $x->getName()))->toBeSame(vec['bar']);
    expect($params[0]->getTypehint()?->getTypeText())->toBeSame('string');
    expect(Vec\map($params, $x ==> $x->isPassedByReference()))->toBeSame(
      vec[true],
    );
  }

  public function testWithArrayParam(): void {
    $data = '<?hh function foo(array $bar) {}';
    $parser = FileParser::fromData($data);
    $function = $parser->getFunction('foo');

    expect(
      Vec\map(
        $function->getParameters(),
        $x ==> $x->getTypehint()?->getTypeName(),
      ),
    )->toBeSame(vec['array']);
  }

  public function testWithCommentedParam(): void {
    $data = '<?hh function foo(/* foo */ $bar) {}';
    $parser = FileParser::fromData($data);
    $function = $parser->getFunction('foo');

    expect(Vec\map($function->getParameters(), $x ==> $x->getName()))->toBeSame(
      vec['bar'],
    );
    $this->assertNull($function->getParameters()[0]->getTypehint());
  }

  public function testWithUntypedVariadicParam(): void {
    $data = '<?hh function foo(string $bar, ...$baz) {}';

    $parser = FileParser::fromData($data);
    $function = $parser->getFunction('foo');
    $params = $function->getParameters();

    expect(Vec\map($params, $x ==> $x->getName()))->toBeSame(vec['bar', 'baz']);

    expect(Vec\map($params, $x ==> $x->isVariadic()))->toBeSame(
      vec[false, true],
    );

    expect(Vec\map($params, $x ==> $x->getTypehint()?->getTypeText()))
      ->toBeSame(vec['string', null]);
  }

  public function testWithTypedVariadicParam(): void {
    $data = '<?hh function foo(array<mixed> ...$bar) {}';

    $parser = FileParser::fromData($data);
    $function = $parser->getFunction('foo');
    $params = $function->getParameters();

    expect(Vec\map($params, $x ==> $x->getName()))->toBeSame(vec['bar']);

    expect(Vec\map($params, $x ==> $x->isVariadic()))->toBeSame(vec[true]);

    expect(Vec\map($params, $x ==> $x->getTypehint()?->getTypeText()))
      ->toBeSame(vec['array<mixed>']);
  }

  public function testWithUnnamedVariadic(): void {
    $data = '<?hh function foo(string $bar, ...) {}';

    $parser = FileParser::fromData($data);
    $function = $parser->getFunction('foo');
    $params = $function->getParameters();

    expect(Vec\map($params, $x ==> $x->getName()))->toBeSame(vec['bar', '']);
    expect(Vec\map($params, $x ==> $x->isVariadic()))->toBeSame(
      vec[false, true],
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
    expect(
      Vec\map($fun->getParameters(), $p ==> $p->getTypehint()?->getTypeName()),
    )->toBeSame(vec['Herp']);
    expect(
      Vec\map($fun->getParameters(), $p ==> $p->getTypehint()?->isNullable()),
    )->toBeSame(vec[false]);
  }

  public function testNullableTypehint(): void {
    $data = '<?hh function foo(?Herp $derp) {}';
    $parser = FileParser::fromData($data);
    $fun = $parser->getFunction('foo');
    expect(
      Vec\map($fun->getParameters(), $p ==> $p->getTypehint()?->getTypeName()),
    )->toBeSame(vec['Herp']);
    expect(
      Vec\map($fun->getParameters(), $p ==> $p->getTypehint()?->isNullable()),
    )->toBeSame(vec[true]);
  }
}
