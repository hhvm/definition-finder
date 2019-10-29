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

final class ScannedParameter extends ScannedDefinition {
  public function __construct(
    HHAST\Node $ast,
    string $name,
    self::TContext $context,
    dict<string, vec<mixed>> $attributes,
    ?string $docComment,
    private ?ScannedTypehint $type,
    private bool $inout,
    private bool $variadic,
    private ?ScannedExpression $default,
    private ?VisibilityToken $visibility,
  ) {
    parent::__construct($ast, $name, $context, $attributes, $docComment);
  }

  <<__Override>>
  public static function getType(): ?DefinitionType {
    return null;
  }

  public function getTypehint(): ?ScannedTypehint {
    return $this->type;
  }

  public function isInOut(): bool {
    return $this->inout;
  }

  public function isVariadic(): bool {
    return $this->variadic;
  }

  public function isOptional(): bool {
    return $this->hasDefault();
  }

  public function hasDefault(): bool {
    return $this->default !== null;
  }

  public function getDefault(): ?ScannedExpression {
    return $this->default;
  }

  public function __isPromoted(): bool {
    return $this->visibility !== null;
  }

  public function __getVisibility(): VisibilityToken {
    $v = $this->visibility;
    invariant(
      $v !== null,
      'Tried to get visibility for a non-promoted parameter',
    );
    return $v;
  }
}
