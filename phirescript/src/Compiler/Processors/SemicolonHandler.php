<?php

declare(strict_types=1);

namespace PHireScript\Compiler\Processors;

class SemicolonHandler implements PreprocessorInterface
{
    public function __construct(private readonly ObjectsHandler $objectHandler)
    {
    }

    public function process(string $code): string
    {
        $lines = \explode("\n", $code);
        $result = [];

        foreach ($lines as $line) {
            $trimmed = \trim($line);

            if ($trimmed === '}') {
                $result[] = $line . ';';
                continue;
            }

            if ($trimmed === '' || $trimmed === '<?php' || $trimmed === '}' || $trimmed === '{') {
                $result[] = $line;
                continue;
            }

            if (\str_ends_with($trimmed, ';') || \str_ends_with($trimmed, '{')) {
                $result[] = $line;
                continue;
            }

            if (preg_match('/^(function|func|if|else|for|while|foreach|try|catch|do)/i', $trimmed)) {
                $result[] = $line;
                continue;
            }

            if (\str_contains($line, '//')) {
                $parts = \explode('//', $line, 2);
                $content = \rtrim($parts[0]);
                if ($content !== '') {
                    $result[] = $content . ';' . ' //' . $parts[1];
                } else {
                    $result[] = $line;
                }
                continue;
            }

            $result[] = $line . ';';
        }

        $code = \implode("\n", $result);

        foreach ($this->objectHandler->getObjectPlaceholders() as $placeholder => $originalContent) {
            $code = \str_replace($placeholder, $originalContent, $code);
        }
        return $code;
    }
}
