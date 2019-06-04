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

function constants_from_ast(
  ConsumerContext $context,
  HHAST\ConstDeclaration $decl,
): vec<ScannedConstant> {
  return Vec\map(
    $decl->getDeclarators()->getChildrenOfItems(),
    $inner ==> constant_from_ast($context, $decl, $inner),
  );
}
