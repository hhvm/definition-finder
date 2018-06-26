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
use namespace HH\Lib\Str;

function parameter_from_ast(
  ConsumerContext $context,
  HHAST\ParameterDeclaration $node,
  ?string $doccomment,
): ScannedParameter {
  $variadic = false;
  $name = $node->getName();
  if ($name instanceof HHAST\VariableToken) {
    $info = shape('name' => $name, 'byref' => false, 'variadic' => false);
  } else if ($name instanceof HHAST\DecoratedExpression) {
    $info = parameter_info_from_decorated_expression($name);
  } else {
    invariant_violation(
      "Don't know how to handle name type %s",
      \get_class($name),
    );
  }

  $v = $node->getVisibility();
  if ($v instanceof HHAST\PrivateToken) {
    $visibility = VisibilityToken::T_PRIVATE;
  } else if ($v instanceof HHAST\ProtectedToken) {
    $visibility = VisibilityToken::T_PROTECTED;
  } else if ($v instanceof HHAST\PublicToken) {
    $visibility = VisibilityToken::T_PUBLIC;
  } else {
    $visibility = null;
  }

  return new ScannedParameter(
    $node,
    Str\strip_prefix($info['name']->getText(), '$'),
    context_with_node_position($context, $node)['definitionContext'],
    attributes_from_ast($node->getAttribute()),
    $doccomment,
    typehint_from_ast($context, $node->getType()),
    $info['byref'],
    $node->getCallConvention() instanceof HHAST\InoutToken,
    $info['variadic'],
    ($node->getDefaultValue() === null)
      ? null
      : ast_without_trivia($node->getDefaultValuex()->getValue())->getCode(),
    $visibility,
  );
}
