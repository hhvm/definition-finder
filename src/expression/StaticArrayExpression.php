<?hh // strict
/*
 *  Copyright (c) 2015, Facebook, Inc.
 *  All rights reserved.
 *
 *  This source code is licensed under the BSD-style license found in the
 *  LICENSE file in the root directory of this source tree. An additional grant
 *  of patent rights can be found in the PATENTS file in the same directory.
 *
 */

namespace Facebook\DefinitionFinder\Expression;

use Facebook\DefinitionFinder\TokenQueue;

final class StaticArrayExpression extends Expression {
  protected static function matchImpl(TokenQueue $tq): ?Expression {
    list($t, $ttype) = $tq->shift();
    if ($t === '[') {
      $end = ']';
    } else if ($ttype === T_ARRAY) {
      list ($t, $ttype) = $tq->shift();
      if ($t !== '(') {
        return null;
      }
      $end = ')';
    } else {
      return null;
    }

    $values = StaticArrayPairListExpression::match($tq);
    if ($values === null) {
      $values = StaticArrayListExpression::match($tq);
    }
    $values = $values?->getValue() ?? [];

    list($t, $_) = $tq->shift();
    if ($t !== $end) {
      return null;
    }
    return new self($values);
  }
}