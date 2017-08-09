<?hh // strict
/*
 *  Copyright (c) 2015-present, Facebook, Inc.
 *  All rights reserved.
 *
 *  This source code is licensed under the BSD-style license found in the
 *  LICENSE file in the root directory of this source tree. An additional grant
 *  of patent rights can be found in the PATENTS file in the same directory.
 *
 */

namespace Facebook\DefinitionFinder\Test;

use Facebook\DefinitionFinder\FileParser;
use Facebook\DefinitionFinder\ScannedBase;
use Facebook\DefinitionFinder\ScannedFunction;

class DocCommentTest extends \PHPUnit_Framework_TestCase {
  private Map<string, ScannedBase> $defs = Map {};

  protected function setUp(): void {
    $parser = FileParser::FromFile(__DIR__.'/data/doc_comments.php');
    $this->addDefs($parser->getClasses());
    $this->addDefs($parser->getFunctions());
    $this->addDefs($parser->getEnums());
    $this->addDefs($parser->getTypes());
    $this->addDefs($parser->getNewtypes());
  }

  private function addDefs(\ConstVector<ScannedBase> $defs): void {
    foreach ($defs as $def) {
      $this->defs[$def->getName()] = $def;
    }
  }

  public function testClassWithDoc(): void {
    $def = $this->getDef('ClassWithDocComment');
    $this->assertSame('/** class doc */', $def->getDocComment());
  }

  public function testClassWithoutDoc(): void {
    $def = $this->getDef('ClassWithoutDocComment');
    $this->assertNull($def->getDocComment());
  }

  public function testFunctionWithDoc(): void {
    $def = $this->getDef('function_with_doc_comment');
    $this->assertSame('/** function doc */', $def->getDocComment());
  }

  public function testFunctionWithoutDoc(): void {
    $def = $this->getDef('function_without_doc_comment');
    $this->assertNull($def->getDocComment());
  }

  public function testTypeWithDoc(): void {
    $def = $this->getDef('TypeWithDocComment');
    $this->assertSame('/** type doc */', $def->getDocComment());
  }

  public function testNewtypeWithDoc(): void {
    $def = $this->getDef('NewtypeWithDocComment');
    $this->assertSame('/** newtype doc */', $def->getDocComment());
  }

  public function testEnumWithDoc(): void {
    $def = $this->getDef('EnumWithDocComment');
    $this->assertSame('/** enum doc */', $def->getDocComment());
  }

  public function testParameterWithDoc(): void {
    $fun = $this->getDef('param_with_doc_comment');
    assert($fun instanceof ScannedFunction);
    $params = $fun->getParameters();
    $this->assertEquals(
      Vector { 'commented', 'uncommented' },
      $params->map($x ==> $x->getName()),
    );
    $this->assertEquals(
      Vector { '/** param doc */', null },
      $params->map($x ==> $x->getDocComment()),
    );
  }

  private function getDef(string $name): ScannedBase {
    return $this->defs['Facebook\\DefinitionFinder\\DocCommentTest\\'.$name];
  }
}
