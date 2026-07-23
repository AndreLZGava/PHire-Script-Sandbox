<?php

namespace Sandbox\Samples\error\case_54;

use PHireScript\Orchestrator\AbstractCaseValidation;

class CaseValidation extends AbstractCaseValidation
{
    public function execute(): void
    {
        $this->assertHasMessage([
            "Duplicate named argument: 'times'",
        ]);
    }
}
