<?hh
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

enum DefinitionToken: int {
  NAMESPACE_DEF = T_NAMESPACE;
  CLASS_DEF = T_CLASS;
  INTERFACE_DEF = T_INTERFACE;
  TRAIT_DEF = T_TRAIT;
  ENUM_DEF = T_ENUM;
  TYPE_DEF = 403; // facebook/hhvm#4872
  NEWTYPE_DEF = 405; // facebook/hhvm#4872
  FUNCTION_DEF = T_FUNCTION;
  CONST_DEF = T_CONST;
}

class FileParser {
  private ?string $data;
  private array $tokens = [];
  private string $namespace = '';
  private Vector<string> $classes = Vector { };
  private Vector<string> $interfaces = Vector { };
  private Vector<string> $traits = Vector { };
  private Vector<string> $enums = Vector { };
  private Vector<string> $types = Vector { };
  private Vector<string> $newtypes = Vector { };
  private Vector<string> $functions = Vector { };
  private Vector<string> $constants = Vector { };

  private function __construct(
    private string $file,
    string $data,
  ) {
    $this->data = $data;
    $this->consumeFile();
  }

  ///// Constructors /////

  public static function fromFile(
    string $filename,
  ): FileParser {
    return new FileParser(
      $filename,
      file_get_contents($filename),
    );
  }

  public static function fromData(
    string $filename,
    string $data,
  ): FileParser {
    return new FileParser(
      $filename,
      $data,
    );
  }

  ///// Accessors /////

  public function getFilename(): string { return $this->file; }
  public function getClasses(): Vector<string> { return $this->classes; }
  public function getInterfaces(): Vector<string> { return $this->interfaces; }
  public function getTraits(): Vector<string> { return $this->traits; }
  public function getEnums(): Vector<string> { return $this->enums; }
  public function getTypes(): Vector<string> { return $this->types; }
  public function getNewtypes(): Vector<string> { return $this->newtypes; }
  public function getFunctions(): Vector<string> { return $this->functions; }
  public function getConstants(): Vector<string> { return $this->constants; }

  ///// Implementation /////

  private function consumeFile(): void {
    $data = $this->data;
    invariant(
      $data !== null,
      'somehow got constructed with null data for %s',
      $this->file,
    );
    $this->tokens = token_get_all($data);

    while ($this->tokens) {
      $this->skipToCode();
      while ($this->tokens) {
        $token = array_shift($this->tokens);
        if (!is_array($token)) {
          continue;
        }

        $ttype = $token[0];
        if (DefinitionToken::isValid($ttype)) {
          $this->consumeDefinition($ttype);
        }
        if ($ttype === T_CLOSE_TAG) {
          break;
        }
      }
    } 
    $this->data = '';
  }

  private function skipToCode(): void {
    $token_type = null;
    do {
      $token = array_shift($this->tokens);
      $token_type = is_array($token) ? $token[0] : null;
    } while ($this->tokens && $token_type !== T_OPEN_TAG);
  }

  private function consumeDefinition(DefinitionToken $def_type): void {
    $tname = token_name($def_type);

    $next = array_shift($this->tokens);
    invariant(
      is_array($next) && $next[0] === T_WHITESPACE,
      'Expect whitespace after %s in %s',
      $tname,
      realpath($this->file),
    );
    $next = array_shift($this->tokens);
    $next_type = is_array($next) ? $next[0] : null;
    invariant(
      $next_type === T_STRING || $next_type = T_XHP_LABEL,
      'Expected definition name after %s in %s',
      $tname,
      realpath($this->file),
    );
    $name = $next[1];
    if ($next_type === T_XHP_LABEL) {
      invariant(
        $def_type === DefinitionToken::CLASS_DEF,
        '%s is only valid as an XHP class name, not a %s - in %s',
        $name,
        $tname,
        realpath($this->file),
      );
      // 'class :foo:bar' is really 'class xhp_foo__bar'
      $name = 'xhp_'.str_replace(':', '__', substr($name, 1));
    }
    $fqn = $this->namespace.$name;

    switch ($def_type) {
      case DefinitionToken::NAMESPACE_DEF:
        $this->consumeNamespaceDefinition($name);
        break;
      case DefinitionToken::CLASS_DEF:
        $this->classes[] = $fqn;
        $this->skipToAndConsumeBlock();
        break;
      case DefinitionToken::INTERFACE_DEF:
        $this->interfaces[] = $fqn;
        $this->skipToAndConsumeBlock();
        break;
      case DefinitionToken::TRAIT_DEF:
        $this->traits[] = $fqn;
        $this->skipToAndConsumeBlock();
        break;
      case DefinitionToken::ENUM_DEF:
        $this->enums[] = $fqn;
        break;
      case DefinitionToken::TYPE_DEF:
        $this->types[] = $fqn;
        break;
      case DefinitionToken::NEWTYPE_DEF:
        $this->newtypes[] = $fqn;
        break;
      case DefinitionToken::FUNCTION_DEF:
        $this->functions[] = $fqn;
        break;
      case DefinitionToken::CONST_DEF:
        $this->constants[] = $fqn;
        break;
    }
  }

  private function consumeNamespaceDefinition(string $base): void {
    $name = $base;
    do {
      $next = array_shift($this->tokens);
      $next_type = is_array($next) ? $next[0] : null;
      if ($next_type === T_NS_SEPARATOR) {
        $next = array_shift($this->tokens);
        $next_type = is_array($next) ? $next[0] : null;
        invariant(
          $next_type === T_STRING,
          'Expected T_STRING after T_NS_SEPARATOR in %s, got %s',
          $this->file,
          token_name($next_type),
        );
        $name .= "\\".$next[1];
      } else {
        break;
      }
    } while ($this->tokens);

    $this->namespace = str_replace(
      "\\\\",
      "\\",
      $name."\\",
    );

  }

  private function skipToAndConsumeBlock(): void {
    $nesting = 0;
    while ($this->tokens) {
      $next = array_shift($this->tokens);
      if ($next === '{') {
        ++$nesting;
      } else if ($next === '}') {
        --$nesting;
        if ($nesting === 0) {
          return;
        }
      }
    }
  }
}
