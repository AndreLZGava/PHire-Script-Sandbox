<?php

use PHPUnit\Framework\TestCase;

class AutoAssignmentTest extends TestCase
{
    private array $vars = [];

    protected function setUp(): void
    {
        $this->vars = (function() {
            require __DIR__ . '/AutoAssignment.php';
            return get_defined_vars();
        })();
    }

    public function testMystringIsUppercased(): void
    {
        $this->assertEquals('THIS IS A STRING', $this->vars['mystring']);
    }
}
