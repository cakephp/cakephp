<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;

return RectorConfig::configure()
    ->withPaths([
        __DIR__ . '/config',
        __DIR__ . '/contrib',
        __DIR__ . '/src',
        __DIR__ . '/tests',
    ])
    ->withSkip([
        __DIR__ . '/tests/test_app/templates',
        __DIR__ . '/tests/test_app/Plugin/TestPlugin/templates',
    ])
    ->withPhpSets(php55: true);
