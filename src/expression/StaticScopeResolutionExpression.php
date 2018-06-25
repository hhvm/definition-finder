<?hh // strict
/*
 *  Copyright (c) 2015-present, Facebook, Inc.
 *  All rights reserved.
 *
 *  This source code is licensed under the MIT license found in the
 *  LICENSE file in the root directory of this source tree.
 *
 */

namespace Facebook\DefinitionFinder\Expression;

use namespace Facebook\HHAST;
use namespace HH\Lib\Str;

/** Support for `classname<T>` */
final class StaticScopeResolutionExpression extends Expression<string> {
  const type TNode = HHAST\ScopeResolutionExpression;
  <<__Override>>
  protected static function matchImpl(
    HHAST\ScopeResolutionExpression $n,
  ): ?Expression<string> {
    $name = $n->getName();
    if (!$name instanceof HHAST\ClassToken) {
      return null;
    }
    $name = \Facebook\DefinitionFinder\name_from_ast($n->getQualifier());
    if ($name === null) {
      return null;
    }
    return new self($name);
  }
}