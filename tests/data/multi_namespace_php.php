<?php
/*
 *  Copyright (c) 2015-present, Facebook, Inc.
 *  All rights reserved.
 *
 *  This source code is licensed under the MIT license found in the
 *  LICENSE file in the root directory of this source tree.
 *
 */

// Only valid in HNI files
function no_namespace_block() {}

namespace Foo {
  class Bar { }
  function myfunc() {}
}

namespace Herp {
  class Derp { }
  function myfunc() {}
}

namespace {
  class EmptyNamespace { }
  function myfunc() {}
}
