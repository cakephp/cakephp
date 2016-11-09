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

use Cake\Database\Query;
use Cake\TestSuite\TestCase;
use PDO;

/**
 * Tests Postgres driver
 *
 * @group Postgres
 */
class PostgresTest extends TestCase
{

    /**
     * Test connecting to Postgres with default configuration
     *
     * @return void
     */
    public function testConnectionConfigDefault()
    {
        $driver = $this->getMockBuilder('Cake\Database\Driver\Postgres')
            ->setMethods(['_connect', 'connection'])
            ->getMock();
        $dsn = 'pgsql:host=localhost;port=5432;dbname=cake';
        $expected = [
            'persistent' => true,
            'host' => 'localhost',
            'username' => 'root',
            'password' => '',
            'database' => 'cake',
            'schema' => 'public',
            'port' => 5432,
            'encoding' => 'utf8',
            'timezone' => null,
            'flags' => [],
            'init' => [],
        ];

        $expected['flags'] += [
            PDO::ATTR_PERSISTENT => true,
            PDO::ATTR_EMULATE_PREPARES => false,
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
        ];

        $connection = $this->getMockBuilder('stdClass')
            ->setMethods(['exec', 'quote'])
            ->getMock();
        $connection->expects($this->any())
            ->method('quote')
            ->will($this->onConsecutiveCalls(
                $this->returnArgument(0),
                $this->returnArgument(0),
                $this->returnArgument(0)
            ));

        $connection->expects($this->at(1))->method('exec')->with('SET NAMES utf8');
        $connection->expects($this->at(3))->method('exec')->with('SET search_path TO public');
        $connection->expects($this->exactly(2))->method('exec');

        $driver->expects($this->once())->method('_connect')
            ->with($dsn, $expected);
        $driver->expects($this->any())->method('connection')
            ->will($this->returnValue($connection));

        $driver->connect();
    }

    /**
     * Test connecting to Postgres with custom configuration
     *
     * @return void
     */
    public function testConnectionConfigCustom()
    {
        $config = [
            'persistent' => false,
            'host' => 'foo',
            'database' => 'bar',
            'username' => 'user',
            'password' => 'pass',
            'port' => 3440,
            'flags' => [1 => true, 2 => false],
            'encoding' => 'a-language',
            'timezone' => 'Antartica',
            'schema' => 'fooblic',
            'init' => ['Execute this', 'this too']
        ];
        $driver = $this->getMockBuilder('Cake\Database\Driver\Postgres')
            ->setMethods(['_connect', 'connection'])
            ->setConstructorArgs([$config])
            ->getMock();
        $dsn = 'pgsql:host=foo;port=3440;dbname=bar';

        $expected = $config;
        $expected['flags'] += [
            PDO::ATTR_PERSISTENT => false,
            PDO::ATTR_EMULATE_PREPARES => false,
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
        ];

        $connection = $this->getMockBuilder('stdClass')
            ->setMethods(['exec', 'quote'])
            ->getMock();
        $connection->expects($this->any())
            ->method('quote')
            ->will($this->onConsecutiveCalls(
                $this->returnArgument(0),
                $this->returnArgument(0),
                $this->returnArgument(0)
            ));

        $connection->expects($this->at(1))->method('exec')->with('SET NAMES a-language');
        $connection->expects($this->at(3))->method('exec')->with('SET search_path TO fooblic');
        $connection->expects($this->at(5))->method('exec')->with('Execute this');
        $connection->expects($this->at(6))->method('exec')->with('this too');
        $connection->expects($this->at(7))->method('exec')->with('SET timezone = Antartica');
        $connection->expects($this->exactly(5))->method('exec');

        $driver->connection($connection);
        $driver->expects($this->once())->method('_connect')
            ->with($dsn, $expected);

        $driver->expects($this->any())->method('connection')
            ->will($this->returnValue($connection));

        $driver->connect();
    }

    /**
     * Tests that insert queries get a "RETURNING *" string at the end
     *
     * @return void
     */
    public function testInsertReturning()
    {
        $driver = $this->getMockBuilder('Cake\Database\Driver\Postgres')
            ->setMethods(['_connect', 'connection'])
            ->setConstructorArgs([[]])
            ->getMock();
        $connection = $this
            ->getMockBuilder('\Cake\Database\Connection')
            ->setMethods(['connect'])
            ->disableOriginalConstructor()
            ->getMock();

        $query = new Query($connection);
        $query->insert(['id', 'title'])
            ->into('articles')
            ->values([1, 'foo']);
        $translator = $driver->queryTranslator('insert');
        $query = $translator($query);
        $this->assertEquals('RETURNING *', $query->clause('epilog'));

        $query = new Query($connection);
        $query->insert(['id', 'title'])
            ->into('articles')
            ->values([1, 'foo'])
            ->epilog('FOO');
        $query = $translator($query);
        $this->assertEquals('FOO', $query->clause('epilog'));
    }

    /**
     * Test update with limit
     *
     * @return void
     */
    public function testUpdateLimit()
    {
        $driver = $this->getMockBuilder('Cake\Database\Driver\Postgres')
            ->setMethods(['_connect', 'connection'])
            ->setConstructorArgs([[]])
            ->getMock();

        $connection = $this->getMockBuilder('\Cake\Database\Connection')
            ->setMethods(['connect', 'driver'])
            ->setConstructorArgs([['log' => false]])
            ->getMock();

        $connection
            ->expects($this->any())
            ->method('driver')
            ->will($this->returnValue($driver));

        $query = new \Cake\Database\Query($connection);

        $query->update('articles')
            ->set(['title' => 'FooBar'])
            ->where(['published' => true])
            ->limit(5);

        $this->assertEquals('UPDATE articles SET title = :c0 FROM (SELECT oid FROM articles WHERE published = :c1 LIMIT 5) __cake_update__ WHERE oid = (__cake_update__.oid)', $query->sql());
    }
}
