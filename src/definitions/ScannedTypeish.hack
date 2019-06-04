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

<<__ConsistentConstruct, __Sealed(ScannedType::class, ScannedNewtype::class)>>
abstract class ScannedTypeish extends ScannedDefinition {
  public function __construct(
    HHAST\Node $ast,
    string $name,
    self::TContext $context,
    dict<string, vec<mixed>> $attributes,
    ?string $doccomment,
    private ScannedTypehint $aliasedType,
  ) {
    parent::__construct($ast, $name, $context, $attributes, $doccomment);
  }

  public function getAliasedType(): ScannedTypehint {
    return $this->aliasedType;
  }
}
