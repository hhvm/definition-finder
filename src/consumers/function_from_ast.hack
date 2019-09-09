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
  $context = context_with_node_position($context, $node);

  $header = $node->getDeclarationHeader();
  $generics = generics_from_ast($context, $header->getTypeParameterList());

  return new ScannedFunction(
    $node,
    decl_name_in_context($context, name_from_ast($header->getName())),
    $context['definitionContext'],
    attributes_from_ast($node->getAttributeSpec()),
    /* docblock = */ null,
    $generics,
    typehint_from_ast($context, $header->getType()),
    parameters_from_ast($context, $header),
  );
}
