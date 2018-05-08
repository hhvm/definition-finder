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

final class NamespaceStringExpression extends Expression<mixed> {
  protected static function matchImpl(TokenQueue $tq): ?this {
    list($t, $ttype) = $tq->shift();
    if ($ttype === \T_NS_SEPARATOR) {
      list($t, $ttype) = $tq->shift();
    }
    if ($ttype !== \T_STRING) {
      return null;
    }
    $value = $t;

    while ($tq->haveTokens()) {
      list($t, $ttype) = $tq->peek();
      if ($ttype !== \T_NS_SEPARATOR) {
        return new self($value);
      }
      $tq->shift();
      list($t, $ttype) = $tq->shift();
      if ($ttype !== \T_STRING) {
        return null;
      }
      $value .= "\\".$t;
    }
    invariant_violation('Unexpected EOF');
  }
}
