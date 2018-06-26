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
use namespace HH\Lib\Vec;

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
    invariant($node instanceof HHAST\ListItem, "Got non-listitem child");
    $item = $node->getItem();
    invariant(
      $item instanceof HHAST\ParameterDeclaration,
      "Got non-decl child",
    );
    $out[] = parameter_from_ast($context, $item, $next_doccomment);
    $next_doccomment = doccomment_from_ast(
      $context['definitionContext'],
      $node->getSeparator()?->getTrailing() ?? HHAST\Missing(),
    );
  }
  return $out;
}
