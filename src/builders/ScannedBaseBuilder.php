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

abstract class ScannedBaseBuilder {
  protected ?SourcePosition $position;
  protected ?Map<string, Vector<mixed>> $attributes;
  protected ?string $docblock;

  public function __construct(protected string $name) {
  }

  public function setPosition(SourcePosition $pos): this {
    $this->position = $pos;
    return $this;
  }

  public function setDocComment(?string $docblock): this {
    $this->docblock = $docblock;
    return $this;
  }

  public function setAttributes(
    Map<string, Vector<mixed>> $v
  ): this {
    $this->attributes = $v;
    return $this;
  }
}
