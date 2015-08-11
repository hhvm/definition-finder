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

final class ScannedMethodBuilder
  extends ScannedFunctionAbstractBuilder<ScannedMethod> {

  protected ?VisibilityToken $visibility;

  public function build(): ScannedMethod{
    return new ScannedMethod(
      nullthrows($this->position),
      nullthrows($this->namespace).$this->name,
      nullthrows($this->attributes),
      $this->docblock,
      nullthrows($this->generics),
      $this->returnType,
      nullthrows($this->parameters),
      nullthrows($this->visibility),
    );
  }
  
  public function setVisibility(VisibilityToken $visibility): this {
    $this->visibility = $visibility;
    return $this;
  }
}
