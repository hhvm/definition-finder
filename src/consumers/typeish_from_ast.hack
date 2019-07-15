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

function typeish_from_ast<T as ScannedTypeish>(
  ConsumerContext $context,
  classname<T> $def_class,
  HHAST\AliasDeclaration $node,
): ?T {
  switch ($def_class) {
    case ScannedType::class:
      if (!$node->getKeyword() is HHAST\TypeToken) {
        return null;
      }
      break;
    case ScannedNewtype::class:
      if (!$node->getKeyword() is HHAST\NewtypeToken) {
        return null;
      }
      break;
  }

  $context = context_with_node_position($context, $node);

  return new $def_class(
    $node,
    decl_name_in_context($context, name_from_ast($node->getName())),
    $context['definitionContext'],
    attributes_from_ast($node->getAttributeSpec()),
    null,
    nullthrows(typehint_from_ast($context, $node->getType())),
  );
}
