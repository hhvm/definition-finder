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

function function_from_ast(
  ScannedScope::TContext $context,
  HHAST\EditableNode $root,
  HHAST\FunctionDeclaration $node,
): ScannedFunction {
  $pos = HHAST\find_position($root, $node);
  $context['position'] = shape('line' => $pos[0], 'character' => $pos[1]);

  return (
    new ScannedFunctionBuilder(
      $node->getDeclarationHeaderx()->getNamex()->getCode(),
      $context,
    )
  )
    ->setAttributes(attributes_from_ast($node->getAttributeSpec()))
    ->setGenerics(
      generics_from_ast($node->getDeclarationHeader()->getTypeParameterList()),
    )
    ->build();
}
