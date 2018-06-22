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

final class ScannedNamespaceBuilder
  extends ScannedSingleTypeBuilder<ScannedNamespace> {
  private ?ScannedScopeBuilder $scopeBuilder;

  public function setContents(ScannedScopeBuilder $scope): this {
    invariant($this->scopeBuilder === null, 'namespace already has a scope');
    $this->scopeBuilder = $scope;
    return $this;
  }

  <<__Override>>
  public function build(): ScannedNamespace {
    $scope = nullthrows($this->scopeBuilder)->build();
    return new ScannedNamespace(
      $this->ast,
      nullthrows($this->name),
      $this->getDefinitionContext(),
      $scope,
    );
  }
}
