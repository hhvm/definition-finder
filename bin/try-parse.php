#!/usr/bin/env hhvm
<?hh // partial
/*
 *  Copyright (c) 2015-present, Facebook, Inc.
 *  All rights reserved.
 * *  This source code is licensed under the MIT license found in the
 *  LICENSE file in the root directory of this source tree.
 *
 */

namespace Facebook\DefinitionFinder;

use namespace HH\Lib\{Str, Vec};

async function try_parse_async(string $path): Awaitable<void> {
  $line = Str\format('%s ... ', $path);
  try {
    await FileParser::fromFileAsync($path);
  } catch (\Exception $e) {
    if (!Str\ends_with($path, '.hhi')) {
      $ret_code = -1;
      \system(
        \sprintf(
          '%s -l %s >/dev/null',
          \escapeshellarg(\PHP_BINARY),
          \escapeshellarg($path),
        ),
        inout $ret_code,
      );
      if ($ret_code !== 0) {
        print $line."HHVM SYNTAX ERROR\n";
        return;
      }
    }
    $_output_array = null;
    $_exit_code = null;
    $json = \exec(
      'hh_parse --full-fidelity-json '.\escapeshellarg($path).' 2>/dev/null',
      inout $_output_array,
      inout $_exit_code,
    );
    $json = Str\trim($json);
    $json_error = null;
    if (
      \json_decode_with_error($json, inout $json_error) is null &&
      $json_error is nonnull &&
      $json_error[0] === \JSON_ERROR_DEPTH
    ) {
      print $line."JSON TOO DEEP";
      return;
    }
    print $line;
    throw $e;
  }
  print $line."OK\n";
}

<<__EntryPoint>>
async function try_parse_main_async(): Awaitable<void> {
  require_once(__DIR__.'/../vendor/hh_autoload.php');
  $files = Vec\drop(\HH\global_get('argv') as Traversable<_>, 1);
  await Vec\map_async(
    $files,
    async $file ==> await try_parse_async($file as string),
  );
}
