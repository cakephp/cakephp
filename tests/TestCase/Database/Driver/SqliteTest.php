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
use Cake\Database\Driver\Sqlite;
use Cake\Testsuite\TestCase;
use \PDO;

/**
 * Tests Sqlite driver
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
        $driver = $this->getMock('Cake\Database\Driver\Sqlite', ['_connect']);
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
        $driver = $this->getMock(
            'Cake\Database\driver\Sqlite',
            ['_connect', 'connection'],
            [$config]
        );
        $dsn = 'sqlite:bar.db';

        $expected = $config;
        $expected += ['username' => null, 'password' => null];
        $expected['flags'] += [
            PDO::ATTR_PERSISTENT => true,
            PDO::ATTR_EMULATE_PREPARES => false,
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
        ];

        $connection = $this->getMock('StdClass', ['exec']);
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
            ['farts', '"farts"'],
        ];
    }

    /**
     * Test the schemaValue method on Driver.
     *
     * @dataProvider schemaValueProvider
     * @return void
     */
    public function testSchemaValue($input, $expected)
    {
        $driver = new Sqlite();
        $mock = $this->getMock('FakePdo', ['quote', 'quoteIdentifier']);
        $mock->expects($this->any())
            ->method('quote')
            ->will($this->returnCallback(function ($value) {
                return '"' . $value . '"';
            }));
        $driver->connection($mock);
        $this->assertEquals($expected, $driver->schemaValue($input));
    }
}
