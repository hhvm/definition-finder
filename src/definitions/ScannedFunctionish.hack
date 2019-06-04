/*
 *  Copyright (c) 2015-present, Facebook, Inc.
 *  All rights reserved.
 *
 *  This source code is licensed under the MIT license found in the
 *  LICENSE file in the root directory of this source tree.
 *
 */

namespace Facebook\DefinitionFinder;

use namespace Facebook\HHAST;

abstract class ScannedFunctionish extends ScannedDefinition
  implements HasScannedGenerics {
  public function __construct(
    HHAST\Node $ast,
    string $name,
    self::TContext $context,
    dict<string, vec<mixed>> $attributes,
    ?string $docComment,
    private vec<ScannedGeneric> $generics,
    private ?ScannedTypehint $returnType,
    private vec<ScannedParameter> $parameters,
  ) {
    parent::__construct($ast, $name, $context, $attributes, $docComment);
  }

  <<__Override>>
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
