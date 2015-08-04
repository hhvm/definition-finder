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

namespace Facebook\DefinitionFinder;

final class ScannedNewtype extends ScannedBase {
  public static function getType(): DefinitionType {
    return DefinitionType::NEWTYPE_DEF;
  }
}

class ScannedNewtypeBuilder extends ScannedSingleTypeBuilder<ScannedNewtype> {
  public function build(): ScannedNewtype {
    return new ScannedNewtype(
      nullthrows($this->position),
      nullthrows($this->namespace).$this->name,
      /* attributes = */ Map { },
    );
  }
}
