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

namespace Facebook\DefinitionFinder\DocCommentTest;

/** class doc */
class ClassWithDocComment {}

class ClassWithoutDocComment {}

/** function doc */
function function_with_doc_comment() {}

function function_without_doc_comment() {}

/** type doc */
type TypeWithDocComment = string;

/** newtype doc */
newtype NewtypeWithDocComment = string;

/** enum doc */
enum EnumWithDocComment: string {}

function param_with_doc_comment(/** param doc */ $commented, $uncommented) {}
