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

use Cake\Database\Driver\Mysql;
use Cake\Database\Driver\Sqlite;
use Cake\Database\Driver\Sqlserver;
use Cake\Database\DriverInterface;
use Cake\Database\Expression\QueryExpression;
use Cake\Database\Expression\WindowExpression;
use Cake\Database\Query;
use Cake\Datasource\ConnectionManager;
use Cake\TestSuite\TestCase;
use RuntimeException;

/**
 * Tests WindowExpression class
 */
class WindowQueryTest extends TestCase
{
    protected $fixtures = [
        'core.Comments',
    ];

    /**
     * @var \Cake\Database\Connection
     */
    protected $connection = null;

    /**
     * @var bool
     */
    protected $autoQuote;

    /**
     * @var bool
     */
    protected $skipTests = false;

    public function setUp(): void
    {
        parent::setUp();
        $this->connection = ConnectionManager::get('test');
        $this->autoQuote = $this->connection->getDriver()->isAutoQuotingEnabled();

        $driver = $this->connection->getDriver();
        if (
            $driver instanceof Mysql ||
            $driver instanceof Sqlite
        ) {
            $this->skipTests = !$this->connection->getDriver()->supports(DriverInterface::FEATURE_WINDOW);
        } else {
            $this->skipTests = false;
        }
    }

    public function tearDown(): void
    {
        parent::tearDown();
    }

    /**
     * Tests window sql generation.
     */
    public function testWindowSql(): void
    {
        $query = new Query($this->connection);
        $sql = $query
            ->select('*')
            ->window('name', new WindowExpression())
            ->sql();
        $this->assertRegExpSql('SELECT \* WINDOW <name> AS \(\)', $sql, !$this->autoQuote);

        $sql = $query
            ->window('name2', new WindowExpression('name'))
            ->sql();
        $this->assertRegExpSql('SELECT \* WINDOW <name> AS \(\), <name2> AS \(<name>\)', $sql, !$this->autoQuote);

        $sql = $query
            ->window('name', function ($window, $query) {
                return $window->name('name3');
            }, true)
            ->sql();
        $this->assertEqualsSql('SELECT * WINDOW name AS (name3)', $sql);
    }

    public function testMissingWindow(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('You must return a `WindowExpression`');
        (new Query($this->connection))->window('name', function () {
            return new QueryExpression();
        });
    }

    public function testPartitions(): void
    {
        $this->skipIf($this->skipTests);

        $query = new Query($this->connection);
        $result = $query
            ->select(['num_rows' => $query->func()->count('*')->over()])
            ->from('comments')
            ->execute()
            ->fetchAll();
        $this->assertCount(6, $result);

        $query = new Query($this->connection);
        $result = $query
            ->select(['num_rows' => $query->func()->count('*')->partition('article_id')])
            ->from('comments')
            ->order(['article_id'])
            ->execute()
            ->fetchAll('assoc');
        $this->assertEquals(4, $result[0]['num_rows']);

        $query = new Query($this->connection);
        $result = $query
            ->select(['num_rows' => $query->func()->count('*')->partition('article_id')->order('updated')])
            ->from('comments')
            ->order(['updated'])
            ->execute()
            ->fetchAll('assoc');
        $this->assertEquals(1, $result[0]['num_rows']);
        $this->assertEquals(4, $result[3]['num_rows']);
        $this->assertEquals(1, $result[4]['num_rows']);
    }

    /**
     * Tests adding named windows to the query.
     */
    public function testNamedWindow(): void
    {
        $skip = $this->skipTests;
        if (!$skip) {
            $skip = $this->connection->getDriver() instanceof Sqlserver;
        }
        $this->skipIf($skip);

        $query = new Query($this->connection);
        $result = $query
            ->select(['num_rows' => $query->func()->count('*')->over('window1')])
            ->from('comments')
            ->window('window1', (new WindowExpression())->partition('article_id'))
            ->order(['article_id'])
            ->execute()
            ->fetchAll('assoc');
        $this->assertEquals(4, $result[0]['num_rows']);
    }

    public function testWindowChaining(): void
    {
        $skip = $this->skipTests;
        if (!$skip) {
            $driver = $this->connection->getDriver();
            $skip = $driver instanceof Sqlserver;
            if ($driver instanceof Sqlite) {
                $skip = version_compare($driver->version(), '3.28.0', '<');
            }
        }
        $this->skipIf($skip);

        $query = new Query($this->connection);
        $result = $query
            ->select(['num_rows' => $query->func()->count('*')->over('window2')])
            ->from('comments')
            ->window('window1', (new WindowExpression())->partition('article_id'))
            ->window('window2', new WindowExpression('window1'))
            ->order(['article_id'])
            ->execute()
            ->fetchAll('assoc');
        $this->assertEquals(4, $result[0]['num_rows']);
    }
}
