/*
 *  Copyright (c) 2015-present, Facebook, Inc.
 *  All rights reserved.
 *
 *  This source code is licensed under the MIT license found in the
 *  LICENSE file in the root directory of this source tree.
 *
 */

namespace Facebook\DefinitionFinder;

<<__Sealed(Some::class, None::class)>>
interface Option<T> {
  public function isSome(): bool;
  public function isNone(): bool;
  public function getValue(): T;
}

final class Some<T> implements Option<T> {
  public function __construct(private T $value) {
  }

  public function isSome(): bool {
    return true;
  }

  public function isNone(): bool {
    return false;
  }

  public function getValue(): T {
    return $this->value;
  }
}

function Some<T>(T $v): Some<T> {
  return new Some($v);
}

final class None<T> implements Option<T> {
  public function isSome(): bool {
    return false;
  }

  public function isNone(): bool {
    return true;
  }

  public function getValue(): T {
    invariant_violation('%s called on %s', __METHOD__, __CLASS__);
  }
}

function None<T>(): None<T> {
  return new None();
}
