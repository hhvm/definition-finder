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
use namespace HH\Lib\{Str, Vec};

/** Represents a parameter, property, constant, or return type hint */
final class ScannedTypehint {
  public function __construct(
    private HHAST\Node $ast,
    private string $typeName,
    private vec<ScannedTypehint> $generics,
    private bool $nullable,
    private ?vec<ScannedShapeField> $shapeFields,
    private ?(vec<(?HHAST\InoutToken, ScannedTypehint)>, ScannedTypehint)
      $functionTypehints,
  ) {
  }

  public function getAST(): HHAST\Node {
    return $this->ast;
  }

  public function getTypeName(): string {
    return $this->typeName;
  }

  public function isGeneric(): bool {
    return (bool)$this->generics;
  }

  public function isNullable(): bool {
    return $this->nullable;
  }

  public function getGenericTypes(): vec<ScannedTypehint> {
    return $this->generics;
  }

  public function isShape(): bool {
    return $this->shapeFields !== null;
  }

  public function getShapeFields(): vec<ScannedShapeField> {
    $fields = $this->shapeFields;
    invariant($fields !== null, "Called getShapeFields, but not a shape");
    return $fields;
  }

  public function getFunctionTypehints(
  ): ?(vec<(?HHAST\InoutToken, ScannedTypehint)>, ScannedTypehint) {
    return $this->functionTypehints;
  }

  public function getTypeText(): string {
    $base = $this->isNullable() ? '?' : '';

    if ($this->shapeFields is nonnull) {
      return $base.self::getShapeTypeText($this->shapeFields);
    } else if ($this->functionTypehints is nonnull) {
      return $base.self::getFunctionTypeText(...$this->functionTypehints);
    }

    $base .= $this->typeName;

    invariant(
      \strpbrk($base, '<>') === false,
      'Typename "%s" contains <>, which should have been parsed and removed.',
      $base,
    );

    $generics = $this->getGenericTypes();
    if ($generics) {
      $sub = $generics
        |> Vec\map($$, $g ==> $g->getTypeText())
        |> Str\join($$, ',');
      if ($base === 'tuple') {
        return '('.$sub.')';
      } else if ($base === '?tuple') {
        return '?('.$sub.')';
      } else {
        return $base.'<'.$sub.'>';
      }
    }
    return $base;
  }

  private static function getShapeTypeText(
    vec<ScannedShapeField> $fields,
  ): string {
    return Vec\map(
      $fields,
      $field ==> Str\format(
        '%s=>%s',
        $field->getName()->getAST() |> ast_without_trivia($$)->getCode(),
        $field->getValueType()->getTypeText(),
      ),
    )
      |> Str\join($$, ',')
      |> 'shape('.$$.')';
  }

  private static function getFunctionTypeText(
    vec<(?HHAST\InoutToken, ScannedTypehint)> $parameter_types,
    ScannedTypehint $return_type,
  ): string {
    return Str\format(
      '(function(%s)%s)',
      Vec\map(
        $parameter_types,
        $inout_and_type ==> {
          list($inout, $type) = $inout_and_type;
          return ($inout is nonnull ? $inout->getText().' ' : '').
            $type->getTypeText();
        },
      )
        |> Str\join($$, ','),
      $return_type is nonnull ? ':'.$return_type->getTypeText() : '',
    );
  }
}
