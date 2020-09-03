/*
 *  Copyright (c) 2015-present, Facebook, Inc.
 *  All rights reserved.
 *
 *  This source code is licensed under the MIT license found in the
 *  LICENSE file in the root directory of this source tree.
 *
 */

namespace Facebook\DefinitionFinder {
  // For historical reasons, the new `xhp class` syntax allows declaring any
  // part of the namespace inside the class name.
  final xhp class test:xhp_class_for_classname {
  }
}

namespace {
  function facebook_definition_finder_test_xhp_class_for_classname(
  ): classname<mixed> {
    return Facebook\DefinitionFinder\test\xhp_class_for_classname::class;
  }
}
