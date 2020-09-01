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
  HHAST\XHPClassNameToken $token,
): string {
  // With disable_xhp_element_mangling=true (default), the only required change
  // is the namespace separator.
  return Str\replace($token->getText(), ':', '\\');
}
