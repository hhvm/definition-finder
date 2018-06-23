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

final class StaticDarrayExpression extends Expression<darray<arraykey, mixed>> {
  const type TNode = HHAST\DarrayIntrinsicExpression;

  <<__Override>>
  protected static function matchImpl(
    this::TNode $node,
  ): ?Expression<darray<arraykey, mixed>> {
    if ($node instanceof HHAST\DarrayIntrinsicExpression) {
      $members = $node->getMembers();
    } else if ($node instanceof HHAST\ShapeExpression) {
      $members = $node->getFields();
    } else {
      return null;
    }
    $members = $members?->getItemsOfType(HHAST\EditableNode::class) ?? vec[];
    $ret = darray[];
    foreach ($members as $m) {
      $pair = StaticElementInitializerExpression::match($m);
      if ($pair === null) {
        return null;
      }
      list($key, $value) = $pair->getValue();
      if (is_int($key)) {
        /* HH_IGNORE_ERROR[4110] PHP-compatible array craziness */
        $ret[$key] = $value;
      } else if (is_string($key)) {
        /* HH_IGNORE_ERROR[4110] PHP-compatible array craziness */
        $ret[$key] = $value;
      } else {
        return null;
      }
    }
    return new self($ret);
  }
}
