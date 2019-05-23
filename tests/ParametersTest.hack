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
use type Facebook\HackTest\DataProvider;
use namespace HH\Lib\{C, Vec};
use function Facebook\DefinitionFinder\{ast_without_trivia, nullthrows};
use function Facebook\FBExpect\expect;

final class ParametersTest extends \Facebook\HackTest\HackTest {
  public async function testWithoutTypes(): Awaitable<void> {
    $data = '<?hh function foo($bar, $baz) {}';

    $parser = await FileParser::fromDataAsync($data);
    $function = $parser->getFunction('foo');

    $params = $function->getParameters();

    expect(\count($params))->toBeSame(2);
    expect($params[0]->getName())->toBeSame('bar');
    expect($params[1]->getName())->toBeSame('baz');
    expect($params[0]->getTypehint())->toBeNull();
    expect($params[1]->getTypehint())->toBeNull();
  }

  public async function testWithSimpleType(): Awaitable<void> {
    $data = '<?hh function foo(string $bar) {}';

    $parser = await FileParser::fromDataAsync($data);
    $function = $parser->getFunction('foo');

    $params = $function->getParameters();
    expect(\count($params))->toBeSame(1);
    $param = $params[0];
    expect($param->getName())->toBeSame('bar');
    $typehint = $param->getTypehint();
    expect($typehint?->getTypeName())->toBeSame('string');
    expect($typehint?->getGenericTypes())->toBeEmpty();
  }

  public async function testWithDefault(): Awaitable<void> {
    $data = '<?hh function foo($bar, $baz = "herp") {}';
    $parser = await FileParser::fromDataAsync($data);
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

  public async function testWithNullDefault(): Awaitable<void> {
    $data = '<?hh function foo($bar, $baz = null) {}';
    $parameters =
      (await FileParser::fromDataAsync($data))->getFunction('foo')->getParameters();
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

  <<DataProvider('getUnusualDefaults')>>
  public async function testWithUnusualDefault(string $in, string $expected): Awaitable<void> {
    $data = '<?hh function foo($bar, $baz = '.$in.') {}';
    $parser = await FileParser::fromDataAsync($data);
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
      tuple('<?hh function foo(string $bar): Awaitable<void> {}', 'string', false),
      tuple('<?hh function foo(inout $bar): Awaitable<void> {}', null, true),
      tuple('<?hh function foo(inout string $bar): Awaitable<void> {}', 'string', true),
    ];
  }

  <<DataProvider('getInOutExamples')>>
  public async function testInOut(string $code, ?string $type, bool $inout): Awaitable<void> {
    $parser = await FileParser::fromDataAsync($code);
    $function = $parser->getFunction('foo');

    $param = C\firstx($function->getParameters());
    expect($param->getName())->toBeSame('bar');
    expect($param->getTypehint()?->getTypeText())->toBeSame($type);
    expect($param->isInOut())->toBeSame($inout);
  }

  public async function testWithTypeAndDefault(): Awaitable<void> {
    $data = '<?hh function foo(string $bar = "baz") {}';
    $parser = await FileParser::fromDataAsync($data);
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

  public async function testWithRootNamespacedType(): Awaitable<void> {
    $data = '<?hh function foo(\Iterator $bar) {}';
    $parser = await FileParser::fromDataAsync($data);
    $function = $parser->getFunction('foo');

    $params = $function->getParameters();
    expect(Vec\map($function->getParameters(), $x ==> $x->getName()))->toBeSame(
      vec['bar'],
    );
    expect($function->getParameters()[0]->getTypehint()?->getTypeName())
      ->toBeSame('Iterator');
  }

  public async function testWithNamespacedType(): Awaitable<void> {
    $data = '<?hh function foo(\Foo\Bar $bar) {}';
    $parser = await FileParser::fromDataAsync($data);
    $function = $parser->getFunction('foo');

    $params = $function->getParameters();
    expect(Vec\map($function->getParameters(), $x ==> $x->getName()))->toBeSame(
      vec['bar'],
    );
    expect($function->getParameters()[0]->getTypehint()?->getTypeName())
      ->toBeSame('Foo\\Bar');
  }

  public async function testWithLegacyCallableType(): Awaitable<void> {
    $data = '<?hh function foo(callable $bar) {}';
    $parser = await FileParser::fromDataAsync($data);
    $function = $parser->getFunction('foo');

    $params = $function->getParameters();
    expect(Vec\map($function->getParameters(), $x ==> $x->getName()))->toBeSame(
      vec['bar'],
    );
    expect($function->getParameters()[0]->getTypehint()?->getTypeName())
      ->toBeSame('callable');
  }

  public async function testWithByRefParam(): Awaitable<void> {
    $data = '<?hh function foo(&$bar, $baz) {}';
    $parser = await FileParser::fromDataAsync($data);
    $function = $parser->getFunction('foo');

    $params = $function->getParameters();
    expect(Vec\map($params, $x ==> $x->getName()))->toBeSame(vec['bar', 'baz']);
    expect(Vec\map($params, $x ==> $x->isPassedByReference()))->toBeSame(
      vec[true, false],
    );
  }

  public async function testWithTypedByRefParam(): Awaitable<void> {
    $data = '<?hh function foo(string &$bar) {}';
    $parser = await FileParser::fromDataAsync($data);
    $function = $parser->getFunction('foo');

    $params = $function->getParameters();
    expect(Vec\map($params, $x ==> $x->getName()))->toBeSame(vec['bar']);
    expect($params[0]->getTypehint()?->getTypeText())->toBeSame('string');
    expect(Vec\map($params, $x ==> $x->isPassedByReference()))->toBeSame(
      vec[true],
    );
  }

  public async function testWithArrayParam(): Awaitable<void> {
    $data = '<?hh function foo(array $bar) {}';
    $parser = await FileParser::fromDataAsync($data);
    $function = $parser->getFunction('foo');

    expect(
      Vec\map(
        $function->getParameters(),
        $x ==> $x->getTypehint()?->getTypeName(),
      ),
    )->toBeSame(vec['array']);
  }

  public async function testWithCommentedParam(): Awaitable<void> {
    $data = '<?hh function foo(/* foo */ $bar) {}';
    $parser = await FileParser::fromDataAsync($data);
    $function = $parser->getFunction('foo');

    expect(Vec\map($function->getParameters(), $x ==> $x->getName()))->toBeSame(
      vec['bar'],
    );
    expect($function->getParameters()[0]->getTypehint())->toBeNull();
  }

  public async function testWithUntypedVariadicParam(): Awaitable<void> {
    $data = '<?hh function foo(string $bar, ...$baz) {}';

    $parser = await FileParser::fromDataAsync($data);
    $function = $parser->getFunction('foo');
    $params = $function->getParameters();

    expect(Vec\map($params, $x ==> $x->getName()))->toBeSame(vec['bar', 'baz']);

    expect(Vec\map($params, $x ==> $x->isVariadic()))->toBeSame(
      vec[false, true],
    );

    expect(Vec\map($params, $x ==> $x->getTypehint()?->getTypeText()))
      ->toBeSame(vec['string', null]);
  }

  public async function testWithTypedVariadicParam(): Awaitable<void> {
    $data = '<?hh function foo(array<mixed> ...$bar) {}';

    $parser = await FileParser::fromDataAsync($data);
    $function = $parser->getFunction('foo');
    $params = $function->getParameters();

    expect(Vec\map($params, $x ==> $x->getName()))->toBeSame(vec['bar']);

    expect(Vec\map($params, $x ==> $x->isVariadic()))->toBeSame(vec[true]);

    expect(Vec\map($params, $x ==> $x->getTypehint()?->getTypeText()))
      ->toBeSame(vec['array<mixed>']);
  }

  public async function testWithUnnamedVariadic(): Awaitable<void> {
    $data = '<?hh function foo(string $bar, ...) {}';

    $parser = await FileParser::fromDataAsync($data);
    $function = $parser->getFunction('foo');
    $params = $function->getParameters();

    expect(Vec\map($params, $x ==> $x->getName()))->toBeSame(vec['bar', '']);
    expect(Vec\map($params, $x ==> $x->isVariadic()))->toBeSame(
      vec[false, true],
    );
  }

  public async function testWithHackCallableTypehint(): Awaitable<void> {
    $data = '<?hh function foo((function(int): string) $bar) {}';
    $parser = await FileParser::fromDataAsync($data);
    $type = $parser->getFunction('foo')->getParameters()[0]->getTypehint();

    expect($type?->getTypeName())->toBeSame('callable');
    expect($type?->getTypeText())->toBeSame('(function(int):string)');
  }

  public async function testEmptyShapeTypehint(): Awaitable<void> {
    $data = '<?hh function foo(shape() $bar) {}';
    $parser = await FileParser::fromDataAsync($data);
    $type = $parser->getFunction('foo')->getParameters()[0]->getTypehint();

    expect($type?->getTypeName())->toBeSame('shape');
    expect($type?->getTypeText())->toBeSame('shape()');
  }

  public async function testNonNullableTypehint(): Awaitable<void> {
    $data = '<?hh function foo(Herp $derp) {}';
    $parser = await FileParser::fromDataAsync($data);
    $fun = $parser->getFunction('foo');
    expect(
      Vec\map($fun->getParameters(), $p ==> $p->getTypehint()?->getTypeName()),
    )->toBeSame(vec['Herp']);
    expect(
      Vec\map($fun->getParameters(), $p ==> $p->getTypehint()?->isNullable()),
    )->toBeSame(vec[false]);
  }

  public async function testNullableTypehint(): Awaitable<void> {
    $data = '<?hh function foo(?Herp $derp) {}';
    $parser = await FileParser::fromDataAsync($data);
    $fun = $parser->getFunction('foo');
    expect(
      Vec\map($fun->getParameters(), $p ==> $p->getTypehint()?->getTypeName()),
    )->toBeSame(vec['Herp']);
    expect(
      Vec\map($fun->getParameters(), $p ==> $p->getTypehint()?->isNullable()),
    )->toBeSame(vec[true]);
  }
}
