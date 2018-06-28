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

final class ScannedShapeField extends ScannedDefinition {
  public function __construct(
    HHAST\EditableNode $ast,
    string $name,
    self::TContext $context,
    dict<string, vec<mixed>> $attributes,
    ?string $docComment,
    private OptionalityToken $optional,
    private ScannedTypehint $type,
  ) {
    parent::__construct($ast, $name, $context, $attributes, $docComment);
  }

  <<__Override>>
  public static function getType(): DefinitionType {
    return DefinitionType::SHAPE_FIELD_DEF;
  }

  public function isOptional(): bool {
    return $this->optional === OptionalityToken::IS_OPTIONAL;
  }

  public function getValueType(): ScannedTypehint {
    return $this->type;
  }
}
