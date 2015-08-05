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

namespace Facebook\DefinitionFinder;

abstract class Consumer {
  public function __construct(
    protected TokenQueue $tq,
  ) {
  }

  protected function consumeWhitespace(): void {
    list($t, $ttype) = $this->tq->shift();
    if ($ttype === T_WHITESPACE) {
      return;
    }
    $this->tq->unshift($t, $ttype);
  }

  protected function consumeStatement(): void {
    while ($this->tq->haveTokens()) {
      list($tv, $ttype) = $this->tq->shift();
      if ($tv === ';') {
        return;
      }
      if ($tv === '{') {
        $this->consumeBlock();
        return;
      }
    }
  }

  protected function skipToBlock(): void {
    while ($this->tq->haveTokens()) {
      list($next, $next_type) = $this->tq->shift();
      if ($next === '{' || $next_type === T_CURLY_OPEN) {
        return;
      }
    }
    invariant_violation('no block');
  }

  protected function consumeBlock(): void {
    $nesting = 1;
    while ($this->tq->haveTokens()) {
      list($next, $next_type) = $this->tq->shift();
      if ($next === '{' || $next_type === T_CURLY_OPEN) {
        ++$nesting;
      } else if ($next === '}') { // no such thing as T_CURLY_CLOSE
        --$nesting;
        if ($nesting === 0) {
          return;
        }
      }
    }
  }
}
