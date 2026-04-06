
<?php

use FastVolt\Helper\Markdown;

require __DIR__ . '/../vendor/autoload.php';

$srcDir = realpath(__DIR__ . '/../src');
$docDir = realpath(__DIR__ . '/../doc') ?: __DIR__ . '/../doc';



/*
|--------------------------------------------------------------------------
| Scan markdown files
|--------------------------------------------------------------------------
*/

function scanMarkdownFiles(string $dir): array {
  $files = [];

  $iterator = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator($dir)
  );

  foreach ($iterator as $file) {
    if ($file->isFile() && $file->getExtension() === 'md') {
      $files[] = $file->getPathname();
    }
  }

  return $files;
}

/*
|--------------------------------------------------------------------------
| Build tree structure
|--------------------------------------------------------------------------
*/

function buildTree(array $files, string $srcDir): array {
  $tree = [];

  foreach ($files as $file) {

    $relativePath = str_replace($srcDir . '/', '', $file);
    $parts = explode('/', $relativePath);

    $current = &$tree;

    foreach ($parts as $index => $part) {

      if ($index === count($parts) - 1) {
        $current['__files'][] = $relativePath;
      } else {
        $current[$part] ??= [];
        $current = &$current[$part];
      }
    }
  }

  return $tree;
}

/*
|--------------------------------------------------------------------------
| Render menu recursively
|--------------------------------------------------------------------------
*/

function renderMenu(array $tree, string $basePath = ''): string {
  $html = '<ul>';

  foreach ($tree as $key => $branch) {

    if ($key === '__files') {

      foreach ($branch as $file) {
        $name = basename($file, '.md');
        $link = preg_replace('/\.md$/', '.html', $file);
        $html .= "<li><a href=\"{$link}\">{$name}</a></li>";
      }

      continue;
    }

    $html .= "<li class=\"folder\">{$key}";
    $html .= renderMenu($branch, $basePath . '/' . $key);
    $html .= '</li>';
  }

  $html .= '</ul>';

  return $html;
}

/*
|--------------------------------------------------------------------------
| Ensure directory
|--------------------------------------------------------------------------
*/

function ensureDirectory(string $path): void {
  if (!is_dir($path)) {
    mkdir($path, 0777, true);
  }
}

/*
|--------------------------------------------------------------------------
| Build doc path
|--------------------------------------------------------------------------
*/

function buildDocPath(string $srcFile, string $srcDir, string $docDir): string {
  $relativePath = str_replace($srcDir, '', $srcFile);
  $htmlPath = preg_replace('/\.md$/', '.html', $relativePath);

  return $docDir . $htmlPath;
}

/*
|--------------------------------------------------------------------------
| Template
|--------------------------------------------------------------------------
*/

function buildPage(string $menu, string $content): string {
  return <<<HTML
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Documentation</title>
    <style>
        body {
            margin: 0;
            font-family: Arial, sans-serif;
            display: flex;
        }

        aside {
            width: 260px;
            background: #111;
            color: #eee;
            height: 100vh;
            overflow-y: auto;
            padding: 20px;
        }

        aside a {
            color: #9ad;
            text-decoration: none;
        }

        aside ul {
            list-style: none;
            padding-left: 15px;
        }

        main {
            flex: 1;
            padding: 40px;
        }

        .folder {
            font-weight: bold;
            margin-top: 10px;
        }
    </style>
</head>
<body>

<aside>
    {$menu}
</aside>

<main>
    {$content}
</main>

</body>
</html>
HTML;
}

/*
|--------------------------------------------------------------------------
| Pipeline
|--------------------------------------------------------------------------
*/

$markdownFiles = scanMarkdownFiles($srcDir);

$tree = buildTree($markdownFiles, $srcDir);
$menu = renderMenu($tree);

foreach ($markdownFiles as $mdFile) {
  $markdown = new Markdown();
  $docPath = buildDocPath($mdFile, $srcDir, $docDir);

  ensureDirectory(dirname($docPath));

  $content = file_get_contents($mdFile);

  $markdown->setContent($content);
  $htmlContent = $markdown->getHtml();

  $fullPage = buildPage($menu, $htmlContent);

  file_put_contents($docPath, $fullPage);

  echo "✔ Generated: {$docPath}" . PHP_EOL;
}

echo PHP_EOL . "🚀 Docs ready." . PHP_EOL;
