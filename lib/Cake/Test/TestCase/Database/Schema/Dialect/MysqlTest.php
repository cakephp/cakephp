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
namespace Cake\Test\TestCase\Database\Schema\Dialect;

use Cake\Core\Configure;
use Cake\Database\Connection;
use Cake\Database\Schema\Collection as SchemaCollection;
use Cake\Database\Schema\Dialect\Mysql;
use Cake\Database\Schema\Driver\Mysql as MysqlDriver;
use Cake\TestSuite\TestCase;


/**
 * Test case for Mysql Schema Dialect.
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
				'INTEGER(11)',
				['integer', 11]
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
		$driver = $this->getMock('Cake\Database\Driver\Mysql');
		$dialect = new Mysql($driver);
		$this->assertEquals($expected, $dialect->convertColumn($input));
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
		$driver = $this->getMock('Cake\Database\Driver\Mysql');
		$dialect = new Mysql($driver);
		$this->assertEquals($expected, $dialect->convertIndex($input));
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
		foreach ($expected as $field => $definition) {
			$this->assertEquals($definition, $result->column($field));
		}
	}

}
