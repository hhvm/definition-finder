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

class TreeParser extends BaseParser {
  protected ScannedScope $defs;

  private function __construct(string $path) {
    $builder = new ScannedScopeBuilder(shape(
      'position' => shape('filename' => '__TREE__'),
      'sourceType' => SourceType::MULTIPLE_FILES,
    ));

    $rdi = new \RecursiveDirectoryIterator($path);
    $rii = new \RecursiveIteratorIterator($rdi);
    foreach ($rii as $info) {
      if (!$info->isFile()) {
        continue;
      }
      if (!$info->isReadable()) {
        continue;
      }
      $ext = $info->getExtension();
      if ($ext !== 'php' && $ext !== 'hh' && $ext !== 'xhp') {
        continue;
      }
      $parser = FileParser::FromFile($info->getPathname());
      $builder->addSubScope($parser->defs);
    }
    $this->defs = $builder->build();
  }

  public static function FromPath(string $path): TreeParser {
    return new TreeParser($path);
  }
}
