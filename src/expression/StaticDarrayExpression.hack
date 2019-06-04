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

final class StaticDarrayExpression extends Expression<darray<arraykey, mixed>> {
  const type TNode = HHAST\DarrayIntrinsicExpression;

  <<__Override>>
  protected static function matchImpl(
    this::TNode $node,
  ): ?Expression<darray<arraykey, mixed>> {
    $members = $node->getMembers();
    $members = $members?->getChildrenOfItemsOfType(HHAST\Node::class) ?? vec[];
    $ret = darray[];
    foreach ($members as $m) {
      $pair = StaticElementInitializerExpression::match($m);
      if ($pair === null) {
        return null;
      }
      list($key, $value) = $pair->getValue();
      $ret[$key] = $value;
    }
    return new self($ret);
  }
}
