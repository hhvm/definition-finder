<?hh // strict
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
use namespace HH\Lib\{Dict, Vec};

function generic_from_ast(
  HHAST\TypeParameter $node,
): ScannedGeneric {
  $v = $node->getVariance();
  if ($v instanceof HHAST\PlusToken) {
    $v = VarianceToken::COVARIANT;
  } else if ($v instanceof HHAST\MinusToken) {
    $v = VarianceToken::CONTRAVARIANT;
  } else {
    invariant($v === null, 'unknown variance');
    $v = VarianceToken::INVARIANT;
  }

  return new ScannedGeneric(
    $node->getName()->getText(),
    $v,
    vec[], // FIXME: constraints
  );
}
