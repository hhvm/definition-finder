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
use namespace Facebook\TypeAssert;

abstract class StaticArrayExpression extends Expression<mixed> {
  /**
   * @return null if did not match
   * @return string for end token if matched
   */
  abstract protected static function consumeStart(TokenQueue $tq): ?string;

  final protected static function matchImpl(TokenQueue $tq): ?this {
    $end = static::consumeStart($tq);
    if ($end === null) {
      return null;
    }

    self::consumeWhitespace($tq);

    $class = static::class;
    $values = null;
    $converted = null;
    if (\is_a($class, StaticDictLikeArrayExpression::class, /* string = */ true)) {
      $class = TypeAssert\classname_of(StaticDictLikeArrayExpression::class, $class);
      list($t, $_) = $tq->peek();
      $values = StaticArrayPairListExpression::match($tq)?->getValue();
      $converted = $class::convertDict($values ?? dict[]);
    }

    if (
      $values === null &&
      \is_a($class, StaticVecLikeArrayExpression::class, /* string = */ true)
    ) {
      $class = TypeAssert\classname_of(StaticVecLikeArrayExpression::class, $class);
      $values = StaticArrayListExpression::match($tq)?->getValue();
      $converted = $class::convertVec($values ?? vec[]);
    }

    list($t, $_) = $tq->shift();
    if ($t !== $end) {
      return null;
    }
    self::consumeWhitespace($tq);
    return new static($converted);
  }
}
