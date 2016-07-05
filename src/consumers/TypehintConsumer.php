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

final class TypehintConsumer extends Consumer {
  public function getTypehint(): ScannedTypehint {
    return $this->consumeType();
  }

  private function consumeType(): ScannedTypehint {
    $nullable = false;
    $type = null;
    $generics = Vector { };

    $nesting = 0;
    while ($this->tq->haveTokens()) {
      list($t, $ttype) = $this->tq->shift();

      if ($ttype === T_WHITESPACE) {
        if ($nesting === 0) {
          break;
        }
        continue;
      }

      if ($nesting !== 0) {
        $type .= $t;
        if ($t === '(') {
          ++$nesting;
        }
      }

      if ($ttype === T_SHAPE) {
        $type = $t;
        continue;
      }

      // Handle functions
      if ($t === '(' && $nesting === 0) {
        $this->consumeWhitespace();
        list($t, $ttype) = $this->tq->peek();
        if ($ttype === T_FUNCTION) {
          $type = '(';
          ++$nesting;
          continue;
        }

        if ($type !== null) {
          $type .= '(';
          ++$nesting;
          continue;
        }

        $type = 'tuple';
        while ($this->tq->haveTokens()) {
          $this->consumeWhitespace();
          $generics[] = $this->consumeType();
          $this->consumeWhitespace();

          list($t, $_) = $this->tq->shift();
          if ($t === ')') {
            break;
          }
          invariant(
            $t === ',',
            'expected ) or , after tuple member at line %d',
            $this->tq->getLine(),
          );
        }
        break;
      }

      if ($t === ')') {
        --$nesting;
        if ($nesting === 0) {
          break;
        }
        continue;
      }

      if ($ttype === null && $t === '?') {
        $nullable = true;
      }

      if (
        $ttype !== T_STRING
        && $ttype !== T_NS_SEPARATOR
        && $ttype !== T_CALLABLE
        && $ttype !== T_ARRAY
        && $ttype !== T_XHP_LABEL
        && !StringishTokens::isValid($ttype)
      ) {
        continue;
      }

      if ($nesting !== 0) {
        continue;
      }

      if ($ttype === T_XHP_LABEL) {
        $t = normalize_xhp_class($t);
      }

      $type = $t;
      // Handle \foo
      if ($ttype === T_NS_SEPARATOR) {
        list($t, $_) = $this->tq->shift();
        $type .= $t;
      }

      // Handle \foo\bar and foo\bar
      while ($this->tq->haveTokens()) {
        list($_, $ttype) = $this->tq->peek();
        if ($ttype !== T_NS_SEPARATOR) {
          break;
        }
        $this->tq->shift();
        $type .= "\\";
        list($t, $_) = $this->tq->shift();
        $type .= $t;
      }

      // consume generics and recurse
      $this->consumeWhitespace();
      list($t, $ttype) = $this->tq->peek();
      if ($ttype === T_TYPELIST_LT) {
        $this->tq->shift();
        while ($this->tq->haveTokens()) {
          $this->consumeWhitespace();
          $generics[] = $this->consumeType();
          $this->consumeWhitespace();

          list($t, $ttype) = $this->tq->shift();
          if ($ttype === T_TYPELIST_GT) {
            break;
          }
          invariant(
            $t === ',',
            'expected > or , after generic type',
          );
        }
        break;
      }
      break;
    }
    $type = $this->unaliasName($type);
    invariant($type !== null, 'expected a type');
    return new ScannedTypehint($type, $generics, $nullable);
  }
}
