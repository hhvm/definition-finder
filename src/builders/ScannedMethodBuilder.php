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

final class ScannedMethodBuilder
  extends ScannedFunctionishBuilder<ScannedMethod> {

  protected ?VisibilityToken $visibility;
  private ?StaticityToken $staticity;
  private ?AbstractnessToken $abstractness;
  private ?FinalityToken $finality;

  <<__Override>>
  public function build(): ScannedMethod {
    return new ScannedMethod(
      $this->ast,
      $this->name,
      $this->getDefinitionContext(),
      nullthrows($this->attributes),
      $this->docblock,
      nullthrows($this->generics),
      $this->returnType,
      nullthrows($this->parameters),
      nullthrows($this->visibility),
      nullthrows($this->staticity),
      nullthrows($this->abstractness),
      nullthrows($this->finality),
    );
  }

  public function setVisibility(VisibilityToken $visibility): this {
    $this->visibility = $visibility;
    return $this;
  }

  public function setStaticity(StaticityToken $staticity): this {
    $this->staticity = $staticity;
    return $this;
  }

  public function setAbstractness(AbstractnessToken $abstractness): this {
    $this->abstractness = $abstractness;
    return $this;
  }

  public function setFinality(FinalityToken $finality): this {
    $this->finality = $finality;
    return $this;
  }
}
