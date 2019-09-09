/*
 *  Copyright (c) 2015-present, Facebook, Inc.
 *  All rights reserved.
 *
 *  This source code is licensed under the MIT license found in the
 *  LICENSE file in the root directory of this source tree.
 *
 */

namespace Facebook\DefinitionFinder;

/**
 * Options to customize the output of ScannedTypehint::getTypeText().
 *
 * This is meant to be a flags-style enum, all values must be powers of 2.
 */
enum TypeTextOptions: int as int {
  STRIP_AUTOIMPORTED_NAMESPACE = 1;
}
