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

use namespace HH\Lib\Str;

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

  private function consumeName(): string {
    list($t, $tt) = $this->tq->shift();
    invariant(
      StringishTokens::isValid($tt) ||
      (Str\starts_with($t, '__') && Str\ends_with($t, '__')),
      'Expected a constant name at line %d, got %s',
      $this->tq->getLine(),
      $t,
    );
    return $t;
  }

  private function consumeTypehintAndName(): (ScannedTypehint, string) {
    $th = (new TypehintConsumer($this->tq, $this->context))->getTypehint();
    $this->consumeWhitespace();
    return tuple($th, $this->consumeName());
  }

  public function getBuilder(): ScannedConstantBuilder {
    $this->consumeWhitespace();
    $saved_state = $this->tq->getState();
    try {
      list($type, $name) = $this->consumeTypehintAndName();
    } catch (\Exception $_) {
      $this->tq->restoreState($saved_state);
      $type = null;
      $name = $this->consumeName();
    }
    $this->consumeWhitespace();
    $value = null;

    $next = $this->tq->shiftNG();
    list($t, $_) = $next->asLegacyToken();
    if ($t === '=') {
      $this->consumeWhitespace();
      while ($this->tq->haveTokens()) {
        list($nnv, $nnt) = $this->tq->peek();
        if ($nnv === ';') {
          break;
        }
        $this->tq->shift();
        $value .= $nnv;
      }
      $next = $this->tq->shiftNG();
      list($t, $_) = $next->asLegacyToken();
    }
    invariant($t === ';', 'expected semicolon, got %s', $t);

    $this->tq->unshiftNG($next);
    $builder = new ScannedConstantBuilder(
      $this->normalizeName(nullthrows($name)),
      $this->getBuilderContext(),
      $value,
      $type,
      $this->abstractness,
    );
    $this->consumeStatement();
    return $builder;
  }
}
