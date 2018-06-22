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
use namespace Facebook\TypeAssert;
use namespace HH\Lib\{C, Dict, Str, Vec};

function scope_from_ast(
  ConsumerContext $context,
  ?HHAST\EditableList $ast,
): ScannedScope {
  if ($ast === null) {
    $ast = new HHAST\EditableList(vec[]);
  }

  $namespaces = _Private\items_of_type($ast, HHAST\NamespaceDeclaration::class);
  $without_bodies = Vec\filter(
    $namespaces,
    $ns ==>
      $ns->getBody() instanceof HHAST\NamespaceEmptyBody || !$ns->hasBody(),
  );
  // TODO: Process ones with bodies
  invariant(
    C\count($without_bodies) <= 1,
    "Too many namespace declarations!\n",
  );
  $context['namespace'] = C\first($without_bodies)?->getName()?->getCode();

  $uses = _Private\items_of_type($ast, HHAST\NamespaceUseDeclaration::class);
  foreach ($uses as $use) {
    $kind = $use->getKind();
    if ($kind instanceof HHAST\ConstToken) {
      continue;
    }
    if ($kind instanceof HHAST\FunctionToken) {
      continue;
    }
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

    if ($kind instanceof HHAST\TypeToken || $kind === null) {
      $context['usedTypes'] = Dict\merge($context['usedTypes'], $mapping);
      continue;
    }
    if ($kind instanceof HHAST\NamespaceToken || $kind === null) {
      $context['usedNamespaces'] =
        Dict\merge($context['usedNamespaces'], $mapping);
      continue;
    }
  }

  // TODO: group use clauses

  $classish = _Private\items_of_type($ast, HHAST\ClassishDeclaration::class);

  return new ScannedScope(
    $ast,
    $context['definitionContext'],
    /* classes = */ Vec\filter_nulls(Vec\map(
      $classish,
      $node ==> classish_from_ast($context, ScannedClass::class, $node),
    )),
    /* interfaces = */ Vec\filter_nulls(Vec\map(
      $classish,
      $node ==> classish_from_ast($context, ScannedInterface::class, $node),
    )),
    /* traits = */ Vec\filter_nulls(Vec\map(
      $classish,
      $node ==> classish_from_ast($context, ScannedTrait::class, $node),
    )),
    /* functions = */ Vec\map(
      _Private\items_of_type($ast, HHAST\FunctionDeclaration::class),
      $node ==> function_from_ast($context, $node),
    ),
    /* methods = */ Vec\map(
      _Private\items_of_type($ast, HHAST\MethodishDeclaration::class),
      $node ==> method_from_ast($context, $node),
    ),
    /* trait use statements = */ Vec\map(
      _Private\items_of_type($ast, HHAST\TraitUse::class),
      $node ==> Vec\map(
        $node->getNames()->getItemsOfType(HHAST\EditableNode::class),
        $inner ==> typehint_from_ast($context, $inner),
      ),
    )
    |> Vec\flatten($$) |> Vec\filter_nulls($$),
    /* properties = */ vec[],
    /* constants = */ Vec\concat(
      Vec\map(
        _Private\items_of_type($ast, HHAST\ConstDeclaration::class),
        $node ==> constants_from_ast($context, $node),
      )
      |> Vec\flatten($$),
      _Private\items_of_type($ast, HHAST\ExpressionStatement::class)
        |> Vec\map($$, $s ==> $s->getExpression())
        |> Vec\filter($$, $e ==> $e instanceof HHAST\DefineExpression::class)
        |> Vec\map(
          $$,
          $e ==> TypeAssert\instance_of(HHAST\DefineExpression::class, $e),
        )
        |> Vec\map($$, $e ==> constant_from_define_ast($context, $e)),
    ),
    /* type constants = */ vec[],
    /* enums = */ Vec\map(
      _Private\items_of_type($ast, HHAST\EnumDeclaration::class),
      $node ==> enum_from_ast($context, $node),
    ),
    /* types = */ vec[],
    /* newtypes = */ vec[],
  );
}
