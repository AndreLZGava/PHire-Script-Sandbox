<?php

declare(strict_types=1);

require 'vendor/autoload.php';

use PHireScript\Compiler;
use PHireScript\Core\CompileMode;
use PHireScript\Core\CompilerContext;
use PHireScript\Helper\Debug\Debug;

$baseDir = __DIR__;
$requestFile = $_GET['file'] ?? null;

if ($requestFile !== null) {
    $requestFile = \ltrim($requestFile, '/\\');
    $file = realpath($baseDir . DIRECTORY_SEPARATOR . $requestFile);

    if (
        $file !== false &&
        is_file($file) &&
        str_starts_with($file, $baseDir) &&
        pathinfo($file, PATHINFO_EXTENSION) === 'ps'
    ) {
        $context = new CompilerContext(
            CompileMode::DEBUG,
            true,
            file: $requestFile,
            displayInsideCompiler:true,
        );

        $compiler = new Compiler($context);
        $compiler->compile();
        exit;
        //echo '<pre style="background:#111;color:#0f0;padding:20px;border-radius:8px;">';
        //echo htmlspecialchars(file_get_contents($file));
        //echo '</pre>';
    }

    http_response_code(404);
    echo "Invalid file.";
    exit;
}

function findPsFiles(string $baseDir): array
{
    $files = [];

    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator(
            $baseDir,
            FilesystemIterator::SKIP_DOTS
        )
    );

    foreach ($iterator as $file) {
        if ($file->isFile() && $file->getExtension() === 'ps') {
            $files[] = \str_replace($baseDir . DIRECTORY_SEPARATOR, '', $file->getPathname());
        }
    }

    sort($files);

    return $files;
}

$baseDir = __DIR__;
$psFiles = findPsFiles($baseDir);

?>
<!DOCTYPE html>
<html>

<head>
    <meta charset="UTF-8">
    <title>PHireScript Files</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            padding: 40px;
        }

        ul {
            list-style: none;
            padding: 0;
        }

        li {
            margin-bottom: 6px;
        }

        a {
            text-decoration: none;
            color: #007BFF;
        }

        a:hover {
            text-decoration: underline;
        }
    </style>
</head>

<body>

    <h1>Files .ps found</h1>

    <ul>
        <?php foreach ($psFiles as $file) : ?>
            <li>
                <a href="?file=<?= urlencode($file) ?>">
                    <?= htmlspecialchars($file) ?>
                </a>
            </li>
        <?php endforeach; ?>
    </ul>

    <?php if (empty($psFiles)) : ?>
        <p>No .ps File found!</p>
    <?php endif; ?>

</body>

</html>
