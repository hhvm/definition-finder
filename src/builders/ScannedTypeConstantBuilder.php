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

use namespace Facebook\HHAST;

class ScannedTypeConstantBuilder
  extends ScannedSingleTypeBuilder<ScannedTypeConstant> {
  public function __construct(
    HHAST\EditableNode $ast,
    string $name,
    self::TContext $context,
    private ?ScannedTypehint $value,
    private AbstractnessToken $abstractness,
  ) {
    parent::__construct($ast, $name, $context);
  }

  <<__Override>>
  public function build(): ScannedTypeConstant {
    return new ScannedTypeConstant(
      $this->ast,
      $this->name,
      $this->getDefinitionContext(),
      $this->docblock,
      $this->value,
      $this->abstractness,
    );
  }
}
