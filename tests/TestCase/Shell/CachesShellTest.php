<?php
/**
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @since         3.3.0
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Test\TestCase\Shell;

use Cake\Cache\Cache;
use Cake\Shell\CachesShell;
use Cake\TestSuite\TestCase;

/**
 * CachesShell tests.
 */
class CachesShellTest extends TestCase
{

    /**
     * setup method
     *
     * @return void
     */
    public function setUp()
    {
        parent::setUp();
        $this->io = $this->getMock('Cake\Console\ConsoleIo');
        $this->shell = new CachesShell($this->io);
        Cache::config('test', ['engine' => 'File', 'path' => TMP]);
    }

    /**
     * Teardown
     *
     * @return void
     */
    public function tearDown()
    {
        parent::tearDown();
        unset($this->io);
        unset($this->shell);
        Cache::drop('test');
    }

    /**
     * Test that getOptionParser() returns an instance of \Cake\Console\ConsoleOptionParser
     *
     * @return void
     */
    public function testGetOptionParser()
    {
        $this->assertInstanceOf('Cake\Console\ConsoleOptionParser', $this->shell->getOptionParser());
    }

    /**
     * Test that clear() throws \Cake\Console\Exception\StopException if cache prefix is invalid
     *
     * @return void
     */
    public function testClearInvalidPrefix()
    {
        $this->setExpectedException('Cake\Console\Exception\StopException');
        $this->shell->clear('foo');
    }

    /**
     * Test that clear() clears the specified cache when a valid prefix is used
     *
     * @return void
     */
    public function testClearValidPrefix()
    {
        Cache::add('key', 'value', 'test');
        $this->shell->clear('test');
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
        $this->shell->clear('_cake_core_');
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
        $this->shell->clearAll();
        $this->assertFalse(Cache::read('key', 'test'));
        $this->assertFalse(Cache::read('key', '_cake_core_'));
    }
}
