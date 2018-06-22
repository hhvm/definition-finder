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

final class ScannedFunctionBuilder
  extends ScannedFunctionishBuilder<ScannedFunction> {

  <<__Override>>
  public function build(): ScannedFunction {
    return new ScannedFunction(
      $this->ast,
      $this->name,
      $this->getDefinitionContext(),
      nullthrows($this->attributes),
      $this->docblock,
      nullthrows($this->generics),
      $this->returnType,
      nullthrows($this->parameters),
    );
  }
}
