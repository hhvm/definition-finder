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

class ClassWithContents {
  private bool $foo = true;
  public string $herp = 'derp';

  /** FooDoc */
  const string FOO = 'bar';
  /** BarDoc */
  const int BAR = 60 * 60 * 24;

  public function publicMethod(): void {}
  protected function protectedMethod(): void {}
  private function privateMethod(): void {}

  public static function PublicStaticMethod(): void {}
}
