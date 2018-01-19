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

abstract class ScannedFunctionAbstract extends ScannedBase
  implements HasScannedGenerics {
  public function __construct(
    string $name,
    self::TContext $context,
    dict<string, vec<mixed>> $attributes,
    ?string $docComment,
    private vec<ScannedGeneric> $generics,
    private ?ScannedTypehint $returnType,
    private vec<ScannedParameter> $parameters,
  ) {
    parent::__construct($name, $context, $attributes, $docComment);
  }

  public static function getType(): DefinitionType {
    return DefinitionType::FUNCTION_DEF;
  }

  public function getGenericTypes(): vec<ScannedGeneric> {
    return $this->generics;
  }

  public function getReturnType(): ?ScannedTypehint {
    return $this->returnType;
  }

  public function getParameters(): vec<ScannedParameter> {
    return $this->parameters;
  }
}
