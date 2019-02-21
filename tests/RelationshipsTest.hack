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
use type Facebook\DefinitionFinder\FileParser;
use namespace HH\Lib\{C, Vec};

class RelationshipsTest extends \Facebook\HackTest\HackTest {
  public function testClassExtends(): void {
    $data = '<?hh class Foo extends Bar {}';
    $def = FileParser::fromData($data)->getClass('Foo');
    expect($def->getParentClassName())->toBeSame('Bar');
    expect($def->getInterfaceNames())->toBeEmpty();
  }

  public function testClassImplements(): void {
    $data = '<?hh class Foo implements Bar, Baz {}';
    $def = FileParser::fromData($data)->getClass('Foo');
    expect($def->getInterfaceNames())->toBeSame(vec['Bar', 'Baz']);
    expect($def->getParentClassName())->toBeNull();
  }

  public function testInterfaceExtends(): void {
    $data = '<?hh interface Foo extends Bar, Baz {}';
    $def = FileParser::fromData($data)->getInterface('Foo');
    expect($def->getInterfaceNames())->toBeSame(vec['Bar', 'Baz']);
    expect($def->getParentClassName())->toBeNull();
  }

  public function testClassExtendsAndImplements(): void {
    $data = '<?hh class Foo extends Bar implements Herp, Derp {}';
    $def = FileParser::fromData($data)->getClass('Foo');
    expect($def->getParentClassName())->toBeSame('Bar');
    expect($def->getInterfaceNames())->toBeSame(vec['Herp', 'Derp']);
  }

  public function testClassExtendsGeneric(): void {
    $data = '<?hh class Foo extends Bar<Baz> {}';
    $def = FileParser::fromData($data)->getClass('Foo');
    expect($def->getParentClassName())->toBeSame('Bar');
    expect($def->getParentClassInfo()?->getTypeText())->toBeSame('Bar<Baz>');
  }

  public function testClassImplementsGenerics(): void {
    $data = '<?hh class Foo implements KeyedIterable<Tk,Tv> {}';
    $def = FileParser::fromData($data)->getClass('Foo');
    expect($def->getInterfaceNames())->toBeSame(vec['KeyedIterable']);
    expect(Vec\map($def->getInterfaceInfo(), $x ==> $x->getTypeText()))
      ->toBeSame(vec['KeyedIterable<Tk,Tv>']);
  }

  public function testClassImplementsNestedGenerics(): void {
    $data = '<?hh class VectorIterable<Tv> implements Iterable<vec<Tv>> {}';
    $def = FileParser::fromData($data)->getClass('VectorIterable');
    expect($def->getInterfaceNames())->toBeSame(vec['Iterable']);
    expect(
      Vec\map(
        $def->getInterfaceInfo(),
        $x ==> Vec\map($x->getGenericTypes(), $y ==> $y->getTypeName()),
      ),
    )->toBeSame(vec[vec['vec']]);
    expect(Vec\map($def->getInterfaceInfo(), $x ==> $x->getTypeText()))
      ->toBeSame(vec['Iterable<vec<Tv>>']);
  }

  public function testTraitImplements(): void {
    $data = '<?hh interface IFoo {}; trait TFoo implements IFoo {}';
    $def = FileParser::fromData($data)->getTrait('TFoo');
    expect($def->getInterfaceNames())->toBeSame(vec['IFoo']);
  }

  public function testUsesTraits(): void {
    $data = '<?hh class Foo { use Herp; use Derp; }';
    $def = FileParser::fromData($data)->getClass('Foo');
    expect($def->getTraitNames())->toBeSame(vec['Herp', 'Derp']);
  }

  public function testUsesMultipleTraitsInSingleStatement(): void {
    $data = '<?hh class Foo { use Herp, Derp; }';
    $def = FileParser::fromData($data)->getClass('Foo');
    expect($def->getTraitNames())->toBeSame(vec['Herp', 'Derp']);
  }

  public function testUseTraitWithConflictResolution(): void {
    $data = "<?php\n".
      "class MyClass {\n".
      "  use Foo, Bar {\n".
      "    Foo::herp insteadof Bar;\n".
      "    Bar::herp as derp;\n".
      "  };\n".
      "}";
    $def = FileParser::fromData($data)->getClass('MyClass');
    expect($def->getTraitNames())->toBeSame(vec['Foo', 'Bar']);
  }

  public function testUsesTraitsInNamespace(): void {
    $data =
      "<?hh\n"."namespace MyNamespace;".'class Foo { use Herp; use Derp; }';
    $def = FileParser::fromData($data)->getClass('MyNamespace\\Foo');
    expect($def->getTraitNames())->toBeSame(
      vec['MyNamespace\\Herp', 'MyNamespace\\Derp'],
    );
  }

  public function testUsesGenericTrait(): void {
    $data = '<?hh class Foo { use Herp<string>; }';
    $def = FileParser::fromData($data)->getClass('Foo');
    $traits = $def->getTraitGenerics();
    expect(C\count($traits))->toBeSame(1);
    expect(C\first_key($traits))->toBeSame('Herp');
    expect(Vec\map(C\first($traits) ?? vec[], $a ==> $a->getTypeText()))
      ->toBeSame(vec['string']);
  }
}
