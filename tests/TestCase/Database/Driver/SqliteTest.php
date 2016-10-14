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

use Cake\Database\Driver\Sqlite;
use Cake\TestSuite\TestCase;
use PDO;

/**
 * Tests Sqlite driver
 *
 * @group Sqlite
 */
class SqliteTest extends TestCase
{

    /**
     * Test connecting to Sqlite with default configuration
     *
     * @return void
     */
    public function testConnectionConfigDefault()
    {
        $driver = $this->getMockBuilder('Cake\Database\Driver\Sqlite')
            ->setMethods(['_connect'])
            ->getMock();
        $dsn = 'sqlite::memory:';
        $expected = [
            'persistent' => false,
            'database' => ':memory:',
            'encoding' => 'utf8',
            'username' => null,
            'password' => null,
            'flags' => [],
            'init' => [],
        ];

        $expected['flags'] += [
            PDO::ATTR_PERSISTENT => false,
            PDO::ATTR_EMULATE_PREPARES => false,
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
        ];
        $driver->expects($this->once())->method('_connect')
            ->with($dsn, $expected);
        $driver->connect([]);
    }

    /**
     * Test connecting to Sqlite with custom configuration
     *
     * @return void
     */
    public function testConnectionConfigCustom()
    {
        $config = [
            'persistent' => true,
            'host' => 'foo',
            'database' => 'bar.db',
            'flags' => [1 => true, 2 => false],
            'encoding' => 'a-language',
            'init' => ['Execute this', 'this too']
        ];
        $driver = $this->getMockBuilder('Cake\Database\driver\Sqlite')
            ->setMethods(['_connect', 'connection'])
            ->setConstructorArgs([$config])
            ->getMock();
        $dsn = 'sqlite:bar.db';

        $expected = $config;
        $expected += ['username' => null, 'password' => null];
        $expected['flags'] += [
            PDO::ATTR_PERSISTENT => true,
            PDO::ATTR_EMULATE_PREPARES => false,
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
        ];

        $connection = $this->getMockBuilder('StdClass')
            ->setMethods(['exec'])
            ->getMock();
        $connection->expects($this->at(0))->method('exec')->with('Execute this');
        $connection->expects($this->at(1))->method('exec')->with('this too');
        $connection->expects($this->exactly(2))->method('exec');

        $driver->expects($this->once())->method('_connect')
            ->with($dsn, $expected);
        $driver->expects($this->any())->method('connection')
            ->will($this->returnValue($connection));
        $driver->connect($config);
    }

    /**
     * Data provider for schemaValue()
     *
     * @return array
     */
    public static function schemaValueProvider()
    {
        return [
            [null, 'NULL'],
            [false, 'FALSE'],
            [true, 'TRUE'],
            [3.14159, '3.14159'],
            ['33', '33'],
            [66, 66],
            [0, 0],
            [10e5, '1000000'],
            ['hearts', '"hearts"'],
        ];
    }

    /**
     * Test the schemaValue method on Driver.
     *
     * @dataProvider schemaValueProvider
     * @param mixed $input
     * @param string $expected
     */
    public function testSchemaValue($input, $expected)
    {
        $driver = new Sqlite();
        $pdo = PDO::class;
        if (version_compare(PHP_VERSION, '5.6', '<')) {
            $pdo = 'FakePdo';
        }
        $mock = $this->getMockBuilder($pdo)
            ->setMethods(['quote', 'quoteIdentifier'])
            ->disableOriginalConstructor()
            ->getMock();
        $mock->expects($this->any())
            ->method('quote')
            ->will($this->returnCallback(function ($value) {
                return '"' . $value . '"';
            }));
        $driver->connection($mock);
        $this->assertEquals($expected, $driver->schemaValue($input));
    }

    /**
     * Test update with limit
     *
     * @return void
     */
    public function testUpdateLimit()
    {
        $driver = $this->getMockBuilder('Cake\Database\driver\Sqlite')
            ->setMethods(['_connect', 'connection'])
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

        $this->assertEquals('UPDATE articles SET title = :c0 WHERE published = 1 LIMIT 5', $query->sql());
    }

    /**
     * Test update with limit and offset
     *
     * @return void
     */
    public function testUpdateLimitOffset()
    {
        $driver = $this->getMockBuilder('Cake\Database\driver\Sqlite')
            ->setMethods(['_connect', 'connection'])
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
            ->limit(5)
            ->offset(3);

        $this->assertEquals('UPDATE articles SET title = :c0 WHERE published = 1 LIMIT 5 OFFSET 3', $query->sql());
    }

    /**
     * Test update with limit
     *
     * @return void
     */
    public function testUpdateOrderBy()
    {
        $driver = $this->getMockBuilder('Cake\Database\driver\Sqlite')
            ->setMethods(['_connect', 'connection'])
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
            ->order(['created' => 'DESC']);

        $this->assertEquals('UPDATE articles SET title = :c0 WHERE published = 1 ORDER BY created DESC', $query->sql());
    }

    /**
     * Test update with limit and offset
     *
     * @return void
     */
    public function testUpdateOrderByLimitOffset()
    {
        $driver = $this->getMockBuilder('Cake\Database\driver\Sqlite')
            ->setMethods(['_connect', 'connection'])
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
            ->limit(5)
            ->offset(3)
            ->order(['created' => 'DESC']);

        $this->assertEquals('UPDATE articles SET title = :c0 WHERE published = 1 ORDER BY created DESC LIMIT 5 OFFSET 3', $query->sql());
    }

}
