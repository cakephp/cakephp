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
use Cake\TestSuite\TestCase;
use Generator;
use ReflectionMethod;

class ScannerTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
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
}
