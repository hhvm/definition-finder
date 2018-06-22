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

function decl_name_in_context(
  ConsumerContext $context,
  string $name,
): string {
  if ($context['namespace'] === null) {
    return $name;
  }
  return $context['namespace']."\\".$name;
}
