<?php
declare(strict_types=1);

/**
 * CacheSessionTest
 *
 * CakePHP(tm) : Rapid Development Framework (https://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 * @link          https://cakephp.org CakePHP(tm) Project
 * @since         2.0.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Test\TestCase\Http\Session;

use Cake\Cache\Cache;
use Cake\Http\Session\CacheSession;
use Cake\TestSuite\TestCase;
use InvalidArgumentException;

/**
 * CacheSessionTest
 */
class CacheSessionTest extends TestCase
{
    protected static $_sessionBackup;

    /**
     * @var \Cake\Http\Session\CacheSession
     */
    protected $storage;

    /**
     * setup
     */
    public function setUp(): void
    {
        parent::setUp();
        Cache::setConfig(['session_test' => ['engine' => 'File']]);
        $this->storage = new CacheSession(['config' => 'session_test']);
    }

    /**
     * tearDown
     */
    public function tearDown(): void
    {
        parent::tearDown();
        Cache::clear('session_test');
        Cache::drop('session_test');
        unset($this->storage);
    }

    /**
     * test open
     */
    public function testOpen(): void
    {
        $this->assertTrue($this->storage->open(null, null));
    }

    /**
     * test write()
     */
    public function testWrite(): void
    {
        $this->storage->write('abc', 'Some value');
        $this->assertSame('Some value', Cache::read('abc', 'session_test'), 'Value was not written.');
    }

    /**
     * test reading.
     */
    public function testRead(): void
    {
        $this->storage->write('test_one', 'Some other value');
        $this->assertSame('Some other value', $this->storage->read('test_one'), 'Incorrect value.');
    }

    /**
     * test destroy
     */
    public function testDestroy(): void
    {
        $this->storage->write('test_one', 'Some other value');
        $this->assertTrue($this->storage->destroy('test_one'), 'Value was not deleted.');

        $this->assertNull(Cache::read('test_one', 'session_test'), 'Value stuck around.');
    }

    /**
     * Tests that a cache config is required
     */
    public function testMissingConfig(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The cache configuration name to use is required');
        new CacheSession(['foo' => 'bar']);
    }
}
