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

/** Shape containing information required to accurately understand the
 * definitions found in the AST */
type ConsumerContext = shape(
  'definitionContext' => ScannedDefinition::TContext,
  'scopeType' => ScopeType,
  'ast' => HHAST\Script,
  'namespace' => ?string,
);
