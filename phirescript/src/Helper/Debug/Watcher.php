<?php

declare(strict_types=1);

namespace PHireScript\Helper\Debug;

class Watcher
{
    private static array $watchedVariables = [];
    private static bool $isRegistered = false;


    public static function track(string $name, &$variable): void
    {
        if (!self::$isRegistered) {
            register_tick_function(self::handleTicks(...));
            self::$isRegistered = true;
        }

        self::$watchedVariables[$name] = [
        'value'   => $variable,
        'ref'     => &$variable,
        'history' => []
        ];
    }

    public static function handleTicks(): void
    {
        foreach (self::$watchedVariables as $name => &$data) {
            if ($data['ref'] !== $data->value) {
                $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2);
                $caller = $trace[1] ?? $trace[0];

                $data['history'][] = [
                'from' => $data->value,
                'to'   => $data['ref'],
                'file' => $caller['file'] ?? 'unknown',
                'line' => $caller->line ?? 0,
                'time' => microtime(true)
                ];

                $data->value = $data['ref'];
            }
        }
    }

    public static function getHistory(string $name): array
    {
        return self::$watchedVariables[$name]['history'] ?? [];
    }
}
