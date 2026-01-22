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
namespace Cake\Attribute\Resolver;

use Cake\Core\Configure;
use Cake\Core\Plugin;
use Cake\Core\PluginConfig;
use Cake\Utility\Fs\Finder;
use EmptyIterator;
use Generator;
use Iterator;
use Throwable;

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
     * List of files that were scanned.
     *
     * @var array<string>
     */
    private array $scannedFiles = [];

    /**
     * @param \Cake\Attribute\Resolver\Parser $parser Attribute parser
     * @param array<string> $paths Relative glob patterns to scan (e.g., ['src/**\/*.php'])
     * @param array<string> $excludePaths Relative patterns to exclude (e.g., ['vendor/**', 'tests/**'])
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
     * @return \Generator<\Cake\Attribute\Resolver\ValueObject\AttributeInfo>
     */
    public function scanAll(): Generator
    {
        $this->scannedFiles = [];
        $finder = $this->buildFinder();

        foreach ($finder as $file) {
            $filePath = $file->getRealPath();
            $this->scannedFiles[] = $filePath;

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
        return $this->scannedFiles;
    }

    /**
     * Resolve base paths with plugin information.
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

        $this->basePaths = $basePaths;

        return $basePaths;
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
     * Build a Finder instance for scanning files.
     *
     * @return \Iterator
     */
    protected function buildFinder(): Iterator
    {
        if ($this->paths === []) {
            return new EmptyIterator();
        }

        $basePaths = $this->resolveBasePaths();
        $directories = array_map(fn(array $info): string => $info['path'], $basePaths);

        $finder = new Finder();

        // Add all base directories
        foreach ($directories as $dir) {
            if (is_dir($dir)) {
                $finder->in($dir);
            }
        }

        // Apply relative path patterns
        foreach ($this->paths as $pattern) {
            $finder->pattern($pattern);
        }

        // Apply exclusions
        foreach ($this->excludePaths as $excludePattern) {
            $finder->notPath($excludePattern);
        }

        // Filter out files larger than 10MB
        $finder->filter(function ($file) {
            return $file->getSize() <= self::MAX_FILE_SIZE;
        });

        return $finder->files();
    }

    /**
     * Identify which plugin a file belongs to based on its path.
     *
     * @param string $filePath Absolute file path
     * @return string|null Plugin name or null if file is in APP
     */
    private function identifyPluginName(string $filePath): ?string
    {
        foreach ($this->resolveBasePaths() as $baseInfo) {
            if ($baseInfo['plugin'] !== null && str_starts_with($filePath, $baseInfo['path'])) {
                return $baseInfo['plugin'];
            }
        }

        return null;
    }
}
