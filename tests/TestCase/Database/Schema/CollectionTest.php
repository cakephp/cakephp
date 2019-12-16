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
 * @since         3.0.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Test\TestCase\Database\Schema;

use Cake\Cache\Cache;
use Cake\Database\Schema\Collection;
use Cake\Datasource\ConnectionManager;
use Cake\TestSuite\TestCase;

/**
 * Test case for Collection
 */
class CollectionTest extends TestCase
{
    /**
     * @var \Cake\Database\Connection
     */
    public $connection;

    /**
     * @var array
     */
    public $fixtures = [
        'core.Users',
    ];

    /**
     * Setup function
     *
     * @return void
     */
    public function setUp()
    {
        parent::setUp();
        $this->connection = ConnectionManager::get('test');
        Cache::clear(false, '_cake_model_');
        Cache::enable();
    }

    /**
     * Teardown function
     *
     * @return void
     */
    public function tearDown()
    {
        parent::tearDown();
        unset($this->connection);
    }

    /**
     * Test that describing non-existent tables fails.
     *
     * Tests for positive describe() calls are in each platformSchema
     * test case.
     *
     * @return void
     */
    public function testDescribeIncorrectTable()
    {
        $this->expectException(\Cake\Database\Exception::class);
        $schema = new Collection($this->connection);
        $this->assertNull($schema->describe('derp'));
    }

    /**
     * Tests that schema metadata is cached
     *
     * @return void
     */
    public function testDescribeCache()
    {
        $schema = $this->connection->getSchemaCollection();
        $table = $schema->describe('users');

        Cache::delete('test_users', '_cake_model_');
        $this->connection->cacheMetadata(true);
        $schema = $this->connection->getSchemaCollection();

        $result = $schema->describe('users');
        $this->assertEquals($table, $result);

        $result = Cache::read('test_users', '_cake_model_');
        $this->assertEquals($table, $result);
    }
}
