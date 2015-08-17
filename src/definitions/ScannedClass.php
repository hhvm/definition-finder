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

<<__ConsistentConstruct>>
abstract class ScannedClass
  extends ScannedBase
  implements HasScannedGenerics {

  public function __construct(
    SourcePosition $position,
    string $name,
    Map<string, Vector<mixed>> $attributes,
    ?string $docblock,
    private \ConstVector<ScannedMethod> $methods,
    private \ConstVector<ScannedProperty> $properties,
    private \ConstVector<ScannedConstant> $constants,
    private \ConstVector<ScannedGeneric> $generics,
    private ?string $parent,
    private \ConstVector<string> $interfaces,
  ) {
    parent::__construct($position, $name, $attributes, $docblock);
  }

  public function isInterface(): bool {
    return static::getType() === DefinitionType::INTERFACE_DEF;
  }

  public function isTrait(): bool {
    return static::getType() === DefinitionType::TRAIT_DEF;
  }

  public function getMethods(): \ConstVector<ScannedMethod> {
    return $this->methods;
  }

  public function getProperties(): \ConstVector<ScannedProperty> {
    return $this->properties;
  }

  public function getConstants(): \ConstVector<ScannedConstant> {
    return $this->constants;
  }

  public function getGenericTypes(): \ConstVector<ScannedGeneric> {
    return $this->generics;
  }

  public function getInterfaceNames(): \ConstVector<string> {
    return $this->interfaces;
  }

  public function getParentClassName(): ?string {
    return $this->parent;
  }
}
