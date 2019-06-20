/*
 *  Copyright (c) 2015-present, Facebook, Inc.
 *  All rights reserved.
 *
 *  This source code is licensed under the MIT license found in the
 *  LICENSE file in the root directory of this source tree.
 *
 */

namespace Facebook\DefinitionFinder\Expression;

use namespace Facebook\HHAST;

final class StaticBinaryExpression extends Expression<mixed> {
  const type TNode = HHAST\BinaryExpression;

  <<__Override>>
  protected static function matchImpl(
    this::TNode $node,
  ): ?Expression<mixed> {
    $left = StaticExpression::match($node->getLeftOperand());
    if ($left === null) {
      return null;
    }
    $right = StaticExpression::match($node->getRightOperandx());
    if ($right === null) {
      return null;
    }
    $left = $left->getValue();
    $right = $right->getValue();

    $op = $node->getOperator();
    if ($op instanceof HHAST\ExclamationEqualToken) {
      /* HHAST_IGNORE_ALL[NoPHPEquality] cant suppress more specifically */
      return new self($left != $right);
    }
    if ($op instanceof HHAST\ExclamationEqualEqualToken) {
      return new self($left !== $right);
    }
    if ($op instanceof HHAST\PercentToken) {
      return new self(/* HH_FIXME[4110] */ $left % $right);
    }
    if ($op instanceof HHAST\AmpersandToken) {
      return new self(/* HH_FIXME[4110] */ $left & $right);
    }
    if ($op instanceof HHAST\AmpersandAmpersandToken) {
      return new self($left && $right);
    }
    if ($op instanceof HHAST\StarToken) {
      return new self(/* HH_FIXME[4110] */ $left * $right);
    }
    if ($op instanceof HHAST\StarStarToken) {
      return new self(/* HH_FIXME[4110] */ $left ** $right);
    }
    if ($op instanceof HHAST\PlusToken) {
      return new self(/* HH_FIXME[4110] */ $left + $right);
    }
    if ($op instanceof HHAST\MinusToken) {
      return new self(/* HH_FIXME[4110] */ $left - $right);
    }
    if ($op instanceof HHAST\DotToken) {
      return new self((string)$left.(string)$right);
    }
    if ($op instanceof HHAST\SlashToken) {
      return new self(/* HH_FIXME[4110] */ $left / $right);
    }
    if ($op instanceof HHAST\LessThanToken) {
      return new self(/* HH_FIXME[4240] */ $left < $right);
    }
    if ($op instanceof HHAST\LessThanEqualToken) {
      return new self(/* HH_FIXME[4240] */ $left <= $right);
    }
    if ($op instanceof HHAST\GreaterThanToken) {
      return new self(/* HH_FIXME[4240] */ $left > $right);
    }
    if ($op instanceof HHAST\GreaterThanEqualToken) {
      return new self(/* HH_FIXME[4240] */ $left >= $right);
    }
    if ($op instanceof /* sic */ HHAST\CaratToken) {
      return new self(/* HH_FIXME[4110] */ $left ^ $right);
    }
    if ($op instanceof /* sic */ HHAST\BarToken) {
      return new self(/* HH_FIXME[4110] */ $left | $right);
    }
    if ($op instanceof /* sic */ HHAST\BarBarToken) {
      return new self($left || $right);
    }

    return null;
  }
}
