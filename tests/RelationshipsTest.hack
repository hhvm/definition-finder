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
  public async function testClassExtends(): Awaitable<void> {
    $data = '<?hh class Foo extends Bar {}';
    $def = (await FileParser::fromDataAsync($data))->getClass('Foo');
    expect($def->getParentClassName())->toBeSame('Bar');
    expect($def->getInterfaceNames())->toBeEmpty();
  }

  public async function testClassImplements(): Awaitable<void> {
    $data = '<?hh class Foo implements Bar, Baz {}';
    $def = (await FileParser::fromDataAsync($data))->getClass('Foo');
    expect($def->getInterfaceNames())->toBeSame(vec['Bar', 'Baz']);
    expect($def->getParentClassName())->toBeNull();
  }

  public async function testInterfaceExtends(): Awaitable<void> {
    $data = '<?hh interface Foo extends Bar, Baz {}';
    $def = (await FileParser::fromDataAsync($data))->getInterface('Foo');
    expect($def->getInterfaceNames())->toBeSame(vec['Bar', 'Baz']);
    expect($def->getParentClassName())->toBeNull();
  }

  public async function testClassExtendsAndImplements(): Awaitable<void> {
    $data = '<?hh class Foo extends Bar implements Herp, Derp {}';
    $def = (await FileParser::fromDataAsync($data))->getClass('Foo');
    expect($def->getParentClassName())->toBeSame('Bar');
    expect($def->getInterfaceNames())->toBeSame(vec['Herp', 'Derp']);
  }

  public async function testClassExtendsGeneric(): Awaitable<void> {
    $data = '<?hh class Foo extends Bar<Baz> {}';
    $def = (await FileParser::fromDataAsync($data))->getClass('Foo');
    expect($def->getParentClassName())->toBeSame('Bar');
    expect($def->getParentClassInfo()?->getTypeText())->toBeSame('Bar<Baz>');
  }

  public async function testClassImplementsGenerics(): Awaitable<void> {
    $data = '<?hh class Foo implements KeyedIterable<Tk,Tv> {}';
    $def = (await FileParser::fromDataAsync($data))->getClass('Foo');
    expect($def->getInterfaceNames())->toBeSame(vec['HH\\KeyedIterable']);
    expect($def->getInterfaceNames())->toBeSame(vec[KeyedIterable::class]);
    expect(Vec\map($def->getInterfaceInfo(), $x ==> $x->getTypeText()))
      ->toBeSame(vec['HH\\KeyedIterable<Tk,Tv>']);
  }

  public async function testClassImplementsNestedGenerics(): Awaitable<void> {
    $data = '<?hh class VectorIterable<Tv> implements Iterable<vec<Tv>> {}';
    $def = (await FileParser::fromDataAsync($data))->getClass('VectorIterable');
    expect($def->getInterfaceNames())->toBeSame(vec['HH\\Iterable']);
    expect(
      Vec\map(
        $def->getInterfaceInfo(),
        $x ==> Vec\map($x->getGenericTypes(), $y ==> $y->getTypeName()),
      ),
    )->toBeSame(vec[vec['vec']]);
    expect(Vec\map($def->getInterfaceInfo(), $x ==> $x->getTypeText()))
      ->toBeSame(vec['HH\\Iterable<vec<Tv>>']);
  }

  public async function testTraitImplements(): Awaitable<void> {
    $data = '<?hh interface IFoo {}; trait TFoo implements IFoo {}';
    $def = (await FileParser::fromDataAsync($data))->getTrait('TFoo');
    expect($def->getInterfaceNames())->toBeSame(vec['IFoo']);
  }

  public async function testUsesTraits(): Awaitable<void> {
    $data = '<?hh class Foo { use Herp; use Derp; }';
    $def = (await FileParser::fromDataAsync($data))->getClass('Foo');
    expect($def->getTraitNames())->toBeSame(vec['Herp', 'Derp']);
  }

  public async function testUsesMultipleTraitsInSingleStatement(): Awaitable<void> {
    $data = '<?hh class Foo { use Herp, Derp; }';
    $def = (await FileParser::fromDataAsync($data))->getClass('Foo');
    expect($def->getTraitNames())->toBeSame(vec['Herp', 'Derp']);
  }

  public async function testUseTraitWithConflictResolution(): Awaitable<void> {
    $data = "<?php\n".
      "class MyClass {\n".
      "  use Foo, Bar {\n".
      "    Foo::herp insteadof Bar;\n".
      "    Bar::herp as derp;\n".
      "  };\n".
      "}";
    $def = (await FileParser::fromDataAsync($data))->getClass('MyClass');
    expect($def->getTraitNames())->toBeSame(vec['Foo', 'Bar']);
  }

  public async function testUsesTraitsInNamespace(): Awaitable<void> {
    $data =
      "<?hh\n"."namespace MyNamespace;".'class Foo { use Herp; use Derp; }';
    $def = (await FileParser::fromDataAsync($data))->getClass('MyNamespace\\Foo');
    expect($def->getTraitNames())->toBeSame(
      vec['MyNamespace\\Herp', 'MyNamespace\\Derp'],
    );
  }

  public async function testUsesGenericTrait(): Awaitable<void> {
    $data = '<?hh class Foo { use Herp<string>; }';
    $def = (await FileParser::fromDataAsync($data))->getClass('Foo');
    $traits = $def->getTraitGenerics();
    expect(C\count($traits))->toBeSame(1);
    expect(C\first_key($traits))->toBeSame('Herp');
    expect(Vec\map(C\first($traits) ?? vec[], $a ==> $a->getTypeText()))
      ->toBeSame(vec['string']);
  }
}
