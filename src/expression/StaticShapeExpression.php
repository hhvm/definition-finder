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

final class StaticShapeExpression extends StaticArrayExpression
implements StaticDictLikeArrayExpression {
  public static function convertDict(dict<arraykey, mixed> $values): mixed {
    return /* HH_FIXME[4107] */ /* HH_FIXME[2049] */darray($values);
  }

  <<__Override>>
  protected static function consumeStart(TokenQueue $tq): ?string {
    list($t, $ttype) = $tq->shift();
    if ($ttype !== \Facebook\DefinitionFinder\T_SHAPE) {
      return null;
    }
    list($t, $ttype) = $tq->shift();
    if ($t !== '(') {
      return null;
    }

    return ')';
  }
}
