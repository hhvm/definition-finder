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

final class StaticPHPArrayExpression extends StaticArrayExpression
implements StaticDictLikeArrayExpression, StaticVecLikeArrayExpression {
  public static function convertDict(dict<arraykey, mixed> $values): mixed {
    return /* HH_IGNORE_ERROR[4007] */ (array) $values;
  }

  public static function convertVec(vec<mixed> $values): mixed {
    return /* HH_IGNORE_ERROR[4007] */ (array) $values;
  }

  <<__Override>>
  protected static function consumeStart(TokenQueue $tq): ?string {
    list($t, $ttype) = $tq->shift();
    if ($t === '[') {
      return ']';
    }

    if ($ttype === \T_ARRAY) {
      list($t, $ttype) = $tq->shift();
      if ($t !== '(') {
        return null;
      }
      return ')';
    }
    return null;
  }
}
