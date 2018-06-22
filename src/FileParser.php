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

use namespace Facebook\HHAST;
use namespace HH\Lib\{C, Str, Vec};

final class FileParser extends BaseParser {
  private function __construct(private string $file, HHAST\Script $ast) {
    $context = shape(
      'definitionContext' => self::getScopeContext($file, $ast),
      'ast' => $ast,
      'namespace' => null,
      'usedTypes' => dict[],
      'usedNamespaces' => dict[],
      'genericTypeNames' => keyset[],
    );
    $this->defs = scope_from_ast($context, $ast->getDeclarations());
  }

  ///// Constructors /////

  public static function fromFile(string $filename): this {
    $ast = HHAST\from_file($filename);
    invariant(
      $ast instanceof HHAST\Script,
      "Expected the top-level definition to be a Script, got a %s",
      \get_class($ast),
    );
    return new self($filename, $ast);
  }

  public static function fromData(
    string $data,
    ?string $filename = null,
  ): this {
    $ast = HHAST\from_code($data);
    invariant(
      $ast instanceof HHAST\Script,
      "Expected top-level definition to be a Script, got a %s",
      \get_class($ast),
    );
    return new self($filename ?? '__DATA__', $ast);
  }

  ///// Accessors /////

  public function getFilename(): string {
    return $this->file;
  }

  ///// Implementation /////
  private static function getScopeContext(
    string $file,
    HHAST\EditableNode $ast,
  ): ScannedScope::TContext {
    $suffix = C\first($ast->getDescendantsOfType(HHAST\MarkupSuffix::class));
    $name = $suffix?->getName()?->getText();
    if ($name === 'php' || $name === '' || $name === null) {
      $type = SourceType::PHP;
    } else if ($name === 'hh') {
      $mode = nullthrows($suffix)->getLastTokenx()->getTrailing()->getCode()
        |> Str\trim($$) // '// strict' or //strict'
        |> Str\strip_prefix($$, '//')
        |> Str\trim($$);
      if ($mode === 'strict') {
        $type = SourceType::HACK_STRICT;
      } else if ($mode === 'decl') {
        $type = SourceType::HACK_DECL;
      } else {
        $type = SourceType::HACK_PARTIAL;
      }
    } else {
      $type = SourceType::UNKNOWN;
    }
    return shape(
      'filename' => $file,
      'sourceType' => $type,
    );
  }
}
