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
 * @since         3.0.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Test\TestCase\Database\Schema;

use Cake\Cache\Cache;
use Cake\Database\Connection;
use Cake\Database\Exception\DatabaseException;
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
    protected $connection;

    /**
     * @var array<string>
     */
    protected $fixtures = [
        'core.Users',
    ];

    /**
     * Setup function
     */
    public function setUp(): void
    {
        parent::setUp();
        $this->connection = ConnectionManager::get('test');
        Cache::clear('_cake_model_');
        Cache::enable();
    }

    /**
     * Teardown function
     */
    public function tearDown(): void
    {
        $this->connection->cacheMetadata(false);
        parent::tearDown();
        unset($this->connection);
    }

    /**
     * Test that describing nonexistent tables fails.
     *
     * Tests for positive describe() calls are in each platformSchema
     * test case.
     */
    public function testDescribeIncorrectTable(): void
    {
        $this->expectException(DatabaseException::class);
        $schema = new Collection($this->connection);
        $this->assertNull($schema->describe('derp'));
    }

    /**
     * Tests that schema metadata is cached
     */
    public function testDescribeCache(): void
    {
        $this->connection->cacheMetadata('_cake_model_');
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

    /**
     * @doesNotPerformAssertions
     */
    public function testListTables()
    {
        $config = $this->connection->config();
        $driver = new $config['driver']($config);
        $connection = new Connection([
            'driver' => $driver,
        ]);

        $collection = new Collection($connection);
        $collection->listTables();
    }
}
