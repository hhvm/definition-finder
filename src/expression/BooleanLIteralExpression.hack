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

final class BooleanLiteralExpression extends Expression<bool> {
  const type TNode = HHAST\BooleanLiteralToken;
  <<__Override>>
  protected static function matchImpl(
    self::TNode $node,
  ): ?Expression<bool> {
    $t = Str\lowercase($node->getText());
    if ($t === 'false') {
      return new self(false);
    }
    if ($t === 'true') {
      return new self(true);
    }
    invariant_violation(
      "Invalid boolean literal value: %s\n",
      $node->getCode(),
    );
  }
}
