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

final class StaticArrayExpression extends Expression<mixed> {
  const type TNode = HHAST\Node;

  <<__Override>>
  protected static function matchImpl(this::TNode $node): ?Expression<mixed> {
    if ($node is HHAST\ArrayIntrinsicExpression) {
      $members = $node->getMembers();
    } else {
      return null;
    }
    $members = $members?->getChildrenOfItemsOfType(HHAST\Node::class) ?? vec[];
    $ret = darray[];
    foreach ($members as $m) {
      $pair = StaticElementInitializerExpression::match($m);
      if ($pair) {
        list($key, $value) = $pair->getValue();
        if ($key is int) {
          $ret[$key] = $value;
        } else if ($key is string) {
          $ret[$key] = $value;
        } else {
          return null;
        }
      } else {
        /* HH_IGNORE_ERROR[4006] PHP-compatible array craziness */
        $ret[] = StaticExpression::match($m)?->getValue();
      }
    }
    return new self($ret);
  }
}
