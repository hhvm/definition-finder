#!/usr/bin/env hhvm
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

require_once(__DIR__.'/../vendor/autoload.php');

function try_parse(string $path): void {
  printf('%s... ', $path);
  FileParser::FromFile($path);
  print("OK\n");
}

$files = array_slice($argv, 1);

foreach ($files as $file) {
  try_parse($file);
}
