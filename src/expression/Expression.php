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

abstract class Expression<TValue> {
  final protected function __construct(private TValue $value) {
  }

  final public static function match(TokenQueue $tq): ?Expression<TValue> {
    $state = $tq->getState();
    $ret = static::matchImpl($tq);
    if ($ret) {
      return $ret;
    }
    $tq->restoreState($state);
    return null;
  }

  abstract protected static function matchImpl(TokenQueue $tq): ?Expression<TValue>;

  protected static function consumeWhitespace(TokenQueue $tq): void {
    while ($tq->haveTokens()) {
      list($_, $ttype) = $tq->peek();
      if ($ttype !== \T_WHITESPACE) {
        return;
      }
      $tq->shift();
    }
  }

  final public function getValue(): TValue {
    return $this->value;
  }
}
