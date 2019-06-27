/*
 *  Copyright (c) 2015-present, Facebook, Inc.
 *  All rights reserved.
 *
 *  This source code is licensed under the MIT license found in the
 *  LICENSE file in the root directory of this source tree.
 *
 */

namespace Facebook\DefinitionFinder\Expression;

use namespace Facebook\{HHAST, TypeAssert};

abstract class Expression<+TValue> {
  abstract const type TNode as HHAST\Node;

  final protected function __construct(private TValue $value) {
  }

  final public static function match(
    HHAST\Node $node,
  ): ?Expression<TValue> {
    $ts = type_structure(static::class, 'TNode');
    try {
      $node = TypeAssert\matches_type_structure($ts, $node);
    } catch (\Throwable $_) {
      return null;
    }
    return static::matchImpl($node);
  }

  abstract protected static function matchImpl(
    this::TNode $node,
  ): ?Expression<TValue>;

  final public function getValue(): TValue {
    return $this->value;
  }
}
