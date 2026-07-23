<?php

declare(strict_types=1);


namespace PHireScript\Sandbox\samples\success\case_54;


 class Builder
{

    public function __construct(
        string $name,
        int $value,
    ) {
        $this->name = $name;
        $this->value = $value;
        
    }
    public string $name;
    public int $value;
    public function withName(): static{
$this->name = "default";
return $this;
}

    public function withValue(): static{
$this->value = 42;
return $this;
}

    public function getName(): string{
return $this->name;
}

    public function getValue(): int{
return $this->value;
}

}

