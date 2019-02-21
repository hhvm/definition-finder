/*
 *  Copyright (c) 2015-present, Facebook, Inc.
 *  All rights reserved.
 *
 *  This source code is licensed under the MIT license found in the
 *  LICENSE file in the root directory of this source tree.
 *
 */

namespace Facebook\DefinitionFinder;

use namespace HH\Lib\Vec;

abstract class BaseParser {
  protected ScannedScope $defs;

  public function getClasses(): vec<ScannedClass> {
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

  public function getClass(string $name): ScannedClass {
    return self::getX($name, $this->getClasses());
  }

  public function getConstant(string $name): ScannedConstant {
    return self::getX($name, $this->getConstants());
  }

  public function getInterface(string $name): ScannedInterface {
    return self::getX($name, $this->getInterfaces());
  }

  public function getTrait(string $name): ScannedTrait {
    return self::getX($name, $this->getTraits());
  }

  public function getFunction(string $name): ScannedFunction {
    return self::getX($name, $this->getFunctions());
  }

  public function getType(string $name): ScannedType {
    return self::getX($name, $this->getTypes());
  }

  public function getNewtype(string $name): ScannedNewtype {
    return self::getX($name, $this->getNewtypes());
  }

  private static function getX<T as ScannedDefinition>(
    string $name,
    vec<T> $defs,
  ): T {
    $defs = Vec\filter($defs, $x ==> $x->getName() === $name);
    invariant(\count($defs) === 1, 'not found: %s', $name);
    return $defs[0];
  }
}
