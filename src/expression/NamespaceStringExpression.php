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

final class NamespaceStringExpression extends Expression {
  protected static function matchImpl(TokenQueue $tq): ?Expression {
    list($t, $ttype) = $tq->shift();
    if ($ttype === T_NS_SEPARATOR) {
      list($t, $ttype) = $tq->shift();
    }
    if ($ttype !== T_STRING) {
      return null;
    }
    $value = $t;

    while ($tq->haveTokens()) {
      list($t, $ttype) = $tq->peek();
      if ($ttype !== T_NS_SEPARATOR) {
        return new self($value);
      }
      $tq->shift();
      list($t, $ttype) = $tq->shift();
      if ($ttype !== T_STRING) {
        return null;
      }
      $value .= "\\".$t;
    }
    invariant_violation('Unexpected EOF');
  }
}