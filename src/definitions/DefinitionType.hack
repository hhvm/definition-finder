/*
 *  Copyright (c) 2015-present, Facebook, Inc.
 *  All rights reserved.
 *
 *  This source code is licensed under the MIT license found in the
 *  LICENSE file in the root directory of this source tree.
 *
 */

namespace Facebook\DefinitionFinder;

enum DefinitionType: string {
  CLASS_DEF = 'class';
  INTERFACE_DEF = 'interface';
  TRAIT_DEF = 'trait';
  ENUM_DEF = 'enum';
  TYPE_DEF = 'type';
  NEWTYPE_DEF = 'newtype';
  FUNCTION_DEF = 'function';
  CONST_DEF = 'constant';
  SHAPE_FIELD_DEF = 'shape field';
}
