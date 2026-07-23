<?php

namespace Sandbox\Samples\error\case_51;

use PHireScript\Orchestrator\AbstractCaseValidation;

class CaseValidation extends AbstractCaseValidation
{
    public function execute(): void
    {
        $this->assertHasMessage([
            'Cannot mix positional and named arguments in the same call',
        ]);
    }
}
