<?hh // strict
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

final class StaticPrefixUnaryExpression extends Expression<mixed> {
  const type TNode = HHAST\PrefixUnaryExpression;

  <<__Override>>
  protected static function matchImpl(
    this::TNode $node,
  ): ?Expression<mixed> {
    $value = StaticExpression::match($node->getOperand());
    if ($value === null) {
      return null;
    }
    $value = $value->getValue();

    $op = $node->getOperator();
    if ($op instanceof HHAST\ExclamationToken) {
      return new self(!(bool)$value);
    }
    if ($op instanceof HHAST\PlusToken) {
      return new self((int)$value);
    }
    if ($op instanceof HHAST\MinusToken) {
      return new self(-(int)$value);
    }

    return null;
  }
}
