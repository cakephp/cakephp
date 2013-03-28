<?php
/**
 * PHP Version 5.4
 *
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @package       Cake.Model
 * @since         CakePHP(tm) v 3.0.0
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
namespace Cake\Test\TestCase\Model\Datasource\Database\Driver;

use Cake\Core\Configure;
use Cake\Model\Datasource\Database\Connection;
use \PDO;

/**
 * Tests Mysql driver
 *
 */
class MysqlTest extends \Cake\TestSuite\TestCase {

/**
 * setUp
 *
 * @return void
 */
	public function setUp() {
		parent::setUp();
		$config = Configure::read('Datasource.test');
		$this->skipIf(strpos($config['datasource'], 'Mysql') === false, 'Not using Mysql for test config');
	}
/**
 * Test connecting to Mysql with default configuration
 *
 * @return void
 */
	public function testConnectionConfigDefault() {
		$driver = $this->getMock('Cake\Model\Datasource\Database\Driver\Mysql', ['_connect']);
		$expected = [
			'persistent' => true,
			'host' => 'localhost',
			'login' => 'root',
			'password' => '',
			'database' => 'cake',
			'port' => '3306',
			'flags' => [],
			'encoding' => 'utf8',
			'timezone' => '+0:00',
			'init' => ["SET time_zone = '+0:00'"],
			'dsn' => 'mysql:host=localhost;port=3306;dbname=cake;charset=utf8'
		];

		$expected['flags'] += [
			PDO::ATTR_PERSISTENT => true,
			PDO::MYSQL_ATTR_USE_BUFFERED_QUERY => true,
			PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
			PDO::MYSQL_ATTR_INIT_COMMAND => "SET time_zone = '+0:00'"
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
		$driver = $this->getMock('Cake\Model\Datasource\Database\Driver\Mysql', ['_connect']);
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

		$expected = $config;
		$expected['dsn'] = 'mysql:host=foo;port=3440;dbname=bar;charset=a-language';
		$expected['init'][] = "SET time_zone = '+0:00'";
		$expected['flags'] += [
			PDO::ATTR_PERSISTENT => false,
			PDO::MYSQL_ATTR_USE_BUFFERED_QUERY => true,
			PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
			PDO::MYSQL_ATTR_INIT_COMMAND => "Execute this;this too;SET time_zone = '+0:00'"
		];
		$driver->expects($this->once())->method('_connect')
			->with($expected);
		$driver->connect($config);
	}

/**
 * Helper method for testing methods.
 *
 * @return void
 */
	protected function _createTables($connection) {
		$connection->execute('DROP TABLE IF EXISTS articles');
		$connection->execute('DROP TABLE IF EXISTS authors');

		$table = <<<SQL
CREATE TABLE authors(
id INT(11) PRIMARY KEY AUTO_INCREMENT,
name VARCHAR(50),
bio TEXT,
created DATETIME
)
SQL;
		$connection->execute($table);

		$table = <<<SQL
CREATE TABLE articles(
id BIGINT PRIMARY KEY AUTO_INCREMENT,
title VARCHAR(20),
body TEXT,
author_id INT(11) NOT NULL,
created DATETIME
) COLLATE=utf8_general_ci
SQL;
		$connection->execute($table);
	}

/**
 * Dataprovider for column testing
 *
 * @return array
 */
	public static function columnProvider() {
		return [
			[
				'DATETIME',
				['datetime', null]
			],
			[
				'DATE',
				['date', null]
			],
			[
				'TIME',
				['time', null]
			],
			[
				'TINYINT(1)',
				['boolean', null]
			],
			[
				'TINYINT(2)',
				['integer', 2]
			],
			[
				'BIGINT',
				['biginteger', null]
			],
			[
				'VARCHAR(255)',
				['string', 255]
			],
			[
				'CHAR(25)',
				['string', 25]
			],
			[
				'TINYTEXT',
				['string', null]
			],
			[
				'BLOB',
				['binary', null]
			],
			[
				'MEDIUMBLOB',
				['binary', null]
			],
			[
				'FLOAT',
				['float', null]
			],
			[
				'DOUBLE',
				['float', null]
			],
			[
				'DECIMAL(11,2)',
				['decimal', null]
			],
		];
	}

/**
 * Test parsing MySQL column types.
 *
 * @dataProvider columnProvider
 * @return void
 */
	public function testConvertColumnType($input, $expected) {
		$driver = $this->getMock('Cake\Model\Datasource\Database\Driver\Mysql', ['_connect']);
		$this->assertEquals($driver->convertColumn($input), $expected);
	}

/**
 * Provider for testing index conversion
 *
 * @return array
 */
	public static function convertIndexProvider() {
		return [
			['PRI', 'primary'],
			['UNI', 'unique'],
			['MUL', 'index'],
		];
	}
/**
 * Test parsing MySQL index types.
 *
 * @dataProvider convertIndexProvider
 * @return void
 */
	public function testConvertIndex($input, $expected) {
		$driver = $this->getMock('Cake\Model\Datasource\Database\Driver\Mysql', ['_connect']);
		$this->assertEquals($driver->convertIndex($input), $expected);
	}

/**
 * Test listing tables with Mysql
 *
 * @return void
 */
	public function testListTables() {
		$connection = new Connection(Configure::read('Datasource.test'));
		$this->_createTables($connection);

		$result = $connection->listTables();
		$this->assertInternalType('array', $result);
		$this->assertCount(2, $result);
		$this->assertEquals('articles', $result[0]);
		$this->assertEquals('authors', $result[1]);
	}

/**
 * Test describing a table with Mysql
 *
 * @return void
 */
	public function testDescribeTable() {
		$connection = new Connection(Configure::read('Datasource.test'));
		$this->_createTables($connection);

		$result = $connection->describe('articles');
		$expected = [
			'id' => [
				'type' => 'biginteger',
				'null' => false,
				'default' => null,
				'length' => 20,
				'key' => 'primary',
			],
			'title' => [
				'type' => 'string',
				'null' => true,
				'default' => null,
				'length' => 20,
				'collate' => 'utf8_general_ci',
			],
			'body' => [
				'type' => 'text',
				'null' => true,
				'default' => null,
				'length' => null,
				'collate' => 'utf8_general_ci',
			],
			'author_id' => [
				'type' => 'integer',
				'null' => false,
				'default' => null,
				'length' => 11,
			],
			'created' => [
				'type' => 'datetime',
				'null' => true,
				'default' => null,
				'length' => null,
			],
		];
		$this->assertEquals($expected, $result);
	}
}
