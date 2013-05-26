<?php
/**
 * CakePHP(tm) Tests <http://book.cakephp.org/2.0/en/development/testing.html>
 * Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://book.cakephp.org/2.0/en/development/testing.html CakePHP(tm) Tests
 * @since         CakePHP(tm) v 1.2.0.4667
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
namespace Cake\Test\TestCase\TestSuite;

use Cake\Core\Configure;
use Cake\Model\ConnectionManager;
use Cake\Model\Model;
use Cake\TestSuite\Fixture\TestFixture;
use Cake\TestSuite\TestCase;
use Cake\Utility\ClassRegistry;

/**
 * ArticleFixture class
 *
 * @package       Cake.Test.TestCase.TestSuite
 */
class ArticleFixture extends TestFixture {

/**
 * Table property
 *
 * @var string
 */
	public $table = 'articles';

/**
 * Fields array
 *
 * @var array
 */
	public $fields = [
		'id' => ['type' => 'integer', 'key' => 'primary'],
		'name' => ['type' => 'string', 'length' => '255'],
		'created' => ['type' => 'datetime'],
		'constraints' => [
			'primary' => ['type' => 'primary', 'columns' => ['id']]
		]
	];

/**
 * Records property
 *
 * @var array
 */
	public $records = array(
		array('name' => 'Gandalf', 'created' => '2009-04-28 19:20:00'),
		array('name' => 'Captain Picard', 'created' => '2009-04-28 19:20:00'),
		array('name' => 'Chewbacca', 'created' => '2009-04-28 19:20:00')
	);
}

/**
 * StringFieldsTestFixture class
 *
 * @package       Cake.Test.Case.TestSuite
 * @subpackage    cake.cake.tests.cases.libs
 */
class StringsTestFixture extends TestFixture {

/**
 * Table property
 *
 * @var string
 */
	public $table = 'strings';

/**
 * Fields array
 *
 * @var array
 */
	public $fields = array(
		'id' => array('type' => 'integer', 'key' => 'primary'),
		'name' => array('type' => 'string', 'length' => '255'),
		'email' => array('type' => 'string', 'length' => '255'),
		'age' => array('type' => 'integer', 'default' => 10)
	);

/**
 * Records property
 *
 * @var array
 */
	public $records = array(
		array('name' => 'Mark Doe', 'email' => 'mark.doe@email.com'),
		array('name' => 'John Doe', 'email' => 'john.doe@email.com', 'age' => 20),
		array('email' => 'jane.doe@email.com', 'name' => 'Jane Doe', 'age' => 30)
	);
}


/**
 * ImportFixture class
 *
 * @package       Cake.Test.Case.TestSuite
 */
class ImportFixture extends TestFixture {

/**
 * Import property
 *
 * @var mixed
 */
	public $import = ['table' => 'posts', 'connection' => 'test'];
}

/**
 * Test case for TestFixture
 *
 * @package       Cake.Test.Case.TestSuite
 */
class TestFixtureTest extends TestCase {

/**
 * Fixtures for this test.
 *
 * @var array
 */
	public $fixtures = ['core.post'];

/**
 * test initializing a static fixture
 *
 * @return void
 */
	public function testInitStaticFixture() {
		$Fixture = new ArticleFixture();
		$this->assertEquals('articles', $Fixture->table);

		$Fixture = new ArticleFixture();
		$Fixture->table = null;
		$Fixture->init();
		$this->assertEquals('articles', $Fixture->table);

		$schema = $Fixture->schema();
		$this->assertInstanceOf('Cake\Database\Schema\Table', $schema);

		$fields = $Fixture->fields;
		unset($fields['constraints'], $fields['indexes']);
		$this->assertEquals(
			array_keys($fields),
			$schema->columns(),
			'Fields do not match'
		);
		$this->assertEquals(array_keys($Fixture->fields['constraints']), $schema->constraints());
		$this->assertEmpty($schema->indexes());
	}

/**
 * test initializing an import fixture.
 *
 * @return void
 */
	public function testInitImportFixture() {
		$this->markTestIncomplete('not done');
	}

/**
 * test that init() correctly sets the fixture table when the connection
 * or model have prefixes defined.
 *
 * @return void
 */
	public function testInitDbPrefix() {
		$this->markTestSkipped('Skipped for now as table prefixes need to be re-worked.');

		$db = ConnectionManager::getDataSource('test');
		$Source = new TestFixtureTestFixture();
		$Source->drop($db);
		$Source->create($db);
		$Source->insert($db);

		$Fixture = new TestFixtureTestFixture();
		$expected = array('id', 'name', 'created');
		$this->assertEquals($expected, array_keys($Fixture->fields));

		$config = $db->config;
		$config['prefix'] = 'fixture_test_suite_';
		ConnectionManager::create('fixture_test_suite', $config);

		$Fixture->fields = $Fixture->records = null;
		$Fixture->import = array('table' => 'fixture_tests', 'connection' => 'test', 'records' => true);
		$Fixture->init();
		$this->assertEquals(count($Fixture->records), count($Source->records));
		$Fixture->create(ConnectionManager::getDataSource('fixture_test_suite'));

		$Fixture = new TestFixtureImportFixture();
		$Fixture->fields = $Fixture->records = $Fixture->table = null;
		$Fixture->import = array('model' => 'FixtureImportTestModel', 'connection' => 'test');
		$Fixture->init();
		$this->assertEquals(array('id', 'name', 'created'), array_keys($Fixture->fields));
		$this->assertEquals('fixture_tests', $Fixture->table);

		$keys = array_flip(ClassRegistry::keys());
		$this->assertFalse(array_key_exists('fixtureimporttestmodel', $keys));

		$Fixture->drop(ConnectionManager::getDataSource('fixture_test_suite'));
		$Source->drop($db);
	}

/**
 * test that fixtures don't duplicate the test db prefix.
 *
 * @return void
 */
	public function testInitDbPrefixDuplication() {
		$this->markTestSkipped('Skipped for now as table prefixes need to be re-worked.');

		$this->skipIf($this->db instanceof Sqlite, 'Cannot open 2 connections to Sqlite');
		$db = ConnectionManager::getDataSource('test');
		$backPrefix = $db->config['prefix'];
		$db->config['prefix'] = 'cake_fixture_test_';
		ConnectionManager::create('fixture_test_suite', $db->config);
		$newDb = ConnectionManager::getDataSource('fixture_test_suite');
		$newDb->config['prefix'] = 'cake_fixture_test_';

		$Source = new TestFixtureTestFixture();
		$Source->create($db);
		$Source->insert($db);

		$Fixture = new TestFixtureImportFixture();
		$Fixture->fields = $Fixture->records = $Fixture->table = null;
		$Fixture->import = array('model' => 'FixtureImportTestModel', 'connection' => 'test');

		$Fixture->init();
		$this->assertEquals(array('id', 'name', 'created'), array_keys($Fixture->fields));
		$this->assertEquals('fixture_tests', $Fixture->table);

		$Source->drop($db);
		$db->config['prefix'] = $backPrefix;
	}

/**
 * test init with a model that has a tablePrefix declared.
 *
 * @return void
 */
	public function testInitModelTablePrefix() {
		$this->markTestSkipped('Skipped for now as table prefixes need to be re-worked.');

		$Source = new TestFixtureTestFixture();
		$Source->create($this->db);
		$Source->insert($this->db);

		$Fixture = new TestFixtureTestFixture();
		unset($Fixture->table);
		$Fixture->fields = $Fixture->records = null;
		$Fixture->import = array('model' => 'FixturePrefixTest', 'connection' => 'test', 'records' => false);
		$Fixture->init();
		$this->assertEquals('fixture_tests', $Fixture->table);

		$keys = array_flip(ClassRegistry::keys());
		$this->assertFalse(array_key_exists('fixtureimporttestmodel', $keys));

		$Source->drop($this->db);
	}

/**
 * testImport
 *
 * @return void
 */
	public function testImport() {
		$this->markTestSkipped('Skipped for now as table prefixes need to be re-worked.');
		Configure::write('App.namespace', 'TestApp');
		$Fixture = new ImportFixture();
		$Fixture->fields = $Fixture->records = null;
		$Fixture->import = [
			'model' => 'Post',
			'connection' => 'test',
		];
		$Fixture->init();

		$expected = [
			'id',
			'author_id',
			'title',
			'body',
			'published',
			'created',
			'updated',
		];
		$this->assertEquals($expected, array_keys($Fixture->fields));

		$keys = array_flip(ClassRegistry::keys());
		$this->assertFalse(array_key_exists('post', $keys));
	}

/**
 * test that importing with records works. Make sure to try with postgres as its
 * handling of aliases is a workaround at best.
 *
 * @return void
 */
	public function testImportWithRecords() {
		$this->markTestSkipped('Skipped for now as table prefixes need to be re-worked.');
		Configure::write('App.namespace', 'TestApp');
		$Fixture = new ImportFixture();
		$Fixture->fields = $Fixture->records = null;
		$Fixture->import = [
			'model' => 'Post',
			'connection' => 'test',
			'records' => true
		];
		$Fixture->init();
		$expected = [
			'id',
			'author_id',
			'title',
			'body',
			'published',
			'created',
			'updated',
		];
		$this->assertEquals($expected, array_keys($Fixture->fields));
		$this->assertFalse(empty($Fixture->records[0]), 'No records loaded on importing fixture.');
		$this->assertTrue(isset($Fixture->records[0]['title']), 'No title loaded for first record');
	}

/**
 * test create method
 *
 * @return void
 */
	public function testCreate() {
		$fixture = new ArticleFixture();
		$db = $this->getMock('Cake\Database\Connection', [], [], '', false);
		$table = $this->getMock('Cake\Database\Schema\Table', [], ['articles']);
		$table->expects($this->once())
			->method('createSql')
			->with($db)
			->will($this->returnValue(['sql', 'sql']));
		$fixture->schema($table);

		$db->expects($this->exactly(2))->method('execute');
		$this->assertTrue($fixture->create($db));
	}

/**
 * test create method, trigger error
 *
 * @expectedException PHPUnit_Framework_Error
 * @return void
 */
	public function testCreateError() {
		$fixture = new ArticleFixture();
		$db = $this->getMock('Cake\Database\Connection', [], [], '', false);
		$table = $this->getMock('Cake\Database\Schema\Table', [], ['articles']);
		$table->expects($this->once())
			->method('createSql')
			->with($db)
			->will($this->throwException(new \Exception('oh noes')));
		$fixture->schema($table);

		$fixture->create($db);
	}

/**
 * test the insert method
 *
 * @return void
 */
	public function testInsert() {
		$fixture = new ArticleFixture();

		$db = $this->getMock('Cake\Database\Connection', [], [], '', false);
		$query = $this->getMock('Cake\Database\Query', [], [$db]);
		$db->expects($this->once())
			->method('newQuery')
			->will($this->returnValue($query));

		$query->expects($this->once())
			->method('insert')
			->with('articles', ['name', 'created'], ['string', 'datetime'])
			->will($this->returnSelf());
		$expected = [
			['name' => 'Gandalf', 'created' => '2009-04-28 19:20:00'],
			['name' => 'Captain Picard', 'created' => '2009-04-28 19:20:00'],
			['name' => 'Chewbacca', 'created' => '2009-04-28 19:20:00']
		];
		$query->expects($this->at(1))
			->method('values')
			->with($expected[0])
			->will($this->returnSelf());
		$query->expects($this->at(2))
			->method('values')
			->with($expected[1])
			->will($this->returnSelf());
		$query->expects($this->at(3))
			->method('values')
			->with($expected[2])
			->will($this->returnSelf());

		$query->expects($this->once())
			->method('execute')
			->will($this->returnValue(true));

		$this->assertTrue($fixture->insert($db));
	}

/**
 * test the insert method
 *
 * @return void
 */
	public function testInsertStrings() {
		$fixture = new StringsTestFixture();

		$db = $this->getMock('Cake\Database\Connection', [], [], '', false);
		$query = $this->getMock('Cake\Database\Query', [], [$db]);
		$db->expects($this->once())
			->method('newQuery')
			->will($this->returnValue($query));

		$query->expects($this->once())
			->method('insert')
			->with('strings', ['name', 'email', 'age'], ['string', 'string', 'integer'])
			->will($this->returnSelf());

		$expected = [
			['name' => 'Mark Doe', 'email' => 'mark.doe@email.com', 'age' => null],
			['name' => 'John Doe', 'email' => 'john.doe@email.com', 'age' => 20],
			['name' => 'Jane Doe', 'email' => 'jane.doe@email.com', 'age' => 30],
		];
		$query->expects($this->at(1))
			->method('values')
			->with($expected[0])
			->will($this->returnSelf());
		$query->expects($this->at(2))
			->method('values')
			->with($expected[1])
			->will($this->returnSelf());
		$query->expects($this->at(3))
			->method('values')
			->with($expected[2])
			->will($this->returnSelf());

		$query->expects($this->once())
			->method('execute')
			->will($this->returnValue(true));

		$this->assertTrue($fixture->insert($db));
	}

/**
 * Test the drop method
 *
 * @return void
 */
	public function testDrop() {
		$fixture = new ArticleFixture();

		$db = $this->getMock('Cake\Database\Connection', [], [], '', false);
		$db->expects($this->once())
			->method('execute')
			->with('sql');

		$table = $this->getMock('Cake\Database\Schema\Table', [], ['articles']);
		$table->expects($this->once())
			->method('dropSql')
			->with($db)
			->will($this->returnValue(['sql']));
		$fixture->schema($table);

		$this->assertTrue($fixture->drop($db));
	}

/**
 * Test the truncate method.
 *
 * @return void
 */
	public function testTruncate() {
		$fixture = new ArticleFixture();

		$db = $this->getMock('Cake\Database\Connection', [], [], '', false);
		$db->expects($this->once())
			->method('execute')
			->with('sql');

		$table = $this->getMock('Cake\Database\Schema\Table', [], ['articles']);
		$table->expects($this->once())
			->method('truncateSql')
			->with($db)
			->will($this->returnValue(['sql']));
		$fixture->schema($table);

		$this->assertTrue($fixture->truncate($db));
	}

}
