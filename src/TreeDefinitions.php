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

interface TreeDefinitions {
  public function getClasses(): \ConstMap<string, Set<string>>;
  public function getInterfaces(): \ConstMap<string, Set<string>>;
  public function getTraits(): \ConstMap<string, Set<string>>;
  public function getEnums(): \ConstMap<string, Set<string>>;
  public function getTypes(): \ConstMap<string, Set<string>>;
  public function getNewtypes(): \ConstMap<string, Set<string>>;
  public function getFunctions(): \ConstMap<string, Set<string>>;
  public function getConstants(): \ConstMap<string, Set<string>>;
}
