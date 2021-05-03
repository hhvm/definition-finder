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
    if ($op is HHAST\ExclamationEqualToken) {
      /* HHAST_IGNORE_ALL[NoPHPEquality] cant suppress more specifically */
      return new self($left != $right);
    }
    if ($op is HHAST\ExclamationEqualEqualToken) {
      return new self($left !== $right);
    }
    if ($op is HHAST\PercentToken) {
      return new self(/* HH_FIXME[4110] */ $left % $right);
    }
    if ($op is HHAST\AmpersandToken) {
      return new self(/* HH_FIXME[4110] */ /* HH_FIXME[4423] */ $left & $right);
    }
    if ($op is HHAST\AmpersandAmpersandToken) {
      return new self($left && $right);
    }
    if ($op is HHAST\StarToken) {
      return new self(/* HH_FIXME[4110] */ $left * $right);
    }
    if ($op is HHAST\StarStarToken) {
      return new self(/* HH_FIXME[4110] */ $left ** $right);
    }
    if ($op is HHAST\PlusToken) {
      return new self(/* HH_FIXME[4110] */ $left + $right);
    }
    if ($op is HHAST\MinusToken) {
      return new self(/* HH_FIXME[4110] */ $left - $right);
    }
    if ($op is HHAST\DotToken) {
      return new self((string)$left.(string)$right);
    }
    if ($op is HHAST\SlashToken) {
      return new self(/* HH_FIXME[4110] */ $left / $right);
    }
    if ($op is HHAST\LessThanToken) {
      return new self(/* HH_FIXME[4240] */ $left < $right);
    }
    if ($op is HHAST\LessThanEqualToken) {
      return new self(/* HH_FIXME[4240] */ $left <= $right);
    }
    if ($op is HHAST\GreaterThanToken) {
      return new self(/* HH_FIXME[4240] */ $left > $right);
    }
    if ($op is HHAST\GreaterThanEqualToken) {
      return new self(/* HH_FIXME[4240] */ $left >= $right);
    }
    if ($op is /* sic */ HHAST\CaratToken) {
      return new self(/* HH_FIXME[4110] */ /* HH_FIXME[4423] */ $left ^ $right);
    }
    if ($op is /* sic */ HHAST\BarToken) {
      return new self(/* HH_FIXME[4110] */ /* HH_FIXME[4423] */ $left | $right);
    }
    if ($op is /* sic */ HHAST\BarBarToken) {
      return new self($left || $right);
    }

    return null;
  }
}
