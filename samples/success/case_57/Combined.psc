<?php

declare(strict_types=1);


namespace PHireScript\Sandbox\samples\success\case_57;


 class Combined
{

    public function __construct(
        string $username,
        int $count,
        bool $active,
    ) {
        $this->username = $username;
        $this->count = $count;
        $this->active = $active;
        
    }
    public string $username;
    public int $count;
    public bool $active;
    public function getUsername(): string
    {
        return $this->username;
    }

    public function setUsername(string $username): void
    {
        $this->username = $username;
    }

    public function getCount(): int
    {
        return $this->count;
    }

    public function setCount(int $count): void
    {
        $this->count = $count;
    }

    public function getActive(): bool
    {
        return $this->active;
    }

    public function setActive(bool $active): void
    {
        $this->active = $active;
    }

}

