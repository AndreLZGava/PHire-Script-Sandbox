<?php

declare(strict_types=1);

namespace PHireScript\Compiler\Parser\IdentifyTokenFactories;

use PHireScript\Compiler\Parser\Ast\Nodes\Node;
use PHireScript\Compiler\Parser\IdentifyTokenFactories\Symbols\ArrayCastVariable;
use PHireScript\Compiler\Parser\IdentifyTokenFactories\Symbols\BlockBracketsCommaOnMethod;
use PHireScript\Compiler\Parser\IdentifyTokenFactories\Symbols\BlockBrackets;
use PHireScript\Compiler\Parser\IdentifyTokenFactories\Symbols\BlockParenthesisOnMethod;
use PHireScript\Compiler\Parser\IdentifyTokenFactories\Symbols\BoolCastVariable;
use PHireScript\Compiler\Parser\IdentifyTokenFactories\Symbols\BoolLiteralVariable;
use PHireScript\Compiler\Parser\IdentifyTokenFactories\Symbols\CharactersOnMethods;
use PHireScript\Compiler\Parser\IdentifyTokenFactories\Symbols\ComplexObject;
use PHireScript\Compiler\Parser\IdentifyTokenFactories\Symbols\DotAsPointer;
use PHireScript\Compiler\Parser\IdentifyTokenFactories\Symbols\DotOnGeneral;
use PHireScript\Compiler\Parser\IdentifyTokenFactories\Symbols\FloatCastVariable;
use PHireScript\Compiler\Parser\IdentifyTokenFactories\Symbols\GetterAndSetters;
use PHireScript\Compiler\Parser\IdentifyTokenFactories\Symbols\GettingArguments;
use PHireScript\Compiler\Parser\IdentifyTokenFactories\Symbols\IntCastVariable;
use PHireScript\Compiler\Parser\IdentifyTokenFactories\Symbols\NumberLiteralVariable;
use PHireScript\Compiler\Parser\IdentifyTokenFactories\Symbols\ObjectArrayLiteralVariable;
use PHireScript\Compiler\Parser\IdentifyTokenFactories\Symbols\ObjectCastVariable;
use PHireScript\Compiler\Parser\IdentifyTokenFactories\Symbols\SingleCommaOnClass;
use PHireScript\Compiler\Parser\IdentifyTokenFactories\Symbols\SingleOpenParenthesisOperator;
use PHireScript\Compiler\Parser\IdentifyTokenFactories\Symbols\StringCastVariable;
use PHireScript\Compiler\Parser\IdentifyTokenFactories\Symbols\StringLiteralVariable;
use PHireScript\Compiler\Parser\IdentifyTokenFactories\Symbols\SuperTypeCastVariable;
use PHireScript\Compiler\Parser\IdentifyTokenFactories\Symbols\UuidCastVariable;
use PHireScript\Compiler\Parser\IdentifyTokenFactories\Symbols\VariableAssignmentFactory;
use PHireScript\Compiler\Parser\IdentifyTokenFactories\Symbols\VariableLiteralVariable;
use PHireScript\Compiler\Parser\IdentifyTokenFactories\Traits\DataArrayObjectModelingTrait;
use PHireScript\Compiler\Parser\IdentifyTokenFactories\Traits\DataParamsModelingTrait;
use PHireScript\Compiler\Parser\ParseContext;
use PHireScript\Compiler\Program;
use PHireScript\Helper\Debug\Debug;

class Symbol extends GlobalFactory
{
public Program $program;

public function process(Token $token, ParseContext $parseContext): ?Node
{
    $this->program = $program;

    $factories = [
        new ArrayCastVariable($this->tokenManager, $this->parseContext),
        new BlockBrackets($this->tokenManager, $this->parseContext),
        new BlockBracketsCommaOnMethod($this->tokenManager, $this->parseContext),
        new BlockParenthesisOnMethod($this->tokenManager, $this->parseContext),
        new BoolCastVariable($this->tokenManager, $this->parseContext),
        new BoolLiteralVariable($this->tokenManager, $this->parseContext),
        new CharactersOnMethods($this->tokenManager, $this->parseContext),
        new ComplexObject($this->tokenManager, $this->parseContext),
        new DotAsPointer($this->tokenManager, $this->parseContext),
        new DotOnGeneral($this->tokenManager, $this->parseContext),
        new FloatCastVariable($this->tokenManager, $this->parseContext),
        new GetterAndSetters($this->tokenManager, $this->parseContext),
        new GettingArguments($this->tokenManager, $this->parseContext),
        new IntCastVariable($this->tokenManager, $this->parseContext),
        new NumberLiteralVariable($this->tokenManager, $this->parseContext),
        new ObjectArrayLiteralVariable($this->tokenManager, $this->parseContext),
        new ObjectCastVariable($this->tokenManager, $this->parseContext),
        new SingleCommaOnClass($this->tokenManager, $this->parseContext),
        new SingleOpenParenthesisOperator($this->tokenManager, $this->parseContext),
        new StringCastVariable($this->tokenManager, $this->parseContext),
        new StringLiteralVariable($this->tokenManager, $this->parseContext),
        new SuperTypeCastVariable($this->tokenManager, $this->parseContext),
        new VariableLiteralVariable($this->tokenManager, $this->parseContext),
        new VariableAssignmentFactory($this->tokenManager, $this->parseContext),

    ];

    foreach ($factories as $parser) {
        if ($parser->Token $token, ParseContext $parseContext) :
            bool {
            return $parser->process($program);
            }
        }

        Debug::show($this->tokenManager->getLeftTokens());
        exit;

        Debug::show(
            [
            'currentToken' => $this->tokenManager->getCurrentToken(),
            'context' => $this->tokenManager->getContext(),
            'program' => $program
            ],
            debug_backtrace(2)
        );
        exit;
    }
}
