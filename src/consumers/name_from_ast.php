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
use namespace HH\Lib\{Str, Vec};

function name_from_ast(HHAST\EditableNode $node): string {
  if ($node instanceof HHAST\EditableToken) {
    return $node->getText();
  }
  if ($node instanceof HHAST\QualifiedName) {
    // Join with `\` as the `\` is an item separator, not an actual item in the
    // lists.
    //
    // If there's a leading `\` in the name, the first item is empty.
    return $node->getParts()->getItems()
      |> Vec\map($$, $x ==> $x?->getText() ?? '')
      |> Str\join($$, "\\");
  }

  if ($node instanceof HHAST\SimpleTypeSpecifier) {
    return name_from_ast($node->getSpecifier());
  }

  invariant_violation(
    "Expected EditableToken or QualifiedName, got %s",
    \get_class($node),
  );
}
