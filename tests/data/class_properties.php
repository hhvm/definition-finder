<?hh // strict
/*
 *  Copyright (c) 2015-present, Facebook, Inc.
 *  All rights reserved.
 *
 *  This source code is licensed under the MIT license found in the
 *  LICENSE file in the root directory of this source tree.
 *
 */

namespace Facebook\DefinitionFinder\Test;

class ClassWithProperties {
  private bool $foo = true;
  protected int $bar = 123;
  public string $herp = 'derp';

  public function varsArentProps(): void {
    $local = 'test';
  }
}

namespace Facebook\DefinitionFinder\Test2 {
  class ClassWithProperties {
    public bool $foobar = false;
    public function varsStillArentProps(): void {
      $local = true;
    }
  }
}
