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
use namespace HH\Lib\Vec;

class ScannedScopeBuilder extends ScannedSingleTypeBuilder<ScannedScope> {
  public function __construct(
    HHAST\EditableNode $ast,
    self::TContext $context,
  ) {
    parent::__construct($ast, '__SCOPE__', $context);
  }

  private vec<ScannedClassishBuilder> $classBuilders = vec[];
  private vec<ScannedFunctionBuilder> $functionBuilders = vec[];
  private vec<ScannedMethodBuilder> $methodBuilders = vec[];
  private vec<ScannedTypehint> $usedTraits = vec[];
  private vec<ScannedPropertyBuilder> $propertyBuilders = vec[];
  private vec<ScannedConstantBuilder> $constantBuilders = vec[];
  private vec<ScannedTypeConstantBuilder> $typeConstantBuilders = vec[];
  private vec<ScannedEnumBuilder> $enumBuilders = vec[];
  private vec<ScannedTypeBuilder> $typeBuilders = vec[];
  private vec<ScannedNewtypeBuilder> $newtypeBuilders = vec[];

  private vec<ScannedNamespaceBuilder> $namespaceBuilders = vec[];
  private vec<ScannedScope> $subscopes = vec[];

  public function addProperty(ScannedPropertyBuilder $b): void {
    $this->propertyBuilders[] = $b;
  }

  public function addUsedTrait(ScannedTypehint $trait): void {
    $this->usedTraits[] = $trait;
  }

  public function addClass(ScannedClassishBuilder $b): void {
    $this->classBuilders[] = $b;
  }

  public function addFunction(ScannedFunctionBuilder $b): void {
    $this->functionBuilders[] = $b;
  }

  public function addMethod(ScannedMethodBuilder $b): void {
    $this->methodBuilders[] = $b;
  }

  public function addConstant(ScannedConstantBuilder $b): void {
    $this->constantBuilders[] = $b;
  }

  public function addTypeConstant(ScannedTypeConstantBuilder $b): void {
    $this->typeConstantBuilders[] = $b;
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

  public function addSubScope(ScannedScope $s): void {
    $this->subscopes[] = $s;
  }

  <<__Override>>
  public function build(): ScannedScope {
    $classes = vec[];
    $interfaces = vec[];
    $traits = vec[];
    foreach ($this->classBuilders as $b) {
      switch ($b->getType()) {
        case ClassDefinitionType::CLASS_DEF:
          $classes[] = $b->build(ScannedClass::class);
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
    $methods = $this->buildAll($this->methodBuilders);
    $properties = $this->buildAll($this->propertyBuilders);
    $constants = $this->buildAll($this->constantBuilders);
    $typeConstants = $this->buildAll($this->typeConstantBuilders);
    $enums = $this->buildAll($this->enumBuilders);
    $types = $this->buildAll($this->typeBuilders);
    $newtypes = $this->buildAll($this->newtypeBuilders);

    $namespaces = $this->buildAll($this->namespaceBuilders);
    $scopes = Vec\concat(
      Vec\map($namespaces, $ns ==> $ns->getContents()),
      $this->subscopes,
    );

    $classes = self::merge($classes, $scopes, $s ==> $s->getClasses());
    $interfaces = self::merge($interfaces, $scopes, $s ==> $s->getInterfaces());
    $traits = self::merge($traits, $scopes, $s ==> $s->getTraits());
    $functions = self::merge($functions, $scopes, $s ==> $s->getFunctions());
    $constants = self::merge($constants, $scopes, $s ==> $s->getConstants());
    $enums = self::merge($enums, $scopes, $s ==> $s->getEnums());
    $types = self::merge($types, $scopes, $s ==> $s->getTypes());
    $newtypes = self::merge($newtypes, $scopes, $s ==> $s->getNewtypes());

    return new ScannedScope(
      $this->ast,
      $this->getDefinitionContext(),
      $classes,
      $interfaces,
      $traits,
      $functions,
      $methods,
      $this->usedTraits,
      $properties,
      $constants,
      $typeConstants,
      $enums,
      $types,
      $newtypes,
    );
  }

  private function buildAll<T>(
    vec<ScannedSingleTypeBuilder<T>> $builders,
  ): vec<T> {
    return Vec\map($builders, $builder ==> $builder->build());
  }

  private static function merge<T>(
    Traversable<T> $existing,
    Traversable<ScannedScope> $scopes,
    (function(ScannedScope): Traversable<T>) $selector,
  ): vec<T> {
    return $scopes
      |> Vec\map($$, $selector)
      |> Vec\concat($existing, Vec\flatten($$));
  }
}
