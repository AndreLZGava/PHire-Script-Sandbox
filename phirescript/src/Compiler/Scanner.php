<?php

declare(strict_types=1);

namespace PHireScript\Compiler;

use PHireScript\Compiler\Parser\Managers\Token\Token;
use PHireScript\Helper\Debug\Debug;
use PHireScript\Runtime\RuntimeClass;

class Scanner
{
    private readonly string $code;
    private int $cursor = 0;
    private int $line = 1;
    private int $lineStartOffset = 0;

    private const PATTERNS = [
        'T_COMMENT'     => '/^\/\/.*|^\/\*[\s\S]*?\*\//',
        'T_STRING_LIT'  => '/^"([^"\\\\]*(?:\\\\.[^"\\\\]*)*)"|^\'([^\'\\\\]*(?:\\\\.[^\'\\\\]*)*)\'/',
        'T_RANGE' => '/^-?\d+\.\.-?\d+/',
        'T_NUMBER'      => '/^\d+(\.\d+)?/',
        'T_DEPENDENCY_SCOPE'     => '/^\b(singleton|scoped|transient|newable)\b/',
        'T_KEYWORD'     => '/^\b(class|interface|trait|type|extends|with|' .
            'implements|inject|async|spawn|return|immutable|try|handle|always' .
            'if|else|elseif|this|self|super|pkg|use|as|external|abstract|' .
            'schedule|cache|readonly|static|package)\b/',
        'T_MAGIC_METHODS' => '/^\b(onCreate|onDestroy|onGet|onSet|onHas|onUnset' .
            '|onCall|onStaticCall|toString|toSerialize|toUnserialize|beforeSerialize' .
            '|afterUnserialize|onClone|toInspect)\b/',
        // Magic Methods Mapping (FireScript → PHP)
        //
        // onCreate          → __construct
        // onDestroy         → __destruct
        //
        // onGet     → __get
        // onSet     → __set
        // onHas     → __isset
        // onUnset   → __unset
        //
        // onCall            → __call
        // onStaticCall      → __callStatic
        //
        // toString          → __toString
        //
        // toSerialize         → __serialize
        // toUnserialize       → __unserialize
        // beforeSerialize   → __sleep   (hook)
        // afterUnserialize  → __wakeup  (hook)
        //
        // onClone           → __clone
        //
        // toInspect           → __debugInfo
        'T_BOOL'        => '/^\b(true|false)\b/',
        'T_NULL'        => '/^\b(null)\b/',
        'T_EOL'         => '/^[\r\n]+/',
        'T_WHITESPACE'  => '/^[ \t]+/',
        'T_ACCESSORS'   => '/^(\+>|<>|#>|\*>|\+<|><|#<|\*<)/',
        'T_MODIFIER'    => '/^(\->|=>|::|\.\.\.|\+\+|--|==|!=|<=|>=|&&|\|\|)/',
        'T_PRIMITIVE' => '/^\b(Int|String|Float|Bool|Object|Array|Void|' .
            'Null|Mixed|Any|Queue|List|Stack|Map|Struct)\b/',
        'T_META_TYPE' => '/^\b(Card|Currency|Date|DateTime|Password' .
            '|Phone|Time)\b/',
        'T_SUPER_TYPE'        => '/^\b(Email|Ipv4|Ipv6|Uuid|Color|Url|' .
            'CardNumber|Cron|Cvv|Duration|ExpiryDate|Json|Mac|Slug)\b/',
        'T_CONST' => '/^[A-Z][A-Z0-9_]*\b/',
        'T_IDENTIFIER' => '/^[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff!?]*/',
        'T_SYMBOL'      => '/^([{}();,:=+<>\#!?\[\]\.$*\/%|-])/',
        'T_BACKSLASH' => '/^\\\\/',
    ];

    private const TEST_PATTERNS = [
        'T_TEST_KEYWORD'     => '/^\b(validate|skip|test)\b/',
        'T_TEST_HOOKS' => '/^\b(beforeAll|beforeEach|afterAll|afterEach)\b/',
    ];

    public function __construct(string $code, public string $path)
    {
        $this->code = \str_replace(["\r\n", "\r"], "\n", $code);
    }

    public function tokenize(): array
    {
        $tokens = [];
        $length = strlen($this->code);
        $patterns = self::PATTERNS;
        $isTestingFile = \str_ends_with($this->path, '.' . RuntimeClass::DEFAULT_FILE_TEST_EXTENSION);
        if($isTestingFile) {
            $patterns = array_merge(self::TEST_PATTERNS, $patterns);
        }

        while ($this->cursor < $length) {
            $snippet = substr($this->code, $this->cursor);
            $match = false;

            foreach ($patterns as $type => $pattern) {
                if (preg_match($pattern, $snippet, $matches)) {
                    $value = $matches[0];

                    $column = $this->cursor - $this->lineStartOffset + 1;

                    if ($type !== 'T_WHITESPACE') {
                        $tokens[] = new Token(
                            type: $type,
                            value: $value,
                            line: $this->line,
                            column: $column,
                        );
                    }

                    $lastNewlinePos = strrpos($value, "\n");
                    if ($lastNewlinePos !== false) {
                        $this->line += \substr_count($value, "\n");
                        $this->lineStartOffset = $this->cursor + $lastNewlinePos + 1;
                    }

                    $this->cursor += strlen($value);
                    $match = true;
                    break;
                }
            }

            if (!$match) {
                $this->cursor++;
            }
        }
        return $tokens;
    }
}
