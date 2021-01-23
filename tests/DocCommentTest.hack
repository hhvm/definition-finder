/*
 *  Copyright (c) 2015-present, Facebook, Inc.
 *  All rights reserved.
 *
 *  This source code is licensed under the MIT license found in the
 *  LICENSE file in the root directory of this source tree.
 *
 */

namespace Facebook\DefinitionFinder\Test;

use function Facebook\FBExpect\expect;
use type Facebook\DefinitionFinder\{FileParser, ScannedDefinition, ScannedFunction};

use namespace HH\Lib\Vec;

class DocCommentTest extends \Facebook\HackTest\HackTest {
  private Map<string, ScannedDefinition> $defs = Map {};

  <<__Override>>
  public async function beforeEachTestAsync(): Awaitable<void> {
    $parser = await FileParser::fromFileAsync(__DIR__.'/data/doc_comments.php');
    $this->addDefs($parser->getClasses());
    $this->addDefs($parser->getFunctions());
    $this->addDefs($parser->getEnums());
    $this->addDefs($parser->getTypes());
    $this->addDefs($parser->getNewtypes());
  }

  private function addDefs(vec<ScannedDefinition> $defs): void {
    foreach ($defs as $def) {
      $this->defs[$def->getName()] = $def;
    }
  }

  public function testClassWithDoc(): void {
    $def = $this->getDef('ClassWithDocComment');
    expect($def->getDocComment())->toBeSame('/** class doc */');
  }

  public function testClassWithoutDoc(): void {
    $def = $this->getDef('ClassWithoutDocComment');
    expect($def->getDocComment())->toBeNull();
  }

  public function testFunctionWithDoc(): void {
    $def = $this->getDef('function_with_doc_comment');
    expect($def->getDocComment())->toBeSame('/** function doc */');
  }

  public function testFunctionWithoutDoc(): void {
    $def = $this->getDef('function_without_doc_comment');
    expect($def->getDocComment())->toBeNull();
  }

  public function testTypeWithDoc(): void {
    $def = $this->getDef('TypeWithDocComment');
    expect($def->getDocComment())->toBeSame('/** type doc */');
  }

  public function testNewtypeWithDoc(): void {
    $def = $this->getDef('NewtypeWithDocComment');
    expect($def->getDocComment())->toBeSame('/** newtype doc */');
  }

  public function testEnumWithDoc(): void {
    $def = $this->getDef('EnumWithDocComment');
    expect($def->getDocComment())->toBeSame('/** enum doc */');
  }

  public function testParameterWithDoc(): void {
    $fun = $this->getDef('param_with_doc_comment');
    $fun = expect($fun)->toBeInstanceOf(ScannedFunction::class);
    $params = $fun->getParameters();
    expect(Vec\map($params, $x ==> $x->getName()))->toBeSame(
      vec['commented', 'uncommented'],
    );
    expect(Vec\map($params, $x ==> $x->getDocComment()))->toBeSame(
      vec['/** param doc */', null],
    );
  }

  private function getDef(string $name): ScannedDefinition {
    return $this->defs['Facebook\\DefinitionFinder\\DocCommentTest\\'.$name];
  }
}
