#!/usr/bin/php -q
<?php
declare(strict_types=1);

/*
 * Validate that each deprecation of a class is followed by class_alias() and class_exists()
 * in each relevant file complementing each other.
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
/** @var array<\SplFileInfo> $iterator */
$iterator = new RecursiveIteratorIterator($di);

$code = 0;
foreach ($iterator as $file) {
    if (pathinfo((string)$file, PATHINFO_EXTENSION) !== 'php') {
        continue;
    }
    if (pathinfo((string)$file, PATHINFO_FILENAME) === 'functions') {
        continue;
    }
    if (strpos($file->getRealPath(), '/TestSuite/')) {
        continue;
    }

    $content = file_get_contents((string)$file);
    if (!strpos($content, 'class_alias(')) {
        continue;
    }

    preg_match('#class_alias\(\s*\'([^\']+)\',#', $content, $matches);
    if (!$matches) {
        var_dump($content);
        var_dump($file->getPath());
        exit(1);
    }

    echo $matches[1] . PHP_EOL;
    $filePath = str_replace('\\', '/', $matches[1]);
    $filePath = str_replace('Cake/', $path, $filePath);
    $filePath .= '.php';
    if (!file_exists($filePath)) {
        throw new RuntimeException('Cannot find path for `' . $matches[1] . '`');
    }

    $newFileContent = file_get_contents($filePath);

    if (strpos($newFileContent, 'class_exists(') === false && !str_contains($newFileContent, 'class_alias(')) {
        $oldPath = str_replace($path, '', $file->getRealPath());
        $newPath = str_replace($path, '', $filePath);
        echo "\033[31m" . ' * Missing `class_exists()` or `class_alias()` on new file for `' . $oldPath . '` => `' . $newPath . '`' .  "\033[0m" . PHP_EOL;
        $code = 1;
    } else {
        echo ' * OK' . PHP_EOL;
    }
}

exit($code);
