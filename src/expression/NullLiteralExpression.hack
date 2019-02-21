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

final class NullLiteralExpression extends Expression<mixed> {
  const type TNode = HHAST\NullLiteralToken;

  <<__Override>>
  protected static function matchImpl(
    self::TNode $_,
  ): Expression<mixed> {
    return new self(null);
  }
}
