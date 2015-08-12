<?hh // strict
/*
 *  Copyright (c) 2015, Facebook, Inc.
 *  All rights reserved.
 *
 *  This source code is licensed under the BSD-style license found in the
 *  LICENSE file in the root directory of this source tree. An additional grant
 *  of patent rights can be found in the PATENTS file in the same directory.
 *
 */

namespace Facebook\DefinitionFinder;

final class UserAttributesConsumer extends Consumer {
  public function getUserAttributes(): AttributeMap {
    $attrs = Map { };
    while (true) {
      list($name, $_) = $this->tq->shift();
      if (!$attrs->containsKey($name)) {
        $attrs[$name] = Vector { };
      }

      list($t, $ttype) = $this->tq->shift();
      if ($ttype === T_SR) { // this was the last attribute
        return $attrs;
      }
      if ($t === ',') { // there's another
        continue;
      }

      // this attribute has values
      invariant(
        $t === '(',
        'Expected attribute name to be followed by >>, (, or , at line %d',
        $this->tq->getLine(),
      );

      while (true) {
        list($value, $ttype) = $this->tq->shift();
        switch ((int) $ttype) {
          case T_CONSTANT_ENCAPSED_STRING:
            $value = substr($value, 1, -1);
            break;
          case T_LNUMBER:
            $value = (int) $value;
            break;
          default:
            invariant_violation(
              "Invalid attribute value token type: %d",
              $ttype
            );
        }
        $attrs[$name][] = $value;
        list($t, $_) = $this->tq->shift();
        if ($t === ')') {
          break;
        }
        invariant(
          $t === ',',
          'Expected attribute value to be followed by , or ) at line %d',
          $this->tq->getLine(),
        );
      }
      list($t, $ttype) = $this->tq->shift();
      if ($ttype === T_SR) {
        return $attrs;
      }
      invariant(
        $t === ',',
        'Expected attribute value list to be followed by >> or ,',
      );
    }
    invariant_violation(
      'attribute list did not end at line %d',
      $this->tq->getLine(),
    );
  }
}
