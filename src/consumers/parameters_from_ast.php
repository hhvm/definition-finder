<?hh // strict
/*
 *  Copyright (c) 2015-present, Facebook, Inc.
 *  All rights reserved.
 *
 *  This source code is licensed under the MIT license found in the
 *  LICENSE file in the root directory of this source tree.
 *
 */

namespace Facebook\DefinitionFinder;

use namespace Facebook\HHAST;
use namespace HH\Lib\Vec;

function parameters_from_ast(
  ConsumerContext $context,
  ?HHAST\EditableList $node,
): vec<ScannedParameter> {
  return Vec\map(
    _Private\items_of_type($node, HHAST\ParameterDeclaration::class),
    $n ==> parameter_from_ast($context, $n),
  );
}
