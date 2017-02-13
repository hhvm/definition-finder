#!/bin/sh
#  Copyright (c) 2015, Facebook, Inc.
#  All rights reserved.
#
#  This source code is licensed under the BSD-style license found in the
#  LICENSE file in the root directory of this source tree. An additional grant
#  of patent rights can be found in the PATENTS file in the same directory.

set -e

if [ ! -d "$1" ]; then
  echo "Usage: $0 /path/to/hhvm/source/tree"
  exit 1
fi

HHVM="$1"
TRY_PARSE="$(dirname "$0")/try-parse.php"

# blacklist egrep is for usage of dict, vec, keyset, and facebook/hhvm#7668
gfind \
  "$HHVM/hphp/test/zend" \
  "$HHVM/hphp/test/quick" \
  "$HHVM/hphp/test/slow" \
  -name '*.php' \
| egrep -v 'quick/(dict|keyset|vec)/static.php|quick/init-basic.php|slow/parser/unicode-literal-error.php' \
| xargs "$TRY_PARSE"
