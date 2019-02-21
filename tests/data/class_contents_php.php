<?php
/*
 *  Copyright (c) 2015-present, Facebook, Inc.
 *  All rights reserved.
 *
 *  This source code is licensed under the MIT license found in the
 *  LICENSE file in the root directory of this source tree.
 *
 */

class ClassUsingAnonymousClass {
	public function methodOne() {
		$foo = new class {
			public function method_in_anonymous_class() {
				return true;
			}
		};

		return $foo->method_in_anonymous_class();
	}

	public function methodTwo() {
		return false;
  }
}
