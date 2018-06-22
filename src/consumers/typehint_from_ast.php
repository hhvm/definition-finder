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
use namespace HH\Lib\{Dict, Str, Vec};

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

  if ($node instanceof HHAST\EditableToken) {
    $name = name_in_context($context, name_from_ast($node));
    return new ScannedTypehint($name, $name, vec[], false, $node);
  }
  if ($node instanceof HHAST\QualifiedName) {
    $str = name_in_context($context, name_from_ast($node));
    return new ScannedTypehint($str, $str, vec[], false, $node);
  }

  // This list is taken from the docblock of
  // FunctionDeclarationheader::getType()

  if ($node instanceof HHAST\ClassnameTypeSpecifier) {
    return new ScannedTypehint(
      'classname',
      'classname',
      typehints_from_ast_va($context, $node->getType()),
      /* nullable = */ false,
      $node,
    );
  }
  if ($node instanceof HHAST\ClosureTypeSpecifier) {
    return
      new ScannedTypehint('closure', $node->getCode(), vec[], false, $node);
  }
  if ($node instanceof HHAST\DarrayTypeSpecifier) {
    return new ScannedTypehint(
      'darray',
      'darray',
      typehints_from_ast_va($context, $node->getKey(), $node->getValue()),
      false,
      $node,
    );
  }
  if ($node instanceof HHAST\DictionaryTypeSpecifier) {
    return new ScannedTypehint(
      'dict',
      'dict',
      typehints_from_ast($context, $node->getMembers()),
      false,
      $node,
    );
  }
  if ($node instanceof HHAST\GenericTypeSpecifier) {
    return new ScannedTypehint(
      $node->getClassType()->getCode(),
      $node->getClassType()->getCode(),
      typehints_from_ast($context, $node->getArgumentList()->getTypes()),
      false,
      $node,
    );
  }
  if ($node instanceof HHAST\KeysetTypeSpecifier) {
    return new ScannedTypehint(
      'keyset',
      'keyset',
      typehints_from_ast_va($context, $node->getType()),
      false,
      $node,
    );
  }
  if ($node instanceof HHAST\MapArrayTypeSpecifier) {
    return new ScannedTypehint(
      'array',
      'array',
      typehints_from_ast_va($context, $node->getKey(), $node->getValue()),
      false,
      $node,
    );
  }
  // HHAST\Missing was handled at top
  if ($node instanceof HHAST\NullableTypeSpecifier) {
    $inner = nullthrows(typehint_from_ast($context, $node->getType()));
    return new ScannedTypehint(
      '?'.$inner->getTypeName(),
      $inner->getTypeTextBase(),
      $inner->getGenericTypes(),
      /* nullable = */ true,
      $node,
    );
  }
  if ($node instanceof HHAST\ShapeTypeSpecifier) {
    return new ScannedTypehint('shape', 'shape', vec[], false, $node);
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
      'tuple',
      'tuple',
      typehints_from_ast($context, $node->getTypes()),
      false,
      $node,
    );
  }
  if ($node instanceof HHAST\TypeConstant) {
    $left = nullthrows(typehint_from_ast($context, $node->getLeftType()))->getTypeText();
    $str = $left.'::'.$node->getRightType()->getText();
    return new ScannedTypehint($str, $str, vec[], false, $node);
  }
  if ($node instanceof HHAST\VarrayTypeSpecifier) {
    return new ScannedTypehint(
      'varray',
      'varray',
      typehints_from_ast_va($context, $node->getType()),
      false,
      $node,
    );
  }
  if ($node instanceof HHAST\VectorArrayTypeSpecifier) {
    return new ScannedTypehint(
      'array',
      'array',
      typehints_from_ast_va($context, $node->getType()),
      false,
      $node,
    );
  }
  if ($node instanceof HHAST\VectorTypeSpecifier) {
    return new ScannedTypehint(
      'vec',
      'vec',
      typehints_from_ast_va($context, $node->getType()),
      false,
      $node,
    );
  }

  // ...

  invariant_violation('Unhandled type: %s', \get_class($node));
}
