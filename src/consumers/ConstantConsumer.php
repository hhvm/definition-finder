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

namespace Facebook\DefinitionFinder;

/** Deals with new-style constants.
 *
 * const CONST_NAME =
 * const type_name CONST_NAME =
 *
 * See DefineConsumer for old-style constants.
 */
final class ConstantConsumer extends Consumer {
  public function __construct(
    TokenQueue $tq,
    self::TContext $context,
    private AbstractnessToken $abstractness,
  ) {
    parent::__construct($tq, $context);
  }

  public function getBuilder(): ScannedConstantBuilder {
    $name = null;
    $value = null;
    $builder = null;
    $typehint = null;

    while ($this->tq->haveTokens()) {
      $next_token = $this->tq->shiftNG();
      list($next, $next_type) = $next_token->asLegacyToken();
      if ($next_type === T_WHITESPACE) {
        continue;
      }
      if (StringishTokens::isValid($next_type)) {
        $this->consumeWhitespace();
        list($_, $nnt) = $this->tq->peek();
        if ($nnt === T_STRING) {
          $this->tq->unshiftNG($next_token);
          $typehint =
            (new TypehintConsumer($this->tq, $this->context))->getTypehint();
          continue;
        } else {
          $name = $next;
          continue;
        }
      }
      if ($next === '=') {
        $this->consumeWhitespace();
        while ($this->tq->haveTokens()) {
          list($nnv, $nnt) = $this->tq->peek();
          if ($nnv === ';') {
            break;
          }
          $this->tq->shift();
          $value .= $nnv;
        }
      }

      if ($next === ';') {
        $this->tq->unshiftNG($next_token);
        $builder = new ScannedConstantBuilder(
          $this->normalizeName(nullthrows($name)),
          $this->getBuilderContext(),
          $value,
          $typehint,
          $this->abstractness,
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
