<?php

namespace PHireScript\Sandbox\samples\success\case_58;

use PHireScript\Runtime\Types\SuperTypes\Email;
class ExampleGetterSetterClass
{
    public function __construct(int $id, string $email, string $username, bool $isAdmin, array $metadata)
    {
        $this->id = $id;
        $this->email = Email::cast($email);
        $this->username = $username;
        $this->isAdmin = $isAdmin;
        $this->metadata = $metadata;
    }
    public int $id;
    public string $email;
    public string $username;
    public bool $isAdmin;
    private array $metadata;
    public function getId(): int
    {
        return $this->id;
    }
    public function setEmail(string $email): void
    {
        $this->email = Email::cast($email);
    }
    public function getUsername(): string
    {
        return $this->username;
    }
    public function setUsername(string $username): void
    {
        $this->username = $username;
    }
    private function getIsAdmin(): bool
    {
        return $this->isAdmin;
    }
    protected function setIsAdmin(bool $isAdmin): void
    {
        $this->isAdmin = $isAdmin;
    }
    protected function getMetadata(): array
    {
        return $this->metadata;
    }
    private function setMetadata(array $metadata): void
    {
        $this->metadata = $metadata;
    }
}