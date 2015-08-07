<?hh // strict
/*
 *  Copyright (c) 2015, Facebook, Inc.
 *  All rights reserved.
 *
 *  This source code is licensed under the BSD-style license found in the
 *  LICENSE file in the root directory of this source tree. An additional grant
 *  of patent rights can be found in the PATENTS file in the same directory.
 *
 */
namespace Facebook\DefinitionFinder;

function nullthrows<T>(?T $v): T {
  invariant(
    $v !== null,
    'unexpected null',
  );
  return $v;
}

// Defined in runtime in global namespace, but not in HHI
// facebook/hhvm#4872
const int T_TYPELIST_LT = 398;
const int T_TYPELIST_GT = 399;
