<?php

declare(strict_types=1);

namespace PHireScript\Compiler\Parser\Ast\Resolver\Expressions;

use Exception;
use PHireScript\Compiler\Parser\Ast\Context\AbstractContext;
use PHireScript\Compiler\Parser\Ast\Context\Expressions\FunctionCallContext;
use PHireScript\Compiler\Parser\Ast\Resolver\ContextTokenResolver;
use PHireScript\Compiler\Parser\Ast\Nodes\ArrayLiteralNode;
use PHireScript\Compiler\Parser\Ast\Nodes\BoolNode;
use PHireScript\Compiler\Parser\Ast\Nodes\FunctionNode;
use PHireScript\Compiler\Parser\Ast\Nodes\NumberNode;
use PHireScript\Compiler\Parser\Ast\Nodes\ObjectLiteralNode;
use PHireScript\Compiler\Parser\Ast\Nodes\StringNode;
use PHireScript\Compiler\Parser\Managers\Token\Token;
use PHireScript\Compiler\Parser\ParseContext;
use PHireScript\Helper\Debug\Debug;
use PHireScript\Runtime\Exceptions\CompileException;

// possiveis cenários:
// Tenho uma função que retorna string e a proxima chama string, está consumindo a variavel de foco
// Tenho uma função que retorna uma string e a proxima é um array, está
// Tenho função que retorna mais de um tipo
class FunctionCallResolver implements ContextTokenResolver
{
    private bool $assignmentContext = false;
    public function isTheCase(Token $token, ParseContext $parseContext, AbstractContext $context): bool
    {
        try {
            return $token->isIdentifier() &&
                $parseContext->tokenManager->getNextTokenAfterCurrent()->isOpeningParenthesis() &&
                (
                    $parseContext->symbolTable->getFunctionFromLastExecution($token->value) ||
                    $parseContext->symbolTable->from(
                        $parseContext->variables->getVariableOnFocus()?->type?->getRawType()
                    )->getFunction($token->value)

                );
        } catch (\Exception $e) {
            Debug::display($parseContext->variables->getVariableOnFocus());
            exit;
        }
    }

    public function resolve(
        Token $token,
        ParseContext $parseContext,
        AbstractContext $context
    ): void {
        $variableType = $parseContext->variables->getVariableOnFocus()?->type?->getRawType();
        $functionDefinition = $parseContext->symbolTable->from(
            $variableType
        )->getFunction($token->value);

        $onFocus = $parseContext->variables->getVariableOnFocus();

        if (empty($functionDefinition)) {
            $this->assignmentContext = $context->assignmentContext ?: false;
            $functionDefinition = $parseContext->symbolTable->getFunctionFromLastExecution($token->value, true);
            /**$onFocus = \end($parseContext->program->statements);
            if($onFocus instanceof AssignmentNode) {
                $onFocus = $onFocus->right;
            }
            $onFocus->type = $onFocus;
            $onFocus->processedByOther = false;
            $onFocus->mustProcess = false;
            $onFocus = clone $onFocus;
            $onFocus->mustProcess = true;*/
        }

        if (is_null($functionDefinition)) {
            throw new CompileException(
                'Method ' . $token->value . ' is not defined for variable of type '
                    . $variableType,
                $token->line,
                $token->column
            );
        }

        $function = new FunctionNode(token: $token);
        $function->method = $functionDefinition;
        $function->variableBase = $onFocus;

        $this->overrideVariableOnFocus($function, $functionDefinition, $token);
        //$parseContext->variables->setVirtualVariable($function);

        $parseContext->contextManager->enter(
            new FunctionCallContext($function)
        );
        $context->addChild($function);
    }

    private function overrideVariableOnFocus($function, $functionDefinition, $token)
    {
        $function->overrideVariableFocus = \count($functionDefinition->returnOfPhpExecution) > 0 &&
            $functionDefinition->overridesSelfParam &&
            !$this->assignmentContext;

        if ($function->overrideVariableFocus) {
            $firstType = \current($function->method->returnOfPhpExecution);
            $firstType = $firstType == 'Mixed' ? \current($function->variableBase?->type?->types ?? []) : $firstType;
            $newVariable = $this->getNewVirtualVariable($token, $firstType);
            if (property_exists($newVariable, 'types')) {
                $newVariable->types = $function->method->subTypes;
            }
            $function->variableBase->value = $newVariable;
            $function->variableBase->type = $newVariable;
        }
    }

    private function getNewVirtualVariable($token, $value)
    {
        return match (true) {
            $value === 'Array'  => new ArrayLiteralNode($token),
            $value === 'String' => new StringNode($token, $value),

            $value === 'Int', $value === 'Float' => new NumberNode($token, floatval($value)),

            $value === 'Object' => new ObjectLiteralNode($token, $value),
            $value === 'Bool'   => new BoolNode($token, boolval($value)),
            default => throw new CompileException('Type not supported: ' . $value, $token->line, $token->column),
        };
    }
}
