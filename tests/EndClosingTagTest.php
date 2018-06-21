<?hh // strict
/*
 *  Copyright (c) 2015-present, Facebook, Inc.
 *  All rights reserved.
 *
 *  This source code is licensed under the MIT license found in the
 *  LICENSE file in the root directory of this source tree.
 *
 */

class EndClosingTagTest extends AbstractPHPTest {
  <<__Override>>
  protected function getFilename(): string {
    return 'end_closing_tag.php';
  }

  <<__Override>>
  protected function getPrefix(): string {
    return '';
  }
}
