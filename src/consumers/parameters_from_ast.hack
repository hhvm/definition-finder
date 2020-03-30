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

function parameters_from_ast(
  ConsumerContext $context,
  HHAST\FunctionDeclarationHeader $header,
): vec<ScannedParameter> {
  $params = $header->getParameterList();
  if ($params === null) {
    return vec[];
  }
  // Doc comments are being attached as trailing on the preceding token
  $next_doccomment = doccomment_from_ast(
    $context['definitionContext'],
    $header->getLeftParenx()->getTrailing(),
  );
  $out = vec[];
  foreach ($params->getChildren() as $node) {
    invariant($node is HHAST\ListItem<_>, 'Got non-listitem child');
    $item = $node->getItem();
    if ($item is HHAST\VariadicParameter) {
      $out[] = new ScannedParameter(
        $item,
        '',
        context_with_node_position($context, $item)['definitionContext'],
        /* attributes = */ dict[],
        /* doccomment = */ null,
        typehint_from_ast($context, $item->getType()),
        /* inout = */ false,
        /* variadic = */ true,
        /* default = */ null,
        /* visibility = */ null,
      );
      continue;
    }
    invariant(
      $item is HHAST\ParameterDeclaration,
      "Got non-decl child: %s: %s\n%s",
      \get_class($item),
      $item->getCode(),
      $header->getCode(),
    );
    $out[] = parameter_from_ast($context, $item, $next_doccomment);
    $next_doccomment = doccomment_from_ast(
      $context['definitionContext'],
      $node->getSeparator()?->getTrailing(),
    );
  }
  return $out;
}
