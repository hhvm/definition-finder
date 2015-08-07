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
type TokenWithLine = (Token, int);

class TokenQueue {
  private Vector<TokenWithLine> $tokens = Vector {};
  private int $line = 0;

  public function __construct(string $data) {
    $line = 0;
    foreach (token_get_all($data) as $token) {
      if (is_array($token)) {
        $line = $token[2];
        $this->tokens[] = tuple(tuple($token[1], $token[0]), $line);
      } else {
        $this->tokens[] = tuple(tuple($token, null), $line);
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
    list($token, $line) = array_shift($this->tokens);
    $this->line = $line;
    return $token;
  }

  public function unshift(TokenValue $v, TokenType $t): void {
    $token = tuple($v, $t);
    array_unshift($this->tokens, tuple($token, $this->line));
  }

  public function peek(): Token {
    $t = $this->shift();
    list($s, $ttype) = $t;
    $this->unshift($s, $ttype);
    return $t;
  }

  public function getLine(): int {
    return $this->line;
  }
}
