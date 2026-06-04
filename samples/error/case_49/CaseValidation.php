<?php

namespace Sandbox\Samples\error\case_49;

use PHireScript\Orchestrator\AbstractCaseValidation;
use PHireScript\Orchestrator\Attributes\Description;
use PHireScript\Orchestrator\Attributes\Documentation;

#[Tag('method-chaining')]
#[Tag('checker')]
#[Tag('error')]
#[Documentation(true)]
#[Description('Chain after nullable method without ?. must produce CheckerException with guidance')]
class CaseValidation extends AbstractCaseValidation
{
    public function execute()
    {
        $this->assertHasMessage([
            'may return `Null`',
        ]);
    }
}
