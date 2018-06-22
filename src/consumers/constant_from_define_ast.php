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
use namespace HH\Lib\{C, Str};

function constant_from_define_ast(
  ConsumerContext $context,
  HHAST\DefineExpression $node,
): ScannedConstant {
  $arg_list = $node->getArgumentListx();
  $items = $arg_list->getItemsOfType(HHAST\EditableNode::class);
  invariant(C\count($items) === 2, 'Expected define() to have two arguments');
  $name = $items[0];
  $value = $items[1];

  if ($name instanceof HHAST\NameToken) {
    $name = $name->getText();
  } else {
    invariant(
      $name instanceof HHAST\LiteralExpression,
      "Don't know how to handle define name of type %s",
      \get_class($name),
    );
    $name = $name->getExpression();
    if ($name instanceof HHAST\SingleQuotedStringLiteralToken) {
      $name = Str\slice($name->getText(), 1, Str\length($name->getText()) - 2);
    } else if ($name instanceof HHAST\DoubleQuotedStringLiteralToken) {
      $name = Str\slice($name->getText(), 1, Str\length($name->getText()) - 2);
    } else {
      invariant_violation(
        "Don't know how to handle define name literal of type %s",
        \get_class($name),
      );
    }
  }

  return (
    new ScannedConstantBuilder(
      $node,
      $name, // these are not relative to the current namespace
      context_with_node_position($context, $node)['definitionContext'],
      value_from_ast($value),
      /* typehint = */ null,
      AbstractnessToken::NOT_ABSTRACT,
    )
  )->build();
}
