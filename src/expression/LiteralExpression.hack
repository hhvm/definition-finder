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
      BinaryLiteralExpression::class,
      BooleanLiteralExpression::class,
      DecimalLiteralExpression::class,
      DoubleQuotedStringLiteralExpression::class,
      FloatingLiteralExpression::class,
      HeredocStringLiteralExpression::class,
      HexadecimalLiteralExpression::class,
      NowdocStringLiteralExpression::class,
      NullLiteralExpression::class,
      OctalLiteralExpression::class,
      SingleQuotedStringLiteralExpression::class,
    ];
    $expr = $node->getExpression();
    if ($expr is nonnull) {
      foreach ($classes as $class) {
        $m = $class::match($expr);
        if ($m !== null) {
          return $m;
        }
      }
    }
    invariant_violation(
      "Unhandled literal expression: %s: %s\n",
      $expr is nonnull ? \get_class($expr) : 'null',
      $node->getCode(),
    );
    return null;
  }
}
