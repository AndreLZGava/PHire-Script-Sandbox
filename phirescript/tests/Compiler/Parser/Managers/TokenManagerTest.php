<?php

declare(strict_types=1);

namespace Tests\Unit\PHireScript\Compiler\Parser\Managers;

use PHPUnit\Framework\TestCase;
use PHireScript\Compiler\Parser\Managers\TokenManager;
use PHireScript\Compiler\Parser\Managers\Token\Token;

class TokenManagerTest extends TestCase
{
    private array $dummyTokens;
    private TokenManager $manager;
    private string $context = 'test_context';

    protected function setUp(): void
    {
        parent::setUp();


        $this->dummyTokens = [
            new Token('T_IDENTIFIER', 'let', 1, 1),
            new Token('T_IDENTIFIER', 'x', 1, 5),
            new Token('T_ASSIGN', '=', 1, 7),
            new Token('T_NUMBER', '10', 1, 9),
            new Token('T_SEMICOLON', ';', 1, 11),
        ];


        $this->manager = new TokenManager($this->context, $this->dummyTokens, 0);
    }

    public function testConstructorInitializesCorrectly()
    {
        $this->assertEquals($this->context, $this->manager->getContext());
        $this->assertEquals(0, $this->manager->getCurrentPosition());
        $this->assertSame($this->dummyTokens, $this->manager->getTokens());
        $this->assertSame($this->dummyTokens[0], $this->manager->getCurrentToken());
    }

    public function testGetLeftTokens()
    {
        $this->manager->setCurrentPosition(2);

        $leftTokens = $this->manager->getLeftTokens(2);

        $this->assertCount(2, $leftTokens);
        $this->assertSame('=', $leftTokens[0]->value);
        $this->assertSame('10', $leftTokens[1]->value);
    }

    public function testGetProcessedTokens()
    {
        $this->manager->setCurrentPosition(3);

        $processedTokens = $this->manager->getProcessedTokens(2);


        $this->assertCount(2, $processedTokens);
        $this->assertSame('x', $processedTokens[0]->value);
        $this->assertSame('=', $processedTokens[1]->value);
    }

    public function testGetAllReturnsCorrectStructure()
    {
        $this->manager->advance();
        $all = $this->manager->getAll();

        $this->assertArrayHasKey('context', $all);
        $this->assertArrayHasKey('currentPosition', $all);
        $this->assertArrayHasKey('positionLookup', $all);
        $this->assertArrayHasKey('previous', $all);
        $this->assertArrayHasKey('currentToken', $all);
        $this->assertArrayHasKey('next', $all);
        $this->assertArrayHasKey('tokens', $all);

        $this->assertEquals($this->context, $all['context']);
        $this->assertEquals(1, $all['currentPosition']);
    }

    public function testGetNextAfterFirstFoundElement()
    {

        $nextToken = $this->manager->getNextAfterFirstFoundElement(['=']);
        $this->assertNotNull($nextToken);
        $this->assertSame('10', $nextToken->value);


        $notFound = $this->manager->getNextAfterFirstFoundElement(['inexistente']);
        $this->assertNull($notFound);
    }

    public function testAdvanceMovesToNextToken()
    {
        $this->manager->advance();
        $this->assertEquals(1, $this->manager->getCurrentPosition());
        $this->assertSame('x', $this->manager->getCurrentToken()->value);
    }

    public function testAdvanceHandlesEndOfFileToken()
    {

        for ($i = 0; $i < 10; $i++) {
            $this->manager->advance();
        }

        $this->assertTrue($this->manager->isEndOfTokens());
        $this->assertSame('T_EOF', $this->manager->getCurrentToken()->type);
    }

    public function testWalkJumpsPositions()
    {
        $this->manager->walk(3);

        $this->assertEquals(3, $this->manager->getCurrentPosition());
        $this->assertSame('10', $this->manager->getCurrentToken()->value);
    }

    public function testIsEndOfTokens()
    {
        $this->assertFalse($this->manager->isEndOfTokens());

        $this->manager->setCurrentPosition(5);
        $this->assertTrue($this->manager->isEndOfTokens());
    }

    public function testSetCurrentPosition()
    {
        $this->manager->setCurrentPosition(2);
        $this->assertEquals(2, $this->manager->getCurrentPosition());
    }

    public function testGetNextTokenUpdatesLookup()
    {
        $nextToken = $this->manager->getNextToken();
        $this->assertSame('x', $nextToken->value);
        $this->assertEquals(1, $this->manager->positionLookup);
    }

    public function testGetNextTokenAfterCurrentResetsLookup()
    {
        $this->manager->positionLookup = 10;
        $nextToken = $this->manager->getNextTokenAfterCurrent();


        $this->assertSame('x', $nextToken->value);
        $this->assertEquals(1, $this->manager->positionLookup);
    }

    public function testGetPreviousTokenUpdatesLookup()
    {
        $this->manager->setCurrentPosition(2);
        $this->manager->positionLookup = 2;

        $prevToken = $this->manager->getPreviousToken();
        $this->assertSame('x', $prevToken->value);
        $this->assertEquals(1, $this->manager->positionLookup);
    }

    public function testGetPreviousTokenBeforeCurrentResetsLookup()
    {
        $this->manager->setCurrentPosition(2);
        $this->manager->positionLookup = 10;

        $prevToken = $this->manager->getPreviousTokenBeforeCurrent();


        $this->assertSame('x', $prevToken->value);
        $this->assertEquals(1, $this->manager->positionLookup);
    }

    public function testPeekRetrievesTokenWithOffset()
    {
        $this->manager->setCurrentPosition(1);

        $this->assertSame('x', $this->manager->peek()->value);
        $this->assertSame('=', $this->manager->peek(1)->value);
        $this->assertSame('let', $this->manager->peek(-1)->value);
    }

    public function testPeekReturnsEofTokenWhenOutOfBounds()
    {
        $eof = $this->manager->peek(100);
        $this->assertSame('T_EOF', $eof->type);
    }

    public function testMatchSequenceTypeOnce()
    {
        $rules = [
            [
                'type' => 'once',
                'match' => fn(Token $t) => $t->value === 'let',
            ],
            [
                'type' => 'once',
                'match' => fn(Token $t) => $t->value === 'x',
            ]
        ];

        $until = fn(Token $t) => $t->type === 'T_EOF';

        $this->assertTrue($this->manager->matchSequence($rules, $until));
    }

    public function testMatchSequenceTypeOnceFails()
    {
        $rules = [
            [
                'type' => 'once',
                'match' => fn(Token $t) => $t->value === 'const',
            ]
        ];

        $until = fn(Token $t) => $t->type === 'T_EOF';

        $this->assertFalse($this->manager->matchSequence($rules, $until));
    }

    public function testMatchSequenceTypeSeparated()
    {

        $tokens = [
            new Token('T_ID', 'a', 1, 1),
            new Token('T_COMMA', ',', 1, 2),
            new Token('T_ID', 'b', 1, 3),
            new Token('T_COMMA', ',', 1, 4),
            new Token('T_ID', 'c', 1, 5),
            new Token('T_SEMI', ';', 1, 6),
        ];

        $manager = new TokenManager('context', $tokens, 0);

        $rules = [
            [
                'type' => 'separated',
                'match' => fn(Token $t) => $t->type === 'T_ID',
                'separator' => fn(Token $t) => $t->type === 'T_COMMA',
            ]
        ];

        $until = fn(Token $t) => $t->type === 'T_SEMI' || $t->type === 'T_EOF';

        $this->assertTrue($manager->matchSequence($rules, $until));
    }

    public function testMatchSequenceStopsWhenUntilIsMet()
    {
        $rules = [
            [
                'type' => 'once',
                'match' => fn(Token $t) => true,
            ]
        ];

        $until = fn(Token $t) => true;

        $this->assertFalse($this->manager->matchSequence($rules, $until));
    }
}
