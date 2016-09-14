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

class ScannedProperty
  extends ScannedBase
  implements HasScannedVisibility {
  public function __construct(
    SourcePosition $position,
    string $name,
    Map<string, Vector<mixed>> $attributes,
    ?string $docComment,
    private ?ScannedTypehint $typehint,
    private VisibilityToken $visibility,
    private StaticityToken $staticity = StaticityToken::NOT_STATIC,
  ) {
    parent::__construct(
      $position,
      $name,
      $attributes,
      $docComment,
    );
  }

  public static function getType(): ?DefinitionType {
    return null;
  }

  public function getTypehint(): ?ScannedTypehint {
    return $this->typehint;
  }

  public function isPublic(): bool {
    return $this->visibility === T_PUBLIC;
  }

  public function isProtected(): bool {
    return $this->visibility === T_PROTECTED;
  }

  public function isPrivate(): bool {
    return $this->visibility === T_PRIVATE;
  }

  public function isStatic(): bool {
    return $this->staticity === StaticityToken::IS_STATIC;
  }
}
