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
use namespace HH\Lib\{Dict, Vec};

function attributes_from_ast(
  ?HHAST\AttributeSpecification $node,
): dict<string, vec<mixed>> {
  if ($node === null) {
    return dict[];
  }
  return _Private\items_of_type($node->getAttributes(), HHAST\Attribute::class)
    |> Dict\pull(
      $$,
      $attr ==> Vec\map(
        $attr->getValues()?->getItemsOfType(HHAST\EditableNode::class) ?? vec[],
        $child ==> value_from_ast($child),
      ),
      $attr ==> name_from_ast($attr->getName()),
    );
}
