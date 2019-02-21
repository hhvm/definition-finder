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

final class StaticVecExpression extends Expression<vec<mixed>> {
  const type TNode = HHAST\VectorIntrinsicExpression;

  <<__Override>>
  protected static function matchImpl(
    this::TNode $node,
  ): ?Expression<vec<mixed>> {
    $m = $node->getMembers();
    if ($m === null) {
      return new self(vec[]);
    }
    return StaticListExpression::match($m);
  }
}
