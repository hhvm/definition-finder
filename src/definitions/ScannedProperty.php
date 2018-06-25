<?hh // strict
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

class ScannedProperty extends ScannedDefinition implements HasScannedVisibility {
  public function __construct(
    HHAST\EditableNode $ast,
    string $name,
    self::TContext $context,
    dict<string, vec<mixed>> $attributes,
    ?string $docComment,
    private ?ScannedTypehint $typehint,
    private VisibilityToken $visibility,
    private StaticityToken $staticity,
  ) {
    parent::__construct($ast, $name, $context, $attributes, $docComment);
  }

  <<__Override>>
  public static function getType(): ?DefinitionType {
    return null;
  }

  public function getTypehint(): ?ScannedTypehint {
    return $this->typehint;
  }

  public function isPublic(): bool {
    return $this->visibility === \T_PUBLIC;
  }

  public function isProtected(): bool {
    return $this->visibility === \T_PROTECTED;
  }

  public function isPrivate(): bool {
    return $this->visibility === \T_PRIVATE;
  }

  public function isStatic(): bool {
    return $this->staticity === StaticityToken::IS_STATIC;
  }
}
