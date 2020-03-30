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
    private string $sourceFile,
    private ?SourcePosition $pos,
    \Exception $previous,
  ) {
    parent::__construct(
      \sprintf(
        '%s:%d:%d %s',
        $sourceFile,
        $pos['line'] ?? -1,
        $pos['character'] ?? -1,
        $previous->getMessage(),
      ),
      /* code = */ 0,
      $previous,
    );
  }

  public function getFilename(): string {
    return $this->sourceFile;
  }

  public function getPosition(): ?SourcePosition {
    return $this->pos;
  }
}
