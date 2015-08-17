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
  protected \ConstVector<ScannedGeneric> $generics = Vector { };
  private \ConstVector<string> $interfaces = Vector { };
  private ?string $parent = null;

  public function setGenericTypes(
    \ConstVector<ScannedGeneric> $generics,
  ): this {
    $this->generics = $generics;
    return $this;
  }

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

  public function setParentClassName(string $parent): this {
    $this->parent = $parent;
    return $this;
  }

  public function setInterfaceNames(\ConstVector<string> $interfaces): this {
    $this->interfaces = $interfaces;
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

    $methods = $scope->getMethods();
    $properties = new Vector($scope->getProperties());

    foreach ($methods as $method) {
      if ($method->getName() === '__construct') {
        foreach ($method->getParameters() as $param) {
          if ($param->__isPromoted()) {
            // Not using the builder as we should have all the data up front,
            // and I want the typechecker to notice if we're missing something
            $properties[] = new ScannedProperty(
              $param->getPosition(),
              $param->getName(),
              $param->getAttributes(),
              $param->getDocComment(),
              $param->getTypehint(),
              $param->__getVisibility(),
              /* is static = */ false,
            );
          }
        }
        break;
      }
    }

    return /* UNSAFE_EXPR */ new $what(
      nullthrows($this->position),
      nullthrows($this->namespace).$this->name,
      nullthrows($this->attributes),
      $this->docblock,
      $methods,
      $properties,
      $scope->getConstants(),
      $this->generics,
      $this->parent,
      $this->interfaces,
    );
  }

  public function getType(): ClassDefinitionType {
    return $this->type;
  }
}
