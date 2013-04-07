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
use Cake\Database\Connection;
use Cake\Database\Driver\Postgres;
use \PDO;

/**
 * Tests Postgres driver
 *
 */
class PostgresTest extends \Cake\TestSuite\TestCase {

/**
 * Test connecting to Postgres with default configuration
 *
 * @return void
 */
	public function testConnectionConfigDefault() {
		$driver = $this->getMock('Cake\Database\driver\Postgres', ['_connect', 'connection']);
		$expected = [
			'persistent' => true,
			'host' => 'localhost',
			'login' => 'root',
			'password' => '',
			'database' => 'cake',
			'schema' => 'public',
			'port' => 5432,
			'encoding' => 'utf8',
			'timezone' => 'UTC',
			'flags' => [],
			'init' => [],
			'dsn' => 'pgsql:host=localhost;port=5432;dbname=cake'
		];

		$expected['flags'] += [
			PDO::ATTR_PERSISTENT => true,
			PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
		];

		$connection = $this->getMock('stdClass', ['exec', 'quote']);
		$connection->expects($this->any())
			->method('quote')
			->will($this->onConsecutiveCalls(
				$this->returnArgument(0),
				$this->returnArgument(0),
				$this->returnArgument(0)
			));

		$connection->expects($this->at(1))->method('exec')->with('SET NAMES utf8');
		$connection->expects($this->at(3))->method('exec')->with('SET search_path TO public');
		$connection->expects($this->at(5))->method('exec')->with('SET timezone = UTC');
		$connection->expects($this->exactly(3))->method('exec');

		$driver->expects($this->once())->method('_connect')
			->with($expected);
		$driver->expects($this->any())->method('connection')
			->will($this->returnValue($connection));

		$driver->connect([]);
	}

/**
 * Test connecting to Postgres with custom configuration
 *
 * @return void
 */
	public function testConnectionConfigCustom() {
		$driver = $this->getMock('Cake\Database\driver\Postgres', ['_connect', 'connection']);
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
			'schema' => 'fooblic',
			'init' => ['Execute this', 'this too']
		];

		$expected = $config;
		$expected['dsn'] = 'pgsql:host=foo;port=3440;dbname=bar';
		$expected['flags'] += [
			PDO::ATTR_PERSISTENT => false,
			PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
		];

		$connection = $this->getMock('stdClass', ['exec', 'quote']);
		$connection->expects($this->any())
			->method('quote')
			->will($this->onConsecutiveCalls(
				$this->returnArgument(0),
				$this->returnArgument(0),
				$this->returnArgument(0)
			));

		$connection->expects($this->at(1))->method('exec')->with('SET NAMES a-language');
		$connection->expects($this->at(3))->method('exec')->with('SET search_path TO fooblic');
		$connection->expects($this->at(5))->method('exec')->with('Execute this');
		$connection->expects($this->at(6))->method('exec')->with('this too');
		$connection->expects($this->at(7))->method('exec')->with('SET timezone = Antartica');
		$connection->expects($this->exactly(5))->method('exec');

		$driver->expects($this->once())->method('_connect')
			->with($expected);
		$driver->expects($this->any())->method('connection')
			->will($this->returnValue($connection));

		$driver->connect($config);
	}

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
				['datetime', null]
			],
			[
				'TIMESTAMP WITHOUT TIME ZONE',
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
				'SMALLINT',
				['integer', 5]
			],
			[
				'INTEGER',
				['integer', 10]
			],
			[
				'SERIAL',
				['integer', 10]
			],
			[
				'BIGINT',
				['biginteger', 20]
			],
			[
				'NUMERIC',
				['decimal', null]
			],
			[
				'DECIMAL(10,2)',
				['decimal', null]
			],
			[
				'MONEY',
				['decimal', null]
			],
			[
				'VARCHAR',
				['string', null]
			],
			[
				'CHARACTER VARYING',
				['string', null]
			],
			[
				'CHAR',
				['string', null]
			],
			[
				'UUID',
				['string', 36]
			],
			[
				'CHARACTER',
				['string', null]
			],
			[
				'INET',
				['string', 39]
			],
			[
				'TEXT',
				['text', null]
			],
			[
				'BYTEA',
				['binary', null]
			],
			[
				'REAL',
				['float', null]
			],
			[
				'DOUBLE PRECISION',
				['float', null]
			],
			[
				'BIGSERIAL',
				['biginteger', 20]
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
		$driver = new Postgres();
		$this->assertEquals($expected, $driver->convertColumn($input));
	}

/**
 * Test listing tables with Postgres
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
 * Test describing a table with Postgres
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
				'comment' => 'a title',
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
				'length' => 10,
			],
			'published' => [
				'type' => 'boolean',
				'null' => true,
				'default' => 0,
				'length' => null,
			],
			'views' => [
				'type' => 'integer',
				'null' => true,
				'default' => 0,
				'length' => 5,
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
