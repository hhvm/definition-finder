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

function typehint_from_ast(
  ConsumerContext $context,
  ?HHAST\EditableNode $node,
): ?ScannedTypehint {
  if ($node === null) {
    return null;
  }
  if ($node->isMissing()) {
    return null;
  }

  // Special cases
  if ($node instanceof HHAST\XHPClassNameToken) {
    $name = used_name_in_context($context, mangle_xhp_name_token($node));
    return new ScannedTypehint($node, $name, $name, vec[], false);
  }
  if ($node instanceof HHAST\EditableToken) {
    $name = used_name_in_context($context, name_from_ast($node));
    return new ScannedTypehint($node, $name, $name, vec[], false);
  }
  if ($node instanceof HHAST\QualifiedName) {
    $str = used_name_in_context($context, name_from_ast($node));
    return new ScannedTypehint($node, $str, $str, vec[], false);
  }

  // This list is taken from the docblock of
  // FunctionDeclarationheader::getType()

  if ($node instanceof HHAST\ClassnameTypeSpecifier) {
    return new ScannedTypehint(
      $node,
      'classname',
      'classname',
      typehints_from_ast_va($context, $node->getType()),
      /* nullable = */ false,
    );
  }
  if ($node instanceof HHAST\ClosureTypeSpecifier) {
    $normalized = ast_without_trivia($node);
    // Remove trailing comma
    $parameters = $normalized->getParameterList();
    if ($parameters !== null) {
      $parameters = $parameters->getChildren();
      $key = C\last_keyx($parameters);
      $item = $parameters[$key];
      invariant($item instanceof HHAST\ListItem, "List with non-item children");
      $parameters[$key] = $item->withSeparator(HHAST\Missing());
      $normalized = $normalized->withParameterList(
        new HHAST\EditableList(vec($parameters)),
      );
    }
    return new ScannedTypehint(
      $node,
      'callable',
      $normalized->getCode(),
      vec[],
      false,
    );
  }
  if ($node instanceof HHAST\DarrayTypeSpecifier) {
    return new ScannedTypehint(
      $node,
      'darray',
      'darray',
      typehints_from_ast_va($context, $node->getKey(), $node->getValue()),
      false,
    );
  }
  if ($node instanceof HHAST\DictionaryTypeSpecifier) {
    return new ScannedTypehint(
      $node,
      'dict',
      'dict',
      typehints_from_ast($context, $node->getMembers()),
      false,
    );
  }
  if ($node instanceof HHAST\GenericTypeSpecifier) {
    return new ScannedTypehint(
      $node,
      $node->getClassType()->getCode(),
      $node->getClassType()->getCode(),
      typehints_from_ast($context, $node->getArgumentList()->getTypes()),
      false,
    );
  }
  if ($node instanceof HHAST\KeysetTypeSpecifier) {
    return new ScannedTypehint(
      $node,
      'keyset',
      'keyset',
      typehints_from_ast_va($context, $node->getType()),
      false,
    );
  }
  if ($node instanceof HHAST\MapArrayTypeSpecifier) {
    return new ScannedTypehint(
      $node,
      'array',
      'array',
      typehints_from_ast_va($context, $node->getKey(), $node->getValue()),
      false,
    );
  }
  // HHAST\Missing was handled at top
  if ($node instanceof HHAST\NullableTypeSpecifier) {
    $inner = nullthrows(typehint_from_ast($context, $node->getType()));
    return new ScannedTypehint(
      $node,
      $inner->getTypeName(),
      $inner->getTypeTextBase(),
      $inner->getGenericTypes(),
      /* nullable = */ true,
    );
  }
  if ($node instanceof HHAST\ShapeTypeSpecifier) {
    return new ScannedTypehint(
      $node,
      'shape',
      ast_without_trivia($node)->getCode(),
      vec[],
      false,
    );
  }
  if ($node instanceof HHAST\SimpleTypeSpecifier) {
    return typehint_from_ast($context, $node->getSpecifier());
  }
  if ($node instanceof HHAST\SoftTypeSpecifier) {
    return typehint_from_ast($context, $node->getType());
  }
  // HHAST\NoReturnToken was handled at top
  if ($node instanceof HHAST\TupleTypeSpecifier) {
    return new ScannedTypehint(
      $node,
      'tuple',
      'tuple',
      typehints_from_ast($context, $node->getTypes()),
      false,
    );
  }
  if ($node instanceof HHAST\TypeConstant) {
    $left = nullthrows(typehint_from_ast($context, $node->getLeftType()))
      ->getTypeText();
    $str = $left.'::'.$node->getRightType()->getText();
    return new ScannedTypehint($node, $str, $str, vec[], false);
  }
  if ($node instanceof HHAST\VarrayTypeSpecifier) {
    return new ScannedTypehint(
      $node,
      'varray',
      'varray',
      typehints_from_ast_va($context, $node->getType()),
      false,
    );
  }
  if ($node instanceof HHAST\VectorArrayTypeSpecifier) {
    return new ScannedTypehint(
      $node,
      'array',
      'array',
      typehints_from_ast_va($context, $node->getType()),
      false,
    );
  }
  if ($node instanceof HHAST\VectorTypeSpecifier) {
    return new ScannedTypehint(
      $node,
      'vec',
      'vec',
      typehints_from_ast_va($context, $node->getType()),
      false,
    );
  }

  invariant_violation('Unhandled type: %s', \get_class($node));
}
