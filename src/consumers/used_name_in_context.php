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
use namespace HH\Lib\Str;

function used_name_in_context(
  ConsumerContext $context,
  string $name,
): string {
  $used = $context['usedTypes'][$name] ?? null;
  if ($used !== null) {
    return $used;
  }

  $ns = Str\search($name, "\\");
  if ($ns !== null) {
    $first = Str\slice($name, 0, $ns);
    $used = $context['usedNamespaces'][$first] ?? null;
    if ($used !== null) {
      return $used.Str\slice($name, $ns);
    }
  }

  return decl_name_in_context($context, $name);
}
