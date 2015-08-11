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

class ScannedParameter {
  public function __construct(
    private string $name,
    private ?ScannedTypehint $type,
    private bool $byref,
  ) {
  }

  public function getName(): string {
    return $this->name;
  }

  public function getTypehint(): ?ScannedTypehint {
    return $this->type;
  }

  public function isPassedByReference(): bool {
    return $this->byref;
  }
}
