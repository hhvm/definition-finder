<?hh // strict
/*
 *  Copyright (c) 2015-present, Facebook, Inc.
 *  All rights reserved.
 *
 *  This source code is licensed under the MIT license found in the
 *  LICENSE file in the root directory of this source tree.
 *
 */

namespace Facebook\DefinitionFinder;

use namespace Facebook\HHAST;

class ScannedNamespace extends ScannedDefinition {
  public function __construct(
    HHAST\EditableNode $ast,
    string $name,
    self::TContext $context,
    private ScannedScope $contents,
  ) {
    parent::__construct(
      $ast,
      $name,
      $context,
      /* attributes = */ dict[],
      /* docblock = */ null,
    );
  }

  <<__Override>>
  public static function getType(): ?DefinitionType {
    return null;
  }

  public function getContents(): ScannedScope {
    return $this->contents;
  }
}
