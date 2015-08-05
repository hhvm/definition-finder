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

class ScannedConstant extends ScannedBase {
  public function __construct(
    SourcePosition $position,
    string $name,
    private mixed $value,
  ) {
    parent::__construct(
      $position,
      $name,
      /* attributes = */ Map { },
    );
  }

  public static function getType(): DefinitionType {
    return DefinitionType::CONST_DEF;
  }

  public function getValue(): mixed {
    return $this->value;
  }
}
