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

class ScannedTypeConstantBuilder
  extends ScannedSingleTypeBuilder<ScannedTypeConstant> {
  public function __construct(
    string $name,
    self::TContext $context,
    private ?ScannedTypehint $value,
    private AbstractnessToken $abstractness,
  ) {
    parent::__construct($name, $context);
  }

  public function build(): ScannedTypeConstant {
    return new ScannedTypeConstant(
      $this->name,
      $this->getDefinitionContext(),
      $this->docblock,
      $this->value,
      $this->abstractness,
    );
  }
}
