<?php

declare(strict_types=1);

namespace PHireScript\Compiler\Parser\Managers\Token;

class Token {
    public function __construct(
        public readonly string $type,
        public readonly mixed $value,
        public readonly int $line,
        public readonly int $column,
        public ?string $processedBy = null,
    ) {
    }

    public function isDependencyScope(): bool {
        return $this->type === 'T_DEPENDENCY_SCOPE';
    }

    public function isComment(): bool {
        return $this->type === 'T_COMMENT';
    }

    public function isStringLiteral(): bool {
        return $this->type === 'T_STRING_LIT';
    }

    public function isNumber(): bool {
        return $this->type === 'T_NUMBER';
    }

    public function isKeyword(): bool {
        return $this->type === 'T_KEYWORD';
    }

    public function isGlobalConst(): bool {
        return $this->type === 'T_CONST';
    }

    public function isBool(): bool {
        return $this->type === 'T_BOOL';
    }
    public function isEndOfLine(): bool {
        return $this->type === 'T_EOL';
    }
    public function isWhiteSpace(): bool {
        return $this->type === 'T_WHITESPACE';
    }
    public function isAccessor(): bool {
        return $this->type === 'T_ACCESSORS';
    }

    public function isRange(): bool {
        return $this->type === 'T_RANGE';
    }

    public function isModifier(): bool {
        return $this->type === 'T_MODIFIER';
    }

    public function isMagicMethod(): bool {
        return $this->type === 'T_MAGIC_METHODS';
    }

    public function isType(): bool {
        return $this->isPrimitive() || $this->isSuperType() || $this->isMetaType();
    }
    public function isPrimitive(): bool {
        return $this->type === 'T_PRIMITIVE';
    }

    public function isSuperType(): bool {
        return $this->type === 'T_SUPER_TYPE';
    }

    public function isMetaType(): bool {
        return $this->type === 'T_META_TYPE';
    }

    public function isNull(): bool {
        return $this->type === 'T_NULL';
    }

    public function isVariable(): bool {
        return $this->type === 'T_VARIABLE';
    }
    public function isIdentifier(): bool {
        return $this->type === 'T_IDENTIFIER';
    }
    public function isSymbol(): bool {
        return $this->type === 'T_SYMBOL';
    }
    public function isBackslash(): bool {
        return $this->type === 'T_BACKSLASH';
    }

    public function isOpeningCurlyBracket(): bool {
        return $this->value === '{';
    }

    public function isClosingCurlyBracket(): bool {
        return $this->value === '}';
    }

    public function isOpeningParenthesis(): bool {
        return $this->value === '(';
    }

    public function isClosingParenthesis(): bool {
        return $this->value === ')';
    }

    public function isOpeningBracket(): bool {
        return $this->value === '[';
    }

    public function isClosingBracket(): bool {
        return $this->value === ']';
    }

    public function isLeftAngleBracket(): bool {
        return $this->value === '<';
    }

    public function isRightAngleBracket(): bool {
        return $this->value === '>';
    }

    public function isComma(): bool {
        return $this->value === ',';
    }

    public function isDot(): bool {
        return $this->value === '.';
    }

    public function isColon(): bool {
        return $this->value === ':';
    }

    public function isPipe(): bool {
        return $this->value === '|';
    }
}
