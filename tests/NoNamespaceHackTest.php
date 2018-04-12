<?hh // strict
/*
 *  Copyright (c) 2015-present, Facebook, Inc.
 *  All rights reserved.
 *
 *  This source code is licensed under the MIT license found in the
 *  LICENSE file in the root directory of this source tree.
 *
 */

class NoNamespaceHackTest extends AbstractHackTest {
  protected function getFilename(): string {
    return 'no_namespace_hack.php';
  }

  protected function getPrefix(): string {
    return '';
  }

  protected function getSuffixForRootDefinitions(): string {
    return '';
  }
}
