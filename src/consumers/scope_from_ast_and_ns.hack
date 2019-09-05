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

function scope_from_ast_and_ns(
  ConsumerContext $context,
  ?HHAST\NodeList<HHAST\Node> $ast,
  ?string $ns,
): ScannedScope {
  if ($ast === null) {
    $ast = new HHAST\NodeList(vec[]);
  }

  $context['namespace'] = $ns;

  $items = $ast->getChildren();
  $break = C\find_key($items, $i ==> $i is HHAST\NamespaceDeclaration);
  if ($break !== null) {
    $items = Vec\take($items, $break);
  }
  $ast = new HHAST\NodeList<HHAST\Node>($items);

  $classish = $ast->getChildrenOfType(HHAST\ClassishDeclaration::class);
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
      $ast->getChildrenOfType(HHAST\FunctionDeclaration::class),
      $node ==> function_from_ast($context, $node),
    ),
    /* methods = */ Vec\map(
      $ast->getChildrenOfType(HHAST\MethodishDeclaration::class),
      $node ==> method_from_ast($context, $node),
    ),
    /* trait use statements = */ Vec\concat(
      Vec\map(
        $ast->getChildrenOfType(HHAST\TraitUse::class),
        $node ==> $node->getNames()->getChildrenOfType(HHAST\Node::class),
      ),
      Vec\map(
        $ast->getChildrenOfType(HHAST\TraitUseConflictResolution::class),
        $node ==> $node->getNames()->getChildrenOfType(HHAST\Node::class),
      ),
    )
    |> Vec\flatten($$)
    |> Vec\map($$, $node ==> typehint_from_ast($context, $node))
    |> Vec\filter_nulls($$),
    /* properties = */ Vec\map(
      $ast->getChildrenOfType(HHAST\PropertyDeclaration::class),
      $node ==> properties_from_ast($context, $node),
    )
    |> Vec\flatten($$),
    /* constants = */ Vec\map(
      $ast->getChildrenOfType(HHAST\ConstDeclaration::class),
      $node ==> constants_from_ast($context, $node),
    )
    |> Vec\flatten($$),
    /* type constants = */ Vec\map(
      $ast->getChildrenOfType(HHAST\TypeConstDeclaration::class),
      $node ==> type_constant_from_ast($context, $node),
    ),
    /* enums = */ Vec\map(
      $ast->getChildrenOfType(HHAST\EnumDeclaration::class),
      $node ==> enum_from_ast($context, $node),
    ),
    /* types = */ Vec\map(
      $ast->getChildrenOfType(HHAST\AliasDeclaration::class),
      $node ==> typeish_from_ast($context, ScannedType::class, $node),
    )
    |> Vec\filter_nulls($$),
    /* newtypes = */ Vec\map(
      $ast->getChildrenOfType(HHAST\AliasDeclaration::class),
      $node ==> typeish_from_ast($context, ScannedNewtype::class, $node),
    )
      |> Vec\filter_nulls($$),
  );
}
