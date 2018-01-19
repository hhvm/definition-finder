<?hh // strict
/*
 *  Copyright (c) 2015-present, Facebook, Inc.
 *  All rights reserved.
 *
 *  This source code is licensed under the BSD-style license found in the
 *  LICENSE file in the root directory of this source tree. An additional grant
 *  of patent rights can be found in the PATENTS file in the same directory.
 *
 */

namespace Facebook\DefinitionFinder\Expression;

use Facebook\DefinitionFinder\TokenQueue;

final class StaticScalarExpression extends Expression {
  protected static function matchImpl(TokenQueue $tq): ?Expression {

    $subtypes = vec[
      CommonScalarExpression::class,
      StaticStringExpression::class,
      StaticClassClassConstantExpression::class,
      AttributeConstantExpression::class,
      PlusMinusStaticNumericScalarExpression::class,
      StaticArrayExpression::class,
      StaticShapeExpression::class,
      /*
      | static_dict_literal_ae             { $$ = $1;}
      | static_vec_literal_ae              { $$ = $1;}
      | static_keyset_literal_ae           { $$ = $1;}
      */
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
