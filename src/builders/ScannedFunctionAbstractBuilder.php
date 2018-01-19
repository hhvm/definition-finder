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

use namespace HH\Lib\Vec;

abstract class ScannedFunctionAbstractBuilder<T as ScannedFunctionAbstract>
  extends ScannedSingleTypeBuilder<T> {

  protected ?bool $byRefReturn;
  protected ?vec<ScannedGeneric> $generics = null;
  protected ?ScannedTypehint $returnType;
  protected vec<ScannedParameterBuilder> $parameters = vec[];

  public function setByRefReturn(bool $v): this {
    $this->byRefReturn = $v;
    return $this;
  }

  public function setGenerics(vec<ScannedGeneric> $generics): this {
    $this->generics = $generics;
    return $this;
  }

  public function setReturnType(?ScannedTypehint $type): this {
    $this->returnType = $type;
    return $this;
  }

  public function addParameter(ScannedParameterBuilder $parameter): void {
    $this->parameters[] = $parameter;
  }

  protected function buildParameters(): vec<ScannedParameter> {
    return Vec\map($this->parameters, $builder ==> $builder->build());
  }
}
