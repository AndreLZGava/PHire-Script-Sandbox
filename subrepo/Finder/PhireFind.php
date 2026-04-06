<?php

function require_find(string $target): string {
    // ⏱️ 1. Inicia o cronômetro
    $startTime = microtime(true);
    $warningEmitted = false; // Flag para não flodar o terminal com o mesmo alerta

    $startDir = realpath(getcwd());

    if (!$startDir) {
        throw new Exception("Diretório atual inválido.");
    }

    $target = preg_replace('/^(\.\.\/|\.\/|\/|\\\\)+/', '', $target);
    $target = rtrim($target, '/\\');
    $target = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $target);

    if (empty($target)) {
        throw new Exception("O caminho de busca fornecido está vazio ou é inválido.");
    }

    $currentDir = $startDir;
    $excludeDir = null;

    while (true) {
        // Passamos o tempo inicial e a flag por referência (&$warningEmitted) para a função
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

    throw new Exception("O arquivo ou diretório '{$target}' não foi encontrado no projeto.");
}

// ⏱️ 2. Recebemos $startTime e usamos &$warningEmitted (passagem por referência)
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
        // ⏱️ 3. Calcula quanto tempo passou desde o início
        $elapsedTime = microtime(true) - $startTime;

        // 🚨 Timeout Crítico: Aborta após 10 segundos
        if ($elapsedTime > 10.0) {
            throw new Exception("\n[Timeout] A busca por '{$target}' foi abortada após 10 segundos. Verifique se o nome digitado está correto.");
        }

        // ⚠️ Alerta de Lentidão: Avisa aos 2 segundos (apenas uma vez)
        if ($elapsedTime > 2.0 && !$warningEmitted) {
            echo "\n[Aviso] A busca por '{$target}' está demorando mais de 2 segundos. Vasculhando projeto...\n";
            $warningEmitted = true; // Marca como emitido para não repetir no próximo arquivo
        }

        $path = $file->getPathname();

        if (str_ends_with($path, $targetMatch) || $path === $dir . DIRECTORY_SEPARATOR . $target) {
            return $path;
        }
    }

    return null;
}

// ==========================================
// EXEMPLOS DE USO:
// ==========================================
try {
    // Simulando um erro de digitação proposital
    $path = require_find('Example.txt');

    echo "Encontrado em: {$path}\n";
} catch (Exception $e) {
    echo $e->getMessage() . "\n";
}
