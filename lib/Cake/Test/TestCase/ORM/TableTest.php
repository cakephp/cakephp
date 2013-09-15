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
use Cake\Database\ConnectionManager;
use Cake\ORM\Table;

/**
 * Used to test correct class is instantiated when using Table::build();
 */
class UsersTable extends Table {

/**
 * Overrides default table name
 *
 * @var string
 */
	protected $_table = 'users';

}

/**
 * Used to test correct class is instantiated when using Table::build();
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
 * Tests Table class
 *
 */
class TableTest extends \Cake\TestSuite\TestCase {

	public $fixtures = ['core.user', 'core.category'];

	public function setUp() {
		parent::setUp();
		$this->connection = ConnectionManager::get('test');
	}

	public function tearDown() {
		parent::tearDown();
		Table::clearRegistry();
	}
/**
 * Tests that table options can be pre-configured for the factory method
 *
 * @return void
 */
	public function testConfigAndBuild() {
		$map = Table::config();
		$this->assertEquals([], $map);

		$options = ['connection' => $this->connection];
		Table::config('users', $options);
		$map = Table::config();
		$this->assertEquals(['users' => $options], $map);
		$this->assertEquals($options, Table::config('users'));

		$schema = ['id' => ['type' => 'rubbish']];
		$options += ['schema' => $schema];
		Table::config('users', $options);

		$table = Table::build('foo', ['table' => 'users']);
		$this->assertInstanceOf('Cake\ORM\Table', $table);
		$this->assertEquals('users', $table->table());
		$this->assertEquals('foo', $table->alias());
		$this->assertSame($this->connection, $table->connection());
		$this->assertEquals(array_keys($schema), $table->schema()->columns());
		$this->assertEquals($schema['id']['type'], $table->schema()->column('id')['type']);

		Table::clearRegistry();
		$this->assertEmpty(Table::config());

		Table::config('users', $options);
		$table = Table::build('foo', ['className' => __NAMESPACE__ . '\MyUsersTable']);
		$this->assertInstanceOf(__NAMESPACE__ . '\MyUsersTable', $table);
		$this->assertEquals('users', $table->table());
		$this->assertEquals('foo', $table->alias());
		$this->assertSame($this->connection, $table->connection());
		$this->assertEquals(array_keys($schema), $table->schema()->columns());
	}

/**
 * Tests getting and setting a Table instance in the registry
 *
 * @return void
 */
	public function testInstance() {
		$this->assertNull(Table::instance('users'));
		$table = new Table(['table' => 'users']);
		Table::instance('users', $table);
		$this->assertSame($table, Table::instance('users'));
	}

/**
 * Tests the table method
 *
 * @return void
 */
	public function testTableMethod() {
		$table = new Table(['table' => 'users']);
		$this->assertEquals('users', $table->table());

		$table = new UsersTable;
		$this->assertEquals('users', $table->table());

		$table = $this->getMockBuilder('\Cake\ORM\Table')
			->setMethods(['find'])
			->setMockClassName('SpecialThingTable')
			->getMock();
		$this->assertEquals('special_things', $table->table());

		$table = new Table(['alias' => 'LoveBoat']);
		$this->assertEquals('love_boats', $table->table());

		$table->table('other');
		$this->assertEquals('other', $table->table());
	}

/**
 * Tests the alias method
 *
 * @return void
 */
	public function testAliasMethod() {
		$table = new Table(['alias' => 'users']);
		$this->assertEquals('users', $table->alias());

		$table = new Table(['table' => 'stuffs']);
		$this->assertEquals('stuffs', $table->alias());

		$table = new UsersTable;
		$this->assertEquals('Users', $table->alias());

		$table = $this->getMockBuilder('\Cake\ORM\Table')
			->setMethods(['find'])
			->setMockClassName('SpecialThingTable')
			->getMock();
		$this->assertEquals('SpecialThing', $table->alias());

		$table->alias('AnotherOne');
		$this->assertEquals('AnotherOne', $table->alias());
	}

/**
 * Tests connection method
 *
 * @return void
 */
	public function testConnection() {
		$table = new Table(['table' => 'users']);
		$this->assertNull($table->connection());
		$table->connection($this->connection);
		$this->assertSame($this->connection, $table->connection());
	}

/**
 * Tests primaryKey method
 *
 * @return void
 */
	public function testPrimaryKey() {
		$table = new Table(['table' => 'users']);
		$this->assertEquals('id', $table->primaryKey());
		$table->primaryKey('thingID');
		$this->assertEquals('thingID', $table->primaryKey());
	}

/**
 * Tests schema method
 *
 * @return void
 */
	public function testSchema() {
		$schema = $this->connection->schemaCollection()->describe('users');
		$table = new Table(['table' => 'users', 'connection' => $this->connection]);
		$this->assertEquals($schema, $table->schema());

		$table = new Table(['table' => 'stuff']);
		$table->schema($schema);
		$this->assertSame($schema, $table->schema());

		$table = new Table(['table' => 'another']);
		$schema = ['id' => ['type' => 'integer']];
		$table->schema($schema);
		$this->assertEquals(
			new \Cake\Database\Schema\Table('another', $schema),
			$table->schema()
		);
	}

/**
 * Tests that all fields for a table are added by default in a find when no
 * other fields are specified
 *
 * @return void
 */
	public function testFindAllNoFields() {
		$table = new Table(['table' => 'users', 'connection' => $this->connection]);
		$results = $table->find('all')->where(['id IN' => [1, 2]])->order('id')->toArray();
		$expected = [
			[
				'id' => 1,
				'username' => 'mariano',
				'password' => '5f4dcc3b5aa765d61d8327deb882cf99',
				'created' => new \DateTime('2007-03-17 01:16:23'),
				'updated' => new \DateTime('2007-03-17 01:18:31'),
			],
			[
				'id' => 2,
				'username' => 'nate',
				'password' => '5f4dcc3b5aa765d61d8327deb882cf99',
				'created' => new \DateTime('2008-03-17 01:18:23'),
				'updated' => new \DateTime('2008-03-17 01:20:31'),
			],
		];
		$this->assertEquals($expected, $results);
	}

/**
 * Tests that it is possible to select only a few fields when finding over a table
 *
 * @return void
 */
	public function testFindAllSomeFields() {
		$table = new Table(['table' => 'users', 'connection' => $this->connection]);
		$results = $table->find('all')->select(['username', 'password'])->order('username')->toArray();
		$expected = [
			['username' => 'garrett', 'password' => '5f4dcc3b5aa765d61d8327deb882cf99'],
			['username' => 'larry', 'password' => '5f4dcc3b5aa765d61d8327deb882cf99'],
			['username' => 'mariano', 'password' => '5f4dcc3b5aa765d61d8327deb882cf99'],
			['username' => 'nate', 'password' => '5f4dcc3b5aa765d61d8327deb882cf99'],
		];
		$this->assertSame($expected, $results);

		$results = $table->find('all')->select(['foo' => 'username', 'password'])->order('username')->toArray();
		$expected = [
			['foo' => 'garrett', 'password' => '5f4dcc3b5aa765d61d8327deb882cf99'],
			['foo' => 'larry', 'password' => '5f4dcc3b5aa765d61d8327deb882cf99'],
			['foo' => 'mariano', 'password' => '5f4dcc3b5aa765d61d8327deb882cf99'],
			['foo' => 'nate', 'password' => '5f4dcc3b5aa765d61d8327deb882cf99'],
		];
		$this->assertSame($expected, $results);
	}

/**
 * Tests that the query will automatically casts complex conditions to the correct
 * types when the columns belong to the default table
 *
 * @return void
 */
	public function testFindAllConditionAutoTypes() {
		$table = new Table(['table' => 'users', 'connection' => $this->connection]);
		$query = $table->find('all')
			->select(['id', 'username'])
			->where(['created >=' => new \DateTime('2010-01-22 00:00')])
			->order('id');
		$expected = [
			['id' => 3, 'username' => 'larry'],
			['id' => 4, 'username' => 'garrett']
		];
		$this->assertSame($expected, $query->toArray());

		$query->orWhere(['users.created' => new \DateTime('2008-03-17 01:18:23')]);
		$expected = [
			['id' => 2, 'username' => 'nate'],
			['id' => 3, 'username' => 'larry'],
			['id' => 4, 'username' => 'garrett']
		];
		$this->assertSame($expected, $query->toArray());
	}

/**
 * Test that beforeFind events can mutate the query.
 *
 * @return void
 */
	public function testFindBeforeFindEventMutateQuery() {
		$table = new Table(['table' => 'users', 'connection' => $this->connection]);
		$table->getEventManager()->attach(function ($event, $query, $options) {
			$query->limit(1);
		}, 'Model.beforeFind');

		$result = $table->find('all')->execute();
		$this->assertCount(1, $result, 'Should only have 1 record, limit 1 applied.');
	}

/**
 * Test that beforeFind events are fired and can stop the find and
 * return custom results.
 *
 * @return void
 */
	public function testFindBeforeFindEventOverrideReturn() {
		$table = new Table(['table' => 'users', 'connection' => $this->connection]);
		$expected = ['One', 'Two', 'Three'];
		$table->getEventManager()->attach(function ($event, $query, $options) use ($expected) {
			$query->setResult($expected);
			$event->stopPropagation();
		}, 'Model.beforeFind');

		$query = $table->find('all');
		$query->limit(1);
		$this->assertEquals($expected, $query->execute());
	}

/**
 * Tests that belongsTo() creates and configures correctly the association
 *
 * @return void
 */
	public function testBelongsTo() {
		$options = ['foreignKey' => 'fake_id', 'conditions' => ['a' => 'b']];
		$table = new Table(['table' => 'dates']);
		$belongsTo = $table->belongsTo('user', $options);
		$this->assertInstanceOf('\Cake\ORM\Association\BelongsTo', $belongsTo);
		$this->assertSame($belongsTo, $table->association('user'));
		$this->assertEquals('user', $belongsTo->name());
		$this->assertEquals('fake_id', $belongsTo->foreignKey());
		$this->assertEquals(['a' => 'b'], $belongsTo->conditions());
		$this->assertSame($table, $belongsTo->source());
	}

/**
 * Tests that hasOne() creates and configures correctly the association
 *
 * @return void
 */
	public function testHasOne() {
		$options = ['foreignKey' => 'user_id', 'conditions' => ['b' => 'c']];
		$table = new Table(['table' => 'users']);
		$hasOne = $table->hasOne('profile', $options);
		$this->assertInstanceOf('\Cake\ORM\Association\HasOne', $hasOne);
		$this->assertSame($hasOne, $table->association('profile'));
		$this->assertEquals('profile', $hasOne->name());
		$this->assertEquals('user_id', $hasOne->foreignKey());
		$this->assertEquals(['b' => 'c'], $hasOne->conditions());
		$this->assertSame($table, $hasOne->source());
	}

/**
 * Tests that hasMany() creates and configures correctly the association
 *
 * @return void
 */
	public function testHasMany() {
		$options = [
			'foreignKey' => 'author_id',
			'conditions' => ['b' => 'c'],
			'sort' => ['foo' => 'asc']
		];
		$table = new Table(['table' => 'authors']);
		$hasMany = $table->hasMany('article', $options);
		$this->assertInstanceOf('\Cake\ORM\Association\HasMany', $hasMany);
		$this->assertSame($hasMany, $table->association('article'));
		$this->assertEquals('article', $hasMany->name());
		$this->assertEquals('author_id', $hasMany->foreignKey());
		$this->assertEquals(['b' => 'c'], $hasMany->conditions());
		$this->assertEquals(['foo' => 'asc'], $hasMany->sort());
		$this->assertSame($table, $hasMany->source());
	}

/**
 * Tests that BelongsToMany() creates and configures correctly the association
 *
 * @return void
 */
	public function testBelongsToMany() {
		$options = [
			'foreignKey' => 'thing_id',
			'joinTable' => 'things_tags',
			'conditions' => ['b' => 'c'],
			'sort' => ['foo' => 'asc']
		];
		$table = new Table(['table' => 'authors']);
		$belongsToMany = $table->belongsToMany('tag', $options);
		$this->assertInstanceOf('\Cake\ORM\Association\BelongsToMany', $belongsToMany);
		$this->assertSame($belongsToMany, $table->association('tag'));
		$this->assertEquals('tag', $belongsToMany->name());
		$this->assertEquals('thing_id', $belongsToMany->foreignKey());
		$this->assertEquals(['b' => 'c'], $belongsToMany->conditions());
		$this->assertEquals(['foo' => 'asc'], $belongsToMany->sort());
		$this->assertSame($table, $belongsToMany->source());
		$this->assertSame('things_tags', $belongsToMany->pivot()->table());
	}

/**
 * Test basic multi row updates.
 *
 * @return void
 */
	public function testUpdateAll() {
		$table = new Table(['table' => 'users', 'connection' => $this->connection]);
		$fields = ['username' => 'mark'];
		$result = $table->updateAll($fields, ['id <' => 4]);
		$this->assertTrue($result);

		$result = $table->find('all')
			->select(['username'])
			->order(['id' => 'asc'])
			->toArray();
		$expected = array_fill(0, 3, $fields);
		$expected[] = ['username' => 'garrett'];
		$this->assertEquals($expected, $result);
	}

/**
 * Test that exceptions from the Query bubble up.
 *
 * @expectedException Cake\Database\Exception
 */
	public function testUpdateAllFailure() {
		$table = $this->getMock(
			'Cake\ORM\Table',
			['_buildQuery'],
			['table' => 'users', 'connection' => $this->connection]
		);
		$query = $this->getMock('Cake\ORM\Query', ['executeStatement'], [$this->connection, null]);
		$table->expects($this->once())
			->method('_buildQuery')
			->will($this->returnValue($query));
		$query->expects($this->once())
			->method('executeStatement')
			->will($this->throwException(new \Cake\Database\Exception('Not good')));
		$table->updateAll(['username' => 'mark'], []);
	}

/**
 * Test deleting many records.
 *
 * @return void
 */
	public function testDeleteAll() {
		$table = new Table(['table' => 'users', 'connection' => $this->connection]);
		$result = $table->deleteAll(['id <' => 4]);
		$this->assertTrue($result);

		$result = $table->find('all')->toArray();
		$this->assertCount(1, $result, 'Only one record should remain');
		$this->assertEquals(4, $result[0]['id']);
	}

/**
 * Test that exceptions from the Query bubble up.
 *
 * @expectedException Cake\Database\Exception
 */
	public function testDeleteAllFailure() {
		$table = $this->getMock(
			'Cake\ORM\Table',
			['_buildQuery'],
			['table' => 'users', 'connection' => $this->connection]
		);
		$query = $this->getMock('Cake\ORM\Query', ['executeStatement'], [$this->connection, null]);
		$table->expects($this->once())
			->method('_buildQuery')
			->will($this->returnValue($query));
		$query->expects($this->once())
			->method('executeStatement')
			->will($this->throwException(new \Cake\Database\Exception('Not good')));
		$table->deleteAll(['id >' => 4]);
	}

/**
 * Tests that array options are passed to the query object using applyOptions
 *
 * @return void
 */
	public function testFindApplyOptions() {
		$table = $this->getMock('Cake\ORM\Table', ['_buildQuery'], ['table' => 'users']);
		$query = $this->getMock('Cake\ORM\Query', [], [$this->connection, $table]);
		$table->expects($this->once())
			->method('_buildQuery')
			->will($this->returnValue($query));

		$options = ['fields' => ['a', 'b'], 'connections' => ['a >' => 1]];
		$query->expects($this->any())
			->method('select')
			->will($this->returnSelf());
		$query->expects($this->once())
			->method('applyOptions')
			->with($options);
		$table->find('all', $options);
	}

/**
 * Tests find('list')
 *
 * @return void
 */
	public function testFindList() {
		$table = new Table(['table' => 'users', 'connection' => $this->connection]);
		$query = $table->find('list', ['fields' => ['id', 'username']])->order('id');
		$expected = [
			1 => 'mariano',
			2 => 'nate',
			3 => 'larry',
			4 => 'garrett'
		];
		$this->assertSame($expected, $query->toArray());

		$query = $table->find('list')
			->select(['id', 'username', 'odd' => 'id % 2 = 0'])
			->order('id');
		$expected = [
			0 => [
				1 => 'mariano',
				3 => 'larry'
			],
			1 => [
				2 => 'nate',
				4 => 'garrett'
			]
		];
		$this->assertSame($expected, $query->toArray());
	}

/**
 * Tests find('threaded')
 *
 * @return void
 */
	public function testFindThreaded() {
		$table = new Table(['table' => 'categories', 'connection' => $this->connection]);
		$expected = [
			[
				'id' => 1,
				'parent_id' => 0,
				'name' => 'Category 1',
				'children' => [
					[
						'id' => 2,
						'parent_id' => 1,
						'name' => 'Category 1.1',
						'children' => [
							[
								'id' => 7,
								'parent_id' => 2,
								'name' => 'Category 1.1.1',
								'children' => []
							],
							[
								'id' => 8,
								'parent_id' => '2',
								'name' => 'Category 1.1.2',
								'children' => []
							]
						],
					],
					[
						'id' => 3,
						'parent_id' => '1',
						'name' => 'Category 1.2',
						'children' => []
					],
				]
			],
			[
				'id' => 4,
				'parent_id' => 0,
				'name' => 'Category 2',
				'children' => []
			],
			[
				'id' => 5,
				'parent_id' => 0,
				'name' => 'Category 3',
				'children' => [
					[
						'id' => '6',
						'parent_id' => '5',
						'name' => 'Category 3.1',
						'children' => []
					]
				]
			]
		];
		$results = $table->find('all')
			->select(['id', 'parent_id', 'name'])
			->hydrate(false)
			->threaded()
			->toArray();
		$this->assertEquals($expected, $results);
	}

/**
 * Tests that finders can be called directly
 *
 * @return void
 */
	public function testCallingFindersDirectly() {
		$table = $this->getMock('\Cake\ORM\Table', ['find']);
		$query = $this->getMock('\Cake\ORM\Query', [], [$this->connection, $table]);
		$table->expects($this->once())
			->method('find')
			->with('list', [])
			->will($this->returnValue($query));
		$this->assertSame($query, $table->list());

		$table = $this->getMock('\Cake\ORM\Table', ['find']);
		$table->expects($this->once())
			->method('find')
			->with('threaded', ['order' => ['name' => 'ASC']])
			->will($this->returnValue($query));
		$this->assertSame($query, $table->threaded(['order' => ['name' => 'ASC']]));
	}

/**
 * Tests that finders can be stacked
 *
 * @return void
 */
	public function testStackingFinders() {
		$table = $this->getMock('\Cake\ORM\Table', ['find', 'findList']);
		$params = [$this->connection, $table];
		$query = $this->getMock('\Cake\ORM\Query', ['addDefaultTypes'], $params);

		$table->expects($this->once())
			->method('find')
			->with('threaded', ['order' => ['name' => 'ASC']])
			->will($this->returnValue($query));

		$table->expects($this->once())
			->method('findList')
			->with($query, ['keyPath' => 'id'])
			->will($this->returnValue($query));

		$result = $table
			->threaded(['order' => ['name' => 'ASC']])
			->list(['keyPath' => 'id']);
		$this->assertSame($query, $result);
	}

/**
 * Tests find('threaded') with hydrated results
 *
 * @return void
 */
	public function testFindThreadedHydrated() {
		$table = new Table(['table' => 'categories', 'connection' => $this->connection]);
		$results = $table->find('all')
			->hydrate(true)
			->threaded()
			->select(['id', 'parent_id', 'name'])
			->toArray();

		$this->assertEquals(1, $results[0]->id);
		$expected = [
			'id' => 8,
			'parent_id' => 2,
			'name' => 'Category 1.1.2',
			'children' => []
		];
		$this->assertEquals($expected, $results[0]->children[0]->children[1]->toArray());
	}

}
