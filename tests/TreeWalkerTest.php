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

use \Facebook\DefinitionFinder\TreeWalker;

final class TreeWalkerTest extends PHPUnit_Framework_TestCase {
  private function createWalker(
    ?(function(string):bool) $filter
  ): TreeWalker {
    return new TreeWalker(realpath(__DIR__.'/../'), $filter);
  }

  public function testHasThisLibraryDefinitions(): void {
    $w = $this->createWalker(null);
    $this->assertContains(
      'Facebook\DefinitionFinder\TreeWalker',
      $w->getClasses()->keys(),
    );
    $this->assertContains(
      'Facebook\DefinitionFinder\DefinitionToken',
      $w->getEnums()->keys(),
    );
    $this->assertContains(
      'Facebook\DefinitionFinder\TreeDefinitions',
      $w->getInterfaces()->keys(),
    );

    $this->assertContains(
      realpath(__DIR__.'/../src/TreeWalker.php'),
      $w->getClasses()['Facebook\DefinitionFinder\TreeWalker'],
    );
  }

  public function testEachDefinitionKind(): void {
    $w = $this->createWalker(null);
    $this->assertContains('SimpleClass', $w->getClasses()->keys());
    $this->assertContains('SimpleInterface', $w->getInterfaces()->keys());
    $this->assertContains('SimpleTrait', $w->getTraits()->keys());
    $this->assertContains('MyEnum', $w->getEnums()->keys());
    $this->assertContains('MyType', $w->getTypes()->keys());
    $this->assertContains('MyNewtype', $w->getNewtypes()->keys());
    $this->assertContains('generic_function', $w->getFunctions()->keys());
    $this->assertContains('MY_CONST', $w->getConstants()->keys());
  }

  public function testPathFilters(): void {
    $w = $this->createWalker(null);
    $this->assertContains('SimpleClass', $w->getClasses()->keys());
    $this->assertContains('SingleNamespace\SimpleClass', $w->getClasses()->keys());

    $w = $this->createWalker($path ==> strpos($path, 'single_namespace') === false);
    $this->assertContains('SimpleClass', $w->getClasses()->keys());
    $this->assertNotContains('SingleNamespace\SimpleClass', $w->getClasses()->keys());
  }

  public function testContainsDuplicates(): void {
    $w = $this->createWalker(null);
    $this->assertContains('SimpleClass', $w->getClasses()->keys());
    
    $files = $w->getClasses()['SimpleClass'];
    $data = realpath(__DIR__.'/data');
    $this->assertContains($data.'/no_namespace_php.php', $files);
    $this->assertContains($data.'/mixed_php_html.php', $files);
  }
}
