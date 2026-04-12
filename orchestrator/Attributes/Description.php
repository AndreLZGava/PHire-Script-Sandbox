<?php

namespace PHireScript\Orchestrator\Attributes;

#[\Attribute(\Attribute::TARGET_CLASS)]
class Description
{
    public function __construct(public string $text) {}
}
