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
use namespace HH\Lib\{C, Keyset};

function method_from_ast(
  ConsumerContext $context,
  HHAST\MethodishDeclaration $node,
): ScannedMethod {
  $context = context_with_node_position($context, $node);

  $header = $node->getFunctionDeclHeader();
  $modifiers = $header->getModifiers() ?? (new HHAST\EditableList(vec[]));
  $has_modifier = $m ==> !C\is_empty($modifiers->getItemsOfType($m));

  $generics = generics_from_ast($context, $header->getTypeParameterList());
  $context['genericTypeNames'] = Keyset\union(
    $context['genericTypeNames'],
    Keyset\map($generics, $g ==> $g->getName()),
  );

  return (
    new ScannedMethodBuilder(
      $node,
      // Don't bother with decl_name_in_context() as methods are always inside
      // a class, so don't get decorated with the namespace
      $header->getNamex()->getCode(),
      $context['definitionContext'],
    )
  )
    ->setAttributes(attributes_from_ast($node->getAttribute()))
    ->setGenerics($generics)
    ->setParameters(parameters_from_ast($context, $header->getParameterList()))
    ->setReturnType(typehint_from_ast($context, $header->getType()))
    ->setVisibility(
      $has_modifier(HHAST\PrivateToken::class)
        ? VisibilityToken::T_PRIVATE
        : (
            $has_modifier(HHAST\ProtectedToken::class)
              ? VisibilityToken::T_PROTECTED
              : VisibilityToken::T_PUBLIC
          ),
    )
    ->setStaticity(
      $has_modifier(HHAST\StaticToken::class)
        ? StaticityToken::IS_STATIC
        : StaticityToken::NOT_STATIC,
    )
    ->setAbstractness(
      $has_modifier(HHAST\AbstractToken::class)
        ? AbstractnessToken::IS_ABSTRACT
        : AbstractnessToken::NOT_ABSTRACT,
    )
    ->setFinality(
      $has_modifier(HHAST\FinalToken::class)
        ? FinalityToken::IS_FINAL
        : FinalityToken::NOT_FINAL,
    )
    ->build();
}
