<?hh

namespace Foo {
  class Derp {}
}

namespace Bar {
  class Derp {}
}

namespace {
  /* HH_IGNORE_ERROR[1002] not valid in HHVM/Hack yet */
  use namespace Foo as Derp;
  use Bar\Derp;

  function main(
    Derp $x, /* Bar\Derp */
    /* HH_IGNORE_ERROR[2049] not valid in HHVM/Hack yet */
    Derp\Derp $y, /* Foo\Derp */
  ): void {
  }
}
