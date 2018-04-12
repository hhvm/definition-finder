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

<<Foo>>
class ClassWithSimpleAttribute {}

<<Foo,Bar>>
class ClassWithSimpleAttributes {}

<<Herp('derp')>>
class ClassWithStringAttribute {}

<<Herp(123)>>
class ClassWithIntAttribute {}

<<Foo('bar','baz')>>
class ClassWithMultipleAttributeValues {}

<<FunctionFoo>>
function function_after_classes(): void {}

<<ClassFoo>>
class ClassAfterFunction {}

<<
Foo,
Bar
(
    'herp',
    'derp',
)
>>
class ClassWithFormattedAttributes {}

<<
Bar(
  ['herp']
)
>>
class ClassWithFormattedArrayAttribute {}
