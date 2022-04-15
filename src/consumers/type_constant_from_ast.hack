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

function type_constant_from_ast(
  ConsumerContext $context,
  HHAST\TypeConstDeclaration $node,
): ScannedTypeConstant {
  $is_abstract = C\any(
    $node->getModifiers()?->getChildren() ?? vec[],
    $t ==> $t is HHAST\AbstractToken,
  );
  if ($is_abstract) {
    // multiple type constraints are supported at the syntax level
    // but they do not typecheck yet; grab the first one for now
    $constraints = $node->getTypeConstraints()?->getChildren() ?? vec[];
    $typehint = C\first($constraints)?->getType();
  } else {
    $typehint = $node->getTypeSpecifier();
  }
  return (
    new ScannedTypeConstant(
      $node,
      name_from_ast($node->getName()),
      context_with_node_position($context, $node)['definitionContext'],
      /* docblock = */ null,
      typehint_from_ast(
        $context,
        $typehint,
      ),
      $is_abstract
        ? AbstractnessToken::IS_ABSTRACT
        : AbstractnessToken::NOT_ABSTRACT,
    )
  );
}
