<?hh // strict
/*
 *  Copyright (c) 2015-present, Facebook, Inc.
 *  All rights reserved.
 *
 *  This source code is licensed under the MIT license found in the
 *  LICENSE file in the root directory of this source tree.
 *
 */

namespace Facebook\DefinitionFinder;

abstract class FunctionAbstractConsumer<T as ScannedFunctionAbstract>
  extends Consumer {

  private ?string $name;

  abstract protected function constructBuilder(
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
      \preg_match('/^[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*$/', $t) === 1,
      'Expected function name at line %d',
      $tq->getLine(),
    );
    $this->name = $t;
    $name = $t;

    $builder = $this->constructBuilder($name)->setByRefReturn($by_ref_return);

    list($_, $ttype) = $tq->peek();
    $generics = vec[];
    if ($ttype === T_TYPELIST_LT) {
      $generics =
        (new GenericsConsumer($this->tq, $this->context))->getGenerics();
    }
    $builder->setGenerics($generics);
    $this->consumeParameterList($builder, $generics);

    $this->consumeWhitespace();
    list($t, $ttype) = $tq->peek();
    if ($t === ':') {
      $tq->shift();
      $this->consumeWhitespace();
      $builder->setReturnType((
        new TypehintConsumer(
          $this->tq,
          $this->getContextWithGenerics($generics),
        )
      )->getTypehint());
    }
    $this->consumeWhitespace();
    list($t, $_) = $tq->peek();
    if ($t === '{') {
      $this->skipToBlock();
      $this->consumeBlock();
    }
    return $builder;
  }

  private function consumeParameterList(
    ScannedFunctionAbstractBuilder<T> $builder,
    vec<ScannedGeneric> $generics,
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
    $inout = false;
    $variadic = false;
    $attrs = dict[];
    $doc = null;
    while ($tq->haveTokens()) {
      $ngtoken = $tq->shiftNG();
      list($t, $ttype) = $ngtoken->asLegacyToken();

      if ($t === ')') {
        break;
      }

      if ($t === '&') {
        $byref = true;
        continue;
      }
      if ($ttype === \T_ELLIPSIS) {
        $variadic = true;
        invariant(
          !$have_variadic,

          'multiple variadics at line %d',
          $tq->getLine(),
        );
        $have_variadic = true;
        continue;
      }

      if ($ttype === T_INOUT) {
        $inout = true;
        continue;
      }

      if ($ttype === \T_VARIABLE) {
        $default = $this->consumeDefaultValue();
        $name = \substr($t, 1); // remove '$'
        invariant(
          $variadic || !$have_variadic,
          'non-variadic parameter after variadic at line %d',
          $tq->getLine(),
        );
        invariant(
          !($inout && $byref),
          "parameters can not be both inout and byref at line %d",
          $tq->getLine(),
        );
        $builder->addParameter(
          (new ScannedParameterBuilder($name, $this->getBuilderContext()))
            ->setTypehint($param_type)
            ->setIsPassedByReference($byref)
            ->setIsInOut($inout)
            ->setIsVariadic($variadic)
            ->setDefaultString($default)
            ->setVisibility($visibility)
            ->setAttributes($attrs)
            ->setDocComment($doc),
        );
        $param_type = null;
        $visibility = null;
        $byref = false;
        $inout = false;
        $variadic = false;
        $attrs = dict[];
        $doc = null;
        continue;
      }

      if ($ttype === \T_WHITESPACE || $t === ',' || $ttype === \T_COMMENT) {
        continue;
      }

      if (VisibilityToken::isValid($ttype)) {
        invariant(
          $this->name === '__construct',
          'Saw %s for a non-constructor function parameter at line %d',
          \token_name($ttype),
          $tq->getLine(),
        );
        $visibility = VisibilityToken::assert($ttype);
        continue;
      }

      if ($ttype === \T_SL) {
        $attrs = (new UserAttributesConsumer($this->tq, $this->context))
          ->getUserAttributes();
        continue;
      }

      if ($ttype === \T_DOC_COMMENT) {
        $doc = $t;
        continue;
      }

      invariant(
        $param_type === null,
        'found two things that look like typehints for the same parameter '.
        'at line %d - previous is "%s"',
        $tq->getLine(),
        $param_type->getTypeText(),
      );
      $tq->unshiftNG($ngtoken);
      $param_type = (
        new TypehintConsumer(
          $this->tq,
          $this->getContextWithGenerics($generics),
        )
      )->getTypehint();
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
    while ($this->tq->haveTokens()) {
      $this->consumeWhitespace();
      list($t, $_) = $this->tq->peek();

      if ($nesting === 0) {
        if ($t === ',' || $t === ')') {
          break;
        }
      }
      $this->tq->shift();

      $default .= $t;
      if ($t === '(' || $t === '[' || $t === '{') {
        $nesting++;
        continue;
      }

      if ($t === ')' || $t === ']' || $t === '}') {
        $nesting--;
        continue;
      }
    }

    return $default;
  }
}
