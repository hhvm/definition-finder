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

final class ScannedConstant extends ScannedDefinition {
  public function __construct(
    HHAST\Node $node,
    string $name,
    self::TContext $context,
    ?string $docblock,
    private ?ScannedExpression $value,
    private ?ScannedTypehint $typehint,
    private AbstractnessToken $abstractness,
  ) {
    parent::__construct(
      $node,
      $name,
      $context,
      /* attributes = */ dict[],
      $docblock,
    );
    invariant(
      ($value === null) === ($abstractness === AbstractnessToken::IS_ABSTRACT),
      'Abstract constants with value or non-abstract constant without',
    );
  }

  <<__Override>>
  public static function getType(): DefinitionType {
    return DefinitionType::CONST_DEF;
  }

  public function isAbstract(): bool {
    return $this->abstractness === AbstractnessToken::IS_ABSTRACT;
  }

  public function hasValue(): bool {
    return $this->value !== null;
  }

  public function getValue(): ScannedExpression {
    invariant($this->value !== null, "Can't get value of an abstract constant");
    return $this->value;
  }

  public function getTypehint(): ?ScannedTypehint {
    return $this->typehint;
  }
}
