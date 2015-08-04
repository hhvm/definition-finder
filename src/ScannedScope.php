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

class ScannedScope extends ScannedBase {

  public function __construct(
    private SourcePosition $position,
    private \ConstVector<ScannedBasicClass> $classes,
    private \ConstVector<ScannedInterface> $interfaces,
    private \ConstVector<ScannedTrait> $traits,
    private \ConstVector<ScannedFunction> $functions,
    private \ConstVector<ScannedConstant> $constants,
    private \ConstVector<ScannedEnum> $enums,
    private \ConstVector<ScannedType> $types,
    private \ConstVector<ScannedNewtype> $newtypes,
  ) {
    parent::__construct(
      $position,
      '__SCOPE__',
      /* attributes = */ Map { },
    );
  }

  public static function getType(): ?DefinitionType {
    return null;
  }

  public function getClasses(): \ConstVector<ScannedBasicClass> {
    return $this->classes;
  }

  public function getInterfaces(): \ConstVector<ScannedInterface> {
    return $this->interfaces;
  }

  public function getTraits(): \ConstVector<ScannedTrait> {
    return $this->traits;
  }

  public function getFunctions(): \ConstVector<ScannedFunction> {
    return $this->functions;
  }

  public function getConstants(): \ConstVector<ScannedConstant> {
    return $this->constants;
  }

  public function getEnums(): \ConstVector<ScannedEnum> {
    return $this->enums;
  }

  public function getTypes(): \ConstVector<ScannedType> {
    return $this->types;
  }

  public function getNewtypes(): \ConstVector<ScannedNewtype> {
    return $this->newtypes;
  }
}

class ScannedScopeBuilder extends ScannedSingleTypeBuilder<ScannedScope> {
  public function __construct() {
    parent::__construct('__SCOPE__');
  }

  private Vector<ScannedClassBuilder> $classBuilders = Vector { };
  private Vector<ScannedFunctionBuilder> $functionBuilders = Vector { };
  private Vector<ScannedConstantBuilder> $constantBuilders = Vector { };
  private Vector<ScannedEnumBuilder> $enumBuilders = Vector { };
  private Vector<ScannedTypeBuilder> $typeBuilders = Vector { };
  private Vector<ScannedNewtypeBuilder> $newtypeBuilders = Vector { };

  private Vector<ScannedNamespaceBuilder> $namespaceBuilders = Vector { };

  public function addClass(ScannedClassBuilder $b): void {
    $this->classBuilders[] = $b;
  }

  public function addFunction(ScannedFunctionBuilder $b): void {
    $this->functionBuilders[] = $b;
  }

  public function addConstant(ScannedConstantBuilder $b): void {
    $this->constantBuilders[] = $b;
  }

  public function addEnum(ScannedEnumBuilder $b): void {
    $this->enumBuilders[] = $b;
  }

  public function addType(ScannedTypeBuilder $b): void {
    $this->typeBuilders[] = $b;
  }

  public function addNewtype(ScannedNewtypeBuilder $b): void {
    $this->newtypeBuilders[] = $b;
  }

  public function addNamespace(ScannedNamespaceBuilder $b): void {
    $this->namespaceBuilders[] = $b;
  }

  public function build(): ScannedScope {
    $ns = nullthrows($this->namespace);
    $pos = nullthrows($this->position);

    $classes = Vector { };
    $interfaces= Vector { };
    $traits = Vector { };
    foreach ($this->classBuilders as $b) {
      $b->setPosition($pos)->setNamespace($ns);
      switch ($b->getType()) {
        case ClassDefinitionType::CLASS_DEF:
          $classes[] = $b->build(ScannedBasicClass::class);
          break;
        case ClassDefinitionType::INTERFACE_DEF:
          $interfaces[] = $b->build(ScannedInterface::class);
          break;
        case ClassDefinitionType::TRAIT_DEF:
          $traits[] = $b->build(ScannedTrait::class);
          break;
      }
    }

    $functions = $this->buildAll($this->functionBuilders);
    $constants = $this->buildAll($this->constantBuilders);
    $enums = $this->buildAll($this->enumBuilders);
    $types = $this->buildAll($this->typeBuilders);
    $newtypes = $this->buildAll($this->newtypeBuilders);

    $namespaces = $this->buildAll($this->namespaceBuilders);
    $scopes = $namespaces->map($ns ==> $ns->getContents());
    foreach ($scopes as $scope) {
      $classes->addAll($scope->getClasses());
      $interfaces->addAll($scope->getInterfaces());
      $traits->addAll($scope->getTraits());
      $functions->addAll($scope->getFunctions());
      $constants->addAll($scope->getConstants());
      $enums->addAll($scope->getEnums());
      $types->addAll($scope->getTypes());
      $newtypes->addAll($scope->getNewtypes());
    }

    return new ScannedScope(
      nullthrows($this->position),
      $classes,
      $interfaces,
      $traits,
      $functions,
      $constants,
      $enums,
      $types,
      $newtypes,
    );
  }

  private function buildAll<T>(
    \ConstVector<ScannedSingleTypeBuilder<T>> $v,
  ): Vector<T> {
    return $v->map($b ==> $b
      ->setPosition(nullthrows($this->position))
      ->setNamespace(nullthrows($this->namespace))
      ->build()
    )->toVector();
  }

  public function getNamespace(): string {
    return nullthrows($this->namespace);
  }
}
