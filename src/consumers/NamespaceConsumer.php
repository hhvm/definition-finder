<?hh // strict
/*
 *  Copyright (c) 2015, Facebook, Inc.
 *  All rights reserved.
 *
 *  This source code is licensed under the BSD-style license found in the
 *  LICENSE file in the root directory of this source tree. An additional grant
 *  of patent rights can be found in the PATENTS file in the same directory.
 *
 */


namespace Facebook\DefinitionFinder;

class NamespaceConsumer extends Consumer {
  public function getBuilder(): ScannedNamespaceBuilder {
    $parts = [];
    do {
      $this->consumeWhitespace();
      list($next, $next_type) = $this->tq->shift();
      if ($next_type === T_STRING) {
        $parts[] = $next;
        continue;
      } else if ($next_type === T_NS_SEPARATOR) {
        continue;
      } else if ($next === '{' || $next === ';') {
        break;
      }
      invariant_violation(
        'Unexpected token %s',
        var_export($next, true),
      );
    } while ($this->tq->haveTokens());

    $ns = $parts ? (implode('\\', $parts).'\\') : '';

    $builder = (new ScannedNamespaceBuilder($ns))
      ->setContents(
        (new ScopeConsumer(
          $this->tq,
          ScopeType::NAMESPACE_SCOPE,
          $ns,
          /* aliases = */ Map{},
        ))->getBuilder()
    );
    return $builder;
  }
}
