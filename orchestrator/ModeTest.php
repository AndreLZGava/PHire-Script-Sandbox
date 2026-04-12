<?php

namespace PHireScript\Orchestrator;

abstract class ModeTest
{
    public function before(AbstractCaseValidation $abstractCase) {}

    abstract public function execute(AbstractCaseValidation $abstractCase);

    public function rightAfterFirstExecution(AbstractCaseValidation $abstractCase) {}

    function executeAgain(AbstractCaseValidation $abstractCase) {}

    public function after(AbstractCaseValidation $abstractCase) {}

    public function executeTest(AbstractCaseValidation $abstractCase) {}
}
