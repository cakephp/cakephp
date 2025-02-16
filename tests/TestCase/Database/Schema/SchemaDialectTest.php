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
 * @since         5.2.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Test\TestCase\Database\Schema;

use Cake\Database\Driver\Mysql;
use Cake\Database\Driver\Sqlite;
use Cake\Database\Exception\DatabaseException;
use Cake\Datasource\ConnectionManager;
use Cake\TestSuite\TestCase;
use TestApp\Database\Schema\CompatDialect;

/**
 * Test case for SchemaDialect methods
 * that can pass tests across all drivers
 */
class SchemaDialectTest extends TestCase
{
    /**
     * @var array<string>
     */
    protected array $fixtures = [
        'core.Products',
        'core.Users',
        'core.Orders',
    ];

    /**
     * @var \Cake\Database\Schema\SchemaDialect
     */
    protected $dialect;

    /**
     * Setup function
     */
    public function setUp(): void
    {
        parent::setUp();
        $this->dialect = ConnectionManager::get('test')->getDriver()->schemaDialect();
    }

    /**
     * Test that describing nonexistent tables fails.
     */
    public function testDescribeIncorrectTable(): void
    {
        $this->expectException(DatabaseException::class);
        $this->assertNull($this->dialect->describe('derp'));
    }

    public function testListTables(): void
    {
        $result = $this->dialect->listTables();
        $this->assertNotEmpty($result);
        $this->assertContains('users', $result);
    }

    public function testListTablesWithoutViews(): void
    {
        $result = $this->dialect->listTablesWithoutViews();
        $this->assertNotEmpty($result);
        $this->assertContains('users', $result);
    }

    public function testDescribe(): void
    {
        $result = $this->dialect->describe('users');
        $this->assertNotEmpty($result);
        $this->assertEquals('users', $result->name());
        $this->assertTrue($result->hasColumn('username'));
    }

    public function testDescribeColumns(): void
    {
        $result = $this->dialect->describeColumns('users');
        $this->assertCount(5, $result);
        foreach ($result as $column) {
            // Validate the interface for column array shape
            $this->assertArrayHasKey('name', $column);
            $this->assertTrue(is_string($column['name']));

            $this->assertArrayHasKey('type', $column);
            $this->assertTrue(is_string($column['type']));

            $this->assertArrayHasKey('length', $column);
            $this->assertTrue(is_int($column['length']) || $column['length'] === null);

            $this->assertArrayHasKey('default', $column);

            $this->assertArrayHasKey('null', $column);
            $this->assertTrue(is_bool($column['null']));

            $this->assertArrayHasKey('comment', $column);
            $this->assertTrue(is_string($column['comment']) || $column['comment'] === null);
        }
    }

    public function testDescribeIndexes(): void
    {
        $result = $this->dialect->describeIndexes('orders');
        // TODO(mark) this should be 2 once all dialects implement describeIndexes
        // This is the ideal place to return primary key indexes/constraints
        // as describeForeignKey and describeColumns are not good fits.
        // $this->assertCount(1, $result);
        foreach ($result as $index) {
            // Validate the interface for column array shape
            $this->assertArrayHasKey('name', $index);
            $this->assertTrue(is_string($index['name']));

            $this->assertArrayHasKey('type', $index);
            $this->assertTrue(is_string($index['type']));

            $this->assertArrayHasKey('length', $index);

            $this->assertArrayHasKey('columns', $index);
            $this->assertTrue(is_array($index['columns']));
        }
    }

    public function testDescribeForeignKeys(): void
    {
        $result = $this->dialect->describeForeignKeys('orders');
        $this->assertCount(1, $result);
        foreach ($result as $key) {
            // Validate the interface for column array shape
            $this->assertArrayHasKey('name', $key);
            $this->assertTrue(is_string($key['name']));

            $this->assertArrayHasKey('type', $key);
            $this->assertTrue(is_string($key['type']));

            $this->assertArrayHasKey('columns', $key);
            $this->assertTrue(is_array($key['columns']));

            $this->assertArrayHasKey('references', $key);
            $this->assertTrue(is_array($key['references']));

            $this->assertArrayHasKey('update', $key);
            $this->assertTrue(is_string($key['update']) || $key['update'] === null);
            $this->assertArrayHasKey('delete', $key);
            $this->assertTrue(is_string($key['delete']) || $key['delete'] === null);
        }
    }

    public function testDescribeOptions(): void
    {
        $result = $this->dialect->describeOptions('orders');
        $this->assertTrue(is_array($result));
    }

    public function testDescribeOptionsMysql(): void
    {
        $this->skipIf(!(ConnectionManager::get('test')->getDriver() instanceof Mysql), 'requires mysql');
        $result = $this->dialect->describeOptions('orders');
        $this->assertTrue(is_array($result));
        $this->assertArrayHasKey('engine', $result);
        $this->assertArrayHasKey('collation', $result);
    }

    public function testHasColumn(): void
    {
        $this->assertFalse($this->dialect->hasColumn('orders', 'nope'));
        $this->assertFalse($this->dialect->hasColumn('orders', ''));
        $this->assertFalse($this->dialect->hasColumn('invalid', 'also invalid'));

        $this->assertTrue($this->dialect->hasColumn('users', 'username'));
        $this->assertFalse($this->dialect->hasColumn('users', 'USERNAME'));
    }

    public function testHasTable(): void
    {
        $this->assertFalse($this->dialect->hasTable('nope'));
        $this->assertFalse($this->dialect->hasTable('USERS'));
        $this->assertFalse($this->dialect->hasTable('user'));
        $this->assertTrue($this->dialect->hasTable('users'));
    }

    public function testHasIndex(): void
    {
        $this->assertFalse($this->dialect->hasIndex('orders', ['product_category']));

        // Columns are reversed
        $this->assertFalse($this->dialect->hasIndex('orders', ['product_id', 'product_category']));

        // Name is wrong
        $this->assertFalse($this->dialect->hasIndex('orders', ['product_category', 'product_id'], 'product_category_index'));

        $this->assertTrue($this->dialect->hasIndex('orders', ['product_category', 'product_id']));
        $this->assertTrue($this->dialect->hasIndex('orders', ['product_category', 'product_id'], 'product_category'));
    }

    public function testHasForeignKey(): void
    {
        // Columns are missing and reversed
        $this->assertFalse($this->dialect->hasForeignKey('orders', ['product_category']));
        $this->assertFalse($this->dialect->hasForeignKey('orders', ['product_id', 'product_category']));

        $this->assertTrue($this->dialect->hasForeignKey('orders', ['product_category', 'product_id']));
    }

    public function testHasForeignKeyNamed(): void
    {
        // TODO this could be resolved if we use the key reflection logic from phinx/migrations
        // that logic parses the SQL of the key to extract and preserve the name.
        $driver = ConnectionManager::get('test')->getDriver();
        $this->skipIf($driver instanceof Sqlite, 'sqlite does not preserve foreign key names');
        $this->skipIf($driver instanceof Mysql, 'mysql tests fail when this runs');

        // Name is wrong
        $this->assertFalse($this->dialect->hasForeignKey('orders', ['product_category', 'product_id'], 'product_category_index'));

        $this->assertTrue($this->dialect->hasForeignKey('orders', ['product_category', 'product_id'], 'product_category_fk'));
        $this->assertTrue($this->dialect->hasForeignKey('orders', [], 'product_category_fk'));
    }

    /**
     * Test that SchemaDialect implementations without describeColumns etc
     * implemented still work with describe().
     */
    public function testBackwardsCompatibility(): void
    {
        /** @var \Cake\Database\Driver $driver */
        $driver = ConnectionManager::get('test')->getDriver();
        $this->skipIf(!($driver instanceof Sqlite), 'requires sqlite connection');
        $dialect = new CompatDialect($driver);
        $table = $dialect->describe('orders');

        $this->assertNotEmpty($table->columns());
        $this->assertNotEmpty($table->indexes());
        $this->assertNotEmpty($table->constraints());
    }
}
