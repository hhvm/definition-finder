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
use namespace HH\Lib\{Dict, Vec};

final class StaticDictExpression extends Expression<dict<arraykey, mixed>> {
  const type TNode = HHAST\DictionaryIntrinsicExpression;

  <<__Override>>
  protected static function matchImpl(
    this::TNode $node,
  ): ?Expression<dict<arraykey, mixed>> {
    return Vec\map(
      $node->getMembers()?->getChildrenOfItemsOfType(HHAST\Node::class) ?? vec[],
      $m ==> StaticElementInitializerExpression::match($m),
    )
      |> Vec\filter_nulls($$)
      |> Vec\map($$, $e ==> $e->getValue())
      |> Dict\from_entries($$)
      |> new self($$);
  }
}
