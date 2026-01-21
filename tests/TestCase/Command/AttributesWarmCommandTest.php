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
namespace Cake\Test\TestCase\Command;

use Cake\Attribute\Resolver;
use Cake\Cache\Cache;
use Cake\Console\CommandInterface;
use Cake\Console\TestSuite\ConsoleIntegrationTestTrait;
use Cake\TestSuite\TestCase;
use Cake\Utility\Filesystem;

/**
 * AttributesWarmCommandTest class
 */
class AttributesWarmCommandTest extends TestCase
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
        $this->exec('attributes warm --help');
        $this->assertExitCode(CommandInterface::CODE_SUCCESS);
        $this->assertOutputContains('attributes warm');
    }

    /**
     * Test getDescription
     */
    public function testGetDescription(): void
    {
        $this->exec('attributes warm --help');
        $this->assertExitCode(CommandInterface::CODE_SUCCESS);
        $this->assertOutputContains('Warm');
    }

    /**
     * Test help output
     */
    public function testHelp(): void
    {
        $this->exec('attributes warm --help');
        $this->assertExitCode(CommandInterface::CODE_SUCCESS);
        $this->assertOutputContains('attributes warm');
        $this->assertOutputContains('config');
    }

    /**
     * Test basic execution
     */
    public function testBasicExecution(): void
    {
        $this->exec('attributes warm');
        $this->assertExitCode(CommandInterface::CODE_SUCCESS);
    }

    /**
     * Test outputs warming message
     */
    public function testOutputsWarmingMessage(): void
    {
        $this->exec('attributes warm');
        $this->assertOutputContains('Warming attribute cache');
    }

    /**
     * Test outputs success with count
     */
    public function testOutputsSuccessWithCount(): void
    {
        $this->exec('attributes warm');
        $this->assertOutputContains('Cached');
        $this->assertOutputContains('attributes');
    }

    /**
     * Test outputs elapsed time
     */
    public function testOutputsElapsedTime(): void
    {
        $this->exec('attributes warm');
        $this->assertOutputRegExp('/\d+\.?\d*s/');
    }

    /**
     * Test returns success code
     */
    public function testReturnsSuccessCode(): void
    {
        $this->exec('attributes warm');
        $this->assertExitCode(CommandInterface::CODE_SUCCESS);
    }

    /**
     * Test creates cache entry
     */
    public function testCreatesCacheEntry(): void
    {
        $this->exec('attributes warm');
        $cached = Cache::read('attribute_resolver_default', $this->cacheConfig);
        $this->assertNotNull($cached);
    }

    /**
     * Test cache contains attributes
     */
    public function testCacheContainsAttributes(): void
    {
        $this->exec('attributes warm');

        $cached = Cache::read('attribute_resolver_default', $this->cacheConfig);
        $this->assertIsArray($cached);
        $this->assertNotEmpty($cached);
    }

    /**
     * Test error when cache disabled
     */
    public function testErrorWhenCacheDisabled(): void
    {
        Resolver::setConfig('disabled', [
            'paths' => ['Attribute/Resolver/Fixture/*.php'],
            'basePath' => APP,
            'cache' => false,
        ]);

        $this->exec('attributes warm --config disabled');
        $this->assertExitCode(CommandInterface::CODE_ERROR);
        $this->assertErrorContains('disabled');

        Resolver::drop('disabled');
    }

    /**
     * Test uses default config
     */
    public function testUsesDefaultConfig(): void
    {
        $this->exec('attributes warm');
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

        $this->exec('attributes warm --config custom');
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
        $this->exec('attributes warm --config nonexistent');
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

        $this->exec('attributes warm --config empty');
        $this->assertOutputContains('Cached 0 attributes');

        Resolver::drop('empty');
        Cache::clear($emptyCacheConfig);
        Cache::drop($emptyCacheConfig);
        $fs = new Filesystem();
        $fs->deleteDir($emptyDir);
    }

    /**
     * Test option parser has config option
     */
    public function testOptionParserHasConfigOption(): void
    {
        $this->exec('attributes warm --help');
        $this->assertOutputContains('config');
    }
}
