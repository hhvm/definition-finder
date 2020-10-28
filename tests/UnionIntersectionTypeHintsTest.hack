/*
 *  Copyright (c) 2015-present, Facebook, Inc.
 *  All rights reserved.
 *
 *  This source code is licensed under the MIT license found in the
 *  LICENSE file in the root directory of this source tree.
 *
 */

namespace Facebook\DefinitionFinder\Test;

use namespace HH\Lib\{C, Str, Vec};
use type Facebook\HackTest\{DataProvider, HackTest};
use type Facebook\DefinitionFinder\{FileParser, ScannedTypehint};
use function Facebook\FBExpect\expect;

final class UnionIntersectionTypeHintsTest extends HackTest {

  private static ?dict<string, ScannedTypehint> $allParams;

  <<__Override>>
  public static async function beforeFirstTestAsync(): Awaitable<void> {
    $parser = await FileParser::fromFileAsync(
      __DIR__.'/data/union_intersection_type_hints.hhi',
    );
    $params = dict[];
    foreach ($parser->getFunctions() as $function) {
      $params[$function->getShortName()] =
        C\onlyx($function->getParameters())->getTypehint() as nonnull;
    }
    self::$allParams = $params;
  }

  public static function provider(
  ): vec<(string, string, bool, string)> {
    $ns = 'Facebook\DefinitionFinder\Test';
    return vec[
      tuple('intersection', ScannedTypehint::INTERSECTION, false, '(I & J)'),
      tuple('union', ScannedTypehint::UNION, false, '(I | J)'),
      tuple('nullable_inter', ScannedTypehint::INTERSECTION, true, '?(I & J)'),
      tuple('nullable_union', ScannedTypehint::UNION, true, '?(I | J)'),
      tuple('complex', ScannedTypehint::INTERSECTION, false, '(I & ?(?J | K))'),
    ]
      |> Vec\map(
        $$,
        $testcase ==> {
          $testcase[3] = Str\replace_every(
            $testcase[3],
            dict['I' => $ns.'\\I', 'J' => $ns.'\\J', 'K' => $ns.'\\K'],
          );
          return $testcase;
        },
      );
  }

  <<DataProvider('provider')>>
  public async function test(
    string $function_name,
    string $expected_type_name,
    bool $expected_nullable,
    string $expected_type_text,
  ): Awaitable<void> {
    $type = self::$allParams as nonnull[$function_name];
    expect($type->getTypeName())->toEqual($expected_type_name);
    expect($type->isNullable())->toEqual($expected_nullable);
    expect($type->getTypeText())->toEqual($expected_type_text);
  }
}
