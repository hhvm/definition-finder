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

/** Deals with new-style constants.
 *
 * const CONST_NAME =
 * const type_name CONST_NAME =
 *
 * See DefineConsumer for old-style constants.
 */
final class ConstantConsumer extends Consumer {
  public function getBuilder(): ScannedConstantBuilder {
    $name = null;
    $value = null;
    $builder = null;
    $typehint = null;

    while ($this->tq->haveTokens()) {
      list ($next, $next_type) = $this->tq->shift();
      if ($next_type === T_WHITESPACE) {
        continue;
      }
      if (StringishTokens::isValid($next_type)) {
        $this->consumeWhitespace();
        list($_, $nnt) = $this->tq->peek();
        if ($nnt === T_STRING) {
          $this->tq->unshift($next, $next_type);
          $typehint = (new TypehintConsumer($this->tq, $this->aliases))
            ->getTypehint();
          continue;
        } else {
          $name = $next;
          continue;
        }
      }
      if ($next === '=') {
        $this->consumeWhitespace();
        while ($this->tq->haveTokens()) {
          list($nnv, $nnt) = $this->tq->shift();
          if ($nnv === ';') {
            $this->tq->unshift($nnv, $nnt);
            break;
          }
          $value .= $nnv;
        }
        $builder = new ScannedConstantBuilder(
          nullthrows($name),
          $value,
          $typehint,
        );
        $name = null;
        $value = null;
        $typehint = null;
        break;
      }
    }
    invariant($builder, 'invalid constant definition');
    $this->consumeStatement();
    return $builder;
  }
}
