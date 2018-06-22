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

final class LiteralExpression extends Expression<mixed> {
  const type TNode = HHAST\LiteralExpression;
  <<__Override>>
  protected static function matchImpl(
    self::TNode $node,
  ): ?Expression<mixed> {
    $classes = vec[
      DecimalLiteralExpression::class,
      DoubleQuotedStringLiteralExpression::class,
      SingleQuotedStringLiteralExpression::class,
    ];
    foreach ($classes as $class) {
      $m = $class::match($node->getExpression());
      if ($m !== null) {
        return $m;
      }
    }
    return null;
  }
}
