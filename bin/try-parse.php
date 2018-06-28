#!/usr/bin/env hhvm
<?hh
/*
 *  Copyright (c) 2015-present, Facebook, Inc.
 *  All rights reserved.
 * *  This source code is licensed under the MIT license found in the
 *  LICENSE file in the root directory of this source tree.
 *
 */

namespace Facebook\DefinitionFinder;

require_once (__DIR__.'/../vendor/hh_autoload.php');

use namespace HH\Lib\Str;

function try_parse(string $path): void {
  \printf('%s ... ', $path);
  try {
    FileParser::fromFile($path);
  } catch (\Exception $e) {
    if (!Str\ends_with($path, '.hhi')) {
      $ret_code = -1;
      \system(
        \sprintf(
          '%s -l %s >/dev/null',
          \escapeshellarg(\PHP_BINARY),
          \escapeshellarg($path),
        ),
        &$ret_code,
      );
      if ($ret_code !== 0) {
        print("HHVM SYNTAX ERROR\n");
        return;
      }
    }
    $json = exec(
      'hh_parse --full-fidelity-json '.\escapeshellarg($path).' 2>/dev/null'
    );
    $json = Str\trim($json);
    if (json_decode($json) === null && \json_last_error() === \JSON_ERROR_DEPTH) {
      print("JSON TOO DEEP\n");
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
