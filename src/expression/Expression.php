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

abstract class Expression {
  final protected function __construct(private mixed $value) {}

  final public static function match(TokenQueue $tq): ?Expression {
    $state = $tq->getState();
    $ret = static::matchImpl($tq);
    if ($ret) {
      return $ret;
    }
    $tq->restoreState($state);
    return null;
  }

  abstract protected static function matchImpl(TokenQueue $tq): ?Expression;

  protected static function consumeWhitespace(TokenQueue $tq): void {
    while ($tq->haveTokens()) {
      list($_, $ttype) = $tq->peek();
      if ($ttype !== T_WHITESPACE) {
        return;
      }
      $tq->shift();
    }
  }

  final public function getValue(): mixed {
    return $this->value;
  }
}