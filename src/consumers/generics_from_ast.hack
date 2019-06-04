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

function generics_from_ast(
  ConsumerContext $context,
  ?HHAST\TypeParameters $node,
): vec<ScannedGeneric> {
  if ($node === null) {
    return vec[];
  }
  return Vec\map(
    $node->getParameters()->getChildrenOfItems(),
    $p ==> generic_from_ast($context, $p),
  );
}
