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

namespace Cake\Test\TestCase\Database\Driver;

use Cake\Database\Driver\Sqlserver;
use Cake\Database\Exception\MissingConnectionException;
use Cake\Database\Query;
use Cake\Datasource\ConnectionManager;
use Cake\TestSuite\TestCase;
use InvalidArgumentException;
use PDO;

/**
 * Tests Sqlserver driver
 */
class SqlserverTest extends TestCase
{
    /**
     * @var bool
     */
    protected $missingExtension;

    /**
     * Set up
     *
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();
        $this->missingExtension = !defined('PDO::SQLSRV_ENCODING_UTF8');
    }

    /**
     * data provider for testDnsString
     *
     * @return array
     */
    public function dnsStringDataProvider()
    {
        return [
            [
                [
                    'app' => 'CakePHP-Testapp',
                    'encoding' => '',
                    'connectionPooling' => true,
                    'failoverPartner' => 'failover.local',
                    'loginTimeout' => 10,
                    'multiSubnetFailover' => 'failover.local',
                ],
                'sqlsrv:Server=localhost\SQLEXPRESS;Database=cake;MultipleActiveResultSets=false;APP=CakePHP-Testapp;ConnectionPooling=1;Failover_Partner=failover.local;LoginTimeout=10;MultiSubnetFailover=failover.local',
            ],
            [
                [
                    'app' => 'CakePHP-Testapp',
                    'encoding' => '',
                    'failoverPartner' => 'failover.local',
                    'multiSubnetFailover' => 'failover.local',
                ],
                'sqlsrv:Server=localhost\SQLEXPRESS;Database=cake;MultipleActiveResultSets=false;APP=CakePHP-Testapp;Failover_Partner=failover.local;MultiSubnetFailover=failover.local',
            ],
            [
                [
                    'encoding' => '',
                ],
                'sqlsrv:Server=localhost\SQLEXPRESS;Database=cake;MultipleActiveResultSets=false',
            ],
            [
                [
                    'app' => 'CakePHP-Testapp',
                    'encoding' => '',
                    'host' => 'localhost\SQLEXPRESS',
                    'port' => 9001,
                ],
                'sqlsrv:Server=localhost\SQLEXPRESS,9001;Database=cake;MultipleActiveResultSets=false;APP=CakePHP-Testapp',
            ],
        ];
    }

    /**
     * Test if all options in dns string are set
     *
     * @dataProvider dnsStringDataProvider
     * @param array $constructorArgs
     * @param string $dnsString
     * @return void
     */
    public function testDnsString($constructorArgs, $dnsString)
    {
        $driver = $this->getMockBuilder('Cake\Database\Driver\Sqlserver')
            ->onlyMethods(['_connect', 'getConnection'])
            ->setConstructorArgs([$constructorArgs])
            ->getMock();

        $driver->method('_connect')
            ->with($this->callback(function ($dns) use ($dnsString) {
                $this->assertSame($dns, $dnsString);

                return true;
            }))
            ->will($this->returnValue(true));

        $driver->method('getConnection')
            ->will($this->returnValue(null));

        $driver->connect();
    }

    /**
     * Test connecting to Sqlserver with custom configuration
     *
     * @return void
     */
    public function testConnectionConfigCustom()
    {
        $this->skipIf($this->missingExtension, 'pdo_sqlsrv is not installed.');
        $config = [
            'host' => 'foo',
            'username' => 'Administrator',
            'password' => 'blablabla',
            'database' => 'bar',
            'encoding' => 'a-language',
            'flags' => [1 => true, 2 => false],
            'init' => ['Execute this', 'this too'],
            'settings' => ['config1' => 'value1', 'config2' => 'value2'],
        ];
        $driver = $this->getMockBuilder('Cake\Database\Driver\Sqlserver')
            ->onlyMethods(['_connect', 'setConnection', 'getConnection'])
            ->setConstructorArgs([$config])
            ->getMock();
        $dsn = 'sqlsrv:Server=foo;Database=bar;MultipleActiveResultSets=false';

        $expected = $config;
        $expected['flags'] += [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::SQLSRV_ATTR_ENCODING => 'a-language',
        ];
        $expected['attributes'] = [];
        $expected['app'] = null;
        $expected['connectionPooling'] = null;
        $expected['failoverPartner'] = null;
        $expected['loginTimeout'] = null;
        $expected['multiSubnetFailover'] = null;
        $expected['port'] = null;

        $connection = $this->getMockBuilder('stdClass')
            ->addMethods(['exec', 'quote'])
            ->getMock();
        $connection->expects($this->any())
            ->method('quote')
            ->will($this->onConsecutiveCalls(
                $this->returnArgument(0),
                $this->returnArgument(0),
                $this->returnArgument(0)
            ));

        $connection->expects($this->exactly(4))
            ->method('exec')
            ->withConsecutive(
                ['Execute this'],
                ['this too'],
                ['SET config1 value1'],
                ['SET config2 value2']
            );

        $driver->setConnection($connection);
        $driver->expects($this->once())->method('_connect')
            ->with($dsn, $expected);

        $driver->expects($this->any())->method('getConnection')
            ->will($this->returnValue($connection));

        $driver->connect();
    }

    /**
     * Test connecting to Sqlserver with persistent set to false
     *
     * @return void
     */
    public function testConnectionPersistentFalse()
    {
        $this->skipIf($this->missingExtension, 'pdo_sqlsrv is not installed.');
        $config = [
            'persistent' => false,
            'host' => 'foo',
            'username' => 'Administrator',
            'password' => 'blablabla',
            'database' => 'bar',
            'encoding' => 'a-language',
        ];
        $driver = $this->getMockBuilder('Cake\Database\Driver\Sqlserver')
            ->onlyMethods(['_connect'])
            ->setConstructorArgs([$config])
            ->getMock();
        $dsn = 'sqlsrv:Server=foo;Database=bar;MultipleActiveResultSets=false';

        $expected = $config;
        $expected['flags'] = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::SQLSRV_ATTR_ENCODING => 'a-language',
        ];
        $expected['attributes'] = [];
        $expected['settings'] = [];
        $expected['init'] = [];
        $expected['app'] = null;
        $expected['connectionPooling'] = null;
        $expected['failoverPartner'] = null;
        $expected['loginTimeout'] = null;
        $expected['multiSubnetFailover'] = null;
        $expected['port'] = null;

        $driver->expects($this->once())->method('_connect')
            ->with($dsn, $expected);

        $this->expectException(MissingConnectionException::class);
        $driver->connect();
    }

    /**
     * Test if attempting to connect with the driver throws an exception when
     * using an invalid config setting.
     *
     * @return void
     */
    public function testConnectionPersistentTrueException()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Config setting "persistent" cannot be set to true, as the Sqlserver PDO driver does not support PDO::ATTR_PERSISTENT');
        $this->skipIf($this->missingExtension, 'pdo_sqlsrv is not installed.');
        $config = [
            'persistent' => true,
            'host' => 'foo',
            'username' => 'Administrator',
            'password' => 'blablabla',
            'database' => 'bar',
        ];
        $driver = $this->getMockBuilder('Cake\Database\Driver\Sqlserver')
            ->onlyMethods(['_connect', 'getConnection'])
            ->setConstructorArgs([$config])
            ->getMock();
        $driver->connect();
    }

    /**
     * Test select with limit only and SQLServer2012+
     *
     * @return void
     */
    public function testSelectLimitVersion12()
    {
        $driver = $this->getMockBuilder('Cake\Database\Driver\Sqlserver')
            ->onlyMethods(['_connect', 'getConnection', 'version'])
            ->setConstructorArgs([[]])
            ->getMock();
        $driver->method('version')
            ->will($this->returnValue('12'));

        $connection = $this->getMockBuilder('Cake\Database\Connection')
            ->onlyMethods(['connect', 'getDriver', 'setDriver'])
            ->setConstructorArgs([['log' => false]])
            ->getMock();
        $connection->method('getDriver')
            ->will($this->returnValue($driver));

        $query = new Query($connection);
        $query->select(['id', 'title'])
            ->from('articles')
            ->order(['id'])
            ->offset(10);
        $this->assertSame('SELECT id, title FROM articles ORDER BY id OFFSET 10 ROWS', $query->sql());

        $query = new Query($connection);
        $query->select(['id', 'title'])
            ->from('articles')
            ->order(['id'])
            ->limit(10)
            ->offset(50);
        $this->assertSame('SELECT id, title FROM articles ORDER BY id OFFSET 50 ROWS FETCH FIRST 10 ROWS ONLY', $query->sql());

        $query = new Query($connection);
        $query->select(['id', 'title'])
            ->from('articles')
            ->offset(10);
        $this->assertSame('SELECT id, title FROM articles ORDER BY (SELECT NULL) OFFSET 10 ROWS', $query->sql());

        $query = new Query($connection);
        $query->select(['id', 'title'])
            ->from('articles')
            ->limit(10);
        $this->assertSame('SELECT TOP 10 id, title FROM articles', $query->sql());
    }

    /**
     * Test select with limit on lte SQLServer2008
     *
     * @return void
     */
    public function testSelectLimitOldServer()
    {
        $driver = $this->getMockBuilder('Cake\Database\Driver\Sqlserver')
            ->onlyMethods(['_connect', 'getConnection', 'version'])
            ->setConstructorArgs([[]])
            ->getMock();
        $driver->expects($this->any())
            ->method('version')
            ->will($this->returnValue('8'));

        $connection = $this->getMockBuilder('Cake\Database\Connection')
            ->onlyMethods(['connect', 'getDriver', 'setDriver'])
            ->setConstructorArgs([['log' => false]])
            ->getMock();
        $connection->expects($this->any())
            ->method('getDriver')
            ->will($this->returnValue($driver));

        $query = new Query($connection);
        $query->select(['id', 'title'])
            ->from('articles')
            ->limit(10);
        $expected = 'SELECT TOP 10 id, title FROM articles';
        $this->assertSame($expected, $query->sql());

        $query = new Query($connection);
        $query->select(['id', 'title'])
            ->from('articles')
            ->offset(10);
        $identifier = '_cake_page_rownum_';
        if ($connection->getDriver()->isAutoQuotingEnabled()) {
            $identifier = $connection->getDriver()->quoteIdentifier($identifier);
        }
        $expected = 'SELECT * FROM (SELECT id, title, (ROW_NUMBER() OVER (ORDER BY (SELECT NULL))) AS ' . $identifier . ' ' .
            'FROM articles) _cake_paging_ ' .
            'WHERE _cake_paging_._cake_page_rownum_ > 10';
        $this->assertSame($expected, $query->sql());

        $query = new Query($connection);
        $query->select(['id', 'title'])
            ->from('articles')
            ->order(['id'])
            ->offset(10);
        $expected = 'SELECT * FROM (SELECT id, title, (ROW_NUMBER() OVER (ORDER BY id)) AS ' . $identifier . ' ' .
            'FROM articles) _cake_paging_ ' .
            'WHERE _cake_paging_._cake_page_rownum_ > 10';
        $this->assertSame($expected, $query->sql());

        $query = new Query($connection);
        $query->select(['id', 'title'])
            ->from('articles')
            ->order(['id'])
            ->where(['title' => 'Something'])
            ->limit(10)
            ->offset(50);
        $expected = 'SELECT * FROM (SELECT id, title, (ROW_NUMBER() OVER (ORDER BY id)) AS ' . $identifier . ' ' .
            'FROM articles WHERE title = :c0) _cake_paging_ ' .
            'WHERE (_cake_paging_._cake_page_rownum_ > 50 AND _cake_paging_._cake_page_rownum_ <= 60)';
        $this->assertSame($expected, $query->sql());

        $query = new Query($connection);
        $subquery = new Query($connection);
        $subquery->select(1);
        $query
            ->select([
                'id',
                'computed' => $subquery,
            ])
            ->from('articles')
            ->order([
                'computed' => 'ASC',
            ])
            ->offset(10);
        $expected =
            'SELECT * FROM (' .
                'SELECT id, (SELECT 1) AS computed, ' .
                '(ROW_NUMBER() OVER (ORDER BY (SELECT 1) ASC)) AS _cake_page_rownum_ FROM articles' .
            ') _cake_paging_ ' .
            'WHERE _cake_paging_._cake_page_rownum_ > 10';
        $this->assertSame($expected, $query->sql());

        $subqueryA = new Query($connection);
        $subqueryA
            ->select('count(*)')
            ->from(['a' => 'articles'])
            ->where([
                'a.id = articles.id',
                'a.published' => 'Y',
            ]);

        $subqueryB = new Query($connection);
        $subqueryB
            ->select('count(*)')
            ->from(['b' => 'articles'])
            ->where([
                'b.id = articles.id',
                'b.published' => 'N',
            ]);

        $query = new Query($connection);
        $query
            ->select([
                'id',
                'computedA' => $subqueryA,
                'computedB' => $subqueryB,
            ])
            ->from('articles')
            ->order([
                'computedA' => 'ASC',
            ])
            ->offset(10);

        $this->assertSame(
            'SELECT * FROM (' .
                'SELECT id, ' .
                '(SELECT count(*) FROM articles a WHERE (a.id = articles.id AND a.published = :c0)) AS computedA, ' .
                '(SELECT count(*) FROM articles b WHERE (b.id = articles.id AND b.published = :c1)) AS computedB, ' .
                '(ROW_NUMBER() OVER (ORDER BY (SELECT count(*) FROM articles a WHERE (a.id = articles.id AND a.published = :c2)) ASC)) AS _cake_page_rownum_ FROM articles' .
            ') _cake_paging_ ' .
            'WHERE _cake_paging_._cake_page_rownum_ > 10',
            $query->sql()
        );
    }

    /**
     * Test that insert queries have results available to them.
     *
     * @return void
     */
    public function testInsertUsesOutput()
    {
        $driver = $this->getMockBuilder('Cake\Database\Driver\Sqlserver')
            ->onlyMethods(['_connect', 'getConnection'])
            ->setConstructorArgs([[]])
            ->getMock();
        $connection = $this->getMockBuilder('Cake\Database\Connection')
            ->onlyMethods(['connect', 'getDriver', 'setDriver'])
            ->setConstructorArgs([['log' => false]])
            ->getMock();
        $connection->expects($this->any())
            ->method('getDriver')
            ->will($this->returnValue($driver));
        $query = new Query($connection);
        $query->insert(['title'])
            ->into('articles')
            ->values(['title' => 'A new article']);
        $expected = 'INSERT INTO articles (title) OUTPUT INSERTED.* VALUES (:c0)';
        $this->assertSame($expected, $query->sql());
    }

    /**
     * Test that having queries replace the aggregated alias field.
     *
     * @return void
     */
    public function testHavingReplacesAlias()
    {
        $driver = $this->getMockBuilder('Cake\Database\Driver\Sqlserver')
            ->onlyMethods(['connect', 'getConnection', 'version'])
            ->setConstructorArgs([[]])
            ->getMock();
        $driver->expects($this->any())
            ->method('version')
            ->will($this->returnValue('8'));

        $connection = $this->getMockBuilder('\Cake\Database\Connection')
            ->onlyMethods(['connect', 'getDriver', 'setDriver'])
            ->setConstructorArgs([['log' => false]])
            ->getMock();
        $connection->expects($this->any())
            ->method('getDriver')
            ->will($this->returnValue($driver));

        $query = new Query($connection);
        $query
            ->select([
                'posts.author_id',
                'post_count' => $query->func()->count('posts.id'),
            ])
            ->group(['posts.author_id'])
            ->having([$query->newExpr()->gte('post_count', 2, 'integer')]);

        $expected = 'SELECT posts.author_id, (COUNT(posts.id)) AS post_count ' .
            'GROUP BY posts.author_id HAVING COUNT(posts.id) >= :c0';
        $this->assertSame($expected, $query->sql());
    }

    /**
     * Test that having queries replaces nothing is no alias is used.
     *
     * @return void
     */
    public function testHavingWhenNoAliasIsUsed()
    {
        $driver = $this->getMockBuilder('Cake\Database\Driver\Sqlserver')
            ->onlyMethods(['connect', 'getConnection', 'version'])
            ->setConstructorArgs([[]])
            ->getMock();
        $driver->expects($this->any())
            ->method('version')
            ->will($this->returnValue('8'));

        $connection = $this->getMockBuilder('\Cake\Database\Connection')
            ->onlyMethods(['connect', 'getDriver', 'setDriver'])
            ->setConstructorArgs([['log' => false]])
            ->getMock();
        $connection->expects($this->any())
            ->method('getDriver')
            ->will($this->returnValue($driver));

        $query = new Query($connection);
        $query
            ->select([
                'posts.author_id',
                'post_count' => $query->func()->count('posts.id'),
            ])
            ->group(['posts.author_id'])
            ->having([$query->newExpr()->gte('posts.author_id', 2, 'integer')]);

        $expected = 'SELECT posts.author_id, (COUNT(posts.id)) AS post_count ' .
            'GROUP BY posts.author_id HAVING posts.author_id >= :c0';
        $this->assertSame($expected, $query->sql());
    }

    public function testExceedingMaxParameters()
    {
        $connection = ConnectionManager::get('test');
        $this->skipIf(!$connection->getDriver() instanceof Sqlserver);

        $query = $connection->newQuery()
            ->from('articles')
            ->whereInList('id', range(0, 2100));

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'Exceeded maximum number of parameters (2100) for prepared statements in Sql Server'
        );
        $connection->getDriver()->prepare($query);
    }
}
