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
): ?ScannedConstant {
  $arg_list = $node->getArgumentList();
  if ($arg_list === null) {
    return null;
  }
  $items = $arg_list->getItemsOfType(HHAST\EditableNode::class);
  invariant(C\count($items) === 2, 'Expected define() to have two arguments');
  $name = $items[0];
  $value = $items[1];

  if ($name instanceof HHAST\NameToken) {
    $name = $name->getText();
  } else {
    $name = value_from_ast($name);
    if ($name === null) {
      return null;
    }
    $name = (string)$name;
  }

  return new ScannedConstant(
    $node,
    $name, // these are not relative to the current namespace
    context_with_node_position($context, $node)['definitionContext'],
    null,
    value_from_ast($value),
    /* typehint = */ null,
    AbstractnessToken::NOT_ABSTRACT,
  );
}
