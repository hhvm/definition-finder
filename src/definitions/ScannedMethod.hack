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

final class ScannedMethod extends ScannedFunctionish
  implements HasScannedVisibility {
  public function __construct(
    HHAST\Node $ast,
    string $name,
    self::TContext $context,
    dict<string, vec<mixed>> $attributes,
    ?string $docComment,
    vec<ScannedGeneric> $generics,
    ?ScannedTypehint $returnType,
    vec<ScannedParameter> $parameters,
    private VisibilityToken $visibility,
    private StaticityToken $staticity = StaticityToken::NOT_STATIC,
    private AbstractnessToken $abstractness = AbstractnessToken::NOT_ABSTRACT,
    private FinalityToken $finality = FinalityToken::NOT_FINAL,
  ) {
    parent::__construct(
      $ast,
      $name,
      $context,
      $attributes,
      $docComment,
      $generics,
      $returnType,
      $parameters,
    );
  }

  public function isPublic(): bool {
    return $this->visibility === VisibilityToken::T_PUBLIC;
  }

  public function isProtected(): bool {
    return $this->visibility === VisibilityToken::T_PROTECTED;
  }

  public function isPrivate(): bool {
    return $this->visibility === VisibilityToken::T_PRIVATE;
  }

  public function isStatic(): bool {
    return $this->staticity === StaticityToken::IS_STATIC;
  }

  public function isAbstract(): bool {
    return $this->abstractness === AbstractnessToken::IS_ABSTRACT;
  }

  public function isFinal(): bool {
    return $this->finality === FinalityToken::IS_FINAL;
  }
}
