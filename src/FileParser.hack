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

final class FileParser extends BaseParser {
  private function __construct(private string $file, HHAST\Script $ast) {
    $context = shape(
      'definitionContext' => self::getScopeContext($file, $ast),
      'scopeType' => ScopeType::FILE_SCOPE,
      'ast' => $ast,
      'namespace' => null,
    );
    try {
      $this->defs = scope_from_ast($context, $ast->getDeclarations());
    } catch (namespace\Exception $e) {
      throw $e;
    } catch (\Exception $e) {
      throw new ParseException(
        $file,
        /* pos = */ null,
        $e,
      );
    }
  }

  ///// Constructors /////

  public static async function fromFileAsync(
    string $filename,
  ): Awaitable<this> {
    try {
      $ast = await HHAST\from_file_async(HHAST\File::fromPath($filename));
    } catch (HHAST\ASTDeserializationError $_) {
      return new self($filename, new HHAST\Script(new HHAST\NodeList(vec[])));
    }
    return new self($filename, $ast);
  }

  public static async function fromDataAsync(
    string $data,
    ?string $filename = null,
  ): Awaitable<this> {
    $filename ??= '__DATA__';
    try {
      $ast = await HHAST\from_file_async(
        HHAST\File::fromPathAndContents($filename, $data),
      );
    } catch (HHAST\ASTDeserializationError $_) {
      return new self($filename, new HHAST\Script(new HHAST\NodeList(vec[])));
    }
    return new self($filename, $ast);
  }

  ///// Accessors /////

  public function getFilename(): string {
    return $this->file;
  }

  ///// Implementation /////
  private static function getScopeContext(
    string $file,
    HHAST\Node $ast,
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
