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
 * @since         CakePHP(tm) v 3.0.0
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
namespace Cake\Test\TestCase\Database\Schema;

use Cake\Core\Configure;
use Cake\Database\Connection;
use Cake\Database\Schema\Collection as SchemaCollection;
use Cake\Database\Schema\SqliteSchema;
use Cake\TestSuite\TestCase;


/**
 * Test case for Sqlite Schema Dialect.
 */
class SqliteSchemaTest extends TestCase {

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
		$driver = $this->getMock('Cake\Database\Driver\Sqlite');
		$dialect = new SqliteSchema($driver);
		$this->assertEquals($expected, $dialect->convertColumn($input));
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
 * Test SchemaCollection listing tables with Sqlite
 *
 * @return void
 */
	public function testListTables() {
		$connection = new Connection(Configure::read('Datasource.test'));
		$this->_createTables($connection);

		$schema = new SchemaCollection($connection);
		$result = $schema->listTables();

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

		$schema = new SchemaCollection($connection);
		$result = $schema->describe('articles');
		$expected = [
			'id' => [
				'type' => 'integer',
				'null' => false,
				'default' => null,
				'length' => null,
				'fixed' => null,
				'charset' => null,
				'comment' => null,
				'collate' => null,
			],
			'title' => [
				'type' => 'string',
				'null' => true,
				'default' => 'testing',
				'length' => 20,
				'fixed' => null,
				'charset' => null,
				'comment' => null,
				'collate' => null,
			],
			'body' => [
				'type' => 'text',
				'null' => true,
				'default' => null,
				'length' => null,
				'fixed' => null,
				'charset' => null,
				'comment' => null,
				'collate' => null,
			],
			'author_id' => [
				'type' => 'integer',
				'null' => false,
				'default' => null,
				'length' => 11,
				'fixed' => null,
				'charset' => null,
				'comment' => null,
				'collate' => null,
			],
			'published' => [
				'type' => 'boolean',
				'null' => true,
				'default' => 0,
				'length' => null,
				'fixed' => null,
				'charset' => null,
				'comment' => null,
				'collate' => null,
			],
			'created' => [
				'type' => 'datetime',
				'null' => true,
				'default' => null,
				'length' => null,
				'fixed' => null,
				'charset' => null,
				'comment' => null,
				'collate' => null,
			],
		];
		$this->assertInstanceOf('Cake\Database\Schema\Table', $result);
		foreach ($expected as $field => $definition) {
			$this->assertEquals($definition, $result->column($field));
		}
	}
}
