/*
 *  Copyright (c) 2015-present, Facebook, Inc.
 *  All rights reserved.
 *
 *  This source code is licensed under the MIT license found in the
 *  LICENSE file in the root directory of this source tree.
 *
 */


class NoNamespacePHPTest extends AbstractPHPTest {
  <<__Override>>
  protected function getFilename(): string {
    return 'no_namespace_php.php';
  }

  <<__Override>>
  protected function getPrefix(): string {
    return '';
  }
}
