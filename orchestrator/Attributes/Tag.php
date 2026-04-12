<?php

namespace PHireScript\Orchestrator\Attributes;

#[\Attribute(\Attribute::TARGET_CLASS | \Attribute::IS_REPEATABLE)]
class Tag
{
    public function __construct(public string $name) {}
}
