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

abstract class ScannedBase {
  // Namespace (e.g., of a class) if it exists
  private string $namespace;
  // Short name of the name without the namespace.
  // $shortname === $name if there is no namespace
  private string $shortName;

  public function __construct(
    private SourcePosition $position,
    private string $name,
    private Map<string, Vector<mixed>> $attributes,
    private ?string $docComment,
  ) {
    list($this->namespace, $this->shortName) = $this->breakName($name);
  }

  abstract public static function getType(): ?DefinitionType;

  public function getPosition(): SourcePosition {
    return $this->position;
  }

  public function getDocComment(): ?string {
    return $this->docComment;
  }

  public function getFileName(): string {
    return $this->position['filename'];
  }

  public function getName(): string {
    return $this->name;
  }

  public function getAttributes(): Map<string, Vector<mixed>> {
    return $this->attributes;
  }

  public function getNamespaceName(): ?string {
    return $this->namespace;
  }

  public function getShortName(): string {
    return $this->shortName;
  }

  // Break a name into its namespace (if exists) and short name.
  // Short name === name if no namespace
  private function breakName(string $name): (string, string) {
    $pos = strrpos($name, '\\');
    $ns = $pos !== false ? substr($name, 0, $pos) : '';
    $shortName = $ns !== '' ? substr($name, $pos + 1) : $name;
    return tuple($ns, $shortName);
  }
}
