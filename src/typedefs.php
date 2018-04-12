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

// Composer can't autoload these, so put them all in one file that we tell
// composer to always autoload

type SourcePosition = shape('filename' => string, 'line' => ?int);

type AttributeMap = dict<string, vec<mixed>>;

enum VisibilityToken: int {
  T_PUBLIC = \T_PUBLIC;
  T_PRIVATE = \T_PRIVATE;
  T_PROTECTED = \T_PROTECTED;
}

enum VarianceToken: string {
  COVARIANT = '+';
  INVARIANT = '';
  CONTRAVARIANT = '-';
}

enum RelationshipToken: string {
  SUBTYPE = 'as';
  SUPERTYPE = 'super';
}

enum StaticityToken: string {
  IS_STATIC = 'static';
  NOT_STATIC = '';
}

enum AbstractnessToken: string {
  IS_ABSTRACT = 'abstract';
  NOT_ABSTRACT = '';
}

enum FinalityToken: string {
  IS_FINAL = 'final';
  NOT_FINAL = '';
}

enum NameNormalizationMode: string {
  REFERENCE = 'ref';
  DEFINITION = 'def';
}

enum SourceType: string {
  PHP = '<?php';
  HACK_STRICT = '<?hh // strict';
  HACK_PARTIAL = '<?hh';
  HACK_DECL = '<?hh // decl';
  MULTIPLE_FILES = '__multiple__';
  NOT_YET_DETERMINED = '__pending__';
  UNKNOWN = '__unknown__'; // Not PHP or Hack, as far as we know
}

const int T_SELECT = 422;
const int T_SHAPE = 402;
const int T_ON = 415;
const int T_DICT = 442;
const int T_VEC = 443;
const int T_KEYSET = 444;
const int T_WHERE = 445;
const int T_VARRAY = 446;
const int T_DARRAY = 447;
const int T_INOUT = 449;

// See ident_no_semireserved in hphp.y
enum StringishTokens: int {
  T_SELECT = T_SELECT;
  T_ON = T_ON;
  T_STRING = \T_STRING;
  T_SUPER = T_SUPER;
  T_WHERE = T_WHERE;
  T_XHP_CATEGORY = \T_XHP_CATEGORY;
  T_XHP_ATTRIBUTE = \T_XHP_ATTRIBUTE;
  T_XHP_CHILDREN = \T_XHP_CHILDREN;
  T_XHP_REQUIRED = \T_XHP_REQUIRED;
  T_ENUM = \T_ENUM;
  T_DICT = T_DICT;
  T_VEC = T_VEC;
  T_KEYSET = T_KEYSET;
  T_VARRAY = T_VARRAY;
  T_DARRAY = T_DARRAY;
  T_INOUT = T_INOUT;
}

enum UseStatementType: string {
  NAMESPACE_ONLY = 'use namespace';
  NAMESPACE_AND_TYPE = 'use';
  TYPE_ONLY = 'use type';
}
