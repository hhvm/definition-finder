<?hh // strict
/*
 *  Copyright (c) 2015-present, Facebook, Inc.
 *  All rights reserved.
 *
 *  This source code is licensed under the MIT license found in the
 *  LICENSE file in the root directory of this source tree.
 *
 */

class NestedNamespaceHackTest extends AbstractHackTest {
  protected function getFilename(): string {
    return 'nested_namespace_hack.php';
  }

  protected function getPrefix(): string {
    return 'Namespaces\\AreNestedNow\\';
  }

  protected function getSuffixForRootDefinitions(): string {
    return '_FROM_NESTED_NS';
  }
}
