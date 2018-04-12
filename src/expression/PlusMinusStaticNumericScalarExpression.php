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

use Facebook\DefinitionFinder\TokenQueue;

final class PlusMinusStaticNumericScalarExpression extends Expression {
  protected static function matchImpl(TokenQueue $tq): ?Expression {
    list($t, $_) = $tq->shift();
    if ($t !== '+' && $t !== '-') {
      return null;
    }
    $match = StaticNumericScalarExpression::match($tq);
    if (!$match) {
      return null;
    }
    return new self((int)($t.(string)$match->getValue()));
  }
}
