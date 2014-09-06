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
namespace Cake\Test\TestCase\Database\Schema;

use Cake\Database\Expression\TableNameExpression;
use Cake\Database\Schema\SqliteSchema;
use Cake\Database\Schema\Table;
use Cake\Datasource\ConnectionManager;
use Cake\TestSuite\TestCase;

/**
 * Test case for Mysql Schema Dialect.
 */
class BaseSchemaTest extends TestCase {

/**
 * Tests getting the connection prefix from the current Connection
 *
 * @return void
 */
	public function testGetConnectionPrefix() {
		$driver = $this->_getMockedDriver();
		$dialect = new SqliteSchema($driver);

		$testConnection = ConnectionManager::get('test');
		$config = $testConnection->config();
		$config['prefix'] = '';
		$this->assertEquals('', $dialect->getConnectionPrefix($config));

		unset($config['prefix']);
		$this->assertEquals('', $dialect->getConnectionPrefix($config));

		$config['prefix'] = 'prefix_';
		$this->assertEquals('prefix_', $dialect->getConnectionPrefix($config));
	}

/**
 * Tests get the full table name
 *
 * @return void
 */
	public function testGetFullTableName() {
		$driver = $this->_getMockedDriver();
		$dialect = new SqliteSchema($driver);
		$tableName = 'foo';

		$testConnection = ConnectionManager::get('test');
		$config = $testConnection->config();

		$expression = new TableNameExpression($tableName, '');

		$config['prefix'] = '';
		$this->assertEquals('"foo"', $dialect->getFullTableName($tableName, $config));
		$this->assertEquals('foo', $dialect->getFullTableName($tableName, $config, false));
		$this->assertEquals('"foo"', $dialect->getFullTableName($expression, $config));
		$this->assertEquals('foo', $dialect->getFullTableName($expression, $config, false));

		$config['prefix'] = 'prefix_';
		$expression->setPrefix($config['prefix']);
		$this->assertEquals('"prefix_foo"', $dialect->getFullTableName($tableName, $config));
		$this->assertEquals('prefix_foo', $dialect->getFullTableName($tableName, $config, false));
		$this->assertEquals('"prefix_foo"', $dialect->getFullTableName($expression, $config));
		$this->assertEquals('prefix_foo', $dialect->getFullTableName($expression, $config, false));
	}

/**
 * Get a schema instance with a mocked driver/pdo instances
 *
 * @return Driver
 */
	protected function _getMockedDriver() {
		$driver = new \Cake\Database\Driver\Sqlite();
		$mock = $this->getMock('FakePdo', ['quote', 'quoteIdentifier', 'prepare']);
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
