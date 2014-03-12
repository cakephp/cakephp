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
namespace Cake\Test\TestCase\ORM;

use Cake\Core\Configure;
use Cake\Datasource\ConnectionManager;
use Cake\ORM\Table;
use Cake\ORM\TableRegistry;
use Cake\TestSuite\TestCase;

/**
 * Used to test correct class is instantiated when using TableRegistry::get();
 */
class MyUsersTable extends Table {

/**
 * Overrides default table name
 *
 * @var string
 */
	protected $_table = 'users';

}


/**
 * Test case for TableRegistry
 */
class TableRegistryTest extends TestCase {

/**
 * setup
 *
 * @return void
 */
	public function setUp() {
		parent::setUp();
		Configure::write('App.namespace', 'TestApp');
	}

/**
 * tear down
 *
 * @return void
 */
	public function tearDown() {
		parent::tearDown();
		TableRegistry::clear();
	}

/**
 * Test config() method.
 *
 * @return void
 */
	public function testConfig() {
		$this->assertEquals([], TableRegistry::config('Tests'));

		$data = [
			'connection' => 'testing',
			'entityClass' => 'TestApp\Model\Entity\Article',
		];
		$result = TableRegistry::config('Tests', $data);
		$this->assertEquals($data, $result, 'Returns config data.');

		$result = TableRegistry::config();
		$expected = ['Tests' => $data];
		$this->assertEquals($expected, $result);
	}

/**
 * Test calling config() on existing instances throws an error.
 *
 * @expectedException RuntimeException
 * @expectedExceptionMessage You cannot configure "Users", it has already been constructed.
 * @return void
 */
	public function testConfigOnDefinedInstance() {
		$users = TableRegistry::get('Users');
		TableRegistry::config('Users', ['table' => 'my_users']);
	}

/**
 * Test the exists() method.
 *
 * @return void
 */
	public function testExists() {
		$this->assertFalse(TableRegistry::exists('Articles'));

		TableRegistry::config('Articles', ['table' => 'articles']);
		$this->assertFalse(TableRegistry::exists('Articles'));

		TableRegistry::get('Articles', ['table' => 'articles']);
		$this->assertTrue(TableRegistry::exists('Articles'));
	}

/**
 * Test getting instances from the registry.
 *
 * @return void
 */
	public function testGet() {
		$result = TableRegistry::get('Articles', [
			'table' => 'my_articles',
		]);
		$this->assertInstanceOf('Cake\ORM\Table', $result);
		$this->assertEquals('my_articles', $result->table());

		$result2 = TableRegistry::get('Articles');
		$this->assertSame($result, $result2);
		$this->assertEquals('my_articles', $result->table());
	}

/**
 * Test that get() uses config data set with config()
 *
 * @return void
 */
	public function testGetWithConfig() {
		TableRegistry::config('Articles', [
			'table' => 'my_articles',
		]);
		$result = TableRegistry::get('Articles');
		$this->assertEquals('my_articles', $result->table(), 'Should use config() data.');
	}

/**
 * Test get with config throws an exception if the alias exists already.
 *
 * @expectedException RuntimeException
 * @expectedExceptionMessage You cannot configure "Users", it already exists in the registry.
 * @return void
 */
	public function testGetExistingWithConfigData() {
		$users = TableRegistry::get('Users');
		TableRegistry::get('Users', ['table' => 'my_users']);
	}

/**
 * Tests that tables can be instantiated based on conventions
 * and using plugin notation
 *
 * @return void
 */
	public function testBuildConvention() {
		$table = TableRegistry::get('articles');
		$this->assertInstanceOf('\TestApp\Model\Table\ArticlesTable', $table);
		$table = TableRegistry::get('Articles');
		$this->assertInstanceOf('\TestApp\Model\Table\ArticlesTable', $table);

		$table = TableRegistry::get('authors');
		$this->assertInstanceOf('\TestApp\Model\Table\AuthorsTable', $table);
		$table = TableRegistry::get('Authors');
		$this->assertInstanceOf('\TestApp\Model\Table\AuthorsTable', $table);

		$class = $this->getMockClass('\Cake\ORM\Table');

		if (!class_exists('MyPlugin\Model\Table\SuperTestsTable')) {
			class_alias($class, 'MyPlugin\Model\Table\SuperTestsTable');
		}

		$table = TableRegistry::get('MyPlugin.SuperTests', ['connection' => 'test']);
		$this->assertInstanceOf($class, $table);
	}

/**
 * Tests that table options can be pre-configured for the factory method
 *
 * @return void
 */
	public function testConfigAndBuild() {
		TableRegistry::clear();
		$map = TableRegistry::config();
		$this->assertEquals([], $map);

		$connection = ConnectionManager::get('test', false);
		$options = ['connection' => $connection];
		TableRegistry::config('users', $options);
		$map = TableRegistry::config();
		$this->assertEquals(['users' => $options], $map);
		$this->assertEquals($options, TableRegistry::config('users'));

		$schema = ['id' => ['type' => 'rubbish']];
		$options += ['schema' => $schema];
		TableRegistry::config('users', $options);

		$table = TableRegistry::get('users', ['table' => 'users']);
		$this->assertInstanceOf('Cake\ORM\Table', $table);
		$this->assertEquals('users', $table->table());
		$this->assertEquals('users', $table->alias());
		$this->assertSame($connection, $table->connection());
		$this->assertEquals(array_keys($schema), $table->schema()->columns());
		$this->assertEquals($schema['id']['type'], $table->schema()->column('id')['type']);

		TableRegistry::clear();
		$this->assertEmpty(TableRegistry::config());

		TableRegistry::config('users', $options);
		$table = TableRegistry::get('users', ['className' => __NAMESPACE__ . '\MyUsersTable']);
		$this->assertInstanceOf(__NAMESPACE__ . '\MyUsersTable', $table);
		$this->assertEquals('users', $table->table());
		$this->assertEquals('users', $table->alias());
		$this->assertSame($connection, $table->connection());
		$this->assertEquals(array_keys($schema), $table->schema()->columns());
	}

/**
 * Test setting an instance.
 *
 * @return void
 */
	public function testSet() {
		$mock = $this->getMock('Cake\ORM\Table');
		$this->assertSame($mock, TableRegistry::set('Articles', $mock));
		$this->assertSame($mock, TableRegistry::get('Articles'));
	}

}
