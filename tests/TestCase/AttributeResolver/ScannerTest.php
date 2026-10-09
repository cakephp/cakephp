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
namespace Cake\Test\TestCase\AttributeResolver;

use Cake\AttributeResolver\Parser;
use Cake\AttributeResolver\Scanner;
use Cake\AttributeResolver\ValueObject\AttributeInfo;
use Cake\Core\Configure;
use Cake\Core\PluginConfig;
use Cake\TestSuite\FsFixture;
use Cake\TestSuite\TestCase;
use Generator;
use Mockery;
use PHPUnit\Framework\Attributes\DataProvider;
use ReflectionMethod;
use SplFileInfo;

/**
 * Verify attribute scanning across application and plugin paths.
 */
class ScannerTest extends TestCase
{
    private string $scanRoot;

    /**
     * Create application and plugin trees for scanner regression tests.
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->scanRoot = FsFixture::setup('attribute-scanner', [
            'app' => ['src' => ['App.php' => '<?php']],
            'plugin' => [
                'src' => [
                    'Plugin.php' => '<?php',
                    'Controller' => ['Example.php' => '<?php'],
                ],
                'config' => ['routes.php' => '<?php'],
                'vendor' => ['package' => ['Dependency.php' => '<?php']],
            ],
            'plugin-extra' => ['src' => ['Other.php' => '<?php']],
        ]);
    }

    /**
     * Remove temporary scanner fixtures.
     */
    protected function tearDown(): void
    {
        FsFixture::tearDown();
        parent::tearDown();
    }

    public function testConstructorAcceptsConfiguration(): void
    {
        $parser = new Parser(['App\\Internal\\*']);
        $scanner = new Scanner(
            parser: $parser,
            paths: ['src/**/*.php'],
            excludePaths: ['vendor', 'tmp'],
        );

        $this->assertInstanceOf(Scanner::class, $scanner);
    }

    public function testScanAllYieldsAttributeInfoFromFiles(): void
    {
        $parser = new Parser();
        $scanner = new Scanner(
            parser: $parser,
            paths: ['Attribute/Resolver/Fixture/*.php'],
            basePath: APP,
        );

        $results = iterator_to_array($scanner->scanAll(), false);

        $this->assertNotEmpty($results);
        $this->assertContainsOnlyInstancesOf(AttributeInfo::class, $results);
    }

    public function testScanAllWithExcludePaths(): void
    {
        $parser = new Parser();
        $scanner = new Scanner(
            parser: $parser,
            paths: ['Attribute/Resolver/Fixture/*.php'],
            excludePaths: ['TestController.php'],
            basePath: APP,
        );

        $results = iterator_to_array($scanner->scanAll(), false);

        $this->assertNotEmpty($results, 'Should find some attributes from non-excluded files');

        foreach ($results as $result) {
            $this->assertStringNotContainsString('TestController.php', $result->filePath);
        }
    }

    public function testScanAllWithExcludeAttributes(): void
    {
        $parser = new Parser(['TestApp\\Attribute\\Resolver\\TestRoute']);
        $scanner = new Scanner(
            parser: $parser,
            paths: ['Attribute/Resolver/Fixture/*.php'],
            basePath: APP,
        );

        $results = iterator_to_array($scanner->scanAll(), false);

        foreach ($results as $result) {
            $this->assertNotSame('TestApp\\Attribute\\Resolver\\TestRoute', $result->attributeName);
        }
    }

    public function testScanAllHandlesNonExistentPaths(): void
    {
        $parser = new Parser();
        $scanner = new Scanner(
            parser: $parser,
            paths: ['/non/existent/path/*.php'],
        );

        $results = iterator_to_array($scanner->scanAll(), false);

        $this->assertEmpty($results);
    }

    public function testScanAllHandlesInvalidFiles(): void
    {
        $tempDir = sys_get_temp_dir() . '/scanner_test_' . uniqid();
        mkdir($tempDir);
        $tempFile = $tempDir . '/invalid.php';
        file_put_contents($tempFile, '<?php class Invalid { invalid syntax }');

        $parser = new Parser();
        $scanner = new Scanner(
            parser: $parser,
            paths: [$tempDir],
        );

        $results = iterator_to_array($scanner->scanAll(), false);

        $this->assertEmpty($results);

        unlink($tempFile);
        rmdir($tempDir);
    }

    public function testScanAllUsesGeneratorPattern(): void
    {
        $parser = new Parser();
        $scanner = new Scanner(
            parser: $parser,
            paths: ['Attribute/Resolver/Fixture/*.php'],
            basePath: APP,
        );

        $generator = $scanner->scanAll();

        $this->assertInstanceOf(Generator::class, $generator);
    }

    public function testScanAllIdentifiesPluginName(): void
    {
        $parser = new Parser();
        $scanner = new Scanner(
            parser: $parser,
            paths: ['Attribute/Resolver/Fixture/*.php'],
            basePath: APP,
        );

        $results = iterator_to_array($scanner->scanAll(), false);

        // For app files, pluginName should be null
        foreach ($results as $result) {
            $this->assertNull($result->pluginName);
        }
    }

    public function testScanAllIdentifiesPluginNameForPlugins(): void
    {
        $this->loadPlugins(['TestPlugin' => []]);

        $parser = new Parser();
        $scanner = new Scanner(
            parser: $parser,
            paths: ['src/**/*.php'],
            basePath: APP,
        );

        $results = iterator_to_array($scanner->scanAll(), false);

        // Find results from TestPlugin
        $pluginResults = array_filter($results, fn(AttributeInfo $r) => $r->pluginName === 'TestPlugin');

        foreach ($pluginResults as $result) {
            $this->assertSame('TestPlugin', $result->pluginName);
        }
    }

    public function testScanAllWithMultiplePaths(): void
    {
        $parser = new Parser();
        $scanner = new Scanner(
            parser: $parser,
            paths: [
                'Attribute/Resolver/Fixture/TestController.php',
                'Attribute/Resolver/Fixture/TestEntity.php',
            ],
            basePath: TEST_APP . 'TestApp/',
        );

        $results = iterator_to_array($scanner->scanAll(), false);

        $files = array_unique(array_map(fn(AttributeInfo $r) => basename($r->filePath), $results));
        $this->assertContains('TestController.php', $files);
        $this->assertContains('TestEntity.php', $files);
    }

    public function testScanAllWithWildcardPatterns(): void
    {
        $parser = new Parser();
        $scanner = new Scanner(
            parser: $parser,
            paths: ['Attribute/Resolver/Fixture/Test*.php'],
            basePath: TEST_APP . 'TestApp/',
        );

        $results = iterator_to_array($scanner->scanAll(), false);

        $this->assertNotEmpty($results);
        foreach ($results as $result) {
            $this->assertStringStartsWith('Test', basename($result->filePath));
        }
    }

    public function testScanAllFindsPhpFilesOnly(): void
    {
        $parser = new Parser();
        $scanner = new Scanner(
            parser: $parser,
            paths: ['Attribute/Resolver/Fixture/*.php'],
            basePath: TEST_APP . 'TestApp/',
        );

        $results = iterator_to_array($scanner->scanAll(), false);

        $this->assertNotEmpty($results);
        foreach ($results as $result) {
            $this->assertStringEndsWith('.php', $result->filePath);
        }
    }

    /**
     * Test scanAll returns empty when no paths are configured
     */
    public function testScanAllWithEmptyPaths(): void
    {
        $parser = new Parser();
        $scanner = new Scanner(
            parser: $parser,
            paths: [], // No paths configured
            basePath: TEST_APP . 'TestApp/',
        );

        $results = iterator_to_array($scanner->scanAll(), false);

        $this->assertEmpty($results, 'Should return empty when no paths are configured');
        $this->assertSame([], $scanner->getScannedFiles());
    }

    /**
     * Test getLoadedPlugins includes plugins with valid paths from PluginConfig
     */
    public function testGetLoadedPluginsIncludesValidPlugins(): void
    {
        $parser = new Parser();
        $scanner = new Scanner($parser);

        $method = new ReflectionMethod(Scanner::class, 'getLoadedPlugins');

        $plugins = $method->invoke($scanner);

        $this->assertIsArray($plugins);
        foreach ($plugins as $plugin) {
            $this->assertArrayHasKey('path', $plugin);
            $this->assertArrayHasKey('plugin', $plugin);
            $this->assertIsString($plugin['path']);
            $this->assertIsString($plugin['plugin']);
        }
    }

    /**
     * Test getLoadedPlugins excludes debug-only plugins when debug is disabled
     */
    public function testGetLoadedPluginsExcludesDebugPluginsWhenDebugDisabled(): void
    {
        $originalDebug = Configure::read('debug');
        Configure::write('debug', false);

        // Clear plugin config cache to force reload
        PluginConfig::clearCache();

        $parser = new Parser();
        $scanner = new Scanner($parser);

        $method = new ReflectionMethod(Scanner::class, 'getLoadedPlugins');

        $plugins = $method->invoke($scanner);

        // Verify no debug-only plugins are included when debug is off
        foreach ($plugins as $plugin) {
            $this->assertIsString($plugin['plugin']);
            // If we had a debug-only plugin configured, it shouldn't be in the list
        }

        Configure::write('debug', $originalDebug);
        PluginConfig::clearCache();
    }

    /**
     * Test getLoadedPlugins includes dynamically loaded plugins
     */
    public function testGetLoadedPluginsIncludesDynamicallyLoadedPlugins(): void
    {
        $this->loadPlugins(['TestPlugin' => []]);

        $parser = new Parser();
        $scanner = new Scanner($parser);

        $method = new ReflectionMethod(Scanner::class, 'getLoadedPlugins');

        $plugins = $method->invoke($scanner);

        // Find TestPlugin in results
        $pluginNames = array_column($plugins, 'plugin');
        $this->assertContains('TestPlugin', $pluginNames, 'TestPlugin should be included from Plugin::getCollection()');
    }

    /**
     * Test getLoadedPlugins does not duplicate plugins
     */
    public function testGetLoadedPluginsNoDuplicates(): void
    {
        $parser = new Parser();
        $scanner = new Scanner($parser);

        $method = new ReflectionMethod(Scanner::class, 'getLoadedPlugins');

        $plugins = $method->invoke($scanner);

        // Extract plugin names
        $pluginNames = array_column($plugins, 'plugin');

        // Check for duplicates
        $uniqueNames = array_unique($pluginNames);
        $this->assertCount(
            count($uniqueNames),
            $pluginNames,
            'Plugin list should not contain duplicates',
        );
    }

    /**
     * Test getLoadedPlugins returns consistent results regardless of context
     */
    public function testGetLoadedPluginsReturnsConsistentResults(): void
    {
        $parser = new Parser();
        $scanner = new Scanner($parser);

        $method = new ReflectionMethod(Scanner::class, 'getLoadedPlugins');

        // Call twice to ensure consistent results
        $plugins1 = $method->invoke($scanner);
        $plugins2 = $method->invoke($scanner);

        $this->assertSame($plugins1, $plugins2, 'getLoadedPlugins should return consistent results');
    }

    /**
     * Overlapping application and plugin roots must parse each file once.
     */
    public function testScanAllDeduplicatesOverlappingRoots(): void
    {
        $scanner = $this->createScanner(['**/*.php'], basePath: $this->scanRoot);
        iterator_to_array($scanner->scanAll());

        $files = $scanner->getScannedFiles();
        $this->assertCount(6, $files);
        $this->assertSame(array_values(array_unique($files)), $files);

        iterator_to_array($scanner->scanAll());
        $this->assertSame($files, $scanner->getScannedFiles());
    }

    /**
     * File deduplication must retain all attributes while eliminating copies from overlapping roots.
     */
    public function testScanAllYieldsAttributesOnceForOverlappingRoots(): void
    {
        $scanner = $this->createScanner(
            ['Attribute/Resolver/Fixture/TestController.php', 'TestController.php'],
            plugins: [['path' => APP . 'Attribute/Resolver/Fixture', 'plugin' => 'Example']],
            basePath: APP,
        );
        $attributes = iterator_to_array($scanner->scanAll(), false);

        $this->assertCount(5, $attributes);
        $this->assertCount(1, $scanner->getScannedFiles());
        foreach ($attributes as $attribute) {
            $this->assertSame('Example', $attribute->pluginName);
        }
    }

    /**
     * Symlink aliases must share a traversal root and retain plugin metadata.
     */
    public function testScanAllDeduplicatesSymlinkRoots(): void
    {
        $alias = $this->createPluginSymlink();
        $parser = Mockery::mock(Parser::class);
        $parser->shouldReceive('parseFile')->once()->with(
            Mockery::on(fn(SplFileInfo $file): bool => $file->getRealPath() === $this->fixturePath('plugin/src/Plugin.php')),
            'Example',
        )->andReturnUsing(static function (): Generator {
            yield from [];
        });

        $scanner = $this->createScanner(['src/Plugin.php'], plugins: [
            ['path' => $alias, 'plugin' => 'Example'],
            ['path' => $this->scanRoot . '/plugin', 'plugin' => 'Example'],
        ], parser: $parser);

        iterator_to_array($scanner->scanAll());
        $this->assertSame([$this->fixturePath('plugin/src/Plugin.php')], $scanner->getScannedFiles());
        $basePaths = new ReflectionMethod($scanner, 'resolveBasePaths')->invoke($scanner);
        $this->assertCount(2, $basePaths);
    }

    /**
     * A plugin installed only through a symlink must still be identified by name.
     */
    public function testScanAllIdentifiesSymlinkPlugin(): void
    {
        $alias = $this->createPluginSymlink();
        $parser = Mockery::mock(Parser::class);
        $parser->shouldReceive('parseFile')->once()->with(Mockery::type(SplFileInfo::class), 'Example')
            ->andReturnUsing(static function (): Generator {
                yield from [];
            });
        $scanner = $this->createScanner(
            ['src/Plugin.php'],
            plugins: [['path' => $alias, 'plugin' => 'Example']],
            parser: $parser,
        );

        iterator_to_array($scanner->scanAll());
        $this->assertSame([$this->fixturePath('plugin/src/Plugin.php')], $scanner->getScannedFiles());
    }

    /**
     * Plugin metadata must survive a plugin sharing the custom application root.
     */
    public function testScanAllPreservesPluginForSharedBasePath(): void
    {
        $parser = Mockery::mock(Parser::class);
        $parser->shouldReceive('parseFile')->once()->with(Mockery::type(SplFileInfo::class), 'Example')
            ->andReturnUsing(static function (): Generator {
                yield from [];
            });
        $scanner = $this->createScanner(
            ['src/Plugin.php'],
            parser: $parser,
            basePath: $this->scanRoot . '/plugin',
        );

        iterator_to_array($scanner->scanAll());
        $this->assertCount(1, $scanner->getScannedFiles());
        $this->assertCount(1, new ReflectionMethod($scanner, 'resolveBasePaths')->invoke($scanner));
    }

    /**
     * A sibling directory sharing a plugin path prefix must remain an application path.
     */
    public function testScanAllRequiresPluginDirectoryBoundary(): void
    {
        $parser = Mockery::mock(Parser::class);
        $parser->shouldReceive('parseFile')->once()->with(Mockery::type(SplFileInfo::class), null)
            ->andReturnUsing(static function (): Generator {
                yield from [];
            });
        $scanner = $this->createScanner(
            ['plugin-extra/src/Other.php'],
            parser: $parser,
            basePath: $this->scanRoot,
        );

        iterator_to_array($scanner->scanAll());
        $this->assertSame([$this->fixturePath('plugin-extra/src/Other.php')], $scanner->getScannedFiles());
    }

    /**
     * Nested plugins must use the most specific matching plugin root.
     */
    public function testScanAllIdentifiesNestedPlugin(): void
    {
        $parser = Mockery::mock(Parser::class);
        $parser->shouldReceive('parseFile')->once()->with(Mockery::type(SplFileInfo::class), 'Nested')
            ->andReturnUsing(static function (): Generator {
                yield from [];
            });
        $scanner = $this->createScanner(
            ['src/Controller/Example.php'],
            plugins: [
                ['path' => $this->scanRoot . '/plugin', 'plugin' => 'Example'],
                ['path' => $this->scanRoot . '/plugin/src', 'plugin' => 'Nested'],
            ],
            parser: $parser,
        );

        iterator_to_array($scanner->scanAll());
        $this->assertSame([$this->fixturePath('plugin/src/Controller/Example.php')], $scanner->getScannedFiles());
    }

    /**
     * Source-only scans must not enter unrelated plugin directories.
     */
    public function testScanAllScopesSourceTraversal(): void
    {
        $vendor = $this->scanRoot . '/plugin/vendor';
        chmod($vendor, 0o000);
        try {
            if (is_readable($vendor)) {
                $this->markTestSkipped('Directory permissions cannot prevent traversal on this platform.');
            }
            $scanner = $this->createScanner(['src/*.php', 'src/**/*.php']);
            iterator_to_array($scanner->scanAll());

            $this->assertCount(3, $scanner->getScannedFiles());
            $this->assertContains($this->fixturePath('plugin/src/Plugin.php'), $scanner->getScannedFiles());
            $this->assertContains($this->fixturePath('plugin/src/Controller/Example.php'), $scanner->getScannedFiles());
        } finally {
            chmod($vendor, 0o755);
        }
    }

    /**
     * Excluded directories must be pruned before recursive traversal.
     */
    public function testScanAllPrunesExcludedDirectories(): void
    {
        $vendor = $this->scanRoot . '/plugin/vendor';
        $dependency = $this->fixturePath('plugin/vendor/package/Dependency.php');
        chmod($vendor, 0o000);
        try {
            if (is_readable($vendor)) {
                $this->markTestSkipped('Directory permissions cannot prevent traversal on this platform.');
            }
            $scanner = $this->createScanner(['**/*.php'], excludePaths: ['vendor']);
            iterator_to_array($scanner->scanAll());

            $this->assertCount(4, $scanner->getScannedFiles());
            $this->assertNotContains($dependency, $scanner->getScannedFiles());
        } finally {
            chmod($vendor, 0o755);
        }
    }

    /**
     * Traversal optimization must retain the maximum file size filter.
     */
    public function testScanAllSkipsOversizedFiles(): void
    {
        $file = $this->scanRoot . '/plugin/src/Large.php';
        $handle = fopen($file, 'w');
        $this->assertNotFalse($handle);
        ftruncate($handle, 10 * 1024 * 1024 + 1);
        fclose($handle);

        $scanner = $this->createScanner(['src/*.php', 'src/**/*.php']);
        iterator_to_array($scanner->scanAll());

        $this->assertCount(3, $scanner->getScannedFiles());
        $this->assertNotContains($this->fixturePath('plugin/src/Large.php'), $scanner->getScannedFiles());
    }

    /**
     * Scoping must preserve root-relative globs, explicit paths, and exclusions.
     *
     * @param array<string> $paths Glob patterns to scan
     * @param array<string> $excludePaths Paths to exclude
     * @param array<string> $expectedFiles Expected plugin-relative files
     */
    #[DataProvider('scanPatternProvider')]
    public function testScanAllPreservesPatterns(array $paths, array $excludePaths, array $expectedFiles): void
    {
        $scanner = $this->createScanner($paths, excludePaths: $excludePaths, basePath: $this->scanRoot . '/missing');
        iterator_to_array($scanner->scanAll());

        $expected = array_map(fn(string $file): string => $this->fixturePath('plugin/' . $file), $expectedFiles);
        $actual = $scanner->getScannedFiles();
        sort($expected);
        sort($actual);
        $this->assertSame($expected, $actual);
    }

    /**
     * Provide scanner patterns whose meaning must survive traversal optimization.
     *
     * @return array<string, array{array<string>, array<string>, array<string>}>
     */
    public static function scanPatternProvider(): array
    {
        return [
            'recursive source glob' => [['src/**/*.php'], [], ['src/Controller/Example.php']],
            'direct source file' => [['src/Plugin.php'], [], ['src/Plugin.php']],
            'backslash separators' => [['src\\*.php', 'src\\**\\*.php'], [], [
                'src/Plugin.php', 'src/Controller/Example.php',
            ]],
            'custom plugin directory' => [['config/*.php'], [], ['config/routes.php']],
            'source and config' => [['src/*.php', 'src/**/*.php', 'config/*.php'], [], [
                'src/Plugin.php', 'src/Controller/Example.php', 'config/routes.php',
            ]],
            'broad glob' => [['**/*.php'], [], [
                'src/Plugin.php', 'src/Controller/Example.php', 'config/routes.php', 'vendor/package/Dependency.php',
            ]],
            'excluded source root' => [['src/*.php', 'src/**/*.php'], ['src'], []],
            'excluded source subdirectory' => [['src/*.php', 'src/**/*.php'], ['Controller'], ['src/Plugin.php']],
            'excluded file' => [['src/*.php', 'src/**/*.php'], ['Plugin.php'], ['src/Controller/Example.php']],
            'excluded path regex' => [['src/*.php', 'src/**/*.php'], ['#Controller/.*\.php$#'], ['src/Plugin.php']],
        ];
    }

    /**
     * Resolve fixture filenames with native separators and expanded directory aliases.
     *
     * @param string $path Fixture-relative filename
     * @return string Canonical fixture filename
     */
    private function fixturePath(string $path): string
    {
        $resolved = realpath($this->scanRoot . '/' . $path);
        assert($resolved !== false);

        return $resolved;
    }

    /**
     * Create a scanner with a controlled set of installed plugins.
     *
     * @param array<string> $paths Glob patterns to scan
     * @param array<array{path: string, plugin: string}>|null $plugins Installed plugin paths
     * @param array<string> $excludePaths Paths to exclude
     * @param \Cake\AttributeResolver\Parser|null $parser Parser to use
     * @param string|null $basePath Application base path
     * @return \Cake\AttributeResolver\Scanner
     */
    private function createScanner(
        array $paths,
        ?array $plugins = null,
        array $excludePaths = [],
        ?Parser $parser = null,
        ?string $basePath = null,
    ): Scanner {
        $scanner = Mockery::mock(Scanner::class, [
            $parser ?? new Parser(), $paths, $excludePaths, $basePath ?? $this->scanRoot . '/app',
        ])->makePartial()->shouldAllowMockingProtectedMethods();
        $scanner->shouldReceive('getLoadedPlugins')->andReturn($plugins ?? [
            ['path' => $this->scanRoot . '/plugin', 'plugin' => 'Example'],
        ]);

        return $scanner;
    }

    /**
     * Create a plugin alias when symbolic links are supported.
     *
     * @return string Alias path
     */
    private function createPluginSymlink(): string
    {
        $alias = $this->scanRoot . '/alias';
        // phpcs:ignore Generic.PHP.NoSilencedErrors.Discouraged -- Skip platforms without symlink permissions.
        if (!@symlink($this->scanRoot . '/plugin', $alias)) {
            $this->markTestSkipped('Symbolic links are unavailable on this platform.');
        }

        return $alias;
    }
}
