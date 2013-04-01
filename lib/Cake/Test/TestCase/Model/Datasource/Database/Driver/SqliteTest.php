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
use Cake\Model\Datasource\Database\Driver\Sqlite;
use Cake\Testsuite\TestCase;
use \PDO;

/**
 * Tests Sqlite driver
 */
class SqliteTest extends TestCase {

/**
 * Helper method for skipping tests that need a real connection.
 *
 * @return void
 */
	protected function _needsConnection() {
		$config = Configure::read('Datasource.test');
		$this->skipIf(strpos($config['datasource'], 'Sqlite') === false, 'Not using Sqlite for test config');
	}

/**
 * Test connecting to Sqlite with default configuration
 *
 * @return void
 */
	public function testConnectionConfigDefault() {
		$driver = $this->getMock('Cake\Model\Datasource\Database\driver\Sqlite', ['_connect']);
		$expected = [
			'persistent' => false,
			'database' => ':memory:',
			'encoding' => 'utf8',
			'login' => null,
			'password' => null,
			'flags' => [],
			'init' => [],
			'dsn' => 'sqlite::memory:'
		];

		$expected['flags'] += [
			PDO::ATTR_PERSISTENT => false,
			PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
		];
		$driver->expects($this->once())->method('_connect')
			->with($expected);
		$driver->connect([]);
	}

/**
 * Test connecting to Sqlite with custom configuration
 *
 * @return void
 */
	public function testConnectionConfigCustom() {
		$driver = $this->getMock('Cake\Model\Datasource\Database\driver\Sqlite', ['_connect', 'connection']);
		$config = [
			'persistent' => true,
			'host' => 'foo',
			'database' => 'bar.db',
			'flags' => [1 => true, 2 => false],
			'encoding' => 'a-language',
			'init' => ['Execute this', 'this too']
		];

		$expected = $config;
		$expected += ['login' => null, 'password' => null];
		$expected['dsn'] = 'sqlite:bar.db';
		$expected['flags'] += [
			PDO::ATTR_PERSISTENT => true,
			PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
		];

		$connection = $this->getMock('StdClass', ['exec']);
		$connection->expects($this->at(0))->method('exec')->with('Execute this');
		$connection->expects($this->at(1))->method('exec')->with('this too');
		$connection->expects($this->exactly(2))->method('exec');

		$driver->expects($this->once())->method('_connect')
			->with($expected);
		$driver->expects($this->any())->method('connection')
			->will($this->returnValue($connection));
		$driver->connect($config);
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
				'BOOLEAN',
				['boolean', null]
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
				'BLOB',
				['binary', null]
			],
			[
				'INTEGER(11)',
				['integer', 11]
			],
			[
				'TINYINT(5)',
				['integer', 5]
			],
			[
				'MEDIUMINT(10)',
				['integer', 10]
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
				'REAL',
				['float', null]
			],
			[
				'DECIMAL(11,2)',
				['decimal', null]
			],
		];
	}

/**
 * Test parsing SQLite column types.
 *
 * @dataProvider columnProvider
 * @return void
 */
	public function testConvertColumnType($input, $expected) {
		$driver = new Sqlite();
		$this->assertEquals($expected, $driver->convertColumn($input));
	}

/**
 * Creates tables for testing listTables/describe()
 *
 * @param Connection $connection
 * @return void
 */
	protected function _createTables($connection) {
		$this->_needsConnection();
		$connection->execute('DROP TABLE IF EXISTS articles');
		$connection->execute('DROP TABLE IF EXISTS authors');

		$table = <<<SQL
CREATE TABLE authors(
id INTEGER PRIMARY KEY AUTOINCREMENT,
name VARCHAR(50),
bio TEXT,
created DATETIME
)
SQL;
		$connection->execute($table);

		$table = <<<SQL
CREATE TABLE articles(
id INTEGER PRIMARY KEY AUTOINCREMENT,
title VARCHAR(20) DEFAULT 'testing',
body TEXT,
author_id INT(11) NOT NULL,
published BOOLEAN DEFAULT 0,
created DATETIME
)
SQL;
		$connection->execute($table);
	}

/**
 * Test listing tables with Sqlite
 *
 * @return void
 */
	public function testListTables() {
		$connection = new Connection(Configure::read('Datasource.test'));
		$this->_createTables($connection);

		$result = $connection->listTables();
		$this->assertInternalType('array', $result);
		$this->assertCount(3, $result);
		$this->assertEquals('articles', $result[0]);
		$this->assertEquals('authors', $result[1]);
		$this->assertEquals('sqlite_sequence', $result[2]);
	}

/**
 * Test describing a table with Sqlite
 *
 * @return void
 */
	public function testDescribeTable() {
		$connection = new Connection(Configure::read('Datasource.test'));
		$this->_createTables($connection);

		$result = $connection->describe('articles');
		$expected = [
			'id' => [
				'type' => 'integer',
				'null' => false,
				'default' => null,
				'length' => null,
				'key' => 'primary',
			],
			'title' => [
				'type' => 'string',
				'null' => true,
				'default' => 'testing',
				'length' => 20,
			],
			'body' => [
				'type' => 'text',
				'null' => true,
				'default' => null,
				'length' => null,
			],
			'author_id' => [
				'type' => 'integer',
				'null' => false,
				'default' => null,
				'length' => 11,
			],
			'published' => [
				'type' => 'boolean',
				'null' => true,
				'default' => 0,
				'length' => null,
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
