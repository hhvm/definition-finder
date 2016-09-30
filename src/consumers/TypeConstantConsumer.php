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

/** Deals with type constants.
 *
 * abstract const type CONST_NAME [as ...];
 * const type CONST_NAME = type_name;
 *
 * expects the next token in the queue to be DefinitionType::TYPE_DEF
 */
final class TypeConstantConsumer extends Consumer {

  public function __construct(
    TokenQueue $tq,
    ?string $namespace,
    \ConstMap<string, string> $aliases,
    private AbstractnessToken $abstractness,
  ) {
    parent::__construct($tq, $namespace, $aliases);
  }

  public function getBuilder(): ScannedTypeConstantBuilder {
    $this->checkForTypeToken();
    return new ScannedTypeConstantBuilder(
      $this->consumeName(),
      $this->consumeValue(),
      $this->abstractness,
    );
  }

  private function checkForTypeToken(): void {
    $this->consumeWhitespace();
    list($next, $next_token) = $this->tq->shift();
    invariant(
      $next_token === DefinitionType::TYPE_DEF,
      'misidentified type constant.',
   );
  }

  private function consumeName(): string {
    $this->consumeWhitespace();
    list($next, $next_type) = $this->tq->shift();
    invariant(
      StringishTokens::isValid($next_type),
      'invalid type constant name %s',
      $next,
    );
    return $next;
  }

  private function consumeValue(): ?ScannedTypehint {
    $this->consumeWhitespace();

    $expectValue = false;

    list($next, $next_type) = $this->tq->peek();
    if($next === RelationshipToken::SUBTYPE) {
      invariant(
        $this->abstractness === AbstractnessToken::IS_ABSTRACT,
        'concrete type constant may not have a type constraint',
      );
      $this->tq->shift();
      $this->consumeWhitespace();
      return (new TypehintConsumer(
        $this->tq,
        $this->namespace,
        $this->aliases,
      ))->getTypehint();
    }

    if($next === '=') {
      invariant(
        $this->abstractness === AbstractnessToken::NOT_ABSTRACT,
        'abstract type constants may not have concrete values',
      );
      $this->tq->shift();
      $this->consumeWhitespace();
      return (new TypehintConsumer(
        $this->tq,
        $this->namespace,
        $this->aliases,
      ))->getTypehint();
    }

      $this->consumeStatement();
      return null;
  }
}
