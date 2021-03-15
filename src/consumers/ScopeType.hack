/*
 *  Copyright (c) 2015-present, Facebook, Inc.
 *  All rights reserved.
 *
 *  This source code is licensed under the MIT license found in the
 *  LICENSE file in the root directory of this source tree.
 *
 */

namespace Facebook\DefinitionFinder;

enum ScopeType: string {
  FILE_SCOPE = 'file';
  NAMESPACE_SCOPE = 'ns';
  CLASSISH_SCOPE = 'classish';
}
