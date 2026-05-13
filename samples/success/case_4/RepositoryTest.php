<?php

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/Repository.php';

class RepositoryTest extends TestCase
{
    private string $fqcn = 'PHireScript\Sandbox\src\output\Repository';

    private function getReflection(): \ReflectionClass
    {
        if (!class_exists($this->fqcn)) {
            $this->fail("Class {$this->fqcn} does not exist");
        }

        return new \ReflectionClass($this->fqcn);
    }

    private function makeConcreteRepository(string $tableName = 'users'): object
    {
        return new class($tableName) extends \PHireScript\Sandbox\src\output\Repository {
            public function __construct(string $table)
            {
                parent::__construct($table);
            }
        };
    }

    public function testClassIsAbstract(): void
    {
        $this->assertTrue($this->getReflection()->isAbstract());
    }

    public function testTableNamePropertyExists(): void
    {
        $this->assertTrue($this->getReflection()->hasProperty('tableName'));
    }

    public function testTableNamePropertyIsPublicString(): void
    {
        $prop = $this->getReflection()->getProperty('tableName');
        $this->assertTrue($prop->isPublic());
        $this->assertEquals('string', $prop->getType()->getName());
    }

    public function testMethodExampleExists(): void
    {
        $this->assertTrue($this->getReflection()->hasMethod('methodExample'));
    }

    public function testMethodExampleIsPublic(): void
    {
        $this->assertTrue($this->getReflection()->getMethod('methodExample')->isPublic());
    }

    public function testMethodExampleReturnsNull(): void
    {
        $repo = $this->makeConcreteRepository();
        $this->assertNull($repo->methodExample());
    }

    public function testConcreteRepositoryCanBeInstantiatedWithTableName(): void
    {
        $repo = $this->makeConcreteRepository('orders');
        $this->assertEquals('orders', $repo->tableName);
    }

    public function testConstructorRequiresTableNameArgument(): void
    {
        $reflection = $this->getReflection();
        $params = $reflection->getMethod('__construct')->getParameters();
        $this->assertCount(1, $params);
        $this->assertEquals('tableName', $params[0]->getName());
        $this->assertEquals('string', $params[0]->getType()->getName());
        $this->assertFalse($params[0]->isOptional());
    }

    public function testTableNameIsAccessibleOnConcreteInstance(): void
    {
        $repo = $this->makeConcreteRepository('products');
        $this->assertIsString($repo->tableName);
        $this->assertEquals('products', $repo->tableName);
    }
}
