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

function context_with_node_position(
  ConsumerContext $context,
  HHAST\Node $node,
): ConsumerContext {
  $pos = HHAST\find_position($context['ast'], $node);
  $context['definitionContext']['position'] = shape(
    'line' => $pos[0],
    'character' => $pos[1],
  );
  return $context;
}
