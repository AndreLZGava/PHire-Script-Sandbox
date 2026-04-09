<?php

use PhpCsFixer\Config;
use PhpCsFixer\Finder;

return (new Config())
    ->setRules([
        '@PSR12' => true,
        'strict_param' => true,
        'declare_strict_types' => true,
    ])
    ->setFinder(
        Finder::create()->in(__DIR__ . '/src')
    );
