<?php
declare(strict_types=1);

/**
 * ApcuEngineTest file
 *
 * CakePHP(tm) : Rapid Development Framework (https://cakephp.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 * @link          https://cakephp.org CakePHP(tm) Project
 * @since         3.5.4
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Test\TestCase\Cache\Engine;

use Cake\Cache\Cache;
use Cake\TestSuite\TestCase;
use DateInterval;

/**
 * ApcuEngineTest class
 */
class ApcuEngineTest extends TestCase
{
    /**
     * useRequestTime original value
     *
     * @var string
     */
    protected static $useRequestTime;

    /**
     * Ensure use_request_time is turned off
     *
     * If use_request_time is on, all cache entries are inserted with the same
     * timestamp and ttl comparisons within the same request are effectively
     * meaningless
     */
    public static function setUpBeforeClass(): void
    {
        static::$useRequestTime = ini_get('apc.use_request_time');
        ini_set('apc.use_request_time', '0');
    }

    /**
     * Reset apc.user_request_time to original value
     */
    public static function teardownAfterClass(): void
    {
        ini_set('apc.use_request_time', static::$useRequestTime ? '1' : '0');
    }

    /**
     * setUp method
     */
    public function setUp(): void
    {
        parent::setUp();
        $this->skipIf(!function_exists('apcu_store'), 'APCu is not installed or configured properly.');

        if (PHP_SAPI === 'cli' || PHP_SAPI === 'phpdbg') {
            $this->skipIf(!ini_get('apc.enable_cli'), 'APCu is not enabled for the CLI.');
        }

        Cache::enable();
        $this->_configCache();
        Cache::clearAll();
    }

    /**
     * tearDown method
     */
    public function tearDown(): void
    {
        parent::tearDown();
        Cache::drop('apcu');
        Cache::drop('apcu_groups');
    }

    /**
     * Helper method for testing.
     *
     * @param array $config
     */
    protected function _configCache(array $config = []): void
    {
        $defaults = [
            'className' => 'Apcu',
            'prefix' => 'cake_',
            'warnOnWriteFailures' => true,
        ];
        Cache::drop('apcu');
        Cache::setConfig('apcu', array_merge($defaults, $config));
    }

    /**
     * testReadAndWriteCache method
     */
    public function testReadAndWriteCache(): void
    {
        $this->_configCache(['duration' => 1]);

        $result = Cache::read('test', 'apcu');
        $this->assertNull($result);

        $data = 'this is a test of the emergency broadcasting system';
        $result = Cache::write('test', $data, 'apcu');
        $this->assertTrue($result);

        $result = Cache::read('test', 'apcu');
        $expecting = $data;
        $this->assertSame($expecting, $result);

        Cache::delete('test', 'apcu');
    }

    /**
     * Writing cache entries with duration = 0 (forever) should work.
     */
    public function testReadWriteDurationZero(): void
    {
        Cache::drop('apcu');
        Cache::setConfig('apcu', ['engine' => 'Apcu', 'duration' => 0, 'prefix' => 'cake_']);
        Cache::write('zero', 'Should save', 'apcu');
        sleep(1);

        $result = Cache::read('zero', 'apcu');
        $this->assertSame('Should save', $result);
    }

    /**
     * Test get with default value
     */
    public function testGetDefaultValue(): void
    {
        $apcu = Cache::pool('apcu');
        $this->assertFalse($apcu->get('nope', false));
        $this->assertNull($apcu->get('nope', null));
        $this->assertTrue($apcu->get('nope', true));
        $this->assertSame(0, $apcu->get('nope', 0));

        $apcu->set('yep', 0);
        $this->assertSame(0, $apcu->get('yep', false));
    }

    /**
     * testExpiry method
     */
    public function testExpiry(): void
    {
        $this->_configCache(['duration' => 1]);

        $result = Cache::read('test', 'apcu');
        $this->assertNull($result);

        $data = 'this is a test of the emergency broadcasting system';
        $result = Cache::write('other_test', $data, 'apcu');
        $this->assertTrue($result);

        sleep(2);
        $result = Cache::read('other_test', 'apcu');
        $this->assertNull($result);
        $this->assertSame(0, Cache::pool('apcu')->get('other_test', 0), 'expired values get default.');
    }

    /**
     * test set ttl parameter
     */
    public function testSetWithTtl(): void
    {
        $this->_configCache(['duration' => 99]);
        $engine = Cache::pool('apcu');
        $this->assertNull($engine->get('test'));

        $data = 'this is a test of the emergency broadcasting system';
        $this->assertTrue($engine->set('default_ttl', $data));
        $this->assertTrue($engine->set('int_ttl', $data, 1));
        $this->assertTrue($engine->set('interval_ttl', $data, new DateInterval('PT1S')));

        sleep(2);
        $this->assertNull($engine->get('int_ttl'));
        $this->assertNull($engine->get('interval_ttl'));
        $this->assertSame($data, $engine->get('default_ttl'));
    }

    /**
     * testDeleteCache method
     */
    public function testDeleteCache(): void
    {
        $data = 'this is a test of the emergency broadcasting system';
        $result = Cache::write('delete_test', $data, 'apcu');
        $this->assertTrue($result);

        $result = Cache::delete('delete_test', 'apcu');
        $this->assertTrue($result);
    }

    /**
     * testDecrement method
     */
    public function testDecrement(): void
    {
        $result = Cache::write('test_decrement', 5, 'apcu');
        $this->assertTrue($result);

        $result = Cache::decrement('test_decrement', 1, 'apcu');
        $this->assertSame(4, $result);

        $result = Cache::read('test_decrement', 'apcu');
        $this->assertSame(4, $result);

        $result = Cache::decrement('test_decrement', 2, 'apcu');
        $this->assertSame(2, $result);

        $result = Cache::read('test_decrement', 'apcu');
        $this->assertSame(2, $result);
    }

    /**
     * testIncrement method
     */
    public function testIncrement(): void
    {
        $result = Cache::write('test_increment', 5, 'apcu');
        $this->assertTrue($result);

        $result = Cache::increment('test_increment', 1, 'apcu');
        $this->assertSame(6, $result);

        $result = Cache::read('test_increment', 'apcu');
        $this->assertSame(6, $result);

        $result = Cache::increment('test_increment', 2, 'apcu');
        $this->assertSame(8, $result);

        $result = Cache::read('test_increment', 'apcu');
        $this->assertSame(8, $result);
    }

    /**
     * test the clearing of cache keys
     */
    public function testClear(): void
    {
        apcu_store('not_cake', 'survive');
        Cache::write('some_value', 'value', 'apcu');

        $result = Cache::clear('apcu');
        $this->assertTrue($result);
        $this->assertNull(Cache::read('some_value', 'apcu'));
        $this->assertSame('survive', apcu_fetch('not_cake'));
        apcu_delete('not_cake');
    }

    /**
     * Tests that configuring groups for stored keys return the correct values when read/written
     * Shows that altering the group value is equivalent to deleting all keys under the same
     * group
     */
    public function testGroupsReadWrite(): void
    {
        Cache::setConfig('apcu_groups', [
            'engine' => 'Apcu',
            'duration' => 0,
            'groups' => ['group_a', 'group_b'],
            'prefix' => 'test_',
            'warnOnWriteFailures' => true,
        ]);
        $this->assertTrue(Cache::write('test_groups', 'value', 'apcu_groups'));
        $this->assertSame('value', Cache::read('test_groups', 'apcu_groups'));

        apcu_inc('test_group_a');
        $this->assertNull(Cache::read('test_groups', 'apcu_groups'));
        $this->assertTrue(Cache::write('test_groups', 'value2', 'apcu_groups'));
        $this->assertSame('value2', Cache::read('test_groups', 'apcu_groups'));

        apcu_inc('test_group_b');
        $this->assertNull(Cache::read('test_groups', 'apcu_groups'));
        $this->assertTrue(Cache::write('test_groups', 'value3', 'apcu_groups'));
        $this->assertSame('value3', Cache::read('test_groups', 'apcu_groups'));
    }

    /**
     * Tests that deleting from a groups-enabled config is possible
     */
    public function testGroupDelete(): void
    {
        Cache::setConfig('apcu_groups', [
            'engine' => 'Apcu',
            'duration' => 0,
            'groups' => ['group_a', 'group_b'],
            'prefix' => 'test_',
            'warnOnWriteFailures' => true,
        ]);
        $this->assertTrue(Cache::write('test_groups', 'value', 'apcu_groups'));
        $this->assertSame('value', Cache::read('test_groups', 'apcu_groups'));
        $this->assertTrue(Cache::delete('test_groups', 'apcu_groups'));

        $this->assertNull(Cache::read('test_groups', 'apcu_groups'));
    }

    /**
     * Test clearing a cache group
     */
    public function testGroupClear(): void
    {
        Cache::setConfig('apcu_groups', [
            'engine' => 'Apcu',
            'duration' => 0,
            'groups' => ['group_a', 'group_b'],
            'prefix' => 'test_',
            'warnOnWriteFailures' => true,
        ]);

        $this->assertTrue(Cache::write('test_groups', 'value', 'apcu_groups'));
        $this->assertTrue(Cache::clearGroup('group_a', 'apcu_groups'));
        $this->assertNull(Cache::read('test_groups', 'apcu_groups'));

        $this->assertTrue(Cache::write('test_groups', 'value2', 'apcu_groups'));
        $this->assertTrue(Cache::clearGroup('group_b', 'apcu_groups'));
        $this->assertNull(Cache::read('test_groups', 'apcu_groups'));
    }

    /**
     * Test add
     */
    public function testAdd(): void
    {
        Cache::delete('test_add_key', 'apcu');

        $result = Cache::add('test_add_key', 'test data', 'apcu');
        $this->assertTrue($result);

        $expected = 'test data';
        $result = Cache::read('test_add_key', 'apcu');
        $this->assertSame($expected, $result);

        $result = Cache::add('test_add_key', 'test data 2', 'apcu');
        $this->assertFalse($result);
    }
}
