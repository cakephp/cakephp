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

use Cake\Database\Exception\DatabaseException;
use Cake\Datasource\ConnectionManager;
use Cake\TestSuite\TestCase;

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
        'core.Users',
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
     *
     * Tests for positive describe() calls are in each platformSchema
     * test case.
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
}
