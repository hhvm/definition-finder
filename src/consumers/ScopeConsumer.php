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

class ScopeConsumer extends Consumer {
  protected function getEmptyBuilder(): ScannedScopeBuilder{
    return new ScannedScopeBuilder();
  }

  public function getBuilder(): ScannedScopeBuilder {
    $builder = (new ScannedScopeBuilder())->setNamespace('');
    $attrs = Map { };
    $docblock = null;

    $tq = $this->tq;
    $parens_depth = 0;
    while ($tq->haveTokens()) {
      list ($token, $ttype) = $tq->shift();
      if ($token === '(') {
        ++$parens_depth;
      }
      if ($token === ')') {
        --$parens_depth;
      }

      if ($parens_depth !== 0 || $ttype === null) {
        continue;
      }

      if ($ttype === T_CLOSE_TAG) { /* ?> ... <?php */
        $this->consumeNonCode();
      }

      if ($ttype === T_SL) {
        $attrs = $this->consumeUserAttributes();
        continue;
      }

      if ($ttype === T_DOC_COMMENT) {
        $docblock = $token;
        continue;
      }

      if (DefinitionType::isValid($ttype)) {
        $this->consumeDefinition(
          $builder,
          DefinitionType::assert($ttype),
          $attrs,
          $docblock,
        );
        $attrs = Map { };
        $docblock = null;
        continue;
      }

      // I hate you, PHP.
      if ($ttype === T_STRING && strtolower($token) === 'define') {
        $builder->addConstant((new DefineConsumer($tq))->getBuilder());
        continue;
      }
    }

    return $builder;
  }

  private function consumeDefinition(
    ScannedScopeBuilder $builder,
    DefinitionType $def_type,
    AttributeMap $attrs,
    ?string $docblock,
   ): void {
    $this->consumeWhitespace();

    switch ($def_type) {
      case DefinitionType::NAMESPACE_DEF:
        $builder->addNamespace((new NamespaceConsumer($this->tq))->getBuilder());
        return;
      case DefinitionType::CLASS_DEF:
      case DefinitionType::INTERFACE_DEF:
      case DefinitionType::TRAIT_DEF:
        $builder->addClass(
          (new ClassConsumer(ClassDefinitionType::assert($def_type), $this->tq))
            ->getBuilder()
            ->setAttributes($attrs)
            ->setDocComment($docblock)
        );
        return;
      case DefinitionType::FUNCTION_DEF:
        $fb = (new FunctionConsumer($this->tq))
          ->getBuilder();
        if ($fb) {
          $builder->addFunction(
            $fb
              ->setAttributes($attrs)
              ->setDocComment($docblock)
          );
        }
        return;
      case DefinitionType::CONST_DEF:
        $builder->addConstant(
          (new ConstantConsumer($this->tq))
          ->getBuilder()
          ->setDocComment($docblock)
        );
        return;
      case DefinitionType::TYPE_DEF:
      case DefinitionType::NEWTYPE_DEF:
      case DefinitionType::ENUM_DEF:
        $this->consumeSimpleDefinition($builder, $def_type, $docblock);
        return;
    }
  }

  private function consumeUserAttributes(): AttributeMap {
    $attrs = Map { };
    while (true) {
      list($name, $_) = $this->tq->shift();
      if (!$attrs->containsKey($name)) {
        $attrs[$name] = Vector { };
      }

      list($t, $ttype) = $this->tq->shift();
      if ($ttype === T_SR) { // this was the last attribute
        return $attrs;
      }
      if ($t === ',') { // there's another
        continue;
      }

      // this attribute has values
      invariant(
        $t === '(',
        'Expected attribute name to be followed by >>, (, or ,',
      );

      while (true) {
        list($value, $ttype) = $this->tq->shift();
        switch ((int) $ttype) {
          case T_CONSTANT_ENCAPSED_STRING:
            $value = substr($value, 1, -1);
            break;
          case T_LNUMBER:
            $value = (int) $value;
            break;
          default:
            invariant_violation(
              "Invalid attribute value token type: %d",
              $ttype
            );
        }
        $attrs[$name][] = $value;
        list($t, $_) = $this->tq->shift();
        if ($t === ')') {
          break;
        }
        invariant($t === ',', 'Expected attribute value to be followed by , or )');
      }
      list($t, $ttype) = $this->tq->shift();
      if ($ttype === T_SR) {
        return $attrs;
      }
      invariant(
        $t === ',',
        'Expected attribute value list to be followed by >> or ,',
      );
    }
  }

  private function consumeSimpleDefinition(
    ScannedScopeBuilder $builder,
    DefinitionType $def_type,
    ?string $docblock,
  ): void {
    list($name, $ttype) = $this->tq->shift();
    invariant(
      $ttype=== T_STRING,
      'Expected a string for %s, got %d',
      token_name($def_type),
      $ttype,
    );
    switch ($def_type) {
      case DefinitionType::TYPE_DEF:
        $builder->addType(
          (new ScannedTypeBuilder($name))->setDocComment($docblock)
        );
        break;
      case DefinitionType::NEWTYPE_DEF:
        $builder->addNewtype(
          (new ScannedNewtypeBuilder($name))->setDocComment($docblock)
        );
        break;
      case DefinitionType::ENUM_DEF:
        $builder->addEnum(
          (new ScannedEnumBuilder($name))->setDocComment($docblock)
        );
        $this->skipToBlock();
        $this->consumeBlock();
        return;
      default:
        invariant_violation(
          '%d is not a simple definition',
          $def_type,
        );
    }
    $this->consumeStatement();
  }

  /** ?> ... <?php */
  private function consumeNonCode(): void {
    do {
      list ($_, $ttype) = $this->tq->shift();
    } while ($this->tq->haveTokens() && $ttype !== T_OPEN_TAG);
  }
}
