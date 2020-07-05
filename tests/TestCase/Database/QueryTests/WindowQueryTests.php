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

use Cake\Database\Expression\QueryExpression;
use Cake\Database\Expression\WindowExpression;
use Cake\Database\Query;
use Cake\Datasource\ConnectionManager;
use Cake\TestSuite\TestCase;
use RuntimeException;

/**
 * Tests WindowExpression class
 */
class WindowQueryTests extends TestCase
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
            $driver instanceof \Cake\Database\Driver\Mysql ||
            $driver instanceof \Cake\Database\Driver\Sqlite
        ) {
            $this->skipTests = !$this->connection->getDriver()->supportsWindowFunctions();
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
     *
     * @return void
     */
    public function testWindowSql()
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

    public function testMissingWindow()
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('You must return a `WindowExpression`');
        (new Query($this->connection))->window('name', function () {
            return new QueryExpression();
        });
    }

    public function testPartitions()
    {
        $this->skipIf($this->skipTests);
        $this->loadFixtures('Comments');

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
     *
     * @return void
     */
    public function testNamedWindow()
    {
        $skip = $this->skipTests;
        if (!$skip) {
            $skip = $this->connection->getDriver() instanceof \Cake\Database\Driver\Sqlserver;
        }
        $this->skipIf($skip);

        $this->loadFixtures('Comments');

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

    public function testWindowChaining()
    {
        $skip = $this->skipTests;
        if (!$skip) {
            $driver = $this->connection->getDriver();
            $skip = $driver instanceof \Cake\Database\Driver\Sqlserver;
            if ($driver instanceof \Cake\Database\Driver\Sqlite) {
                $skip = version_compare($driver->version(), '3.28.0', '<');
            }
        }
        $this->skipIf($skip);

        $this->loadFixtures('Comments');

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
