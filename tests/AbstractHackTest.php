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

use Facebook\DefinitionFinder\ScannedFunction;
use Facebook\DefinitionFinder\ScannedTypehint;

abstract class AbstractHackTest extends PHPUnit_Framework_TestCase {
  private ?Facebook\DefinitionFinder\FileParser $parser;

  abstract protected function getFilename(): string;
  abstract protected function getPrefix(): string;

  protected function setUp(): void {
    $this->parser = \Facebook\DefinitionFinder\FileParser::FromFile(
      __DIR__.'/data/'.$this->getFilename(),
    );
  }

  public function testClasses(): void {
    $this->assertEquals(
      Vector {
        $this->getPrefix().'SimpleClass',
        $this->getPrefix().'GenericClass',
        $this->getPrefix().'AbstractFinalClass',
        $this->getPrefix().'AbstractClass',
        $this->getPrefix().'xhp_foo',
        $this->getPrefix().'xhp_foo__bar',
      },
      $this->parser?->getClassNames(),
    );
  }

  public function testTypes(): void {
    $this->assertEquals(
      Vector {
        $this->getPrefix().'MyType',
        $this->getPrefix().'MyGenericType',
      },
      $this->parser?->getTypeNames(),
    );
  }

  public function testNewtypes(): void {
    $this->assertEquals(
      Vector {
        $this->getPrefix().'MyNewtype',
        $this->getPrefix().'MyGenericNewtype',
      },
      $this->parser?->getNewtypeNames(),
    );
  }

  public function testEnums(): void {
    $this->assertEquals(
      Vector {
        $this->getPrefix().'MyEnum',
      },
      $this->parser?->getEnumNames(),
    );
  }

  public function testFunctions(): void {
    // As well as testing that these functions were mentioned,
    // this also checks that SimpelClass::iAmNotAGlobalFunction
    // was not listed
    $this->assertEquals(
      Vector {
        $this->getPrefix().'simple_function',
        $this->getPrefix().'generic_function',
        $this->getPrefix().'constrained_generic_function',
        $this->getPrefix().'byref_return_function',
        $this->getPrefix().'returns_int',
        $this->getPrefix().'returns_generic',
        $this->getPrefix().'returns_nested_generic',
      },
      $this->parser?->getFunctionNames(),
    );
  }

  public function testConstants(): void {
    // Makes sure that GenericClass::NOT_A_GLOBAL_CONSTANT is not returned
    $this->assertEquals(
      Vector {
        $this->getPrefix().'MY_CONST',
        $this->getPrefix().'MY_TYPED_CONST',
        $this->getPrefix().'MY_OLD_STYLE_CONST',
        $this->getPrefix().'MY_OTHER_OLD_STYLE_CONST',
        $this->getPrefix().'NOW_IM_JUST_FUCKING_WITH_YOU',
      },
      $this->parser?->getConstantNames(),
    );
  }

  public function testFunctionGenerics(): void {
    $func = $this->getFunction('generic_function');

    $this->assertEquals(
      Vector {'Tk', 'Tv'},
      $func->getGenerics()->map($x ==> $x->getName()),
    );

    $this->assertEquals(
      Vector {null, null},
      $func->getGenerics()->map($x ==> $x->getConstraint()),
    );

    $func = $this->getFunction('constrained_generic_function');

    $this->assertEquals(
      Vector {'Tk', 'Tv'},
      $func->getGenerics()->map($x ==> $x->getName()),
    );

    $this->assertEquals(
      Vector {'arraykey', null},
      $func->getGenerics()->map($x ==> $x->getConstraint()),
    );
  }

  public function testFunctionReturnTypes(): void {
    $type = $this->getFunction('returns_int')->getReturnType();
    $this->assertSame('int', $type?->getTypeName());
    $this->assertEmpty($type?->getGenerics());

    $type = $this->getFunction('returns_generic')->getReturnType();
    $this->assertSame('Vector', $type?->getTypeName());
    $generics = $type?->getGenerics();
    $this->assertSame(1, count($generics));
    $sub_type = $generics?->get(0);
    $this->assertSame('int', $sub_type?->getTypeName());
    $this->assertEmpty($sub_type?->getGenerics());

    $type = $this->getFunction('returns_nested_generic')->getReturnType();
    $this->assertSame('Vector', $type?->getTypeName());
    $generics = $type?->getGenerics();
    $this->assertSame(1, count($generics));
    $sub_type = $generics?->get(0);
    $this->assertSame('Vector', $sub_type?->getTypeName());
    $sub_generics = $sub_type?->getGenerics();
    $this->assertSame(1, count($sub_generics));
    $sub_sub_type = $sub_generics?->get(0);
    $this->assertSame('int', $sub_sub_type?->getTypeName());
    $this->assertEmpty($sub_sub_type?->getGenerics());
  }

  private function getFunction(string $name): ScannedFunction {
    $funcs = $this->parser?->getFunctions();

    $func = $funcs?->filter(
      $x ==> $x->getName() === ($this->getPrefix().$name)
    )?->get(0);
    invariant($func !== null, 'Could not find function %s', $name);
    return $func;
  }
}
