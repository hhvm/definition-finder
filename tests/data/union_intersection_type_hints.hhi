<?hh // strict
/*
 *  Copyright (c) 2015-present, Facebook, Inc.
 *  All rights reserved.
 *
 *  This source code is licensed under the MIT license found in the
 *  LICENSE file in the root directory of this source tree.
 *
 */

<<file: __EnableUnstableFeatures('union_intersection_type_hints')>>

namespace Facebook\DefinitionFinder\Test;

interface I {}
interface J {}
interface K {}

function intersection((I & J) $_x): void {}
function union((I | J) $_x): void {}
function nullable_inter(?(I & J) $_x): void {}
function nullable_union(?(I | J) $_x): void {}
function complex((I & ?(?J | K)) $_x): void {}
