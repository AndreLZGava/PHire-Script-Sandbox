<?php

declare(strict_types=1);

namespace PHireScript\Compiler\Emitter\NodeEmitters;

use Exception;
use PHireScript\Compiler\Emitter\EmitContext;
use PHireScript\Compiler\Emitter\NodeEmitter;
use PHireScript\Compiler\Parser\Ast\Nodes\FunctionNode;
use PHireScript\Helper\Debug\Debug;
use PHireScript\Runtime\DefaultOverrideMethods\BaseParams;

class FunctionEmitter extends NodeEmitterAbstract implements NodeEmitter
{
    public function supports(object $node, EmitContext $ctx): bool
    {
        return $node instanceof FunctionNode;
    }

    public function emit(object $node, EmitContext $ctx): string
    {
        $code = $this->overrideVariable($node, $ctx);
        $code .= $this->overrideSelf($node, $ctx);
        $normalized = $this->normalizeParams(
            $node->params->params,
            $node->method->params,
            $code,
            $ctx
        );
        $code = $this->overrideParams($normalized);
        $code .= ";\n";
        return $code;
    }

    private function overrideVariable($node, $ctx)
    {
        if ($node->overrideVariableFocus) {
            return "$" . $node->variableBase->name . ' = ';
        }

        return '';
    }

    private function overrideSelf($node, $ctx)
    {
        $variable = $ctx->emitter->emit($node->variableBase, $ctx);
        $method = $node->method->phpCodeForConversion;

        if (\is_array($method)) {
            $lines = [];

            foreach ($method as $line) {
                $lines[] = \str_replace('@self', $variable, $line);
            }

            return $this->wrapAsIIFE($lines, $variable);
        }

        return \str_replace('@self', $variable, $method);
    }

    private function wrapAsIIFE(array $lines, string $variable): string
    {
        $indented = \implode("\n    ", $lines);

        return "(function() use ($variable) {\n    $indented\n})()";
    }

    private function overrideParams($normalized)
    {
        $params = \implode(', ', $normalized->params);
        $code = \str_replace('@params', $params, $normalized->code);
        return $code;
    }

    private function normalizeParams($sentParams, $expected, $code, $ctx)
    {
        $params = [];
        $last = \end($expected) ?: (object) ['name' => ''];
        foreach ($sentParams as $methodParamId => $param) {
            if (isset($sentParams[$methodParamId])) {
                $params[$methodParamId] = $ctx->emitter->emit($sentParams[$methodParamId], $ctx);
                $expectedParam = isset($expected[$methodParamId]) ? $expected[$methodParamId] : $last;
                $expectedParamName = $expectedParam->name === '@params' ? '' : $expectedParam->name;
                $code = $this->processNamedParams($expectedParamName, $params[$methodParamId], $code);
                continue;
            }
            $code = $this->processNamedParams($param->name, $this->processDefaultValue($param), $code);
        }

        $code = \preg_replace('/@(?!(params)\b)\w+/', '', $code);


        return (object) ['params' => $params, 'code' => $code];
    }

    private function processDefaultValue(BaseParams $param)
    {
        $type = $param->type;

        if (!$param->required && $param->defaultValue === null) {
            return '';
        }

        if ($type === 'string') {
            return "'" . $param->defaultValue . "'";
        }

        if ($type === 'bool') {
            return \filter_var($param->defaultValue, FILTER_VALIDATE_BOOLEAN);
        }

        if ($type === 'float') {
            return \filter_var($param->defaultValue, FILTER_VALIDATE_FLOAT);
        }

        if ($type === 'int') {
            return (int) $param->defaultValue;
        }
    }

    private function processNamedParams($paramName, $paramValue, $originalCode)
    {
        if (\gettype($paramName) !== 'integer') {
            return \str_replace($paramName, $paramValue, $originalCode);
        }
        return '';
    }
}
