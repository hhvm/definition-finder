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
use namespace HH\Lib\C;

function method_from_ast(
  ConsumerContext $context,
  HHAST\MethodishDeclaration $node,
): ScannedMethod {
  $context = context_with_node_position($context, $node);

  $header = $node->getFunctionDeclHeader();
  $modifiers = $header->getModifiers() ?? (new HHAST\NodeList(vec[]));
  $has_modifier = (
    classname<HHAST\Node> $m
  ) ==> !C\is_empty($modifiers->getChildrenOfType($m));

  $generics = generics_from_ast($context, $header->getTypeParameterList());

  return new ScannedMethod(
    $node,
    // Don't use decl_name_in_context() as methods are always inside
    // a class, so never get decorated with the namespace
    $header->getNamex()->getCode(),
    $context['definitionContext'],
    attributes_from_ast($node->getAttribute()),
    /* docblock = */ null,
    $generics,
    typehint_from_ast($context, $header->getType()),
    parameters_from_ast($context, $header),
    $has_modifier(HHAST\PrivateToken::class)
      ? VisibilityToken::T_PRIVATE
      : (
          $has_modifier(HHAST\ProtectedToken::class)
            ? VisibilityToken::T_PROTECTED
            : VisibilityToken::T_PUBLIC
        ),
    $has_modifier(HHAST\StaticToken::class)
      ? StaticityToken::IS_STATIC
      : StaticityToken::NOT_STATIC,
    $has_modifier(HHAST\AbstractToken::class)
      ? AbstractnessToken::IS_ABSTRACT
      : AbstractnessToken::NOT_ABSTRACT,
    $has_modifier(HHAST\FinalToken::class)
      ? FinalityToken::IS_FINAL
      : FinalityToken::NOT_FINAL,
  );
}
