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

/** Placeholder to not have hack errors */
final class TokenQueue {
  public function getState(): mixed {
    return null;
  }

  public function restoreState(mixed $_): void{
  }

  public function haveTokens(): bool {
    return false;
  }

  public function peek(): (string, arraykey) {
    invariant_violation("Don't call me, I'll call you");
  }

  public function shift(): (string, arraykey) {
    invariant_violation("Don't call me, I'll call you");
  }
}
