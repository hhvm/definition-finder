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

final class StaticVarrayExpression extends Expression<vec<mixed>> {
  const type TNode = HHAST\VarrayIntrinsicExpression;

  <<__Override>>
  protected static function matchImpl(
    this::TNode $node,
  ): ?Expression<vec<mixed>> {
    $m = $node->getMembers();
    if ($m === null) {
      return new self(vec[]);
    }
    $values = StaticListExpression::match($m);
    if ($values === null) {
      return null;
    }
    $out = vec[];
    foreach ($values->getValue() as $value) {
      $out[] = $value;
    }
    return new self($out);
  }
}
