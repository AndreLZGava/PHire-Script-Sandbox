<?php

namespace PHireScript;

interface TranspilerInterface
{
    public function compile(string $code, string $path): string;
}
