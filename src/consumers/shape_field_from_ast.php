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

function shape_field_from_ast(
  ConsumerContext $context,
  HHAST\FieldSpecifier $node,
): ScannedShapeField {
  $context = context_with_node_position($context, $node);

  return new ScannedShapeField(
    $node,
    (string)nullthrows(Expression\LiteralExpression::match($node->getName()))
      ->getValue(),
    $context['definitionContext'],
    /* attributes (unsupported) = */ dict[],
    /* doccomment = */ null,
    $node->getQuestion() instanceof HHAST\QuestionToken
      ? OptionalityToken::IS_OPTIONAL
      : OptionalityToken::IS_REQUIRED,
    nullthrows(typehint_from_ast($context, $node->getType())),
  );
}
