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

abstract class BaseTreeDefinitionsFilter implements TreeDefinitions {
  private \ConstMap<string, Set<string>> $classes;
  private \ConstMap<string, Set<string>> $interfaces;
  private \ConstMap<string, Set<string>> $traits;
  private \ConstMap<string, Set<string>> $enums;
  private \ConstMap<string, Set<string>> $types;
  private \ConstMap<string, Set<string>> $newtypes;
  private \ConstMap<string, Set<string>> $functions;
  private \ConstMap<string, Set<string>> $constants;

  public function getClasses(): \ConstMap<string, Set<string>> { return $this->classes; }
  public function getInterfaces(): \ConstMap<string, Set<string>> { return $this->interfaces; }
  public function getTraits(): \ConstMap<string, Set<string>> { return $this->traits; }
  public function getEnums(): \ConstMap<string, Set<string>> { return $this->enums; }
  public function getTypes(): \ConstMap<string, Set<string>> { return $this->types; }
  public function getNewtypes(): \ConstMap<string, Set<string>> { return $this->newtypes; }
  public function getFunctions(): \ConstMap<string, Set<string>> { return $this->functions; }
  public function getConstants(): \ConstMap<string, Set<string>> { return $this->constants; }

  abstract protected static function Filtered(
    \ConstMap<string, Set<string>> $collection,
    DefinitionType $type,
  ): \ConstMap<string, Set<string>>;

  public static function Filter(TreeDefinitions $in): TreeDefinitions {
    return new static($in);
  }

  private function __construct(TreeDefinitions $in) {
    $this->classes = static::Filtered(
      $in->getClasses(), DefinitionType::CLASS_DEF);
    $this->interfaces= static::Filtered(
      $in->getInterfaces(), DefinitionType::INTERFACE_DEF);
    $this->traits = static::Filtered(
      $in->getTraits(), DefinitionType::TRAIT_DEF);
    $this->enums = static::Filtered(
      $in->getEnums(), DefinitionType::ENUM_DEF);
    $this->types = static::Filtered(
      $in->getTypes(), DefinitionType::TYPE_DEF);
    $this->newtypes = static::Filtered(
      $in->getNewtypes(), DefinitionType::NEWTYPE_DEF);
    $this->functions = static::Filtered(
      $in->getFunctions(), DefinitionType::FUNCTION_DEF);
    $this->constants = static::Filtered(
      $in->getConstants(), DefinitionType::CONST_DEF);
  }
}
