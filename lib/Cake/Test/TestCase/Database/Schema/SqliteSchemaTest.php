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
use Cake\Database\Schema\SqliteSchema;
use Cake\Database\Schema\Table;
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
		$config = ConnectionManager::config('test');
		$this->skipIf(strpos($config['className'], 'Sqlite') === false, 'Not using Sqlite for test config');
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
				['type' => 'date', 'length' => null]
			],
			[
				'TIME',
				['type' => 'time', 'length' => null]
			],
			[
				'BOOLEAN',
				['type' => 'boolean', 'length' => null]
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
				['type' => 'string', 'fixed' => true, 'length' => 25]
			],
			[
				'BLOB',
				['type' => 'binary', 'length' => null]
			],
			[
				'INTEGER(11)',
				['type' => 'integer', 'length' => 11]
			],
			[
				'TINYINT(5)',
				['type' => 'integer', 'length' => 5]
			],
			[
				'MEDIUMINT(10)',
				['type' => 'integer', 'length' => 10]
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
				'REAL',
				['type' => 'float', 'length' => null]
			],
			[
				'DECIMAL(11,2)',
				['type' => 'decimal', 'length' => null]
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
		$connection->execute('DROP TABLE IF EXISTS schema_articles');
		$connection->execute('DROP TABLE IF EXISTS schema_authors');

		$table = <<<SQL
CREATE TABLE schema_authors (
id INTEGER PRIMARY KEY AUTOINCREMENT,
name VARCHAR(50),
bio TEXT,
created DATETIME
)
SQL;
		$connection->execute($table);

		$table = <<<SQL
CREATE TABLE schema_articles (
id INTEGER PRIMARY KEY AUTOINCREMENT,
title VARCHAR(20) DEFAULT 'testing',
body TEXT,
author_id INT(11) NOT NULL,
published BOOLEAN DEFAULT 0,
created DATETIME,
CONSTRAINT "title_idx" UNIQUE ("title", "body")
CONSTRAINT "author_idx" FOREIGN KEY ("author_id") REFERENCES "schema_authors" ("id") ON UPDATE CASCADE ON DELETE RESTRICT
);
SQL;
		$connection->execute($table);
		$connection->execute('CREATE INDEX "created_idx" ON "schema_articles" ("created")');
	}

/**
 * Test SchemaCollection listing tables with Sqlite
 *
 * @return void
 */
	public function testListTables() {
		$connection = ConnectionManager::get('test');
		$this->_createTables($connection);

		$schema = new SchemaCollection($connection);
		$result = $schema->listTables();

		$this->assertInternalType('array', $result);
		$this->assertCount(3, $result);
		$this->assertEquals('schema_articles', $result[0]);
		$this->assertEquals('schema_authors', $result[1]);
		$this->assertEquals('sqlite_sequence', $result[2]);
	}

/**
 * Test describing a table with Sqlite
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
				'type' => 'integer',
				'null' => false,
				'default' => null,
				'length' => null,
				'precision' => null,
				'fixed' => null,
				'comment' => null,
			],
			'title' => [
				'type' => 'string',
				'null' => true,
				'default' => 'testing',
				'length' => 20,
				'precision' => null,
				'fixed' => null,
				'comment' => null,
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
				'length' => 11,
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
		$this->assertInstanceOf('Cake\Database\Schema\Table', $result);
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
			'sqlite_autoindex_schema_articles_1' => [
				'type' => 'unique',
				'columns' => ['title', 'body'],
				'length' => []
			],
			'author_id_fk' => [
				'type' => 'foreign',
				'columns' => ['author_id'],
				'references' => ['schema_authors', 'id'],
				'length' => [],
				'update' => 'cascade',
				'delete' => 'restrict',
			]
		];
		$this->assertCount(3, $result->constraints());
		$this->assertEquals($expected['primary'], $result->constraint('primary'));
		$this->assertEquals(
			$expected['sqlite_autoindex_schema_articles_1'],
			$result->constraint('sqlite_autoindex_schema_articles_1')
		);
		$this->assertEquals(
			$expected['author_id_fk'],
			$result->constraint('author_id_fk')
		);

		$this->assertCount(1, $result->indexes());
		$expected = [
			'type' => 'index',
			'columns' => ['created'],
			'length' => []
		];
		$this->assertEquals($expected, $result->index('created_idx'));
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
				'"id" VARCHAR(32) NOT NULL'
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
				'"post_id" INTEGER(11)'
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
				'"value" FLOAT(11,3)'
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
				'"created" DATETIME'
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
		$schema = new SqliteSchema($driver);

		$table = (new Table('articles'))->addColumn($name, $data);
		$this->assertEquals($expected, $schema->columnSql($table, $name));
	}

/**
 * Test generating a column that is a primary key.
 *
 * @return void
 */
	public function testColumnSqlPrimaryKey() {
		$driver = $this->_getMockedDriver();
		$schema = new SqliteSchema($driver);

		$table = new Table('articles');
		$table->addColumn('id', [
				'type' => 'integer',
				'null' => false
			])
			->addConstraint('primary', [
				'type' => 'primary',
				'columns' => ['id']
			]);
		$result = $schema->columnSql($table, 'id');
		$this->assertEquals($result, '"id" INTEGER NOT NULL PRIMARY KEY AUTOINCREMENT');

		$result = $schema->constraintSql($table, 'primary');
		$this->assertEquals('', $result, 'Integer primary keys are special in sqlite.');
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
				'CONSTRAINT "primary" PRIMARY KEY ("title")'
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
		$schema = new SqliteSchema($driver);

		$table = (new Table('articles'))->addColumn('title', [
			'type' => 'string',
			'length' => 255
		])->addColumn('author_id', [
			'type' => 'integer',
		])->addConstraint($name, $data);

		$this->assertEquals($expected, $schema->constraintSql($table, $name));
	}

/**
 * Provide data for testing indexSql
 *
 * @return array
 */
	public static function indexSqlProvider() {
		return [
			[
				'author_idx',
				['type' => 'index', 'columns' => ['title', 'author_id']],
				'CREATE INDEX "author_idx" ON "articles" ("title", "author_id")'
			],
		];
	}

/**
 * Test the indexSql method.
 *
 * @dataProvider indexSqlProvider
 */
	public function testIndexSql($name, $data, $expected) {
		$driver = $this->_getMockedDriver();
		$schema = new SqliteSchema($driver);

		$table = (new Table('articles'))->addColumn('title', [
			'type' => 'string',
			'length' => 255
		])->addColumn('author_id', [
			'type' => 'integer',
		])->addIndex($name, $data);

		$this->assertEquals($expected, $schema->indexSql($table, $name));
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

		$table = (new Table('articles'))->addColumn('id', [
				'type' => 'integer',
				'null' => false
			])
			->addColumn('title', [
				'type' => 'string',
				'null' => false,
			])
			->addColumn('body', ['type' => 'text'])
			->addColumn('created', 'datetime')
			->addConstraint('primary', [
				'type' => 'primary',
				'columns' => ['id']
			])
			->addIndex('title_idx', [
				'type' => 'index',
				'columns' => ['title']
			]);

		$expected = <<<SQL
CREATE TABLE "articles" (
"id" INTEGER NOT NULL PRIMARY KEY AUTOINCREMENT,
"title" VARCHAR NOT NULL,
"body" TEXT,
"created" DATETIME
)
SQL;
		$result = $table->createSql($connection);
		$this->assertCount(2, $result);
		$this->assertEquals($expected, $result[0]);
		$this->assertEquals(
			'CREATE INDEX "title_idx" ON "articles" ("title")',
			$result[1]
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

		$table = new Table('articles');
		$result = $table->dropSql($connection);
		$this->assertCount(1, $result);
		$this->assertEquals('DROP TABLE "articles"', $result[0]);
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

		$table = new Table('articles');
		$result = $table->truncateSql($connection);
		$this->assertCount(2, $result);
		$this->assertEquals('DELETE FROM sqlite_sequence WHERE name="articles"', $result[0]);
		$this->assertEquals('DELETE FROM "articles"', $result[1]);
	}

/**
 * Get a schema instance with a mocked driver/pdo instances
 *
 * @return Driver
 */
	protected function _getMockedDriver() {
		$driver = new \Cake\Database\Driver\Sqlite();
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
