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
use Cake\Database\Expression\OrderByExpression;
use Cake\Database\Expression\QueryExpression;
use Cake\ORM\Table;
use Cake\ORM\TableRegistry;
use Cake\Validation\Validator;

/**
 * Used to test correct class is instantiated when using TableRegistry::get();
 */
class UsersTable extends Table {

}

/**
 * Tests Table class
 *
 */
class TableTest extends \Cake\TestSuite\TestCase {

	public $fixtures = [
		'core.user', 'core.category', 'core.article', 'core.author',
		'core.tag', 'core.articles_tag'
	];

/**
 * Handy variable containing the next primary key that will be inserted in the
 * users table
 *
 * @var integer
 */
	public static $nextUserId = 5;

	public function setUp() {
		parent::setUp();
		$this->connection = ConnectionManager::get('test');
		Configure::write('App.namespace', 'TestApp');
	}

	public function tearDown() {
		parent::tearDown();
		TableRegistry::clear();
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
			->setMockClassName('SpecialThingsTable')
			->getMock();
		$this->assertEquals('special_things', $table->table());

		$table = new Table(['alias' => 'LoveBoats']);
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
 * Tests that name will be selected as a displayField
 *
 * @return void
 */
	public function testDisplayFieldName() {
		$table = new Table([
			'table' => 'users',
			'schema' => [
				'foo' => ['type' => 'string'],
				'name' => ['type' => 'string']
			]
		]);
		$this->assertEquals('name', $table->displayField());
	}

/**
 * Tests that title will be selected as a displayField
 *
 * @return void
 */
	public function testDisplayFieldTitle() {
		$table = new Table([
			'table' => 'users',
			'schema' => [
				'foo' => ['type' => 'string'],
				'title' => ['type' => 'string']
			]
		]);
		$this->assertEquals('title', $table->displayField());
	}

/**
 * Tests that no displayField will fallback to primary key
 *
 * @return void
 */
	public function testDisplayFallback() {
		$table = new Table([
			'table' => 'users',
			'schema' => [
				'id' => ['type' => 'string'],
				'foo' => ['type' => 'string']
			]
		]);
		$this->assertEquals('id', $table->displayField());
	}

/**
 * Tests that displayField can be changed
 *
 * @return void
 */
	public function testDisplaySet() {
		$table = new Table([
			'table' => 'users',
			'schema' => [
				'id' => ['type' => 'string'],
				'foo' => ['type' => 'string']
			]
		]);
		$this->assertEquals('id', $table->displayField());
		$table->displayField('foo');
		$this->assertEquals('foo', $table->displayField());
	}

/**
 * Tests schema method
 *
 * @return void
 */
	public function testSchema() {
		$schema = $this->connection->schemaCollection()->describe('users');
		$table = new Table([
			'table' => 'users',
			'connection' => $this->connection,
		]);
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
	public function testFindAllNoFieldsAndNoHydration() {
		$table = new Table([
			'table' => 'users',
			'connection' => $this->connection,
		]);
		$results = $table
			->find('all')
			->where(['id IN' => [1, 2]])
			->order('id')
			->hydrate(false)
			->toArray();
		$expected = [
			[
				'id' => 1,
				'username' => 'mariano',
				'password' => '$2a$10$u05j8FjsvLBNdfhBhc21LOuVMpzpabVXQ9OpC2wO3pSO0q6t7HHMO',
				'created' => new \DateTime('2007-03-17 01:16:23'),
				'updated' => new \DateTime('2007-03-17 01:18:31'),
			],
			[
				'id' => 2,
				'username' => 'nate',
				'password' => '$2a$10$u05j8FjsvLBNdfhBhc21LOuVMpzpabVXQ9OpC2wO3pSO0q6t7HHMO',
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
	public function testFindAllSomeFieldsNoHydration() {
		$table = new Table([
			'table' => 'users',
			'connection' => $this->connection,
		]);
		$results = $table->find('all')
			->select(['username', 'password'])
			->hydrate(false)
			->order('username')->toArray();
		$expected = [
			['username' => 'garrett', 'password' => '$2a$10$u05j8FjsvLBNdfhBhc21LOuVMpzpabVXQ9OpC2wO3pSO0q6t7HHMO'],
			['username' => 'larry', 'password' => '$2a$10$u05j8FjsvLBNdfhBhc21LOuVMpzpabVXQ9OpC2wO3pSO0q6t7HHMO'],
			['username' => 'mariano', 'password' => '$2a$10$u05j8FjsvLBNdfhBhc21LOuVMpzpabVXQ9OpC2wO3pSO0q6t7HHMO'],
			['username' => 'nate', 'password' => '$2a$10$u05j8FjsvLBNdfhBhc21LOuVMpzpabVXQ9OpC2wO3pSO0q6t7HHMO'],
		];
		$this->assertSame($expected, $results);

		$results = $table->find('all')
			->select(['foo' => 'username', 'password'])
			->order('username')
			->hydrate(false)
			->toArray();
		$expected = [
			['foo' => 'garrett', 'password' => '$2a$10$u05j8FjsvLBNdfhBhc21LOuVMpzpabVXQ9OpC2wO3pSO0q6t7HHMO'],
			['foo' => 'larry', 'password' => '$2a$10$u05j8FjsvLBNdfhBhc21LOuVMpzpabVXQ9OpC2wO3pSO0q6t7HHMO'],
			['foo' => 'mariano', 'password' => '$2a$10$u05j8FjsvLBNdfhBhc21LOuVMpzpabVXQ9OpC2wO3pSO0q6t7HHMO'],
			['foo' => 'nate', 'password' => '$2a$10$u05j8FjsvLBNdfhBhc21LOuVMpzpabVXQ9OpC2wO3pSO0q6t7HHMO'],
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
		$table = new Table([
			'table' => 'users',
			'connection' => $this->connection,
		]);
		$query = $table->find('all')
			->select(['id', 'username'])
			->where(['created >=' => new \DateTime('2010-01-22 00:00')])
			->hydrate(false)
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
		$table = new Table([
			'table' => 'users',
			'connection' => $this->connection,
		]);
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
		$table = new Table([
			'table' => 'users',
			'connection' => $this->connection,
		]);
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
		$table = new Table(['table' => 'authors', 'connection' => $this->connection]);
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
		$table = new Table([
			'table' => 'users',
			'connection' => $this->connection,
		]);
		$fields = ['username' => 'mark'];
		$result = $table->updateAll($fields, ['id <' => 4]);
		$this->assertTrue($result);

		$result = $table->find('all')
			->select(['username'])
			->order(['id' => 'asc'])
			->hydrate(false)
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
			[['table' => 'users']]
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
		$table = new Table([
			'table' => 'users',
			'connection' => $this->connection,
		]);
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
			[['table' => 'users', 'connection' => $this->connection]]
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
		$table = $this->getMock(
			'Cake\ORM\Table',
			['_buildQuery'],
			[['table' => 'users', 'connection' => $this->connection]]
		);
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
	public function testFindListNoHydration() {
		$table = new Table([
			'table' => 'users',
			'connection' => $this->connection,
		]);
		$table->displayField('username');
		$query = $table->find('list')
			->hydrate(false)
			->order('id');
		$expected = [
			1 => 'mariano',
			2 => 'nate',
			3 => 'larry',
			4 => 'garrett'
		];
		$this->assertSame($expected, $query->toArray());

		$query = $table->find('list', ['fields' => ['id', 'username']])
			->hydrate(false)
			->order('id');
		$expected = [
			1 => 'mariano',
			2 => 'nate',
			3 => 'larry',
			4 => 'garrett'
		];
		$this->assertSame($expected, $query->toArray());

		$query = $table->find('list', ['groupField' => 'odd'])
			->select(['id', 'username', 'odd' => new QueryExpression('id % 2 = 0')])
			->hydrate(false)
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
	public function testFindThreadedNoHydration() {
		$table = new Table([
			'table' => 'categories',
			'connection' => $this->connection,
		]);
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
			->find('threaded')
			->toArray();

		$this->assertEquals($expected, $results);
	}

/**
 * Tests that finders can be stacked
 *
 * @return void
 */
	public function testStackingFinders() {
		$table = $this->getMock('\Cake\ORM\Table', ['find', 'findList'], [], '', false);
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
			->find('threaded', ['order' => ['name' => 'ASC']])
			->find('list', ['keyPath' => 'id']);
		$this->assertSame($query, $result);
	}

/**
 * Tests find('threaded') with hydrated results
 *
 * @return void
 */
	public function testFindThreadedHydrated() {
		$table = new Table([
			'table' => 'categories',
			'connection' => $this->connection,
		]);
		$results = $table->find('all')
			->find('threaded')
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

/**
 * Tests find('list') with hydrated records
 *
 * @return void
 */
	public function testFindListHydrated() {
		$table = new Table([
			'table' => 'users',
			'connection' => $this->connection,
		]);
		$table->displayField('username');
		$query = $table
			->find('list', ['fields' => ['id', 'username']])
			->order('id');
		$expected = [
			1 => 'mariano',
			2 => 'nate',
			3 => 'larry',
			4 => 'garrett'
		];
		$this->assertSame($expected, $query->toArray());

		$query = $table->find('list', ['groupField' => 'odd'])
			->select(['id', 'username', 'odd' => new QueryExpression('id % 2 = 0')])
			->hydrate(true)
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
 * Test the default entityClass.
 *
 * @return void
 */
	public function testEntityClassDefault() {
		$table = new Table();
		$this->assertEquals('\Cake\ORM\Entity', $table->entityClass());
	}

/**
 * Tests that using a simple string for entityClass will try to
 * load the class from the App namespace
 *
 * @return void
 */
	public function testRepositoryClassInApp() {
		$class = $this->getMockClass('\Cake\ORM\Entity');

		if (!class_exists('TestApp\Model\Entity\TestUser')) {
			class_alias($class, 'TestApp\Model\Entity\TestUser');
		}

		$table = new Table();
		$this->assertEquals('TestApp\Model\Entity\TestUser', $table->entityClass('TestUser'));
	}

/**
 * Tests that using a simple string for entityClass will try to
 * load the class from the Plugin namespace when using plugin notation
 *
 * @return void
 */
	public function testRepositoryClassInPlugin() {
		$class = $this->getMockClass('\Cake\ORM\Entity');

		if (!class_exists('MyPlugin\Model\Entity\SuperUser')) {
			class_alias($class, 'MyPlugin\Model\Entity\SuperUser');
		}

		$table = new Table();
		$this->assertEquals(
			'MyPlugin\Model\Entity\SuperUser',
			$table->entityClass('MyPlugin.SuperUser')
		);
	}

/**
 * Tests that using a simple string for entityClass will throw an exception
 * when the class does not exist in the namespace
 *
 * @expectedException Cake\ORM\Error\MissingEntityException
 * @expectedExceptionMessage Entity class FooUser could not be found.
 * @return void
 */
	public function testRepositoryClassNonExisting() {
		$table = new Table;
		$this->assertFalse($table->entityClass('FooUser'));
	}

/**
 * Tests getting the entityClass based on conventions for the entity
 * namespace
 *
 * @return void
 */
	public function testRepositoryClassConventionForAPP() {
		$table = new \TestApp\Model\Repository\ArticlesTable;
		$this->assertEquals('TestApp\Model\Entity\Article', $table->entityClass());
	}

/**
 * Tests setting a entity class object using the setter method
 *
 * @return void
 */
	public function testSetEntityClass() {
		$table = new Table;
		$class = '\\' . $this->getMockClass('\Cake\ORM\Entity');
		$table->entityClass($class);
		$this->assertEquals($class, $table->entityClass());
	}

/**
 * Proves that associations, even though they are lazy loaded, will fetch
 * records using the correct table class and hydrate with the correct entity
 *
 * @return void
 */
	public function testReciprocalBelongsToLoading() {
		$table = new \TestApp\Model\Repository\ArticlesTable([
			'connection' => $this->connection,
		]);
		$result = $table->find('all')->contain(['authors'])->first();
		$this->assertInstanceOf('TestApp\Model\Entity\Author', $result->author);
	}

/**
 * Proves that associations, even though they are lazy loaded, will fetch
 * records using the correct table class and hydrate with the correct entity
 *
 * @return void
 */
	public function testReciprocalHasManyLoading() {
		$table = new \TestApp\Model\Repository\ArticlesTable([
			'connection' => $this->connection,
		]);
		$result = $table->find('all')->contain(['authors' => ['articles']])->first();
		$this->assertCount(2, $result->author->articles);
		foreach ($result->author->articles as $article) {
			$this->assertInstanceOf('TestApp\Model\Entity\Article', $article);
		}
	}

/**
 * Tests that the correct table and entity are loaded for the pivot association in
 * a belongsToMany setup
 *
 * @return void
 */
	public function testReciprocalBelongsToMany() {
		$table = new \TestApp\Model\Repository\ArticlesTable([
			'connection' => $this->connection,
		]);
		$result = $table->find('all')->contain(['tags'])->first();
		$this->assertInstanceOf('TestApp\Model\Entity\Tag', $result->tags[0]);
		$this->assertInstanceOf(
			'TestApp\Model\Entity\ArticlesTag',
			$result->tags[0]->extraInfo
		);
	}

/**
 * Tests that recently fetched entities are always clean
 *
 * @return void
 */
	public function testFindCleanEntities() {
		$table = new \TestApp\Model\Repository\ArticlesTable([
			'connection' => $this->connection,
		]);
		$results = $table->find('all')->contain(['tags', 'authors'])->toArray();
		$this->assertCount(3, $results);
		foreach ($results as $article) {
			$this->assertFalse($article->dirty('id'));
			$this->assertFalse($article->dirty('title'));
			$this->assertFalse($article->dirty('author_id'));
			$this->assertFalse($article->dirty('body'));
			$this->assertFalse($article->dirty('published'));
			$this->assertFalse($article->dirty('author'));
			$this->assertFalse($article->author->dirty('id'));
			$this->assertFalse($article->author->dirty('name'));
			$this->assertFalse($article->dirty('tag'));
			if ($article->tag) {
				$this->assertFalse($article->tag[0]->extraInfo->dirty('tag_id'));
			}
		}
	}

/**
 * Tests that recently fetched entities are marked as not new
 *
 * @return void
 */
	public function testFindPersistedEntities() {
		$table = new \TestApp\Model\Repository\ArticlesTable([
			'connection' => $this->connection,
		]);
		$results = $table->find('all')->contain(['tags', 'authors'])->toArray();
		$this->assertCount(3, $results);
		foreach ($results as $article) {
			$this->assertFalse($article->isNew());
			foreach ((array)$article->tag as $tag) {
				$this->assertFalse($tag->isNew());
				$this->assertFalse($tag->extraInfo->isNew());
			}
		}
	}

/**
 * Tests the exists function
 *
 * @return void
 */
	public function testExists() {
		$table = TableRegistry::get('users');
		$this->assertTrue($table->exists(['id' => 1]));
		$this->assertFalse($table->exists(['id' => 501]));
		$this->assertTrue($table->exists(['id' => 3, 'username' => 'larry']));
	}

/**
 * Test adding a behavior to a table.
 *
 * @return void
 */
	public function testAddBehavior() {
		$mock = $this->getMock('Cake\ORM\BehaviorRegistry', [], [], '', false);
		$mock->expects($this->once())
			->method('load')
			->with('Sluggable');

		$table = new Table([
			'table' => 'articles',
			'behaviors' => $mock
		]);
		$table->addBehavior('Sluggable');
	}

/**
 * Ensure exceptions are raised on missing behaviors.
 *
 * @expectedException Cake\Error\MissingBehaviorException
 */
	public function testAddBehaviorMissing() {
		$table = TableRegistry::get('article');
		$this->assertNull($table->addBehavior('NopeNotThere'));
	}

/**
 * Test mixin methods from behaviors.
 *
 * @return void
 */
	public function testCallBehaviorMethod() {
		$table = TableRegistry::get('article');
		$table->addBehavior('Sluggable');
		$this->assertEquals('some_value', $table->slugify('some value'));
	}

/**
 * Test you can alias a behavior method
 *
 * @return void
 */
	public function testCallBehaviorAliasedMethod() {
		$table = TableRegistry::get('article');
		$table->addBehavior('Sluggable', ['implementedMethods' => ['wednesday' => 'slugify']]);
		$this->assertEquals('some_value', $table->wednesday('some value'));
	}

/**
 * Test finder methods from behaviors.
 *
 * @return void
 */
	public function testCallBehaviorFinder() {
		$table = TableRegistry::get('articles');
		$table->addBehavior('Sluggable');

		$query = $table->find('noSlug');
		$this->assertInstanceOf('Cake\ORM\Query', $query);
		$this->assertNotEmpty($query->clause('where'));
	}

/**
 * testCallBehaviorAliasedFinder
 *
 * @return void
 */
	public function testCallBehaviorAliasedFinder() {
		$table = TableRegistry::get('articles');
		$table->addBehavior('Sluggable', ['implementedFinders' => ['special' => 'findNoSlug']]);

		$query = $table->find('special');
		$this->assertInstanceOf('Cake\ORM\Query', $query);
		$this->assertNotEmpty($query->clause('where'));
	}

/**
 * Test implementedEvents
 *
 * @return void
 */
	public function testImplementedEvents() {
		$table = $this->getMock(
			'Cake\ORM\Table',
			['beforeFind', 'beforeSave', 'afterSave', 'beforeDelete', 'afterDelete']
		);
		$result = $table->implementedEvents();
		$expected = [
			'Model.beforeFind' => 'beforeFind',
			'Model.beforeSave' => 'beforeSave',
			'Model.afterSave' => 'afterSave',
			'Model.beforeDelete' => 'beforeDelete',
			'Model.afterDelete' => 'afterDelete',
		];
		$this->assertEquals($expected, $result, 'Events do not match.');
	}

/**
 * Tests that it is possible to insert a new row using the save method
 *
 * @group save
 * @return void
 */
	public function testSaveNewEntity() {
		$entity = new \Cake\ORM\Entity([
			'username' => 'superuser',
			'password' => 'root',
			'created' => new \DateTime('2013-10-10 00:00'),
			'updated' => new \DateTime('2013-10-10 00:00')
		]);
		$table = TableRegistry::get('users');
		$this->assertSame($entity, $table->save($entity));
		$this->assertEquals($entity->id, self::$nextUserId);

		$row = $table->find('all')->where(['id' => self::$nextUserId])->first();
		$this->assertEquals($entity->toArray(), $row->toArray());
	}

/**
 * Tests that saving an entity will filter out properties that
 * are not present in the table schema when saving
 *
 * @group save
 * @return void
 */
	public function testSaveEntityOnlySchemaFields() {
		$entity = new \Cake\ORM\Entity([
			'username' => 'superuser',
			'password' => 'root',
			'crazyness' => 'super crazy value',
			'created' => new \DateTime('2013-10-10 00:00'),
			'updated' => new \DateTime('2013-10-10 00:00'),
		]);
		$table = TableRegistry::get('users');
		$this->assertSame($entity, $table->save($entity));
		$this->assertEquals($entity->id, self::$nextUserId);

		$row = $table->find('all')->where(['id' => self::$nextUserId])->first();
		$entity->unsetProperty('crazyness');
		$this->assertEquals($entity->toArray(), $row->toArray());
	}

/**
 * Tests that it is possible to modify data from the beforeSave callback
 *
 * @group save
 * @return void
 */
	public function testBeforeSaveModifyData() {
		$table = TableRegistry::get('users');
		$data = new \Cake\ORM\Entity([
			'username' => 'superuser',
			'created' => new \DateTime('2013-10-10 00:00'),
			'updated' => new \DateTime('2013-10-10 00:00')
		]);
		$listener = function($e, $entity, $options) use ($data) {
			$this->assertSame($data, $entity);
			$entity->set('password', 'foo');
		};
		$table->getEventManager()->attach($listener, 'Model.beforeSave');
		$this->assertSame($data, $table->save($data));
		$this->assertEquals($data->id, self::$nextUserId);
		$row = $table->find('all')->where(['id' => self::$nextUserId])->first();
		$this->assertEquals('foo', $row->get('password'));
	}

/**
 * Tests that it is possible to modify the options array in beforeSave
 *
 * @group save
 * @return void
 */
	public function testBeforeSaveModifyOptions() {
		$table = TableRegistry::get('users');
		$data = new \Cake\ORM\Entity([
			'username' => 'superuser',
			'password' => 'foo',
			'created' => new \DateTime('2013-10-10 00:00'),
			'updated' => new \DateTime('2013-10-10 00:00')
		]);
		$listener1 = function($e, $entity, $options) {
			$options['crazy'] = true;
		};
		$listener2 = function($e, $entity, $options) {
			$this->assertTrue($options['crazy']);
		};
		$table->getEventManager()->attach($listener1, 'Model.beforeSave');
		$table->getEventManager()->attach($listener2, 'Model.beforeSave');
		$this->assertSame($data, $table->save($data));
		$this->assertEquals($data->id, self::$nextUserId);

		$row = $table->find('all')->where(['id' => self::$nextUserId])->first();
		$this->assertEquals($data->toArray(), $row->toArray());
	}

/**
 * Tests that it is possible to stop the saving altogether, without implying
 * the save operation failed
 *
 * @group save
 * @return void
 */
	public function testBeforeSaveStopEvent() {
		$table = TableRegistry::get('users');
		$data = new \Cake\ORM\Entity([
			'username' => 'superuser',
			'created' => new \DateTime('2013-10-10 00:00'),
			'updated' => new \DateTime('2013-10-10 00:00')
		]);
		$listener = function($e, $entity) {
			$e->stopPropagation();
			return $entity;
		};
		$table->getEventManager()->attach($listener, 'Model.beforeSave');
		$this->assertSame($data, $table->save($data));
		$this->assertNull($data->id);
		$row = $table->find('all')->where(['id' => self::$nextUserId])->first();
		$this->assertNull($row);
	}

/**
 * Asserts that afterSave callback is called on successful save
 *
 * @group save
 * @return void
 */
	public function testAfterSave() {
		$table = TableRegistry::get('users');
		$data = new \Cake\ORM\Entity([
			'username' => 'superuser',
			'created' => new \DateTime('2013-10-10 00:00'),
			'updated' => new \DateTime('2013-10-10 00:00')
		]);

		$called = false;
		$listener = function($e, $entity, $options) use ($data, &$called) {
			$this->assertSame($data, $entity);
			$called = true;
		};
		$table->getEventManager()->attach($listener, 'Model.afterSave');
		$this->assertSame($data, $table->save($data));
		$this->assertEquals($data->id, self::$nextUserId);
		$this->assertTrue($called);
	}

/**
 * Asserts that afterSave callback not is called on unsuccessful save
 *
 * @group save
 * @return void
 */
	public function testAfterSaveNotCalled() {
		$table = $this->getMock(
			'\Cake\ORM\Table',
			['_buildQuery', 'exists'],
			[['table' => 'users', 'connection' => $this->connection]]
		);
		$query = $this->getMock(
			'\Cake\ORM\Query',
			['executeStatement', 'addDefaultTypes'],
			[null, $table]
		);
		$statement = $this->getMock('\Cake\Database\Statement\StatementDecorator');
		$data = new \Cake\ORM\Entity([
			'username' => 'superuser',
			'created' => new \DateTime('2013-10-10 00:00'),
			'updated' => new \DateTime('2013-10-10 00:00')
		]);

		$table->expects($this->once())->method('exists')
			->will($this->returnValue(false));
		$table->expects($this->once())->method('_buildQuery')
			->will($this->returnValue($query));

		$query->expects($this->once())->method('executeStatement')
			->will($this->returnValue($statement));

		$statement->expects($this->once())->method('rowCount')
			->will($this->returnValue(0));

		$called = false;
		$listener = function($e, $entity, $options) use ($data, &$called) {
			$called = true;
		};
		$table->getEventManager()->attach($listener, 'Model.afterSave');
		$this->assertFalse($table->save($data));
		$this->assertFalse($called);
	}

/**
 * Tests that save is wrapped around a transaction
 *
 * @group save
 * @return void
 */
	public function testAtomicSave() {
		$config = ConnectionManager::config('test');

		$connection = $this->getMock(
			'\Cake\Database\Connection',
			['begin', 'commit'],
			[$config]
		);
		$connection->driver($this->connection->driver());

		$table = $this->getMock('\Cake\ORM\Table', ['connection'], [['table' => 'users']]);
		$table->expects($this->any())->method('connection')
			->will($this->returnValue($connection));

		$connection->expects($this->once())->method('begin');
		$connection->expects($this->once())->method('commit');
		$data = new \Cake\ORM\Entity([
			'username' => 'superuser',
			'created' => new \DateTime('2013-10-10 00:00'),
			'updated' => new \DateTime('2013-10-10 00:00')
		]);
		$this->assertSame($data, $table->save($data));
	}

/**
 * Tests that save will rollback the transaction in the case of an exception
 *
 * @group save
 * @expectedException \PDOException
 * @return void
 */
	public function testAtomicSaveRollback() {
		$connection = $this->getMock(
			'\Cake\Database\Connection',
			['begin', 'rollback'],
			[ConnectionManager::config('test')]
		);
		$connection->driver(ConnectionManager::get('test')->driver());
		$table = $this->getMock(
			'\Cake\ORM\Table',
			['_buildQuery', 'connection', 'exists'],
			[['table' => 'users']]
		);
		$query = $this->getMock(
			'\Cake\ORM\Query',
			['executeStatement', 'addDefaultTypes'],
			[null, $table]
		);

		$table->expects($this->once())->method('exists')
			->will($this->returnValue(false));

		$table->expects($this->any())->method('connection')
			->will($this->returnValue($connection));

		$table->expects($this->once())->method('_buildQuery')
			->will($this->returnValue($query));

		$connection->expects($this->once())->method('begin');
		$connection->expects($this->once())->method('rollback');
		$query->expects($this->once())->method('executeStatement')
			->will($this->throwException(new \PDOException));

		$data = new \Cake\ORM\Entity([
			'username' => 'superuser',
			'created' => new \DateTime('2013-10-10 00:00'),
			'updated' => new \DateTime('2013-10-10 00:00')
		]);
		$table->save($data);
	}

/**
 * Tests that save will rollback the transaction in the case of an exception
 *
 * @group save
 * @return void
 */
	public function testAtomicSaveRollbackOnFailure() {
		$connection = $this->getMock(
			'\Cake\Database\Connection',
			['begin', 'rollback'],
			[ConnectionManager::config('test')]
		);
		$connection->driver(ConnectionManager::get('test')->driver());
		$table = $this->getMock(
			'\Cake\ORM\Table',
			['_buildQuery', 'connection', 'exists'],
			[['table' => 'users']]
		);
		$query = $this->getMock(
			'\Cake\ORM\Query',
			['executeStatement', 'addDefaultTypes'],
			[null, $table]
		);

		$table->expects($this->once())->method('exists')
			->will($this->returnValue(false));

		$table->expects($this->any())->method('connection')
			->will($this->returnValue($connection));

		$table->expects($this->once())->method('_buildQuery')
			->will($this->returnValue($query));

		$statement = $this->getMock('\Cake\Database\Statement\StatementDecorator');
		$statement->expects($this->once())->method('rowCount')
			->will($this->returnValue(0));
		$connection->expects($this->once())->method('begin');
		$connection->expects($this->once())->method('rollback');
		$query->expects($this->once())->method('executeStatement')
			->will($this->returnValue($statement));

		$data = new \Cake\ORM\Entity([
			'username' => 'superuser',
			'created' => new \DateTime('2013-10-10 00:00'),
			'updated' => new \DateTime('2013-10-10 00:00')
		]);
		$table->save($data);
	}

/**
 * Tests that only the properties marked as dirty are actually saved
 * to the database
 *
 * @group save
 * @return void
 */
	public function testSaveOnlyDirtyProperties() {
		$entity = new \Cake\ORM\Entity([
			'username' => 'superuser',
			'password' => 'root',
			'created' => new \DateTime('2013-10-10 00:00'),
			'updated' => new \DateTime('2013-10-10 00:00')
		]);
		$entity->clean();
		$entity->dirty('username', true);
		$entity->dirty('created', true);
		$entity->dirty('updated', true);

		$table = TableRegistry::get('users');
		$this->assertSame($entity, $table->save($entity));
		$this->assertEquals($entity->id, self::$nextUserId);

		$row = $table->find('all')->where(['id' => self::$nextUserId])->first();
		$entity->set('password', null);
		$this->assertEquals($entity->toArray(), $row->toArray());
	}

/**
 * Tests that a recently saved entity is marked as clean
 *
 * @group save
 * @return void
 */
	public function testsASavedEntityIsClean() {
		$entity = new \Cake\ORM\Entity([
			'username' => 'superuser',
			'password' => 'root',
			'created' => new \DateTime('2013-10-10 00:00'),
			'updated' => new \DateTime('2013-10-10 00:00')
		]);
		$table = TableRegistry::get('users');
		$this->assertSame($entity, $table->save($entity));
		$this->assertFalse($entity->dirty('usermane'));
		$this->assertFalse($entity->dirty('password'));
		$this->assertFalse($entity->dirty('created'));
		$this->assertFalse($entity->dirty('updated'));
	}

/**
 * Tests that a recently saved entity is marked as not new
 *
 * @group save
 * @return void
 */
	public function testsASavedEntityIsNotNew() {
		$entity = new \Cake\ORM\Entity([
			'username' => 'superuser',
			'password' => 'root',
			'created' => new \DateTime('2013-10-10 00:00'),
			'updated' => new \DateTime('2013-10-10 00:00')
		]);
		$table = TableRegistry::get('users');
		$this->assertSame($entity, $table->save($entity));
		$this->assertFalse($entity->isNew());
	}

/**
 * Tests that save can detect automatically if it needs to insert
 * or update a row
 *
 * @group save
 * @return void
 */
	public function testSaveUpdateAuto() {
		$entity = new \Cake\ORM\Entity([
			'id' => 2,
			'username' => 'baggins'
		]);
		$table = TableRegistry::get('users');
		$original = $table->find('all')->where(['id' => 2])->first();
		$this->assertSame($entity, $table->save($entity));

		$row = $table->find('all')->where(['id' => 2])->first();
		$this->assertEquals('baggins', $row->username);
		$this->assertEquals($original->password, $row->password);
		$this->assertEquals($original->created, $row->created);
		$this->assertEquals($original->updated, $row->updated);
		$this->assertFalse($entity->isNew());
		$this->assertFalse($entity->dirty('id'));
		$this->assertFalse($entity->dirty('username'));
	}

/**
 * Tests that beforeFind gets the correct isNew() state for the entity
 *
 * @return void
 */
	public function testBeforeSaveGetsCorrectPersistance() {
		$entity = new \Cake\ORM\Entity([
			'id' => 2,
			'username' => 'baggins'
		]);
		$table = TableRegistry::get('users');
		$called = false;
		$listener = function($event, $entity) use (&$called) {
			$this->assertFalse($entity->isNew());
			$called = true;
		};
		$table->getEventManager()->attach($listener, 'Model.beforeSave');
		$this->assertSame($entity, $table->save($entity));
		$this->assertTrue($called);
	}

/**
 * Tests that marking an entity as already persisted will prevent the save
 * method from trying to infer the entity's actual status.
 *
 * @group save
 * @return void
 */
	public function testSaveUpdateWithHint() {
		$table = $this->getMock(
			'\Cake\ORM\Table',
			['exists'],
			[['table' => 'users', 'connection' => ConnectionManager::get('test')]]
		);
		$entity = new \Cake\ORM\Entity([
			'id' => 2,
			'username' => 'baggins'
		], ['markNew' => false]);
		$this->assertFalse($entity->isNew());
		$table->expects($this->never())->method('exists');
		$this->assertSame($entity, $table->save($entity));
	}

/**
 * Tests that when updating the primary key is not passed to the list of
 * attributes to change
 *
 * @group save
 * @return void
 */
	public function testSaveUpdatePrimaryKeyNotModified() {
		$table = $this->getMock(
			'\Cake\ORM\Table',
			['_buildQuery'],
			[['table' => 'users', 'connection' => $this->connection]]
		);

		$query = $this->getMock(
			'\Cake\ORM\Query',
			['executeStatement', 'addDefaultTypes', 'set'],
			[null, $table]
		);

		$table->expects($this->once())->method('_buildQuery')
			->will($this->returnValue($query));

		$statement = $this->getMock('\Cake\Database\Statement\StatementDecorator');
		$statement->expects($this->once())->method('rowCount')
			->will($this->returnValue(1));

		$query->expects($this->once())->method('executeStatement')
			->will($this->returnValue($statement));

		$query->expects($this->once())->method('set')
			->with(['username' => 'baggins'])
			->will($this->returnValue($query));

		$entity = new \Cake\ORM\Entity([
			'id' => 2,
			'username' => 'baggins'
		], ['markNew' => false]);
		$this->assertSame($entity, $table->save($entity));
	}

/**
 * Tests that passing only the primary key to save will not execute any queries
 * but still return success
 *
 * @group save
 * @return void
 */
	public function testUpdateNoChange() {
		$table = $this->getMock(
			'\Cake\ORM\Table',
			['_buildQuery'],
			[['table' => 'users', 'connection' => $this->connection]]
		);
		$table->expects($this->never())->method('_buildQuery');
		$entity = new \Cake\ORM\Entity([
			'id' => 2,
		], ['markNew' => false]);
		$this->assertSame($entity, $table->save($entity));
	}

/**
 * Tests that failing to pass a primary key to save will result in exception
 *
 * @group save
 * @expectedException \InvalidArgumentException
 * @return void
 */
	public function testUpdateNoPrimaryButOtherKeys() {
		$table = $this->getMock(
			'\Cake\ORM\Table',
			['_buildQuery'],
			[['table' => 'users', 'connection' => $this->connection]]
		);
		$table->expects($this->never())->method('_buildQuery');
		$entity = new \Cake\ORM\Entity([
			'username' => 'mariano',
		], ['markNew' => false]);
		$this->assertSame($entity, $table->save($entity));
	}

/**
 * Test simple delete.
 *
 * @return void
 */
	public function testDelete() {
		$table = TableRegistry::get('users');
		$conditions = [
			'limit' => 1,
			'conditions' => [
				'username' => 'nate'
			]
		];
		$query = $table->find('all', $conditions);
		$entity = $query->first();
		$result = $table->delete($entity);
		$this->assertTrue($result);

		$query = $table->find('all', $conditions);
		$results = $query->execute();
		$this->assertCount(0, $results, 'Find should fail.');
	}

/**
 * Test delete with dependent records
 *
 * @return void
 */
	public function testDeleteDependent() {
		$table = TableRegistry::get('authors');
		$table->hasOne('articles', [
			'foreignKey' => 'author_id',
			'dependent' => true,
		]);

		$query = $table->find('all')->where(['id' => 1]);
		$entity = $query->first();
		$result = $table->delete($entity);

		$articles = $table->association('articles')->target();
		$query = $articles->find('all', [
			'conditions' => [
				'author_id' => $entity->id
			]
		]);
		$this->assertNull($query->execute()->one(), 'Should not find any rows.');
	}

/**
 * Test delete with dependent = false does not cascade.
 *
 * @return void
 */
	public function testDeleteNoDependentNoCascade() {
		$table = TableRegistry::get('authors');
		$table->hasMany('article', [
			'foreignKey' => 'author_id',
			'dependent' => false,
		]);

		$query = $table->find('all')->where(['id' => 1]);
		$entity = $query->first();
		$result = $table->delete($entity);

		$articles = $table->association('articles')->target();
		$query = $articles->find('all')->where(['author_id' => $entity->id]);
		$this->assertCount(2, $query->execute(), 'Should find rows.');
	}

/**
 * Test delete with BelongsToMany
 *
 * @return void
 */
	public function testDeleteBelongsToMany() {
		$table = TableRegistry::get('articles');
		$table->belongsToMany('tag', [
			'foreignKey' => 'article_id',
			'joinTable' => 'articles_tags'
		]);
		$query = $table->find('all')->where(['id' => 1]);
		$entity = $query->first();
		$table->delete($entity);

		$pivot = $table->association('tags')->pivot();
		$query = $pivot->find('all')->where(['article_id' => 1]);
		$this->assertNull($query->execute()->one(), 'Should not find any rows.');
	}

/**
 * Test delete callbacks
 *
 * @return void
 */
	public function testDeleteCallbacks() {
		$entity = new \Cake\ORM\Entity(['id' => 1, 'name' => 'mark']);
		$options = new \ArrayObject(['atomic' => true]);

		$mock = $this->getMock('Cake\Event\EventManager');

		$mock->expects($this->at(0))
			->method('attach');

		$mock->expects($this->at(1))
			->method('dispatch')
			->with($this->logicalAnd(
				$this->attributeEqualTo('_name', 'Model.beforeDelete'),
				$this->attributeEqualTo(
					'data',
					['entity' => $entity, 'options' => $options]
				)
			));

		$mock->expects($this->at(2))
			->method('dispatch')
			->with($this->logicalAnd(
				$this->attributeEqualTo('_name', 'Model.afterDelete'),
				$this->attributeEqualTo(
					'data',
					['entity' => $entity, 'options' => $options]
				)
			));

		$table = TableRegistry::get('users', ['eventManager' => $mock]);
		$entity->isNew(false);
		$table->delete($entity);
	}

/**
 * Test delete beforeDelete can abort the delete.
 *
 * @return void
 */
	public function testDeleteBeforeDeleteAbort() {
		$entity = new \Cake\ORM\Entity(['id' => 1, 'name' => 'mark']);
		$options = new \ArrayObject(['atomic' => true, 'cascade' => true]);

		$mock = $this->getMock('Cake\Event\EventManager');
		$mock->expects($this->once())
			->method('dispatch')
			->will($this->returnCallback(function($event) {
				$event->stopPropagation();
			}));

		$table = TableRegistry::get('users', ['eventManager' => $mock]);
		$entity->isNew(false);
		$result = $table->delete($entity);
		$this->assertNull($result);
	}

/**
 * Test delete beforeDelete return result
 *
 * @return void
 */
	public function testDeleteBeforeDeleteReturnResult() {
		$entity = new \Cake\ORM\Entity(['id' => 1, 'name' => 'mark']);
		$options = new \ArrayObject(['atomic' => true, 'cascade' => true]);

		$mock = $this->getMock('Cake\Event\EventManager');
		$mock->expects($this->once())
			->method('dispatch')
			->will($this->returnCallback(function($event) {
				$event->stopPropagation();
				$event->result = 'got stopped';
			}));

		$table = TableRegistry::get('users', ['eventManager' => $mock]);
		$entity->isNew(false);
		$result = $table->delete($entity);
		$this->assertEquals('got stopped', $result);
	}

/**
 * Test deleting new entities does nothing.
 *
 * @return void
 */
	public function testDeleteIsNew() {
		$entity = new \Cake\ORM\Entity(['id' => 1, 'name' => 'mark']);

		$table = $this->getMock(
			'Cake\ORM\Table',
			['_buildQuery'],
			[['connection' => $this->connection]]
		);
		$table->expects($this->never())
			->method('_buildQuery');

		$entity->isNew(true);
		$result = $table->delete($entity);
		$this->assertFalse($result);
	}

/**
 * test hasField()
 *
 * @return void
 */
	public function testHasField() {
		$table = TableRegistry::get('articles');
		$this->assertFalse($table->hasField('nope'), 'Should not be there.');
		$this->assertTrue($table->hasField('title'), 'Should be there.');
		$this->assertTrue($table->hasField('body'), 'Should be there.');
	}

/**
 * Tests that there exists a default validator
 *
 * @return void
 */
	public function testValidatorDefault() {
		$table = new Table();
		$validator = $table->validator();
		$this->assertInstanceOf('\Cake\Validation\Validator', $validator);
		$default = $table->validator('default');
		$this->assertSame($validator, $default);
	}

/**
 * Tests that it is possible to define custom validator methods
 *
 * @return void
 */
	public function functionTestValidationWithDefiner() {
		$table = $this->getMock('\Cake\ORM\Table', ['validationForOtherStuff']);
		$table->expects($this->once())->method('validationForOtherStuff')
			->will($this->returnArgument(0));
		$other = $table->validator('forOtherStuff');
		$this->assertInstanceOf('\Cake\Validation\Validator', $other);
		$this->assertNotSame($other, $table->validator());
	}

/**
 * Tests that it is possible to set a custom validator under a name
 *
 * @return void
 */
	public function testValidatorSetter() {
		$table = new Table;
		$validator = new \Cake\Validation\Validator;
		$table->validator('other', $validator);
		$this->assertSame($validator, $table->validator('other'));
	}

/**
 * Tests saving with validation
 *
 * @return void
 */
	public function testSaveWithValidationError() {
		$entity = new \Cake\ORM\Entity([
			'username' => 'superuser'
		]);
		$table = TableRegistry::get('users');
		$table->validator()->validatePresence('password');
		$this->assertFalse($table->save($entity));
		$this->assertNotEmpty($entity->errors('password'));
		$this->assertSame($entity, $table->validator()->provider('entity'));
		$this->assertSame($table, $table->validator()->provider('table'));
	}

/**
 * Tests saving with validation and field list
 *
 * @return void
 */
	public function testSaveWithValidationErrorAndFieldList() {
		$entity = new \Cake\ORM\Entity([
			'username' => 'superuser'
		]);
		$table = TableRegistry::get('users');
		$table->validator()->validatePresence('password');
		$this->assertFalse($table->save($entity));
		$this->assertNotEmpty($entity->errors('password'));
	}

/**
 * Tests using a custom validation object when saving
 *
 * @return void
 */
	public function testSaveWithDifferentValidator() {
		$entity = new \Cake\ORM\Entity([
			'username' => 'superuser'
		]);
		$table = TableRegistry::get('users');
		$validator = (new Validator)->validatePresence('password');
		$table->validator('custom', $validator);
		$this->assertFalse($table->save($entity, ['validate' => 'custom']));
		$this->assertNotEmpty($entity->errors('password'));

		$this->assertSame($entity, $table->save($entity), 'default was not used');
	}

/**
 * Tests saving with successful validation
 *
 * @return void
 */
	public function testSaveWithValidationSuccess() {
		$entity = new \Cake\ORM\Entity([
			'username' => 'superuser',
			'password' => 'hey'
		]);
		$table = TableRegistry::get('users');
		$table->validator()->validatePresence('password');
		$this->assertSame($entity, $table->save($entity));
		$this->assertEmpty($entity->errors('password'));
	}

/**
 * Tests beforeValidate event is triggered
 *
 * @return void
 */
	public function testBeforeValidate() {
		$entity = new \Cake\ORM\Entity([
			'username' => 'superuser'
		]);
		$table = TableRegistry::get('users');
		$table->getEventManager()->attach(function($ev, $en, $opt, $val) use ($entity) {
			$this->assertSame($entity, $en);
			$this->assertTrue($opt['crazy']);
			$this->assertSame($ev->subject()->validator('default'), $val);
			$val->validatePresence('password');
		}, 'Model.beforeValidate');
		$this->assertFalse($table->save($entity, ['crazy' => true]));
		$this->assertNotEmpty($entity->errors('password'));
	}

/**
 * Tests that beforeValidate can set the validation result
 *
 * @return void
 */
	public function testBeforeValidateSetResult() {
		$entity = new \Cake\ORM\Entity([
			'username' => 'superuser'
		]);
		$table = TableRegistry::get('users');
		$table->getEventManager()->attach(function($ev, $en) {
			$en->errors('username', 'Not good');
			return false;
		}, 'Model.beforeValidate');
		$this->assertFalse($table->save($entity));
		$this->assertEquals(['Not good'], $entity->errors('username'));
	}

/**
 * Tests that afterValidate is triggered and can set a result
 *
 * @return void
 */
	public function testAfterValidate() {
		$entity = new \Cake\ORM\Entity([
			'username' => 'superuser',
			'password' => 'hey'
		]);
		$table = TableRegistry::get('users');
		$table->validator()->validatePresence('password');
		$table->getEventManager()->attach(function($ev, $en, $opt, $val) use ($entity) {
			$this->assertSame($entity, $en);
			$this->assertTrue($opt['crazy']);
			$this->assertSame($ev->subject()->validator('default'), $val);

			$en->errors('username', 'Not good');
			return false;
		}, 'Model.afterValidate');

		$this->assertFalse($table->save($entity, ['crazy' => true]));
		$this->assertEmpty($entity->errors('password'));
		$this->assertEquals(['Not good'], $entity->errors('username'));
	}

/**
 * Test magic findByXX method.
 *
 * @return void
 */
	public function testMagicFindDefaultToAll() {
		$table = TableRegistry::get('Users');

		$result = $table->findByUsername('garrett');
		$this->assertInstanceOf('Cake\ORM\Query', $result);
		$expected = new QueryExpression(['username' => 'garrett'], ['username' => 'string']);
		$this->assertEquals($expected, $result->clause('where'));
	}

/**
 * Test magic findByXX errors on missing arguments.
 *
 * @expectedException Cake\Error\Exception
 * @expectedExceptionMessage Not enough arguments to magic finder. Got 0 required 1
 * @return void
 */
	public function testMagicFindError() {
		$table = TableRegistry::get('Users');

		$table->findByUsername();
	}

/**
 * Test magic findByXX errors on missing arguments.
 *
 * @expectedException Cake\Error\Exception
 * @expectedExceptionMessage Not enough arguments to magic finder. Got 1 required 2
 * @return void
 */
	public function testMagicFindErrorMissingField() {
		$table = TableRegistry::get('Users');

		$table->findByUsernameAndId('garrett');
	}

/**
 * Test magic findByXX errors when there is a mix of or & and.
 *
 * @expectedException Cake\Error\Exception
 * @expectedExceptionMessage Cannot mix "and" & "or" in a magic finder. Use find() instead.
 * @return void
 */
	public function testMagicFindErrorMixOfOperators() {
		$table = TableRegistry::get('Users');

		$table->findByUsernameAndIdOrPassword('garrett', 1, 'sekret');
	}

/**
 * Test magic findByXX method.
 *
 * @return void
 */
	public function testMagicFindFirstAnd() {
		$table = TableRegistry::get('Users');

		$result = $table->findByUsernameAndId('garrett', 4);
		$this->assertInstanceOf('Cake\ORM\Query', $result);
		$expected = new QueryExpression(
			['username' => 'garrett', 'id' => 4],
			['username' => 'string', 'id' => 'integer'],
			'AND'
		);
		$this->assertEquals($expected, $result->clause('where'));
	}

/**
 * Test magic findByXX method.
 *
 * @return void
 */
	public function testMagicFindFirstOr() {
		$table = TableRegistry::get('Users');

		$result = $table->findByUsernameOrId('garrett', 4);
		$this->assertInstanceOf('Cake\ORM\Query', $result);
		$expected = new QueryExpression();
		$expected->add([
			'OR' => [
				'username' => 'garrett',
				'id' => 4
			]],
			['username' => 'string', 'id' => 'integer']
		);
		$this->assertEquals($expected, $result->clause('where'));
	}

/**
 * Test magic findAllByXX method.
 *
 * @return void
 */
	public function testMagicFindAll() {
		$table = TableRegistry::get('Articles');

		$result = $table->findAllByAuthorId(1);
		$this->assertInstanceOf('Cake\ORM\Query', $result);
		$this->assertNull($result->clause('limit'));
		$expected = new QueryExpression(
			['author_id' => 1],
			['author_id' => 'integer'],
			'AND'
		);
		$this->assertEquals($expected, $result->clause('where'));
	}

/**
 * Test magic findAllByXX method.
 *
 * @return void
 */
	public function testMagicFindAllAnd() {
		$table = TableRegistry::get('Users');

		$result = $table->findAllByAuthorIdAndPublished(1, 'Y');
		$this->assertInstanceOf('Cake\ORM\Query', $result);
		$this->assertNull($result->clause('limit'));
		$expected = new QueryExpression(
			['author_id' => 1, 'published' => 'Y']
		);
		$this->assertEquals($expected, $result->clause('where'));
	}

/**
 * Test magic findAllByXX method.
 *
 * @return void
 */
	public function testMagicFindAllOr() {
		$table = TableRegistry::get('Users');

		$result = $table->findAllByAuthorIdOrPublished(1, 'Y');
		$this->assertInstanceOf('Cake\ORM\Query', $result);
		$this->assertNull($result->clause('limit'));
		$expected = new QueryExpression();
		$expected->add(
			['or' => ['author_id' => 1, 'published' => 'Y']]
		);
		$this->assertEquals($expected, $result->clause('where'));
		$this->assertNull($result->clause('order'));
	}

/**
 * Test the behavior method.
 *
 * @return void
 */
	public function testBehaviorIntrospection() {
		$table = TableRegistry::get('users');
		$this->assertEquals([], $table->behaviors(), 'no loaded behaviors');

		$table->addBehavior('Timestamp');
		$this->assertEquals(['Timestamp'], $table->behaviors(), 'Should have loaded behavior');
		$this->assertTrue($table->hasBehavior('Timestamp'), 'should be true on loaded behavior');
		$this->assertFalse($table->hasBehavior('Tree'), 'should be false on unloaded behavior');
	}
}
