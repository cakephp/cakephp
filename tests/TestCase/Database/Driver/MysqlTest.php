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
use Cake\Database\Driver\Mysql;
use Cake\Datasource\ConnectionManager;
use Cake\TestSuite\TestCase;
use \PDO;

/**
 * Tests Mysql driver
 *
 */
class MysqlTest extends TestCase
{

    /**
     * setup
     *
     * @return void
     */
    public function setup()
    {
        parent::setUp();
        $config = Configure::read('Datasource.test');
        $this->skipIf(strpos($config['datasource'], 'Mysql') === false, 'Not using Mysql for test config');
    }

    /**
     * Test connecting to Mysql with default configuration
     *
     * @return void
     */
    public function testConnectionConfigDefault()
    {
        $driver = $this->getMock('Cake\Database\Driver\Mysql', ['_connect']);
        $dsn = 'mysql:host=localhost;port=3306;dbname=cake;charset=utf8';
        $expected = [
            'persistent' => true,
            'host' => 'localhost',
            'username' => 'root',
            'password' => '',
            'database' => 'cake',
            'port' => '3306',
            'flags' => [],
            'encoding' => 'utf8',
            'timezone' => null,
            'init' => [],
        ];

        $expected['flags'] += [
            PDO::ATTR_PERSISTENT => true,
            PDO::MYSQL_ATTR_USE_BUFFERED_QUERY => true,
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        ];
        $driver->expects($this->once())->method('_connect')
            ->with($dsn, $expected);
        $driver->connect([]);
    }

    /**
     * Test connecting to Mysql with custom configuration
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
            'init' => ['Execute this', 'this too']
        ];
        $driver = $this->getMock(
            'Cake\Database\Driver\Mysql',
            ['_connect', 'connection'],
            [$config]
        );
        $dsn = 'mysql:host=foo;port=3440;dbname=bar;charset=a-language';
        $expected = $config;
        $expected['init'][] = "SET time_zone = 'Antartica'";
        $expected['flags'] += [
            PDO::ATTR_PERSISTENT => false,
            PDO::MYSQL_ATTR_USE_BUFFERED_QUERY => true,
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        ];

        $connection = $this->getMock('StdClass', ['exec']);
        $connection->expects($this->at(0))->method('exec')->with('Execute this');
        $connection->expects($this->at(1))->method('exec')->with('this too');
        $connection->expects($this->at(2))->method('exec')->with("SET time_zone = 'Antartica'");
        $connection->expects($this->exactly(3))->method('exec');

        $driver->expects($this->once())->method('_connect')
            ->with($dsn, $expected);
        $driver->expects($this->any())->method('connection')
            ->will($this->returnValue($connection));
        $driver->connect($config);
    }
}
