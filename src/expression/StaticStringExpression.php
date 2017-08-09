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

final class StaticStringExpression extends Expression {
  protected static function matchImpl(TokenQueue $tq): ?Expression {
    $value = '';
    do {
      list($t, $ttype) = $tq->shift();
      if ($ttype !== T_CONSTANT_ENCAPSED_STRING) {
        return null;
      }
      $value .= substr($t, 1, -1); // remove wrapping quotes

      self::consumeWhitespace($tq);
      list($t, $_) = $tq->peek();
      if ($t !== '.') {
        return new self($value);
      }
      $tq->shift();
      self::consumeWhitespace($tq);
    } while ($tq->haveTokens());
    invariant_violation('Unexpected EOF');
  }
}
