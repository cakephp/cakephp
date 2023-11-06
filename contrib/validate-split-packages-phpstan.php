#!/usr/bin/php -q
<?php
declare(strict_types=1);

/*
 * Validate split packages through PHPStan.
 */

$options = [
    __DIR__ . '/../vendor/autoload.php',
    __DIR__ . '/vendor/autoload.php',
];
if (!empty($_SERVER['PWD'])) {
    array_unshift($options, $_SERVER['PWD'] . '/vendor/autoload.php');
}

foreach ($options as $file) {
    if (file_exists($file)) {
        define('COMPOSER_INSTALL', $file);

        break;
    }
}
require COMPOSER_INSTALL;

$path = dirname(__DIR__) . DS . 'src' . DS;
$di = new RecursiveDirectoryIterator($path, RecursiveDirectoryIterator::SKIP_DOTS);
$iterator = new RecursiveIteratorIterator($di, RecursiveIteratorIterator::LEAVES_ONLY);
/** @var array<\SplFileInfo> $iterator */
$iterator = new RegexIterator($iterator, '~/src/\w+/composer.json$~');

$packages = [];
$code = 0;
foreach ($iterator as $file) {
    $filePath = $file->getPath();
    $package = substr($filePath, strrpos($filePath, '/') + 1);
    $packages[$filePath . '/'] = $package;
}
ksort($packages);

$issues = [];
foreach ($packages as $path => $package) {
    // For now, demo only
    if (!file_exists($path . 'phpstan.neon.dist')) {
        continue;
    }

    $exitCode = null;
    exec('cd ' . $path . ' && composer install && vendor/bin/phpstan analyze ./', $output, $exitCode);
    if ($exitCode !== 0) {
        $code = $exitCode;
    }
    exec('cd ' . $path . ' && rm composer.lock && rm -rf vendor');
}

exit($code);
