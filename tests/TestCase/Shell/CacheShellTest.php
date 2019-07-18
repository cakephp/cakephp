<?php
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
namespace Cake\Test\TestCase\Shell;

use Cake\Cache\Cache;
use Cake\Console\Shell;
use Cake\TestSuite\ConsoleIntegrationTestCase;

/**
 * CacheShell tests.
 */
class CacheShellTest extends ConsoleIntegrationTestCase
{

    /**
     * setup method
     *
     * @return void
     */
    public function setUp()
    {
        parent::setUp();
        Cache::setConfig('test', ['engine' => 'File', 'path' => CACHE]);
    }

    /**
     * Teardown
     *
     * @return void
     */
    public function tearDown()
    {
        parent::tearDown();
        Cache::drop('test');
    }

    /**
     * Test that getOptionParser() returns an instance of \Cake\Console\ConsoleOptionParser
     *
     * @return void
     */
    public function testGetOptionParser()
    {
        $this->exec('cache -h');

        $this->assertExitCode(Shell::CODE_SUCCESS);
        $this->assertOutputContains('list_prefixes');
        $this->assertOutputContains('clear_all');
    }

    /**
     * Test that clear() throws \Cake\Console\Exception\StopException if cache prefix is invalid
     *
     * @return void
     */
    public function testClearInvalidPrefix()
    {
        $this->exec('cache clear foo');
        $this->assertExitCode(Shell::CODE_ERROR);
        $this->assertErrorContains('The "foo" cache configuration does not exist');
    }

    /**
     * Test that clear() clears the specified cache when a valid prefix is used
     *
     * @return void
     */
    public function testClearValidPrefix()
    {
        Cache::add('key', 'value', 'test');
        $this->exec('cache clear test');

        $this->assertExitCode(Shell::CODE_SUCCESS);
        $this->assertFalse(Cache::read('key', 'test'));
    }

    /**
     * Test that clear() only clears the specified cache
     *
     * @return void
     */
    public function testClearIgnoresOtherCaches()
    {
        Cache::add('key', 'value', 'test');
        $this->exec('cache clear _cake_core_');

        $this->assertExitCode(Shell::CODE_SUCCESS);
        $this->assertEquals('value', Cache::read('key', 'test'));
    }

    /**
     * Test that clearAll() clears values from all defined caches
     *
     * @return void
     */
    public function testClearAll()
    {
        Cache::add('key', 'value1', 'test');
        Cache::add('key', 'value3', '_cake_core_');
        $this->exec('cache clear_all');

        $this->assertExitCode(Shell::CODE_SUCCESS);
        $this->assertFalse(Cache::read('key', 'test'));
        $this->assertFalse(Cache::read('key', '_cake_core_'));
    }
}
