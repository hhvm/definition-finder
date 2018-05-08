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

final class StaticClassClassConstantExpression extends Expression<mixed> {
  protected static function matchImpl(TokenQueue $tq): ?this {
    $class = NamespaceStringExpression::match($tq);
    if ($class === null) {
      return null;
    }
    list($t, $ttype) = $tq->shift();
    if ($ttype !== \T_DOUBLE_COLON) {
      return null;
    }
    list($t, $ttype) = $tq->shift();
    if ($ttype !== \T_CLASS) {
      return null;
    }
    return new self($class->getValue());
  }
}
