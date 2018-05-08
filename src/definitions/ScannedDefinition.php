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

abstract class ScannedDefinition {
  const type TContext =
    shape('position' => SourcePosition, 'sourceType' => SourceType);
  // Namespace (e.g., of a class) if it exists
  private string $namespace;
  // Short name of the name without the namespace.
  // $shortname === $name if there is no namespace
  private string $shortName;

  public function __construct(
    private string $name,
    private self::TContext $context,
    private dict<string, vec<mixed>> $attributes,
    private ?string $docComment,
  ) {
    list($this->namespace, $this->shortName) = $this->breakName($name);
  }

  abstract public static function getType(): ?DefinitionType;

  public function getPosition(): SourcePosition {
    return $this->context['position'];
  }

  public function getDocComment(): ?string {
    return $this->docComment;
  }

  public function getContext(): self::TContext {
    return $this->context;
  }

  public function getFileName(): string {
    return $this->context['position']['filename'];
  }

  public function getSourceType(): SourceType {
    return $this->context['sourceType'];
  }

  public function getName(): string {
    return $this->name;
  }

  public function getAttributes(): dict<string, vec<mixed>> {
    return $this->attributes;
  }

  public function getNamespaceName(): string {
    return $this->namespace;
  }

  public function getShortName(): string {
    return $this->shortName;
  }

  // Break a name into its namespace (if exists) and short name.
  // Short name === name if no namespace
  private function breakName(string $name): (string, string) {
    $pos = \strrpos($name, '\\');
    $ns = $pos !== false ? \substr($name, 0, $pos) : '';
    $shortName = $ns !== '' ? \substr($name, $pos + 1) : $name;
    return tuple($ns, $shortName);
  }
}
