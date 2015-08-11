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
    private \ConstVector<ScannedMethod> $methods,
    private \ConstVector<ScannedConstant> $constants,
    private \ConstVector<ScannedEnum> $enums,
    private \ConstVector<ScannedType> $types,
    private \ConstVector<ScannedNewtype> $newtypes,
  ) {
    parent::__construct(
      $position,
      '__SCOPE__',
      /* attributes = */ Map { },
      /* docblock = */ null,
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

  public function getMethods(): \ConstVector<ScannedMethod> {
    return $this->methods;
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
