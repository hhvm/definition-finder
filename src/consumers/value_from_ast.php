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

function value_from_ast(
  ?HHAST\EditableNode $node,
): ?ScannedValue {
  if ($node === null) {
    return null;
  }
  $expr = Expression\StaticExpression::match($node);
  if (!$expr) {
    return new ScannedValue(
      $node,
      None(),
    );
  }
  return new ScannedValue($node, Some($expr->getValue()));
}
