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

  private Map<string, string> $scopeAliases;

  public function __construct(
    TokenQueue $tq,
    private ScopeType $scopeType,
    ?string $namespace,
    \ConstMap<string, string> $aliases,
  ) {
    $this->scopeAliases = new Map($aliases);
    parent::__construct($tq, $namespace, $aliases);
  }

  public function getBuilder(): ScannedScopeBuilder {
    $builder = (new ScannedScopeBuilder());
    $attrs = Map { };
    $docblock = null;

    $tq = $this->tq;
    $parens_depth = 0;
    $scope_depth = 1;
    $visibility = null;
    $staticity = null;
    $abstractness = null;
    $finality = null;
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
        $attrs = (new UserAttributesConsumer(
          $tq,
          $this->namespace,
          $this->scopeAliases,
        ))->getUserAttributes();
        continue;
      }

      if ($ttype === T_DOC_COMMENT) {
        $docblock = $token;
        continue;
      }

      if ($ttype === T_STATIC) {
        $staticity = StaticityToken::IS_STATIC;
      }

      if ($ttype === T_ABSTRACT) {
        $abstractness = AbstractnessToken::IS_ABSTRACT;
      }

      if ($ttype === T_FINAL) {
        $finality = FinalityToken::IS_FINAL;
      }

      if ($ttype === T_ABSTRACT) {
        $abstract = true;
      }

      if ($ttype === T_XHP_ATTRIBUTE) {
        $this->consumeStatement();
        continue;
      }

      if ($ttype === T_USE && $this->scopeType !== ScopeType::CLASS_SCOPE) {
        $this->scopeAliases->setAll($this->consumeUseStatement());
        continue;
      }

      if ($ttype === T_USE && $this->scopeType === ScopeType::CLASS_SCOPE) {
        do {
          $this->consumeWhitespace();
          $builder->addUsedTrait((new TypehintConsumer(
            $tq,
            $this->namespace,
            $this->scopeAliases,
          ))->getTypehint());
          $this->consumeWhitespace();

          list($peeked, $_) = $tq->peek();
          if ($peeked === ',') {
            $tq->shift();
            continue;
          }
          break;
        } while (true);
        $this->consumeStatement();
        continue;
      }

      // I hate you, PHP.
      if ($ttype === T_STRING && strtolower($token) === 'define') {
        $sub_builder = (new DefineConsumer(
          $tq,
          $this->namespace,
          $this->scopeAliases,
        ))->getBuilder();
        // I hate you more, PHP. $sub_builder is null in case we've not
        // actually got a constant: define($variable, ...);
        if ($sub_builder) {
          $builder->addConstant($sub_builder);
        }
        continue;
      }

      if ($ttype === T_DOUBLE_COLON) {
        // Whatever's next it can't be the start of a definition. This stops
        // '::class' being considered the start of a class definition.
        $this->tq->shift();
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
        $property_type = (new TypehintConsumer(
          $tq,
          $this->namespace,
          $this->scopeAliases,
        ))->getTypehint();
        continue;
      }

      // make sure we're not inside a method body
      if ($ttype === T_VARIABLE && $scope_depth === 1) {
        $name = substr($token, 1); // remove prefixed '$'
        if ($visibility === null) {
          $visibility = VisibilityToken::T_PUBLIC;
        }
        if ($staticity === null) {
          $staticity = StaticityToken::NOT_STATIC;
        }
        $builder->addProperty(
          (new ScannedPropertyBuilder($name))
          ->setAttributes($attrs)
          ->setDocComment($docblock)
          ->setVisibility($visibility)
          ->setTypehint($property_type)
          ->setStaticity($staticity)
        );

        $attrs = Map { };
        $docblock = null;
        $visibility = null;
        $staticity = null;
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
          $staticity,
          $abstractness,
          $finality,
        );

        $attrs = Map { };
        $docblock = null;
        $visibility = null;
        $staticity = null;
        $property_type = null;
        $abstractness = null;
        $finality = null;
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
    ?StaticityToken $staticity,
    ?AbstractnessToken $abstractness,
    ?FinalityToken $finality,
   ): void {
    $this->consumeWhitespace();

    switch ($def_type) {
      case DefinitionType::NAMESPACE_DEF:
        $builder->addNamespace(
          (new NamespaceConsumer(
            $this->tq,
            /* ns = */ null,
            $this->scopeAliases,
          ))->getBuilder()
        );
        return;
      case DefinitionType::CLASS_DEF:
      case DefinitionType::INTERFACE_DEF:
      case DefinitionType::TRAIT_DEF:
        if ($abstractness === null) {
          $abstractness = AbstractnessToken::NOT_ABSTRACT;
        }
        if ($finality === null) {
          $finality = FinalityToken::NOT_FINAL;
        }
        $builder->addClass(
          (new ClassConsumer(
            ClassDefinitionType::assert($def_type),
            $this->tq,
            $this->namespace,
            $this->scopeAliases
          ))
            ->getBuilder()
            ->setAttributes($attrs)
            ->setDocComment($docblock)
            ->setAbstractness($abstractness)
            ->setFinality($finality)
        );
        return;
      case DefinitionType::FUNCTION_DEF:
        if ($this->scopeType === ScopeType::CLASS_SCOPE) {
          $fb = (new MethodConsumer(
            $this->tq,
            $this->namespace,
            $this->scopeAliases,
          ))->getBuilder();
        } else {
          $fb = (new FunctionConsumer(
            $this->tq,
            $this->namespace,
            $this->scopeAliases,
          ))->getBuilder();
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
          if ($staticity === null) {
            $staticity = StaticityToken::NOT_STATIC;
          }
          if ($visibility === null) {
            $visibility = VisibilityToken::T_PUBLIC;
          }
          if ($abstractness === null) {
            $abstractness = AbstractnessToken::NOT_ABSTRACT;
          }
          if ($finality === null) {
            $finality = FinalityToken::NOT_FINAL;
          }
          $builder->addMethod(
            $fb
            ->setVisibility($visibility)
            ->setStaticity($staticity)
            ->setAbstractness($abstractness)
            ->setFinality($finality)
          );
        }
        return;
      case DefinitionType::CONST_DEF:
        $namespace = $this->scopeType === ScopeType::CLASS_SCOPE
          ? null
          : $this->namespace;

        list($next, $next_token) = $this->tq->peek();
        if ($next_token === DefinitionType::TYPE_DEF) {
          if ($abstractness === null) {
            $abstractness = AbstractnessToken::NOT_ABSTRACT;
          }
          $builder->addTypeConstant(
            (new TypeConstantConsumer(
              $this->tq,
              $namespace,
              $this->scopeAliases,
              $abstractness,
            ))
            ->getBuilder()
            ->setDocComment($docblock)
          );
          return;
        }
        $builder->addConstant(
          (new ConstantConsumer(
            $this->tq,
            $namespace,
            $this->scopeAliases,
          ))
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
      $ttype === T_STRING,
      'Expected a string for %s, got %d',
      token_name($def_type),
      $ttype,
    );
    $name = $this->normalizeName($name);

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

  private function consumeUseStatement(): ImmMap<string, string> {
    $parts = [];
    $alias = '';

    do {
      $this->consumeWhitespace();
      list($token, $type) = $this->tq->shift();

      if ($type === T_STRING) {
        $parts[] = $token;
        continue;
      } else if ($type === T_NS_SEPARATOR) {
        continue;
      } else if ($type === T_AS) {
        $alias = $this->consumeAlias();
        break;
      } else if ($token === '{') {
        return $this->consumeGroupUseStatement(new ImmVector($parts));
      } else if ($token === ';') {
        break;
      }

      invariant_violation(
        'Unexpected token %s',
        var_export($token, true),
      );

    } while ($this->tq->haveTokens());

    if($alias === '') {
       $alias = $parts[count($parts) - 1];
    }

    $namespace = implode('\\', $parts);

    return ImmMap { $alias => $namespace };
  }

  private function consumeGroupUseStatement(
    ImmVector<string> $prefix,
  ): ImmMap<string, string> {
    $aliases = Map { };
    $tq = $this->tq;
    do {
      $this->consumeWhitespace();
      list($t, $ttype) = $tq->shift();
      invariant($ttype === T_STRING, 'expected definition name');
      $name = $t;

      $this->consumeWhitespace();
      list($t, $ttype) = $tq->shift();
      if ($t === '}') {
        $aliases[$name] = $name;
        break;
      }
      if ($t === ',') {
        $aliases[$name] = $name;
        $this->consumeWhitespace();
        continue;
      }

      invariant(
        $ttype === T_AS,
        "Unexpected token %s",
        var_export($t, true),
      );
      $this->consumeWhitespace();

      list($t, $ttype) = $tq->shift();
      invariant(
        $ttype === T_STRING,
        'Expected alias (T_STRING), got %s',
        var_export($t, true),
      );
      $aliases[$t] = $name;
      $this->consumeWhitespace();
      list($t, $ttype) = $tq->shift();
      if ($t === '}') {
        break;
      }
      if ($t === ',') {
        continue;
      }
      invariant_violation(
        "Expected '}' or ',', got %s",
        var_export($t, true),
      );
    } while (!$tq->isEmpty());

    $prefix = implode("\\", $prefix)."\\";
    return $aliases->map($value ==> $prefix.$value)->immutable();
  }

  private function consumeAlias(): string {

    $this->consumeWhitespace();

    if($this->tq->isEmpty()) {
      invariant_violation('Expected alias name after AS statement.');
    }

    list($name, $type) = $this->tq->shift();
    if($type !== T_STRING) {
      invariant_violation(
        'Unexpected token %s',
        var_export($name, true),
      );
    }

    $this->consumeWhitespace();

    if(!$this->tq->isEmpty()) {
       list($next, $_) = $this->tq->shift();
       if($next !== ';') {
         invariant_violation(
           'Unexpected token %s',
           var_export($next, true),
         );
       }
    }
    return $name;
  }

  <<__Override>>
  protected function normalizeName(string $name): string {
    if ($this->scopeType === ScopeType::CLASS_SCOPE) {
      return $name;
    }
    return parent::normalizeName($name);
  }
}
