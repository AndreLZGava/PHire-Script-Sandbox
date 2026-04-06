<?php

declare(strict_types=1);

namespace PHireScript\Compiler;

use PHireScript\Helper\Debug\Debug;
use PHireScript\Runtime\Exceptions\CompileException;
use PHireScript\Runtime\RuntimeClass;

class Validator
{
    private array $open = ['(' => 0, '{' => 0, '[' => 0, '<' => 0];
    private array $close = [')' => 0, '}' => 0, ']' => 0, '>' => 0];
    private array $forbidden = [
        'namespace' => 'Use "pkg" to declare a package',

        ';' => 'Use break line instead!',
        '::' => 'Use ":" line instead!',
        '->' => 'Use "." line instead!',

        '><' => 'Split like "< >" and follow the order!',
        '#<' => 'Split like "# <" and follow the order!',
        '*<' => 'Split like "* <" and follow the order!',
        '+<' => 'Split like "+ <" and follow the order!',
        '<>' => 'Split like "< >" and follow the order!',
        '#>' => 'Split like "# >" and follow the order!',
        '*>' => 'Split like "* >" and follow the order!',
        '+>' => 'Split like "+ >" and follow the order!',

        'void' => 'Use "Void" instead!',
        'string' => 'Use "String" instead!',
        'int' => 'Use "Int" instead!',
        'float' => 'Use "Float" instead!',
        'stdClass' => 'Use "{}" instead!',
        'array' => 'Use "Array" instead!',
        'bool' => 'Use "Bool" instead!',

        'array_key_exists' => 'Use "hasKey" method, allowed to any array variable!',

        'die' => 'Use "Exit" instead!',
        'eval' => 'Use of eval not permitted!',
        'var_dump' => 'Use "Debug.show(...args)" instead!',

        'public' => 'Use "*" or just leave without definition instead, ' .
            'all methods and classes are public by default!',
        'protected' => 'Use "+" to define a method, class or property as protected!',
        'private' => 'Use "#" to define a method, class or property as private!',

        'function' => 'Declare a function or a method using "arrow function"!',
        '__construct' => 'Declare a constructor using "constructor"!',

    ];

    public function validate(array $tokens): void
    {
        $mustHavePkg =  false;
        $objectAllowed = RuntimeClass::OBJECT_AS_CLASS;
        $hasPkg = false;
        $hasMoreThanOneObjectByFile = 0;
        foreach ($tokens as $token) {
            $tokenValue = $token->value;
            $line = $token->line;
            if ($this->isForbidden($tokenValue)) {
                $message = $this->getMessage($tokenValue);
                throw new CompileException(
                    "Error: '{$tokenValue}' is not allowed in line {$line}. " . $message,
                    $token->line,
                    $token->column
                );
            }

            if ($tokenValue === RuntimeClass::KEYWORD_PACKAGE) {
                if ($hasPkg) {
                    throw new CompileException(
                        'You must define pkg only once per file!',
                        $token->line,
                        $token->column
                    );
                }
                $hasPkg = true;
            }

            if (\in_array($tokenValue, $objectAllowed, true)) {
                $mustHavePkg  = true;
                $hasMoreThanOneObjectByFile++;
                if ($hasMoreThanOneObjectByFile > 1) {
                    throw new CompileException(
                        'Its allowed only one definition of ' .
                            \implode(', ', $objectAllowed) . ' per file. Please move ' .
                            'content from line ' . $line . ' to another file!',
                        $token->line,
                        $token->column
                    );
                }
            }

            $this->countCounterPart($token, '(', ')');
            $this->countCounterPart($token, '{', '}');
            $this->countCounterPart($token, '[', ']');
            $this->countCounterPart($token, '<', '>');
        }

        $this->validateCounting('(', ')');
        $this->validateCounting('{', '}');
        $this->validateCounting('[', ']');
        $this->validateCounting('<', '>');
        if ($mustHavePkg && !$hasPkg) {
            throw new CompileException('You must define a pkg or package for file that contains '
                . \implode(', ', $objectAllowed), 0, 0);
        }
    }

    private function getMessage(string $word): string
    {
        return $this->forbidden[$word] ?? '';
    }

    private function isForbidden(string $word): bool
    {
        return \array_key_exists($word, $this->forbidden) ||
            \in_array($word, $this->forbidden, true);
    }

    private function countCounterPart($token, $open, $close)
    {
        if ($token->value === $open) {
            $this->open[$open]++;
        }

        if ($token->value === $close) {
            $this->close[$close]++;
        }
    }

    private function validateCounting($open, $close)
    {
        if ($this->open[$open] !== $this->close[$close]) {
            throw new CompileException("Amount of {$open} ({$this->open[$open]}) " .
                "diverge from {$close} ({$this->close[$close]})", 0, 0);
        }
    }
}
