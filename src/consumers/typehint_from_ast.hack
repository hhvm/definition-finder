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
use namespace HH\Lib\Vec;

function typehint_from_ast(
  ConsumerContext $context,
  ?HHAST\Node $node,
): ?ScannedTypehint {
  if ($node === null) {
    return null;
  }

  // Special cases
  if ($node is HHAST\XHPClassNameToken) {
    $name = HHAST\resolve_type(
      mangle_xhp_name_token($node),
      $context['ast'],
      $node,
    )['name'];
    return new ScannedTypehint($node, $name, vec[], false, null, null);
  }
  if ($node is HHAST\NameToken || $node is HHAST\QualifiedName) {
    $name = HHAST\resolve_type(
      name_from_ast($node),
      $context['ast'],
      $node,
    )['name'];
    return new ScannedTypehint($node, $name, vec[], false, null, null);
  }
  if ($node is HHAST\Token) {
    // Any other tokens (string, void, etc.) don't need to be resolved.
    $name = name_from_ast($node);
    return new ScannedTypehint($node, $name, vec[], false, null, null);
  }

  // This list is taken from the docblock of
  // FunctionDeclarationheader::getType()

  if ($node is HHAST\ClassnameTypeSpecifier) {
    return new ScannedTypehint(
      $node,
      'classname',
      typehints_from_ast_va($context, $node->getType()),
      /* nullable = */ false,
      /* shape fields = */ null,
      /* function typehints = */ null,
    );
  }
  if ($node is HHAST\ClosureTypeSpecifier) {
    return new ScannedTypehint(
      $node,
      'callable',
      vec[],
      false,
      null,
      tuple(
        // parameters
        Vec\map(
          $node->getParameterList()?->getChildrenOfItems() ?? vec[],
          $item ==> $item is HHAST\ClosureParameterTypeSpecifier
            ? tuple(
                $item->getCallConvention(),
                typehint_from_ast($context, $item->getType()) as nonnull,
              )
            : tuple(null, typehint_from_ast($context, $item) as nonnull),
        ),
        // return type
        typehint_from_ast($context, $node->getReturnType()) as nonnull,
      ),
    );
  }
  if ($node is HHAST\DarrayTypeSpecifier) {
    return new ScannedTypehint(
      $node,
      'darray',
      typehints_from_ast_va($context, $node->getKey(), $node->getValue()),
      false,
      null,
      null,
    );
  }
  if ($node is HHAST\DictionaryTypeSpecifier) {
    return new ScannedTypehint(
      $node,
      'dict',
      typehints_from_ast($context, $node->getMembers()),
      false,
      null,
      null,
    );
  }
  if ($node is HHAST\GenericTypeSpecifier) {
    return new ScannedTypehint(
      $node,
      $node->getClassType()->getCode(),
      typehints_from_ast($context, $node->getArgumentList()->getTypes()),
      false,
      null,
      null,
    );
  }
  if ($node is HHAST\KeysetTypeSpecifier) {
    return new ScannedTypehint(
      $node,
      'keyset',
      // https://github.com/hhvm/hhast/issues/95
      typehints_from_ast_va($context, $node->getTypeUNTYPED()),
      false,
      null,
      null,
    );
  }
  if ($node is HHAST\MapArrayTypeSpecifier) {
    return new ScannedTypehint(
      $node,
      'array',
      typehints_from_ast_va($context, $node->getKey(), $node->getValue()),
      false,
      null,
      null,
    );
  }
  // HHAST\Missing was handled at top
  if ($node is HHAST\NullableTypeSpecifier) {
    $inner = nullthrows(typehint_from_ast($context, $node->getType()));
    return new ScannedTypehint(
      $node,
      $inner->getTypeName(),
      $inner->getGenericTypes(),
      /* nullable = */ true,
      $inner->isShape() ? $inner->getShapeFields() : null,
      $inner->getFunctionTypehints(),
    );
  }
  if ($node is HHAST\ShapeTypeSpecifier) {
    return new ScannedTypehint(
      $node,
      'shape',
      vec[],
      false,
      Vec\map(
        $node->getFields()?->getChildrenOfItems() ?? vec[],
        $field ==> shape_field_from_ast($context, $field),
      ),
      null,
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
      typehints_from_ast($context, $node->getTypes()),
      false,
      null,
      null,
    );
  }
  if ($node is HHAST\TypeConstant) {
    $left = nullthrows(typehint_from_ast($context, $node->getLeftType()))
      ->getTypeText();
    $str = $left.'::'.$node->getRightType()->getText();
    return new ScannedTypehint($node, $str, vec[], false, null, null);
  }
  if ($node is HHAST\VarrayTypeSpecifier) {
    return new ScannedTypehint(
      $node,
      'varray',
      typehints_from_ast_va($context, $node->getType()),
      false,
      null,
      null,
    );
  }
  if ($node is HHAST\VectorArrayTypeSpecifier) {
    return new ScannedTypehint(
      $node,
      'array',
      typehints_from_ast_va($context, $node->getType()),
      false,
      null,
      null,
    );
  }
  if ($node is HHAST\VectorTypeSpecifier) {
    return new ScannedTypehint(
      $node,
      'vec',
      typehints_from_ast_va($context, $node->getType()),
      false,
      null,
      null,
    );
  }
  if ($node is HHAST\ListItem<_>) {
    return typehint_from_ast($context, $node->getItem());
  }

  invariant_violation('Unhandled type: %s', \get_class($node));
}
