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
use Cake\Database\ConnectionManager;
use Cake\Database\Schema\Collection as SchemaCollection;
use Cake\Database\Schema\PostgresSchema;
use Cake\Database\Schema\Table;
use Cake\TestSuite\TestCase;

/**
 * Postgres schema test case.
 */
class PostgresSchemaTest extends TestCase {

/**
 * Helper method for skipping tests that need a real connection.
 *
 * @return void
 */
	protected function _needsConnection() {
		$config = ConnectionManager::config('test');
		$this->skipIf(strpos($config['className'], 'Postgres') === false, 'Not using Postgres for test config');
	}

/**
 * Helper method for testing methods.
 *
 * @return void
 */
	protected function _createTables($connection) {
		$this->_needsConnection();

		$connection->execute('DROP TABLE IF EXISTS schema_articles');
		$connection->execute('DROP TABLE IF EXISTS schema_authors');

		$table = <<<SQL
CREATE TABLE schema_authors (
id SERIAL,
name VARCHAR(50),
bio DATE,
created TIMESTAMP,
PRIMARY KEY (id)
)
SQL;
		$connection->execute($table);

		$table = <<<SQL
CREATE TABLE schema_articles (
id BIGINT PRIMARY KEY,
title VARCHAR(20),
body TEXT,
author_id INTEGER NOT NULL,
published BOOLEAN DEFAULT false,
views SMALLINT DEFAULT 0,
created TIMESTAMP,
CONSTRAINT "content_idx" UNIQUE ("title", "body"),
CONSTRAINT "author_idx" FOREIGN KEY ("author_id") REFERENCES "schema_authors" ("id") ON DELETE RESTRICT ON UPDATE CASCADE
)
SQL;
		$connection->execute($table);
		$connection->execute('COMMENT ON COLUMN "schema_articles"."title" IS \'a title\'');
		$connection->execute('CREATE INDEX "author_idx" ON "schema_articles" ("author_id")');
	}

/**
 * Data provider for convert column testing
 *
 * @return array
 */
	public static function convertColumnProvider() {
		return [
			[
				'TIMESTAMP',
				['type' => 'datetime', 'length' => null]
			],
			[
				'TIMESTAMP WITHOUT TIME ZONE',
				['type' => 'datetime', 'length' => null]
			],
			[
				'DATE',
				['type' => 'date', 'length' => null]
			],
			[
				'TIME',
				['type' => 'time', 'length' => null]
			],
			[
				'SMALLINT',
				['type' => 'integer', 'length' => 5]
			],
			[
				'INTEGER',
				['type' => 'integer', 'length' => 10]
			],
			[
				'SERIAL',
				['type' => 'integer', 'length' => 10]
			],
			[
				'BIGINT',
				['type' => 'biginteger', 'length' => 20]
			],
			[
				'NUMERIC',
				['type' => 'decimal', 'length' => null]
			],
			[
				'DECIMAL(10,2)',
				['type' => 'decimal', 'length' => null]
			],
			[
				'MONEY',
				['type' => 'decimal', 'length' => null]
			],
			[
				'VARCHAR',
				['type' => 'string', 'length' => null]
			],
			[
				'VARCHAR(10)',
				['type' => 'string', 'length' => 10]
			],
			[
				'CHARACTER VARYING',
				['type' => 'string', 'length' => null]
			],
			[
				'CHARACTER VARYING(10)',
				['type' => 'string', 'length' => 10]
			],
			[
				'CHAR(10)',
				['type' => 'string', 'fixed' => true, 'length' => 10]
			],
			[
				'CHARACTER(10)',
				['type' => 'string', 'fixed' => true, 'length' => 10]
			],
			[
				'UUID',
				['type' => 'string', 'fixed' => true, 'length' => 36]
			],
			[
				'INET',
				['type' => 'string', 'length' => 39]
			],
			[
				'TEXT',
				['type' => 'text', 'length' => null]
			],
			[
				'BYTEA',
				['type' => 'binary', 'length' => null]
			],
			[
				'REAL',
				['type' => 'float', 'length' => null]
			],
			[
				'DOUBLE PRECISION',
				['type' => 'float', 'length' => null]
			],
			[
				'BIGSERIAL',
				['type' => 'biginteger', 'length' => 20]
			],
		];
	}

/**
 * Test parsing Postgres column types from field description.
 *
 * @dataProvider convertColumnProvider
 * @return void
 */
	public function testConvertColumn($type, $expected) {
		$field = [
			'name' => 'field',
			'type' => $type,
			'null' => 'YES',
			'default' => 'Default value',
			'comment' => 'Comment section',
			'char_length' => null,
		];
		$expected += [
			'null' => true,
			'default' => 'Default value',
			'comment' => 'Comment section',
		];

		$driver = $this->getMock('Cake\Database\Driver\Postgres');
		$dialect = new PostgresSchema($driver);

		$table = $this->getMock('Cake\Database\Schema\Table', [], ['table']);
		$table->expects($this->at(0))->method('addColumn')->with('field', $expected);

		$dialect->convertFieldDescription($table, $field);
	}

/**
 * Test listing tables with Postgres
 *
 * @return void
 */
	public function testListTables() {
		$connection = ConnectionManager::get('test');
		$this->_createTables($connection);

		$schema = new SchemaCollection($connection);
		$result = $schema->listTables();
		$this->assertInternalType('array', $result);
		$this->assertCount(2, $result);
		$this->assertEquals('schema_articles', $result[0]);
		$this->assertEquals('schema_authors', $result[1]);
	}

/**
 * Test describing a table with Postgres
 *
 * @return void
 */
	public function testDescribeTable() {
		$connection = ConnectionManager::get('test');
		$this->_createTables($connection);

		$schema = new SchemaCollection($connection);
		$result = $schema->describe('schema_articles');
		$expected = [
			'id' => [
				'type' => 'biginteger',
				'null' => false,
				'default' => null,
				'length' => 20,
				'precision' => null,
				'fixed' => null,
				'comment' => null,
			],
			'title' => [
				'type' => 'string',
				'null' => true,
				'default' => null,
				'length' => 20,
				'precision' => null,
				'comment' => 'a title',
				'fixed' => null,
			],
			'body' => [
				'type' => 'text',
				'null' => true,
				'default' => null,
				'length' => null,
				'precision' => null,
				'fixed' => null,
				'comment' => null,
			],
			'author_id' => [
				'type' => 'integer',
				'null' => false,
				'default' => null,
				'length' => 10,
				'precision' => null,
				'fixed' => null,
				'comment' => null,
			],
			'published' => [
				'type' => 'boolean',
				'null' => true,
				'default' => 0,
				'length' => null,
				'precision' => null,
				'fixed' => null,
				'comment' => null,
			],
			'views' => [
				'type' => 'integer',
				'null' => true,
				'default' => 0,
				'length' => 5,
				'precision' => null,
				'fixed' => null,
				'comment' => null,
			],
			'created' => [
				'type' => 'datetime',
				'null' => true,
				'default' => null,
				'length' => null,
				'precision' => null,
				'fixed' => null,
				'comment' => null,
			],
		];
		$this->assertEquals(['id'], $result->primaryKey());
		foreach ($expected as $field => $definition) {
			$this->assertEquals($definition, $result->column($field));
		}
	}

/**
 * Test describing a table with indexes
 *
 * @return void
 */
	public function testDescribeTableIndexes() {
		$connection = ConnectionManager::get('test');
		$this->_createTables($connection);

		$schema = new SchemaCollection($connection);
		$result = $schema->describe('schema_articles');
		$this->assertInstanceOf('Cake\Database\Schema\Table', $result);
		$expected = [
			'primary' => [
				'type' => 'primary',
				'columns' => ['id'],
				'length' => []
			],
			'content_idx' => [
				'type' => 'unique',
				'columns' => ['title', 'body'],
				'length' => []
			]
		];
		$this->assertCount(3, $result->constraints());
		$expected = [
			'primary' => [
				'type' => 'primary',
				'columns' => ['id'],
				'length' => []
			],
			'content_idx' => [
				'type' => 'unique',
				'columns' => ['title', 'body'],
				'length' => []
			],
			'author_idx' => [
				'type' => 'foreign',
				'columns' => ['author_id'],
				'references' => ['schema_authors', 'id'],
				'length' => [],
				'update' => 'cascade',
				'delete' => 'restrict',
			]
		];
		$this->assertEquals($expected['primary'], $result->constraint('primary'));
		$this->assertEquals($expected['content_idx'], $result->constraint('content_idx'));
		$this->assertEquals($expected['author_idx'], $result->constraint('author_idx'));

		$this->assertCount(1, $result->indexes());
		$expected = [
			'type' => 'index',
			'columns' => ['author_id'],
			'length' => []
		];
		$this->assertEquals($expected, $result->index('author_idx'));
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
				'"title" VARCHAR(25) NOT NULL'
			],
			[
				'title',
				['type' => 'string', 'length' => 25, 'null' => true, 'default' => 'ignored'],
				'"title" VARCHAR(25) DEFAULT NULL'
			],
			[
				'id',
				['type' => 'string', 'length' => 32, 'fixed' => true, 'null' => false],
				'"id" CHAR(32) NOT NULL'
			],
			[
				'id',
				['type' => 'string', 'length' => 36, 'fixed' => true, 'null' => false],
				'"id" UUID NOT NULL'
			],
			[
				'role',
				['type' => 'string', 'length' => 10, 'null' => false, 'default' => 'admin'],
				'"role" VARCHAR(10) NOT NULL DEFAULT "admin"'
			],
			[
				'title',
				['type' => 'string'],
				'"title" VARCHAR'
			],
			// Text
			[
				'body',
				['type' => 'text', 'null' => false],
				'"body" TEXT NOT NULL'
			],
			// Integers
			[
				'post_id',
				['type' => 'integer', 'length' => 11],
				'"post_id" INTEGER'
			],
			[
				'post_id',
				['type' => 'biginteger', 'length' => 20],
				'"post_id" BIGINT'
			],
			// Decimal
			[
				'value',
				['type' => 'decimal'],
				'"value" DECIMAL'
			],
			[
				'value',
				['type' => 'decimal', 'length' => 11],
				'"value" DECIMAL(11,0)'
			],
			[
				'value',
				['type' => 'decimal', 'length' => 12, 'precision' => 5],
				'"value" DECIMAL(12,5)'
			],
			// Float
			[
				'value',
				['type' => 'float'],
				'"value" FLOAT'
			],
			[
				'value',
				['type' => 'float', 'length' => 11, 'precision' => 3],
				'"value" FLOAT(3)'
			],
			// Binary
			[
				'img',
				['type' => 'binary'],
				'"img" BYTEA'
			],
			// Boolean
			[
				'checked',
				['type' => 'boolean', 'default' => false],
				'"checked" BOOLEAN DEFAULT FALSE'
			],
			[
				'checked',
				['type' => 'boolean', 'default' => true, 'null' => false],
				'"checked" BOOLEAN NOT NULL DEFAULT TRUE'
			],
			// datetimes
			[
				'created',
				['type' => 'datetime'],
				'"created" TIMESTAMP'
			],
			// Date & Time
			[
				'start_date',
				['type' => 'date'],
				'"start_date" DATE'
			],
			[
				'start_time',
				['type' => 'time'],
				'"start_time" TIME'
			],
			// timestamps
			[
				'created',
				['type' => 'timestamp', 'null' => true],
				'"created" TIMESTAMP DEFAULT NULL'
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
		$driver = $this->_getMockedDriver();
		$schema = new PostgresSchema($driver);

		$table = (new Table('schema_articles'))->addColumn($name, $data);
		$this->assertEquals($expected, $schema->columnSql($table, $name));
	}

/**
 * Test generating a column that is a primary key.
 *
 * @return void
 */
	public function testColumnSqlPrimaryKey() {
		$driver = $this->_getMockedDriver();
		$schema = new PostgresSchema($driver);

		$table = new Table('schema_articles');
		$table->addColumn('id', [
				'type' => 'integer',
				'null' => false
			])
			->addConstraint('primary', [
				'type' => 'primary',
				'columns' => ['id']
			]);
		$result = $schema->columnSql($table, 'id');
		$this->assertEquals($result, '"id" SERIAL');
	}

/**
 * Provide data for testing constraintSql
 *
 * @return array
 */
	public static function constraintSqlProvider() {
		return [
			[
				'primary',
				['type' => 'primary', 'columns' => ['title']],
				'PRIMARY KEY ("title")'
			],
			[
				'unique_idx',
				['type' => 'unique', 'columns' => ['title', 'author_id']],
				'CONSTRAINT "unique_idx" UNIQUE ("title", "author_id")'
			],
			[
				'author_id_idx',
				['type' => 'foreign', 'columns' => ['author_id'], 'references' => ['authors', 'id']],
				'CONSTRAINT "author_id_idx" FOREIGN KEY ("author_id") ' .
				'REFERENCES "authors" ("id") ON UPDATE RESTRICT ON DELETE RESTRICT'
			],
			[
				'author_id_idx',
				['type' => 'foreign', 'columns' => ['author_id'], 'references' => ['authors', 'id'], 'update' => 'cascade'],
				'CONSTRAINT "author_id_idx" FOREIGN KEY ("author_id") ' .
				'REFERENCES "authors" ("id") ON UPDATE CASCADE ON DELETE RESTRICT'
			],
			[
				'author_id_idx',
				['type' => 'foreign', 'columns' => ['author_id'], 'references' => ['authors', 'id'], 'update' => 'restrict'],
				'CONSTRAINT "author_id_idx" FOREIGN KEY ("author_id") ' .
				'REFERENCES "authors" ("id") ON UPDATE RESTRICT ON DELETE RESTRICT'
			],
			[
				'author_id_idx',
				['type' => 'foreign', 'columns' => ['author_id'], 'references' => ['authors', 'id'], 'update' => 'setNull'],
				'CONSTRAINT "author_id_idx" FOREIGN KEY ("author_id") ' .
				'REFERENCES "authors" ("id") ON UPDATE SET NULL ON DELETE RESTRICT'
			],
			[
				'author_id_idx',
				['type' => 'foreign', 'columns' => ['author_id'], 'references' => ['authors', 'id'], 'update' => 'noAction'],
				'CONSTRAINT "author_id_idx" FOREIGN KEY ("author_id") ' .
				'REFERENCES "authors" ("id") ON UPDATE NO ACTION ON DELETE RESTRICT'
			],
		];
	}

/**
 * Test the constraintSql method.
 *
 * @dataProvider constraintSqlProvider
 */
	public function testConstraintSql($name, $data, $expected) {
		$driver = $this->_getMockedDriver();
		$schema = new PostgresSchema($driver);

		$table = (new Table('schema_articles'))->addColumn('title', [
			'type' => 'string',
			'length' => 255
		])->addColumn('author_id', [
			'type' => 'integer',
		])->addConstraint($name, $data);

		$this->assertEquals($expected, $schema->constraintSql($table, $name));
	}

/**
 * Integration test for converting a Schema\Table into MySQL table creates.
 *
 * @return void
 */
	public function testCreateSql() {
		$driver = $this->_getMockedDriver();
		$connection = $this->getMock('Cake\Database\Connection', [], [], '', false);
		$connection->expects($this->any())->method('driver')
			->will($this->returnValue($driver));

		$table = (new Table('schema_articles'))->addColumn('id', [
				'type' => 'integer',
				'null' => false
			])
			->addColumn('title', [
				'type' => 'string',
				'null' => false,
				'comment' => 'This is the title',
			])
			->addColumn('body', ['type' => 'text'])
			->addColumn('created', 'datetime')
			->addConstraint('primary', [
				'type' => 'primary',
				'columns' => ['id'],
			])
			->addIndex('title_idx', [
				'type' => 'index',
				'columns' => ['title'],
			]);

		$expected = <<<SQL
CREATE TABLE "schema_articles" (
"id" SERIAL,
"title" VARCHAR NOT NULL,
"body" TEXT,
"created" TIMESTAMP,
PRIMARY KEY ("id")
)
SQL;
		$result = $table->createSql($connection);

		$this->assertCount(3, $result);
		$this->assertEquals($expected, $result[0]);
		$this->assertEquals(
			'CREATE INDEX "title_idx" ON "schema_articles" ("title")',
			$result[1]
		);
		$this->assertEquals(
			'COMMENT ON COLUMN "schema_articles"."title" IS "This is the title"',
			$result[2]
		);
	}

/**
 * test dropSql
 *
 * @return void
 */
	public function testDropSql() {
		$driver = $this->_getMockedDriver();
		$connection = $this->getMock('Cake\Database\Connection', [], [], '', false);
		$connection->expects($this->any())->method('driver')
			->will($this->returnValue($driver));

		$table = new Table('schema_articles');
		$result = $table->dropSql($connection);
		$this->assertCount(1, $result);
		$this->assertEquals('DROP TABLE "schema_articles"', $result[0]);
	}

/**
 * Test truncateSql()
 *
 * @return void
 */
	public function testTruncateSql() {
		$driver = $this->_getMockedDriver();
		$connection = $this->getMock('Cake\Database\Connection', [], [], '', false);
		$connection->expects($this->any())->method('driver')
			->will($this->returnValue($driver));

		$table = new Table('schema_articles');
		$table->addColumn('id', 'integer')
			->addConstraint('primary', [
				'type' => 'primary',
				'columns' => ['id']
			]);
		$result = $table->truncateSql($connection);
		$this->assertCount(1, $result);
		$this->assertEquals('TRUNCATE "schema_articles" RESTART IDENTITY', $result[0]);
	}

/**
 * Get a schema instance with a mocked driver/pdo instances
 *
 * @return Driver
 */
	protected function _getMockedDriver() {
		$driver = new \Cake\Database\Driver\Postgres();
		$mock = $this->getMock('FakePdo', ['quote', 'quoteIdentifier']);
		$mock->expects($this->any())
			->method('quote')
			->will($this->returnCallback(function ($value) {
				return '"' . $value . '"';
			}));
		$mock->expects($this->any())
			->method('quoteIdentifier')
			->will($this->returnCallback(function ($value) {
				return '"' . $value . '"';
			}));
		$driver->connection($mock);
		return $driver;
	}

}
