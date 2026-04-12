<?php

namespace PHireScript\Orchestrator\Attributes;

#[\Attribute(\Attribute::TARGET_CLASS)]
class Documentation
{
    public function __construct(public bool $doc = true) {}
}
