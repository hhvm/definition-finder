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

class ScannedParameter extends ScannedDefinition {
  public function __construct(
    string $name,
    self::TContext $context,
    dict<string, vec<mixed>> $attributes,
    ?string $docComment,
    private ?ScannedTypehint $type,
    private bool $byref,
    private bool $inout,
    private bool $variadic,
    private ?string $defaultString,
    private ?VisibilityToken $visibility,
  ) {
    parent::__construct($name, $context, $attributes, $docComment);
  }

  <<__Override>>
  public static function getType(): ?DefinitionType {
    return null;
  }

  public function getTypehint(): ?ScannedTypehint {
    return $this->type;
  }

  public function isPassedByReference(): bool {
    return $this->byref;
  }

  public function isInOut(): bool {
    return $this->inout;
  }

  public function isVariadic(): bool {
    return $this->variadic;
  }

  public function isOptional(): bool {
    return $this->defaultString !== null;
  }

  public function getDefaultString(): string {
    invariant(
      $this->isOptional(),
      'trying to retrieve default for non-optional param',
    );
    return nullthrows($this->defaultString);
  }

  public function __isPromoted(): bool {
    return $this->visibility !== null;
  }

  public function __getVisibility(): VisibilityToken {
    $v = $this->visibility;
    invariant(
      $v !== null,
      'Tried to get visibility for a non-promoted parameter',
    );
    return $v;
  }
}
