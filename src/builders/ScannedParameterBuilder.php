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

class ScannedParameterBuilder
  extends ScannedSingleTypeBuilder<ScannedParameter> {
  private ?ScannedTypehint $typehint;
  private ?bool $variadic;
  private ?bool $byref;
  private ?string $defaultString;
  private ?VisibilityToken $visibility;

  public function setTypehint(?ScannedTypehint $typehint): this {
    $this->typehint = $typehint;
    return $this;
  }

  public function setVisibility(?VisibilityToken $visibility): this {
    $this->visibility = $visibility;
    return $this;
  }

  public function setIsVariadic(bool $variadic): this {
    $this->variadic = $variadic;
    return $this;
  }

  public function setIsPassedByReference(bool $byref): this {
    $this->byref = $byref;
    return $this;
  }

  public function setDefaultString(?string $default): this {
    $this->defaultString = $default;
    return $this;
  }

  public function build(): ScannedParameter {
    return new ScannedParameter(
      $this->name,
      $this->getDefinitionContext(),
      nullthrows($this->attributes),
      $this->docblock,
      $this->typehint,
      nullthrows($this->byref),
      nullthrows($this->variadic),
      $this->defaultString,
      $this->visibility,
    );
  }
}
