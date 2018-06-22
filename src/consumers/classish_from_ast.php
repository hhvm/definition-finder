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
use namespace HH\Lib\C;

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

  return (
    new ScannedClassishBuilder(
      $name,
      context_with_node_position($context, $node)['definitionContext'],
      ClassDefinitionType::assert($def_class::getType()),
    )
  )
    ->setAttributes(attributes_from_ast($node->getAttribute()))
    ->setAbstractness(
      C\is_empty(
        $node->getModifiers()?->getItemsOfType(HHAST\AbstractToken::class) ??
          vec[],
      )
        ? AbstractnessToken::NOT_ABSTRACT
        : AbstractnessToken::IS_ABSTRACT,
    )
    ->setFinality(
      C\is_empty(
        $node->getModifiers()?->getItemsOfType(HHAST\FinalToken::class) ??
          vec[],
      )
        ? FinalityToken::NOT_FINAL
        : FinalityToken::IS_FINAL,
    )
    ->setGenericTypes(generics_from_ast($context, $node->getTypeParameters()))
    ->setContents(scope_from_ast($context, $node->getBody()->getElements()))
    ->build($def_class);
}
