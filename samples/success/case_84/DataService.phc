<?php

declare(strict_types=1);


namespace PHireScript\Sandbox\samples\success\case_84;


use PHireScript\Sandbox\samples\success\case_84\AppException;

use PHireScript\Sandbox\samples\success\case_84\DatabaseException;

 class DataService
{
    private function withMessage(): void{
throw new AppException(reason: 'bad input', code: 42, message: 'Explicit message override');
}

    private function withContext(): void{
throw new AppException(reason: 'timeout', code: 503, context: (object) ['service' => 'db', 'retry' => 'false']);
}

    private function withCause(AppException $original): void{
throw new DatabaseException(reason: 'wrapped', code: 2, message: 'DB failed', previous: $original);
}

}

