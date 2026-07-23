<?php

namespace Sandbox\Samples\error\case_52;

use PHireScript\Orchestrator\AbstractCaseValidation;

class CaseValidation extends AbstractCaseValidation
{
    public function execute(): void
    {
        $this->assertHasMessage([
            "Unknown named argument: 'count'",
        ]);
    }
}
