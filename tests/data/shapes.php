<?hh // strict
/*
 *  Copyright (c) 2015-present, Facebook, Inc.
 *  All rights reserved.
 *
 *  This source code is licensed under the MIT license found in the
 *  LICENSE file in the root directory of this source tree.
 *
 */

/** A shape used for testing */
type MyExampleShape = shape(
  /** The foo */
  'foo' => string,
  /** The bar */
  ?'bar' => shape(
    /** The herp */
    'herp' => int,
    /** The derp */
    ?'derp' => string,
  ),
);
