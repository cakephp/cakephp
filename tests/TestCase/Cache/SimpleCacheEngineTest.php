<?php
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
 * @since         3.7.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Test\TestCase\Cache;

use Cake\Cache\Cache;
use Cake\Cache\Engine\FileEngine;
use Cake\Cache\SimpleCacheEngine;
use Cake\TestSuite\TestCase;

/**
 * SimpleCacheEngine class
 */
class SimpleCacheEngineTest extends TestCase
{
    public function setUp()
    {
        parent::setUp();

        $this->inner = new FileEngine();
        $this->inner->init([
            'prefix' => '',
            'path' => TMP . 'tests',
            'duration' => 5,
        ]);
        $this->cache = new SimpleCacheEngine($this->inner);
    }

    public function tearDown()
    {
        parent::tearDown();

        $this->inner->clear(false);
    }

    public function testGetSuccess()
    {
        $this->inner->write('key_one', 'Some Value');
        $this->assertSame('Some Value', $this->cache->get('key_one'));
        $this->assertSame('Some Value', $this->cache->get('key_one', 'default'));
    }

    public function testGetNoKey()
    {
        $this->assertSame('default', $this->cache->get('no', 'default'));
        $this->assertNull($this->cache->get('no'));
    }

    public function testGetInvalidKey()
    {
        $this->markTestIncomplete();
    }

    public function testSetNoTtl()
    {
        $this->assertTrue($this->cache->set('key', 'a value'));
        $this->assertSame('a value', $this->cache->get('key'));
    }

    public function testSetWithTtl()
    {
        $this->assertTrue($this->cache->set('key', 'a value'));
        $this->assertTrue($this->cache->set('expired', 'a value', 0));

        sleep(1);
        $this->assertSame('a value', $this->cache->get('key'));
        $this->assertNull($this->cache->get('expired'));
        $this->assertNotEquals(0, $this->inner->getConfig('duration'));
    }

    public function testSetInvalidKey()
    {
        $this->markTestIncomplete();
    }

    public function testDelete()
    {
        $this->cache->set('key', 'a value');
        $this->assertTrue($this->cache->delete('key'));
        $this->assertFalse($this->cache->delete('undefined'));
    }

    public function testDeleteInvalidKey()
    {
        $this->markTestIncomplete();
    }

    public function testClear()
    {
        $this->cache->set('key', 'a value');
        $this->cache->set('key2', 'other value');

        $this->assertTrue($this->cache->clear());
        $this->assertNull($this->cache->get('key'));
        $this->assertNull($this->cache->get('key2'));
    }

    public function testGetMultiple()
    {
        $this->cache->set('key', 'a value');
        $this->cache->set('key2', 'other value');

        $results = $this->cache->getMultiple(['key', 'key2', 'no']);
        $expected = [
            'key' => 'a value',
            'key2' => 'other value',
            'no' => null,
        ];
        $this->assertSame($expected, $results);
    }

    public function testGetMultipleDefault()
    {
        $this->cache->set('key', 'a value');
        $this->cache->set('key2', 'other value');

        $results = $this->cache->getMultiple(['key', 'key2', 'no'], 'default value');
        $expected = [
            'key' => 'a value',
            'key2' => 'other value',
            'no' => 'default value',
        ];
        $this->assertSame($expected, $results);
    }

    public function testSetMultiple()
    {
        $data = [
            'key' => 'a value',
            'key2' => 'other value'
        ];
        $this->cache->setMultiple($data);

        $results = $this->cache->getMultiple(array_keys($data));
        $this->assertSame($data, $results);
    }

    public function testSetMultipleWithTtl()
    {
        $data = [
            'key' => 'a value',
            'key2' => 'other value'
        ];
        $this->cache->setMultiple($data, 0);

        sleep(1);
        $results = $this->cache->getMultiple(array_keys($data));
        $this->assertNull($results['key']);
        $this->assertNull($results['key2']);
        $this->assertGreaterThan(1, $this->inner->getConfig('duration'));
    }
}
