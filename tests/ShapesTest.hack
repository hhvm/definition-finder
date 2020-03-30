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
  ScannedShapeField,
  ScannedType,
  ScannedTypehint,
};
use namespace HH\Lib\{C, Vec};

final class ShapesTest extends \Facebook\HackTest\HackTest {
  private async function getTypeAliasAsync(): Awaitable<ScannedType> {
    return (await FileParser::fromFileAsync(__DIR__.'/data/shapes.php'))
      ->getType('MyExampleShape');
  }

  private async function getShapeAsync(): Awaitable<ScannedTypehint> {
    return (await $this->getTypeAliasAsync())->getAliasedType();
  }

  private async function getFieldsAsync(): Awaitable<vec<ScannedShapeField>> {
    return (await $this->getShapeAsync())->getShapeFields();
  }

  public async function testFieldNames(): Awaitable<void> {
    expect(Vec\map(
      (await $this->getFieldsAsync()),
      $f ==> $f->getName()->getStaticValue(),
    ))
      ->toBeSame(vec['foo', 'bar']);
  }

  public async function testOptionality(): Awaitable<void> {
    expect(Vec\map((await $this->getFieldsAsync()), $f ==> $f->isOptional()))
      ->toBeSame(vec[false, true]);
  }

  public async function testDocComments(): Awaitable<void> {
    expect((await $this->getTypeAliasAsync())->getDocComment())->toBeSame(
      '/** A shape used for testing */',
    );
    expect(Vec\map((await $this->getFieldsAsync()), $f ==> $f->getDocComment()))
      ->toBeSame(vec['/** The foo */', '/** The bar */']);
  }

  public async function testBasicFieldTypes(): Awaitable<void> {
    expect(
      C\firstx((await $this->getFieldsAsync()))->getValueType()->getTypeText(),
    )
      ->toBeSame('string');
  }

  public async function testNestedShapes(): Awaitable<void> {
    $inner = C\lastx((await $this->getFieldsAsync()))->getValueType();
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
