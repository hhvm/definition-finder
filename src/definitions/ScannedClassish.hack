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

<<
  __ConsistentConstruct,
  __Sealed(ScannedClass::class, ScannedInterface::class, ScannedTrait::class)
>>
abstract class ScannedClassish
  extends ScannedDefinition
  implements HasScannedGenerics {

  public function __construct(
    HHAST\Node $ast,
    string $name,
    self::TContext $context,
    dict<string, vec<mixed>> $attributes,
    ?string $docblock,
    private vec<ScannedMethod> $methods,
    private vec<ScannedProperty> $properties,
    private vec<ScannedConstant> $constants,
    private vec<ScannedTypeConstant> $typeConstants,
    private vec<ScannedGeneric> $generics,
    private ?ScannedTypehint $parent,
    private vec<ScannedTypehint> $interfaces,
    private vec<ScannedTypehint> $traits,
    private AbstractnessToken $abstractness = AbstractnessToken::NOT_ABSTRACT,
    private FinalityToken $finality = FinalityToken::NOT_FINAL,
  ) {
    parent::__construct($ast, $name, $context, $attributes, $docblock);
  }

  public function isInterface(): bool {
    return static::getType() === DefinitionType::INTERFACE_DEF;
  }

  public function isTrait(): bool {
    return static::getType() === DefinitionType::TRAIT_DEF;
  }

  public function getMethods(): vec<ScannedMethod> {
    return $this->methods;
  }

  public function getProperties(): vec<ScannedProperty> {
    return $this->properties;
  }

  public function getConstants(): vec<ScannedConstant> {
    return $this->constants;
  }

  public function getTypeConstants(): vec<ScannedTypeConstant> {
    return $this->typeConstants;
  }

  public function getGenericTypes(): vec<ScannedGeneric> {
    return $this->generics;
  }

  public function getInterfaceNames(): vec<string> {
    return Vec\map($this->interfaces, $x ==> $x->getTypeName());
  }

  public function getTraitInfo(): vec<ScannedTypehint> {
    return $this->traits;
  }

  public function getTraitNames(): vec<string> {
    return Vec\map($this->traits, $x ==> $x->getTypeName());
  }

  public function getParentClassName(): ?string {
    return $this->parent?->getTypeName();
  }

  public function getParentClassInfo(): ?ScannedTypehint {
    return $this->parent;
  }

  public function getInterfaceInfo(): vec<ScannedTypehint> {
    return $this->interfaces;
  }

  public function getTraitGenerics(): dict<string, vec<ScannedTypehint>> {
    $traits = dict[];
    foreach ($this->traits as $trait) {
      $traits[$trait->getTypeName()] = $trait->getGenericTypes();
    }
    return $traits;
  }

  public function isAbstract(): bool {
    return $this->abstractness === AbstractnessToken::IS_ABSTRACT;
  }

  public function isFinal(): bool {
    return $this->finality === FinalityToken::IS_FINAL;
  }
}
