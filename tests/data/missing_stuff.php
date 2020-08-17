<?hh
/*
 *  Copyright (c) 2015-present, Facebook, Inc.
 *  All rights reserved.
 *
 *  This source code is licensed under the MIT license found in the
 *  LICENSE file in the root directory of this source tree.
 *
 */

/**
 * Class with missing typehints and other things that aren't required in PHP,
 * Hack partial mode or Hack code with FIXMEs.
 */
class ClassWithMissingStuff {
  /* HH_FIXME[2086] missing visibility */
  /* HH_FIXME[4030] missing return type */
  function defaultVisibility() {}

  /* HH_FIXME[4030] missing return type */
  private function privateVisibility() {}

  /* HH_FIXME[2086] missing visibility */
  /* HH_FIXME[4030] missing return type */
  function alsoDefaultVisibility() {}

  /** FooDoc */
  const FOO = 'bar';
  /** BarDoc */
  /* HH_FIXME[2035] missing type */
  const BAR = 60 * 60 * 24;

  /* HH_FIXME[2001] missing type */
  private $untypedProperty;
}
