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

abstract class ScannedDefinitionBuilder {
  const type TContext = ScannedDefinition::TContext;

  protected ?dict<string, vec<mixed>> $attributes;
  protected ?string $docblock;


  public function __construct(
    protected string $name,
    protected self::TContext $context,
  ) {
  }

  public function setDocComment(?string $docblock): this {
    $this->docblock = $docblock;
    return $this;
  }

  public function setAttributes(dict<string, vec<mixed>> $v): this {
    $this->attributes = $v;
    return $this;
  }

  protected function getDefinitionContext(): ScannedDefinition::TContext {
    $context = $this->context;
    $context['sourceType'] = nullthrows(Shapes::idx($context, 'sourceType'));
    return $context;
  }
}
