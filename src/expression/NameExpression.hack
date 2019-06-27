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

final class NameExpression extends Expression<mixed> {
  const type TNode = HHAST\NameExpression;

  <<__Override>>
  protected static function matchImpl(
    this::TNode $node,
  ): ?Expression<mixed> {
    $inner = $node->getWrappedNode();
    if (!$inner is HHAST\NameToken) {
      return null;
    }
    $text = $inner->getText();
    if ($text === 'INF') {
      return new self(\INF);
    }
    if ($text === '__LINE__') {
      return new self(0);
    }
    if ($text === '__DIR__') {
      return new self('');
    }
    if ($text === '__FILE__') {
      return new self('');
    }
    if ($text === '__FUNCTION__') {
      return new self('');
    }
    if ($text === '__CLASS__') {
      return new self('');
    }
    if ($text === '__TRAIT__') {
      return new self('');
    }
    if ($text === '__METHOD__') {
      return new self('');
    }
    if ($text === '__NAMESPACE__') {
      return new self('');
    }
    if ($text === '__COMPILER_FRONTEND__') {
      return new self('');
    }
    return null;
  }
}
