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

function mangle_xhp_name_token(
  ConsumerContext $context,
  HHAST\XHPClassNameToken $token,
): string {
  return $token->getText()
    |> Str\strip_prefix($$, ':')
    |> Str\replace_every($$, dict[':' => '__', '-' => '_'])
    |> 'xhp_'.$$
    |> name_in_context($context, $$);
}
