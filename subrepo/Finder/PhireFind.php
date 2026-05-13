<?php

function require_find(string $target): string {
    // ⏱️ 1. Starts the timer
    $startTime = microtime(true);
    $warningEmitted = false; // Flag to avoid flooding the terminal with the same warning

    $startDir = realpath(getcwd());

    if (!$startDir) {
        throw new Exception("Invalid current directory.");
    }

    $target = preg_replace('/^(\.\.\/|\.\/|\/|\\\\)+/', '', $target);
    $target = rtrim($target, '/\\');
    $target = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $target);

    if (empty($target)) {
        throw new Exception("The provided search path is empty or invalid.");
    }

    $currentDir = $startDir;
    $excludeDir = null;

    while (true) {
        // We pass the start time and flag by reference (&$warningEmitted) to the function
        $result = searchDownwards($currentDir, $target, $excludeDir, $startTime, $warningEmitted);

        if ($result !== null) {
            return $result;
        }

        if (
            file_exists($currentDir . DIRECTORY_SEPARATOR . 'composer.json') ||
            file_exists($currentDir . DIRECTORY_SEPARATOR . '.git')
        ) {
            break;
        }

        $parentDir = dirname($currentDir);

        if ($parentDir === $currentDir) {
            break;
        }

        $excludeDir = $currentDir;
        $currentDir = $parentDir;
    }

    throw new Exception("File or directory '{$target}' not found in the project.");
}

// ⏱️ 2. Receives $startTime and uses &$warningEmitted (passed by reference)
function searchDownwards(string $dir, string $target, ?string $excludeDir, float $startTime, bool &$warningEmitted): ?string {
    if (!is_dir($dir)) return null;

    $dirIterator = new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS);

    $filter = new RecursiveCallbackFilterIterator($dirIterator, function ($current) use ($excludeDir) {
        if ($current->isDir() && $current->getPathname() === $excludeDir) {
            return false;
        }

        if ($current->isDir() && in_array($current->getFilename(), ['vendor', 'node_modules', '.git', 'storage'])) {
            return false;
        }

        return true;
    });

    $iterator = new RecursiveIteratorIterator($filter, RecursiveIteratorIterator::SELF_FIRST, RecursiveIteratorIterator::CATCH_GET_CHILD);

    $targetMatch = DIRECTORY_SEPARATOR . $target;

    foreach ($iterator as $file) {
        // ⏱️ 3. Calculates elapsed time since start
        $elapsedTime = microtime(true) - $startTime;

        // 🚨 Critical Timeout: Aborts after 10 seconds
        if ($elapsedTime > 10.0) {
            throw new Exception("\n[Timeout] Search for '{$target}' was aborted after 10 seconds. Check if the name is spelled correctly.");
        }

        // ⚠️ Slowness Warning: alerts at 2 seconds (only once)
        if ($elapsedTime > 2.0 && !$warningEmitted) {
            echo "\n[Warning] Search for '{$target}' is taking more than 2 seconds. Scanning project...\n";
            $warningEmitted = true; // Marks as emitted to avoid repeating on the next file
        }

        $path = $file->getPathname();

        if (str_ends_with($path, $targetMatch) || $path === $dir . DIRECTORY_SEPARATOR . $target) {
            return $path;
        }
    }

    return null;
}

// ==========================================
// USAGE EXAMPLES:
// ==========================================
try {
    // Simulating an intentional typo
    $path = require_find('Example.txt');

    echo "Found at: {$path}\n";
} catch (Exception $e) {
    echo $e->getMessage() . "\n";
}
