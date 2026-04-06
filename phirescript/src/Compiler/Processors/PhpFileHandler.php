<?php

declare(strict_types=1);

namespace PHireScript\Compiler\Processors;

class PhpFileHandler implements PreprocessorInterface
{
    public function process(string $code): string
    {
        if (!str_starts_with(\trim($code), '<?php')) {
            $code = "<?php\n" . $code;
        }
        return $code;
    }
}
