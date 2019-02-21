/*
 *  Copyright (c) 2015-present, Facebook, Inc.
 *  All rights reserved.
 *
 *  This source code is licensed under the MIT license found in the
 *  LICENSE file in the root directory of this source tree.
 *
 */

namespace Facebook\DefinitionFinder;

final class ScannedInterface extends ScannedClassish {
  <<__Override>>
  public static function getType(): DefinitionType {
    return DefinitionType::INTERFACE_DEF;
  }
}
