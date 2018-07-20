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
  FileParser,
  ScannedClassish,
  ScannedFunction,
  ScannedMethod,
};
use function Facebook\FBExpect\expect;
use namespace HH\Lib\{C, Vec};

abstract class AbstractHackTest extends PHPUnit_Framework_TestCase {
  private ?FileParser $parser;

  abstract protected function getFilename(): string;
  abstract protected function getPrefix(): string;
  abstract protected function getSuffixForRootDefinitions(): string;

  <<__Override>>
  protected function setUp(): void {
    $this->parser = FileParser::fromFile(__DIR__.'/data/'.$this->getFilename());
  }

  public function testClasses(): void {
    expect($this->parser?->getClassNames())->toBeSame(
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
    );
  }

  public function testSuperClass(): void {
    $class = $this->parser?->getClass($this->getPrefix().'SimpleChildClass');
    expect($class?->getParentClassName())->toBeSame(
      $this->getPrefix().'SimpleClass',
    );
  }

  public function testInterface(): void {
    $class = $this->parser?->getClass($this->getPrefix().'SimpleChildClass');
    expect($class?->getInterfaceNames())->toBeSame(
      vec[$this->getPrefix().'SimpleInterface'],
    );
  }

  public function testUsedTraits(): void {
    $class = $this->parser?->getClass($this->getPrefix().'SimpleChildClass');
    expect($class?->getTraitNames())->toBeSame(
      vec[$this->getPrefix().'SimpleTrait'],
    );
  }

  public function testTypes(): void {
    expect($this->parser?->getTypeNames())->toBeSame(
      vec[
        $this->getPrefix().'MyType',
        $this->getPrefix().'MyGenericType',
      ],
    );
  }

  public function testNewtypes(): void {
    expect($this->parser?->getNewtypeNames())->toBeSame(
      vec[
        $this->getPrefix().'MyNewtype',
        $this->getPrefix().'MyGenericNewtype',
      ],
    );
  }

  public function testEnums(): void {
    expect($this->parser?->getEnumNames())->toBeSame(
      vec[$this->getPrefix().'MyEnum'],
    );
  }

  public function testFunctions(): void {
    // As well as testing that these functions were mentioned,
    // this also checks that SimpelClass::iAmNotAGlobalFunction
    // was not listed
    expect($this->parser?->getFunctionNames())->toBeSame(
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
    );
  }

  public function testConstants(): void {
    // Makes sure that GenericClass::NOT_A_GLOBAL_CONSTANT is not returned
    expect($this->parser?->getConstantNames())->toBeSame(
      vec[
        $this->getPrefix().'MY_CONST',
        $this->getPrefix().'MY_TYPED_CONST',
        // define() puts constants into the root namespace
        'MY_OLD_STYLE_CONST'.$this->getSuffixForRootDefinitions(),
        'MY_OTHER_OLD_STYLE_CONST'.$this->getSuffixForRootDefinitions(),
        'NOW_IM_JUST_MESSING_WITH_YOU'.$this->getSuffixForRootDefinitions(),
      ],
    );
    expect(
      Vec\map(
        $this->parser?->getConstants() ?? vec[],
        $x ==> $x->getValue()->getStaticValue(),
      ),
    )->toBeSame(vec[456, 123, 789, 'herp', 'derp']);
  }

  public function testClassGenerics(): void {
    $class = $this->parser?->getClass($this->getPrefix().'GenericClass');
    assert($class !== null);

    expect(Vec\map($class->getGenericTypes(), $x ==> $x->getName()))->toBeSame(
      vec['Tk', 'Tv'],
    );

    expect(
      Vec\map($class->getGenericTypes(), $x ==> C\count($x->getConstraints())),
    )->toBeSame(vec[0, 0]);

    $class = $this
      ->parser
      ?->getClass($this->getPrefix().'GenericAliasedConstraintClass');
    assert($class !== null);

    expect(Vec\map($class->getGenericTypes(), $x ==> $x->getName()))->toBeSame(
      vec['T'],
    );

    expect(
      Vec\map(
        $class->getGenericTypes(),
        $x ==> $x->getConstraints()[0]['type']->getTypeText(),
      ),
    )->toBeSame(vec['Foo']);
  }

  public function testFunctionGenerics(): void {
    $func = $this->getFunction('generic_function');

    expect(Vec\map($func->getGenericTypes(), $x ==> $x->getName()))->toBeSame(
      vec['Tk', 'Tv'],
    );

    expect(
      Vec\map($func->getGenericTypes(), $x ==> C\count($x->getConstraints())),
    )->toBeSame(vec[0, 0]);

    $func = $this->getFunction('constrained_generic_function');

    expect(Vec\map($func->getGenericTypes(), $x ==> $x->getName()))->toBeSame(
      vec['Tk', 'Tv'],
    );

    expect(
      Vec\map(
        $func->getGenericTypes(),
        $x ==> {
          $constraints = $x->getConstraints();
          return ($constraints[0]['type'] ?? null)?->getTypeText();
        },
      ),
    )->toBeSame(vec['arraykey', null]);
  }

  public function testFunctionReturnTypes(): void {
    $type = $this->getFunction('returns_int')->getReturnType();
    expect($type?->getTypeName())->toBeSame('int');
    expect($type?->getGenericTypes())->toBeEmpty();

    $type = $this->getFunction('returns_generic')->getReturnType();
    expect($type?->getTypeName())->toBeSame('Vector');
    $generics = $type?->getGenericTypes();
    expect(count($generics))->toBeSame(1);
    $sub_type = $generics[0] ?? null;
    expect($sub_type?->getTypeName())->toBeSame('int');
    expect($sub_type?->getGenericTypes())->toBeEmpty();

    $type = $this->getFunction('returns_nested_generic')->getReturnType();
    expect($type?->getTypeName())->toBeSame('Vector');
    $generics = $type?->getGenericTypes();
    expect(count($generics))->toBeSame(1);
    $sub_type = $generics[0] ?? null;
    expect($sub_type?->getTypeName())->toBeSame('Vector');
    $sub_generics = $sub_type?->getGenericTypes();
    expect(count($sub_generics))->toBeSame(1);
    $sub_sub_type = $sub_generics[0] ?? null;
    expect($sub_sub_type?->getTypeName())->toBeSame('int');
    expect($sub_sub_type?->getGenericTypes())->toBeEmpty();
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
      expect($returnType?->getTypeName())->toBeSame($typeName);
      expect($paramType?->getTypeName())->toBeSame($typeName);
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
