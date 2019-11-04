<?hh
/*
 *  Copyright (c) 2015-present, Facebook, Inc.
 *  All rights reserved.
 *
 *  This source code is licensed under the MIT license found in the
 *  LICENSE file in the root directory of this source tree.
 *
 */

namespace Facebook\DefinitionFinder\DocCommentTest;

/** class doc */
class ClassWithDocComment {}

class ClassWithoutDocComment {}

/** function doc */
function function_with_doc_comment(): void {}

function function_without_doc_comment(): void {}

/** type doc */
type TypeWithDocComment = string;

/** newtype doc */
newtype NewtypeWithDocComment = string;

/** enum doc */
enum EnumWithDocComment: string {}

function param_with_doc_comment(
  /** param doc */
  int $commented,
  string $uncommented,
): void {}
