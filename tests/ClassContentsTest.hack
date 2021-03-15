/*
 *  Copyright (c) 2015-present, Facebook, Inc.
 *  All rights reserved.
 *
 *  This source code is licensed under the MIT license found in the
 *  LICENSE file in the root directory of this source tree.
 *
 */

namespace Facebook\DefinitionFinder\Test;

use function Facebook\FBExpect\expect;
use type Facebook\DefinitionFinder\{FileParser, ScannedClassish};
use type Facebook\HackTest\DataProvider;
use namespace HH\Lib\{C, Vec};

class ClassContentsTest extends \Facebook\HackTest\HackTest {
  private ?ScannedClassish $class;

  <<__Override>>
  public async function beforeEachTestAsync(): Awaitable<void> {
    $parser = await FileParser::fromFileAsync(
      __DIR__.'/data/class_contents.php',
    );
    $this->class = $parser->getClasses()[0];
    expect($this->class->getName())->toBeSame(
      'Facebook\\DefinitionFinder\\Test\\ClassWithContents',
    );
  }

  public async function testNamespaceName(): Awaitable<void> {
    expect($this->class?->getNamespaceName())->toBeSame(
      'Facebook\DefinitionFinder\Test',
    );
    expect(
      Vec\map(
        $this->class?->getMethods() ?? vec[],
        $x ==> $x->getNamespaceName(),
      ),
    )->toBeSame(vec['', '', '', '']);
  }

  public async function testShortName(): Awaitable<void> {
    expect($this->class?->getShortName())->toBeSame('ClassWithContents');
    expect(
      Vec\map($this->class?->getMethods() ?? vec[], $x ==> $x->getShortName()),
    )->toBeSame(
      vec[
        'publicMethod',
        'protectedMethod',
        'privateMethod',
        'PublicStaticMethod',
      ],
    );
  }

  public async function testMethodNames(): Awaitable<void> {
    expect(Vec\map($this->class?->getMethods() ?? vec[], $x ==> $x->getName()))
      ->toBeSame(
        vec[
          'publicMethod',
          'protectedMethod',
          'privateMethod',
          'PublicStaticMethod',
        ],
      );
  }

  public async function testMethodVisibility(): Awaitable<void> {
    expect(Vec\map($this->class?->getMethods() ?? vec[], $x ==> $x->isPublic()))
      ->toBeSame(vec[true, false, false, true], 'isPublic');
    expect(
      Vec\map($this->class?->getMethods() ?? vec[], $x ==> $x->isProtected()),
    )->toBeSame(vec[false, true, false, false], 'isProtected');
    expect(
      Vec\map($this->class?->getMethods() ?? vec[], $x ==> $x->isPrivate()),
    )->toBeSame(vec[false, false, true, false], 'isPrivate');
  }

  public async function testHackConstants(): Awaitable<void> {
    $constants = $this->class?->getConstants();
    expect(Vec\map($constants ?? vec[], $x ==> $x->getName()))->toBeSame(
      vec['FOO', 'BAR'],
    );
    expect(
      Vec\map($constants ?? vec[], $x ==> $x->getTypehint()?->getTypeName()),
    )->toBeSame(vec['string', 'int']);
    expect(Vec\map($constants ?? vec[], $x ==> $x->isAbstract()))->toBeSame(
      vec[false, false],
    );
    expect(
      Vec\map($constants ?? vec[], $x ==> $x->getValue()->getStaticValue()),
    )->toBeSame(vec['bar', 60 * 60 * 24]);
    expect(Vec\map($constants ?? vec[], $x ==> $x->getDocComment()))->toBeSame(
      vec['/** FooDoc */', '/** BarDoc */'],
    );
  }

  public async function testTypelessConstants(): Awaitable<void> {
    $parser = await FileParser::fromFileAsync(
      __DIR__.'/data/missing_stuff.php',
    );
    $class = $parser->getClass('ClassWithMissingStuff');
    $constants = $class->getConstants();

    expect(Vec\map($constants, $x ==> $x->getName()))->toBeSame(
      vec['FOO', 'BAR'],
    );
    expect(Vec\map($constants, $x ==> $x->getTypehint()))->toBeSame(
      vec[null, null],
    );
    expect(Vec\map($constants, $x ==> $x->getValue()->getStaticValue()))
      ->toBeSame(vec['bar', 60 * 60 * 24]);
    expect(Vec\map($constants, $x ==> $x->getDocComment()))->toBeSame(
      vec['/** FooDoc */', '/** BarDoc */'],
    );
  }

  /** Omitting public/protected/private is permitted in PHP */
  public async function testDefaultMethodVisibility(): Awaitable<void> {
    $parser = await FileParser::fromFileAsync(
      __DIR__.'/data/missing_stuff.php',
    );
    $funcs = $parser->getClass('ClassWithMissingStuff')->getMethods();

    expect(Vec\map($funcs, $x ==> $x->getName()))->toBeSame(
      vec[
        'defaultVisibility',
        'privateVisibility',
        'alsoDefaultVisibility',
      ],
    );
    expect(Vec\map($funcs, $x ==> $x->isPublic()))->toBeSame(
      vec[true, false, true],
    );
  }

  public async function testMethodsAreStatic(): Awaitable<void> {
    expect(Vec\map($this->class?->getMethods() ?? vec[], $x ==> $x->isStatic()))
      ->toBeSame(vec[false, false, false, true], 'isPublic');
  }

  public async function testPropertyNames(): Awaitable<void> {
    expect(
      Vec\map($this->class?->getProperties() ?? vec[], $x ==> $x->getName()),
    )->toBeSame(vec['foo', 'herp']);
  }

  public async function testPropertyVisibility(): Awaitable<void> {
    expect(
      Vec\map($this->class?->getProperties() ?? vec[], $x ==> $x->isPublic()),
    )->toBeSame(vec[false, true]);
  }

  public async function testPropertyTypes(): Awaitable<void> {
    expect(
      $this->class?->getProperties()
        |> Vec\map($$ ?? vec[], $x ==> $x->getTypehint()?->getTypeName()),
    )->toBeSame(vec['bool', 'string']);
  }

  public async function testTypelessProperty(): Awaitable<void> {
    $parser = await FileParser::fromFileAsync(
      __DIR__.'/data/missing_stuff.php',
    );
    $props = $parser->getClass('ClassWithMissingStuff')->getProperties();

    expect(Vec\map($props, $x ==> $x->getName()))->toBeSame(
      vec['untypedProperty'],
    );
    expect(Vec\map($props, $x ==> $x->getTypehint()))->toBeSame(vec[null]);
  }

  public function staticPropertyProvider(): vec<(string, string, ?string)> {
    return vec[
      tuple('default', '<?hh class Foo { static $bar; }', null),
      tuple('public static', '<?hh class Foo { public static $bar; }', null),
      tuple('static public', '<?hh class Foo { static public $bar; }', null),
      tuple(
        'public static string',
        '<?hh class Foo { public static string $bar; }',
        'string',
      ),
      tuple(
        'static public string',
        '<?hh class Foo { static public string $bar; }',
        'string',
      ),
    ];
  }

  <<DataProvider('staticPropertyProvider')>>
  public async function testStaticProperty(
    string $_,
    string $code,
    ?string $type,
  ): Awaitable<void> {
    $parser = (await FileParser::fromDataAsync($code));
    $class = $parser->getClass('Foo');
    $props = $class->getProperties();

    expect(Vec\map($props, $x ==> $x->getName()))->toBeSame(vec['bar']);

    expect(Vec\map($props, $x ==> $x->getTypehint()?->getTypeName()))->toBeSame(
      vec[$type],
    );

    expect(Vec\map($props, $x ==> $x->isStatic()))->toBeSame(vec[true]);
  }

  public async function testTypeConstant(): Awaitable<void> {
    $data = '<?hh class Foo { const type BAR = int; }';
    $parser = (await FileParser::fromDataAsync($data));
    $constants = $parser->getClass('Foo')->getTypeConstants();
    $constant = C\onlyx($constants);

    expect($constant->getName())->toBeSame('BAR');
    expect($constant->isAbstract())->toBeFalse();
    expect($constant->getAliasedType()?->getTypeText())->toBeSame('int');
  }

  public async function testClassAsTypeConstant(): Awaitable<void> {
    $data = "<?hh\n".
      "class Foo { const type BAR = int; }\n".
      "class Bar {\n".
      "  const type FOO = Foo;\n".
      "  function foo(self::FOO::BAR \$baz): Awaitable<void> {}\n".
      '}';
    $parser = (await FileParser::fromDataAsync($data));
    $class = $parser->getClass('Bar');
    expect(
      Vec\map(
        $class->getMethods(),
        $method ==> Vec\map(
          $method->getParameters(),
          $param ==> $param->getTypehint()?->getTypeText(),
        ),
      ),
    )->toBeSame(vec[vec['self::FOO::BAR']]);
  }

  public async function testConstraintedTypeConstant(): Awaitable<void> {
    // I'm not aware of a use for this, but HHVM allows and tests for it
    $data = '<?hh class Foo { const type BAR as int = int; }';
    $parser = (await FileParser::fromDataAsync($data));
    $constants = $parser->getClass('Foo')->getTypeConstants();
    $constant = C\onlyx($constants);

    expect($constant->getName())->toBeSame('BAR');
    expect($constant->isAbstract())->toBeFalse();
    expect($constant->getAliasedType()?->getTypeText())->toBeSame('int');
  }

  public async function testAbstractConstant(): Awaitable<void> {
    $data = '<?hh abstract class Foo { abstract const string BAR; }';
    $parser = (await FileParser::fromDataAsync($data));
    $constant = C\onlyx($parser->getClass('Foo')->getConstants());

    expect($constant->getName())->toBeSame('BAR');
    expect($constant->isAbstract())->toBeTrue();
    expect($constant->hasValue())->toBeFalse();
  }

  public async function testAbstractTypeConstant(): Awaitable<void> {
    $data = '<?hh abstract class Foo { abstract const type BAR; }';
    $parser = (await FileParser::fromDataAsync($data));
    $constant = C\onlyx($parser->getClass('Foo')->getTypeConstants());

    expect($constant->getName())->toBeSame('BAR');
    expect($constant->isAbstract())->toBeTrue();
    expect($constant->getAliasedType())->toBeNull();
  }

  public async function testConstrainedAbstractTypeConstant(): Awaitable<void> {
    $data = '<?hh abstract class Foo { abstract const type BAR as Bar; }';
    $parser = (await FileParser::fromDataAsync($data));
    $constant = C\onlyx($parser->getClass('Foo')->getTypeConstants());

    expect($constant->getName())->toBeSame('BAR');
    expect($constant->isAbstract())->toBeTrue();
    expect($constant->getAliasedType()?->getTypeText())->toBeSame('Bar');
  }

  public async function testTypeConstantAsProperty(): Awaitable<void> {
    $data = '<?hh class Foo { public this::FOO $foo; }';
    $parser = (await FileParser::fromDataAsync($data));
    $prop = C\onlyx($parser->getClass('Foo')->getProperties());

    expect($prop->getTypehint()?->getTypeText())->toBeSame('this::FOO');
    expect($prop->getName())->toBeSame('foo');
  }

  public async function testTypeconstantAsReturnType(): Awaitable<void> {
    $data = '<?hh class Foo { public async function bar(): this::FOO {} }';
    $parser = (await FileParser::fromDataAsync($data));
    $method = C\onlyx($parser->getClass('Foo')->getMethods());

    expect($method->getReturnType()?->getTypeText())->toBeSame('this::FOO');
  }

  public async function testTypeconstantAsParameterType(): Awaitable<void> {
    $data =
      '<?hh class Foo { public async function bar(this::FOO $foo): Awaitable<void> {} }';
    $parser = (await FileParser::fromDataAsync($data));
    $method = C\onlyx($parser->getClass('Foo')->getMethods());
    $param = C\onlyx($method->getParameters());

    expect($param->getTypehint()?->getTypeText())->toBeSame('this::FOO');
    expect($param->getName())->toBeSame('foo');
  }

  // vec<(namespace, return type text, expected return type text)>
  public static function namespacedReturns(): vec<(string, string, string)> {
    return vec[
      tuple('', 'this::FOO', 'this::FOO'),
      tuple('Bar', 'this::FOO', 'this::FOO'),
      tuple('', 'Bar::FOO', 'Bar::FOO'),
      tuple('NS', 'Bar::FOO', 'NS\Bar::FOO'),
      tuple('NS', '\Bar::FOO', 'Bar::FOO'),
      tuple('NS', 'Nested\Bar::FOO', 'NS\Nested\Bar::FOO'),
      tuple('NS', '\Nested\Bar::FOO', 'Nested\Bar::FOO'),
    ];
  }

  <<DataProvider('namespacedReturns')>>
  public async function testNamespacedTypeconstantAsParameterType(
    string $namespace,
    string $returnText,
    string $expectedTypehintText,
  ): Awaitable<void> {
    $data = \sprintf(
      '<?hh %s class Foo { public async function bar(): %s {} }',
      $namespace === '' ? '' : 'namespace '.$namespace.';',
      $returnText,
    );
    $className = \ltrim($namespace.'\Foo', '\\');
    $parser = (await FileParser::fromDataAsync($data));
    $method = C\onlyx($parser->getClass($className)->getMethods());

    expect($method->getReturnType()?->getTypeText())->toBeSame(
      $expectedTypehintText,
    );
  }
}
