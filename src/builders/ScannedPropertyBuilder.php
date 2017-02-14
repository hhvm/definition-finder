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

class ScannedPropertyBuilder extends ScannedSingleTypeBuilder<ScannedProperty> {
  private ?ScannedTypehint $typehint;
  private ?VisibilityToken $visibility;
  private ?StaticityToken $staticity;

  public function setVisibility(VisibilityToken $visibility): this {
    $this->visibility = $visibility;
    return $this;
  }

  public function setTypehint(?ScannedTypehint $typehint): this {
    $this->typehint = $typehint;
    return $this;
  }

  public function setStaticity(StaticityToken $staticity): this {
    $this->staticity = $staticity;
    return $this;
  }

  public function build(): ScannedProperty {
    return new ScannedProperty(
      $this->name,
      $this->getDefinitionContext(),
      nullthrows($this->attributes),
      $this->docblock,
      $this->typehint,
      nullthrows($this->visibility),
      nullthrows($this->staticity),
    );
  }
}
