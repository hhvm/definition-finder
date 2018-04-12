<?hh // strict
/*
 *  Copyright (c) 2015-present, Facebook, Inc.
 *  All rights reserved.
 *
 *  This source code is licensed under the MIT license found in the
 *  LICENSE file in the root directory of this source tree.
 *
 */

namespace Facebook\DefinitionFinder;

class ParseException extends \Exception {
  public function __construct(
    private SourcePosition $source,
    \Exception $previous,
  ) {
    parent::__construct(
      \sprintf(
        "%s:%d: %s",
        $source['filename'],
        Shapes::idx($source, 'line', -1),
        $previous->getMessage(),
      ),
      /* code = */ 0,
      $previous,
    );
  }

  public function getSourcePosition(): SourcePosition {
    return $this->source;
  }
}
