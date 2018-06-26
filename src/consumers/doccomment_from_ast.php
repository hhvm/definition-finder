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
use namespace HH\Lib\{C, Str, Vec};

function doccomment_from_ast(
  ScannedDefinition::TContext $context,
  HHAST\EditableNode $node,
): ?string {
  if ($node->isMissing()) {
    return null;
  }
  $leading = $node->getFirstToken()?->getLeading() ?? HHAST\Missing();
  if ($leading->isMissing() && $node instanceof HHAST\EditableList) {
    $maybe_doc_comments =
      _Private\items_of_type($node, HHAST\DelimitedComment::class);
  } else if ($leading instanceof HHAST\EditableList) {
    $maybe_doc_comments =
      _Private\items_of_type($leading, HHAST\DelimitedComment::class);
  } else if ($leading instanceof HHAST\DelimitedComment) {
    $maybe_doc_comments = vec[$leading];
  } else {
    return null;
  }
  $doc_comments = $maybe_doc_comments
    |> Vec\map($$, $c ==> $c->getText())
    |> Vec\filter($$, $c ==> Str\starts_with($c, '/**'));
  switch (C\count($doc_comments)) {
    case 0:
      return null;
    case 1:
      return $doc_comments[0];
    default:
      $pos = $context['position'] ??
        shape('line' => -1, 'character' => -1);
      invariant_violation(
        "More than one doccomment for node at %s:%d:%d",
        $context['filename'],
        $pos['line'],
        $pos['character'],
      );
  }
}
