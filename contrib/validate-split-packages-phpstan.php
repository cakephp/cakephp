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

$phivePharsXml = simplexml_load_file(dirname(__FILE__, 2) . DS . '.phive' . DS . 'phars.xml');
$phpstanVersion = null;
foreach ($phivePharsXml->phar as $phar) {
    if ($phar->attributes()->name == 'phpstan') {
        $phpstanVersion = (string)$phar->attributes()->version;
        break;
    }
}
// Prefer the dev branches of sibling cakephp packages over the latest tagged
// release. The split packages require siblings as `5.4.*@dev`, which also matches
// already-published RC/stable tags; composer prefers those tags, so it would
// validate against released code that can lag behind the monorepo. Forcing dev
// stability makes it resolve the `dev-<branch>` mirror instead, matching local src.
$composerCommand = 'composer config minimum-stability dev'
    . ' && composer config prefer-stable false'
    . ' && composer require --dev phpstan/phpstan:' . $phpstanVersion;

$issues = [];
foreach ($packages as $path => $package) {
    if (!file_exists($path . 'phpstan.neon.dist')) {
        continue;
    }

    $exitCode = null;
    exec(
        'cd ' . $path . ' && ' . $composerCommand . ' && vendor/bin/phpstan analyze ./',
        $output,
        $exitCode
    );
    if ($exitCode !== 0) {
        $code = $exitCode;

        $issues[] = $package . ': ' . PHP_EOL . implode(PHP_EOL, $output);
    }
    exec('cd ' . $path . ' && rm composer.lock && rm -rf vendor && git checkout composer.json');
}

echo implode(PHP_EOL . PHP_EOL, $issues);

exit($code);
