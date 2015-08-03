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

type SourcePosition = shape(
  'filename' => string,
);

abstract class ScannedBase {
  public function __construct(
    private SourcePosition $position,
    private string $name,
  ) {
  }

  public function getFileName(): string {
    return $this->position['filename'];
  }

  public function getName(): string {
    return $this->name;
  }
}

final class ScannedClass extends ScannedBase {
}