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
use Facebook\DefinitionFinder\ScannedClass;
use Facebook\DefinitionFinder\ScannedMethod;

class ParameterTest extends \PHPUnit_Framework_TestCase {
  public function testWithoutTypes(): void {
    $data = '<?hh function foo($bar, $baz) {}';
    
    $parser = FileParser::FromData($data);
    $function = $parser->getFunction('foo');

    $params = $function->getParameters();

    $this->assertSame(2, count($params));
    $this->assertSame('$bar', $params[0]->getName());
    $this->assertSame('$baz', $params[1]->getName());
    $this->assertNull($params[0]->getTypehint());
    $this->assertNull($params[1]->getTypehint());
  }

  public function testWithSimpleType(): void {
    $data = '<?hh function foo(string $bar) {}';
    
    $parser = FileParser::FromData($data);
    $function = $parser->getFunction('foo');

    $params = $function->getParameters();
    $this->assertSame(1, count($params));
    $param = $params[0];
    $this->assertSame('$bar', $param->getName());
    $typehint = $param->getTypehint();
    $this->assertSame('string', $typehint?->getTypeName());
    $this->assertEmpty($typehint?->getGenerics());
  }

  public function testWithGenericType(): void {
    $data = '<?hh function foo(Vector<string> $bar) {}';
    
    $parser = FileParser::FromData($data);
    $function = $parser->getFunction('foo');

    $params = $function->getParameters();
    $this->assertSame(1, count($params));
    $param = $params[0];
    $this->assertSame('$bar', $param->getName());
    $typehint = $param->getTypehint();
    $this->assertSame('Vector', $typehint?->getTypeName());
    $this->assertEquals(
      Vector { 'string' },
      $typehint?->getGenerics()?->map($x ==> $x->getTypeName()),
    );
    $this->assertEquals(
      Vector { Vector { } },
      $typehint?->getGenerics()?->map($x ==> $x->getGenerics()),
    );
  }
}
