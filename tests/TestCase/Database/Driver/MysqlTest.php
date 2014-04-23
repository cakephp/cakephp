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
class MysqlTest extends TestCase {

/**
 * Helper method for skipping tests that need a real connection.
 *
 * @return void
 */
	protected function _needsConnection() {
		$config = Configure::read('Datasource.test');
		$this->skipIf(strpos($config['datasource'], 'Mysql') === false, 'Not using Mysql for test config');
	}

/**
 * Test connecting to Mysql with default configuration
 *
 * @return void
 */
	public function testConnectionConfigDefault() {
		$driver = $this->getMock('Cake\Database\Driver\Mysql', ['_connect']);
		$expected = [
			'persistent' => true,
			'host' => 'localhost',
			'login' => 'root',
			'password' => '',
			'database' => 'cake',
			'port' => '3306',
			'flags' => [],
			'encoding' => 'utf8',
			'timezone' => null,
			'init' => [],
			'dsn' => 'mysql:host=localhost;port=3306;dbname=cake;charset=utf8'
		];

		$expected['flags'] += [
			PDO::ATTR_PERSISTENT => true,
			PDO::MYSQL_ATTR_USE_BUFFERED_QUERY => true,
			PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
		];
		$driver->expects($this->once())->method('_connect')
			->with($expected);
		$driver->connect([]);
	}

/**
 * Test connecting to Mysql with custom configuration
 *
 * @return void
 */
	public function testConnectionConfigCustom() {
		$config = [
			'persistent' => false,
			'host' => 'foo',
			'database' => 'bar',
			'login' => 'user',
			'password' => 'pass',
			'port' => 3440,
			'flags' => [1 => true, 2 => false],
			'encoding' => 'a-language',
			'timezone' => 'Antartica',
			'init' => ['Execute this', 'this too']
		];
		$driver = $this->getMock(
			'Cake\Database\Driver\Mysql',
			['_connect'],
			[$config]
		);
		$expected = $config;
		$expected['dsn'] = 'mysql:host=foo;port=3440;dbname=bar;charset=a-language';
		$expected['init'][] = "SET time_zone = 'Antartica'";
		$expected['flags'] += [
			PDO::ATTR_PERSISTENT => false,
			PDO::MYSQL_ATTR_USE_BUFFERED_QUERY => true,
			PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
			PDO::MYSQL_ATTR_INIT_COMMAND => "Execute this;this too;SET time_zone = 'Antartica'"
		];
		$driver->expects($this->once())->method('_connect')
			->with($expected);
		$driver->connect();
	}

}
