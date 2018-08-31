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
use namespace HH\Lib\{C, Vec};

function scope_from_ast(
  ConsumerContext $context,
  ?HHAST\EditableList<HHAST\EditableNode> $ast,
): ScannedScope {
  if ($ast === null) {
    $ast = new HHAST\EditableList(vec[]);
  }

  $namespaces = $ast->getItemsOfType(HHAST\NamespaceDeclaration::class);

  $scopes = vec[];
  $scopes[] = scope_from_ast_and_ns($context, $ast, $context['namespace']);
  if (C\is_empty($namespaces)) {
    return $scopes[0];
  }

  $items = $ast->getItems();
  $offsets = Vec\map(
    $namespaces,
    $ns ==> nullthrows(C\find_key($items, $item ==> $item === $ns)),
  );
  $count = C\count($namespaces);

  foreach ($namespaces as $i => $ns) {
    $body = $ns->getBody();
    if ($body instanceof HHAST\NamespaceBody) {
      $scopes[] = scope_from_ast_and_ns(
        $context,
        $body->getDeclarations(),
        $ns->hasName() ? name_from_ast($ns->getNamex()) : null,
      );
      continue;
    }

    invariant(
      $body === null || $body instanceof HHAST\NamespaceEmptyBody,
      "Expected a NamespaceBody or NamespaceEmptyBody",
    );

    $offset = $offsets[$i];
    $next_offset = $offsets[$i + 1] ?? null;
    $length = ($next_offset === null) ? null : ($next_offset - $offset);
    $ns_items = Vec\slice($items, $offset + 1, $length);

    $scopes[] = scope_from_ast_and_ns(
      $context,
      new HHAST\EditableList($ns_items),
      name_from_ast($ns->getNamex()),
    );
  }

  return merge_scopes($ast, $context['definitionContext'], $scopes);
}
