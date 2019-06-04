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

final class ScannedExpression {
  public function __construct(
    private HHAST\Node $ast,
    private Option<mixed> $staticValue,
  ) {
  }

  public function getAST(): HHAST\Node {
    return $this->ast;
  }

  public function hasStaticValue(): bool {
    return $this->staticValue->isSome();
  }

  public function getStaticValue(): mixed {
    return $this->staticValue->getValue();
  }
}
