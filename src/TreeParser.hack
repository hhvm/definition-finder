/*
 *  Copyright (c) 2015-present, Facebook, Inc.
 *  All rights reserved.
 *
 *  This source code is licensed under the MIT license found in the
 *  LICENSE file in the root directory of this source tree.
 *
 */

namespace Facebook\DefinitionFinder;

use namespace HH\Lib\Vec;

final class TreeParser extends BaseParser {
  private function __construct(ScannedScope $defs) {
    $this->defs = $defs;
  }

  public static async function fromPathAsync(
    string $path,
  ): Awaitable<TreeParser> {
    $scopes = vec[];

    $rdi = new \RecursiveDirectoryIterator($path);
    $rii = new \RecursiveIteratorIterator($rdi);
    $parsers = vec[];
    foreach ($rii as $info) {
      if (!$info->isFile()) {
        continue;
      }
      if (!$info->isReadable()) {
        continue;
      }
      $ext = $info->getExtension();
      if ($ext !== 'php' && $ext !== 'hh' && $ext !== 'xhp' && $ext !== 'hack' && $ext !== 'hck') {
        continue;
      }
      $parsers[] = FileParser::fromFileAsync($info->getPathname());
    }
    $scopes = await Vec\map_async(
      $parsers,
      async $p ==> {
        $p = await $p;
        return $p->defs;
      },
    );

    return new self(merge_scopes(
      null,
      shape(
        'filename' => '__TREE__',
        'sourceType' => SourceType::MULTIPLE_FILES,
      ),
      $scopes,
    ));
  }
}
