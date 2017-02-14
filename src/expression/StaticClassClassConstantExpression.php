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

final class StaticClassClassConstantExpression extends Expression {
  protected static function matchImpl(TokenQueue $tq): ?Expression {
    $class = NamespaceStringExpression::match($tq);
    if ($class === null) {
      return null;
    }
    list ($t, $ttype) = $tq->shift();
    if ($ttype !== T_DOUBLE_COLON) {
      return null;
    }
    list ($t, $ttype) = $tq->shift();
    if ($ttype !== T_CLASS) {
      return null;
    }
    return new self($class->getValue());
  }
}