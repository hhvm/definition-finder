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

class ScannedTypeConstant extends ScannedBase {
  public function __construct(
    string $name,
    self::TContext $context,
    ?string $docblock,
    private ?ScannedTypehint $value,
    private AbstractnessToken $abstractness,
  ) {
    parent::__construct(
      $name,
      $context,
      /* attributes = */ Map { },
      $docblock,
    );
  }

  public static function getType(): DefinitionType {
    return DefinitionType::CONST_DEF;
  }

  public function isAbstract(): bool {
    return $this->abstractness === AbstractnessToken::IS_ABSTRACT;
  }

  public function getValue(): ?ScannedTypehint {
    return $this->value;
  }
}
