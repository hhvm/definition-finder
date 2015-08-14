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

class ScannedGeneric {
  public function __construct(
    private string $name,
    private ?string $constraint,
    private VarianceToken $variance,
    private ?RelationshipToken $relationship,
  ) {
  }

  public function getName(): string {
    return $this->name;
  }

  public function getConstraintTypeName(): ?string {
    return $this->constraint;
  }

  public function getConstraintRelationship(): ?RelationshipToken {
    return $this->relationship;
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
