<?php

namespace Sandbox\Samples\error\case_53;

use PHireScript\Orchestrator\AbstractCaseValidation;

class CaseValidation extends AbstractCaseValidation
{
    public function execute(): void
    {
        $this->assertHasMessage([
            "Missing required named argument: 'separator'",
        ]);
    }
}
