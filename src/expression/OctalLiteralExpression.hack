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
use namespace HH\Lib\Math;

final class OctalLiteralExpression extends Expression<int> {
  const type TNode = HHAST\OctalLiteralToken;

  <<__Override>>
  protected static function matchImpl(
    self::TNode $n,
  ): Expression<int> {
    return new self(Math\from_base($n->getText(), 8));
  }
}
