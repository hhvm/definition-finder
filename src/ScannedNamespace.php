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

class ScannedNamespace extends ScannedBase {
  public function __construct(
    SourcePosition $position,
    string $name,
    private ScannedScope $contents,
  ) {
    parent::__construct($position, $name, /* attributes = */ Map { });
  }

  public static function getType(): ?DefinitionType {
    return null;
  }

  public function getContents(): ScannedScope {
    return $this->contents;
  }
}

final class ScannedNamespaceBuilder
extends ScannedSingleTypeBuilder<ScannedNamespace> {
  private ?ScannedScopeBuilder $scopeBuilder;

  public function setContents(ScannedScopeBuilder $scope): this {
    invariant($this->scopeBuilder === null, 'namespace already has a scope');
    $this->scopeBuilder = $scope;
    return $this;
  }

  public function build(): ScannedNamespace {
    $scope = nullthrows($this->scopeBuilder)
      ->setPosition(nullthrows($this->position))
      ->setNamespace($this->name)
      ->build();
    return new ScannedNamespace(
      nullthrows($this->position),
      nullthrows($this->name),
      $scope,
    );
  }
}
