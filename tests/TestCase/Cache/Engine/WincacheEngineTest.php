<?php
declare(strict_types=1);

/**
 * CakePHP(tm) : Rapid Development Framework (https://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 * @link          https://cakephp.org CakePHP(tm) Project
 * @since         2.0.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Test\TestCase\Cache\Engine;

use Cake\Cache\Cache;
use Cake\TestSuite\TestCase;
use DateInterval;

/**
 * WincacheEngineTest class
 */
class WincacheEngineTest extends TestCase
{
    /**
     * setUp method
     *
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();
        $this->skipIf(!function_exists('wincache_ucache_set'), 'Wincache is not installed or configured properly.');
        $this->skipIf(!ini_get('wincache.enablecli'), 'Wincache is not enabled on the CLI.');
        Cache::enable();
        $this->_configCache();
    }

    /**
     * tearDown method
     *
     * @return void
     */
    public function tearDown(): void
    {
        parent::tearDown();
        Cache::drop('wincache');
        Cache::drop('wincache_groups');
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
            'className' => 'Wincache',
            'prefix' => 'cake_',
        ];
        Cache::drop('wincache');
        Cache::setConfig('wincache', array_merge($defaults, $config));
    }

    /**
     * testReadAndWriteCache method
     *
     * @return void
     */
    public function testReadAndWriteCache()
    {
        $this->_configCache(['duration' => 1]);

        $result = Cache::read('test', 'wincache');
        $expecting = '';
        $this->assertSame($expecting, $result);

        $data = 'this is a test of the emergency broadcasting system';
        $result = Cache::write('test', $data, 'wincache');
        $this->assertTrue($result);

        $result = Cache::read('test', 'wincache');
        $expecting = $data;
        $this->assertSame($expecting, $result);

        Cache::delete('test', 'wincache');
    }

    /**
     * Test get with default value
     *
     * @return void
     */
    public function testGetDefaultValue()
    {
        $wincache = Cache::pool('wincache');
        $this->assertFalse($wincache->get('nope', false));
        $this->assertNull($wincache->get('nope', null));
        $this->assertTrue($wincache->get('nope', true));
        $this->assertSame(0, $wincache->get('nope', 0));

        $wincache->set('yep', 0);
        $this->assertSame(0, $wincache->get('yep', false));
    }

    /**
     * testExpiry method
     *
     * @return void
     */
    public function testExpiry()
    {
        $this->_configCache(['duration' => 1]);

        $result = Cache::read('test', 'wincache');
        $this->assertNull($result);

        $data = 'this is a test of the emergency broadcasting system';
        $result = Cache::write('other_test', $data, 'wincache');
        $this->assertTrue($result);

        sleep(2);
        $result = Cache::read('other_test', 'wincache');
        $this->assertNull($result);

        $data = 'this is a test of the emergency broadcasting system';
        $result = Cache::write('other_test', $data, 'wincache');
        $this->assertTrue($result);

        sleep(2);
        $result = Cache::read('other_test', 'wincache');
        $this->assertNull($result);
    }

    /**
     * test set ttl parameter
     *
     * @return void
     */
    public function testSetWithTtl()
    {
        $this->_configCache(['duration' => 99]);
        $engine = Cache::pool('wincache');
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
     *
     * @return void
     */
    public function testDeleteCache()
    {
        $data = 'this is a test of the emergency broadcasting system';
        $result = Cache::write('delete_test', $data, 'wincache');
        $this->assertTrue($result);

        $result = Cache::delete('delete_test', 'wincache');
        $this->assertTrue($result);
    }

    /**
     * testDecrement method
     *
     * @return void
     */
    public function testDecrement()
    {
        $this->skipIf(
            !function_exists('wincache_ucache_dec'),
            'No wincache_ucache_dec() function, cannot test decrement().'
        );

        $result = Cache::write('test_decrement', 5, 'wincache');
        $this->assertTrue($result);

        $result = Cache::decrement('test_decrement', 1, 'wincache');
        $this->assertSame(4, $result);

        $result = Cache::read('test_decrement', 'wincache');
        $this->assertSame(4, $result);

        $result = Cache::decrement('test_decrement', 2, 'wincache');
        $this->assertSame(2, $result);

        $result = Cache::read('test_decrement', 'wincache');
        $this->assertSame(2, $result);
    }

    /**
     * testIncrement method
     *
     * @return void
     */
    public function testIncrement()
    {
        $this->skipIf(
            !function_exists('wincache_ucache_inc'),
            'No wincache_inc() function, cannot test increment().'
        );

        $result = Cache::write('test_increment', 5, 'wincache');
        $this->assertTrue($result);

        $result = Cache::increment('test_increment', 1, 'wincache');
        $this->assertSame(6, $result);

        $result = Cache::read('test_increment', 'wincache');
        $this->assertSame(6, $result);

        $result = Cache::increment('test_increment', 2, 'wincache');
        $this->assertSame(8, $result);

        $result = Cache::read('test_increment', 'wincache');
        $this->assertSame(8, $result);
    }

    /**
     * test the clearing of cache keys
     *
     * @return void
     */
    public function testClear()
    {
        wincache_ucache_set('not_cake', 'safe');
        Cache::write('some_value', 'value', 'wincache');

        $result = Cache::clear('wincache');
        $this->assertTrue($result);
        $this->assertNull(Cache::read('some_value', 'wincache'));
        $this->assertSame('safe', wincache_ucache_get('not_cake'));
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
        Cache::setConfig('wincache_groups', [
            'engine' => 'Wincache',
            'duration' => 0,
            'groups' => ['group_a', 'group_b'],
            'prefix' => 'test_',
        ]);
        $this->assertTrue(Cache::write('test_groups', 'value', 'wincache_groups'));
        $this->assertSame('value', Cache::read('test_groups', 'wincache_groups'));

        wincache_ucache_inc('test_group_a');
        $this->assertNull(Cache::read('test_groups', 'wincache_groups'));
        $this->assertTrue(Cache::write('test_groups', 'value2', 'wincache_groups'));
        $this->assertSame('value2', Cache::read('test_groups', 'wincache_groups'));

        wincache_ucache_inc('test_group_b');
        $this->assertNull(Cache::read('test_groups', 'wincache_groups'));
        $this->assertTrue(Cache::write('test_groups', 'value3', 'wincache_groups'));
        $this->assertSame('value3', Cache::read('test_groups', 'wincache_groups'));
    }

    /**
     * Tests that deleting from a groups-enabled config is possible
     *
     * @return void
     */
    public function testGroupDelete()
    {
        Cache::setConfig('wincache_groups', [
            'engine' => 'Wincache',
            'duration' => 0,
            'groups' => ['group_a', 'group_b'],
            'prefix' => 'test_',
        ]);
        $this->assertTrue(Cache::write('test_groups', 'value', 'wincache_groups'));
        $this->assertSame('value', Cache::read('test_groups', 'wincache_groups'));
        $this->assertTrue(Cache::delete('test_groups', 'wincache_groups'));

        $this->assertNull(Cache::read('test_groups', 'wincache_groups'));
    }

    /**
     * Test clearing a cache group
     *
     * @return void
     */
    public function testGroupClear()
    {
        Cache::setConfig('wincache_groups', [
            'engine' => 'Wincache',
            'duration' => 0,
            'groups' => ['group_a', 'group_b'],
            'prefix' => 'test_',
        ]);

        $this->assertTrue(Cache::write('test_groups', 'value', 'wincache_groups'));
        $this->assertTrue(Cache::clearGroup('group_a', 'wincache_groups'));
        $this->assertNull(Cache::read('test_groups', 'wincache_groups'));

        $this->assertTrue(Cache::write('test_groups', 'value2', 'wincache_groups'));
        $this->assertTrue(Cache::clearGroup('group_b', 'wincache_groups'));
        $this->assertNull(Cache::read('test_groups', 'wincache_groups'));
    }
}
