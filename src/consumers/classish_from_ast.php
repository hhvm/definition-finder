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
use namespace HH\Lib\{C, Vec};

function classish_from_ast<T as ScannedClassish>(
  ConsumerContext $context,
  classname<T> $def_class,
  HHAST\ClassishDeclaration $node,
): ?T {
  switch ($def_class) {
    case ScannedClass::class:
      if (!$node->getKeyword() instanceof HHAST\ClassToken) {
        return null;
      }
      break;
    case ScannedInterface::class:
      if (!$node->getKeyword() instanceof HHAST\InterfaceToken) {
        return null;
      }
      break;
    case ScannedTrait::class:
      if (!$node->getKeyword() instanceof HHAST\TraitToken) {
        return null;
      }
      break;
    default:
      invariant_violation('new classish kind: %s', $def_class);
  }

  $name = $node->getName();
  if ($name instanceof HHAST\XHPClassNameToken) {
    $name = decl_name_in_context($context, mangle_xhp_name_token($name));
  } else {
    $name = decl_name_in_context($context, $name->getText());
  }

  $modifiers = $node->getModifiers() ?? new HHAST\EditableList(vec[]);
  $has_modifier = $m ==> !C\is_empty($modifiers->getItemsOfType($m));

  $builder = (
    new ScannedClassishBuilder(
      $node,
      $name,
      context_with_node_position($context, $node)['definitionContext'],
      ClassDefinitionType::assert($def_class::getType()),
    )
  )
    ->setAttributes(attributes_from_ast($node->getAttribute()))
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
    ->setGenericTypes(generics_from_ast($context, $node->getTypeParameters()))
    ->setContents(scope_from_ast($context, $node->getBody()->getElements()));

  $extends = $node->getExtendsList();
  if ($extends) {
    $extends = $extends->getItemsOfType(HHAST\EditableNode::class)
      |> Vec\map($$, $super ==> typehint_from_ast($context, $super))
      |> Vec\filter_nulls($$);
    if ($def_class === ScannedClass::class) {
      $builder->setParentClassInfo(C\onlyx($extends));
    } else {
      invariant(
        $def_class === ScannedInterface::class,
        "Shouldnt see `extends` unless we're dealing with a class or interface",
      );
      $builder->setInterfaces($extends);
    }
  }

  $implements = $node->getImplementsList();
  if ($implements) {
    $implements->getItemsOfType(HHAST\EditableNode::class)
      |> Vec\map($$, $super ==> typehint_from_ast($context, $super))
      |> Vec\filter_nulls($$)
      |> $builder->setInterfaces($$);
  }

  return $builder
    ->build($def_class);
}
