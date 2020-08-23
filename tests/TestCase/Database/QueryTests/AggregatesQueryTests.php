<?php
declare(strict_types=1);

/**
 * CakePHP(tm) : Rapid Development Framework (https://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 *
 * Licensed under The Open Group Test Suite License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 * @link          https://cakephp.org CakePHP(tm) Project
 * @since         4.1.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Test\TestCase\Database\QueryTests;

use Cake\Database\Query;
use Cake\Datasource\ConnectionManager;
use Cake\TestSuite\TestCase;

/**
 * Tests AggregateExpression queries class
 */
class AggregatesQueryTests extends TestCase
{
    protected $fixtures = [
        'core.Comments',
    ];

    public $autoFixtures = false;

    /**
     * @var \Cake\Database\Connection
     */
    protected $connection = null;

    /**
     * @var bool
     */
    protected $skipTests = false;

    public function setUp(): void
    {
        parent::setUp();
        $this->connection = ConnectionManager::get('test');
    }

    public function tearDown(): void
    {
        parent::tearDown();
    }

    /**
     * Tests filtering aggregate function rows.
     *
     * @return void
     */
    public function testFilters()
    {
        $skip = !($this->connection->getDriver() instanceof \Cake\Database\Driver\Postgres);
        if ($this->connection->getDriver() instanceof \Cake\Database\Driver\Sqlite) {
            $skip = version_compare($this->connection->getDriver()->version(), '3.30.0', '<');
        }
        $this->skipif($skip);
        $this->loadFixtures('Comments');

        $query = new Query($this->connection);
        $result = $query
            ->select(['num_rows' => $query->func()->count('*')->filter(['article_id' => 2])])
            ->from('comments')
            ->execute()
            ->fetchAll('assoc');
        $this->assertSame(2, $result[0]['num_rows']);
    }
}
