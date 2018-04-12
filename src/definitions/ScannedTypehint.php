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

use namespace HH\Lib\{C, Str, Vec};

/** Represents a parameter, property, constant, or return type hint */
class ScannedTypehint {
  public function __construct(
    private string $typeName,
    private string $typeTextBase,
    private vec<ScannedTypehint> $generics,
    private bool $nullable,
  ) {
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

  /** Implementation detail.
   *
   * The value here is undefined, but required to manually create/merge
   * instances of ScannedTypehint.
   *
   * Example usage:
   *
   * ```
   * function merge_typehints(
   *   ScannedTypehint $a,
   *   ScannedTypehint $b,
   * ): ScannedTypehint {
   *   if (Str\starts_with($a, "HH\")) {
   *     $name = $a->getTypeName();
   *     $base = $a->getTypeTextBase();
   *   } else {
   *     $name = $b->getTypeName();
   *     $base = $b->getTypeTextBase();
   *   }
   *   return new ScannedTypehint(
   *     $name,
   *     $base,
   *     merge_generics($a, $b),
   *     $a->isNullable() || $b->isNullable(),
   *   );
   * }
   * ```
   */
  public function getTypeTextBase(): string {
    return $this->typeTextBase;
  }

  public function getTypeText(): string {
    $base = $this->isNullable() ? '?' : '';
    $base .= $this->typeTextBase;

    if (\strpbrk($base, '<>')) {
      invariant(
        C\is_empty($this->getGenericTypes()),
        'Typename "%s" contains <> and has generics',
        $base,
      );
      // Invalid in most cases, but valid for eg `(function():vec<string>)`
      return $base;
    }
    $generics = $this->getGenericTypes();
    if ($generics) {
      $sub = $generics
        |> Vec\map($$, $g ==> $g->getTypeText())
        |> Str\join($$, ',');
      if ($base === 'tuple') {
        return '('.$sub.')';
      } else {
        return $base.'<'.$sub.'>';
      }
    }
    return $base;
  }
}
