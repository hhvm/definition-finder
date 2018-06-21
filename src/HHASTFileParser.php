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
use namespace HH\Lib\{C, Str};

final class HHASTFileParser extends BaseParser {
  private function __construct(private string $file, HHAST\EditableNode $ast) {
    $this->defs = new ScannedScope(
      self::getScopeContext($file, $ast),
      vec[],
      vec[],
      vec[],
      vec[],
      vec[],
      vec[],
      vec[],
      vec[],
      vec[],
      vec[],
      vec[],
      vec[],
    );
  }

  ///// Constructors /////
  public static async function fromFileAsync(
    string $filename,
  ): Awaitable<this> {
    // TODO: use HHAST\from_file_async() when it's included in a release
    $ast = HHAST\from_file($filename);
    return new self($filename, $ast);
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
    $name = $suffix?->getName()?->getCode();
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
      'position' => shape('filename' => $file, 'line' => null),
      'sourceType' => $type,
    );
  }
}
