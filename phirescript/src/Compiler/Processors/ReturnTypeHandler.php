<?php

declare(strict_types=1);

namespace PHireScript\Compiler\Processors;

class ReturnTypeHandler implements PreprocessorInterface
{
    private string $arrayTypeRegex = '/(\w+\s*\(.*?\))\s*:\s*\[(.+?)\]/';
    public function process(string $code): string
    {

        return preg_replace_callback($this->arrayTypeRegex, function ($matches) {
            $functionSignature = $matches[1];
            $rawInnerTypes = $matches[2];
            return $functionSignature . " : array /* @PS_VALIDATE_ARRAY[$rawInnerTypes] */";
        }, $code);
    }

    private function mapToNative(string $type): string
    {

        $specialTypes = ['Email', 'Ipv4', 'Ipv6', 'Json', 'Date'];

        if (\in_array($type, $specialTypes, true)) {
            return 'string';
        }

        $map = ['Int' => 'int', 'String' => 'string', 'Float' => 'float', 'Bool' => 'bool'];
        return $map[$type] ?? $type;
    }
}
