<?php

namespace PHireScript\Sandbox\samples\success\case_59;

class ValueHolder
{
    public function __construct(string $value)
    {
        $this->value = $value;
    }
    public string $value;
    public function getValue(): string
    {
        return $this->value;
    }
}