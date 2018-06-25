<?hh // strict
/*
 *  Copyright (c) 2015-present, Facebook, Inc.
 *  All rights reserved.
 *
 *  This source code is licensed under the MIT license found in the
 *  LICENSE file in the root directory of this source tree.
 *
 */

namespace Facebook\DefinitionFinder\Expression;

use namespace Facebook\HHAST;
use namespace HH\Lib\Vec;

final class StaticFieldInitializerExpression extends Expression<(arraykey, mixed)> {
  const type TNode = HHAST\FieldInitializer;

  <<__Override>>
  protected static function matchImpl(
    this::TNode $node,
  ): ?Expression<(arraykey, mixed)> {
    $value = StaticExpression::match($node->getValue())?->getValue();
    $key = StaticExpression::match($node->getName())?->getValue();

    if (is_string($key)) {
      return new self(tuple($key, $value));
    }
    if (is_int($key)) {
      return new self(tuple($key, $value));
    }
    return null;
  }
}
