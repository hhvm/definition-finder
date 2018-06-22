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

final class StaticExpression extends Expression<mixed> {
  const type TNode = HHAST\EditableNode;
  <<__Override>>
  protected static function matchImpl(
    HHAST\EditableNode $n,
  ): ?Expression<mixed> {
    $impls = vec[
      StaticDictExpression::class,
      StaticListExpression::class,
      StaticScalarExpression::class,
      StaticVecExpression::class,
    ];
    foreach ($impls as $class) {
      $r = $class::match($n);
      if ($r) {
        return $r;
      }
    }

    // TODO: throw on failure
    return null;
  }
}
