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

class FileParser {
  // Temporary state
  private string $namespace = '';
  private Map<string,Vector<mixed>> $attributes = Map { };

  // Results
  private Vector<ScannedFunction> $functions = Vector { };
  private Vector<ScannedBasicClass> $classes = Vector { };
  private Vector<ScannedInterface> $interfaces = Vector { };
  private Vector<ScannedTrait> $traits = Vector { };
  private Vector<ScannedConstant> $constants = Vector { };

  private Vector<ScannedEnum> $enums = Vector { };
  private Vector<ScannedType> $types = Vector { };
  private Vector<ScannedNewtype> $newtypes = Vector { };

  private function __construct(
    private string $file,
    private TokenQueue $tokenQueue,
  ) {
    $this->consumeFile();
  }

  ///// Constructors /////

  public static function FromFile(
    string $filename,
  ): FileParser {
    return self::FromData(file_get_contents($filename), $filename);
  }

  public static function FromData(
    string $data,
    ?string $filename = null,
  ): FileParser {
    return new FileParser(
      $filename === null ? '__DATA__' : $filename,
      new TokenQueue($data),
    );
  }

  ///// Accessors /////

  public function getFilename(): string { return $this->file; }
  public function getClasses(): \ConstVector<ScannedBasicClass> {
    return $this->classes;
  }
  public function getInterfaces(): \ConstVector<ScannedInterface> {
    return $this->interfaces;
  }
  public function getTraits(): \ConstVector<ScannedTrait> {
    return $this->traits;
  }
  public function getFunctions(): \ConstVector<ScannedFunction> {
    return $this->functions;
  }
  public function getConstants(): \ConstVector<ScannedConstant> {
    return $this->constants;
  }

  ///// Convenience /////

  public function getClassNames(): \ConstVector<string> {
    return $this->getClasses()->map($class ==> $class->getName());
  }
  
  public function getInterfaceNames(): \ConstVector<string> {
    return $this->getInterfaces()->map($x ==> $x->getName());
  }

  public function getTraitNames(): \ConstVector<string> {
    return $this->getTraits()->map($x ==> $x->getName());
  }

  public function getFunctionNames(): \ConstVector<string> {
    return $this->getFunctions()->map($class ==> $class->getName());
  }

  public function getConstantNames(): \ConstVector<string> {
    return $this->getConstants()->map($constant ==> $constant->getName());
  }

  public function getEnumNames(): \ConstVector<string> {
    return $this->enums->map($x ==> $x->getName());
  }

  public function getTypeNames(): \ConstVector<string> {
    return $this->types->map($x ==> $x->getName());
  }

  public function getNewtypeNames(): \ConstVector<string> {
    return $this->newtypes->map($x ==> $x->getName());
  }

  ///// Implementation /////

  private function consumeFile(): void {
    $tq = $this->tokenQueue;
    $parens_depth = 0;
    while ($tq->haveTokens()) {
      $this->skipToCode();
      while ($tq->haveTokens()) {
        list ($token, $ttype) = $tq->shift();
        if ($token === '(') {
          ++$parens_depth;
        }
        if ($token === ')') {
          --$parens_depth;
        }

        if ($parens_depth !== 0 || $ttype === null) {
          continue;
        }

        if ($ttype === T_CLOSE_TAG) {
          break;
        }

        if ($ttype === T_SL) {
          $this->consumeUserAttributes();
        }

        if (DefinitionType::isValid($ttype)) {
          $this->consumeDefinition(DefinitionType::assert($ttype));
          continue;
        }
        // I hate you, PHP.
        if ($ttype === T_STRING && strtolower($token) === 'define') {
          $this->consumeOldConstantDefinition();
          continue;
        }
      }
    }
  }

  private function skipToCode(): void {
    $token_type = null;
    do {
      list ($token, $token_type) = $this->tokenQueue->shift();
    } while ($this->tokenQueue->haveTokens() && $token_type !== T_OPEN_TAG);
  }

  private function consumeDefinition(DefinitionType $def_type): void {
    $tname = token_name($def_type);

    $this->consumeWhitespace();

    switch ($def_type) {
      case DefinitionType::NAMESPACE_DEF:
        $this->consumeNamespaceDefinition();
        return;
      case DefinitionType::CLASS_DEF:
      case DefinitionType::INTERFACE_DEF:
      case DefinitionType::TRAIT_DEF:
        $this->consumeClassDefinition($def_type);
        return;
      case DefinitionType::FUNCTION_DEF:
        $this->consumeFunctionDefinition();
        return;
      case DefinitionType::CONST_DEF:
        $this->consumeConstantDefinition();
        return;
      case DefinitionType::TYPE_DEF:
      case DefinitionType::NEWTYPE_DEF:
      case DefinitionType::ENUM_DEF:
        $this->consumeSimpleDefinition($def_type);
        return;
    }
  }

  /**
   * /const CONST_NAME =/
   * /const type_name CONST_NAME =/
   */
  private function consumeConstantDefinition(): void {
    $this->constants[] = (new ConstantConsumer($this->tokenQueue))
      ->getBuilder()
      ->setPosition(shape('filename' => $this->file))
      ->setNamespace($this->namespace)
      ->build();
  }

  /**
   * define ('FOO', value);
   * define (FOO, value); // yep, this is different. I *REALLY* hate php.
   *
   * 'define' has been consumed, that's it
   */
  private function consumeOldConstantDefinition(): void {
    $this->constants[] = (new DefineConsumer($this->tokenQueue))
      ->getBuilder()
      ->setPosition(shape('filename' => $this->file))
      ->setNamespace($this->namespace)
      ->build();
  }

  private function consumeWhitespace(): void {
    list($t, $ttype) = $this->tokenQueue->shift();
    if ($ttype === T_WHITESPACE) {
      return;
    }
    $this->tokenQueue->unshift($t, $ttype);
  }

  private function consumeNamespaceDefinition(): void {
    $parts = [];
    do {
      $this->consumeWhitespace();
      list($next, $next_type) = $this->tokenQueue->shift();
      if ($next_type === T_STRING) {
        $parts[] = $next;
        continue;
      } else if ($next_type === T_NS_SEPARATOR) {
        continue;
      } else if ($next === '{' || $next === ';') {
        break;
      }
      invariant_violation(
        'Unexpected token %s in %s',
        var_export($next, true),
        $this->file,
      );
    } while ($this->tokenQueue->haveTokens());

    if ($parts) {
      $this->namespace = implode('\\', $parts).'\\';
    } else {
      $this->namespace = '';
    }
  }

  private function skipToAndConsumeBlock(): void {
    $nesting = 0;
    while ($this->tokenQueue->haveTokens()) {
      list($next, $next_type) = $this->tokenQueue->shift();
      if ($next === '{' || $next_type === T_CURLY_OPEN) {
        ++$nesting;
      } else if ($next === '}') { // no such thing as T_CURLY_CLOSE
        --$nesting;
        if ($nesting === 0) {
          return;
        }
      }
    }
  }

  private function consumeStatement(): void {
    while ($this->tokenQueue->haveTokens()) {
      list($tv, $ttype) = $this->tokenQueue->shift();
      if ($tv === ';') {
        return;
      }
      if ($tv === '{') {
        $this->tokenQueue->unshift($tv, $ttype);
        $this->skipToAndConsumeBlock();
        return;
      }
    }
  }

  private function consumeClassDefinition(DefinitionType $def_type): void {
    $def_type = ClassDefinitionType::assert($def_type);

    $builder = (new ClassConsumer($def_type, $this->tokenQueue))
      ->getBuilder()
      ->setNamespace($this->namespace)
      ->setPosition(shape('filename' => $this->file))
      ->setAttributes($this->attributes);
    $this->attributes = Map { };

    switch ($def_type) {
      case ClassDefinitionType::CLASS_DEF:
        $this->classes[] = $builder->build(ScannedBasicClass::class);
        break;
      case ClassDefinitionType::INTERFACE_DEF:
        $this->interfaces[] = $builder->build(ScannedInterface::class);
        break;
      case ClassDefinitionType::TRAIT_DEF:
        $this->traits[] = $builder->build(ScannedTrait::class);
        break;
    }
  }

  private function consumeSimpleDefinition(DefinitionType $def_type): void {
    list($next, $next_type) = $this->tokenQueue->shift();
    invariant(
      $next_type === T_STRING,
      'Expected a string for %s, got %d - in %s',
      token_name($def_type),
      $next_type,
      $this->file,
    );
    $fqn = $this->namespace.$next;
    switch ($def_type) {
      case DefinitionType::TYPE_DEF:
        $this->types[] = new ScannedType(
          shape('filename' => $this->file),
          $fqn,
          Map { }
        );
        break;
      case DefinitionType::NEWTYPE_DEF:
        $this->newtypes[] = new ScannedNewtype(
          shape('filename' => $this->file),
          $fqn,
          Map { }
        );
        break;
      case DefinitionType::ENUM_DEF:
        $this->enums[] = new ScannedEnum(
          shape('filename' => $this->file),
          $fqn,
          Map { }
        );
        $this->skipToAndConsumeBlock();
        return;
      default:
        invariant_violation(
          '%d is not a simple definition',
          $def_type,
        );
    }
    $this->consumeStatement();
  }

  private function consumeFunctionDefinition(): void {
    $builder = (new FunctionConsumer($this->tokenQueue))->getBuilder();
    if (!$builder) {
      return;
    }
    $this->functions[] = $builder
      ->setNamespace($this->namespace)
      ->setPosition(shape('filename' => $this->file))
      ->setAttributes($this->attributes)
      ->build();
    $this->attributes = Map { };
  }

  private function consumeUserAttributes(): void {
    while (true) {
      list($name, $_) = $this->tokenQueue->shift();
      if (!$this->attributes->containsKey($name)) {
        $this->attributes[$name] = Vector { };
      }

      list($t, $ttype) = $this->tokenQueue->shift();
      if ($ttype === T_SR) { // this was the last attribute
        return;
      }
      if ($t === ',') { // there's another
        continue;
      }

      // this attribute has values
      invariant(
        $t === '(',
        'Expected attribute name to be followed by >>, (, or ,',
      );

      while (true) {
        list($value, $ttype) = $this->tokenQueue->shift();
        switch ((int) $ttype) {
          case T_CONSTANT_ENCAPSED_STRING:
            $this->attributes[$name][] = substr($value, 1, -1);
            break;
          case T_LNUMBER:
            $this->attributes[$name][] = (int) $value;
            break;
          default:
            invariant_violation(
              "Invalid attribute value token type: %d",
              $ttype
            );
        }
        list($t, $_) = $this->tokenQueue->shift();
        if ($t === ')') {
          break;
        }
        invariant($t === ',', 'Expected attribute value to be followed by , or )');
      }
      list($t, $ttype) = $this->tokenQueue->shift();
      if ($ttype === T_SR) {
        return;
      }
      invariant(
        $t === ',',
        'Expected attribute value list to be followed by >> or ,',
      );
    }
  }
}
