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

use Facebook\DefinitionFinder\TokenQueue;

final class CommonScalarExpression extends Expression {
  protected static function matchImpl(TokenQueue $tq): ?Expression {
    // TODO: heredoc support (from common_scalar_ae)
    return StaticNumericScalarExpression::matchImpl($tq);
  }
}
