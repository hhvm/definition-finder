<?hh
/*
 *  Copyright (c) 2015-present, Facebook, Inc.
 *  All rights reserved.
 *
 *  This source code is licensed under the MIT license found in the
 *  LICENSE file in the root directory of this source tree.
 *
 */

namespace Facebook\DefinitionFinder\CurlyTest;

class Foo {
  public function herp(): noreturn {
    $bar = 'Bar';
    throw new \Exception("Foo {$bar}");
  }

  public function derp(): void {
  }
}

function my_func(): void {
}
