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

final class StaticScalarExpression extends Expression<mixed> {
  protected static function matchImpl(TokenQueue $tq): ?Expression<mixed>{
    $subtypes = vec[
      CommonScalarExpression::class,
      StaticStringExpression::class,
      StaticClassClassConstantExpression::class,
      AttributeConstantExpression::class,
      PlusMinusStaticNumericScalarExpression::class,
      StaticPHPArrayExpression::class,
      StaticDictExpression::class,
      StaticVecExpression::class,
      StaticKeysetExpression::class,
      StaticShapeExpression::class,
    ];

    foreach ($subtypes as $subtype) {
      $match = $subtype::match($tq);
      if ($match) {
        return $match;
      }
    }
    return null;
  }
}
