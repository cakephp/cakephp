<?php
declare(strict_types=1);

/**
 * CakePHP(tm) : Rapid Development Framework (https://cakephp.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 * @link          https://cakephp.org CakePHP(tm) Project
 * @since         3.7.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Test\TestCase\Cache\Engine;

use Cake\Cache\Cache;
use Cake\Cache\Engine\ArrayEngine;
use Cake\Cache\InvalidArgumentException;
use Cake\TestSuite\TestCase;

/**
 * ArrayEngineTest class
 */
class ArrayEngineTest extends TestCase
{
    /**
     * setUp method
     *
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();

        Cache::enable();
        $this->_configCache();
        Cache::clearAll();
    }

    /**
     * tearDown method
     *
     * @return void
     */
    public function tearDown(): void
    {
        parent::tearDown();
        Cache::drop('array');
        Cache::drop('array_groups');
    }

    /**
     * Helper method for testing.
     *
     * @param array $config
     * @return void
     */
    protected function _configCache($config = [])
    {
        $defaults = [
            'className' => 'Array',
            'prefix' => 'cake_',
            'warnOnWriteFailures' => true,
        ];
        Cache::drop('array');
        Cache::setConfig('array', array_merge($defaults, $config));
    }

    /**
     * testReadAndWriteCache method
     *
     * @return void
     */
    public function testReadAndWriteCache()
    {
        $this->_configCache(['duration' => 1]);

        $result = Cache::read('test', 'array');
        $this->assertNull($result);

        $data = 'this is a test of the emergency broadcasting system';
        $result = Cache::write('test', $data, 'array');
        $this->assertTrue($result);

        $result = Cache::read('test', 'array');
        $expecting = $data;
        $this->assertSame($expecting, $result);

        Cache::delete('test', 'array');
    }

    /**
     * testExpiry method
     *
     * @return void
     */
    public function testExpiry()
    {
        $this->_configCache(['duration' => 1]);

        $result = Cache::read('test', 'array');
        $this->assertNull($result);

        $data = 'this is a test of the emergency broadcasting system';
        $result = Cache::write('other_test', $data, 'array');
        $this->assertTrue($result);

        sleep(2);
        $result = Cache::read('other_test', 'array');
        $this->assertNull($result);
    }

    /**
     * testDeleteCache method
     *
     * @return void
     */
    public function testDeleteCache()
    {
        $data = 'this is a test of the emergency broadcasting system';
        $result = Cache::write('delete_test', $data, 'array');
        $this->assertTrue($result);

        $result = Cache::delete('delete_test', 'array');
        $this->assertTrue($result);
    }

    /**
     * testDecrement method
     *
     * @return void
     */
    public function testDecrement()
    {
        $result = Cache::write('test_decrement', 5, 'array');
        $this->assertTrue($result);

        $result = Cache::decrement('test_decrement', 1, 'array');
        $this->assertSame(4, $result);

        $result = Cache::read('test_decrement', 'array');
        $this->assertSame(4, $result);

        $result = Cache::decrement('test_decrement', 2, 'array');
        $this->assertSame(2, $result);

        $result = Cache::read('test_decrement', 'array');
        $this->assertSame(2, $result);
    }

    /**
     * testIncrement method
     *
     * @return void
     */
    public function testIncrement()
    {
        $result = Cache::write('test_increment', 5, 'array');
        $this->assertTrue($result);

        $result = Cache::increment('test_increment', 1, 'array');
        $this->assertSame(6, $result);

        $result = Cache::read('test_increment', 'array');
        $this->assertSame(6, $result);

        $result = Cache::increment('test_increment', 2, 'array');
        $this->assertSame(8, $result);

        $result = Cache::read('test_increment', 'array');
        $this->assertSame(8, $result);
    }

    /**
     * test the clearing of cache keys
     *
     * @return void
     */
    public function testClear()
    {
        Cache::write('some_value', 'value', 'array');

        $result = Cache::clear('array');
        $this->assertTrue($result);
        $this->assertNull(Cache::read('some_value', 'array'));
    }

    /**
     * Tests that configuring groups for stored keys return the correct values when read/written
     * Shows that altering the group value is equivalent to deleting all keys under the same
     * group
     *
     * @return void
     */
    public function testGroupsReadWrite()
    {
        Cache::setConfig('array_groups', [
            'engine' => 'array',
            'duration' => 30,
            'groups' => ['group_a', 'group_b'],
            'prefix' => 'test_',
            'warnOnWriteFailures' => true,
        ]);
        $this->assertTrue(Cache::write('test_groups', 'value', 'array_groups'));
        $this->assertSame('value', Cache::read('test_groups', 'array_groups'));

        Cache::clearGroup('group_a', 'array_groups');
        $this->assertNull(Cache::read('test_groups', 'array_groups'));
        $this->assertTrue(Cache::write('test_groups', 'value2', 'array_groups'));
        $this->assertSame('value2', Cache::read('test_groups', 'array_groups'));

        Cache::clearGroup('group_b', 'array_groups');
        $this->assertNull(Cache::read('test_groups', 'array_groups'));
        $this->assertTrue(Cache::write('test_groups', 'value3', 'array_groups'));
        $this->assertSame('value3', Cache::read('test_groups', 'array_groups'));
    }

    /**
     * Tests that deleting from a groups-enabled config is possible
     *
     * @return void
     */
    public function testGroupDelete()
    {
        Cache::setConfig('array_groups', [
            'engine' => 'array',
            'duration' => 10,
            'groups' => ['group_a', 'group_b'],
            'prefix' => 'test_',
            'warnOnWriteFailures' => true,
        ]);
        $this->assertTrue(Cache::write('test_groups', 'value', 'array_groups'));
        $this->assertSame('value', Cache::read('test_groups', 'array_groups'));

        $this->assertTrue(Cache::delete('test_groups', 'array_groups'));
        $this->assertNull(Cache::read('test_groups', 'array_groups'));
    }

    /**
     * Test clearing a cache group
     *
     * @return void
     */
    public function testGroupClear()
    {
        Cache::setConfig('array_groups', [
            'engine' => 'array',
            'duration' => 10,
            'groups' => ['group_a', 'group_b'],
            'prefix' => 'test_',
            'warnOnWriteFailures' => true,
        ]);

        $this->assertTrue(Cache::write('test_groups', 'value', 'array_groups'));
        $this->assertTrue(Cache::clearGroup('group_a', 'array_groups'));
        $this->assertNull(Cache::read('test_groups', 'array_groups'));

        $this->assertTrue(Cache::write('test_groups', 'value2', 'array_groups'));
        $this->assertTrue(Cache::clearGroup('group_b', 'array_groups'));
        $this->assertNull(Cache::read('test_groups', 'array_groups'));
    }

    /**
     * Test add
     *
     * @return void
     */
    public function testAdd()
    {
        Cache::delete('test_add_key', 'array');

        $result = Cache::add('test_add_key', 'test data', 'array');
        $this->assertTrue($result);

        $expected = 'test data';
        $result = Cache::read('test_add_key', 'array');
        $this->assertSame($expected, $result);

        $result = Cache::add('test_add_key', 'test data 2', 'array');
        $this->assertFalse($result);
    }

    /**
     * Test writeMany() with Traversable
     *
     * @return void
     */
    public function testWriteManyTraversable()
    {
        $data = new \ArrayObject([
            'a' => 1,
            'b' => 'foo',
        ]);

        $result = Cache::writeMany($data, 'array');
        $this->assertTrue($result);

        $this->assertSame(1, Cache::read('a', 'array'));
        $this->assertSame('foo', Cache::read('b', 'array'));
    }

    /**
     * Test that passing a non iterable argument to setMultiple() throws exception.
     *
     * @return void
     */
    public function testSetMultipleException()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('A cache set must be either an array or a Traversable.');

        $engine = new ArrayEngine();
        $engine->setMultiple('foo');
    }

    /**
     * Test that passing a non iterable argument to getMultiple() throws exception.
     *
     * @return void
     */
    public function testGetMultipleException()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('A cache key set must be either an array or a Traversable.');

        $engine = new ArrayEngine();
        $engine->getMultiple('foo');
    }

    /**
     * Test that passing a non iterable argument to deleteMultiple() throws exception.
     *
     * @return void
     */
    public function testDeleteMultipleException()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('A cache key set must be either an array or a Traversable.');

        $engine = new ArrayEngine();
        $engine->deleteMultiple('foo');
    }
}
