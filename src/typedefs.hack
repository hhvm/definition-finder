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

type AttributeMap = dict<string, vec<mixed>>;

enum VisibilityToken: int {
  T_PUBLIC = 0;
  T_PRIVATE = 1;
  T_PROTECTED = 2;
}

enum OptionalityToken: int {
  IS_REQUIRED = 0;
  IS_OPTIONAL = 1;
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
