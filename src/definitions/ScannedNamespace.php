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

class ScannedNamespace extends ScannedBase {
  public function __construct(
    string $name,
    self::TContext $context,
    private ScannedScope $contents,
  ) {
    parent::__construct(
      $name,
      $context,
      /* attributes = */ Map { },
      /* docblock = */ null,
    );
  }

  public static function getType(): ?DefinitionType {
    return null;
  }

  public function getContents(): ScannedScope {
    return $this->contents;
  }
}
