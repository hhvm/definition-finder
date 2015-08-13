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

abstract class FunctionAbstractConsumer<T as ScannedFunctionAbstract>
  extends Consumer {

  private ?string $name;

  abstract protected static function ConstructBuilder(
    string $name,
  ): ScannedFunctionAbstractBuilder<T>;

  public function getBuilder(): ?ScannedFunctionAbstractBuilder<T> {
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

    /* Regex taken from http://php.net/manual/en/functions.user-defined.php
     *
     * Some things other than T_STRING are valid, eg 'function select() {}' has
     * a T_SELECT
     */
    invariant(
      preg_match('/^[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*$/', $t) === 1,
      'Expected function name at line %d',
      $tq->getLine(),
    );
    $this->name = $t;
    $name = $t;

    $builder = static::ConstructBuilder($name)
      ->setByRefReturn($by_ref_return);
 
    list($_, $ttype) = $tq->peek();
    $generics = Vector { };
    if ($ttype === T_TYPELIST_LT) {
      $generics = (new GenericsConsumer($tq))->getGenerics();
    }
    $builder->setGenerics($generics);
    $this->consumeParameterList($builder);

    $this->consumeWhitespace();
    list($t, $ttype) = $tq->peek();
    if ($t === ':') {
      $tq->shift();
      $this->consumeWhitespace();
      $builder->setReturnType((new TypehintConsumer($this->tq))->getTypehint());
    }
    return $builder;
  }

  private function consumeParameterList(
    ScannedFunctionAbstractBuilder<T> $builder,
  ): void {
    $this->consumeWhitespace();
    $tq = $this->tq;
    list($t, $ttype) = $tq->shift();
    invariant(
      $t === '(',
      'expected parameter list, got "%s" (%d) at line %d',
      $t,
      $ttype,
      $this->tq->getLine(),
    );

    $have_variadic = false;
    $visibility = null;
    $param_type = null;
    $byref = false;
    $variadic = false;
    $attrs = Map { };
    $doc = null;
    while ($tq->haveTokens()) {
      list($t, $ttype) = $tq->shift();

      if ($t === ')') {
        break;
      }

      if ($t === '&') {
        $byref = true;
        continue;
      }
      if ($ttype === T_ELLIPSIS) {
        $variadic = true;
        invariant(
          !$have_variadic,
          'multiple variadics at line %d',
          $tq->getLine(),
        );
        $have_variadic = true;
        continue;
      }

      if ($ttype === T_VARIABLE) {
        $default = $this->consumeDefaultValue();
        $name = substr($t, 1); // remove '$'
        invariant(
          $variadic || !$have_variadic,  
          'non-variadic parameter after variadic at line %d',
          $tq->getLine(),
        );
        $builder->addParameter(
          (new ScannedParameterBuilder($name))
          ->setTypehint($param_type)
          ->setIsPassedByReference($byref)
          ->setIsVariadic($variadic)
          ->setDefaultString($default)
          ->setVisibility($visibility)
          ->setAttributes($attrs)
          ->setDocComment($doc)
        );
        $param_type = null;
        $visibility = null;
        $byref = false;
        $variadic = false;
        $attrs = Map { };
        $doc = null;
        continue;
      }

      if ($ttype === T_WHITESPACE || $t === ',' || $ttype === T_COMMENT) {
        continue;
      }

      if (VisibilityToken::isValid($ttype)) {
        invariant(
          $this->name === '__construct',
          'Saw %s for a non-constructor function parameter at line %d',
          token_name($ttype),
          $tq->getLine(),
        );
        $visibility = VisibilityToken::assert($ttype);
        continue;
      }

      if ($ttype === T_SL) {
        $attrs = (new UserAttributesConsumer($this->tq))->getUserAttributes();
        continue;
      }

      if ($ttype === T_DOC_COMMENT) {
        $doc = $t;
        continue;
      }
      
      invariant(
        $param_type === null,
        'found two things that look like typehints for the same parameter '.
        'at line %d',
        $tq->getLine(),
      );
      $tq->unshift($t, $ttype);
      $param_type = (new TypehintConsumer($this->tq))->getTypehint();
    }
  }

  private function consumeDefaultValue(): ?string {
    $this->consumeWhitespace();
    list($t, $_) = $this->tq->peek();
    if ($t !== '=') {
      return null;
    }

    $this->tq->shift();
    $nesting = 0;
    $default = '';
    while($this->tq->haveTokens()) {
      $this->consumeWhitespace();
      list($t, $_) = $this->tq->peek();

      if ($nesting === 0) {
        if ($t === ',' || $t === ')') {
          break;
        }
      }
      $this->tq->shift();

      $default .= $t;
      if ($t === '(') {
        $nesting++;
        continue;
      }

      if ($t === ')') {
        $nesting--;
        if ($nesting === 0) {
          break;
        }
        continue;
      }
    }

    return $default;
  }
}
