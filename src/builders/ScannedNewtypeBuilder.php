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

class ScannedNewtypeBuilder extends ScannedSingleTypeBuilder<ScannedNewtype> {
  <<__Override>>
  public function build(): ScannedNewtype {
    return new ScannedNewtype(
      $this->ast,
      $this->name,
      $this->getDefinitionContext(),
      /* attributes = */ dict[],
      $this->docblock,
    );
  }
}
