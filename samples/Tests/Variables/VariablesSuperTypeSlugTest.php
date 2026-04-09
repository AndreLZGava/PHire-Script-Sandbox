<?php

use PHPUnit\Framework\TestCase;
use PHireScript\Runtime\Types\SuperTypes\Slug;

class VariablesSuperTypeSlugTest extends TestCase
{
    public function testCompiledSlugSuperType()
    {
        require __DIR__ . '/../../Compiled/Variables/VariablesSuperTypeSlug.php';

        $this->assertIsString($variables);
        $this->assertIsString($variablesReference);

        $this->assertNotInstanceOf(Slug::class, $variables);

        $this->assertEquals('test-then', $variables);

        $this->assertSame($variables, $variablesReference);
    }
}
