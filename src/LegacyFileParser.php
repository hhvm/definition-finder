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

use namespace HH\Lib\Str;

final class LegacyFileParser extends BaseParser {
  private function __construct(private string $file, TokenQueue $tq) {
    try {
      $this->defs = (
        new ScopeConsumer(
          $tq,
          shape(
            'filename' => $file,
            'namespace' => null,
            'usedNamespaces' => dict[],
            'usedTypes' => dict[],
            'sourceType' => SourceType::NOT_YET_DETERMINED,
            'genericTypeNames' => keyset[],
          ),
          ScopeType::FILE_SCOPE,
        )
      )
        ->getBuilder()
        ->build();
    } catch (/* HH_FIXME[2049] */ \HH\InvariantException $e) {
      throw new ParseException(
        shape('filename' => $file, 'line' => $tq->getLine()),
        $e,
      );
    }
  }

  ///// Constructors /////

  public static function FromFile(string $filename): LegacyFileParser {
    return self::FromData(\file_get_contents($filename), $filename);
  }

  public static function FromData(
    string $data,
    ?string $filename = null,
  ): LegacyFileParser {
    try {
      return new LegacyFileParser(
        $filename ?? '__DATA__',
        new TokenQueue($data),
      );
    } catch (\Exception $e) {
      if (!Str\starts_with($data, '<?php')) {
        throw $e;
      }
      // Tokenizer has started paying attention to if it's PHP or Hack; HHVM
      // tests can assume force_hh is on.
      //
      // We currently assume everything is tokenezed as Hack.
      // Proper fix is to move to hh_parse:
      // https://github.com/hhvm/definition-finder/issues/11
      $data = '<?hh // decl'.Str\strip_prefix($data, '<?php');
      return static::FromData($data, $filename);
    }
  }

  ///// Accessors /////

  public function getFilename(): string {
    return $this->file;
  }
}
