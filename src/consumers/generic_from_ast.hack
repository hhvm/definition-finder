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

function generic_from_ast(
  ConsumerContext $context,
  HHAST\TypeParameter $node,
): ScannedGeneric {
  $v = $node->getVariance();
  if ($v is HHAST\PlusToken) {
    $variance = VarianceToken::COVARIANT;
  } else if ($v is HHAST\MinusToken) {
    $variance = VarianceToken::CONTRAVARIANT;
  } else {
    invariant($v === null, 'unknown variance');
    $variance = VarianceToken::INVARIANT;
  }

  $constraints = $node->getConstraints();
  if ($constraints === null) {
    $constraints = vec[];
  } else {
    $constraints = Vec\map(
      $constraints->getChildren(),
      (HHAST\TypeConstraint $c) ==> {
        $kw = $c->getKeyword();
        if ($kw is HHAST\AsToken) {
          $r = RelationshipToken::SUBTYPE;
        } else {
          invariant(
            $kw is HHAST\SuperToken,
            'unexpected relationship token: %s',
            $kw->getCode(),
          );
          $r = RelationshipToken::SUPERTYPE;
        }
        $type = typehint_from_ast($context, $c->getType());
        if ($type === null) {
          return null;
        }

        return shape(
          'type' => $type,
          'relationship' => $r,
        );
      },
    ) |> Vec\filter_nulls($$);
  }

  return new ScannedGeneric(
    $node->getName()->getText(),
    $variance,
    $constraints,
  );
}
