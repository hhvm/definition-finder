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

/** Represents a parameter, property, constant, or return type hint */
class ScannedTypehint {
  public function __construct(
    private string $typeName,
    private \ConstVector<ScannedTypehint> $generics,
  ) {
  }

  public function getTypeName(): string {
    return $this->typeName;
  }

  public function isGeneric(): bool {
    return (bool) $this->generics;
  }

  public function getGenericTypes(): \ConstVector<ScannedTypehint> {
    return $this->generics;
  }

  public function getTypeText(): string {
    $base = $this->getTypeName();
    invariant(strpbrk($base, '<>') === false, 'generics in type text');
    $generics = $this->getGenericTypes();
    if ($generics) {
      $sub = implode(',',$generics->map($g ==> $g->getTypeText()));
      if ($base === 'tuple') {
        return '('.$sub.')';
      } else {
        return $base.'<'.$sub.'>';
      }
    }
    return $base;
  }
}