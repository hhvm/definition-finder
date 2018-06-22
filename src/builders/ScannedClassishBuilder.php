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

use namespace Facebook\HHAST;

final class ScannedClassishBuilder extends ScannedDefinitionBuilder {
  private ?ScannedScope $scope;
  protected vec<ScannedGeneric> $generics = vec[];
  private vec<ScannedTypehint> $interfaces = vec[];
  private ?ScannedTypehint $parent = null;
  private ?AbstractnessToken $abstractness;
  private ?FinalityToken $finality;

  public function setGenericTypes(
    vec<ScannedGeneric> $generics,
  ): this {
    $this->generics = $generics;
    return $this;
  }

  public function __construct(
    HHAST\EditableNode $ast,
    string $name,
    self::TContext $context,
    private ClassDefinitionType $type,
  ) {
    parent::__construct($ast, $name, $context);
  }

  public function setContents(ScannedScope $scope): this {
    invariant($this->scope === null, 'class already has a scope');
    $this->scope = $scope;
    return $this;
  }

  public function setParentClassInfo(ScannedTypehint $parent): this {
    $this->parent = $parent;
    return $this;
  }

  public function setInterfaces(
    vec<ScannedTypehint> $interfaces,
  ): this {
    $this->interfaces = $interfaces;
    return $this;
  }

  public function setAbstractness(AbstractnessToken $abstractness): this {
    $this->abstractness = $abstractness;
    return $this;
  }

  public function setFinality(FinalityToken $finality): this {
    $this->finality = $finality;
    return $this;
  }

  public function build<T as ScannedClassish>(classname<T> $what): T {
    ClassDefinitionType::assert($what::getType());
    invariant(
      $this->type === $what::getType(),
      "Can't build a %s for a %s",
      $what,
      \token_name($this->type),
    );

    $scope = nullthrows($this->scope);

    $methods = $scope->getMethods();
    $properties = vec($scope->getProperties());

    foreach ($methods as $method) {
      if ($method->getName() === '__construct') {
        foreach ($method->getParameters() as $param) {
          if ($param->__isPromoted()) {
            // Not using the builder as we should have all the data up front,
            // and I want the typechecker to notice if we're missing something
            $properties[] = new ScannedProperty(
              $param->getAST(),
              $param->getName(),
              $param->getContext(),
              $param->getAttributes(),
              $param->getDocComment(),
              $param->getTypehint(),
              $param->__getVisibility(),
              StaticityToken::NOT_STATIC,
            );
          }
        }
        break;
      }
    }

    return new $what(
      $this->ast,
      $this->name,
      $this->getDefinitionContext(),
      nullthrows($this->attributes),
      $this->docblock,
      $methods,
      $properties,
      $scope->getConstants(),
      $scope->getTypeConstants(),
      $this->generics,
      $this->parent,
      $this->interfaces,
      $scope->getUsedTraits(),
      nullthrows($this->abstractness),
      nullthrows($this->finality),
    );
  }

  public function getType(): ClassDefinitionType {
    return $this->type;
  }
}
