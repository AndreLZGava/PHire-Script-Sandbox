<?php

declare(strict_types=1);

namespace PHireScript\Runtime\DefaultOverrideMethods\Types;

use PHireScript\Runtime\DefaultOverrideMethods\BaseMethods;

class UuidMethods extends GeneralType
{
    public function toString()
    {
        return new BaseMethods(
            name: 'toString',
            phpCodeForConversion: 'json_encode(@self)',
            returnOfPhpExecution: ['String'],
            subTypes: [],
            params: [],
        );
    }
}
