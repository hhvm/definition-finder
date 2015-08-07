<?hh
/*
 *  Copyright (c) 2015, Facebook, Inc.
 *  All rights reserved.
 *
 *  This source code is licensed under the BSD-style license found in the
 *  LICENSE file in the root directory of this source tree. An additional grant
 *  of patent rights can be found in the PATENTS file in the same directory.
 *
 */

class SimpleClass {
  public function iAmNotAGlobalFunction(): void { }
}

class GenericClass<Tk, Tv> {
  const NOT_A_GLOBAL_CONSTANT = 42;
  const int ALSO_NOT_A_GLOBAL_CONSTANT = 42;
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

function &byref_return_function() { }

const MY_CONST = 456;
const int MY_TYPED_CONST = 123;
define('MY_OLD_STYLE_CONST', 789);
define("MY_OTHER_OLD_STYLE_CONST", 'herp');
define(NOW_IM_JUST_FUCKING_WITH_YOU, 'derp');

type MyType = int;
type MyGenericType<T> = string;
newtype MyNewtype = string;
newtype MyGenericNewtype<T> = string;

enum MyEnum: string {
}
