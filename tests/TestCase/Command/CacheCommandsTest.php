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
 * @since         3.3.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Test\TestCase\Command;

use Cake\Cache\Cache;
use Cake\Console\Shell;
use Cake\Console\TestSuite\ConsoleIntegrationTestTrait;
use Cake\TestSuite\TestCase;

/**
 * Cache Commands tests.
 */
class CacheCommandsTest extends TestCase
{
    use ConsoleIntegrationTestTrait;

    /**
     * setup method
     */
    public function setUp(): void
    {
        parent::setUp();
        Cache::setConfig('test', ['engine' => 'File', 'path' => CACHE]);
        $this->setAppNamespace();
        $this->useCommandRunner();
    }

    /**
     * Teardown
     */
    public function tearDown(): void
    {
        parent::tearDown();
        Cache::drop('test');
    }

    /**
     * Test help output
     */
    public function testClearHelp(): void
    {
        $this->exec('cache clear -h');

        $this->assertExitCode(Shell::CODE_SUCCESS);
        $this->assertOutputContains('engine to clear');
    }

    /**
     * Test help output
     */
    public function testClearAllHelp(): void
    {
        $this->exec('cache clear_all -h');

        $this->assertExitCode(Shell::CODE_SUCCESS);
        $this->assertOutputContains('Clear all');
    }

    /**
     * Test list output
     */
    public function testList(): void
    {
        $this->exec('cache list');

        $this->assertExitCode(Shell::CODE_SUCCESS);
        $this->assertOutputContains('- test');
        $this->assertOutputContains('- _cake_core_');
        $this->assertOutputContains('- _cake_model_');
    }

    /**
     * Test help output
     */
    public function testListHelp(): void
    {
        $this->exec('cache list -h');

        $this->assertExitCode(Shell::CODE_SUCCESS);
        $this->assertOutputContains('Show a list');
    }

    /**
     * Test that clear() throws \Cake\Console\Exception\StopException if cache prefix is invalid
     */
    public function testClearInvalidPrefix(): void
    {
        $this->exec('cache clear foo');
        $this->assertExitCode(Shell::CODE_ERROR);
        $this->assertErrorContains('The "foo" cache configuration does not exist');
    }

    /**
     * Test that clear() clears the specified cache when a valid prefix is used
     */
    public function testClearValidPrefix(): void
    {
        Cache::add('key', 'value', 'test');
        $this->exec('cache clear test');

        $this->assertExitCode(Shell::CODE_SUCCESS);
        $this->assertNull(Cache::read('key', 'test'));
    }

    /**
     * Test that clear() only clears the specified cache
     */
    public function testClearIgnoresOtherCaches(): void
    {
        Cache::add('key', 'value', 'test');
        $this->exec('cache clear _cake_core_');

        $this->assertExitCode(Shell::CODE_SUCCESS);
        $this->assertSame('value', Cache::read('key', 'test'));
    }

    /**
     * Test that clearAll() clears values from all defined caches
     */
    public function testClearAll(): void
    {
        Cache::add('key', 'value1', 'test');
        Cache::add('key', 'value3', '_cake_core_');
        $this->exec('cache clear_all');

        $this->assertExitCode(Shell::CODE_SUCCESS);
        $this->assertNull(Cache::read('key', 'test'));
        $this->assertNull(Cache::read('key', '_cake_core_'));
    }
}
