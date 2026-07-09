<?php

namespace PHireScript\Sandbox\samples\success\case_63;

class UnaryNegation
{
    public function __construct(bool $flag, int $count)
    {
        $this->flag = $flag;
        $this->count = $count;
    }
    public bool $flag;
    public int $count;
    private function getInverted(): bool
    {
        $inverted = !$this->flag;
        return $inverted;
    }
    private function getNegative(): int
    {
        $negative = -$this->count;
        return $negative;
    }
}