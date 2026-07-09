<?php

namespace PHireScript\Sandbox\samples\success\case_66;

class Person
{
    public function __construct(string $name, int $age)
    {
        $this->name = $name;
        $this->age = $age;
    }
    public string $name;
    public int $age;
    public function setName(string $n): void
    {
        $this->name = $n;
    }
    public function setAge(int $a): void
    {
        $this->age = $a;
    }
    public function greet(string $greeting): string
    {
        return $greeting;
    }
    public function getName(): string
    {
        return $this->name;
    }
    public function getAge(): int
    {
        return $this->age;
    }
}