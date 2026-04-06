<?php


namespace PHireScript\Classes;


 class ExampleGetterSetterClass {
    public int $id;
    public string $email;
    public string $username;
    public bool $isAdmin;
    private array $metadata;
    public function getId(): int {
        return $this->id;
    }

    public function setEmail(string $email): void {
        $this->email = $email;
        return ;
    }

    public function getUsername(): string {
        return $this->username;
    }

    public function setUsername(string $username): void {
        $this->username = $username;
        return ;
    }

    private function getIsAdmin(): bool {
        return $this->isAdmin;
    }

    protected function setIsAdmin(bool $isAdmin): void {
        $this->isAdmin = $isAdmin;
        return ;
    }

    protected function getMetadata(): array {
        return $this->metadata;
    }

    private function setMetadata(array $metadata): void {
        $this->metadata = $metadata;
        return ;
    }

}

