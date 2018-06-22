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
use namespace HH\Lib\Keyset;

function function_from_ast(
  ConsumerContext $context,
  HHAST\FunctionDeclaration $node,
): ScannedFunction {
  $context = context_with_node_position($context, $node);

  $header = $node->getDeclarationHeader();
  $generics = generics_from_ast($context, $header->getTypeParameterList());
  $context['genericTypeNames'] = Keyset\union(
    $context['genericTypeNames'],
    Keyset\map($generics, $g ==> $g->getName()),
  );

  return (
    new ScannedFunctionBuilder(
      $node,
      decl_name_in_context($context, $header->getNamex()->getCode()),
      $context['definitionContext'],
    )
  )
    ->setAttributes(attributes_from_ast($node->getAttributeSpec()))
    ->setGenerics($generics)
    ->setParameters(parameters_from_ast($context, $header->getParameterList()))
    ->setReturnType(typehint_from_ast($context, $header->getType()))
    ->build();
}
