<?hh // strict
/*
 *  Copyright (c) 2015-present, Facebook, Inc.
 *  All rights reserved.
 *
 *  This source code is licensed under the BSD-style license found in the
 *  LICENSE file in the root directory of this source tree. An additional grant
 *  of patent rights can be found in the PATENTS file in the same directory.
 *
 */

namespace Facebook\DefinitionFinder;

use namespace HH\Lib\Vec;

abstract class BaseParser {
  protected ScannedScope $defs;

  public function getClasses(): vec<ScannedBasicClass> {
    return $this->defs->getClasses();
  }
  public function getInterfaces(): vec<ScannedInterface> {
    return $this->defs->getInterfaces();
  }
  public function getTraits(): vec<ScannedTrait> {
    return $this->defs->getTraits();
  }
  public function getFunctions(): vec<ScannedFunction> {
    return $this->defs->getFunctions();
  }
  public function getConstants(): vec<ScannedConstant> {
    return $this->defs->getConstants();
  }
  public function getEnums(): vec<ScannedEnum> {
    return $this->defs->getEnums();
  }
  public function getTypes(): vec<ScannedType> {
    return $this->defs->getTypes();
  }
  public function getNewtypes(): vec<ScannedNewtype> {
    return $this->defs->getNewtypes();
  }

  ///// Convenience /////

  public function getClassNames(): vec<string> {
    return Vec\map($this->getClasses(), $class ==> $class->getName());
  }

  public function getInterfaceNames(): vec<string> {
    return Vec\map($this->getInterfaces(), $x ==> $x->getName());
  }

  public function getTraitNames(): vec<string> {
    return Vec\map($this->getTraits(), $x ==> $x->getName());
  }

  public function getFunctionNames(): vec<string> {
    return Vec\map($this->getFunctions(), $class ==> $class->getName());
  }

  public function getConstantNames(): vec<string> {
    return Vec\map($this->getConstants(), $constant ==> $constant->getName());
  }

  public function getEnumNames(): vec<string> {
    return Vec\map($this->getEnums(), $x ==> $x->getName());
  }

  public function getTypeNames(): vec<string> {
    return Vec\map($this->getTypes(), $x ==> $x->getName());
  }

  public function getNewtypeNames(): vec<string> {
    return Vec\map($this->getNewtypes(), $x ==> $x->getName());
  }

  public function getClass(string $name): ScannedBasicClass {
    return self::GetX($name, $this->getClasses());
  }

  public function getInterface(string $name): ScannedInterface {
    return self::GetX($name, $this->getInterfaces());
  }

  public function getTrait(string $name): ScannedTrait {
    return self::GetX($name, $this->getTraits());
  }

  public function getFunction(string $name): ScannedFunction {
    return self::GetX($name, $this->getFunctions());
  }

  private static function GetX<T as ScannedBase>(
    string $name,
    vec<T> $defs,
  ): T {
    $defs = Vec\filter($defs, $x ==> $x->getName() === $name);
    invariant(\count($defs) === 1, 'not found: %s', $name);
    return $defs[0];
  }
}
