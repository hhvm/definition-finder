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

abstract class ScannedFunctionAbstract extends ScannedBase {
  public function __construct(
    SourcePosition $position,
    string $name,
    Map<string, Vector<mixed>> $attributes,
    ?string $docComment,
    private \ConstVector<ScannedGeneric> $generics,
    private ?ScannedTypehint $returnType,
    private \ConstVector<ScannedParameter> $parameters,
  ) {
    parent::__construct($position, $name, $attributes, $docComment);
  }

  public static function getType(): DefinitionType {
    return DefinitionType::FUNCTION_DEF;
  }

  public function getGenerics(): \ConstVector<ScannedGeneric> {
    return $this->generics;
  }

  public function getReturnType(): ?ScannedTypehint {
    return $this->returnType;
  }

  public function getParameters(): \ConstVector<ScannedParameter> {
    return $this->parameters;
  }
}
