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
  ScannedClass,
};
use namespace HH\Lib\{C, Vec};

class ClassContentsTest extends \PHPUnit_Framework_TestCase {
  private ?ScannedClass $class;

  protected function setUp(): void {
    $parser = FileParser::FromFile(__DIR__.'/data/class_contents.php');
    $this->class = $parser->getClasses()[0];
    $this->assertSame(
      'Facebook\\DefinitionFinder\\Test\\ClassWithContents',
      $this->class->getName(),
    );
  }

  public function testAnonymousClasses(): void {
    $parser = FileParser::FromFile(__DIR__.'/data/class_contents_php.php');
    $this->assertEmpty(
      $parser->getFunctions(),
      'Should be no functions - probably interpreting a method as a function',
    );
    $this->assertSame(
      1,
      C\count($parser->getClasses()),
      'The anonymous class should not be returned',
    );
    $class = $parser->getClass('ClassUsingAnonymousClass');
    $this->assertEquals(
      vec['methodOne', 'methodTwo'],
      Vec\map($class->getMethods(), $it ==> $it->getName()),
    );
  }

  public function testNamespaceName(): void {
    $this->assertEquals(
      'Facebook\DefinitionFinder\Test',
      $this->class?->getNamespaceName(),
    );
    $this->assertEquals(
      vec['', '', '', ''],
      Vec\map($this->class?->getMethods()?? vec[], $x ==> $x->getNamespaceName()),
    );
  }

  public function testShortName(): void {
    $this->assertEquals('ClassWithContents', $this->class?->getShortName());
    $this->assertEquals(
      vec[
        'publicMethod',
        'protectedMethod',
        'privateMethod',
        'PublicStaticMethod',
      ],
      Vec\map($this->class?->getMethods()?? vec[], $x ==> $x->getShortName()),
    );
  }

  public function testMethodNames(): void {
    $this->assertEquals(
      vec[
        'publicMethod',
        'protectedMethod',
        'privateMethod',
        'PublicStaticMethod',
      ],
      Vec\map($this->class?->getMethods()?? vec[], $x ==> $x->getName()),
    );
  }

  public function testMethodVisibility(): void {
    $this->assertEquals(
      vec[true, false, false, true],
      Vec\map($this->class?->getMethods()?? vec[], $x ==> $x->isPublic()),
      'isPublic',
    );
    $this->assertEquals(
      vec[false, true, false, false],
      Vec\map($this->class?->getMethods()?? vec[], $x ==> $x->isProtected()),
      'isProtected',
    );
    $this->assertEquals(
      vec[false, false, true, false],
      Vec\map($this->class?->getMethods()?? vec[], $x ==> $x->isPrivate()),
      'isPrivate',
    );
  }

  public function testHackConstants(): void {
    $constants = $this->class?->getConstants();
    $this->assertEquals(
      vec['FOO', 'BAR'],
      Vec\map($constants?? vec[], $x ==> $x->getName()),
    );
    $this->assertEquals(
      vec['string', 'int'],
      Vec\map($constants?? vec[], $x ==> $x->getTypehint()?->getTypeName()),
    );
    $this->assertEquals(
      vec[false, false],
      Vec\map($constants?? vec[], $x ==> $x->isAbstract()),
    );
    $this->assertEquals(
      vec["'bar'", '60 * 60 * 24'],
      Vec\map($constants?? vec[], $x ==> $x->getValue()),
    );
    $this->assertEquals(
      vec['/** FooDoc */', '/** BarDoc */'],
      Vec\map($constants?? vec[], $x ==> $x->getDocComment()),
    );
  }

  public function testPHPConstants(): void {
    $parser = FileParser::FromFile(__DIR__.'/data/php_class_contents.php');
    $class = $parser->getClass('Foo');
    $constants = $class->getConstants();

    $this->assertEquals(
      vec['FOO', 'BAR'],
      Vec\map($constants, $x ==> $x->getName()),
    );
    $this->assertEquals(
      vec[null, null],
      Vec\map($constants, $x ==> $x->getTypehint()),
    );
    $this->assertEquals(
      vec["'bar'", '60 * 60 * 24'],
      Vec\map($constants, $x ==> $x->getValue()),
    );
    $this->assertEquals(
      vec['/** FooDoc */', '/** BarDoc */'],
      Vec\map($constants, $x ==> $x->getDocComment()),
    );
  }

  /** Omitting public/protected/private is permitted in PHP */
  public function testDefaultMethodVisibility(): void {
    $parser = FileParser::FromFile(__DIR__.'/data/php_class_contents.php');
    $funcs = $parser->getClass('Foo')->getMethods();

    $this->assertEquals(
      vec[
        'defaultVisibility',
        'privateVisibility',
        'alsoDefaultVisibility',
      ],
      Vec\map($funcs, $x ==> $x->getName()),
    );
    $this->assertEquals(
      vec[true, false, true],
      Vec\map($funcs, $x ==> $x->isPublic()),
    );
  }

  public function testMethodsAreStatic(): void {
    $this->assertEquals(
      vec[false, false, false, true],
      Vec\map($this->class?->getMethods()?? vec[], $x ==> $x->isStatic()),
      'isPublic',
    );
  }

  public function testPropertyNames(): void {
    $this->assertEquals(
      vec['foo', 'herp'],
      Vec\map($this->class?->getProperties()?? vec[], $x ==> $x->getName()),
    );
  }

  public function testPropertyVisibility(): void {
    $this->assertEquals(
      vec[false, true],
      Vec\map($this->class?->getProperties()?? vec[], $x ==> $x->isPublic()),
    );
  }

  public function testPropertyTypes(): void {
    $this->assertEquals(
      vec['bool', 'string'],
      $this->class?->getProperties()
      |> Vec\map($$ ?? vec[], $x ==> $x->getTypehint()?->getTypeName()),
    );
  }

  public function testTypelessProperty(): void {
    $parser = FileParser::FromFile(__DIR__.'/data/php_class_contents.php');
    $props = $parser->getClass('Foo')->getProperties();

    $this->assertEquals(
      vec['untypedProperty'],
      Vec\map($props, $x ==> $x->getName()),
    );
    $this->assertEquals(vec[null], Vec\map($props, $x ==> $x->getTypehint()));
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
    $parser = FileParser::FromData($code);
    $class = $parser->getClass('Foo');
    $props = $class->getProperties();

    $this->assertEquals(vec['bar'], Vec\map($props, $x ==> $x->getName()));

    $this->assertEquals(
      vec[$type],
      Vec\map($props, $x ==> $x->getTypehint()?->getTypeName()),
    );


    $this->assertEquals(vec[true], Vec\map($props, $x ==> $x->isStatic()));
  }

  public function testTypeConstant(): void {
    $data = '<?hh class Foo { const type BAR = int; }';
    $parser = FileParser::FromData($data);
    $constants = $parser->getClass('Foo')->getTypeConstants();
    $constant = C\onlyx($constants);

    $this->assertSame('BAR', $constant->getName());
    $this->assertFalse($constant->isAbstract());
    $this->assertSame('int', $constant->getValue()?->getTypeText());
  }

  public function testClassAsTypeConstant(): void {
    $data = "<?hh\n".
      "class Foo { const type BAR = int; }\n".
      "class Bar {\n".
      "  const type FOO = Foo;\n".
      "  function foo(self::FOO::BAR \$baz): void {}\n".
      "}";
    $parser = FileParser::FromData($data);
    $class = $parser->getClass('Bar');
    $this->assertEquals(
      vec[vec['self::FOO::BAR'] ],
      Vec\map(
        $class->getMethods(),
        $method ==> Vec\map(
          $method->getParameters(),
          $param ==> $param->getTypehint()?->getTypeText()
        ),
      ),
    );
  }

  public function testConstraintedTypeConstant(): void {
    // I'm not aware of a use for this, but HHVM allows and tests for it
    $data = '<?hh class Foo { const type BAR as int = int; }';
    $parser = FileParser::FromData($data);
    $constants = $parser->getClass('Foo')->getTypeConstants();
    $constant = C\onlyx($constants);

    $this->assertSame('BAR', $constant->getName());
    $this->assertFalse($constant->isAbstract());
    $this->assertSame('int', $constant->getValue()?->getTypeText());
  }

  public function testAbstractConstant(): void {
    $data = '<?hh abstract class Foo { abstract const string BAR; }';
    $parser = FileParser::FromData($data);
    $constant = C\onlyx($parser->getClass('Foo')->getConstants());

    $this->assertSame('BAR', $constant->getName());
    $this->assertTrue($constant->isAbstract());
    $this->assertNull($constant->getValue());
  }


  public function testAbstractTypeConstant(): void {
    $data = '<?hh abstract class Foo { abstract const type BAR; }';
    $parser = FileParser::FromData($data);
    $constant = C\onlyx($parser->getClass('Foo')->getTypeConstants());

    $this->assertSame('BAR', $constant->getName());
    $this->assertTrue($constant->isAbstract());
    $this->assertNull($constant->getValue());
  }

  public function testConstrainedAbstractTypeConstant(): void {
    $data = '<?hh abstract class Foo { abstract const type BAR as Bar; }';
    $parser = FileParser::FromData($data);
    $constant = C\onlyx($parser->getClass('Foo')->getTypeConstants());

    $this->assertSame('BAR', $constant->getName());
    $this->assertTrue($constant->isAbstract());
    $this->assertSame('Bar', $constant->getValue()?->getTypeText());
  }

  public function testTypeConstantAsProperty(): void {
    $data = '<?hh class Foo { public this::FOO $foo; }';
    $parser = FileParser::FromData($data);
    $prop = C\onlyx($parser->getClass('Foo')->getProperties());

    $this->assertSame('this::FOO', $prop->getTypehint()?->getTypeText());
    $this->assertSame('foo', $prop->getName());
  }

  public function testTypeconstantAsReturnType(): void {
    $data = '<?hh class Foo { public function bar(): this::FOO {} }';
    $parser = FileParser::FromData($data);
    $method = C\onlyx($parser->getClass('Foo')->getMethods());

    $this->assertSame('this::FOO', $method->getReturnType()?->getTypeText());
  }

  public function testTypeconstantAsParameterType(): void {
    $data = '<?hh class Foo { public function bar(this::FOO $foo): void {} }';
    $parser = FileParser::FromData($data);
    $method = C\onlyx($parser->getClass('Foo')->getMethods());
    $param = C\onlyx($method->getParameters());

    $this->assertSame('this::FOO', $param->getTypehint()?->getTypeText());
    $this->assertSame('foo', $param->getName());
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
      $namespace === '' ? '' : "namespace $namespace;",
      $returnText,
    );
    $className = \ltrim($namespace.'\Foo', "\\");
    $parser = FileParser::FromData($data);
    $method = C\onlyx($parser->getClass($className)->getMethods());

    $this->assertSame(
      $expectedTypehintText,
      $method->getReturnType()?->getTypeText(),
    );
  }
}
