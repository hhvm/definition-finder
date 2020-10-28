/*
 *  Copyright (c) 2015-present, Facebook, Inc.
 *  All rights reserved.
 *
 *  This source code is licensed under the MIT license found in the
 *  LICENSE file in the root directory of this source tree.
 *
 */

namespace Facebook\DefinitionFinder;

use namespace HH\Lib\{Str, Vec};
use type Facebook\HHAST\{DotDotDotToken, InoutToken, Node, ResolvedTypeKind};

/** Represents a parameter, property, constant, or return type hint */
final class ScannedTypehint {

  const UNION = '[union]';
  const INTERSECTION = '[intersection]';

  public function __construct(
    private Node $ast,
    private ?ResolvedTypeKind $kind,
    private string $typeName,
    private vec<ScannedTypehint> $generics,
    private bool $nullable,
    private ?vec<ScannedShapeField> $shapeFields,
    private ?(
      vec<(?InoutToken, ScannedTypehint, ?DotDotDotToken)>,
      ScannedTypehint,
    ) $functionTypehints,
  ) {
  }

  public function getAST(): Node {
    return $this->ast;
  }

  public function getKind(): ?ResolvedTypeKind {
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
    invariant($fields !== null, 'Called getShapeFields, but not a shape');
    return $fields;
  }

  public function getFunctionTypehints(
  ): ?(vec<(?InoutToken, ScannedTypehint, ?DotDotDotToken)>, ScannedTypehint) {
    return $this->functionTypehints;
  }

  /**
   * Returns the full typehint, including nested generic types/shape fields/etc.
   * The specified options are applied recursively to all the nested types.
   *
   * @param string fully qualified namespace from which the type is referenced
   * @param int bitwise OR of TypeTextOptions values
   */
  public function getTypeText(
    string $relative_to_namespace = '',
    int /* TypeTextOptions */ $options = 0,
  ): string {
    $base = $this->isNullable() ? '?' : '';

    if ($this->shapeFields is nonnull) {
      return $base.
        self::getShapeTypeText(
          $relative_to_namespace,
          $options,
          $this->shapeFields,
        );
    } else if ($this->functionTypehints is nonnull) {
      return $base.
        self::getFunctionTypeText(
          $relative_to_namespace,
          $options,
          ...$this->functionTypehints
        );
    }

    $type_name = $this->typeName;

    invariant(
      \strpbrk($type_name, '<>') === false,
      'Typename "%s" contains <>, which should have been parsed and removed.',
      $type_name,
    );

    if ($this->kind is nonnull) {
      switch ($this->kind) {
        case ResolvedTypeKind::CALLABLE:
        case ResolvedTypeKind::GENERIC_PARAMETER:
          break;
        case ResolvedTypeKind::QUALIFIED_AUTOIMPORTED_TYPE:
          if ($options & TypeTextOptions::STRIP_AUTOIMPORTED_NAMESPACE) {
            $type_name = Str\strip_prefix($type_name, 'HH\\');
          }
          break;
        case ResolvedTypeKind::QUALIFIED_TYPE:
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
    }

    $sub = null;
    $generics = $this->getGenericTypes();
    if ($generics) {
      $sub = Vec\map(
        $generics,
        $g ==> $g->getTypeText($relative_to_namespace, $options),
      );
    }

    if ($type_name === 'tuple') {
      $sub = Str\join($sub as nonnull, ', ');
      $base .= '('.$sub.')';
    } else if ($type_name === self::UNION) {
      $sub = Str\join($sub as nonnull, ' | ');
      $base .= '('.$sub.')';
    } else if ($type_name === self::INTERSECTION) {
      $sub = Str\join($sub as nonnull, ' & ');
      $base .= '('.$sub.')';
    } else {
      $base .= $type_name;
      if ($sub is nonnull) {
        $sub = Str\join($sub, ', ');
        $base .= '<'.$sub.'>';
      }
    }
    return $base;
  }

  private static function getShapeTypeText(
    string $relative_to_namespace,
    int $options,
    vec<ScannedShapeField> $fields,
  ): string {
    return Vec\map(
      $fields,
      $field ==> Str\format(
        '%s => %s',
        $field->getName()->getAST() |> ast_without_trivia($$)->getCode(),
        $field->getValueType()->getTypeText($relative_to_namespace, $options),
      ),
    )
      |> Str\join($$, ', ')
      |> 'shape('.$$.')';
  }

  private static function getFunctionTypeText(
    string $relative_to_namespace,
    int $options,
    vec<(?InoutToken, ScannedTypehint, ?DotDotDotToken)> $parameter_types,
    ScannedTypehint $return_type,
  ): string {
    return Str\format(
      '(function(%s)%s)',
      Vec\map(
        $parameter_types,
        $type_info ==> {
          list($inout, $type, $ellipsis) = $type_info;
          return ($inout is nonnull ? $inout->getText().' ' : '').
            $type->getTypeText($relative_to_namespace, $options).
            ($ellipsis is nonnull ? ' '.$ellipsis->getText() : '');
        },
      )
        |> Str\join($$, ', '),
      ': '.$return_type->getTypeText($relative_to_namespace, $options),
    );
  }
}
