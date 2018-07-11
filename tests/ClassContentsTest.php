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

use function Facebook\FBExpect\expect;
use type Facebook\DefinitionFinder\{FileParser, ScannedClassish};
use namespace HH\Lib\{C, Vec};

class ClassContentsTest extends \PHPUnit_Framework_TestCase {
  private ?ScannedClassish $class;

  <<__Override>>
  protected function setUp(): void {
    $parser = FileParser::fromFile(__DIR__.'/data/class_contents.php');
    $this->class = $parser->getClasses()[0];
    expect($this->class->getName())->toBeSame(
      'Facebook\\DefinitionFinder\\Test\\ClassWithContents',
    );
  }

  public function testAnonymousClasses(): void {
    $parser = FileParser::fromFile(__DIR__.'/data/class_contents_php.php');
    expect($parser->getFunctions())->toBeEmpty(
      'Should be no functions - probably interpreting a method as a function',
    );
    expect(C\count($parser->getClasses()))->toBeSame(
      1,
      'The anonymous class should not be returned',
    );
    $class = $parser->getClass('ClassUsingAnonymousClass');
    expect(Vec\map($class->getMethods(), $it ==> $it->getName()))->toBeSame(
      vec['methodOne', 'methodTwo'],
    );
  }

  public function testNamespaceName(): void {
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

  public function testShortName(): void {
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

  public function testMethodNames(): void {
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

  public function testMethodVisibility(): void {
    expect(Vec\map($this->class?->getMethods() ?? vec[], $x ==> $x->isPublic()))
      ->toBeSame(vec[true, false, false, true], 'isPublic');
    expect(
      Vec\map($this->class?->getMethods() ?? vec[], $x ==> $x->isProtected()),
    )->toBeSame(vec[false, true, false, false], 'isProtected');
    expect(
      Vec\map($this->class?->getMethods() ?? vec[], $x ==> $x->isPrivate()),
    )->toBeSame(vec[false, false, true, false], 'isPrivate');
  }

  public function testHackConstants(): void {
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

  public function testPHPConstants(): void {
    $parser = FileParser::fromFile(__DIR__.'/data/php_class_contents.php');
    $class = $parser->getClass('Foo');
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
  public function testDefaultMethodVisibility(): void {
    $parser = FileParser::fromFile(__DIR__.'/data/php_class_contents.php');
    $funcs = $parser->getClass('Foo')->getMethods();

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

  public function testMethodsAreStatic(): void {
    expect(Vec\map($this->class?->getMethods() ?? vec[], $x ==> $x->isStatic()))
      ->toBeSame(vec[false, false, false, true], 'isPublic');
  }

  public function testPropertyNames(): void {
    expect(
      Vec\map($this->class?->getProperties() ?? vec[], $x ==> $x->getName()),
    )->toBeSame(vec['foo', 'herp']);
  }

  public function testPropertyVisibility(): void {
    expect(
      Vec\map($this->class?->getProperties() ?? vec[], $x ==> $x->isPublic()),
    )->toBeSame(vec[false, true]);
  }

  public function testPropertyTypes(): void {
    expect(
      $this->class?->getProperties()
        |> Vec\map($$ ?? vec[], $x ==> $x->getTypehint()?->getTypeName()),
    )->toBeSame(vec['bool', 'string']);
  }

  public function testTypelessProperty(): void {
    $parser = FileParser::fromFile(__DIR__.'/data/php_class_contents.php');
    $props = $parser->getClass('Foo')->getProperties();

    expect(Vec\map($props, $x ==> $x->getName()))->toBeSame(
      vec['untypedProperty'],
    );
    expect(Vec\map($props, $x ==> $x->getTypehint()))->toBeSame(vec[null]);
  }

  public function staticPropertyProvider(): array<array<mixed>> {
    return [
      ['default', '<?php class Foo { static $bar; }', null],
      ['public static', '<?php class Foo { public static $bar; }', null],
      ['static public', '<?php class Foo { static public $bar; }', null],
      [
        'public static string',
        '<?hh class Foo { public static string $bar; }',
        'string',
      ],
      [
        'static public string',
        '<?hh class Foo { static public string $bar; }',
        'string',
      ],
    ];
  }

  /** @dataProvider staticPropertyProvider */
  public function testStaticProperty(
    string $_,
    string $code,
    ?string $type,
  ): void {
    $parser = FileParser::fromData($code);
    $class = $parser->getClass('Foo');
    $props = $class->getProperties();

    expect(Vec\map($props, $x ==> $x->getName()))->toBeSame(vec['bar']);

    expect(Vec\map($props, $x ==> $x->getTypehint()?->getTypeName()))->toBeSame(
      vec[$type],
    );


    expect(Vec\map($props, $x ==> $x->isStatic()))->toBeSame(vec[true]);
  }

  public function testTypeConstant(): void {
    $data = '<?hh class Foo { const type BAR = int; }';
    $parser = FileParser::fromData($data);
    $constants = $parser->getClass('Foo')->getTypeConstants();
    $constant = C\onlyx($constants);

    expect($constant->getName())->toBeSame('BAR');
    expect($constant->isAbstract())->toBeFalse();
    expect($constant->getAliasedType()?->getTypeText())->toBeSame('int');
  }

  public function testClassAsTypeConstant(): void {
    $data = "<?hh\n".
      "class Foo { const type BAR = int; }\n".
      "class Bar {\n".
      "  const type FOO = Foo;\n".
      "  function foo(self::FOO::BAR \$baz): void {}\n".
      "}";
    $parser = FileParser::fromData($data);
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

  public function testConstraintedTypeConstant(): void {
    // I'm not aware of a use for this, but HHVM allows and tests for it
    $data = '<?hh class Foo { const type BAR as int = int; }';
    $parser = FileParser::fromData($data);
    $constants = $parser->getClass('Foo')->getTypeConstants();
    $constant = C\onlyx($constants);

    expect($constant->getName())->toBeSame('BAR');
    expect($constant->isAbstract())->toBeFalse();
    expect($constant->getAliasedType()?->getTypeText())->toBeSame('int');
  }

  public function testAbstractConstant(): void {
    $data = '<?hh abstract class Foo { abstract const string BAR; }';
    $parser = FileParser::fromData($data);
    $constant = C\onlyx($parser->getClass('Foo')->getConstants());

    expect($constant->getName())->toBeSame('BAR');
    expect($constant->isAbstract())->toBeTrue();
    expect($constant->hasValue())->toBeFalse();
  }


  public function testAbstractTypeConstant(): void {
    $data = '<?hh abstract class Foo { abstract const type BAR; }';
    $parser = FileParser::fromData($data);
    $constant = C\onlyx($parser->getClass('Foo')->getTypeConstants());

    expect($constant->getName())->toBeSame('BAR');
    expect($constant->isAbstract())->toBeTrue();
    expect($constant->getAliasedType())->toBeNull();
  }

  public function testConstrainedAbstractTypeConstant(): void {
    $data = '<?hh abstract class Foo { abstract const type BAR as Bar; }';
    $parser = FileParser::fromData($data);
    $constant = C\onlyx($parser->getClass('Foo')->getTypeConstants());

    expect($constant->getName())->toBeSame('BAR');
    expect($constant->isAbstract())->toBeTrue();
    expect($constant->getAliasedType()?->getTypeText())->toBeSame('Bar');
  }

  public function testTypeConstantAsProperty(): void {
    $data = '<?hh class Foo { public this::FOO $foo; }';
    $parser = FileParser::fromData($data);
    $prop = C\onlyx($parser->getClass('Foo')->getProperties());

    expect($prop->getTypehint()?->getTypeText())->toBeSame('this::FOO');
    expect($prop->getName())->toBeSame('foo');
  }

  public function testTypeconstantAsReturnType(): void {
    $data = '<?hh class Foo { public function bar(): this::FOO {} }';
    $parser = FileParser::fromData($data);
    $method = C\onlyx($parser->getClass('Foo')->getMethods());

    expect($method->getReturnType()?->getTypeText())->toBeSame('this::FOO');
  }

  public function testTypeconstantAsParameterType(): void {
    $data = '<?hh class Foo { public function bar(this::FOO $foo): void {} }';
    $parser = FileParser::fromData($data);
    $method = C\onlyx($parser->getClass('Foo')->getMethods());
    $param = C\onlyx($method->getParameters());

    expect($param->getTypehint()?->getTypeText())->toBeSame('this::FOO');
    expect($param->getName())->toBeSame('foo');
  }

  public static function namespacedReturns(
  ): array<
    shape(
      'namespace' => string,
      'return type text' => string,
      'expected return type text' => string,
    )
  > {
    return [
      shape(
        'namespace' => '',
        'return type text' => 'this::FOO',
        'expected return type text' => 'this::FOO',
      ),
      shape(
        'namespace' => 'Bar',
        'return type text' => 'this::FOO',
        'expected return type text' => 'this::FOO',
      ),
      shape(
        'namespace' => '',
        'return type text' => 'Bar::FOO',
        'expected return type text' => 'Bar::FOO',
      ),
      shape(
        'namespace' => 'NS',
        'return type text' => 'Bar::FOO',
        'expected return type text' => 'NS\Bar::FOO',
      ),
      shape(
        'namespace' => 'NS',
        'return type text' => '\Bar::FOO',
        'expected return type text' => 'Bar::FOO',
      ),
      shape(
        'namespace' => 'NS',
        'return type text' => 'Nested\Bar::FOO',
        'expected return type text' => 'NS\Nested\Bar::FOO',
      ),
      shape(
        'namespace' => 'NS',
        'return type text' => '\Nested\Bar::FOO',
        'expected return type text' => 'Nested\Bar::FOO',
      ),
    ];
  }

  /**
   * @dataProvider namespacedReturns
   */
  public function testNamespacedTypeconstantAsParameterType(
    string $namespace,
    string $returnText,
    string $expectedTypehintText,
  ): void {
    $data = \sprintf(
      '<?hh %s class Foo { public function bar(): %s {} }',
      $namespace === '' ? '' : "namespace ".$namespace.";",
      $returnText,
    );
    $className = \ltrim($namespace.'\Foo', "\\");
    $parser = FileParser::fromData($data);
    $method = C\onlyx($parser->getClass($className)->getMethods());

    expect($method->getReturnType()?->getTypeText())->toBeSame(
      $expectedTypehintText,
    );
  }
}
