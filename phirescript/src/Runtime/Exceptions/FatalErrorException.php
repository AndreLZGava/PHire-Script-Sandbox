<?php

declare(strict_types=1);

namespace PHireScript\Runtime\Exceptions;

use Throwable;

class FatalErrorException
{
    public static function prettyException(Throwable $e): void
    {
        try {
            if (self::isCli()) {
                self::renderCli($e);
            } else {
                self::render($e);
            }
        } catch (Throwable $fatal) {
            echo "<pre>";
            echo "Fatal error inside error handler:\n\n";
            echo $fatal->getMessage() . "\n\n";
            echo $e->getMessage();
            echo "</pre>";
        }

        exit;
    }

    private static function renderCli(Throwable $e): void
    {
        $red = "\033[31m";
        $yellow = "\033[33m";
        $blue = "\033[36m";
        $gray = "\033[90m";
        $reset = "\033[0m";

        echo "\n{$red}💥 " . \get_class($e) . "{$reset}\n";
        echo $e->getMessage() . "\n\n";

        echo "📄 {$blue}" . $e->getFile() . "{$reset}:{$yellow}" . $e->getLine() . "{$reset}\n";

        echo self::renderCliCodePreview($e->getFile(), $e->getLine());

        echo "\nStack trace:\n";

        foreach ($e->getTrace() as $i => $trace) {
            $file = $trace['file'] ?? '[internal]';
            $line = $trace['line'] ?? '-';
            $class = $trace['class'] ?? '';
            $type = $trace['type'] ?? '';
            $function = $trace['function'] ?? '';

            $shortFile = basename($file);

            echo "{$gray}#$i{$reset} ";
            echo "{$blue}{$shortFile}{$reset}:{$yellow}{$line}{$reset} → ";
            echo "{$class}{$type}{$function}()\n";
        }

        echo "\n";
    }

    private static function renderCliCodePreview(string $file, int $line, int $padding = 2): string
    {
        if (!file_exists($file)) {
            return '';
        }

        $red = "\033[31m";
        $gray = "\033[90m";
        $reset = "\033[0m";

        $lines = file($file);
        $start = max(0, $line - $padding - 1);
        $end = min(\count($lines), $line + $padding);

        $output = "\n";

        for ($i = $start; $i < $end; $i++) {
            $currentLine = $i + 1;
            $prefix = $currentLine === $line ? "{$red}▶{$reset}" : " ";

            $output .= sprintf(
                "%s {$gray}%4d{$reset} | %s",
                $prefix,
                $currentLine,
                $lines[$i]
            );
        }

        return $output;
    }

    private static function isCli(): bool
    {
        return php_sapi_name() === 'cli';
    }

    private static function render($e)
    {
        http_response_code(500);

        echo '<style>
        body {
            font-family: monospace;
            background: #0f172a;
            color: #e2e8f0;
            padding: 20px;
        }
        .box {
            background: #111827;
            border-radius: 12px;
            padding: 20px;
            box-shadow: 0 0 20px rgba(0,0,0,0.5);
        }
        .title {
            font-size: 20px;
            color: #f87171;
            margin-bottom: 10px;
        }
        .meta {
            color: #94a3b8;
            margin-bottom: 15px;
        }
        .trace {
            margin-top: 20px;
        }
        .trace-item {
            padding: 8px;
            border-bottom: 1px solid #1f2933;
        }
        .file {
            color: #38bdf8;
        }
        .line {
            color: #fbbf24;
        }
        .code {
            background: #020617;
            padding: 10px;
            margin-top: 15px;
            border-radius: 8px;
            font-size: 13px;
            overflow-x: auto;
        }
        .highlight {
            background: rgba(248,113,113,0.2);
        }

        .trace-item {
          padding: 12px;
          margin-top: 10px;
          border-radius: 8px;
          background: #020617;
          border: 1px solid #1f2933;
        }
    </style>';

        echo '<div class="box">';
        echo '<div class="title">💥 ' . \get_class($e) . '</div>';
        echo '<div>' . htmlspecialchars($e->getMessage()) . '</div>';

        echo '<div class="meta">';
        echo '📄 <span class="file">' . $e->getFile() . '</span> ';
        echo 'linha <span class="line">' . $e->getLine() . '</span>';
        echo '</div>';

        echo self::renderCodePreview($e->getFile(), $e->getLine());

        echo '<div class="trace">';
        echo '<strong>Stack trace:</strong>';

        foreach ($e->getTrace() as $i => $trace) {
            $file = $trace['file'] ?? '[internal]';
            $line = $trace['line'] ?? '-';
            $class = $trace['class'] ?? '';
            $type = $trace['type'] ?? '';
            $function = $trace['function'] ?? '';

            $shortFile = basename($file);

            echo '<div class="trace-item">';

            echo "<div style='color:#64748b;'>#$i</div>";

            echo "<div>";
            echo "<span class='file'>$shortFile</span>";
            echo " <span class='line'>:$line</span>";
            echo "</div>";

            echo "<div style='color:#a78bfa; margin-top:4px;'>";
            echo "$class$type<strong>$function</strong>()";
            echo "</div>";

            echo '</div>';
        }

        echo '</div>';

        exit;
    }

    private static function renderCodePreview(string $file, int $line, int $padding = 3): string
    {
        if (!file_exists($file)) {
            return '';
        }

        $lines = file($file);
        $start = max(0, $line - $padding - 1);
        $end = min(\count($lines), $line + $padding);

        $html = '<div class="code">';

        for ($i = $start; $i < $end; $i++) {
            $isErrorLine = ($i + 1 === $line);

            $html .= '<div class="' . ($isErrorLine ? 'highlight' : '') . '">';
            $html .= '<span style="color:#64748b;">' . str_pad((string)($i + 1), 4, ' ', STR_PAD_LEFT) . '</span> ';
            $html .= htmlspecialchars($lines[$i]);
            $html .= '</div>';
        }

        $html .= '</div>';

        return $html;
    }
}
