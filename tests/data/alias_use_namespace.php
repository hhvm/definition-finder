<?hh
/*
 *  Copyright (c) 2015-present, Facebook, Inc.
 *  All rights reserved.
 *
 *  This source code is licensed under the MIT license found in the
 *  LICENSE file in the root directory of this source tree.
 *
 */


namespace Foo {
  class Derp {}
}

namespace Bar {
  class Derp {}
}

namespace {
  use namespace Foo as Derp;
  use Bar\Derp;

  function main(
    Derp $x, /* Bar\Derp */
    /* HH_IGNORE_ERROR[2049] not valid in HHVM/Hack yet */
    Derp\Derp $y, /* Foo\Derp */
  ): void {
  }
}
