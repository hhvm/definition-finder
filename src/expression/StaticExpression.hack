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
  const type TNode = HHAST\Node;
  <<__Override>>
  protected static function matchImpl(
    HHAST\Node $n,
  ): ?Expression<mixed> {
    $impls = vec[
      LiteralExpression::class,
      NameExpression::class,
      StaticBinaryExpression::class,
      StaticDarrayExpression::class,
      StaticDictExpression::class,
      StaticKeysetExpression::class,
      StaticListExpression::class,
      StaticPrefixUnaryExpression::class,
      StaticScopeResolutionExpression::class,
      StaticShapeExpression::class,
      StaticVarrayExpression::class,
      StaticVecExpression::class,
    ];
    foreach ($impls as $class) {
      $r = $class::match($n);
      if ($r) {
        return $r;
      }
    }
    return null;
  }
}
