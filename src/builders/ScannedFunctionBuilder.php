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

final class ScannedFunctionBuilder
  extends ScannedSingleTypeBuilder<ScannedFunction> {

  private ?bool $byRefReturn;
  private ?\ConstVector<ScannedGeneric> $generics = null;
  private ?ScannedTypehint $returnType;
  private ?\ConstVector<ScannedParameter> $parameters = null;

  public function setByRefReturn(bool $v): this {
    $this->byRefReturn = $v;
    return $this;
  }

  public function setGenerics(\ConstVector<ScannedGeneric> $generics): this {
    $this->generics = $generics;
    return $this;
  }

  public function setReturnType(?ScannedTypehint $type): this {
    $this->returnType = $type;
    return $this;
  }

  public function setParameters(
    \ConstVector<ScannedParameter> $parameters,
  ): this {
    $this->parameters = $parameters;
    return $this;
  }

  public function build(): ScannedFunction {
    return new ScannedFunction(
      nullthrows($this->position),
      nullthrows($this->namespace).$this->name,
      nullthrows($this->attributes),
      $this->docblock,
      nullthrows($this->generics),
      $this->returnType,
      nullthrows($this->parameters),
    );
  }
}
