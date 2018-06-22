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

final class ScannedConstantBuilder extends ScannedSingleTypeBuilder<ScannedConstant> {
  public function __construct(
    HHAST\EditableNode $ast,
    string $name,
    self::TContext $context,
    private mixed $value,
    private ?ScannedTypehint $typehint,
    private AbstractnessToken $abstractness,
  ) {
    parent::__construct($ast, $name, $context);
  }

  <<__Override>>
  public function build(): ScannedConstant {
    return new ScannedConstant(
      $this->ast,
      $this->name,
      $this->getDefinitionContext(),
      $this->docblock,
      $this->value,
      $this->typehint,
      $this->abstractness,
    );
  }
}
