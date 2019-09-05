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
  $context = context_with_node_position($context, $node);
  switch ($def_class) {
    case ScannedClass::class:
      if (!$node->getKeyword() is HHAST\ClassToken) {
        return null;
      }
      break;
    case ScannedInterface::class:
      if (!$node->getKeyword() is HHAST\InterfaceToken) {
        return null;
      }
      break;
    case ScannedTrait::class:
      if (!$node->getKeyword() is HHAST\TraitToken) {
        return null;
      }
      break;
    default:
      invariant_violation('new classish kind: %s', $def_class);
  }

  $name = $node->getName();
  if ($name === null) {
    return null;
  } else if ($name is HHAST\XHPClassNameToken) {
    $name = decl_name_in_context($context, mangle_xhp_name_token($name));
  } else {
    $name = decl_name_in_context($context, $name->getText());
  }

  $modifiers = $node->getModifiers() ?? new HHAST\NodeList(vec[]);
  $has_modifier = (
    classname<HHAST\Node> $m
  ) ==> !C\is_empty($modifiers->getChildrenOfType($m));

  $generics = generics_from_ast($context, $node->getTypeParameters());

  $extends = $node->getExtendsList();
  $parent = null;
  $interfaces = vec[];
  if ($extends) {
    $extends = $extends->getChildrenOfItemsOfType(HHAST\Node::class)
      |> Vec\map($$, $super ==> typehint_from_ast($context, $super))
      |> Vec\filter_nulls($$);
    if ($def_class === ScannedClass::class) {
      if (C\count($extends) === 1) {
        $parent = C\onlyx($extends);
      }
    } else {
      invariant(
        $def_class === ScannedInterface::class,
        "Shouldnt see `extends` unless we're dealing with a class or interface",
      );
      $interfaces = $extends;
    }
  }

  $implements = $node->getImplementsList();
  if ($implements) {
    $interfaces = $implements->getChildrenOfItemsOfType(HHAST\Node::class)
      |> Vec\map($$, $super ==> typehint_from_ast($context, $super))
      |> Vec\filter_nulls($$);
  }

  $contents_context = $context;
  $contents_context['scopeType'] = ScopeType::CLASSISH_SCOPE;
  $contents =
    scope_from_ast($contents_context, $node->getBody()->getElements());

  $promoted_constructor_args = vec[];
  $constructor =
    C\find($contents->getMethods(), $m ==> $m->getName() === '__construct');
  if ($constructor) {
    $promoted_constructor_args =
      Vec\filter($constructor->getParameters(), $p ==> $p->__isPromoted())
      |> Vec\map(
        $$,
        $p ==> new ScannedProperty(
          $p->getASTx(),
          $p->getName(),
          $p->getContext(),
          $p->getAttributes(),
          $p->getDocComment(),
          $p->getTypehint(),
          $p->__getVisibility(),
          StaticityToken::NOT_STATIC,
          $p->getDefault(),
        ),
      );
  }

  return new $def_class(
    $node,
    $name,
    $context['definitionContext'],
    attributes_from_ast($node->getAttribute()),
    /* docblock = */ null,
    $contents->getMethods(),
    Vec\concat($contents->getProperties(), $promoted_constructor_args),
    $contents->getConstants(),
    $contents->getTypeConstants(),
    $generics,
    $parent,
    $interfaces,
    $contents->getUsedTraits(),
    $has_modifier(HHAST\AbstractToken::class)
      ? AbstractnessToken::IS_ABSTRACT
      : AbstractnessToken::NOT_ABSTRACT,
    $has_modifier(HHAST\FinalToken::class)
      ? FinalityToken::IS_FINAL
      : FinalityToken::NOT_FINAL,
  );
}
