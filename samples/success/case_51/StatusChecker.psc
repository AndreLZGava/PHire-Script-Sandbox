<?php

declare(strict_types=1);


namespace PHireScript\Sandbox\samples\success\case_51;


 class StatusChecker
{

    public function __construct(
        bool $active,
        string $status,
    ) {
        $this->active = $active;
        $this->status = $status;
        
    }
    public bool $active;
    public string $status;
    public function toggle(): void{
if ($this->active == true) {
 $this->status = "inactive";
} else {
 $this->status = "active";
}
}

    public function getStatus(): string{
return $this->status;
}

    public function isActive(): bool{
return $this->active;
}

}

