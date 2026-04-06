<?php

declare(strict_types=1);

namespace PHireScript\Lexer;

use PhpParser\Lexer\Emulative;
use PhpParser\Parser\Tokens;

class CustomLexer extends Emulative
{
    public function getTokens(): array
    {
        $tokens = null;
        $newTokens = [];

        foreach ($tokens as $token) {
            if (is_array($token) && $token[0] === Tokens::T_STRING) {
                $value = $token[1];
                $reserved = ['echo', 'var', 'func', 'return', 'if', 'else', 'new', 'stdClass', 'true', 'false'];

                if (!\in_array($value, $reserved, true)) {
                    $token[0] = Tokens::T_VARIABLE;
                }
            }
            $newTokens[] = $token;
        }
        return $newTokens;
    }
}
