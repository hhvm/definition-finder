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
  $name = $node->getName();
  if ($name is HHAST\VariableToken) {
    $info = shape('name' => $name, 'variadic' => false);
  } else if ($name is HHAST\DecoratedExpression) {
    $info = parameter_info_from_decorated_expression($name);
  } else {
    invariant_violation(
      "Don't know how to handle name type %s",
      \get_class($name),
    );
  }

  $v = $node->getVisibility();
  if ($v is HHAST\PrivateToken) {
    $visibility = VisibilityToken::T_PRIVATE;
  } else if ($v is HHAST\ProtectedToken) {
    $visibility = VisibilityToken::T_PROTECTED;
  } else if ($v is HHAST\PublicToken) {
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
    $node->getCallConvention() is HHAST\InoutToken,
    $info['variadic'],
    value_from_ast($node->getDefaultValue()?->getValue()),
    $visibility,
  );
}
