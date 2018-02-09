<?hh // strict
/*
 *  Copyright (c) 2015-present, Facebook, Inc.
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
  private vec<NGToken> $tokens = vec[];
  private int $line = 0;

  const type TSavedState = shape('tokens' => vec<NGToken>, 'line' => int);

  public function getState(): self::TSavedState {
    return shape('tokens' => $this->tokens, 'line' => $this->line);
  }

  public function restoreState(self::TSavedState $state): void {
    $this->tokens = vec($state['tokens']);
    $this->line = $state['line'];
  }

  public function __construct(string $data) {
    $line = 0;
    foreach (\token_get_all($data) as $token) {
      if (is_array($token)) {
        if ($token[0] === \T_HALT_COMPILER) {
          break;
        }
        $line = $token[2];
        $this->tokens[] = new NGToken(
          $token[1],
          $token[0],
          shape(
            'firstLine' => $line,
            'firstChar' => null,
            'lastChar' => null,
            'lastLine' => null,
          ),
        );
      } else {
        $this->tokens[] = new NGToken(
          $token,
          null,
          shape(
            'firstLine' => $line,
            'firstChar' => null,
            'lastChar' => null,
            'lastLine' => null,
          ),
        );
      }
    }
  }

  public function haveTokens(): bool {
    return (bool)$this->tokens;
  }

  public function isEmpty(): bool {
    return !$this->haveTokens();
  }

  public function shift(): Token {
    $token = $this->shiftNG();
    $this->line = $token->getPosition()['firstLine'];
    return $token->asLegacyToken();
  }

  public function shiftNG(): NGToken {
    invariant($this->haveTokens(), 'tried to shift without tokens');
    return \array_shift(&$this->tokens);
  }

  public function unshiftNG(NGToken $token): void {
    \array_unshift(&$this->tokens, $token);
  }

  public function peek(): Token {
    $t = $this->shiftNG();
    $this->unshiftNG($t);
    return $t->asLegacyToken();
  }

  public function getLine(): int {
    return $this->line;
  }
}
