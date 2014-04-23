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
namespace Cake\Test\TestCase\ORM;

use Cake\Core\Configure;
use Cake\Database\Expression\OrderByExpression;
use Cake\Database\Expression\QueryExpression;
use Cake\Database\TypeMap;
use Cake\Datasource\ConnectionManager;
use Cake\ORM\Table;
use Cake\ORM\TableRegistry;
use Cake\Utility\Time;
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
 * @var int
 */
	public static $nextUserId = 5;

	public function setUp() {
		parent::setUp();
		$this->connection = ConnectionManager::get('test');
		Configure::write('App.namespace', 'TestApp');

		$this->usersTypeMap = new TypeMap([
			'Users.id' => 'integer',
			'id' => 'integer',
			'Users.username' => 'string',
			'username' => 'string',
			'Users.password' => 'string',
			'password' => 'string',
			'Users.created' => 'timestamp',
			'created' => 'timestamp',
			'Users.updated' => 'timestamp',
			'updated' => 'timestamp',
		]);
		$this->articlesTypeMap = new TypeMap([
			'Articles.id' => 'integer',
			'id' => 'integer',
			'Articles.title' => 'string',
			'title' => 'string',
			'Articles.author_id' => 'integer',
			'author_id' => 'integer',
			'Articles.body' => 'text',
			'body' => 'text',
			'Articles.published' => 'string',
			'published' => 'string',
		]);
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
		$table = new Table([
			'table' => 'users',
			'schema' => [
				'id' => ['type' => 'integer'],
				'_constraints' => ['primary' => ['type' => 'primary', 'columns' => ['id']]]
			]
		]);
		$this->assertEquals('id', $table->primaryKey());
		$table->primaryKey('thingID');
		$this->assertEquals('thingID', $table->primaryKey());

		$table->primaryKey(['thingID', 'user_id']);
		$this->assertEquals(['thingID', 'user_id'], $table->primaryKey());
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
				'foo' => ['type' => 'string'],
				'_constraints' => ['primary' => ['type' => 'primary', 'columns' => ['id']]]
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
				'foo' => ['type' => 'string'],
				'_constraints' => ['primary' => ['type' => 'primary', 'columns' => ['id']]]
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
				'created' => new Time('2007-03-17 01:16:23'),
				'updated' => new Time('2007-03-17 01:18:31'),
			],
			[
				'id' => 2,
				'username' => 'nate',
				'password' => '$2a$10$u05j8FjsvLBNdfhBhc21LOuVMpzpabVXQ9OpC2wO3pSO0q6t7HHMO',
				'created' => new Time('2008-03-17 01:18:23'),
				'updated' => new Time('2008-03-17 01:20:31'),
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
			->where(['created >=' => new Time('2010-01-22 00:00')])
			->hydrate(false)
			->order('id');
		$expected = [
			['id' => 3, 'username' => 'larry'],
			['id' => 4, 'username' => 'garrett']
		];
		$this->assertSame($expected, $query->toArray());

		$query->orWhere(['users.created' => new Time('2008-03-17 01:18:23')]);
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

		$result = $table->find('all')->all();
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
		$this->assertEquals($expected, $query->all());
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
		$this->assertSame('things_tags', $belongsToMany->junction()->table());
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
 * @expectedException \Cake\Database\Exception
 */
	public function testUpdateAllFailure() {
		$table = $this->getMock(
			'Cake\ORM\Table',
			['query'],
			[['table' => 'users', 'connection' => $this->connection]]
		);
		$query = $this->getMock('Cake\ORM\Query', ['execute'], [$this->connection, $table]);
		$table->expects($this->once())
			->method('query')
			->will($this->returnValue($query));

		$query->expects($this->once())
			->method('execute')
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
 * @expectedException \Cake\Database\Exception
 */
	public function testDeleteAllFailure() {
		$table = $this->getMock(
			'Cake\ORM\Table',
			['query'],
			[['table' => 'users', 'connection' => $this->connection]]
		);
		$query = $this->getMock('Cake\ORM\Query', ['execute'], [$this->connection, $table]);
		$table->expects($this->once())
			->method('query')
			->will($this->returnValue($query));

		$query->expects($this->once())
			->method('execute')
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
			['query', 'findAll'],
			[['table' => 'users', 'connection' => $this->connection]]
		);
		$query = $this->getMock('Cake\ORM\Query', [], [$this->connection, $table]);
		$table->expects($this->once())
			->method('query')
			->will($this->returnValue($query));

		$options = ['fields' => ['a', 'b'], 'connections' => ['a >' => 1]];
		$query->expects($this->any())
			->method('select')
			->will($this->returnSelf());

		$query->expects($this->once())->method('getOptions')
			->will($this->returnValue(['connections' => ['a >' => 1]]));
		$query->expects($this->once())
			->method('applyOptions')
			->with($options);

		$table->expects($this->once())->method('findAll')
			->with($query, ['connections' => ['a >' => 1]]);
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
			->select(['id', 'username', 'odd' => new QueryExpression('id % 2')])
			->hydrate(false)
			->order('id');
		$expected = [
			1 => [
				1 => 'mariano',
				3 => 'larry'
			],
			0 => [
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
			->select(['id', 'username', 'odd' => new QueryExpression('id % 2')])
			->hydrate(true)
			->order('id');
		$expected = [
			1 => [
				1 => 'mariano',
				3 => 'larry'
			],
			0 => [
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
	public function testTableClassInApp() {
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
	public function testTableClassInPlugin() {
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
 * @expectedException \Cake\ORM\Error\MissingEntityException
 * @expectedExceptionMessage Entity class FooUser could not be found.
 * @return void
 */
	public function testTableClassNonExisting() {
		$table = new Table;
		$this->assertFalse($table->entityClass('FooUser'));
	}

/**
 * Tests getting the entityClass based on conventions for the entity
 * namespace
 *
 * @return void
 */
	public function testTableClassConventionForAPP() {
		$table = new \TestApp\Model\Table\ArticlesTable;
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
		$table = new \TestApp\Model\Table\ArticlesTable([
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
		$table = new \TestApp\Model\Table\ArticlesTable([
			'connection' => $this->connection,
		]);
		$result = $table->find('all')->contain(['authors' => ['articles']])->first();
		$this->assertCount(2, $result->author->articles);
		foreach ($result->author->articles as $article) {
			$this->assertInstanceOf('TestApp\Model\Entity\Article', $article);
		}
	}

/**
 * Tests that the correct table and entity are loaded for the join association in
 * a belongsToMany setup
 *
 * @return void
 */
	public function testReciprocalBelongsToMany() {
		$table = new \TestApp\Model\Table\ArticlesTable([
			'connection' => $this->connection,
		]);
		$result = $table->find('all')->contain(['tags'])->first();
		$this->assertInstanceOf('TestApp\Model\Entity\Tag', $result->tags[0]);
		$this->assertInstanceOf(
			'TestApp\Model\Entity\ArticlesTag',
			$result->tags[0]->_joinData
		);
	}

/**
 * Tests that recently fetched entities are always clean
 *
 * @return void
 */
	public function testFindCleanEntities() {
		$table = new \TestApp\Model\Table\ArticlesTable([
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
				$this->assertFalse($article->tag[0]->_joinData->dirty('tag_id'));
			}
		}
	}

/**
 * Tests that recently fetched entities are marked as not new
 *
 * @return void
 */
	public function testFindPersistedEntities() {
		$table = new \TestApp\Model\Table\ArticlesTable([
			'connection' => $this->connection,
		]);
		$results = $table->find('all')->contain(['tags', 'authors'])->toArray();
		$this->assertCount(3, $results);
		foreach ($results as $article) {
			$this->assertFalse($article->isNew());
			foreach ((array)$article->tag as $tag) {
				$this->assertFalse($tag->isNew());
				$this->assertFalse($tag->_joinData->isNew());
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
 * @expectedException \Cake\ORM\Error\MissingBehaviorException
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
			'created' => new Time('2013-10-10 00:00'),
			'updated' => new Time('2013-10-10 00:00')
		]);
		$table = TableRegistry::get('users');
		$this->assertSame($entity, $table->save($entity));
		$this->assertEquals($entity->id, self::$nextUserId);

		$row = $table->find('all')->where(['id' => self::$nextUserId])->first();
		$this->assertEquals($entity->toArray(), $row->toArray());
	}

/**
 * Test that saving a new empty entity does nothing.
 *
 * @group save
 * @return void
 */
	public function testSaveNewEmptyEntity() {
		$entity = new \Cake\ORM\Entity();
		$table = TableRegistry::get('users');
		$this->assertFalse($table->save($entity));
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
			'created' => new Time('2013-10-10 00:00'),
			'updated' => new Time('2013-10-10 00:00'),
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
			'created' => new Time('2013-10-10 00:00'),
			'updated' => new Time('2013-10-10 00:00')
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
			'created' => new Time('2013-10-10 00:00'),
			'updated' => new Time('2013-10-10 00:00')
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
			'created' => new Time('2013-10-10 00:00'),
			'updated' => new Time('2013-10-10 00:00')
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
			'created' => new Time('2013-10-10 00:00'),
			'updated' => new Time('2013-10-10 00:00')
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
			['query', 'exists'],
			[['table' => 'users', 'connection' => $this->connection]]
		);
		$query = $this->getMock(
			'\Cake\ORM\Query',
			['execute', 'addDefaultTypes'],
			[null, $table]
		);
		$statement = $this->getMock('\Cake\Database\Statement\StatementDecorator');
		$data = new \Cake\ORM\Entity([
			'username' => 'superuser',
			'created' => new Time('2013-10-10 00:00'),
			'updated' => new Time('2013-10-10 00:00')
		]);

		$table->expects($this->once())->method('exists')
			->will($this->returnValue(false));
		$table->expects($this->once())->method('query')
			->will($this->returnValue($query));

		$query->expects($this->once())->method('execute')
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
			'created' => new Time('2013-10-10 00:00'),
			'updated' => new Time('2013-10-10 00:00')
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
			['query', 'connection', 'exists'],
			[['table' => 'users']]
		);
		$query = $this->getMock(
			'\Cake\ORM\Query',
			['execute', 'addDefaultTypes'],
			[null, $table]
		);

		$table->expects($this->once())->method('exists')
			->will($this->returnValue(false));

		$table->expects($this->any())->method('connection')
			->will($this->returnValue($connection));

		$table->expects($this->once())->method('query')
			->will($this->returnValue($query));

		$connection->expects($this->once())->method('begin');
		$connection->expects($this->once())->method('rollback');
		$query->expects($this->once())->method('execute')
			->will($this->throwException(new \PDOException));

		$data = new \Cake\ORM\Entity([
			'username' => 'superuser',
			'created' => new Time('2013-10-10 00:00'),
			'updated' => new Time('2013-10-10 00:00')
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
			['query', 'connection', 'exists'],
			[['table' => 'users']]
		);
		$query = $this->getMock(
			'\Cake\ORM\Query',
			['execute', 'addDefaultTypes'],
			[null, $table]
		);

		$table->expects($this->once())->method('exists')
			->will($this->returnValue(false));

		$table->expects($this->any())->method('connection')
			->will($this->returnValue($connection));

		$table->expects($this->once())->method('query')
			->will($this->returnValue($query));

		$statement = $this->getMock('\Cake\Database\Statement\StatementDecorator');
		$statement->expects($this->once())
			->method('rowCount')
			->will($this->returnValue(0));
		$connection->expects($this->once())->method('begin');
		$connection->expects($this->once())->method('rollback');
		$query->expects($this->once())
			->method('execute')
			->will($this->returnValue($statement));

		$data = new \Cake\ORM\Entity([
			'username' => 'superuser',
			'created' => new Time('2013-10-10 00:00'),
			'updated' => new Time('2013-10-10 00:00')
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
			'created' => new Time('2013-10-10 00:00'),
			'updated' => new Time('2013-10-10 00:00')
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
			'created' => new Time('2013-10-10 00:00'),
			'updated' => new Time('2013-10-10 00:00')
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
			'created' => new Time('2013-10-10 00:00'),
			'updated' => new Time('2013-10-10 00:00')
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
			['query'],
			[['table' => 'users', 'connection' => $this->connection]]
		);

		$query = $this->getMock(
			'\Cake\ORM\Query',
			['execute', 'addDefaultTypes', 'set'],
			[null, $table]
		);

		$table->expects($this->once())->method('query')
			->will($this->returnValue($query));

		$statement = $this->getMock('\Cake\Database\Statement\StatementDecorator');
		$statement->expects($this->once())
			->method('errorCode')
			->will($this->returnValue('00000'));

		$query->expects($this->once())
			->method('execute')
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
			['query'],
			[['table' => 'users', 'connection' => $this->connection]]
		);
		$table->expects($this->never())->method('query');
		$entity = new \Cake\ORM\Entity([
			'id' => 2,
		], ['markNew' => false]);
		$this->assertSame($entity, $table->save($entity));
	}

/**
 * Tests that passing only the primary key to save will not execute any queries
 * but still return success
 *
 * @group save
 * @group integration
 * @return void
 */
	public function testUpdateDirtyNoActualChanges() {
		$table = TableRegistry::get('Articles');
		$entity = $table->get(1);

		$entity->accessible('*', true);
		$entity->set($entity->toArray());
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
			['query'],
			[['table' => 'users', 'connection' => $this->connection]]
		);
		$table->expects($this->never())->method('query');
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
		$this->assertNull($query->all()->first(), 'Should not find any rows.');
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

		$junction = $table->association('tags')->junction();
		$query = $junction->find('all')->where(['article_id' => 1]);
		$this->assertNull($query->all()->first(), 'Should not find any rows.');
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
			['query'],
			[['connection' => $this->connection]]
		);
		$table->expects($this->never())
			->method('query');

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
		$this->assertSame($table, $validator->provider('table'));
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
		$this->assertSame($table, $other->provider('table'));
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
		$this->assertSame($table, $validator->provider('table'));
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

		$expected = new QueryExpression(['username' => 'garrett'], $this->usersTypeMap);
		$this->assertEquals($expected, $result->clause('where'));
	}

/**
 * Test magic findByXX errors on missing arguments.
 *
 * @expectedException \Cake\Error\Exception
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
 * @expectedException \Cake\Error\Exception
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
 * @expectedException \Cake\Error\Exception
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

		$expected = new QueryExpression(['username' => 'garrett', 'id' => 4], $this->usersTypeMap);
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

		$expected = new QueryExpression([], $this->usersTypeMap);
		$expected->add([
			'OR' => [
				'username' => 'garrett',
				'id' => 4
			]]
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

		$expected = new QueryExpression(['author_id' => 1], $this->articlesTypeMap);
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
			['author_id' => 1, 'published' => 'Y'],
			$this->usersTypeMap
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
		$expected->typeMap()->defaults([
			'Users.id' => 'integer',
			'id' => 'integer',
			'Users.username' => 'string',
			'username' => 'string',
			'Users.password' => 'string',
			'password' => 'string',
			'Users.created' => 'timestamp',
			'created' => 'timestamp',
			'Users.updated' => 'timestamp',
			'updated' => 'timestamp',
		]);
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

/**
 * Tests saving belongsTo association
 *
 * @group save
 * @return void
 */
	public function testSaveBelongsTo() {
		$entity = new \Cake\ORM\Entity([
			'title' => 'A Title',
			'body' => 'A body'
		]);
		$entity->author = new \Cake\ORM\Entity([
			'name' => 'Jose'
		]);

		$table = TableRegistry::get('articles');
		$table->belongsTo('authors');
		$this->assertSame($entity, $table->save($entity));
		$this->assertFalse($entity->isNew());
		$this->assertFalse($entity->author->isNew());
		$this->assertEquals(5, $entity->author->id);
		$this->assertEquals(5, $entity->get('author_id'));
	}

/**
 * Tests saving belongsTo association and get a validation error
 *
 * @group save
 * @return void
 */
	public function testsSaveBelongsToWithValidationError() {
		$entity = new \Cake\ORM\Entity([
			'title' => 'A Title',
			'body' => 'A body'
		]);
		$entity->author = new \Cake\ORM\Entity([
			'name' => 'Jose'
		]);

		$table = TableRegistry::get('articles');
		$table->belongsTo('authors');
		$table->association('authors')
			->target()
			->validator()
			->add('name', 'num', ['rule' => 'numeric']);

		$this->assertFalse($table->save($entity));
		$this->assertTrue($entity->isNew());
		$this->assertTrue($entity->author->isNew());
		$this->assertNull($entity->get('author_id'));
		$this->assertNotEmpty($entity->author->errors('name'));
	}

/**
 * Tests saving hasOne association
 *
 * @group save
 * @return void
 */
	public function testSaveHasOne() {
		$entity = new \Cake\ORM\Entity([
			'name' => 'Jose'
		]);
		$entity->article = new \Cake\ORM\Entity([
			'title' => 'A Title',
			'body' => 'A body'
		]);

		$table = TableRegistry::get('authors');
		$table->hasOne('articles');
		$this->assertSame($entity, $table->save($entity));
		$this->assertFalse($entity->isNew());
		$this->assertFalse($entity->article->isNew());
		$this->assertEquals(4, $entity->article->id);
		$this->assertEquals(5, $entity->article->get('author_id'));
		$this->assertFalse($entity->article->dirty('author_id'));
	}

/**
 * Tests saving associations only saves associations
 * if they are entities.
 *
 * @group save
 * @return void
 */
	public function testSaveOnlySaveAssociatedEntities() {
		$entity = new \Cake\ORM\Entity([
			'name' => 'Jose'
		]);

		// Not an entity.
		$entity->article = [
			'title' => 'A Title',
			'body' => 'A body'
		];

		$table = TableRegistry::get('authors');
		$table->hasOne('articles');

		$this->assertSame($entity, $table->save($entity));
		$this->assertFalse($entity->isNew());
		$this->assertInternalType('array', $entity->article);
	}

/**
 * Tests saving hasOne association and returning a validation error will
 * abort the saving process
 *
 * @group save
 * @return void
 */
	public function testSaveHasOneWithValidationError() {
		$entity = new \Cake\ORM\Entity([
			'name' => 'Jose'
		]);
		$entity->article = new \Cake\ORM\Entity([
			'title' => 'A Title',
			'body' => 'A body'
		]);

		$table = TableRegistry::get('authors');
		$table->hasOne('articles');
		$table->association('articles')
			->target()
			->validator()
			->add('title', 'num', ['rule' => 'numeric']);
		$this->assertFalse($table->save($entity));
		$this->assertTrue($entity->isNew());
		$this->assertTrue($entity->article->isNew());
		$this->assertNull($entity->article->id);
		$this->assertNull($entity->article->get('author_id'));
		$this->assertTrue($entity->article->dirty('author_id'));
		$this->assertNotEmpty($entity->article->errors('title'));
	}

/**
 * Tests saving multiple entities in a hasMany association
 *
 * @return void
 */
	public function testSaveHasMany() {
		$entity = new \Cake\ORM\Entity([
			'name' => 'Jose'
		]);
		$entity->articles = [
			new \Cake\ORM\Entity([
				'title' => 'A Title',
				'body' => 'A body'
			]),
			new \Cake\ORM\Entity([
				'title' => 'Another Title',
				'body' => 'Another body'
			])
		];

		$table = TableRegistry::get('authors');
		$table->hasMany('articles');
		$this->assertSame($entity, $table->save($entity));
		$this->assertFalse($entity->isNew());
		$this->assertFalse($entity->articles[0]->isNew());
		$this->assertFalse($entity->articles[1]->isNew());
		$this->assertEquals(4, $entity->articles[0]->id);
		$this->assertEquals(5, $entity->articles[1]->id);
		$this->assertEquals(5, $entity->articles[0]->author_id);
		$this->assertEquals(5, $entity->articles[1]->author_id);
	}

/**
 * Tests saving multiple entities in a hasMany association and getting and
 * error while saving one of them. It should abort all the save operation
 * when options are set to defaults
 *
 * @return void
 */
	public function testSaveHasManyWithErrorsAtomic() {
		$entity = new \Cake\ORM\Entity([
			'name' => 'Jose'
		]);
		$entity->articles = [
			new \Cake\ORM\Entity([
				'title' => '1',
				'body' => 'A body'
			]),
			new \Cake\ORM\Entity([
				'title' => 'Another Title',
				'body' => 'Another body'
			])
		];

		$table = TableRegistry::get('authors');
		$table->hasMany('articles');
		$table->association('articles')
			->target()
			->validator()
			->add('title', 'num', ['rule' => 'numeric']);

		$this->assertFalse($table->save($entity));
		$this->assertTrue($entity->isNew());
		$this->assertNull($entity->articles[0]->isNew());
		$this->assertNull($entity->articles[1]->isNew());
		$this->assertNull($entity->articles[0]->id);
		$this->assertNull($entity->articles[1]->id);
		$this->assertNull($entity->articles[0]->author_id);
		$this->assertNull($entity->articles[1]->author_id);
		$this->assertEmpty($entity->articles[0]->errors());
		$this->assertNotEmpty($entity->articles[1]->errors());
	}

/**
 * Tests that it is possible to continue saving hasMany associations
 * even if any of the records fail validation when atomic is set
 * to false
 *
 * @return void
 */
	public function testSaveHasManyWithErrorsNonAtomic() {
		$entity = new \Cake\ORM\Entity([
			'name' => 'Jose'
		]);
		$entity->articles = [
			new \Cake\ORM\Entity([
				'title' => 'A title',
				'body' => 'A body'
			]),
			new \Cake\ORM\Entity([
				'title' => '1',
				'body' => 'Another body'
			])
		];

		$table = TableRegistry::get('authors');
		$table->hasMany('articles');
		$table->association('articles')
			->target()
			->validator()
			->add('title', 'num', ['rule' => 'numeric']);

		$this->assertSame($entity, $table->save($entity, ['atomic' => false]));
		$this->assertFalse($entity->isNew());
		$this->assertTrue($entity->articles[0]->isNew());
		$this->assertFalse($entity->articles[1]->isNew());
		$this->assertEquals(4, $entity->articles[1]->id);
		$this->assertNull($entity->articles[0]->id);
		$this->assertEquals(5, $entity->articles[0]->author_id);
		$this->assertEquals(5, $entity->articles[1]->author_id);
	}

/**
 * Tests saving hasOne association and returning a validation error will
 * not abort the saving process if atomic is set to false
 *
 * @group save
 * @return void
 */
	public function testSaveHasOneWithValidationErrorNonAtomic() {
		$entity = new \Cake\ORM\Entity([
			'name' => 'Jose'
		]);
		$entity->article = new \Cake\ORM\Entity([
			'title' => 'A Title',
			'body' => 'A body'
		]);

		$table = TableRegistry::get('authors');
		$table->hasOne('articles');
		$table->association('articles')
			->target()
			->validator()
			->add('title', 'num', ['rule' => 'numeric']);

		$this->assertSame($entity, $table->save($entity, ['atomic' => false]));
		$this->assertFalse($entity->isNew());
		$this->assertTrue($entity->article->isNew());
		$this->assertNull($entity->article->id);
		$this->assertNull($entity->article->get('author_id'));
		$this->assertTrue($entity->article->dirty('author_id'));
		$this->assertNotEmpty($entity->article->errors('title'));
	}

/**
 * Tests saving belongsTo association and get a validation error won't stop
 * saving if atomic is set to false
 *
 * @group save
 * @return void
 */
	public function testSaveBelongsToWithValidationErrorNotAtomic() {
		$entity = new \Cake\ORM\Entity([
			'title' => 'A Title',
			'body' => 'A body'
		]);
		$entity->author = new \Cake\ORM\Entity([
			'name' => 'Jose'
		]);

		$table = TableRegistry::get('articles');
		$table->belongsTo('authors');
		$table->association('authors')
			->target()
			->validator()
			->add('name', 'num', ['rule' => 'numeric']);

		$this->assertSame($entity, $table->save($entity, ['atomic' => false]));
		$this->assertFalse($entity->isNew());
		$this->assertTrue($entity->author->isNew());
		$this->assertNull($entity->get('author_id'));
		$this->assertNotEmpty($entity->author->errors('name'));
	}

/**
 * Tests saving belongsToMany records
 *
 * @group save
 * @return void
 */
	public function testSaveBelongsToMany() {
		$entity = new \Cake\ORM\Entity([
			'title' => 'A Title',
			'body' => 'A body'
		]);
		$entity->tags = [
			new \Cake\ORM\Entity([
				'name' => 'Something New'
			]),
			new \Cake\ORM\Entity([
				'name' => 'Another Something'
			])
		];
		$table = TableRegistry::get('articles');
		$table->belongsToMany('tags');
		$this->assertSame($entity, $table->save($entity));
		$this->assertFalse($entity->isNew());
		$this->assertFalse($entity->tags[0]->isNew());
		$this->assertFalse($entity->tags[1]->isNew());
		$this->assertEquals(4, $entity->tags[0]->id);
		$this->assertEquals(5, $entity->tags[1]->id);
		$this->assertEquals(4, $entity->tags[0]->_joinData->article_id);
		$this->assertEquals(4, $entity->tags[1]->_joinData->article_id);
		$this->assertEquals(4, $entity->tags[0]->_joinData->tag_id);
		$this->assertEquals(5, $entity->tags[1]->_joinData->tag_id);
	}

/**
 * Tests saving belongsToMany records with a validation error and atomic set
 * to true
 *
 * @group save
 * @return void
 */
	public function testSaveBelongsToWithValidationErrorAtomic() {
		$entity = new \Cake\ORM\Entity([
			'title' => 'A Title',
			'body' => 'A body'
		]);
		$entity->tags = [
			new \Cake\ORM\Entity([
				'name' => '100'
			]),
			new \Cake\ORM\Entity([
				'name' => 'Something New'
			])
		];
		$table = TableRegistry::get('articles');
		$table->belongsToMany('tags');
		$tags = $table->association('tags')
			->target()
			->validator()
			->add('name', 'num', ['rule' => 'numeric']);

		$this->assertFalse($table->save($entity));
		$this->assertTrue($entity->isNew());
		$this->assertNull($entity->tags[0]->isNew());
		$this->assertNull($entity->tags[1]->isNew());
		$this->assertNull($entity->tags[0]->id);
		$this->assertNull($entity->tags[1]->id);
		$this->assertNull($entity->tags[0]->_joinData);
		$this->assertNull($entity->tags[1]->_joinData);
		$this->assertEmpty($entity->tags[0]->errors('name'));
		$this->assertNotEmpty($entity->tags[1]->errors('name'));
	}

/**
 * Tests saving belongsToMany records with a validation error and atomic set
 * to false
 *
 * @group save
 * @return void
 */
	public function testSaveBelongsToWithValidationErrorNonAtomic() {
		$entity = new \Cake\ORM\Entity([
			'title' => 'A Title',
			'body' => 'A body'
		]);
		$entity->tags = [
			new \Cake\ORM\Entity([
				'name' => 'Something New'
			]),
			new \Cake\ORM\Entity([
				'name' => '100'
			])
		];
		$table = TableRegistry::get('articles');
		$table->belongsToMany('tags');
		$tags = $table->association('tags')
			->target()
			->validator()
			->add('name', 'num', ['rule' => 'numeric']);

		$this->assertSame($entity, $table->save($entity, ['atomic' => false]));
		$this->assertFalse($entity->isNew());
		$this->assertTrue($entity->tags[0]->isNew());
		$this->assertFalse($entity->tags[1]->isNew());
		$this->assertNull($entity->tags[0]->id);
		$this->assertEquals(4, $entity->tags[1]->id);
		$this->assertNull($entity->tags[0]->_joinData);
		$this->assertEquals(4, $entity->tags[1]->_joinData->article_id);
		$this->assertEquals(4, $entity->tags[1]->_joinData->tag_id);
	}

/**
 * Tests saving belongsToMany records with a validation error in a joint entity
 *
 * @group save
 * @return void
 */
	public function testSaveBelongsToWithValidationErrorInJointEntity() {
		$entity = new \Cake\ORM\Entity([
			'title' => 'A Title',
			'body' => 'A body'
		]);
		$entity->tags = [
			new \Cake\ORM\Entity([
				'name' => 'Something New'
			]),
			new \Cake\ORM\Entity([
				'name' => '100'
			])
		];
		$table = TableRegistry::get('articles');
		$table->belongsToMany('tags');
		$table->association('tags')
			->junction()
			->validator()
			->add('article_id', 'num', ['rule' => ['comparison', '>', 4]]);

		$this->assertFalse($table->save($entity));
		$this->assertTrue($entity->isNew());
		$this->assertNull($entity->tags[0]->isNew());
		$this->assertNull($entity->tags[1]->isNew());
		$this->assertNull($entity->tags[0]->id);
		$this->assertNull($entity->tags[1]->id);
		$this->assertNull($entity->tags[0]->_joinData);
		$this->assertNull($entity->tags[1]->_joinData);
	}

/**
 * Tests saving belongsToMany records with a validation error in a joint entity
 * and atomic set to false
 *
 * @group save
 * @return void
 */
	public function testSaveBelongsToWithValidationErrorInJointEntityNonAtomic() {
		$entity = new \Cake\ORM\Entity([
			'title' => 'A Title',
			'body' => 'A body'
		]);
		$entity->tags = [
			new \Cake\ORM\Entity([
				'name' => 'Something New'
			]),
			new \Cake\ORM\Entity([
				'name' => 'New one'
			])
		];
		$table = TableRegistry::get('articles');
		$table->belongsToMany('tags');
		$table->association('tags')
			->junction()
			->validator()
			->add('tag_id', 'num', ['rule' => ['comparison', '>', 4]]);

		$this->assertSame($entity, $table->save($entity, ['atomic' => false]));
		$this->assertFalse($entity->isNew());
		$this->assertFalse($entity->tags[0]->isNew());
		$this->assertFalse($entity->tags[1]->isNew());
		$this->assertEquals(4, $entity->tags[0]->id);
		$this->assertEquals(5, $entity->tags[1]->id);
		$this->assertTrue($entity->tags[0]->_joinData->isNew());
		$this->assertNotEmpty($entity->tags[0]->_joinData->errors());
		$this->assertEquals(4, $entity->tags[1]->_joinData->article_id);
		$this->assertEquals(5, $entity->tags[1]->_joinData->tag_id);
	}

/**
 * Tests that saving a persisted and clean entity will is a no-op
 *
 * @group save
 * @return void
 */
	public function testSaveCleanEntity() {
		$table = $this->getMock('\Cake\ORM\Table', ['_processSave']);
		$entity = new \Cake\ORM\Entity(
			['id' => 'foo'],
			['markNew' => false, 'markClean' => true]
		);
		$table->expects($this->never())->method('_processSave');
		$this->assertSame($entity, $table->save($entity));
	}

/**
 * Integration test to show how to append a new tag to an article
 *
 * @group save
 * @return void
 */
	public function testBelongsToManyIntegration() {
		$table = TableRegistry::get('articles');
		$table->belongsToMany('tags');
		$article = $table->find('all')->where(['id' => 1])->contain(['tags'])->first();
		$tags = $article->tags;
		$this->assertNotEmpty($tags);
		$tags[] = new \TestApp\Model\Entity\Tag(['name' => 'Something New']);
		$article->tags = $tags;
		$this->assertSame($article, $table->save($article));
		$tags = $article->tags;
		$this->assertCount(3, $tags);
		$this->assertFalse($tags[2]->isNew());
		$this->assertEquals(4, $tags[2]->id);
		$this->assertEquals(1, $tags[2]->_joinData->article_id);
		$this->assertEquals(4, $tags[2]->_joinData->tag_id);

		$article = $table->find('all')->where(['id' => 1])->contain(['tags'])->first();
		$this->assertEquals($tags, $article->tags);
	}

/**
 * Tests that it is possible to do a deep save and control what associations get saved,
 * while having control of the options passed to each level of the save
 *
 * @group save
 * @return void
 */
	public function testSaveDeepAssociationOptions() {
		$articles = $this->getMock(
			'\Cake\ORM\Table',
			['_insert'],
			[['table' => 'articles', 'connection' => $this->connection]]
		);
		$authors = $this->getMock(
			'\Cake\ORM\Table',
			['_insert', 'validate'],
			[['table' => 'authors', 'connection' => $this->connection]]
		);
		$supervisors = $this->getMock(
			'\Cake\ORM\Table',
			['_insert', 'validate'],
			[[
				'table' => 'authors',
				'alias' => 'supervisors',
				'connection' => $this->connection
			]]
		);
		$tags = $this->getMock(
			'\Cake\ORM\Table',
			['_insert'],
			[['table' => 'tags', 'connection' => $this->connection]]
		);

		$articles->belongsTo('authors', ['targetTable' => $authors]);
		$authors->hasOne('supervisors', ['targetTable' => $supervisors]);
		$supervisors->belongsToMany('tags', ['targetTable' => $tags]);

		$entity = new \Cake\ORM\Entity([
			'title' => 'bar',
			'author' => new \Cake\ORM\Entity([
				'name' => 'Juan',
				'supervisor' => new \Cake\ORM\Entity(['name' => 'Marc']),
				'tags' => [
					new \Cake\ORM\Entity(['name' => 'foo'])
				]
			]),
		]);
		$entity->isNew(true);
		$entity->author->isNew(true);
		$entity->author->supervisor->isNew(true);
		$entity->author->tags[0]->isNew(true);

		$articles->expects($this->once())
			->method('_insert')
			->with($entity, ['title' => 'bar'])
			->will($this->returnValue($entity));

		$authors->expects($this->once())
			->method('_insert')
			->with($entity->author, ['name' => 'Juan'])
			->will($this->returnValue($entity->author));

		$authors->expects($this->once())
			->method('validate')
			->with($entity->author)
			->will($this->returnValue(true));

		$supervisors->expects($this->once())
			->method('_insert')
			->with($entity->author->supervisor, ['name' => 'Marc'])
			->will($this->returnValue($entity->author->supervisor));

		$supervisors->expects($this->never())->method('validate');

		$tags->expects($this->never())->method('_insert');

		$this->assertSame($entity, $articles->save($entity, [
			'associated' => [
				'authors' => [
					'validate' => 'special',
					'associated' => [
						'supervisors' => [
							'atomic' => false,
							'validate' => false,
							'associated' => false
						]
					]
				]
			]
		]));
	}

/**
 * Integration test for linking entities with belongsToMany
 *
 * @return void
 */
	public function testLinkBelongsToMany() {
		$table = TableRegistry::get('articles');
		$table->belongsToMany('tags');
		$tagsTable = TableRegistry::get('tags');
		$source = ['source' => 'tags'];
		$options = ['markNew' => false];

		$article = new \Cake\ORM\Entity([
			'id' => 1,
		], $options);

		$newTag = new \TestApp\Model\Entity\Tag([
			'name' => 'Foo'
		], $source);
		$tags[] = new \TestApp\Model\Entity\Tag([
			'id' => 3
		], $options + $source);
		$tags[] = $newTag;

		$tagsTable->save($newTag);
		$table->association('tags')->link($article, $tags);

		$this->assertEquals($article->tags, $tags);
		foreach ($tags as $tag) {
			$this->assertFalse($tag->isNew());
		}

		$article = $table->find('all')->where(['id' => 1])->contain(['tags'])->first();
		$this->assertEquals($article->tags[2]->id, $tags[0]->id);
		$this->assertEquals($article->tags[3], $tags[1]);
	}

/**
 * Integration test to show how to unlink a single record from a belongsToMany
 *
 * @return void
 */
	public function testUnlinkBelongsToMany() {
		$table = TableRegistry::get('articles');
		$table->belongsToMany('tags');
		$tagsTable = TableRegistry::get('tags');
		$options = ['markNew' => false];

		$article = $table->find('all')
			->where(['id' => 1])
			->contain(['tags'])->first();

		$table->association('tags')->unlink($article, [$article->tags[0]]);
		$this->assertCount(1, $article->tags);
		$this->assertEquals(2, $article->tags[0]->get('id'));
		$this->assertFalse($article->dirty('tags'));
	}

/**
 * Integration test to show how to unlink multiple records from a belongsToMany
 *
 * @return void
 */
	public function testUnlinkBelongsToManyMultiple() {
		$table = TableRegistry::get('articles');
		$table->belongsToMany('tags');
		$tagsTable = TableRegistry::get('tags');
		$options = ['markNew' => false];

		$article = new \Cake\ORM\Entity(['id' => 1], $options);
		$tags[] = new \TestApp\Model\Entity\Tag(['id' => 1], $options);
		$tags[] = new \TestApp\Model\Entity\Tag(['id' => 2], $options);

		$table->association('tags')->unlink($article, $tags);
		$left = $table->find('all')->where(['id' => 1])->contain(['tags'])->first();
		$this->assertEmpty($left->tags);
	}

/**
 * Integration test to show how to unlink multiple records from a belongsToMany
 * providing some of the joint
 *
 * @return void
 */
	public function testUnlinkBelongsToManyPassingJoint() {
		$table = TableRegistry::get('articles');
		$table->belongsToMany('tags');
		$tagsTable = TableRegistry::get('tags');
		$options = ['markNew' => false];

		$article = new \Cake\ORM\Entity(['id' => 1], $options);
		$tags[] = new \TestApp\Model\Entity\Tag(['id' => 1], $options);
		$tags[] = new \TestApp\Model\Entity\Tag(['id' => 2], $options);

		$tags[1]->_joinData = new \Cake\ORM\Entity([
			'article_id' => 1,
			'tag_id' => 2
		]);

		$table->association('tags')->unlink($article, $tags);
		$left = $table->find('all')->where(['id' => 1])->contain(['tags'])->first();
		$this->assertEmpty($left->tags);
	}

/**
 * Integration test to show how to replace records from a belongsToMany
 *
 * @return void
 */
	public function testReplacelinksBelongsToMany() {
		$table = TableRegistry::get('articles');
		$table->belongsToMany('tags');
		$tagsTable = TableRegistry::get('tags');
		$options = ['markNew' => false];

		$article = new \Cake\ORM\Entity(['id' => 1], $options);
		$tags[] = new \TestApp\Model\Entity\Tag(['id' => 2], $options);
		$tags[] = new \TestApp\Model\Entity\Tag(['id' => 3], $options);
		$tags[] = new \TestApp\Model\Entity\Tag(['name' => 'foo']);

		$table->association('tags')->replaceLinks($article, $tags);
		$this->assertEquals(2, $article->tags[0]->id);
		$this->assertEquals(3, $article->tags[1]->id);
		$this->assertEquals(4, $article->tags[2]->id);

		$article = $table->find('all')->where(['id' => 1])->contain(['tags'])->first();
		$this->assertCount(3, $article->tags);
		$this->assertEquals(2, $article->tags[0]->id);
		$this->assertEquals(3, $article->tags[1]->id);
		$this->assertEquals(4, $article->tags[2]->id);
		$this->assertEquals('foo', $article->tags[2]->name);
	}

/**
 * Integration test to show how remove all links from a belongsToMany
 *
 * @return void
 */
	public function testReplacelinksBelongsToManyWithEmpty() {
		$table = TableRegistry::get('articles');
		$table->belongsToMany('tags');
		$tagsTable = TableRegistry::get('tags');
		$options = ['markNew' => false];

		$article = new \Cake\ORM\Entity(['id' => 1], $options);
		$tags = [];

		$table->association('tags')->replaceLinks($article, $tags);
		$this->assertSame($tags, $article->tags);
		$article = $table->find('all')->where(['id' => 1])->contain(['tags'])->first();
		$this->assertEmpty($article->tags);
	}

/**
 * Integration test to show how to replace records from a belongsToMany
 * passing the joint property along in the target entity
 *
 * @return void
 */
	public function testReplacelinksBelongsToManyWithJoint() {
		$table = TableRegistry::get('articles');
		$table->belongsToMany('tags');
		$tagsTable = TableRegistry::get('tags');
		$options = ['markNew' => false];

		$article = new \Cake\ORM\Entity(['id' => 1], $options);
		$tags[] = new \TestApp\Model\Entity\Tag([
			'id' => 2,
			'_joinData' => new \Cake\ORM\Entity([
				'article_id' => 1,
				'tag_id' => 2,
			])
		], $options);
		$tags[] = new \TestApp\Model\Entity\Tag(['id' => 3], $options);

		$table->association('tags')->replaceLinks($article, $tags);
		$this->assertSame($tags, $article->tags);
		$article = $table->find('all')->where(['id' => 1])->contain(['tags'])->first();
		$this->assertCount(2, $article->tags);
		$this->assertEquals(2, $article->tags[0]->id);
		$this->assertEquals(3, $article->tags[1]->id);
	}

/**
 * Tests that it is possible to call find with no arguments
 *
 * @return void
 */
	public function testSimplifiedFind() {
		$table = $this->getMock(
			'\Cake\ORM\Table',
			['callFinder'],
			[[
				'connection' => $this->connection,
				'schema' => ['id' => ['type' => 'integer']]
			]]
		);

		$query = (new \Cake\ORM\Query($this->connection, $table))->select();
		$table->expects($this->once())->method('callFinder')
			->with('all', $query, []);
		$table->find();
	}

/**
 * Test that get() will use the primary key for searching and return the first
 * entity found
 *
 * @return void
 */
	public function testGet() {
		$table = $this->getMock(
			'\Cake\ORM\Table',
			['callFinder', 'query'],
			[[
				'connection' => $this->connection,
				'schema' => [
					'id' => ['type' => 'integer'],
					'bar' => ['type' => 'integer'],
					'_constraints' => ['primary' => ['type' => 'primary', 'columns' => ['bar']]]
				]
			]]
		);

		$query = $this->getMock(
			'\Cake\ORM\Query',
			['addDefaultTypes', 'first', 'where'],
			[$this->connection, $table]
		);

		$entity = new \Cake\ORM\Entity;
		$table->expects($this->once())->method('query')
			->will($this->returnValue($query));
		$table->expects($this->once())->method('callFinder')
			->with('all', $query, ['fields' => ['id']])
			->will($this->returnValue($query));

		$query->expects($this->once())->method('where')
			->with([$table->alias() . '.bar' => 10])
			->will($this->returnSelf());
		$query->expects($this->once())->method('first')
			->will($this->returnValue($entity));
		$result = $table->get(10, ['fields' => ['id']]);
		$this->assertSame($entity, $result);
	}

/**
 * Tests that get() will throw an exception if the record was not found
 *
 * @expectedException \Cake\ORM\Error\RecordNotFoundException
 * @expectedExceptionMessage Record "10" not found in table "articles"
 * @return void
 */
	public function testGetException() {
		$table = $this->getMock(
			'\Cake\ORM\Table',
			['callFinder', 'query'],
			[[
				'connection' => $this->connection,
				'table' => 'articles',
				'schema' => [
					'id' => ['type' => 'integer'],
					'bar' => ['type' => 'integer'],
					'_constraints' => ['primary' => ['type' => 'primary', 'columns' => ['bar']]]
				]
			]]
		);

		$query = $this->getMock(
			'\Cake\ORM\Query',
			['addDefaultTypes', 'first', 'where'],
			[$this->connection, $table]
		);

		$table->expects($this->once())->method('query')
			->will($this->returnValue($query));
		$table->expects($this->once())->method('callFinder')
			->with('all', $query, ['contain' => ['foo']])
			->will($this->returnValue($query));

		$query->expects($this->once())->method('where')
			->with([$table->alias() . '.bar' => 10])
			->will($this->returnSelf());
		$query->expects($this->once())->method('first')
			->will($this->returnValue(false));
		$result = $table->get(10, ['contain' => ['foo']]);
	}

/**
 * Tests entityValidator
 *
 * @return void
 */
	public function testEntityValidator() {
		$table = new Table;
		$expected = new \Cake\ORM\EntityValidator($table);
		$table->entityValidator();
		$this->assertEquals($expected, $table->entityValidator());
	}

/**
 * Tests that validate will call the entity validator with the correct
 * options
 *
 * @return void
 */
	public function testValidateDefaultAssociations() {
		$table = $this->getMock('\Cake\ORM\Table', ['entityValidator']);
		$table->belongsTo('users');
		$table->hasMany('articles');
		$table->schema([]);

		$entityValidator = $this->getMock('\Cake\ORM\EntityValidator', [], [$table]);
		$entity = $table->newEntity([]);

		$table->expects($this->once())->method('entityValidator')
			->will($this->returnValue($entityValidator));
		$entityValidator->expects($this->once())->method('one')
			->with($entity, ['associated' => ['users', 'articles']])
			->will($this->returnValue(true));
		$this->assertTrue($table->validate($entity));
	}

/**
 * Tests that validate will call the entity validator with the correct
 * options
 *
 * @return void
 */
	public function testValidateWithCustomOptions() {
		$table = $this->getMock('\Cake\ORM\Table', ['entityValidator']);
		$table->schema([]);

		$entityValidator = $this->getMock('\Cake\ORM\EntityValidator', [], [$table]);
		$entity = $table->newEntity([]);
		$options = ['associated' => ['users'], 'validate' => 'foo'];

		$table->expects($this->once())->method('entityValidator')
			->will($this->returnValue($entityValidator));
		$entityValidator->expects($this->once())->method('one')
			->with($entity, $options)
			->will($this->returnValue(false));
		$this->assertFalse($table->validate($entity, $options));
	}

/**
 * Tests that validateMany will call the entity validator with the correct
 * options
 *
 * @return void
 */
	public function testValidateManyDefaultAssociaion() {
		$table = $this->getMock('\Cake\ORM\Table', ['entityValidator']);
		$table->belongsTo('users');
		$table->hasMany('articles');
		$table->schema([]);

		$entityValidator = $this->getMock('\Cake\ORM\EntityValidator', [], [$table]);
		$entities = ['a', 'b'];

		$table->expects($this->once())->method('entityValidator')
			->will($this->returnValue($entityValidator));
		$entityValidator->expects($this->once())->method('many')
			->with($entities, ['associated' => ['users', 'articles']])
			->will($this->returnValue(true));
		$this->assertTrue($table->validateMany($entities));
	}

/**
 * Tests that validateMany will call the entity validator with the correct
 * options
 *
 * @return void
 */
	public function testValidateManyWithCustomOptions() {
		$table = $this->getMock('\Cake\ORM\Table', ['entityValidator']);
		$table->schema([]);

		$entityValidator = $this->getMock('\Cake\ORM\EntityValidator', [], [$table]);
		$entities = ['a', 'b', 'c'];
		$options = ['associated' => ['users'], 'validate' => 'foo'];

		$table->expects($this->once())->method('entityValidator')
			->will($this->returnValue($entityValidator));
		$entityValidator->expects($this->once())->method('many')
			->with($entities, $options)
			->will($this->returnValue(false));
		$this->assertFalse($table->validateMany($entities, $options));
	}

/**
 * Tests that patchEntity delegates the task to the marshaller and passed
 * all associations
 *
 * @return void
 */
	public function testPatchEntity() {
		$table = $this->getMock('Cake\ORM\Table', ['marshaller']);
		$marshaller = $this->getMock('Cake\ORM\Marshaller', [], [$table]);
		$table->belongsTo('users');
		$table->hasMany('articles');
		$table->expects($this->once())->method('marshaller')
			->will($this->returnValue($marshaller));

		$entity = new \Cake\ORM\Entity;
		$data = ['foo' => 'bar'];
		$marshaller->expects($this->once())
			->method('merge')
			->with($entity, $data, ['users', 'articles'])
			->will($this->returnValue($entity));
		$table->patchEntity($entity, $data);
	}

/**
 * Tests that patchEntities delegates the task to the marshaller and passed
 * all associations
 *
 * @return void
 */
	public function testPatchEntities() {
		$table = $this->getMock('Cake\ORM\Table', ['marshaller']);
		$marshaller = $this->getMock('Cake\ORM\Marshaller', [], [$table]);
		$table->belongsTo('users');
		$table->hasMany('articles');
		$table->expects($this->once())->method('marshaller')
			->will($this->returnValue($marshaller));

		$entities = [new \Cake\ORM\Entity];
		$data = [['foo' => 'bar']];
		$marshaller->expects($this->once())
			->method('mergeMany')
			->with($entities, $data, ['users', 'articles'])
			->will($this->returnValue($entities));
		$table->patchEntities($entities, $data);
	}

/**
 * Tests __debugInfo
 *
 * @return void
 */
	public function testDebugInfo() {
		$articles = TableRegistry::get('articles');
		$articles->addBehavior('Timestamp');
		$result = $articles->__debugInfo();
		$expected = [
			'table' => 'articles',
			'alias' => 'articles',
			'entityClass' => 'TestApp\Model\Entity\Article',
			'associations' => ['authors', 'tags', 'articlestags'],
			'behaviors' => ['Timestamp'],
			'defaultConnection' => 'default',
			'connectionName' => 'test'
		];
		$this->assertEquals($expected, $result);
	}

}
