<?php

declare(strict_types=1);

namespace PHireScript\Helper\Debug;

class Debug
{
    private static int $callCount = 0;

    public static function trace(...$args): void
    {
        self::$callCount++;
        $isCli = (php_sapi_name() === 'cli');

        $backtrace = debug_backtrace();
        $file = $backtrace[0]['file'] ?? 'unknown';
        $line = $backtrace[0]['line'] ?? 0;

        $isAlt = self::$callCount % 2 === 0;
        array_unshift($args, debug_backtrace(2));
        foreach ($args as $index => $val) {
            $type = gettype($val);
            $displayValue = self::formatValue($val);

            $isArgAlt = ($index % 2 === 0) ? $isAlt : !$isAlt;

            if ($isCli) {
                self::renderCli($displayValue, $type, $file, $line, $isArgAlt);
            } else {
                self::renderWeb($displayValue, $type, $file, $line, $isArgAlt);
            }
        }
        exit;
    }

    public static function show(...$args): void
    {
        self::$callCount++;
        $isCli = (php_sapi_name() === 'cli');

        $backtrace = debug_backtrace();
        $file = $backtrace[0]['file'] ?? 'unknown';
        $line = $backtrace[0]['line'] ?? 0;

        $isAlt = self::$callCount % 2 === 0;

        foreach ($args as $index => $val) {
            $type = gettype($val);
            $displayValue = self::formatValue($val);

            $isArgAlt = ($index % 2 === 0) ? $isAlt : !$isAlt;

            if ($isCli) {
                self::renderCli($displayValue, $type, $file, $line, $isArgAlt);
            } else {
                self::renderWeb($displayValue, $type, $file, $line, $isArgAlt);
            }
        }
    }

    private static function formatValue($val): string
    {
        if (is_array($val) || is_object($val)) {
            return print_r($val, true);
        }
        if (is_bool($val)) {
            return $val ? 'true' : 'false';
        }
        return (string)$val;
    }

    private static function renderCli($val, $type, $file, $line, $alt): void
    {
        $color = $alt ? "\033[1;37m" : "\033[0;90m";
        $reset = "\033[0m";
        $header = "\033[0;33m[$file : $line]\033[0m";

        echo "$header $color($type)$reset $color$val$reset\n";
    }

    private static function renderWeb($val, $type, $file, $line, $alt): void
    {
        $bgColor = $alt ? "#ffffff" : "#f0f0f0";
        $textColor = $alt ? "#000000" : "#666666";

        echo "<pre style='background: $bgColor; color: $textColor; " .
            "margin: 0; padding: 5px; border-left: 3px solid #ffcc00;" .
            " font-family: monospace;'>";
        echo "<b style='color: #a52a2a;'>[$file : $line]</b> ";
        echo "<i style='opacity: 0.7;'>($type)</i> ";
        echo htmlspecialchars((string) $val);
        echo "</pre>";
    }

    public static function display(...$args): void
    {
        self::$callCount++;
        $isCli = (php_sapi_name() === 'cli');

        $backtrace = debug_backtrace();
        $file = $backtrace[0]['file'] ?? 'unknown';
        $line = $backtrace[0]['line'] ?? 0;

        $isAlt = self::$callCount % 2 === 0;

        foreach ($args as $index => $val) {
            $isArgAlt = ($index % 2 === 0) ? $isAlt : !$isAlt;

            if ($isCli) {
                $type = gettype($val);
                $displayValue = self::formatValue($val);
                self::renderCli($displayValue, $type, $file, $line, $isArgAlt);
            } else {
                self::renderCollapsibleWeb($val, $file, $line, $isArgAlt);
            }
        }
    }

    private static function renderCollapsibleWeb($val, $file, $line, $alt): void
    {
        $bgColor = $alt ? "#ffffff" : "#fbfbfb";
        $textColor = $alt ? "#333333" : "#555555";

        echo "<div style='background: $bgColor; color: $textColor; margin: 10px " .
            "0; padding: 10px; border-left: 4px solid #007bff; font-family: ui-monospace," .
            " SFMono-Regular, Menlo, Monaco, Consolas, monospace; font-size: 13px; " .
            " box-shadow: 0 1px 3px rgba(0,0,0,0.1); overflow-x: auto;'>";
        echo "<div style='margin-bottom: 8px; border-bottom: 1px solid #ddd;" .
        " padding-bottom: 5px;'>";
        echo "<b style='color: #d9534f;'>[$file : $line]</b>";
        echo "</div>";

        echo self::buildCollapsibleHtml($val, 1, 3);

        echo "</div>";
    }

    private static function buildCollapsibleHtml($val, int $depth = 1, int $maxDepth = 3): string
    {
        if ($val === null) {
            return "<span style='color: #888; font-weight: bold;'>null</span>";
        }
        if (is_bool($val)) {
            return "<span style='color: #0000ff; font-weight: bold;'>" . ($val ?
                'true' : 'false') . "</span>";
        }
        if (\is_string($val)) {
            return "<span style='color: #28a745;'>\"" .
                htmlspecialchars($val) . "\"</span>";
        }
        if (is_numeric($val)) {
            return "<span style='color: #ff8c00; font-weight: bold;'>" . $val . "</span>";
        }

        if ($depth > $maxDepth && (is_array($val) || is_object($val))) {
            $type = is_object($val) ? 'Object (' . \get_class($val) . ')' : 'Array';
            return "<span style='color: #999; font-style: italic;'>* " .
                "$type (Limit of $maxDepth reached) *</span>";
        }

        if (is_array($val)) {
            $count = \count($val);
            if ($count === 0) {
                return "<span style='color: #666;'>Array(0) []</span>";
            }

            $html = "<details style='margin-left: 5px;'>";
            $html .= "<summary style='cursor: pointer; color: #0056b3;" .
                " font-weight: bold; user-select: none;'>Array ($count)</summary>";
            $html .= "<div style='margin-left: 15px; border-left:" .
                " 1px dashed #ccc; padding-left: 10px; margin-top: 5px;'>";

            foreach ($val as $k => $v) {
                $html .= "<div style='margin-bottom: 4px;'>[<span style='color: " .
                    "#555;'><strong>" . htmlspecialchars((string)$k) . "</strong></span>] => " .
                    self::buildCollapsibleHtml($v, $depth + 1, $maxDepth) . "</div>";
            }

            $html .= "</div></details>";
            return $html;
        }

        if (is_object($val)) {
            $class = \get_class($val);
            $arrayMap = (array)$val;

            if (empty($arrayMap)) {
                return "<span style='color: #666;'>Object ($class) {}</span>";
            }

            $html = "<details style='margin-left: 5px;'>";
            $html .= "<summary style='cursor: pointer; color: #6f42c1; " .
                "font-weight: bold; user-select: none;'>Object ($class)</summary>";
            $html .= "<div style='margin-left: 15px; border-left: 1px " .
                " dashed #ccc; padding-left: 10px; margin-top: 5px;'>";

            foreach ($arrayMap as $k => $v) {
                $visibility = 'public';
                $propName = $k;

                if (strpos((string)$k, "\0") === 0) {
                    $parts = \explode("\0", (string)$k);
                    $propName = $parts[2] ?? $k;
                    $visibility = ($parts[1] === '*') ? 'protected' : 'private';
                }

                $visColor = $visibility === 'public' ? '#28a745' : ($visibility === 'protected' ? '#fd7e14' :
                    '#dc3545');

                $html .= "<div style='margin-bottom: 4px;'>";
                $html .= "<span style='font-size: 0.85em; color: $visColor; " .
                    "border: 1px solid $visColor; border-radius: 3px; padding: 0 3px;" .
                    " margin-right: 5px;'>$visibility</span>";

                $html .= "<span style='color: #555;'><strong>" .
                    htmlspecialchars((string)$propName) . "</strong></span> => " .
                    self::buildCollapsibleHtml($v, $depth + 1, $maxDepth);
                $html .= "</div>";
            }
            $html .= "</div></details>";
            return $html;
        }

        return htmlspecialchars((string)$val);
    }
}
