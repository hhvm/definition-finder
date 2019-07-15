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

function typehint_from_ast(
  ConsumerContext $context,
  ?HHAST\Node $node,
): ?ScannedTypehint {
  if ($node === null) {
    return null;
  }

  // Special cases
  if ($node is HHAST\XHPClassNameToken) {
    $name = used_name_in_context($context, mangle_xhp_name_token($node));
    return new ScannedTypehint($node, $name, $name, vec[], false, null);
  }
  if ($node is HHAST\Token) {
    $name = used_name_in_context($context, name_from_ast($node));
    return new ScannedTypehint($node, $name, $name, vec[], false, null);
  }
  if ($node is HHAST\QualifiedName) {
    $str = used_name_in_context($context, name_from_ast($node));
    return new ScannedTypehint($node, $str, $str, vec[], false, null);
  }

  // This list is taken from the docblock of
  // FunctionDeclarationheader::getType()

  if ($node is HHAST\ClassnameTypeSpecifier) {
    return new ScannedTypehint(
      $node,
      'classname',
      'classname',
      typehints_from_ast_va($context, $node->getType()),
      /* nullable = */ false,
      /* shape fields = */ null,
    );
  }
  if ($node is HHAST\ClosureTypeSpecifier) {
    $normalized = ast_without_trivia($node);
    // Remove trailing comma
    $parameters = $normalized->getParameterList();
    if ($parameters !== null) {
      $parameters = $parameters->getChildren();
      $key = C\last_keyx($parameters);
      $item = $parameters[$key];
      invariant($item is HHAST\ListItem<_>, "List with non-item children");
      $parameters[$key] = $item->withSeparator(null);
      $normalized = $normalized->withParameterList(
        new HHAST\NodeList(vec($parameters)),
      );
    }
    return new ScannedTypehint(
      $node,
      'callable',
      $normalized->getCode(),
      vec[],
      false,
      null,
    );
  }
  if ($node is HHAST\DarrayTypeSpecifier) {
    return new ScannedTypehint(
      $node,
      'darray',
      'darray',
      typehints_from_ast_va($context, $node->getKey(), $node->getValue()),
      false,
      null,
    );
  }
  if ($node is HHAST\DictionaryTypeSpecifier) {
    return new ScannedTypehint(
      $node,
      'dict',
      'dict',
      typehints_from_ast($context, $node->getMembers()),
      false,
      null,
    );
  }
  if ($node is HHAST\GenericTypeSpecifier) {
    return new ScannedTypehint(
      $node,
      $node->getClassType()->getCode(),
      $node->getClassType()->getCode(),
      typehints_from_ast($context, $node->getArgumentList()->getTypes()),
      false,
      null,
    );
  }
  if ($node is HHAST\KeysetTypeSpecifier) {
    return new ScannedTypehint(
      $node,
      'keyset',
      'keyset',
      // https://github.com/hhvm/hhast/issues/95
      typehints_from_ast_va($context, $node->getTypeUNTYPED()),
      false,
      null,
    );
  }
  if ($node is HHAST\MapArrayTypeSpecifier) {
    return new ScannedTypehint(
      $node,
      'array',
      'array',
      typehints_from_ast_va($context, $node->getKey(), $node->getValue()),
      false,
      null,
    );
  }
  // HHAST\Missing was handled at top
  if ($node is HHAST\NullableTypeSpecifier) {
    $inner = nullthrows(typehint_from_ast($context, $node->getType()));
    return new ScannedTypehint(
      $node,
      $inner->getTypeName(),
      $inner->getTypeTextBase(),
      $inner->getGenericTypes(),
      /* nullable = */ true,
      /* shape fields = */ null,
    );
  }
  if ($node is HHAST\ShapeTypeSpecifier) {
    return new ScannedTypehint(
      $node,
      'shape',
      ast_without_trivia($node)->getCode(),
      vec[],
      false,
      Vec\map(
        $node->getFields()?->getChildrenOfItems() ?? vec[],
        $field ==> shape_field_from_ast($context, $field),
      ),
    );
  }
  if ($node is HHAST\SimpleTypeSpecifier) {
    return typehint_from_ast($context, $node->getSpecifier());
  }
  if ($node is HHAST\SoftTypeSpecifier) {
    return typehint_from_ast($context, $node->getType());
  }
  // HHAST\NoReturnToken was handled at top
  if ($node is HHAST\TupleTypeSpecifier) {
    return new ScannedTypehint(
      $node,
      'tuple',
      'tuple',
      typehints_from_ast($context, $node->getTypes()),
      false,
      null,
    );
  }
  if ($node is HHAST\TypeConstant) {
    $left = nullthrows(typehint_from_ast($context, $node->getLeftType()))
      ->getTypeText();
    $str = $left.'::'.$node->getRightType()->getText();
    return new ScannedTypehint($node, $str, $str, vec[], false, null);
  }
  if ($node is HHAST\VarrayTypeSpecifier) {
    return new ScannedTypehint(
      $node,
      'varray',
      'varray',
      typehints_from_ast_va($context, $node->getType()),
      false,
      null,
    );
  }
  if ($node is HHAST\VectorArrayTypeSpecifier) {
    return new ScannedTypehint(
      $node,
      'array',
      'array',
      typehints_from_ast_va($context, $node->getType()),
      false,
      null,
    );
  }
  if ($node is HHAST\VectorTypeSpecifier) {
    return new ScannedTypehint(
      $node,
      'vec',
      'vec',
      typehints_from_ast_va($context, $node->getType()),
      false,
      null,
    );
  }
  if ($node is HHAST\ListItem<_>) {
    return typehint_from_ast($context, $node->getItem());
  }

  invariant_violation('Unhandled type: %s', \get_class($node));
}
