<?hh
/*
 *  Copyright (c) 2015-present, Facebook, Inc.
 *  All rights reserved.
 *
 *  This source code is licensed under the MIT license found in the
 *  LICENSE file in the root directory of this source tree.
 *
 */

namespace Namespaces\AreNestedNow;

use Foo as Aliased;
use SingleNamespace\Foo as AliasedWithNamespace;
use Namespaces\AreNested\Now\Foo as AliasedWithNestedNamespace;
use Namespaces\AreNested\Now\Bar;
use Namespaces\AreNested\Now as AliasedNamespace;

class SimpleClass {
  public function iAmNotAGlobalFunction(): void { }
  public function aliasInClassScope(Bar $bar): Bar {
    return $bar;
  }
}

interface SimpleInterface {}
trait SimpleTrait {}

class SimpleChildClass
extends SimpleClass
implements SimpleInterface {
  use SimpleTrait;
}

class GenericClass<Tk, Tv> {
  const NOT_A_GLOBAL_CONSTANT = 42;
  const int ALSO_NOT_A_GLOBAL_CONSTANT = 42;
}

class GenericAliasedConstraintClass<T as Aliased> {
}

abstract final class AbstractFinalClass {
}

abstract class AbstractClass {
  abstract public function iAmAlsoNotAGlobalFunction(): void;
}

class :foo {
}

class :foo:bar {
}

function simple_function(): void {
}

function generic_function<Tk, Tv>(): void {
}

function constrained_generic_function<Tk as arraykey, Tv>(): void {
}

function returns_int(): int { return 123; }

function returns_generic(): Vector<int> { return Vector { 123 }; }

function returns_nested_generic(): Vector<Vector<int>> {
  return Vector { Vector { 123 } };
}

function aliased(Aliased $aliased): Aliased {
  return $aliased;
}

function aliased_with_namespace(
  AliasedWithNamespace $aliased,
): AliasedWithNamespace {
  return $aliased;
}

function aliased_with_nested_namespace(
  AliasedWithNestedNamespace $aliased,
): AliasedWithNestedNamespace {
  return $aliased;
}

function aliased_namespace(
  AliasedNamespace\Foo $aliased,
): AliasedNamespace\Foo {
  return $aliased;
}

function aliased_no_as(Bar $aliased): Bar {
   return $aliased;
}

const /* HH_IGNORE_ERROR[2001] intentional for testing */ MY_CONST = 456;
const int MY_TYPED_CONST = 123;

type MyType = int;
type MyGenericType<T> = string;
newtype MyNewtype = string;
newtype MyGenericNewtype<T> = string;

enum MyEnum: string {
}
