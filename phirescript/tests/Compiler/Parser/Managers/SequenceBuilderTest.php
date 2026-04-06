<?php

declare(strict_types=1);

namespace Tests\Unit\PHireScript\Compiler\Parser\Managers\Builder;

use PHPUnit\Framework\TestCase;
use PHireScript\Compiler\Parser\Managers\Builder\SequenceBuilder;
use PHireScript\Compiler\Parser\Managers\TokenManager;
use PHireScript\Compiler\Parser\Managers\Token\Token;

class SequenceBuilderTest extends TestCase
{
    /**
     * Retorna a lista de tokens fornecida no exemplo
     */
    private function getSampleTokens(): array
    {
        return [
            0 => $this->cloneToken('T_IDENTIFIER', 'calcTotal', 1, 1),
            1 => $this->cloneToken('T_SYMBOL', '=', 1, 11),
            2 => $this->cloneToken('T_SYMBOL', '(', 1, 13),
            3 => $this->cloneToken('T_PRIMITIVE', 'Float', 1, 14),
            4 => $this->cloneToken('T_IDENTIFIER', 'price', 1, 20),
            5 => $this->cloneToken('T_SYMBOL', ',', 1, 25),
            6 => $this->cloneToken('T_PRIMITIVE', 'Float', 1, 27),
            7 => $this->cloneToken('T_IDENTIFIER', 'rate', 1, 33),
            8 => $this->cloneToken('T_SYMBOL', ')', 1, 37),
            9 => $this->cloneToken('T_SYMBOL', ':', 1, 38),
            10 => $this->cloneToken('T_PRIMITIVE', 'Float', 1, 40),
            11 => $this->cloneToken('T_MODIFIER', '=>', 1, 46),
            12 => $this->cloneToken('T_SYMBOL', '{', 1, 49),
            13 => $this->cloneToken('T_EOL', "\n", 1, 50),
            14 => $this->cloneToken('T_KEYWORD', 'return', 2, 5),
            15 => $this->cloneToken('T_NUMBER', '12', 2, 12),
            16 => $this->cloneToken('T_EOL', "\n", 2, 14),
            17 => $this->cloneToken('T_SYMBOL', '}', 3, 1),
            18 => $this->cloneToken('T_EOL', "\n\n", 3, 2),
        ];
    }

    /**
     * Cria um mock do TokenManager ajustado para uma posição inicial específica
     */
    private function createMockManager(int $currentPosition = 0): TokenManager
    {
        $tokens = $this->getSampleTokens();

        $manager = $this->createStub(TokenManager::class);
        $manager->method('peek')->willReturnCallback(
            function (int $offset) use ($tokens, $currentPosition) {
                $targetIndex = $currentPosition + $offset;
                return $tokens[$targetIndex] ?? $this->cloneToken('T_EOF', '', 0, 0);
            }
        );

        return $manager;
    }

    public function testOnceAndThenMatchesSequentialTokens()
    {
        $manager = $this->createMockManager(0);
        $builder = new SequenceBuilder($manager);

        $isMatch = $builder
            ->once(fn($t) => $t->value === 'calcTotal')
            ->then(fn($t) => $t->value === '=')
            ->then(fn($t) => $t->value === '(')
            ->match();

        $this->assertTrue($isMatch);
    }

    public function testOnceFailsIfTokenDoesNotMatch()
    {
        $manager = $this->createMockManager(0);
        $builder = new SequenceBuilder($manager);

        $isMatch = $builder
            ->once(fn($t) => $t->value === 'calcTotal')
            ->then(fn($t) => $t->value === '!=')
            ->match();

        $this->assertFalse($isMatch);
    }

    public function testLookBehindMatchesBackwards()
    {
        $manager = $this->createMockManager(2);
        $builder = new SequenceBuilder($manager);

        $isMatch = $builder
            ->lookBehind()
            ->once(fn($t) => $t->value === '(')
            ->then(fn($t) => $t->value === '=')
            ->then(fn($t) => $t->value === 'calcTotal')
            ->match();

        $this->assertTrue($isMatch);
    }

    public function testOptionalMatchesAndConsumesIfPresent()
    {
        $manager = $this->createMockManager(2);
        $builder = new SequenceBuilder($manager);

        $isMatch = $builder
            ->once(fn($t) => $t->value === '(')
            ->optional(function(SequenceBuilder $b) {
                $b->once(fn($t) => $t->value === 'Float')
                  ->then(fn($t) => $t->value === 'price');
            })
            ->then(fn($t) => $t->value === ',')
            ->match();

        $this->assertTrue($isMatch);
    }

    public function testOptionalSkipsAndDoesNotFailIfNotPresent()
    {
        $manager = $this->createMockManager(2);
        $builder = new SequenceBuilder($manager);

        $isMatch = $builder
            ->once(fn($t) => $t->value === '(')
            ->optional(function(SequenceBuilder $b) {
                $b->once(fn($t) => $t->value === 'Int');
            })
            ->then(fn($t) => $t->value === 'Float')
            ->match();

        $this->assertTrue($isMatch);
    }

    public function testOrMatchesFirstValidAlternative()
    {
        $manager = $this->createMockManager(3);
        $builder = new SequenceBuilder($manager);

        $isMatch = $builder
            ->or(
                fn(SequenceBuilder $b) => $b->once(fn($t) => $t->type === 'T_NUMBER'),
                fn(SequenceBuilder $b) => $b->once(fn($t) => $t->type === 'T_PRIMITIVE')
            )
            ->then(fn($t) => $t->value === 'price')
            ->match();

        $this->assertTrue($isMatch);
    }

    public function testGroupGroupsRulesAndConsumesCorrectly()
    {
        $manager = $this->createMockManager(3);
        $builder = new SequenceBuilder($manager);

        $isMatch = $builder
            ->group(function(SequenceBuilder $b) {
                $b->once(fn($t) => $t->type === 'T_PRIMITIVE')
                  ->then(fn($t) => $t->type === 'T_IDENTIFIER');
            })
            ->then(fn($t) => $t->value === ',')
            ->match();

        $this->assertTrue($isMatch);
    }

    public function testSeparatedMatchesListSeparatedByTokens()
    {
        $tokens = [
            $this->cloneToken('T_IDENTIFIER', 'a', 1, 1),
            $this->cloneToken('T_SYMBOL', ',', 1, 2),
            $this->cloneToken('T_IDENTIFIER', 'b', 1, 3),
            $this->cloneToken('T_SYMBOL', ';', 1, 4),
        ];

        $manager = $this->createStub(TokenManager::class);
        $manager->method('peek')->willReturnCallback(
            fn($offset) => $tokens[$offset] ?? $this->cloneToken('T_EOF', '', 0, 0)
        );

        $builder = new SequenceBuilder($manager);

        $isMatch = $builder
            ->separated(
                fn($t) => $t->type === 'T_IDENTIFIER',
                fn($t) => $t->value === ','
            )
            ->then(fn($t) => $t->value === ';')
            ->match();

        $this->assertTrue($isMatch);
    }

    public function testSkipUntilAdvancesToTargetToken()
    {
        $manager = $this->createMockManager(0);
        $builder = new SequenceBuilder($manager);

        $isMatch = $builder
            ->skipUntil(fn($t) => $t->value === '{')
            ->once(fn($t) => $t->value === '{')
            ->then(fn($t) => $t->type === 'T_EOL')
            ->then(fn($t) => $t->value === 'return')
            ->match();

        $this->assertTrue($isMatch);
    }

    public function testAroundValidatesBothDirections()
    {
        $manager = $this->createMockManager(11);
        $builder = new SequenceBuilder($manager);

        $isMatch = $builder
            ->around(

                fn(SequenceBuilder $b) => $b->once(fn($t) => $t->value === '=>')->then(fn($t) => $t->type === 'T_PRIMITIVE'),
                fn(SequenceBuilder $b) => $b->once(fn($t) => $t->value === '=>')->then(fn($t) => $t->value === '{')
            )
            ->match();

        $this->assertTrue($isMatch);
    }

    public function testUntilStopsExecutionPrematurely()
    {
        $manager = $this->createMockManager(0);
        $builder = new SequenceBuilder($manager);

        $isMatch = $builder
            ->until(fn($t) => $t->value === '=')
            ->once(fn($t) => $t->value === 'calcTotal')
            ->then(fn($t) => $t->value === '=')
            ->match();

        $this->assertFalse($isMatch);
    }

    public function testTestReturnsMatchStatusWithoutConsumingOffsets()
    {
        $manager = $this->createMockManager(2);
        $builder = new SequenceBuilder($manager);


        $testResult = $builder->test(function (SequenceBuilder $b) {
            $b->once(fn($t) => $t->value === '(')
              ->then(fn($t) => $t->value === 'Float');
        });

        $this->assertTrue($testResult);


        $finalMatch = $builder
            ->once(fn($t) => $t->value === '(')
            ->match();

        $this->assertTrue($finalMatch);
    }

    public function testMaxForwardLimitBreaksExecution()
    {
        $manager = $this->createMockManager(12);
        $builder = new SequenceBuilder($manager);


        $isMatch = $builder->skipUntil(fn($t) => $t->value === 'PalavraQueNaoExiste')->match();

        $this->assertFalse($isMatch);
    }
    /**
     * Função utilitária para contornar a construção da classe Token
     * Substitua pelo `new Token(...)` original de acordo com a assinatura do seu projeto
     */
    private function cloneToken(string $type, string $value, int $line, int $column) {
        return new Token($type, $value, $line, $column);
    }
}

