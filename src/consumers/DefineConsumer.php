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

/** Deals with old-style constants.
 *
 * define('CONST_NAME', ...)
 * define("CONST_NAME", ...)
 * define(CONST_NAME, ...)
 *
 * See ConstantConsumer for new-style constants.
 */
final class DefineConsumer extends Consumer {
  public function getBuilder(): ?ScannedConstantBuilder {
    $tq = $this->tq;
    $value = null;

    $this->consumeWhitespace();
    list($next, $_) = $tq->shift();
    invariant(
      $next === '(',
      'Expected define to be followed by a paren',
    );
    $this->consumeWhitespace();
    list ($next, $next_type) = $tq->shift();
    if (!(
      $next_type === T_CONSTANT_ENCAPSED_STRING
      || $next_type === T_STRING
    )) {
      // Not considering define($foo, ...) to be a constant D:
      $this->consumeStatement();
      return null;
    }

    $name = $next;
    if ($next_type !== T_STRING) {
      // 'CONST_NAME' or "CONST_NAME"
      invariant(
        $name[0] === $name[strlen($name) - 1],
        'Mismatched quotes',
      );
      $name = substr($name, 1, strlen($name) - 2);
    }
    $this->consumeWhitespace();
    list($next, $_) = $tq->shift();
    invariant(
      $next === ',',
      'Expected first define argument to be followed by a comma',
    );
    $this->consumeWhitespace();
    while ($this->tq->haveTokens()) {
      list($nnv, $nnt) = $this->tq->peek();
      if ($nnv === ')') {
        break;
      }
      $this->tq->shift();
      $value .= $nnv;
    }
    $this->consumeStatement();
    return new ScannedConstantBuilder(
      $name,
      $this->getBuilderContext(),
      $value,
      /* typehint = */ null,
      AbstractnessToken::NOT_ABSTRACT,
    );
  }
}
