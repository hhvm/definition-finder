<?hh // strict
/*
 *  Copyright (c) 2015-present, Facebook, Inc.
 *  All rights reserved.
 *
 *  This source code is licensed under the BSD-style license found in the
 *  LICENSE file in the root directory of this source tree. An additional grant
 *  of patent rights can be found in the PATENTS file in the same directory.
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
