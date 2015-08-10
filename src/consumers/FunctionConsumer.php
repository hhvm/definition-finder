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

class FunctionConsumer extends Consumer {
  public function getBuilder(): ?ScannedFunctionBuilder {
    $by_ref_return = false;

    $tq = $this->tq;
    list($t, $ttype) = $tq->shift();

    if ($t === '&') {
      $by_ref_return = true;
      $this->consumeWhitespace();
      list($t, $ttype) = $tq->shift();
    }

    if ($t === '(') {
      // rvalue, eg '$x = function() { }'
      $this->consumeStatement();
      return null;
    }

    invariant($ttype === T_STRING, 'Expected function name');
    $name = $t;
 
    list($_, $ttype) = $tq->peek();
    $generics = Vector { };
    if ($ttype === T_TYPELIST_LT) {
      $generics = $this->consumeGenerics();
    }
    $params = $this->consumeParameterList();

    $this->consumeWhitespace();
    list($t, $ttype) = $tq->peek();
    $return_type = null;
    if ($t === ':') {
      $tq->shift();
      $this->consumeWhitespace();
      $return_type = $this->consumeType();
    }

    return (new ScannedFunctionBuilder($name))
      ->setByRefReturn($by_ref_return)
      ->setGenerics($generics)
      ->setReturnType($return_type);
  }

  private function consumeParameterList(
  ): \ConstVector<(?ScannedTypehint, string)> {
    $tq = $this->tq;
    list($t, $ttype) = $tq->shift();
    invariant($t === '(', 'expected parameter list, got %s', $t);

    $params = Vector { };
    $visibility = null;
    $param_type = null;
    while ($tq->haveTokens()) {
      list($t, $ttype) = $tq->shift();

      if ($t === ')') {
        break;
      }

      if ($ttype === T_VARIABLE) {
        $params[] = tuple($param_type, $t);
        $param_type = null;
        $visibility = null;
        continue;
      }

      if ($ttype === T_WHITESPACE || $t === ',') {
        continue;
      }

      if (
        $ttype === T_PRIVATE
        || $ttype === T_PUBLIC
        || $ttype === T_PROTECTED
      ) {
        $visibility = $ttype;
        continue;
      }
      
      invariant(
        $param_type === null,
        'found two things that look like typehints for the same parameter',
      );
      $tq->unshift($t, $ttype);
      $param_type = $this->consumeType();
    }
    return $params;
  }

  private function consumeType(): ScannedTypehint {
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

      // Handle functions
      if ($t === '(') {
        ++$nesting;
        continue;
      }
      if ($t === ')') {
        --$nesting;
        if ($nesting === 0) {
          break;
        }
        continue;
      }

      if ($ttype !== T_STRING) {
        continue;
      }

      $type = $t;

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
            'expected > or , after generic type at line %d',
            $this->tq->getLine(),
          );
        }
        break;
      }
      return new ScannedTypehint($type, $generics);
    }
    invariant($type !== null, 'expected a type at line %d', $this->tq->getLine());
    return new ScannedTypehint($type, $generics);
  }

  private function consumeGenerics(): \ConstVector<ScannedGeneric> {
    $tq = $this->tq;
    list($t, $ttype) = $tq->shift();
    invariant($ttype = T_TYPELIST_LT, 'Consuming generics, but not a typelist');

    $ret = Vector { };

    $name = null;
    $constraint = null;

    while ($tq->haveTokens()) {
      list($t, $ttype) = $tq->shift();

      invariant(
        $ttype !== T_TYPELIST_LT,
        "nested generic type",
      );

      if ($ttype === T_WHITESPACE) {
        continue;
      }

      if ($ttype === T_TYPELIST_GT) {
        if ($name !== null) {
          $ret[] = new ScannedGeneric($name, $constraint);
        }
        return $ret;
      }

      if ($t === ',') {
        $ret[] = new ScannedGeneric(nullthrows($name), $constraint);
        $name = null;
        $constraint = null;
        continue;
      }

      if ($name === null) {
        invariant($ttype === T_STRING, 'expected type variable name');
        $name = $t;
        continue;
      }

      if ($ttype === T_AS) {
        continue;
      }

      invariant($ttype === T_STRING, 'expected type constraint');
      $constraint = $t;
    }
    invariant_violation('never reached end of generics definition');
  }
}
