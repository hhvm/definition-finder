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

final class StaticNumericScalarExpression extends Expression {
  protected static function matchImpl(TokenQueue $tq): ?Expression {
    list($t, $ttype) = $tq->shift();
    if ($ttype === null) {
      return null;
    }

    switch ($ttype) {
      case \T_LNUMBER:
      case \T_DNUMBER:
      case \T_ONUMBER:
        return new self((int)$t);
    }
    return null;
  }
}
