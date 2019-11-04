/*
 *  Copyright (c) 2015-present, Facebook, Inc.
 *  All rights reserved.
 *
 *  This source code is licensed under the MIT license found in the
 *  LICENSE file in the root directory of this source tree.
 *
 */

// in root namespace because namespaced XHP classes are not supported yet
namespace {

final class :facebook:definition-finder:test:xhp-class-for-classname {
}

function facebook_definition_finder_test_xhp_class_for_classname(
): classname<mixed> {
  return :facebook:definition-finder:test:xhp-class-for-classname::class;
}

}
