<?php

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../../Compiled/Classes/UserInterface.php';

use PHireScript\Classes\UserInterface;

class UserInterfaceTest extends TestCase
{
    public function testCanImplementInterface()
    {
        $user = new class implements UserInterface {
            public function save(array $data): bool
            {
                return true;
            }

            public function delete(): void
            {
            }

            public function getCompleteUserName(): string|null
            {
                return 'André Gava';
            }
        };

        $this->assertInstanceOf(UserInterface::class, $user);
    }

    public function testSaveReturnsBool()
    {
        $user = new class implements UserInterface {
            public function save(array $data): bool
            {
                return true;
            }

            public function delete(): void
            {
            }

            public function getCompleteUserName(): string|null
            {
                return null;
            }
        };

        $this->assertIsBool(
            $user->save(['name' => 'André'])
        );
    }

    public function testGetCompleteUserNameAllowsNull()
    {
        $user = new class implements UserInterface {
            public function save(array $data): bool
            {
                return true;
            }

            public function delete(): void
            {
            }

            public function getCompleteUserName(): string|null
            {
                return null;
            }
        };

        $this->assertNull($user->getCompleteUserName());
    }
}
