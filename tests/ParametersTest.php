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

  public function testWithDefault(): void {
    $data = '<?hh function foo($bar, $baz = "herp") {}';
    $parser = FileParser::FromData($data);
    $function = $parser->getFunction('foo');

    $params = $function->getParameters();
    $this->assertEquals(
      vec['bar', 'baz'],
      $params->map($x ==> $x->getName()),
    );
    $this->assertEquals(
      vec[null, null],
      $params->map($x ==> $x->getTypehint()),
    );
    $this->assertEquals(
      vec[false, true],
      $params->map($x ==> $x->isOptional()),
    );
    $this->assertEquals(
      vec['"herp"'],
      $params
        ->filter($x ==> $x->isOptional() && $x->getName() === 'baz')
        ->map($x ==> $x->getDefaultString()),
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
    $parser = FileParser::FromData($data);
    $function = $parser->getFunction('foo');
    $this->assertEquals(
      vec['bar', 'baz'],
      $function->getParameters()->map($p ==> $p->getName()),
    );
    $this->assertEquals(
      vec[null, $expected],
      $function
        ->getParameters()
        ->map($p ==> $p->isOptional() ? $p->getDefaultString() : null),
    );
  }

  public function testWithTypeAndDefault(): void {
    $data = '<?hh function foo(string $bar = "baz") {}';
    $parser = FileParser::FromData($data);
    $function = $parser->getFunction('foo');

    $params = $function->getParameters();
    $this->assertEquals(
      vec['bar'],
      $function->getParameters()->map($x ==> $x->getName()),
    );
    $this->assertEquals(
      vec[new ScannedTypehint('string', 'string', vec[], false)],
      $function->getParameters()->map($x ==> $x->getTypehint()),
    );
    $this->assertEquals(
      vec['"baz"'],
      $params->map($x ==> $x->getDefaultString()),
    );
  }

  public function testWithRootNamespacedType(): void {
    $data = '<?hh function foo(\Iterator $bar) {}';
    $parser = FileParser::FromData($data);
    $function = $parser->getFunction('foo');

    $params = $function->getParameters();
    $this->assertEquals(
      vec['bar'],
      $function->getParameters()->map($x ==> $x->getName()),
    );
    $this->assertSame(
      'Iterator',
      $function->getParameters()->at(0)->getTypehint()?->getTypeName(),
    );
  }

  public function testWithNamespacedType(): void {
    $data = '<?hh function foo(\Foo\Bar $bar) {}';
    $parser = FileParser::FromData($data);
    $function = $parser->getFunction('foo');

    $params = $function->getParameters();
    $this->assertEquals(
      vec['bar'],
      $function->getParameters()->map($x ==> $x->getName()),
    );
    $this->assertSame(
      'Foo\\Bar',
      $function->getParameters()->at(0)->getTypehint()?->getTypeName(),
    );
  }

  public function testWithLegacyCallableType(): void {
    $data = '<?hh function foo(callable $bar) {}';
    $parser = FileParser::FromData($data);
    $function = $parser->getFunction('foo');

    $params = $function->getParameters();
    $this->assertEquals(
      vec['bar'],
      $function->getParameters()->map($x ==> $x->getName()),
    );
    $this->assertSame(
      'callable',
      $function->getParameters()->at(0)->getTypehint()?->getTypeName(),
    );
  }

  public function testWithByRefParam(): void {
    $data = '<?hh function foo(&$bar, $baz) {}';
    $parser = FileParser::FromData($data);
    $function = $parser->getFunction('foo');

    $params = $function->getParameters();
    $this->assertEquals(
      vec['bar', 'baz'],
      $params->map($x ==> $x->getName()),
    );
    $this->assertEquals(
      vec[true, false],
      $params->map($x ==> $x->isPassedByReference()),
    );
  }

  public function testWithTypedByRefParam(): void {
    $data = '<?hh function foo(string &$bar) {}';
    $parser = FileParser::FromData($data);
    $function = $parser->getFunction('foo');

    $params = $function->getParameters();
    $this->assertEquals(vec['bar'], $params->map($x ==> $x->getName()));
    $this->assertEquals(
      'string',
      $params->at(0)->getTypehint()?->getTypeText(),
    );
    $this->assertEquals(
      vec[true],
      $params->map($x ==> $x->isPassedByReference()),
    );
  }

  public function testWithArrayParam(): void {
    $data = '<?hh function foo(array $bar) {}';
    $parser = FileParser::FromData($data);
    $function = $parser->getFunction('foo');

    $this->assertEquals(
      vec['array'],
      $function->getParameters()->map($x ==> $x->getTypehint()?->getTypeName()),
    );
  }

  public function testWithCommentedParam(): void {
    $data = '<?hh function foo(/* foo */ $bar) {}';
    $parser = FileParser::FromData($data);
    $function = $parser->getFunction('foo');

    $this->assertEquals(
      vec['bar'],
      $function->getParameters()->map($x ==> $x->getName()),
    );
    $this->assertNull($function->getParameters()->at(0)->getTypehint());
  }

  public function testWithUntypedVariadicParam(): void {
    $data = '<?hh function foo(string $bar, ...$baz) {}';

    $parser = FileParser::FromData($data);
    $function = $parser->getFunction('foo');
    $params = $function->getParameters();

    $this->assertEquals(
      vec['bar', 'baz'],
      $params->map($x ==> $x->getName()),
    );

    $this->assertEquals(
      vec[false, true],
      $params->map($x ==> $x->isVariadic()),
    );

    $this->assertEquals(
      Vector {
        new ScannedTypehint('string', 'string', vec[], false),
        null,
      },
      $params->map($x ==> $x->getTypehint()),
    );
  }

  public function testWithTypedVariadicParam(): void {
    /* HH_FIXME[4106] HHVM_VERSION not defined */
    /* HH_FIXME[2049] HHVM_VERSION not defined */
    if (!version_compare(HHVM_VERSION, '3.11.0', '>=')) {
      $this->markTestSkipped('Typed variadics only supported in 3.11+');
    }
    $data = '<?hh function foo(array<mixed> ...$bar) {}';

    $parser = FileParser::FromData($data);
    $function = $parser->getFunction('foo');
    $params = $function->getParameters();

    $this->assertEquals(vec['bar'], $params->map($x ==> $x->getName()));

    $this->assertEquals(vec[true], $params->map($x ==> $x->isVariadic()));

    $this->assertEquals(
      Vector {
        new ScannedTypehint(
          'array',
          'array',
          vec[new ScannedTypehint('mixed', 'mixed', vec[], false)],
          false,
        ),
      },
      $params->map($x ==> $x->getTypehint()),
    );
  }

  public function testWithHackCallableTypehint(): void {
    $data = '<?hh function foo((function(int): string) $bar) {}';
    $parser = FileParser::FromData($data);
    $type = $parser->getFunction('foo')->getParameters()->at(0)->getTypehint();

    $this->assertSame('callable', $type?->getTypeName());
    $this->assertSame('(function(int):string)', $type?->getTypeText());
  }

  public function testEmptyShapeTypehint(): void {
    $data = '<?hh function foo(shape() $bar) {}';
    $parser = FileParser::FromData($data);
    $type = $parser->getFunction('foo')->getParameters()->at(0)->getTypehint();

    $this->assertSame('shape', $type?->getTypeName());
    $this->assertSame('shape()', $type?->getTypeText());
  }

  public function testNonNullableTypehint(): void {
    $data = '<?hh function foo(Herp $derp) {}';
    $parser = FileParser::FromData($data);
    $fun = $parser->getFunction('foo');
    $this->assertEquals(
      vec['Herp'],
      $fun->getParameters()->map($p ==> $p->getTypehint()?->getTypeName()),
    );
    $this->assertEquals(
      vec[false],
      $fun->getParameters()->map($p ==> $p->getTypehint()?->isNullable()),
    );
  }

  public function testNullableTypehint(): void {
    $data = '<?hh function foo(?Herp $derp) {}';
    $parser = FileParser::FromData($data);
    $fun = $parser->getFunction('foo');
    $this->assertEquals(
      vec['Herp'],
      $fun->getParameters()->map($p ==> $p->getTypehint()?->getTypeName()),
    );
    $this->assertEquals(
      vec[true],
      $fun->getParameters()->map($p ==> $p->getTypehint()?->isNullable()),
    );
  }
}
