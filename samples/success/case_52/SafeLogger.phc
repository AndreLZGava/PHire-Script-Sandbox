<?php

declare(strict_types=1);


namespace PHireScript\Sandbox\samples\success\case_52;


 class SafeLogger
{

    public function __construct(
        string $log,
        bool $hasError,
    ) {
        $this->log = $log;
        $this->hasError = $hasError;
        
    }
    public string $log;
    public bool $hasError;
    public function markError(): void{
$this->log = "error";
$this->hasError = true;
}

    public function clear(): void{
$this->log = "";
$this->hasError = false;
}

    public function getLog(): string{
return $this->log;
}

    public function hasErrors(): bool{
return $this->hasError;
}

    public function copyLog(): void{
$this->log = $this->log;
}

}

