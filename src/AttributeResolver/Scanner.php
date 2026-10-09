<?php
declare(strict_types=1);

/**
 * CakePHP(tm) : Rapid Development Framework (https://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 * @link          https://cakephp.org CakePHP(tm) Project
 * @since         6.0.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\AttributeResolver;

use AppendIterator;
use Cake\Core\Configure;
use Cake\Core\Plugin;
use Cake\Core\PluginConfig;
use Cake\Utility\Fs\Finder;
use Cake\Utility\Fs\Iterator\CallbackFilterIterator;
use Cake\Utility\Fs\Iterator\GlobFilterIterator;
use Cake\Utility\Fs\Path;
use EmptyIterator;
use Generator;
use Iterator;
use SplFileInfo;
use Throwable;

/**
 * Scan configured application and plugin paths for PHP attributes.
 */
class Scanner
{
    /**
     * Maximum file size in bytes (10MB).
     */
    protected const int MAX_FILE_SIZE = 10 * 1024 * 1024;

    /**
     * Cache for base paths with plugin information.
     *
     * @var array<array{path: string, plugin: string|null}>|null
     */
    private ?array $basePaths = null;

    /**
     * Files that were scanned, keyed by their canonical paths.
     *
     * @var array<string, string>
     */
    private array $scannedFiles = [];

    /**
     * Configure attribute parsing and source paths.
     *
     * @param \Cake\AttributeResolver\Parser $parser Attribute parser
     * @param array<string> $paths Relative glob patterns to scan (e.g., ['src/**\/*.php'])
     * @param array<string> $excludePaths Directory names or path filters to exclude
     * @param string|null $basePath Base directory path (defaults to ROOT + all plugins)
     */
    public function __construct(
        private Parser $parser,
        private array $paths = [],
        private array $excludePaths = [],
        private ?string $basePath = null,
    ) {
    }

    /**
     * Scan all configured paths and yield discovered attributes.
     *
     * Expands relative paths against APP root and all loaded plugin paths.
     *
     * @return \Generator<\Cake\AttributeResolver\ValueObject\AttributeInfo>
     */
    public function scanAll(): Generator
    {
        $this->scannedFiles = [];
        $finder = $this->buildFinder();

        foreach ($finder as $file) {
            $filePath = $file->getRealPath();
            if ($filePath === false || isset($this->scannedFiles[$filePath])) {
                continue;
            }
            $this->scannedFiles[$filePath] = $filePath;

            try {
                $pluginName = $this->identifyPluginName($filePath);
                yield from $this->parser->parseFile($file, $pluginName);
            } catch (Throwable) {
                // Skip files that fail to parse
                continue;
            }
        }
    }

    /**
     * Get the list of files that were scanned.
     *
     * @return array<string>
     */
    public function getScannedFiles(): array
    {
        return array_values($this->scannedFiles);
    }

    /**
     * Resolve canonical base paths, merging aliases while preserving plugin information.
     *
     * @return array<array{path: string, plugin: string|null}>
     */
    protected function resolveBasePaths(): array
    {
        if ($this->basePaths !== null) {
            return $this->basePaths;
        }

        // Use custom basePath or default to ROOT
        $basePaths = [
            ['path' => $this->basePath ?? ROOT, 'plugin' => null],
        ];

        foreach ($this->getLoadedPlugins() as $pluginInfo) {
            $basePaths[] = $pluginInfo;
        }

        $unique = [];
        foreach ($basePaths as $baseInfo) {
            $path = realpath($baseInfo['path']) ?: $baseInfo['path'];
            if (DIRECTORY_SEPARATOR === '\\') {
                // Windows can retain short directory names in an absolute symlink target.
                $path = realpath($path) ?: $path;
            }
            $path = Path::normalize($path, true);
            if (!isset($unique[$path]) || $unique[$path]['plugin'] === null) {
                $unique[$path] = ['path' => $path, 'plugin' => $baseInfo['plugin']];
            }
        }

        $this->basePaths = array_values($unique);

        return $this->basePaths;
    }

    /**
     * Get loaded plugins that should be scanned.
     *
     * Returns a consistent list of all loaded plugins regardless of execution context
     * (CLI vs web). This ensures attribute discovery is atomic and cache is consistent.
     *
     * Excludes only:
     * - Unknown plugins (configured but not installed)
     * - Debug-only plugins when debug mode is disabled
     *
     * Includes CLI-only plugins even in web context to maintain cache consistency.
     * Also includes plugins loaded dynamically via the Plugin class
     * that may not be in the static configuration.
     *
     * @return array<array{path: string, plugin: string}>
     */
    protected function getLoadedPlugins(): array
    {
        $installedPlugins = PluginConfig::getInstalledPlugins();
        $debugMode = Configure::read('debug', false);
        $result = [];

        // Process plugins from PluginConfig
        foreach ($installedPlugins as $pluginName => $config) {
            // Skip plugins that shouldn't be included
            if (
                ($config['isUnknown'] ?? false) ||
                (($config['onlyDebug'] ?? false) && !$debugMode) ||
                !isset($config['path'])
            ) {
                continue;
            }

            $result[$pluginName] = [
                'path' => $config['path'],
                'plugin' => $pluginName,
            ];
        }

        // Merge dynamically loaded plugins not in PluginConfig
        $collection = Plugin::getCollection();
        foreach ($collection as $plugin) {
            $pluginName = $plugin->getName();
            if (!isset($result[$pluginName])) {
                $result[$pluginName] = [
                    'path' => $plugin->getPath(),
                    'plugin' => $pluginName,
                ];
            }
        }

        return array_values($result);
    }

    /**
     * Build file iterators, narrowing source-only scans while retaining root-relative globs.
     *
     * @return \Iterator<\SplFileInfo>
     */
    protected function buildFinder(): Iterator
    {
        if ($this->paths === []) {
            return new EmptyIterator();
        }

        $sourceOnly = array_all(
            $this->paths,
            static fn(string $path): bool => str_starts_with(Path::normalize($path), 'src/'),
        );
        if ($sourceOnly && in_array('src', $this->excludePaths, true)) {
            return new EmptyIterator();
        }

        $append = new AppendIterator();
        foreach ($this->resolveBasePaths() as $baseInfo) {
            $directory = $sourceOnly ? Path::join($baseInfo['path'], 'src') : $baseInfo['path'];
            if (!is_dir($directory)) {
                continue;
            }

            $finder = new Finder()->in($directory);
            foreach ($this->excludePaths as $excludePattern) {
                $finder->exclude($excludePattern);
                $finder->notPath($excludePattern);
            }

            $files = new GlobFilterIterator($finder->files(), $this->paths, $baseInfo['path']);
            $append->append(new CallbackFilterIterator(
                $files,
                static fn(SplFileInfo $file): bool => $file->getSize() <= self::MAX_FILE_SIZE,
                $baseInfo['path'],
            ));
        }

        return $append;
    }

    /**
     * Identify which plugin a file belongs to based on its path.
     *
     * @param string $filePath Absolute file path
     * @return string|null Plugin name or null if file is in APP
     */
    private function identifyPluginName(string $filePath): ?string
    {
        $filePath = str_replace('\\', '/', $filePath);
        $pluginName = null;
        $matchedLength = 0;
        foreach ($this->resolveBasePaths() as $baseInfo) {
            if ($baseInfo['plugin'] === null) {
                continue;
            }
            $length = strlen($baseInfo['path']);
            if ($length <= $matchedLength || !str_starts_with($filePath, $baseInfo['path'])) {
                continue;
            }
            $pluginName = $baseInfo['plugin'];
            $matchedLength = $length;
        }

        return $pluginName;
    }
}
