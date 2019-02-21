/*
 *  Copyright (c) 2015-present, Facebook, Inc.
 *  All rights reserved.
 *
 *  This source code is licensed under the MIT license found in the
 *  LICENSE file in the root directory of this source tree.
 *
 */

namespace Facebook\DefinitionFinder;

final class ScannedGeneric {
  const type TConstraint =
    shape('type' => ScannedTypehint, 'relationship' => RelationshipToken);

  public function __construct(
    private string $name,
    private VarianceToken $variance,
    private vec<self::TConstraint> $constraints,
  ) {
  }

  public function getName(): string {
    return $this->name;
  }

  public function getConstraints(): vec<self::TConstraint> {
    return $this->constraints;
  }

  public function isContravariant(): bool {
    return $this->variance === VarianceToken::CONTRAVARIANT;
  }

  public function isInvariant(): bool {
    return $this->variance === VarianceToken::INVARIANT;
  }

  public function isCovariant(): bool {
    return $this->variance === VarianceToken::COVARIANT;
  }
}
