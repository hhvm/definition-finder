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
use type Facebook\DefinitionFinder\{
  FileParser,
  ScannedType,
  ScannedShapeField,
  ScannedTypehint,
};
use namespace HH\Lib\{C, Vec};

final class ShapesTest extends \Facebook\HackTest\HackTest {
  private function getTypeAlias(): ScannedType {
    return FileParser::fromFile(__DIR__.'/data/shapes.php')
      ->getType('MyExampleShape');
  }

  private function getShape(): ScannedTypehint {
    return $this->getTypeAlias()->getAliasedType();
  }

  private function getFields(): vec<ScannedShapeField> {
    return $this->getShape()->getShapeFields();
  }

  public function testFieldNames(): void {
    expect(Vec\map($this->getFields(), $f ==> $f->getName()->getStaticValue()))
      ->toBeSame(vec['foo', 'bar']);
  }

  public function testOptionality(): void {
    expect(Vec\map($this->getFields(), $f ==> $f->isOptional()))->toBeSame(
      vec[false, true],
    );
  }

  public function testDocComments(): void {
    expect($this->getTypeAlias()->getDocComment())->toBeSame(
      "/** A shape used for testing */",
    );
    expect(Vec\map($this->getFields(), $f ==> $f->getDocComment()))->toBeSame(
      vec['/** The foo */', '/** The bar */'],
    );
  }

  public function testBasicFieldTypes(): void {
    expect(C\firstx($this->getFields())->getValueType()->getTypeText())
      ->toBeSame('string');
  }

  public function testNestedShapes(): void {
    $inner = C\lastx($this->getFields())->getValueType();
    expect($inner->getTypeName())->toBeSame('shape');

    $fields = $inner->getShapeFields();
    expect(Vec\map($fields, $f ==> $f->getName()->getStaticValue()))->toBeSame(
      vec['herp', 'derp'],
    );
    expect(Vec\map($fields, $f ==> $f->getDocComment()))
      ->toBeSame(vec['/** The herp */', '/** The derp */']);
    expect(Vec\map($fields, $f ==> $f->getValueType()->getTypeText()))
      ->toBeSame(vec['int', 'string']);
  }
}
