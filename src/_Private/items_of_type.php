<?hh // strict
/*
 *  Copyright (c) 2015-present, Facebook, Inc.
 *  All rights reserved.
 *
 *  This source code is licensed under the MIT license found in the
 *  LICENSE file in the root directory of this source tree.
 *
 */

namespace Facebook\DefinitionFinder\_Private;

use namespace Facebook\HHAST;

/** Fetch all list items of a given type.
 *
 * Workaround while waiting for an HHAST release that genericizes the return
 * type.
 */
function items_of_type<T as HHAST\EditableNode>(
  ?HHAST\EditableList $list,
  classname<T> $what,
): vec<T> {
  if ($list === null) {
    return vec[];
  }
  /* HH_FIXME[4110] remove when getItemsOfType is typed in HHAST itself */
  return $list->getItemsOfType($what);
}
