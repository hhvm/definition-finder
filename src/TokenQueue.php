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

type TokenValue = string;
type TokenType = ?int;
type Token = (TokenValue, TokenType);

class TokenQueue {
  private Vector<Token> $tokens = Vector {};

  public function __construct(string $data) {
    foreach (token_get_all($data) as $token) {
      if (is_array($token)) {
        $this->tokens[] = tuple($token[1], $token[0]);
      } else {
        $this->tokens[] = tuple($token, null);
      }
    }
  }

  public function haveTokens(): bool {
    return (bool) $this->tokens;
  }

  public function isEmpty(): bool {
    return !$this->haveTokens();
  }

  public function shift(): Token {
    invariant($this->haveTokens(), 'tried to shift without tokens');
    return array_shift($this->tokens);
  }

  public function unshift(TokenValue $v, TokenType $t): void {
    array_unshift($this->tokens, tuple($v, $t));
  }
}
