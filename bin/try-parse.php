#!/usr/bin/env hhvm
<?hh
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

require_once (__DIR__.'/../vendor/hh_autoload.php');

function try_parse(string $path): void {
  printf('%s ... ', $path);
  try {
    FileParser::FromFile($path);
  } catch (\Exception $e) {
    $ret_code = -1;
    system(
      sprintf(
        '%s -l %s >/dev/null',
        escapeshellarg(PHP_BINARY),
        escapeshellarg($path),
      ),
      &$ret_code,
    );
    if ($ret_code !== 0) {
      print ("HHVM SYNTAX ERROR\n");
      return;
    }
    throw $e;
  }
  print ("OK\n");
}

$files = array_slice($argv, 1);

foreach ($files as $file) {
  try_parse($file);
}
