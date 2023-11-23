#!/usr/bin/php -q
<?php
declare(strict_types=1);

/*
 * Compare split packages' composer.json with ROOT composer.json in regard to dependency constraints.
 */

use Cake\Utility\Inflector;

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
    if ($package === 'ORM') {
        $fullName = 'cakephp/orm';
    } else {
        $fullName = 'cakephp/' . Inflector::dasherize($package);
    }
    $packages[$fullName] = $package;
}
ksort($packages);

$mainJsonContent = file_get_contents(dirname(__FILE__, 2) . DS . 'composer.json');
$mainJson = json_decode($mainJsonContent, true);
$mainReplace = $mainJson['replace'];
$missing = [];
foreach ($packages as $fullPackageName => $package) {
    if (!empty($mainReplace[$fullPackageName])) {
        unset($mainReplace[$fullPackageName]);

        continue;
    }

    $missing[] = $package;
}
if ($mainReplace) {
    echo "\033[31m" . ' * Missing "replace" statement in ROOT composer.json for package `' . $package . '`' . "\033[0m" . PHP_EOL;
    $code = 1;
}
if ($missing) {
    echo "\033[31m" . ' * Extra "replace" statement in ROOT composer.json for non-existent package(s) `' . implode(', ', $missing)  . '`' . "\033[0m" . PHP_EOL;
    $code = 1;
}

$mainRequire = $mainJson['require'];

$issues = [];
foreach ($packages as $fullPackageName => $package) {
    $content = file_get_contents($path . $package . DS . 'composer.json');
    $json = json_decode($content, true);
    $require = $json['require'] ?? [];

    foreach ($require as $packageName => $constraint) {
        if (isset($packages[$packageName])) {
            continue;
        }

        if (!isset($mainRequire[$packageName])) {
            $issues[$package][] = 'Missing package requirement `' . $packageName . ': ' . $constraint . '` in ROOT composer.json';

            continue;
        }

        if ($mainRequire[$packageName] !== $constraint) {
            $issues[$package][] = 'Package requirement `' . $packageName . ': ' . $constraint . '` does not match the one in ROOT composer.json (`' . $mainRequire[$packageName] . '`)';
        }
    }
}

if ($issues) {
    foreach ($issues as $packageName => $packageIssues) {
        echo "\033[31m" . $packageName  . ':' . "\033[0m" . PHP_EOL;
        foreach ($packageIssues as $issue) {
            echo "\033[31m" . ' - ' . $issue  . "\033[0m" . PHP_EOL;
            $code = 1;
        }
    }
}

exit($code);
