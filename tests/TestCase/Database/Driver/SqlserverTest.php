<?php
/**
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @since         3.0.0
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Test\TestCase\Database\Driver;

use Cake\Core\Configure;
use Cake\Database\Connection;
use Cake\Database\Driver\Sqlserver;
use Cake\Database\Query;
use Cake\TestSuite\TestCase;
use \PDO;

/**
 * Tests Sqlserver driver
 */
class SqlserverTest extends TestCase
{

    /**
     * Set up
     *
     * @return void
     */
    public function setUp()
    {
        parent::setUp();
        $this->skipUnless(defined('PDO::SQLSRV_ENCODING_UTF8'), 'SQL Server extension not present');
    }

    /**
     * Test connecting to Sqlserver with custom configuration
     *
     * @return void
     */
    public function testConnectionConfigCustom()
    {
        $config = [
            'persistent' => false,
            'host' => 'foo',
            'username' => 'Administrator',
            'password' => 'blablabla',
            'database' => 'bar',
            'encoding' => 'a-language',
            'flags' => [1 => true, 2 => false],
            'init' => ['Execute this', 'this too'],
            'settings' => ['config1' => 'value1', 'config2' => 'value2'],
        ];
        $driver = $this->getMock(
            'Cake\Database\Driver\Sqlserver',
            ['_connect', 'connection'],
            [$config]
        );
        $dsn = 'sqlsrv:Server=foo;Database=bar;MultipleActiveResultSets=false';

        $expected = $config;
        $expected['flags'] += [
            PDO::ATTR_PERSISTENT => false,
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::SQLSRV_ATTR_ENCODING => 'a-language'
        ];

        $connection = $this->getMock('stdClass', ['exec', 'quote']);
        $connection->expects($this->any())
            ->method('quote')
            ->will($this->onConsecutiveCalls(
                $this->returnArgument(0),
                $this->returnArgument(0),
                $this->returnArgument(0)
            ));

        $connection->expects($this->at(0))->method('exec')->with('Execute this');
        $connection->expects($this->at(1))->method('exec')->with('this too');
        $connection->expects($this->at(2))->method('exec')->with('SET config1 value1');
        $connection->expects($this->at(3))->method('exec')->with('SET config2 value2');

        $driver->connection($connection);
        $driver->expects($this->once())->method('_connect')
            ->with($dsn, $expected);

        $driver->expects($this->any())->method('connection')
            ->will($this->returnValue($connection));

        $driver->connect();
    }

    /**
     * Test select with limit only and SQLServer2012+
     *
     * @return void
     */
    public function testSelectLimitVersion12()
    {
        $driver = $this->getMock(
            'Cake\Database\Driver\Sqlserver',
            ['_connect', 'connection', '_version'],
            [[]]
        );
        $driver
            ->expects($this->any())
            ->method('_version')
            ->will($this->returnValue(12));

        $connection = $this->getMock(
            '\Cake\Database\Connection',
            ['connect', 'driver'],
            [['log' => false]]
        );
        $connection
            ->expects($this->any())
            ->method('driver')
            ->will($this->returnValue($driver));

        $query = new \Cake\Database\Query($connection);
        $query->select(['id', 'title'])
            ->from('articles')
            ->order(['id'])
            ->offset(10);
        $this->assertEquals('SELECT id, title FROM articles ORDER BY id OFFSET 10 ROWS', $query->sql());

        $query = new \Cake\Database\Query($connection);
        $query->select(['id', 'title'])
            ->from('articles')
            ->order(['id'])
            ->limit(10)
            ->offset(50);
        $this->assertEquals('SELECT id, title FROM articles ORDER BY id OFFSET 50 ROWS FETCH FIRST 10 ROWS ONLY', $query->sql());

        $query = new \Cake\Database\Query($connection);
        $query->select(['id', 'title'])
            ->from('articles')
            ->offset(10);
        $this->assertEquals('SELECT id, title FROM articles ORDER BY (SELECT NULL) OFFSET 10 ROWS', $query->sql());

        $query = new \Cake\Database\Query($connection);
        $query->select(['id', 'title'])
            ->from('articles')
            ->limit(10);
        $this->assertEquals('SELECT TOP 10 id, title FROM articles', $query->sql());
    }

    /**
     * Test select with limit on lte SQLServer2008
     *
     * @return void
     */
    public function testSelectLimitOldServer()
    {
        $driver = $this->getMock(
            'Cake\Database\Driver\Sqlserver',
            ['_connect', 'connection', '_version'],
            [[]]
        );
        $driver
            ->expects($this->any())
            ->method('_version')
            ->will($this->returnValue(8));

        $connection = $this->getMock(
            '\Cake\Database\Connection',
            ['connect', 'driver'],
            [['log' => false]]
        );
        $connection
            ->expects($this->any())
            ->method('driver')
            ->will($this->returnValue($driver));

        $query = new \Cake\Database\Query($connection);
        $query->select(['id', 'title'])
            ->from('articles')
            ->limit(10);
        $expected = 'SELECT TOP 10 id, title FROM articles';
        $this->assertEquals($expected, $query->sql());

        $query = new \Cake\Database\Query($connection);
        $query->select(['id', 'title'])
            ->from('articles')
            ->offset(10);
        $expected = 'SELECT * FROM (SELECT id, title, (ROW_NUMBER() OVER (ORDER BY (SELECT NULL))) AS [_cake_page_rownum_] ' .
            'FROM articles) _cake_paging_ ' .
            'WHERE _cake_paging_._cake_page_rownum_ > :c0';
        $this->assertEquals($expected, $query->sql());

        $query = new \Cake\Database\Query($connection);
        $query->select(['id', 'title'])
            ->from('articles')
            ->order(['id'])
            ->offset(10);
        $expected = 'SELECT * FROM (SELECT id, title, (ROW_NUMBER() OVER (ORDER BY id)) AS [_cake_page_rownum_] ' .
            'FROM articles) _cake_paging_ ' .
            'WHERE _cake_paging_._cake_page_rownum_ > :c0';
        $this->assertEquals($expected, $query->sql());

        $query = new \Cake\Database\Query($connection);
        $query->select(['id', 'title'])
            ->from('articles')
            ->order(['id'])
            ->where(['title' => 'Something'])
            ->limit(10)
            ->offset(50);
        $expected = 'SELECT * FROM (SELECT id, title, (ROW_NUMBER() OVER (ORDER BY id)) AS [_cake_page_rownum_] ' .
            'FROM articles WHERE title = :c0) _cake_paging_ ' .
            'WHERE (_cake_paging_._cake_page_rownum_ > :c1 AND _cake_paging_._cake_page_rownum_ <= :c2)';
        $this->assertEquals($expected, $query->sql());
    }

    /**
     * Test that insert queries have results available to them.
     *
     * @return void
     */
    public function testInsertUsesOutput()
    {
        $driver = $this->getMock(
            'Cake\Database\Driver\Sqlserver',
            ['_connect', 'connection'],
            [[]]
        );
        $connection = $this->getMock(
            '\Cake\Database\Connection',
            ['connect', 'driver'],
            [['log' => false]]
        );
        $connection
            ->expects($this->any())
            ->method('driver')
            ->will($this->returnValue($driver));
        $query = new \Cake\Database\Query($connection);
        $query->insert(['title'])
            ->into('articles')
            ->values(['title' => 'A new article']);
        $expected = 'INSERT INTO articles (title) OUTPUT INSERTED.* VALUES (:c0)';
        $this->assertEquals($expected, $query->sql());
    }
}
