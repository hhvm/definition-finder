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
    private ClassDefinitionType $type,
    TokenQueue $tq,
  ) {
    parent::__construct($tq);
  }

  public function getBuilder(): ScannedClassBuilder {
    list($v, $t) = $this->tq->shift();

    if ($t === T_STRING) {
      $name = $v;
    } else {
      invariant(
        $t === T_XHP_LABEL,
        'Unknown class token %d',
        token_name($t),
      );
      invariant(
        $this->type === DefinitionType::CLASS_DEF,
        'Seeing an XHP class name for a %s',
        token_name($this->type),
      );
      // 'class :foo:bar' is really 'class xhp_foo__bar'
      $name = 'xhp_'.str_replace(':', '__', substr($v, 1));
    }

    list($_, $ttype) = $this->tq->peek();
    $generics = Vector { };
    if ($ttype == T_TYPELIST_LT) {
      $generics = (new GenericsConsumer($this->tq))->getGenerics();
    }

    $this->skipToBlock();
    return (new ScannedClassBuilder($this->type, $name))
      ->setContents(
        (new ScopeConsumer($this->tq, ScopeType::CLASS_SCOPE))
        ->getBuilder()
      )
      ->setGenericTypes($generics);
  }
}
