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

final class ScannedClassBuilder extends ScannedBaseBuilder {

  public function __construct(
    private ClassDefinitionType $type,
    string $name,
  ) {
    parent::__construct($name);
  }

  // Can be safe in 3.9, assuming D2311514 is cherry-picked
  // public function build<T as ScannedClass>(classname<T> $what): T {
  public function build<T as ScannedClass>(string $what): T {
    // UNSAFE
    ClassDefinitionType::assert($what::getType());
    invariant(
      $this->type === $what::getType(),
      "Can't build a %s for a %s",
      $what,
      token_name($this->type),
    );
    return new $what(
      nullthrows($this->position),
      nullthrows($this->namespace).$this->name,
      nullthrows($this->attributes),
      $this->docblock,
    );
  }

  public function getType(): ClassDefinitionType {
    return $this->type;
  }
}
