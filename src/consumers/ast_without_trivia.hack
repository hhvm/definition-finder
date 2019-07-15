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

function ast_without_trivia<T as HHAST\Node>(
  T $node,
): T {
  return $node->rewriteDescendants(
    ($inner, $_) ==>
      $inner is HHAST\Trivia ? null : $inner,
  );
}
