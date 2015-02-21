<?hh
/*
 *  Copyright (c) 2015, Facebook, Inc.
 *  All rights reserved.
 *
 *  This source code is licensed under the BSD-style license found in the
 *  LICENSE file in the root directory of this source tree. An additional grant
 *  of patent rights can be found in the PATENTS file in the same directory.
 *
 */

enum DefinitionToken: int {
  NAMESPACE_DEF = T_NAMESPACE;
  CLASS_DEF = T_CLASS;
  INTERFACE_DEF = T_INTERFACE;
  TRAIT_DEF = T_TRAIT;
  ENUM_DEF = T_ENUM;
  TYPE_DEF = 403; // facebook/hhvm#4872
  NEWTYPE_DEF = 405; // facebook/hhvm#4872
  FUNCTION_DEF = T_FUNCTION;
  CONST_DEF = T_CONST;
}
