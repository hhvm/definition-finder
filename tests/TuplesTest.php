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
use Facebook\DefinitionFinder\ScannedTypehint;

class TuplesTest extends \PHPUnit_Framework_TestCase {
  public function testTupleReturnType(): void {
    $data = '<?hh

<<__Native>>
function foo(): (string, string);
';

    $parser = FileParser::FromData($data);
    $function = $parser->getFunction('foo');

    $this->assertEquals(
      [
        'tuple',
        [
          ['string', []],
          ['string', []],
        ],
      ],
      $this->sthToArray($function->getReturnType()),
    );
  }

  public function testContainerOfTuples(): void {
    $data = '<?hh

<<__Native>>
function foo(): Vector<(string, string)>;
';

    $parser = FileParser::FromData($data);
    $function = $parser->getFunction('foo');

    $return_type = $function->getReturnType();

    $this->assertEquals(
      [
        'Vector',
        [
          [
            'tuple',
            [
              ['string', []],
              ['string', []],
            ],
          ],
        ],
      ],
      $this->sthToArray($function->getReturnType()),
    );
  }

  public function testTupleParameterType(): void {
    $data = '<?hh

function foo((string, string) $bar) {};
';

    $parser = FileParser::FromData($data);
    $function = $parser->getFunction('foo');

    $params = $function->getParameters();
    $this->assertEquals(
      Vector { '$bar' },
      $params->map($x ==> $x->getName()),
    );
    $this->assertEquals(
      [['tuple', [['string', []], ['string', []]]]],
      $params->map($x ==> $this->sthToArray($x->getTypehint()))->toArray(),
    );
  }

  private function sthToArray(?ScannedTypehint $typehint): ?array<mixed> {
    if ($typehint === null) {
      return null;
    }

    $generics = $typehint->getGenerics()->map(
      $x ==> $this->sthToArray($x),
    )->toArray();

    return [$typehint->getTypeName(), $generics];
  }
}
