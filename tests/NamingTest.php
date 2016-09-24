<?hh // strict
/*
 *  Copyright (c) 2015, Facebook, Inc.
 *  All rights reserved.
 *
 *  This source code is licensed under the BSD-style license found in the
 *  LICENSE file in the root directory of this source tree. An additional grant
 *  of patent rights can be found in the PATENTS file in the same directory.
 *
 */

namespace Facebook\DefinitionFinder\Test;

use Facebook\DefinitionFinder\FileParser;

// Hack is unaware of this
const int T_SELECT = 422;
const int T_ON = 415;

class NamingTest extends \PHPUnit_Framework_TestCase {
  public function testFunctionCalledSelect(): void {
    // 'select' is a T_SELECT, not a T_STRING
    $data = '<?hh function select() {}';

    // Check that it parses
    $parser = FileParser::FromData($data);
    $this->assertNotNull($parser->getFunction('select'));
  }

  public function specialTypeProvider(): array<array<string>> {
    return [
      [ 'dict' ], // HHVM >= 3.13
      [ 'vec' ], // HHVM >= 3.14
      [ 'keyset' ], // HHVM >= 3.15
    ];
  }

  /** @dataProvider specialTypeProvider */
  public function testSpecialReturnType(string $type): void {
    $data = '<?hh function foo(): '.$type.' {}';
    $parser = FileParser::FromData($data);
    $func = $parser->getFunction('foo');
    $this->assertSame(
      $type,
      $func->getReturnType()?->getTypeName(),
    );
  }

  /** @dataProvider specialTypeProvider */
  public function testSpecialTypeAsFuncName(string $type): void {
    $data = '<?hh function '.$type.'(): void {}';
    $parser = FileParser::FromData($data);
    $func = $parser->getFunction($type);
    $this->assertSame(
      'void',
      $func->getReturnType()?->getTypeName(),
    );
    $this->assertSame(
      $type,
      $func->getName(),
    );
  }

  /** @dataProvider specialTypeProvider */
  public function testSpecialTypeAsClassName(string $type): void {
    $data = '<?hh class '.$type.' { }';
    $parser = FileParser::FromData($data);
    $class = $parser->getClass($type);
    $this->assertNotNull($class);
  }

  public function testConstantCalledOn(): void {
    $data = '<?hh class Foo { const ON = 0; }';

    $this->assertEquals(
      Vector { 'ON' },
      FileParser::FromData($data)
      ->getClass('Foo')
      ->getConstants()
      ->map($x ==> $x->getName())
    );
  }

  public function testClassMagicConstant(): void {
    $data = "<?hh Foo::class;\nclass Foo{}";

    // This could throw because the ; comes after the keyword class
    $this->assertEquals(
      'Foo',
      FileParser::FromData($data)
      ->getClass('Foo')
      ->getName()
    );
  }

  public function testClassConstant(): void {
    $data = "<?hh Herp::DERP;\nclass Foo{}";

    $this->assertEquals(
      'Foo',
      FileParser::FromData($data)
      ->getClass('Foo')
      ->getName()
    );
  }
}
