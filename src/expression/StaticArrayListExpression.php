<?hh // strict
/*
 *  Copyright (c) 2015-present, Facebook, Inc.
 *  All rights reserved.
 *
 *  This source code is licensed under the BSD-style license found in the
 *  LICENSE file in the root directory of this source tree. An additional grant
 *  of patent rights can be found in the PATENTS file in the same directory.
 *
 */

namespace Facebook\DefinitionFinder\Expression;

use Facebook\DefinitionFinder\TokenQueue;

final class StaticArrayListExpression extends Expression {
  protected static function matchImpl(TokenQueue $tq): ?Expression {
    $values = [];
    while ($tq->haveTokens()) {
      self::consumeWhitespace($tq);
      $expr = StaticScalarExpression::match($tq);
      if (!$expr) {
        if ($values) {
          // Trailing comma
          return new self($values);
        }
        return null;
      }
      $values[] = $expr->getValue();
      self::consumeWhitespace($tq);
      list($t, $_) = $tq->peek();
      if ($t !== ',') {
        return new self($values);
      }
      $tq->shift();
      self::consumeWhitespace($tq);
    }
    return null;
  }
}
