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

abstract class BaseParser {
  protected ScannedScope $defs;

  public function getClasses(): \ConstVector<ScannedBasicClass> {
    return $this->defs->getClasses();
  }
  public function getInterfaces(): \ConstVector<ScannedInterface> {
    return $this->defs->getInterfaces();
  }
  public function getTraits(): \ConstVector<ScannedTrait> {
    return $this->defs->getTraits();
  }
  public function getFunctions(): \ConstVector<ScannedFunction> {
    return $this->defs->getFunctions();
  }
  public function getConstants(): \ConstVector<ScannedConstant> {
    return $this->defs->getConstants();
  }
  public function getEnums(): \ConstVector<ScannedEnum> {
    return $this->defs->getEnums();
  }
  public function getTypes(): \ConstVector<ScannedType> {
    return $this->defs->getTypes();
  }
  public function getNewtypes(): \ConstVector<ScannedNewtype> {
    return $this->defs->getNewtypes();
  }

  ///// Convenience /////

  public function getClassNames(): \ConstVector<string> {
    return $this->getClasses()->map($class ==> $class->getName());
  }

  public function getInterfaceNames(): \ConstVector<string> {
    return $this->getInterfaces()->map($x ==> $x->getName());
  }

  public function getTraitNames(): \ConstVector<string> {
    return $this->getTraits()->map($x ==> $x->getName());
  }

  public function getFunctionNames(): \ConstVector<string> {
    return $this->getFunctions()->map($class ==> $class->getName());
  }

  public function getConstantNames(): \ConstVector<string> {
    return $this->getConstants()->map($constant ==> $constant->getName());
  }

  public function getEnumNames(): \ConstVector<string> {
    return $this->getEnums()->map($x ==> $x->getName());
  }

  public function getTypeNames(): \ConstVector<string> {
    return $this->getTypes()->map($x ==> $x->getName());
  }

  public function getNewtypeNames(): \ConstVector<string> {
    return $this->getNewtypes()->map($x ==> $x->getName());
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
    \ConstVector<T> $defs,
  ): T {
    $defs = $defs->filter($x ==> $x->getName() === $name);
    invariant(count($defs) === 1, 'not found: %s', $name);
    return $defs->at(0);
  }
}
