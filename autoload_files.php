<?php
/*
 *  Copyright (c) 2015-present, Facebook, Inc.
 *  All rights reserved.
 *
 *  This source code is licensed under the BSD-style license found in the
 *  LICENSE file in the root directory of this source tree. An additional grant
 *  of patent rights can be found in the PATENTS file in the same directory.
 *
 */

// Allow the package to be installed on PHP, not just HHVM
// you can't actually /use/ it, but this makes dependency management
// simpler for projects using h2tp or Phack
if (defined('HHVM_VERSION')) {
  $requires = [
    "src/typedefs.php",
    "src/utils.php"
  ];
  foreach ($requires as $require) {
    require_once(__DIR__.'/'.$require);
  }
}
