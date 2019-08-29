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

function merge_scopes(
  ?HHAST\Node $ast,
  ScannedDefinition::TContext $context,
  vec<ScannedScope> $scopes,
): ScannedScope {
  return new ScannedScope(
    $ast,
    $context,
    Vec\map($scopes, $s ==> $s->getClasses()) |> Vec\flatten($$),
    Vec\map($scopes, $s ==> $s->getInterfaces()) |> Vec\flatten($$),
    Vec\map($scopes, $s ==> $s->getTraits()) |> Vec\flatten($$),
    Vec\map($scopes, $s ==> $s->getFunctions()) |> Vec\flatten($$),
    Vec\map($scopes, $s ==> $s->getMethods()) |> Vec\flatten($$),
    Vec\map($scopes, $s ==> $s->getUsedTraits()) |> Vec\flatten($$),
    Vec\map($scopes, $s ==> $s->getProperties()) |> Vec\flatten($$),
    Vec\map($scopes, $s ==> $s->getConstants()) |> Vec\flatten($$),
    Vec\map($scopes, $s ==> $s->getTypeConstants()) |> Vec\flatten($$),
    Vec\map($scopes, $s ==> $s->getEnums()) |> Vec\flatten($$),
    Vec\map($scopes, $s ==> $s->getTypes()) |> Vec\flatten($$),
    Vec\map($scopes, $s ==> $s->getNewtypes()) |> Vec\flatten($$),
  );
}
