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

class FunctionConsumer extends FunctionishConsumer<ScannedFunction> {
  <<__Override>>
  protected function constructBuilder(string $name): ScannedFunctionBuilder {
    return new ScannedFunctionBuilder(
      $this->normalizeName($name),
      $this->getBuilderContext(),
    );
  }
}
