<?hh // strict
/*
 *  Copyright (c) 2015-present, Facebook, Inc.
 *  All rights reserved.
 *
 *  This source code is licensed under the MIT license found in the
 *  LICENSE file in the root directory of this source tree.
 *
 */

namespace Facebook\DefinitionFinder;

final class NGToken {
  const type TPosition = shape(
    'firstLine' => int,
    'lastLine' => ?int,
    'firstChar' => ?int,
    'lastChar' => ?int,
  );

  public function __construct(
    private string $value,
    private ?int $type,
    private self::TPosition $position,
  ) {
  }

  public function getValue(): string {
    return $this->value;
  }

  public function getType(): ?int {
    return $this->type;
  }

  public function getPosition(): self::TPosition {
    return $this->position;
  }

  public function asLegacyToken(): (TokenValue, TokenType) {
    return tuple($this->getValue(), $this->getType());
  }
}
