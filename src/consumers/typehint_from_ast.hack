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
    $resolved_type = HHAST\resolve_type(
      mangle_xhp_name_token($node),
      $context['ast'],
      $node,
    );
    return new ScannedTypehint(
      $node,
      $resolved_type['kind'],
      $resolved_type['name'],
      vec[],
      false,
      null,
      null,
    );
  }
  if ($node is HHAST\NameToken || $node is HHAST\QualifiedName) {
    $resolved_type = HHAST\resolve_type(
      name_from_ast($node),
      $context['ast'],
      $node,
    );
    return new ScannedTypehint(
      $node,
      $resolved_type['kind'],
      $resolved_type['name'],
      vec[],
      false,
      null,
      null,
    );
  }
  if ($node is HHAST\Token) {
    // Any other tokens (string, void, etc.) don't need to be resolved.
    $name = name_from_ast($node);
    return new ScannedTypehint($node, null, $name, vec[], false, null, null);
  }

  // This list is taken from the docblock of
  // FunctionDeclarationheader::getType()

  if ($node is HHAST\ClassnameTypeSpecifier) {
    return new ScannedTypehint(
      $node,
      null,
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
      HHAST\ResolvedTypeKind::CALLABLE,
      'callable',
      vec[],
      false,
      null,
      tuple(
        // parameters
        Vec\map(
          $node->getParameterList()?->getChildrenOfItems() ?? vec[],
          $param_type ==> {
            $inout = null;
            if ($param_type is HHAST\ClosureParameterTypeSpecifier) {
              $inout = $param_type->getCallConvention();
              $param_type = $param_type->getType();
            }
            $ellipsis = null;
            if ($param_type is HHAST\VariadicParameter) {
              $ellipsis = $param_type->getEllipsis();
              $param_type = $param_type->getType();
            }
            return tuple(
              $inout,
              typehint_from_ast($context, $param_type) as nonnull,
              $ellipsis,
            );
          },
        ),
        // return type
        typehint_from_ast($context, $node->getReturnType()) as nonnull,
      ),
    );
  }
  if ($node is HHAST\DarrayTypeSpecifier) {
    return new ScannedTypehint(
      $node,
      null,
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
      null,
      'dict',
      typehints_from_ast($context, $node->getMembers()),
      false,
      null,
      null,
    );
  }
  if ($node is HHAST\GenericTypeSpecifier) {
    $inner = typehint_from_ast($context, $node->getClassType()) as nonnull;
    return new ScannedTypehint(
      $node,
      $inner->getKind(),
      $inner->getTypeName(),
      typehints_from_ast($context, $node->getArgumentList()->getTypes()),
      false,
      null,
      null,
    );
  }
  if ($node is HHAST\KeysetTypeSpecifier) {
    return new ScannedTypehint(
      $node,
      null,
      'keyset',
      // https://github.com/hhvm/hhast/issues/95
      typehints_from_ast_va($context, $node->getTypeUNTYPED()),
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
      $inner->getKind(),
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
      null,
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
  if ($node is HHAST\AttributizedSpecifier) {
    return typehint_from_ast($context, $node->getType());
  }
  // HHAST\NoReturnToken was handled at top
  if ($node is HHAST\TupleTypeSpecifier) {
    return new ScannedTypehint(
      $node,
      null,
      'tuple',
      typehints_from_ast($context, $node->getTypes()),
      false,
      null,
      null,
    );
  }
  if ($node is HHAST\TypeConstant) {
    $left = typehint_from_ast($context, $node->getLeftType()) as nonnull;
    $str = $left->getTypeText().'::'.$node->getRightType()->getText();
    return new ScannedTypehint(
      $node,
      $left->getKind(),
      $str,
      vec[],
      false,
      null,
      null,
    );
  }
  if ($node is HHAST\VarrayTypeSpecifier) {
    return new ScannedTypehint(
      $node,
      null,
      'varray',
      typehints_from_ast_va($context, $node->getType()),
      false,
      null,
      null,
    );
  }
  if ($node is HHAST\VectorTypeSpecifier) {
    return new ScannedTypehint(
      $node,
      null,
      'vec',
      typehints_from_ast_va($context, $node->getType()),
      false,
      null,
      null,
    );
  }
  if ($node is HHAST\UnionTypeSpecifier) {
    return new ScannedTypehint(
      $node,
      null,
      ScannedTypehint::UNION,
      typehints_from_ast($context, $node->getTypes() as HHAST\NodeList<_>),
      false,
      null,
      null,
    );
  }
  if ($node is HHAST\IntersectionTypeSpecifier) {
    return new ScannedTypehint(
      $node,
      null,
      ScannedTypehint::INTERSECTION,
      typehints_from_ast($context, $node->getTypes() as HHAST\NodeList<_>),
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
