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
  ConsumerContext $context,
  HHAST\FunctionDeclaration $node,
): ScannedFunction {
  $pos = HHAST\find_position($context['ast'], $node);
  $def_context = $context['definitionContext'];
  $def_context['position'] = shape('line' => $pos[0], 'character' => $pos[1]);
  $header = $node->getDeclarationHeader();

  return (
    new ScannedFunctionBuilder(
      name_in_context(
        $context,
        $node->getDeclarationHeaderx()->getNamex()->getCode(),
      ),
      $def_context,
    )
  )
    ->setAttributes(attributes_from_ast($node->getAttributeSpec()))
    ->setGenerics(generics_from_ast($header->getTypeParameterList()))
    ->setReturnType(typehint_from_ast($header->getType()))
    ->build();
}
