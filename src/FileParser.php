<?hh // strict
/*
 *  Copyright (c) 2015-present, Facebook, Inc.
 *  All rights reserved.
 *
 *  This source code is licensed under the BSD-style license found in the
 *  LICENSE file in the root directory of this source tree. An additional grant
 *  of patent rights can be found in the PATENTS file in the same directory.
 *
 */

namespace Facebook\DefinitionFinder;

class FileParser extends BaseParser {
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

  public static function FromFile(string $filename): FileParser {
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

  public function getFilename(): string {
    return $this->file;
  }
}
