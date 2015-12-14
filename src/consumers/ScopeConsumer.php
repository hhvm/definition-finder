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

enum ScopeType: string {
  FILE_SCOPE = 'file';
  NAMESPACE_SCOPE = 'namespace';
  CLASS_SCOPE = 'class';
}

class ScopeConsumer extends Consumer {
  public function __construct(
    TokenQueue $tq,
    private ScopeType $scopeType,
  ) {
    parent::__construct($tq);
  }

  public function getBuilder(): ScannedScopeBuilder {
    $builder = (new ScannedScopeBuilder())->setNamespace('');
    $attrs = Map { };
    $docblock = null;

    $tq = $this->tq;
    $parens_depth = 0;
    $scope_depth = 1;
    $visibility = null;
    $static = false;
    $static_access = false;
    $property_type = null;
    while ($tq->haveTokens() && $scope_depth > 0) {
      list ($token, $ttype) = $tq->shift();
      if ($token === '(') {
        ++$parens_depth;
      }
      if ($token === ')') {
        --$parens_depth;
      }

      if ($token === '{' || $ttype == T_CURLY_OPEN) {
        ++$scope_depth;
        continue;
      }
      if ($token === '}') { // no such thing as T_CURLY_CLOSE
        --$scope_depth;
        continue;
      }

      if ($parens_depth !== 0 || $ttype === null) {
        continue;
      }

      if ($ttype === T_CLOSE_TAG) { /* ?> ... <?php */
        $this->consumeNonCode();
      }

      if ($ttype === T_SL && $scope_depth === 1 && $parens_depth === 0) {
        $attrs = (new UserAttributesConsumer($tq))->getUserAttributes();
        continue;
      }

      if ($ttype === T_DOC_COMMENT) {
        $docblock = $token;
        continue;
      }

      if ($ttype === T_STATIC) {
        $static = true;
      }

      // I hate you, PHP.
      if ($ttype === T_STRING && strtolower($token) === 'define') {
        $sub_builder = (new DefineConsumer($tq))->getBuilder();
        // I hate you more, PHP. $sub_builder is null in case we've not
        // actually got a constant: define($variable, ...);
        if ($sub_builder ) {
          $builder->addConstant($sub_builder);
        }
        continue;
      }

      if ($ttype === T_DOUBLE_COLON) {
        $static_access = true;
        continue;
      }

      if (VisibilityToken::isValid($ttype)) {
        invariant(
          $this->scopeType === ScopeType::CLASS_SCOPE,
          "Don't understand public/private/protected outside of a class ".
          "at line %d",
          $tq->getLine(),
        );
        $visibility = VisibilityToken::assert($ttype);

        continue;
      }

      if ($ttype === T_STRING) {
        $tq->unshift($token, $ttype);
        $property_type = (new TypehintConsumer($tq))->getTypehint();
        continue;
      }

      if ($ttype === T_VARIABLE) {
        $name = substr($token, 1); // remove prefixed '$'
        if ($visibility === null) {
          $visibility = VisibilityToken::T_PUBLIC;
        }
        $builder->addProperty(
          (new ScannedPropertyBuilder($name))
          ->setAttributes($attrs)
          ->setDocComment($docblock)
          ->setVisibility($visibility)
          ->setTypehint($property_type)
          ->setIsStatic($static)
        );

        $attrs = Map { };
        $docblock = null;
        $visibility = null;
        $static = false;
        $property_type = null;

        $this->consumeStatement();
        continue;
      }

      if (DefinitionType::isValid($ttype)) {
        $this->consumeDefinition(
          $builder,
          DefinitionType::assert($ttype),
          $attrs,
          $docblock,
          $visibility,
          $static,
          $static_access,
        );
        $attrs = Map { };
        $docblock = null;
        $visibility = null;
        $static = false;
        $static_access = false;
        $property_type = null;
        continue;
      }
    }

    return $builder;
  }

  private function consumeDefinition(
    ScannedScopeBuilder $builder,
    DefinitionType $def_type,
    AttributeMap $attrs,
    ?string $docblock,
    ?VisibilityToken $visibility,
    bool $static,
    bool $static_access,
   ): void {
    $this->consumeWhitespace();

    switch ($def_type) {
      case DefinitionType::NAMESPACE_DEF:
        $builder->addNamespace((new NamespaceConsumer($this->tq))->getBuilder());
        return;
      case DefinitionType::CLASS_DEF:
        if ($static_access) {
          // Foo::class is not a class definition
          return;
        }
        // FALLTHROUGH
      case DefinitionType::INTERFACE_DEF:
      case DefinitionType::TRAIT_DEF:
        $builder->addClass(
          (new ClassConsumer(ClassDefinitionType::assert($def_type), $this->tq))
            ->getBuilder()
            ->setAttributes($attrs)
            ->setDocComment($docblock)
        );
        return;
      case DefinitionType::FUNCTION_DEF:
        if ($this->scopeType === ScopeType::CLASS_SCOPE) {
          $fb = (new MethodConsumer($this->tq))->getBuilder();
        } else {
          $fb = (new FunctionConsumer($this->tq))->getBuilder();
        }

        if (!$fb) {
          return;
        }

        $fb
          ->setAttributes($attrs)
          ->setDocComment($docblock);
         if ($fb instanceof ScannedFunctionBuilder) {
           $builder->addFunction($fb);
         } else {
          invariant(
            $fb instanceof ScannedMethodBuilder,
            'unknown function builder type: %s',
            get_class($fb),
          );
          if ($visibility === null) {
            $visibility = VisibilityToken::T_PUBLIC;
          }
          $builder->addMethod(
            $fb
            ->setVisibility($visibility)
            ->setStatic($static)
          );
        }
        return;
      case DefinitionType::CONST_DEF:
        $builder->addConstant(
          (new ConstantConsumer($this->tq))
          ->getBuilder()
          ->setDocComment($docblock)
        );
        return;
      case DefinitionType::TYPE_DEF:
      case DefinitionType::NEWTYPE_DEF:
      case DefinitionType::ENUM_DEF:
        $this->consumeSimpleDefinition($builder, $def_type, $docblock);
        return;
    }
  }

  private function consumeSimpleDefinition(
    ScannedScopeBuilder $builder,
    DefinitionType $def_type,
    ?string $docblock,
  ): void {
    list($name, $ttype) = $this->tq->shift();
    invariant(
      $ttype=== T_STRING,
      'Expected a string for %s, got %d',
      token_name($def_type),
      $ttype,
    );
    switch ($def_type) {
      case DefinitionType::TYPE_DEF:
        $builder->addType(
          (new ScannedTypeBuilder($name))->setDocComment($docblock)
        );
        break;
      case DefinitionType::NEWTYPE_DEF:
        $builder->addNewtype(
          (new ScannedNewtypeBuilder($name))->setDocComment($docblock)
        );
        break;
      case DefinitionType::ENUM_DEF:
        $builder->addEnum(
          (new ScannedEnumBuilder($name))->setDocComment($docblock)
        );
        $this->skipToBlock();
        $this->consumeBlock();
        return;
      default:
        invariant_violation(
          '%d is not a simple definition',
          $def_type,
        );
    }
    $this->consumeStatement();
  }

  /** ?> ... <?php */
  private function consumeNonCode(): void {
    do {
      list ($_, $ttype) = $this->tq->shift();
    } while ($this->tq->haveTokens() && $ttype !== T_OPEN_TAG);
  }
}
