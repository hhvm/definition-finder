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

final class ScannedShapeField {
  public function __construct(
    private HHAST\Node $ast,
    private ScannedExpression $name,
    ScannedDefinition::TContext $context,
    private ?string $docComment,
    private OptionalityToken $optional,
    private ScannedTypehint $type,
  ) {
    if ($docComment === null) {
      $this->docComment = doccomment_from_ast($context, $ast);
    }
  }

  public function getAST(): HHAST\Node {
    return $this->ast;
  }

  public function getName(): ScannedExpression {
    return $this->name;
  }

  public function getDocComment(): ?string {
    return $this->docComment;
  }

  public function isOptional(): bool {
    return $this->optional === OptionalityToken::IS_OPTIONAL;
  }

  public function getValueType(): ScannedTypehint {
    return $this->type;
  }
}
