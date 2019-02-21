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
use namespace HH\Lib\{C, Vec};

function scope_from_ast_and_ns(
  ConsumerContext $context,
  ?HHAST\EditableList<HHAST\EditableNode> $ast,
  ?string $ns,
): ScannedScope {
  if ($ast === null) {
    $ast = new HHAST\EditableList(vec[]);
  }

  $context['namespace'] = $ns;

  $items = $ast->getItems();
  $break = C\find_key($items, $i ==> $i is HHAST\NamespaceDeclaration);
  if ($break !== null) {
    $items = Vec\take($items, $break);
  }
  $ast = new HHAST\EditableList($items);

  $context = $context
    |> context_with_use_declarations(
      $$,
      $ast->getItemsOfType(HHAST\NamespaceUseDeclaration::class),
    )
    |> context_with_group_use_declarations(
      $$,
      $ast->getItemsOfType(HHAST\NamespaceGroupUseDeclaration::class),
    );

  $classish = $ast->getItemsOfType(HHAST\ClassishDeclaration::class);
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
      $ast->getItemsOfType(HHAST\FunctionDeclaration::class),
      $node ==> function_from_ast($context, $node),
    ),
    /* methods = */ Vec\map(
      $ast->getItemsOfType(HHAST\MethodishDeclaration::class),
      $node ==> method_from_ast($context, $node),
    ),
    /* trait use statements = */ Vec\concat(
      Vec\map(
        $ast->getItemsOfType(HHAST\TraitUse::class),
        $node ==> $node->getNames()->getItemsOfType(HHAST\EditableNode::class),
      ),
      Vec\map(
        $ast->getItemsOfType(HHAST\TraitUseConflictResolution::class),
        $node ==> $node->getNames()->getItemsOfType(HHAST\EditableNode::class),
      ),
    )
    |> Vec\flatten($$)
    |> Vec\map($$, $node ==> typehint_from_ast($context, $node))
    |> Vec\filter_nulls($$),
    /* properties = */ Vec\map(
      $ast->getItemsOfType(HHAST\PropertyDeclaration::class),
      $node ==> properties_from_ast($context, $node),
    )
    |> Vec\flatten($$),
    /* constants = */ Vec\concat(
      Vec\map(
        $ast->getItemsOfType(HHAST\ConstDeclaration::class),
        $node ==> constants_from_ast($context, $node),
      )
      |> Vec\flatten($$),
      $ast->getItemsOfType(HHAST\ExpressionStatement::class)
        |> Vec\map($$, $s ==> $s->getExpression())
        |> Vec\filter($$, $e ==> $e instanceof HHAST\DefineExpression)
        |> Vec\map(
          $$,
          $e ==> TypeAssert\instance_of(HHAST\DefineExpression::class, $e),
        )
        |> Vec\map($$, $e ==> constant_from_define_ast($context, $e))
        |> Vec\filter_nulls($$),
    ),
    /* type constants = */ Vec\map(
      $ast->getItemsOfType(HHAST\TypeConstDeclaration::class),
      $node ==> type_constant_from_ast($context, $node),
    ),
    /* enums = */ Vec\map(
      $ast->getItemsOfType(HHAST\EnumDeclaration::class),
      $node ==> enum_from_ast($context, $node),
    ),
    /* types = */ Vec\map(
      $ast->getItemsOfType(HHAST\AliasDeclaration::class),
      $node ==> typeish_from_ast($context, ScannedType::class, $node),
    )
    |> Vec\filter_nulls($$),
    /* newtypes = */ Vec\map(
      $ast->getItemsOfType(HHAST\AliasDeclaration::class),
      $node ==> typeish_from_ast($context, ScannedNewtype::class, $node),
    )
      |> Vec\filter_nulls($$),
  );
}
