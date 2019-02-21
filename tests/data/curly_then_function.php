<?php
/*
 *  Copyright (c) 2015-present, Facebook, Inc.
 *  All rights reserved.
 *
 *  This source code is licensed under the MIT license found in the
 *  LICENSE file in the root directory of this source tree.
 *
 */

class Foo {
  public function herp() {
    throw new Exception("Foo ${bar} {$bar}");
  }

  public function derp() {
  }
}

function my_func() {
}
