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

enum ClassDefinitionType: DefinitionType {
  CLASS_DEF = DefinitionType::CLASS_DEF;
  INTERFACE_DEF = DefinitionType::INTERFACE_DEF;
  TRAIT_DEF = DefinitionType::TRAIT_DEF;
}

final class ClassConsumer extends Consumer {
  public function __construct(
    TokenQueue $tq,
    self::TContext $context,
    private ClassDefinitionType $type,
  ) {
    parent::__construct($tq, $context);
  }

  public function getBuilder(): ScannedClassBuilder {
    list($v, $t) = $this->tq->shift();

    if ($t === T_STRING || StringishTokens::isValid($t)) {
      $name = $v;
    } else {
      invariant(
        $t === T_XHP_LABEL,
        'Unknown class token %d',
        token_name($t),
      );
      invariant(
        DefinitionType::coerce($this->type) === DefinitionType::CLASS_DEF,
        'Seeing an XHP class name for a %s',
        token_name($this->type),
      );
      // 'class :foo:bar' is really 'class xhp_foo__bar'
      $name = normalize_xhp_class($v);
    }

    $builder = new ScannedClassBuilder(
      $this->type,
      $this->normalizeName($name, NameNormalizationMode::DEFINITION),
    );

    list($_, $ttype) = $this->tq->peek();
    if ($ttype == T_TYPELIST_LT) {
      $builder->setGenericTypes(
        (new GenericsConsumer($this->tq, $this->context))
          ->getGenerics(),
      );
    }

    while ($this->tq->haveTokens()) {
      list($t, $ttype) = $this->tq->shift();
      if ($t === '{') {
        break;
      }

      if ($ttype === T_EXTENDS) {
        $classes = $this->consumeClassList();
        if ($this->type === ClassDefinitionType::INTERFACE_DEF) {
          $builder->setInterfaces($classes);
        } else {
          invariant(
            count($classes) === 1,
            'only interfaces can have more than 1 parent at line %d',
            $this->tq->getLine(),
          );
          $builder->setParentClassInfo($classes[0]);
        }
        continue;
      }

      if ($ttype === T_IMPLEMENTS) {
        invariant(
          $this->type !== ClassDefinitionType::INTERFACE_DEF,
          'interfaces can not implement interfaces at line %d',
          $this->tq->getLine(),
        );
        $builder->setInterfaces($this->consumeClassList());
      }
    }

    return $builder
      ->setContents(
        (new ScopeConsumer(
          $this->tq,
          $this->context,
          ScopeType::CLASS_SCOPE,
        ))
        ->getBuilder()
      );
  }

  private function consumeClassList(): \ConstVector<ScannedTypehint> {
    $classes = Vector { };
    while ($this->tq->haveTokens()) {
      $this->consumeWhitespace();
      list ($t, $ttype) = $this->tq->peek();
      if ($t === ',') {
        $this->tq->shift();
        continue;
      }

      if ($t === '{' || $ttype === T_IMPLEMENTS || $ttype === T_EXTENDS) {
        break;
      }

      $classes[] = (new TypehintConsumer($this->tq, $this->context))
        ->getTypehint();
    }
    return $classes;
  }
}
