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

function enum_from_ast(
  ConsumerContext $context,
  HHAST\EnumDeclaration $node,
): ScannedEnum {
  return new ScannedEnum(
    $node,
    decl_name_in_context($context, $node->getName()->getText()),
    context_with_node_position($context, $node)['definitionContext'],
    attributes_from_ast($node->getAttributeSpec()),
    /* doccomment = */ null,
  );
}
