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
use Cake\Database\Schema\PostgresSchema;
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
		$config = Configure::read('Datasource.test');
		$this->skipIf(strpos($config['datasource'], 'Postgres') === false, 'Not using Postgres for test config');
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
id SERIAL,
name VARCHAR(50),
bio DATE,
created TIMESTAMP
)
SQL;
		$connection->execute($table);

		$table = <<<SQL
CREATE TABLE articles(
id BIGINT PRIMARY KEY,
title VARCHAR(20),
body TEXT,
author_id INTEGER NOT NULL,
published BOOLEAN DEFAULT false,
views SMALLINT DEFAULT 0,
created TIMESTAMP
)
SQL;
		$connection->execute($table);
		$connection->execute('COMMENT ON COLUMN "articles"."title" IS \'a title\'');
	}

/**
 * Dataprovider for column testing
 *
 * @return array
 */
	public static function columnProvider() {
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
 * Test parsing Postgres column types.
 *
 * @dataProvider columnProvider
 * @return void
 */
	public function testConvertColumnType($input, $expected) {
		$driver = $this->getMock('Cake\Database\Driver\Postgres');
		$dialect = new PostgresSchema($driver);
		$this->assertEquals($expected, $dialect->convertColumn($input));
	}

/**
 * Test listing tables with Postgres
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
 * Test describing a table with Postgres
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
				'collate' => null,
			],
		];
		$this->assertEquals(['id'], $result->primaryKey());
		foreach ($expected as $field => $definition) {
			$this->assertEquals($definition, $result->column($field));
		}
	}
}
