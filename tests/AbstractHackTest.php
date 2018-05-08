<?hh // strict
/*
 *  Copyright (c) 2015-present, Facebook, Inc.
 *  All rights reserved.
 *
 *  This source code is licensed under the MIT license found in the
 *  LICENSE file in the root directory of this source tree.
 *
 */

use type Facebook\DefinitionFinder\{
  ScannedClassish,
  ScannedFunction,
  ScannedFunctionish,
  ScannedMethod,
};
use namespace HH\Lib\{C, Vec};

abstract class AbstractHackTest extends PHPUnit_Framework_TestCase {
  private ?Facebook\DefinitionFinder\FileParser $parser;

  abstract protected function getFilename(): string;
  abstract protected function getPrefix(): string;
  abstract protected function getSuffixForRootDefinitions(): string;

  protected function setUp(): void {
    $this->parser = \Facebook\DefinitionFinder\FileParser::FromFile(
      __DIR__.'/data/'.$this->getFilename(),
    );
  }

  public function testClasses(): void {
    $this->assertEquals(
      vec[
        $this->getPrefix().'SimpleClass',
        $this->getPrefix().'SimpleChildClass',
        $this->getPrefix().'GenericClass',
        $this->getPrefix().'GenericAliasedConstraintClass',
        $this->getPrefix().'AbstractFinalClass',
        $this->getPrefix().'AbstractClass',
        $this->getPrefix().'xhp_foo',
        $this->getPrefix().'xhp_foo__bar',
      ],
      $this->parser?->getClassNames(),
    );
  }

  public function testSuperClass(): void {
    $class = $this->parser?->getClass($this->getPrefix().'SimpleChildClass');
    $this->assertSame(
      $this->getPrefix().'SimpleClass',
      $class?->getParentClassName(),
    );
  }

  public function testInterface(): void {
    $class = $this->parser?->getClass($this->getPrefix().'SimpleChildClass');
    $this->assertEquals(
      vec[$this->getPrefix().'SimpleInterface'],
      $class?->getInterfaceNames(),
    );
  }

  public function testUsedTraits(): void {
    $class = $this->parser?->getClass($this->getPrefix().'SimpleChildClass');
    $this->assertEquals(
      vec[$this->getPrefix().'SimpleTrait'],
      $class?->getTraitNames(),
    );
  }

  public function testTypes(): void {
    $this->assertEquals(
      vec[
        $this->getPrefix().'MyType',
        $this->getPrefix().'MyGenericType',
      ],
      $this->parser?->getTypeNames(),
    );
  }

  public function testNewtypes(): void {
    $this->assertEquals(
      vec[
        $this->getPrefix().'MyNewtype',
        $this->getPrefix().'MyGenericNewtype',
      ],
      $this->parser?->getNewtypeNames(),
    );
  }

  public function testEnums(): void {
    $this->assertEquals(
      vec[$this->getPrefix().'MyEnum'],
      $this->parser?->getEnumNames(),
    );
  }

  public function testFunctions(): void {
    // As well as testing that these functions were mentioned,
    // this also checks that SimpelClass::iAmNotAGlobalFunction
    // was not listed
    $this->assertEquals(
      vec[
        $this->getPrefix().'simple_function',
        $this->getPrefix().'generic_function',
        $this->getPrefix().'constrained_generic_function',
        $this->getPrefix().'byref_return_function',
        $this->getPrefix().'returns_int',
        $this->getPrefix().'returns_generic',
        $this->getPrefix().'returns_nested_generic',
        $this->getPrefix().'aliased',
        $this->getPrefix().'aliased_with_namespace',
        $this->getPrefix().'aliased_with_nested_namespace',
        $this->getPrefix().'aliased_namespace',
        $this->getPrefix().'aliased_no_as',
      ],
      $this->parser?->getFunctionNames(),
    );
  }

  public function testConstants(): void {
    // Makes sure that GenericClass::NOT_A_GLOBAL_CONSTANT is not returned
    $this->assertEquals(
      vec[
        $this->getPrefix().'MY_CONST',
        $this->getPrefix().'MY_TYPED_CONST',
        // define() puts constants into the root namespace
        'MY_OLD_STYLE_CONST'.$this->getSuffixForRootDefinitions(),
        'MY_OTHER_OLD_STYLE_CONST'.$this->getSuffixForRootDefinitions(),
        'NOW_IM_JUST_MESSING_WITH_YOU'.$this->getSuffixForRootDefinitions(),
      ],
      $this->parser?->getConstantNames(),
    );
    $this->assertEquals(
      vec['456', '123', '789', "'herp'", "'derp'"],
      Vec\map($this->parser?->getConstants() ?? vec[], $x ==> $x->getValue()),
    );
  }

  public function testClassGenerics(): void {
    $class = $this->parser?->getClass($this->getPrefix().'GenericClass');
    assert($class !== null);

    $this->assertEquals(
      vec['Tk', 'Tv'],
      Vec\map($class->getGenericTypes(), $x ==> $x->getName()),
    );

    $this->assertEquals(
      vec[0, 0],
      Vec\map($class->getGenericTypes(), $x ==> C\count($x->getConstraints())),
    );

    $class = $this
      ->parser
      ?->getClass($this->getPrefix().'GenericAliasedConstraintClass');
    assert($class !== null);

    $this->assertEquals(
      vec['T'],
      Vec\map($class->getGenericTypes(), $x ==> $x->getName()),
    );

    $this->assertEquals(
      vec['Foo'],
      Vec\map($class->getGenericTypes(), $x ==> $x->getConstraints()[0]['type']),
    );
  }

  public function testFunctionGenerics(): void {
    $func = $this->getFunction('generic_function');

    $this->assertEquals(
      vec['Tk', 'Tv'],
      Vec\map($func->getGenericTypes(), $x ==> $x->getName()),
    );

    $this->assertEquals(
      vec[0, 0],
      Vec\map($func->getGenericTypes(), $x ==> C\count($x->getConstraints())),
    );

    $func = $this->getFunction('constrained_generic_function');

    $this->assertEquals(
      vec['Tk', 'Tv'],
      Vec\map($func->getGenericTypes(), $x ==> $x->getName()),
    );

    $this->assertEquals(
      vec['arraykey', null],
      Vec\map(
        $func->getGenericTypes(),
        $x ==> {
          $constraints = $x->getConstraints();
          return $constraints[0]['type'] ?? null;
        },
      ),
    );
  }

  public function testFunctionReturnTypes(): void {
    $type = $this->getFunction('returns_int')->getReturnType();
    $this->assertSame('int', $type?->getTypeName());
    $this->assertEmpty($type?->getGenericTypes());

    $type = $this->getFunction('returns_generic')->getReturnType();
    $this->assertSame('Vector', $type?->getTypeName());
    $generics = $type?->getGenericTypes();
    $this->assertSame(1, count($generics));
    $sub_type = $generics[0] ?? null;
    $this->assertSame('int', $sub_type?->getTypeName());
    $this->assertEmpty($sub_type?->getGenericTypes());

    $type = $this->getFunction('returns_nested_generic')->getReturnType();
    $this->assertSame('Vector', $type?->getTypeName());
    $generics = $type?->getGenericTypes();
    $this->assertSame(1, count($generics));
    $sub_type = $generics[0] ?? null;
    $this->assertSame('Vector', $sub_type?->getTypeName());
    $sub_generics = $sub_type?->getGenericTypes();
    $this->assertSame(1, count($sub_generics));
    $sub_sub_type = $sub_generics[0] ?? null;
    $this->assertSame('int', $sub_sub_type?->getTypeName());
    $this->assertEmpty($sub_sub_type?->getGenericTypes());
  }

  public function testAliasedTypehints(): void {
    $data = Map {
      'Foo' => $this->getFunction('aliased'),
      'SingleNamespace\Foo' => $this->getFunction('aliased_with_namespace'),
      'Namespaces\AreNested\Now\Foo' =>
        $this->getFunction('aliased_with_nested_namespace'),
      'Namespaces\AreNested\Now\Foo' => $this->getFunction('aliased_namespace'),
      'Namespaces\AreNested\Now\Bar' => $this->getFunction('aliased_no_as'),
      'Namespaces\AreNested\Now\Bar' =>
        $this->getClassMethod('SimpleClass', 'aliasInClassScope'),
    };
    foreach ($data as $typeName => $fun) {
      $returnType = $fun->getReturnType();
      $paramType = ($fun->getParameters()[0] ?? null)?->getTypehint();
      $this->assertSame($typeName, $returnType?->getTypeName());
      $this->assertSame($typeName, $paramType?->getTypeName());
    }
  }

  private function getFunction(string $name): ScannedFunction {
    $func = $this->parser?->getFunction($this->getPrefix().$name);
    invariant($func !== null, 'Could not find function %s', $name);
    return $func;
  }

  private function getClass(string $name): ScannedClassish {
    $class = $this->parser?->getClass($this->getPrefix().$name);
    invariant($class !== null, 'Could not find class %s', $name);
    return $class;
  }

  private function getClassMethod(
    string $className,
    string $methodName,
  ): ScannedMethod {
    $method = $this
      ->getClass($className)
      ->getMethods()
      |> Vec\filter($$, $m ==> $m->getName() === $methodName)
      |> C\first($$);
    invariant(
      $method !== null,
      'Could not find method %s in class %s',
      $methodName,
      $className,
    );
    return $method;
  }
}
