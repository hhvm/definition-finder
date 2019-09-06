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
    private ?HHAST\ResolvedTypeKind $kind,
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

  public function getKind(): ?HHAST\ResolvedTypeKind {
    return $this->kind;
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

  public function getTypeText(
    string $relative_to_namespace = '',
    bool $strip_autoimported_namespace = false,
  ): string {
    $base = $this->isNullable() ? '?' : '';

    if ($this->shapeFields is nonnull) {
      return $base.
        self::getShapeTypeText(
          $relative_to_namespace,
          $strip_autoimported_namespace,
          $this->shapeFields,
        );
    } else if ($this->functionTypehints is nonnull) {
      return $base.
        self::getFunctionTypeText(
          $relative_to_namespace,
          $strip_autoimported_namespace,
          ...$this->functionTypehints
        );
    }

    $type_name = $this->typeName;

    invariant(
      \strpbrk($type_name, '<>') === false,
      'Typename "%s" contains <>, which should have been parsed and removed.',
      $type_name,
    );

    switch ($this->kind) {
      case HHAST\ResolvedTypeKind::CALLABLE:
      case HHAST\ResolvedTypeKind::GENERIC_PARAMETER:
        break;
      case HHAST\ResolvedTypeKind::QUALIFIED_AUTOIMPORTED_TYPE:
        if ($strip_autoimported_namespace) {
          $type_name = Str\strip_prefix($type_name, 'HH\\');
        }
        break;
      case HHAST\ResolvedTypeKind::QUALIFIED_TYPE:
        if ($relative_to_namespace !== '') {
          if (Str\starts_with($type_name, $relative_to_namespace.'\\')) {
            $type_name = Str\strip_prefix(
              $type_name,
              $relative_to_namespace.'\\',
            );
          } else {
            $type_name = '\\'.$type_name;
          }
        }
        break;
    }

    $base .= $type_name;

    $generics = $this->getGenericTypes();
    if ($generics) {
      $sub = $generics
        |> Vec\map(
          $$,
          $g ==> $g->getTypeText(
            $relative_to_namespace,
            $strip_autoimported_namespace,
          ),
        )
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
    string $relative_to_namespace,
    bool $strip_autoimported_namespace,
    vec<ScannedShapeField> $fields,
  ): string {
    return Vec\map(
      $fields,
      $field ==> Str\format(
        '%s=>%s',
        $field->getName()->getAST() |> ast_without_trivia($$)->getCode(),
        $field->getValueType()
          ->getTypeText($relative_to_namespace, $strip_autoimported_namespace),
      ),
    )
      |> Str\join($$, ',')
      |> 'shape('.$$.')';
  }

  private static function getFunctionTypeText(
    string $relative_to_namespace,
    bool $strip_autoimported_namespace,
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
            $type->getTypeText(
              $relative_to_namespace,
              $strip_autoimported_namespace,
            );
        },
      )
        |> Str\join($$, ','),
      ':'.$return_type->getTypeText(
        $relative_to_namespace,
        $strip_autoimported_namespace,
      ),
    );
  }
}
