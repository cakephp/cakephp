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
use Cake\Database\Schema\MysqlSchema;
use Cake\Database\Schema\Table;
use Cake\TestSuite\TestCase;


/**
 * Test case for Mysql Schema Dialect.
 */
class MysqlSchemaTest extends TestCase {

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
 * Dataprovider for column testing
 *
 * @return array
 */
	public static function columnProvider() {
		return [
			[
				'DATETIME',
				['type' => 'datetime', 'length' => null]
			],
			[
				'DATE',
				['type' =>  'date', 'length' => null]
			],
			[
				'TIME',
				['type' => 'time', 'length' => null]
			],
			[
				'TINYINT(1)',
				['type' => 'boolean', 'length' => null]
			],
			[
				'TINYINT(2)',
				['type' => 'integer', 'length' => 2]
			],
			[
				'INTEGER(11)',
				['type' => 'integer', 'length' => 11]
			],
			[
				'BIGINT',
				['type' => 'biginteger', 'length' => null]
			],
			[
				'VARCHAR(255)',
				['type' => 'string', 'length' => 255]
			],
			[
				'CHAR(25)',
				['type' => 'string', 'length' => 25, 'fixed' => true]
			],
			[
				'TINYTEXT',
				['type' => 'string', 'length' => null]
			],
			[
				'BLOB',
				['type' => 'binary', 'length' => null]
			],
			[
				'MEDIUMBLOB',
				['type' => 'binary', 'length' => null]
			],
			[
				'FLOAT',
				['type' => 'float', 'length' => null]
			],
			[
				'DOUBLE',
				['type' => 'float', 'length' => null]
			],
			[
				'DECIMAL(11,2)',
				['type' => 'decimal', 'length' => null]
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
		$driver = $this->getMock('Cake\Database\Driver\Mysql');
		$dialect = new MysqlSchema($driver);
		$this->assertEquals($expected, $dialect->convertColumn($input));
	}

/**
 * Helper method for testing methods.
 *
 * @return void
 */
	protected function _createTables($connection) {
		$this->_needsConnection();
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
title VARCHAR(20) COMMENT 'A title',
body TEXT,
author_id INT(11) NOT NULL,
published BOOLEAN DEFAULT 0,
allow_comments TINYINT(1) DEFAULT 0,
created DATETIME
) COLLATE=utf8_general_ci
SQL;
		$connection->execute($table);
	}

/**
 * Integration test for SchemaCollection & MysqlDialect.
 *
 * @return void
 */
	public function testListTables() {
		$connection = new Connection(Configure::read('Datasource.test'));
		$this->_createTables($connection);

		$schema = new SchemaCollection($connection);
		$result = $schema->listTables();

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

		$schema = new SchemaCollection($connection);
		$result = $schema->describe('articles');
		$this->assertInstanceOf('Cake\Database\Schema\Table', $result);
		$expected = [
			'id' => [
				'type' => 'biginteger',
				'null' => false,
				'default' => null,
				'length' => 20,
				'fixed' => null,
				'comment' => null,
				'collate' => null,
				'charset' => null,
			],
			'title' => [
				'type' => 'string',
				'null' => true,
				'default' => null,
				'length' => 20,
				'collate' => 'utf8_general_ci',
				'comment' => 'A title',
				'fixed' => null,
				'charset' => null,
			],
			'body' => [
				'type' => 'text',
				'null' => true,
				'default' => null,
				'length' => null,
				'collate' => 'utf8_general_ci',
				'fixed' => null,
				'comment' => null,
				'charset' => null,
			],
			'author_id' => [
				'type' => 'integer',
				'null' => false,
				'default' => null,
				'length' => 11,
				'fixed' => null,
				'comment' => null,
				'collate' => null,
				'charset' => null,
			],
			'published' => [
				'type' => 'boolean',
				'null' => true,
				'default' => 0,
				'length' => null,
				'fixed' => null,
				'comment' => null,
				'collate' => null,
				'charset' => null,
			],
			'allow_comments' => [
				'type' => 'boolean',
				'null' => true,
				'default' => 0,
				'length' => null,
				'fixed' => null,
				'comment' => null,
				'collate' => null,
				'charset' => null,
			],
			'created' => [
				'type' => 'datetime',
				'null' => true,
				'default' => null,
				'length' => null,
				'fixed' => null,
				'comment' => null,
				'collate' => null,
				'charset' => null,
			],
		];
		$this->assertEquals(['id'], $result->primaryKey());
		foreach ($expected as $field => $definition) {
			$this->assertEquals($definition, $result->column($field));
		}
	}

/**
 * Column provider for creating column sql
 *
 * @return array
 */
	public static function columnSqlProvider() {
		return [
			// strings
			[
				'title',
				['type' => 'string', 'length' => 25, 'null' => false],
				'`title` VARCHAR(25) NOT NULL'
			],
			[
				'title',
				['type' => 'string', 'length' => 25, 'null' => true, 'default' => 'ignored'],
				'`title` VARCHAR(25) DEFAULT NULL'
			],
			[
				'id',
				['type' => 'string', 'length' => 32, 'fixed' => true, 'null' => false],
				'`id` CHAR(32) NOT NULL'
			],
			[
				'role',
				['type' => 'string', 'length' => 10, 'null' => false, 'default' => 'admin'],
				'`role` VARCHAR(10) NOT NULL DEFAULT "admin"'
			],
			[
				'title',
				['type' => 'string'],
				'`title` VARCHAR(255)'
			],
			// Text
			[
				'body',
				['type' => 'text', 'null' => false],
				'`body` TEXT NOT NULL'
			],
			// Integers
			[
				'post_id',
				['type' => 'integer', 'length' => 11],
				'`post_id` INTEGER(11)'
			],
			[
				'post_id',
				['type' => 'biginteger', 'length' => 20],
				'`post_id` BIGINT'
			],
			// Float
			[
				'value',
				['type' => 'float'],
				'`value` FLOAT'
			],
			// Boolean
			[
				'checked',
				['type' => 'boolean', 'default' => false],
				'`checked` BOOLEAN DEFAULT FALSE'
			],
			[
				'checked',
				['type' => 'boolean', 'default' => true, 'null' => false],
				'`checked` BOOLEAN NOT NULL DEFAULT TRUE'
			],
			// datetimes
			[
				'created',
				['type' => 'datetime', 'comment' => 'Created timestamp'],
				'`created` DATETIME COMMENT "Created timestamp"'
			],
			// Date & Time
			[
				'start_date',
				['type' => 'date'],
				'`start_date` DATE'
			],
			[
				'start_time',
				['type' => 'time'],
				'`start_time` TIME'
			],
			// timestamps
			[
				'created',
				['type' => 'timestamp', 'null' => true],
				'`created` TIMESTAMP NULL'
			],
			[
				'created',
				['type' => 'timestamp', 'null' => false, 'default' => 'current_timestamp'],
				'`created` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP'
			],
		];
	}

/**
 * Test generating column definitions
 *
 * @dataProvider columnSqlProvider
 * @return void
 */
	public function testColumnSql($name, $data, $expected) {
		$schema = $this->_getMockedSchema();

		$table = (new Table('articles'))->addColumn($name, $data);
		$this->assertEquals($expected, $schema->columnSql($table, $name));
	}

/**
 * Test generating a column that is a primary key.
 *
 * @return void
 */
	public function testColumnSqlPrimaryKey() {
		$schema = $this->_getMockedSchema();

		$table = new Table('articles');
		$table->addColumn('id', [
				'type' => 'integer',
				'null' => false
			])
			->addIndex('primary', [
				'type' => 'primary',
				'columns' => ['id']
			]);
		$result = $schema->columnSql($table, 'id');
		$this->assertEquals($result, '`id` INTEGER NOT NULL AUTO_INCREMENT');

		$table = new Table('articles');
		$table->addColumn('id', [
				'type' => 'biginteger',
				'null' => false
			])
			->addIndex('primary', [
				'type' => 'primary',
				'columns' => ['id']
			]);
		$result = $schema->columnSql($table, 'id');
		$this->assertEquals($result, '`id` BIGINT NOT NULL AUTO_INCREMENT');
	}

/**
 * Integration test for converting a Schema\Table into MySQL table creates.
 *
 * @return void
 */
	public function testCreateTableSql() {
		$table = (new Table('posts'))->addColumn('id', [
				'type' => 'integer',
				'null' => false
			])
			->addColumn('title', [
				'type' => 'string',
				'null' => false,
				'comment' => 'The title'
			])
			->addColumn('body', ['type' => 'text'])
			->addColumn('created', 'datetime')
			->addIndex('primary', [
				'type' => 'primary',
				'columns' => ['id']
			]);

		$connection = $this->getMock('Cake\Database\Connection', array(), array(), '', false);
		$driver = new \Cake\Database\Driver\Mysql();
		$mock = $this->getMock('FakePdo', ['quote']);
		$driver->connection($mock);

		$dialect = new MysqlSchema($driver);

		$connection->expects($this->any())->method('driver')
			->will($this->returnValue($driver));

		$mock->expects($this->any())
			->method('quote')
			->will($this->returnCallback(function ($value) {
				return '"' . $value . '"';
			}));

		$result = $table->createTableSql($connection);
		$expected = <<<SQL
CREATE TABLE `posts` (
`id` INTEGER NOT NULL AUTO_INCREMENT,
`title` VARCHAR(255) NOT NULL COMMENT "The title",
`body` TEXT,
`created` DATETIME,
PRIMARY KEY (`id`)
);
SQL;
		$this->assertEquals($expected, $result);
	}

/**
 * Get a schema instance with a mocked driver/pdo instances
 *
 * @return MysqlSchema
 */
	protected function _getMockedSchema() {
		$driver = new \Cake\Database\Driver\Mysql();
		$mock = $this->getMock('FakePdo', ['quote']);
		$mock->expects($this->any())
			->method('quote')
			->will($this->returnCallback(function ($value) {
				return '"' . $value . '"';
			}));
		$driver->connection($mock);
		return new MysqlSchema($driver);
	}

}
