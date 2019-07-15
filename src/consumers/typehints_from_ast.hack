/*
 *  Copyright (c) 2015-present, Facebook, Inc.
 *  All rights reserved.
 *
 *  This source code is licensed under the MIT license found in the
 *  LICENSE file in the root directory of this source tree.
 *
 */

namespace Facebook\DefinitionFinder;

use namespace Facebook\HHAST;
use namespace HH\Lib\Vec;

function typehints_from_ast(
  ConsumerContext $context,
  ?HHAST\NodeList<HHAST\Node> $node,
): vec<ScannedTypehint> {
  if ($node === null) {
    return vec[];
  }
  return $node->getChildren()
    |> Vec\map($$, $c ==> $c is HHAST\ListItem<_> ? $c->getItem() : $c)
    |> Vec\map($$, $c ==> typehint_from_ast($context, $c))
    |> Vec\filter_nulls($$);
}
