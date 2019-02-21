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
use namespace HH\Lib\Str;

final class DoubleQuotedStringLiteralExpression extends Expression<string> {
  const type TNode = HHAST\DoubleQuotedStringLiteralToken;

  <<__Override>>
  protected static function matchImpl(
    self::TNode $n,
  ): Expression<string> {
    $t = $n->getText();
    return new self(Str\slice($t, 1, Str\length($t) - 2));
  }
}
