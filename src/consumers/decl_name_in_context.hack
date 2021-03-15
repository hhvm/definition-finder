/*
 *  Copyright (c) 2015-present, Facebook, Inc.
 *  All rights reserved.
 *
 *  This source code is licensed under the MIT license found in the
 *  LICENSE file in the root directory of this source tree.
 *
 */

namespace Facebook\DefinitionFinder;

function decl_name_in_context(
  ConsumerContext $context,
  string $name,
): string {
  if ($context['scopeType'] === ScopeType::CLASSISH_SCOPE) {
    return $name;
  }
  $ns = $context['namespace'];
  if ($ns === null || $ns === '') {
    return $name;
  }
  return $ns.'\\'.$name;
}
