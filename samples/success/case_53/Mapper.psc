<?php

namespace PHireScript\Sandbox\samples\success\case_53;

class Mapper
{
    public function __construct(string $prefix)
    {
        $this->prefix = $prefix;
    }
    public string $prefix;
    public function getTransformer(): any
    {
        $transformer = function (string $item): string {
            return $this->prefix;
        };
        return $transformer;
    }
}