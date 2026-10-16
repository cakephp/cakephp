#!/usr/bin/php -q
<?php
declare(strict_types=1);

/*
 * Validate split packages through PHPStan.
 */

$root = dirname(__DIR__);
$packages = [];
$manifests = [];
$versions = [];
foreach (glob($root . '/src/*/composer.json') ?: [] as $file) {
    $path = dirname($file) . '/';
    $contents = file_get_contents($file);
    if ($contents === false) {
        throw new RuntimeException(sprintf('Cannot read package manifest `%s`.', $file));
    }

    /** @var array{name: string, extra: array{'branch-alias': non-empty-array<string, string>}} $manifest */
    $manifest = json_decode($contents, true, flags: JSON_THROW_ON_ERROR);
    $packages[$path] = basename(dirname($file));
    $manifests[$path] = $contents;
    $versions[$manifest['name']] = array_first($manifest['extra']['branch-alias']);
}
ksort($packages);

$phivePharsXml = simplexml_load_file($root . '/.phive/phars.xml');
if ($phivePharsXml === false) {
    throw new RuntimeException('Cannot read the Phive tool configuration.');
}
$phpstanVersion = null;
foreach ($phivePharsXml->phar as $phar) {
    if ($phar->attributes()->name == 'phpstan') {
        $phpstanVersion = (string)$phar->attributes()->version;
        break;
    }
}
if ($phpstanVersion === null) {
    throw new RuntimeException('PHPStan is missing from the Phive tool configuration.');
}

// Resolve sibling packages from this checkout, including APIs not yet published.
$repository = json_encode([
    'type' => 'path',
    'url' => '../*',
    'options' => ['versions' => $versions, 'symlink' => false],
], JSON_THROW_ON_ERROR);
$composerCommand = 'composer config repositories.monorepo ' . escapeshellarg($repository)
    . ' && composer config minimum-stability dev'
    . ' && composer config prefer-stable false'
    . ' && composer require --dev ' . escapeshellarg('phpstan/phpstan:' . $phpstanVersion);

$issues = [];
$code = 0;
foreach ($packages as $path => $package) {
    if (!file_exists($path . 'phpstan.neon.dist')) {
        continue;
    }

    $output = [];
    $exitCode = null;
    $directory = escapeshellarg($path);
    try {
        exec(
            'cd ' . $directory . ' && ' . $composerCommand . ' && vendor/bin/phpstan analyze ./',
            $output,
            $exitCode,
        );
        if ($exitCode !== 0) {
            $code = $exitCode;
            $issues[] = $package . ': ' . PHP_EOL . implode(PHP_EOL, $output);
        }
    } finally {
        file_put_contents($path . 'composer.json', $manifests[$path]);
        exec('cd ' . $directory . ' && rm -f composer.lock && rm -rf vendor');
    }
}

echo implode(PHP_EOL . PHP_EOL, $issues);

exit($code);
