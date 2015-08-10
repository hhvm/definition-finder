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

final class ScannedClassBuilder extends ScannedBaseBuilder {
  private ?ScannedScopeBuilder $scopeBuilder;

  public function __construct(
    private ClassDefinitionType $type,
    string $name,
  ) {
    parent::__construct($name);
  }

  public function setContents(ScannedScopeBuilder $scope): this {
    invariant($this->scopeBuilder === null, 'class already has a scope');
    $this->scopeBuilder = $scope;
    return $this;
  }

  // Can be safe in 3.9, assuming D2311514 is cherry-picked
  // public function build<T as ScannedClass>(classname<T> $what): T {
  public function build<T as ScannedClass>(string $what): T {
    {
      // UNSAFE
      ClassDefinitionType::assert($what::getType());
      invariant(
        $this->type === $what::getType(),
        "Can't build a %s for a %s",
        $what,
        token_name($this->type),
      );
    }

    $scope = nullthrows($this->scopeBuilder)
      ->setPosition(nullthrows($this->position))
      ->setNamespace('')
      ->build();

    $methods = $scope->getFunctions()->map(
      $f ==> new ScannedMethod(
        $f->getPosition(),
        $f->getName(),
        $f->getAttributes(),
        $f->getDocComment(),
        $f->getGenericTypes(),
        $f->getReturnType(),
        $f->getParameters(),
      )
    );

    return /* UNSAFE_EXPR */ new $what(
      nullthrows($this->position),
      nullthrows($this->namespace).$this->name,
      nullthrows($this->attributes),
      $this->docblock,
      $methods,
    );
  }

  public function getType(): ClassDefinitionType {
    return $this->type;
  }
}
