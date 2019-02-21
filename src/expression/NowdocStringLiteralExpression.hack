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

final class NowdocStringLiteralExpression extends Expression<string> {
  const type TNode = HHAST\NowdocStringLiteralToken;

  <<__Override>>
  protected static function matchImpl(self::TNode $n): ?Expression<string> {
    $text = $n->getText();
    $newline = Str\search($text, "\n");
    if ($newline === null) {
      return null;
    }
    $marker = Str\slice($text, 0, $newline)
      |> Str\strip_prefix($$, "<<<'")
      |> Str\strip_suffix($$, "'");

    return $text
      |> Str\slice($$, $newline + 1)
      |> Str\strip_suffix($$, "\n".$marker)
      |> new self($$);
  }
}
