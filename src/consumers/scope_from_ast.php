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
use namespace HH\Lib\{C, Dict, Str, Vec};

function scope_from_ast(
  ConsumerContext $context,
  ?HHAST\EditableList $ast,
): ScannedScope {
  if ($ast === null) {
    $ast = new HHAST\EditableList(vec[]);
  }

  $ns = _Private\items_of_type($ast, HHAST\NamespaceDeclaration::class);
  invariant(C\count($ns) <= 1, "Too many namespace declarations!\n");
  $context['namespace'] = C\first($ns)?->getName()?->getCode();

  $uses = _Private\items_of_type($ast, HHAST\NamespaceUseDeclaration::class);
  foreach ($uses as $use) {
    $mapping = Dict\pull(
      _Private\items_of_type(
        $use->getClauses(),
        HHAST\NamespaceUseClause::class,
      ),
      $node ==> name_from_ast($node->getName()),
      $node ==> name_from_ast(
        $node->hasAlias() ? $node->getAliasx() : $node->getName(),
      ),
    );
  }

  // TODO: group use clauses

  $classish = _Private\items_of_type($ast, HHAST\ClassishDeclaration::class);

  return new ScannedScope(
    $context['definitionContext'],
    Vec\filter_nulls(Vec\map(
      $classish,
      $node ==> classish_from_ast($context, ScannedClass::class, $node),
    )),
    vec[], // interfaces
    vec[], // traits
    Vec\map(
      _Private\items_of_type($ast, HHAST\FunctionDeclaration::class),
      $node ==> function_from_ast($context, $node),
    ),
    vec[], // methods
    vec[], // used traits
    vec[], // properties
    vec[], // constants
    vec[], // type constants
    vec[], // enums
    vec[], // types
    vec[], // newtypes
  );
}
