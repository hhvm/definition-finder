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

const keyset<string> AUTOIMPORT_TYPES = keyset[
  // not a real type
  'callable',
  // hack/src/parser/hh_autoimport.ml
  'bool',
  'int',
  'float',
  'string',
  'void',
  'num',
  'arraykey',
  'resource',
  'mixed',
  'noreturn',
  'this',
  'varray_or_darray',
  'vec_or_dict',
  'nonnull',
  'classname',
  'typename',
  'boolean',
  'integer',
  'double',
  'real',
  'dynamic',
  'vec',
  'dict',
  'keyset',
  '_',
  // hphp/hack/src/parser/namespaces.ml
  'Awaitable',
  'Vector',
  'Map',
  'Set',
  'ImmVector',
  'ImmMap',
  'ImmSet',
  'Traversable',
  'KeyedTraversable',
  'Container',
  'KeyedContainer',
  'Iterator',
  'KeyedIterator',
  'Iterable',
  'KeyedIterable',
  'Collection',
  'KeyedCollection',
  'IMemoizeParam',
  'AsyncIterator',
  'AsyncGenerator',
  'TypeStructure',
  'shape',
];
