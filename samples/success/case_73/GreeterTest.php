<?php

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/Greeter.php';

class GreeterTest extends TestCase
{
    private string $ns = 'PHireScript\Sandbox\src\output';

    private function fqcn(): string
    {
        return $this->ns . '\Greeter';
    }

    public function testDeclareStrictTypesIsPresentInFile(): void
    {
        $content = file_get_contents(__DIR__ . '/Greeter.php');
        $this->assertStringContainsString(
            'strict_types=1',
            $content,
            'Generated PHP file must contain declare(strict_types=1);'
        );
    }

    public function testDeclareAppearsAfterOpeningTag(): void
    {
        $content = file_get_contents(__DIR__ . '/Greeter.php');
        $phpTagPos     = strpos($content, '<?php');
        $declarePos    = strpos($content, 'strict_types=1');
        $this->assertGreaterThan(
            $phpTagPos,
            $declarePos,
            'declare(strict_types=1) must appear after <?php'
        );
    }

    public function testClassExists(): void
    {
        $this->assertTrue(
            class_exists($this->fqcn()),
            "Greeter class ({$this->fqcn()}) must exist after compilation"
        );
    }

    public function testGreetingMethodExists(): void
    {
        $ref = new \ReflectionClass($this->fqcn());
        $this->assertTrue($ref->hasMethod('greeting'));
    }

    public function testGreetingMethodReturnsString(): void
    {
        $method     = (new \ReflectionClass($this->fqcn()))->getMethod('greeting');
        $returnType = $method->getReturnType();
        $this->assertNotNull($returnType);
        $this->assertEquals('string', $returnType->getName());
    }

    public function testGreetingMethodHasNoParameters(): void
    {
        $params = (new \ReflectionClass($this->fqcn()))->getMethod('greeting')->getParameters();
        $this->assertCount(0, $params);
    }

    public function testGreetingReturnsExpectedString(): void
    {
        $greeter = new ($this->fqcn())();
        $this->assertEquals('Hello World', $greeter->greeting());
    }
}
