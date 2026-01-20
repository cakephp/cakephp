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
 * @since         5.2.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Test\TestCase\Command;

use Cake\Attribute\Resolver;
use Cake\Cache\Cache;
use Cake\Console\CommandInterface;
use Cake\Console\TestSuite\ConsoleIntegrationTestTrait;
use Cake\TestSuite\TestCase;
use Cake\Utility\Filesystem;

/**
 * AttributesResolveCommandTest class
 */
class AttributesResolveCommandTest extends TestCase
{
    use ConsoleIntegrationTestTrait;

    protected string $cacheConfig = '_cake_attributes_test_';

    /**
     * setUp method
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->setAppNamespace();

        Cache::setConfig($this->cacheConfig, [
            'className' => 'File',
            'path' => TMP . 'tests' . DS . 'attributes_cache' . DS,
            'serialize' => true,
        ]);

        Resolver::setConfig('default', [
            'paths' => [
                'Attribute/Resolver/Fixture/*.php',
            ],
            'basePath' => APP,
            'excludePaths' => [],
            'excludeAttributes' => [],
            'cache' => $this->cacheConfig,
            'validateFiles' => false,
        ]);
    }

    /**
     * tearDown method
     */
    protected function tearDown(): void
    {
        parent::tearDown();
        Resolver::drop('default');
        Cache::clear($this->cacheConfig);
        Cache::drop($this->cacheConfig);
    }

    /**
     * Test defaultName
     */
    public function testDefaultName(): void
    {
        $this->exec('attributes resolve --help');
        $this->assertExitCode(CommandInterface::CODE_SUCCESS);
        $this->assertOutputContains('attributes resolve');
    }

    /**
     * Test getDescription
     */
    public function testGetDescription(): void
    {
        $this->exec('attributes resolve --help');
        $this->assertExitCode(CommandInterface::CODE_SUCCESS);
        $this->assertOutputContains('Resolve');
    }

    /**
     * Test help output
     */
    public function testHelp(): void
    {
        $this->exec('attributes resolve --help');
        $this->assertExitCode(CommandInterface::CODE_SUCCESS);
        $this->assertOutputContains('attributes resolve');
        $this->assertOutputContains('no-clear');
        $this->assertOutputContains('clear-only');
    }

    /**
     * Test basic execution
     */
    public function testBasicExecution(): void
    {
        $this->exec('attributes resolve');
        $this->assertExitCode(CommandInterface::CODE_SUCCESS);
    }

    /**
     * Test outputs resolve message
     */
    public function testOutputsResolveMessage(): void
    {
        $this->exec('attributes resolve');
        $this->assertOutputContains('Resolving attributes');
    }

    /**
     * Test outputs clear message
     */
    public function testOutputsClearMessage(): void
    {
        $this->exec('attributes resolve');
        $this->assertOutputContains('Clearing');
    }

    /**
     * Test outputs success with count
     */
    public function testOutputsSuccessWithCount(): void
    {
        $this->exec('attributes resolve');
        $this->assertOutputContains('Resolved');
        $this->assertOutputContains('attributes');
    }

    /**
     * Test outputs elapsed time
     */
    public function testOutputsElapsedTime(): void
    {
        $this->exec('attributes resolve');
        $this->assertOutputRegExp('/\d+\.?\d*s/');
    }

    /**
     * Test returns success code
     */
    public function testReturnsSuccessCode(): void
    {
        $this->exec('attributes resolve');
        $this->assertExitCode(CommandInterface::CODE_SUCCESS);
    }

    /**
     * Test creates cache entry
     */
    public function testCreatesCacheEntry(): void
    {
        $this->exec('attributes resolve');
        $cached = Cache::read('attribute_resolver_default', $this->cacheConfig);
        $this->assertNotNull($cached);
    }

    /**
     * Test cache contains attributes
     */
    public function testCacheContainsAttributes(): void
    {
        $this->exec('attributes resolve');

        $cached = Cache::read('attribute_resolver_default', $this->cacheConfig);
        $this->assertIsArray($cached);
        $this->assertNotEmpty($cached);
    }

    /**
     * Test no-clear option
     */
    public function testNoClearOption(): void
    {
        $this->exec('attributes resolve --no-clear');
        $this->assertOutputNotContains('Clearing');
    }

    /**
     * Test no-clear preserves existing cache
     */
    public function testNoClearPreservesExistingCache(): void
    {
        // First resolve to create cache
        $this->exec('attributes resolve');
        $original = Cache::read('attribute_resolver_default', $this->cacheConfig);
        $this->assertNotNull($original);

        // Resolve again with --no-clear
        $this->exec('attributes resolve --no-clear');
        $new = Cache::read('attribute_resolver_default', $this->cacheConfig);

        // Cache should still exist
        $this->assertNotNull($new);
    }

    /**
     * Test clear-only option
     */
    public function testClearOnlyOption(): void
    {
        // Create cache first
        $this->exec('attributes resolve');
        $this->assertNotNull(Cache::read('attribute_resolver_default', $this->cacheConfig));

        // Clear only
        $this->exec('attributes resolve --clear-only');
        $this->assertOutputContains('Clearing');
        $this->assertOutputNotContains('Resolving');
    }

    /**
     * Test clear-only no resolve message
     */
    public function testClearOnlyNoResolveMessage(): void
    {
        $this->exec('attributes resolve --clear-only');
        $this->assertOutputNotContains('Resolved');
    }

    /**
     * Test clear-only removes cache
     */
    public function testClearOnlyRemovesCache(): void
    {
        // Create cache
        $this->exec('attributes resolve');
        $this->assertNotNull(Cache::read('attribute_resolver_default', $this->cacheConfig));

        // Clear only
        $this->exec('attributes resolve --clear-only');

        // Cache should be removed
        $this->assertNull(Cache::read('attribute_resolver_default', $this->cacheConfig));
    }

    /**
     * Test warning when cache disabled
     */
    public function testWarningWhenCacheDisabled(): void
    {
        Resolver::setConfig('disabled', [
            'paths' => ['Attribute/Resolver/Fixture/*.php'],
            'basePath' => APP,
            'cache' => false,
        ]);

        $this->exec('attributes resolve --config disabled');
        $this->assertErrorContains('disabled');

        Resolver::drop('disabled');
    }

    /**
     * Test uses default config
     */
    public function testUsesDefaultConfig(): void
    {
        $this->exec('attributes resolve');
        $this->assertExitCode(CommandInterface::CODE_SUCCESS);
        $this->assertNotNull(Cache::read('attribute_resolver_default', $this->cacheConfig));
    }

    /**
     * Test config option
     */
    public function testConfigOption(): void
    {
        $customCacheConfig = '_cake_attributes_custom_test_';
        Cache::setConfig($customCacheConfig, [
            'className' => 'File',
            'path' => TMP . 'tests' . DS . 'custom_cache' . DS,
            'serialize' => true,
        ]);

        Resolver::setConfig('custom', [
            'paths' => ['Attribute/Resolver/Fixture/*.php'],
            'basePath' => APP,
            'cache' => $customCacheConfig,
        ]);

        $this->exec('attributes resolve --config custom');
        $this->assertExitCode(CommandInterface::CODE_SUCCESS);
        $this->assertNotNull(Cache::read('attribute_resolver_custom', $customCacheConfig));

        Resolver::drop('custom');
        Cache::clear($customCacheConfig);
        Cache::drop($customCacheConfig);
    }

    /**
     * Test invalid config shows error
     */
    public function testInvalidConfigShowsError(): void
    {
        $this->exec('attributes resolve --config nonexistent');
        $this->assertExitCode(CommandInterface::CODE_ERROR);
        $this->assertErrorContains('Configuration');
    }

    /**
     * Test empty results shows zero count
     */
    public function testEmptyResultsShowsZeroCount(): void
    {
        $emptyDir = TMP . 'empty_test_' . uniqid() . DS;
        mkdir($emptyDir, 0777, true);

        $emptyCacheConfig = '_cake_attributes_empty_test_';
        Cache::setConfig($emptyCacheConfig, [
            'className' => 'File',
            'path' => $emptyDir . 'cache' . DS,
            'serialize' => true,
        ]);

        Resolver::setConfig('empty', [
            'paths' => ['*.php'],
            'basePath' => $emptyDir,
            'cache' => $emptyCacheConfig,
        ]);

        $this->exec('attributes resolve --config empty');
        $this->assertOutputContains('Resolved 0 attributes');

        Resolver::drop('empty');
        Cache::clear($emptyCacheConfig);
        Cache::drop($emptyCacheConfig);
        $fs = new Filesystem();
        $fs->deleteDir($emptyDir);
    }

    /**
     * Test option parser has all options
     */
    public function testOptionParserHasAllOptions(): void
    {
        $this->exec('attributes resolve --help');
        $this->assertOutputContains('no-clear');
        $this->assertOutputContains('clear-only');
        $this->assertOutputContains('config');
    }
}
