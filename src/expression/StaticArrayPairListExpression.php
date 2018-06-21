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

use type Facebook\DefinitionFinder\TokenQueue;
use namespace Facebook\TypeAssert;

final class StaticArrayPairListExpression extends Expression<dict<arraykey, mixed>> {
  <<__Override>>
  protected static function matchImpl(TokenQueue $tq): ?this {
    $values = dict[];
    while ($tq->haveTokens()) {
      self::consumeWhitespace($tq);
      $expr = StaticScalarExpression::match($tq);
      if (!$expr) {
        list($t, $_) = $tq->peek();
        if ($values) {
          return new self($values);
        }
        return null;
      }
      $key = TypeAssert\arraykey($expr->getValue());

      self::consumeWhitespace($tq);
      list($_, $ttype) = $tq->shift();
      if ($ttype !== \T_DOUBLE_ARROW) {
        return null;
      }
      self::consumeWhitespace($tq);
      $expr = StaticScalarExpression::match($tq);
      if ($expr === null) {
        return null;
      }
      $values[$key] = $expr->getValue();

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
