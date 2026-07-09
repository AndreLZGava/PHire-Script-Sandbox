<?php

namespace Sandbox\Samples\success\case_75;

use PHireScript\Orchestrator\AbstractCaseValidation;

class CaseValidation extends AbstractCaseValidation
{
    public function execute(): void
    {
        $this->assertHasMessage([
            '✔ src/output/NamedParamsSplit.ps',
        ]);
    }
}
