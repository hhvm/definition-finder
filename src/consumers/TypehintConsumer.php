<?hh // strict
/*
 *  Copyright (c) 2015-present, Facebook, Inc.
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
    $type_text = null;
    $type_name  = null;
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

      if ($type_text === null) {
        if ($ttype === T_SHAPE) {
          $type_name = 'shape';
        } else if ($t === '(') {
          list ($_, $pttype) = $this->tq->peek();
          if ($pttype === T_FUNCTION) {
            $type_name = 'callable';
          } else {
            $type_name = 'tuple';
          }
        }
      }

      $type_text .= $t;

      if ($t === '(') {
        ++$nesting;
        continue;
      } else if ($t === ')') {
        if (substr($type_text, -2) === ',)') {
          $type_text = substr($type_text, 0, -2).')';
        }
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

      $type_text = $t;
      // Handle \foo
      if ($ttype === T_NS_SEPARATOR) {
        list($t, $_) = $this->tq->shift();
        $type_text .= $t;
      }

      // Handle \foo\bar and foo\bar
      while ($this->tq->haveTokens()) {
        list($_, $ttype) = $this->tq->peek();

        // Handle \foo\bar::TYPE, or self::SOME_CLASS_TYPE::SOME_TYPE_CONST
        if ($ttype === T_DOUBLE_COLON) {
          list($tDoubleColon, $_) = $this->tq->shift();
          list($tConstant, $_) = $this->tq->shift();
          $type_text = $type_text . $tDoubleColon . $tConstant;
          continue;
        }

        if ($ttype !== T_NS_SEPARATOR) {
          break;
        }
        $this->tq->shift();
        $type_text .= "\\";
        list($t, $_) = $this->tq->shift();
        $type_text .= $t;
      }

      // consume generics and recurse
      $this->consumeWhitespace();
      list($t, $ttype) = $this->tq->peek();
      if ($ttype === T_TYPELIST_LT) {
        $this->tq->shift();
        $this->consumeWhitespace();
        while ($this->tq->haveTokens()) {
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
          $this->consumeWhitespace();
          // Trailing comma
          list($_, $ttype) = $this->tq->peek();
          if ($ttype === T_TYPELIST_GT) {
            $this->tq->shift();
            break;
          }
        }
        break;
      }
      break;
    }
    invariant($type_text !== null, "Didn't see anything that looked like a type");
    $type_text = $this->normalizeName($type_text);
    return new ScannedTypehint(
      $type_name ?? $type_text,
      $type_text,
      $generics,
      $nullable,
    );
  }
}
