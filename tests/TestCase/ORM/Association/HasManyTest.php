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
namespace Cake\Test\TestCase\ORM\Association;

use Cake\Database\Expression\IdentifierExpression;
use Cake\Database\Expression\QueryExpression;
use Cake\Database\Expression\TupleComparison;
use Cake\Database\TypeMap;
use Cake\ORM\Association\HasMany;
use Cake\ORM\Entity;
use Cake\ORM\Query;
use Cake\ORM\Table;
use Cake\ORM\TableRegistry;

/**
 * Tests HasMany class
 *
 */
class HasManyTest extends \Cake\TestSuite\TestCase {

/**
 * Set up
 *
 * @return void
 */
	public function setUp() {
		parent::setUp();
		$this->author = TableRegistry::get('Authors', [
			'schema' => [
				'id' => ['type' => 'integer'],
				'username' => ['type' => 'string'],
				'_constraints' => [
					'primary' => ['type' => 'primary', 'columns' => ['id']]
				]
			]
		]);
		$this->article = $this->getMock(
			'Cake\ORM\Table',
			['find', 'deleteAll', 'delete'],
			[['alias' => 'Articles', 'table' => 'articles']]
		);
		$this->article->schema([
			'id' => ['type' => 'integer'],
			'title' => ['type' => 'string'],
			'author_id' => ['type' => 'integer'],
			'_constraints' => [
				'primary' => ['type' => 'primary', 'columns' => ['id']]
			]
		]);
		$this->articlesTypeMap = new TypeMap([
			'Articles.id' => 'integer',
			'id' => 'integer',
			'Articles.title' => 'string',
			'title' => 'string',
			'Articles.author_id' => 'integer',
			'author_id' => 'integer',
		]);
	}

/**
 * Tear down
 *
 * @return void
 */
	public function tearDown() {
		parent::tearDown();
		TableRegistry::clear();
	}

/**
 * Tests that the association reports it can be joined
 *
 * @return void
 */
	public function testCanBeJoined() {
		$assoc = new HasMany('Test');
		$this->assertFalse($assoc->canBeJoined());
	}

/**
 * Tests sort() method
 *
 * @return void
 */
	public function testSort() {
		$assoc = new HasMany('Test');
		$this->assertNull($assoc->sort());
		$assoc->sort(['id' => 'ASC']);
		$this->assertEquals(['id' => 'ASC'], $assoc->sort());
	}

/**
 * Tests requiresKeys() method
 *
 * @return void
 */
	public function testRequiresKeys() {
		$assoc = new HasMany('Test');
		$this->assertTrue($assoc->requiresKeys());
		$assoc->strategy(HasMany::STRATEGY_SUBQUERY);
		$this->assertFalse($assoc->requiresKeys());
		$assoc->strategy(HasMany::STRATEGY_SELECT);
		$this->assertTrue($assoc->requiresKeys());
	}

/**
 * Test the eager loader method with no extra options
 *
 * @return void
 */
	public function testEagerLoader() {
		$config = [
			'sourceTable' => $this->author,
			'targetTable' => $this->article,
			'strategy' => 'select'
		];
		$association = new HasMany('Articles', $config);
		$keys = [1, 2, 3, 4];
		$query = $this->getMock('Cake\ORM\Query', ['all'], [null, null]);
		$this->article->expects($this->once())->method('find')->with('all')
			->will($this->returnValue($query));
		$results = [
			['id' => 1, 'title' => 'article 1', 'author_id' => 2],
			['id' => 2, 'title' => 'article 2', 'author_id' => 1]
		];
		$query->expects($this->once())->method('all')
			->will($this->returnValue($results));

		$callable = $association->eagerLoader(compact('keys', 'query'));
		$row = ['Authors__id' => 1, 'username' => 'author 1'];
		$result = $callable($row);
		$row['Articles'] = [
			['id' => 2, 'title' => 'article 2', 'author_id' => 1]
			];
		$this->assertEquals($row, $result);

		$row = ['Authors__id' => 2, 'username' => 'author 2'];
		$result = $callable($row);
		$row['Articles'] = [
			['id' => 1, 'title' => 'article 1', 'author_id' => 2]
			];
		$this->assertEquals($row, $result);
	}

/**
 * Test the eager loader method with default query clauses
 *
 * @return void
 */
	public function testEagerLoaderWithDefaults() {
		$config = [
			'sourceTable' => $this->author,
			'targetTable' => $this->article,
			'conditions' => ['Articles.is_active' => true],
			'sort' => ['id' => 'ASC'],
			'strategy' => 'select'
		];
		$association = new HasMany('Articles', $config);
		$keys = [1, 2, 3, 4];
		$query = $this->getMock(
			'Cake\ORM\Query',
			['all', 'where', 'andWhere', 'order'],
			[null, null]
		);
		$this->article->expects($this->once())->method('find')->with('all')
			->will($this->returnValue($query));
		$results = [
			['id' => 1, 'title' => 'article 1', 'author_id' => 2],
			['id' => 2, 'title' => 'article 2', 'author_id' => 1]
		];

		$query->expects($this->once())->method('all')
			->will($this->returnValue($results));

		$query->expects($this->at(0))->method('where')
			->with(['Articles.is_active' => true])
			->will($this->returnSelf());

		$query->expects($this->at(1))->method('where')
			->with([])
			->will($this->returnSelf());

		$query->expects($this->once())->method('andWhere')
			->with(['Articles.author_id IN' => $keys])
			->will($this->returnSelf());

		$query->expects($this->once())->method('order')
			->with(['id' => 'ASC'])
			->will($this->returnSelf());

		$association->eagerLoader(compact('keys', 'query'));
	}

/**
 * Test the eager loader method with overridden query clauses
 *
 * @return void
 */
	public function testEagerLoaderWithOverrides() {
		$config = [
			'sourceTable' => $this->author,
			'targetTable' => $this->article,
			'conditions' => ['Articles.is_active' => true],
			'sort' => ['id' => 'ASC'],
			'strategy' => 'select'
		];
		$association = new HasMany('Articles', $config);
		$keys = [1, 2, 3, 4];
		$query = $this->getMock(
			'Cake\ORM\Query',
			['all', 'where', 'andWhere', 'order', 'select', 'contain'],
			[null, null]
		);
		$this->article->expects($this->once())->method('find')->with('all')
			->will($this->returnValue($query));
		$results = [
			['id' => 1, 'title' => 'article 1', 'author_id' => 2],
			['id' => 2, 'title' => 'article 2', 'author_id' => 1]
		];

		$query->expects($this->once())->method('all')
			->will($this->returnValue($results));

		$query->expects($this->at(0))->method('where')
			->with(['Articles.is_active' => true])
			->will($this->returnSelf());

		$query->expects($this->at(1))->method('where')
			->with(['Articles.id !=' => 3])
			->will($this->returnSelf());

		$query->expects($this->once())->method('andWhere')
			->with(['Articles.author_id IN' => $keys])
			->will($this->returnSelf());

		$query->expects($this->once())->method('order')
			->with(['title' => 'DESC'])
			->will($this->returnSelf());

		$query->expects($this->once())->method('select')
			->with([
				'Articles__title' => 'Articles.title',
				'Articles__author_id' => 'Articles.author_id'
			])
			->will($this->returnSelf());

		$query->expects($this->once())->method('contain')
			->with([
				'Categories' => ['fields' => ['a', 'b']],
			])
			->will($this->returnSelf());

		$association->eagerLoader([
			'conditions' => ['Articles.id !=' => 3],
			'sort' => ['title' => 'DESC'],
			'fields' => ['title', 'author_id'],
			'contain' => ['Categories' => ['fields' => ['a', 'b']]],
			'keys' => $keys,
			'query' => $query
		]);
	}

/**
 * Test that failing to add the foreignKey to the list of fields will throw an
 * exception
 *
 * @expectedException \InvalidArgumentException
 * @expectedExceptionMessage You are required to select the "Articles.author_id"
 * @return void
 */
	public function testEagerLoaderFieldsException() {
		$config = [
			'sourceTable' => $this->author,
			'targetTable' => $this->article,
			'strategy' => 'select'
		];
		$association = new HasMany('Articles', $config);
		$keys = [1, 2, 3, 4];
		$query = $this->getMock(
			'Cake\ORM\Query',
			['all'],
			[null, null]
		);
		$this->article->expects($this->once())->method('find')->with('all')
			->will($this->returnValue($query));

		$association->eagerLoader([
			'fields' => ['id', 'title'],
			'keys' => $keys,
			'query' => $query
		]);
	}

/**
 * Tests eager loading using subquery
 *
 * @return void
 */
	public function testEagerLoaderSubquery() {
		$config = [
			'sourceTable' => $this->author,
			'targetTable' => $this->article,
		];
		$association = new HasMany('Articles', $config);
		$parent = (new Query(null, $this->author))
			->join(['foo' => ['table' => 'foo', 'type' => 'inner', 'conditions' => []]])
			->join(['bar' => ['table' => 'bar', 'type' => 'left', 'conditions' => []]]);

		$query = $this->getMock(
			'Cake\ORM\Query',
			['all', 'where', 'andWhere', 'order', 'select', 'contain'],
			[null, null]
		);

		$this->article->expects($this->once())->method('find')->with('all')
			->will($this->returnValue($query));
		$results = [
			['id' => 1, 'title' => 'article 1', 'author_id' => 2],
			['id' => 2, 'title' => 'article 2', 'author_id' => 1]
		];
		$query->expects($this->once())->method('all')
			->will($this->returnValue($results));

		$query->expects($this->at(0))->method('where')
			->with([])
			->will($this->returnSelf());
		$query->expects($this->at(1))->method('where')
			->with([])
			->will($this->returnSelf());

		$expected = clone $parent;
		$joins = $expected->join();
		unset($joins['bar']);
		$expected
			->contain([], true)
			->select(['Authors__id' => 'Authors.id'], true)
			->join($joins, [], true);
		$query->expects($this->once())->method('andWhere')
			->with(['Articles.author_id IN' => $expected])
			->will($this->returnSelf());

		$callable = $association->eagerLoader([
			'query' => $parent, 'strategy' => HasMany::STRATEGY_SUBQUERY, 'keys' => []
		]);
		$row = ['Authors__id' => 1, 'username' => 'author 1'];
		$result = $callable($row);
		$row['Articles'] = [
			['id' => 2, 'title' => 'article 2', 'author_id' => 1]
		];
		$this->assertEquals($row, $result);

		$row = ['Authors__id' => 2, 'username' => 'author 2'];
		$result = $callable($row);
		$row['Articles'] = [
			['id' => 1, 'title' => 'article 1', 'author_id' => 2]
		];
		$this->assertEquals($row, $result);
	}

/**
 * Tests that eager loader accepts a queryBuilder option
 *
 * @return void
 */
	public function testEagerLoaderWithQueryBuilder() {
		$config = [
			'sourceTable' => $this->author,
			'targetTable' => $this->article,
			'strategy' => 'select'
		];
		$association = new HasMany('Articles', $config);
		$keys = [1, 2, 3, 4];
		$query = $this->getMock(
			'Cake\ORM\Query',
			['all', 'select', 'join', 'where'],
			[null, null]
		);
		$this->article->expects($this->once())->method('find')->with('all')
			->will($this->returnValue($query));
		$results = [
			['id' => 1, 'title' => 'article 1', 'author_id' => 2],
			['id' => 2, 'title' => 'article 2', 'author_id' => 1]
		];

		$query->expects($this->once())->method('all')
			->will($this->returnValue($results));

		$query->expects($this->any())->method('select')
			->will($this->returnSelf());
		$query->expects($this->at(2))->method('select')
			->with(['a', 'b'])
			->will($this->returnSelf());

		$query->expects($this->at(3))->method('join')
			->with('foo')
			->will($this->returnSelf());

		$query->expects($this->any())->method('where')
			->will($this->returnSelf());
		$query->expects($this->at(4))->method('where')
			->with(['a' => 1])
			->will($this->returnSelf());

		$queryBuilder = function($query) {
			return $query->select(['a', 'b'])->join('foo')->where(['a' => 1]);
		};

		$association->eagerLoader(compact('keys', 'query', 'queryBuilder'));
	}

/**
 * Test the eager loader method with no extra options
 *
 * @return void
 */
	public function testEagerLoaderMultipleKeys() {
		$config = [
			'sourceTable' => $this->author,
			'targetTable' => $this->article,
			'strategy' => 'select',
			'foreignKey' => ['author_id', 'site_id']
		];

		$this->author->primaryKey(['id', 'site_id']);
		$association = new HasMany('Articles', $config);
		$keys = [[1, 10], [2, 20], [3, 30], [4, 40]];
		$query = $this->getMock('Cake\ORM\Query', ['all', 'andWhere'], [null, null]);
		$this->article->expects($this->once())->method('find')->with('all')
			->will($this->returnValue($query));
		$results = [
			['id' => 1, 'title' => 'article 1', 'author_id' => 2, 'site_id' => 10],
			['id' => 2, 'title' => 'article 2', 'author_id' => 1, 'site_id' => 20]
		];
		$query->expects($this->once())->method('all')
			->will($this->returnValue($results));

		$tuple = new TupleComparison(
			['Articles.author_id', 'Articles.site_id'], $keys, [], 'IN'
		);
		$query->expects($this->once())->method('andWhere')
			->with($tuple)
			->will($this->returnSelf());

		$callable = $association->eagerLoader(compact('keys', 'query'));
		$row = ['Authors__id' => 2, 'Authors__site_id' => 10, 'username' => 'author 1'];
		$result = $callable($row);
		$row['Articles'] = [
			['id' => 1, 'title' => 'article 1', 'author_id' => 2, 'site_id' => 10]
		];
		$this->assertEquals($row, $result);

		$row = ['Authors__id' => 1, 'username' => 'author 2', 'Authors__site_id' => 20];
		$result = $callable($row);
		$row['Articles'] = [
			['id' => 2, 'title' => 'article 2', 'author_id' => 1, 'site_id' => 20]
		];
		$this->assertEquals($row, $result);
	}

/**
 * Tests that the correct join and fields are attached to a query depending on
 * the association config
 *
 * @return void
 */
	public function testAttachTo() {
		$query = $this->getMock('\Cake\ORM\Query', ['join', 'select'], [null, null]);
		$config = [
			'sourceTable' => $this->author,
			'targetTable' => $this->article,
			'conditions' => ['Articles.is_active' => true]
		];

		$field = new IdentifierExpression('Articles.author_id');
		$association = new HasMany('Articles', $config);
		$query->expects($this->once())->method('join')->with([
			'Articles' => [
				'conditions' => new QueryExpression([
					'Articles.is_active' => true,
					['Authors.id' => $field]
				], $this->articlesTypeMap),
				'type' => 'INNER',
				'table' => 'articles'
			]
		]);
		$query->expects($this->once())->method('select')->with([
			'Articles__id' => 'Articles.id',
			'Articles__title' => 'Articles.title',
			'Articles__author_id' => 'Articles.author_id'
		]);
		$association->attachTo($query);
	}

/**
 * Tests that default config defined in the association can be overridden
 *
 * @return void
 */
	public function testAttachToConfigOverride() {
		$query = $this->getMock('\Cake\ORM\Query', ['join', 'select'], [null, null]);
		$config = [
			'sourceTable' => $this->author,
			'targetTable' => $this->article,
			'conditions' => ['Articles.is_active' => true]
		];
		$association = new HasMany('Articles', $config);
		$query->expects($this->once())->method('join')->with([
			'Articles' => [
				'conditions' => new QueryExpression([
					'Articles.is_active' => false
				], $this->articlesTypeMap),
				'type' => 'INNER',
				'table' => 'articles'
			]
		]);
		$query->expects($this->once())->method('select')->with([
			'Articles__title' => 'Articles.title'
		]);

		$override = [
			'conditions' => ['Articles.is_active' => false],
			'foreignKey' => false,
			'fields' => ['title']
		];
		$association->attachTo($query, $override);
	}

/**
 * Tests that it is possible to avoid fields inclusion for the associated table
 *
 * @return void
 */
	public function testAttachToNoFields() {
		$query = $this->getMock('\Cake\ORM\Query', ['join', 'select'], [null, null]);
		$config = [
			'sourceTable' => $this->author,
			'targetTable' => $this->article,
			'conditions' => ['Articles.is_active' => true]
		];
		$field = new IdentifierExpression('Articles.author_id');
		$association = new HasMany('Articles', $config);
		$query->expects($this->once())->method('join')->with([
			'Articles' => [
				'conditions' => new QueryExpression([
					'Articles.is_active' => true,
					['Authors.id' => $field]
				], $this->articlesTypeMap),
				'type' => 'INNER',
				'table' => 'articles'
			]
		]);
		$query->expects($this->never())->method('select');
		$association->attachTo($query, ['includeFields' => false]);
	}

/**
 * Tests that using hasMany with a table having a multi column primary
 * key will work if the foreign key is passed
 *
 * @return void
 */
	public function testAttachToMultiPrimaryKey() {
		$query = $this->getMock('\Cake\ORM\Query', ['join', 'select'], [null, null]);
		$this->author->primaryKey(['id', 'site_id']);
		$config = [
			'sourceTable' => $this->author,
			'targetTable' => $this->article,
			'conditions' => ['Articles.is_active' => true],
			'foreignKey' => ['author_id', 'author_site_id']
		];
		$field1 = new IdentifierExpression('Articles.author_id');
		$field2 = new IdentifierExpression('Articles.author_site_id');
		$association = new HasMany('Articles', $config);
		$query->expects($this->once())->method('join')->with([
			'Articles' => [
				'conditions' => new QueryExpression([
					'Articles.is_active' => true,
					['Authors.id' => $field1, 'Authors.site_id' => $field2]
				], $this->articlesTypeMap),
				'type' => 'INNER',
				'table' => 'articles'
			]
		]);
		$query->expects($this->never())->method('select');
		$association->attachTo($query, ['includeFields' => false]);
	}

/**
 * Tests that using hasMany with a table having a multi column primary
 * key will work if the foreign key is passed
 *
 * @expectedException \RuntimeException
 * @expectedExceptionMessage Cannot match provided foreignKey for "Articles", got "(author_id)" but expected foreign key for "(id, site_id)
 * @return void
 */
	public function testAttachToMultiPrimaryKeyMistmatch() {
		$query = $this->getMock('\Cake\ORM\Query', ['join', 'select'], [null, null]);
		$this->author->primaryKey(['id', 'site_id']);
		$config = [
			'sourceTable' => $this->author,
			'targetTable' => $this->article,
			'conditions' => ['Articles.is_active' => true],
			'foreignKey' => 'author_id'
		];
		$field1 = new IdentifierExpression('Articles.author_id');
		$field2 = new IdentifierExpression('Articles.author_site_id');
		$association = new HasMany('Articles', $config);
		$association->attachTo($query, ['includeFields' => false]);
	}

/**
 * Tests that by supplying a query builder function, it is possible to add fields
 * and conditions to an association
 *
 * @return void
 */
	public function testAttachToWithQueryBuilder() {
		$query = $this->getMock('\Cake\ORM\Query', ['join', 'select'], [null, null]);
		$config = [
			'sourceTable' => $this->author,
			'targetTable' => $this->article,
			'conditions' => ['Articles.is_active' => true]
		];
		$field = new IdentifierExpression('Articles.author_id');
		$association = new HasMany('Articles', $config);
		$query->expects($this->once())->method('join')->with([
			'Articles' => [
				'conditions' => new QueryExpression([
					'a' => 1,
					'Articles.is_active' => true,
					['Authors.id' => $field],
				], $this->articlesTypeMap),
				'type' => 'INNER',
				'table' => 'articles'
			]
		]);
		$query->expects($this->once())->method('select')
			->with([
				'Articles__a' => 'Articles.a',
				'Articles__b' => 'Articles.b'
			]);
		$builder = function($q) {
			return $q->select(['a', 'b'])->where(['a' => 1]);
		};
		$association->attachTo($query, ['queryBuilder' => $builder]);
	}

/**
 * Test cascading deletes.
 *
 * @return void
 */
	public function testCascadeDelete() {
		$config = [
			'dependent' => true,
			'sourceTable' => $this->author,
			'targetTable' => $this->article,
			'conditions' => ['Articles.is_active' => true],
		];
		$association = new HasMany('Articles', $config);

		$this->article->expects($this->once())
			->method('deleteAll')
			->with([
				'Articles.is_active' => true,
				'author_id' => 1
			]);

		$entity = new Entity(['id' => 1, 'name' => 'PHP']);
		$association->cascadeDelete($entity);
	}

/**
 * Test cascading delete with has many.
 *
 * @return void
 */
	public function testCascadeDeleteCallbacks() {
		$config = [
			'dependent' => true,
			'sourceTable' => $this->author,
			'targetTable' => $this->article,
			'conditions' => ['Articles.is_active' => true],
			'cascadeCallbacks' => true,
		];
		$association = new HasMany('Articles', $config);

		$articleOne = new Entity(['id' => 2, 'title' => 'test']);
		$articleTwo = new Entity(['id' => 3, 'title' => 'testing']);
		$iterator = new \ArrayIterator([
			$articleOne,
			$articleTwo
		]);

		$query = $this->getMock('\Cake\ORM\Query', [], [], '', false);
		$query->expects($this->at(0))
			->method('where')
			->with(['Articles.is_active' => true])
			->will($this->returnSelf());
		$query->expects($this->at(1))
			->method('where')
			->with(['author_id' => 1])
			->will($this->returnSelf());
		$query->expects($this->any())
			->method('getIterator')
			->will($this->returnValue($iterator));
		$query->expects($this->once())
			->method('bufferResults')
			->with(false)
			->will($this->returnSelf());

		$this->article->expects($this->once())
			->method('find')
			->will($this->returnValue($query));

		$this->article->expects($this->at(1))
			->method('delete')
			->with($articleOne, []);
		$this->article->expects($this->at(2))
			->method('delete')
			->with($articleTwo, []);

		$entity = new Entity(['id' => 1, 'name' => 'mark']);
		$this->assertTrue($association->cascadeDelete($entity));
	}

/**
 * Test that save() ignores non entity values.
 *
 * @return void
 */
	public function testSaveOnlyEntities() {
		$mock = $this->getMock('Cake\ORM\Table', [], [], '', false);
		$config = [
			'sourceTable' => $this->author,
			'targetTable' => $mock,
		];

		$entity = new Entity([
			'username' => 'Mark',
			'email' => 'mark@example.com',
			'articles' => [
				['title' => 'First Post'],
				new Entity(['title' => 'Second Post']),
			]
		]);

		$mock->expects($this->never())
			->method('save');

		$association = new HasMany('Articles', $config);
		$association->save($entity);
	}

/**
 * Tests that property is being set using the constructor options.
 *
 * @return void
 */
	public function testPropertyOption() {
		$config = ['propertyName' => 'thing_placeholder'];
		$association = new hasMany('Thing', $config);
		$this->assertEquals('thing_placeholder', $association->property());
	}

/**
 * Test that plugin names are omitted from property()
 *
 * @return void
 */
	public function testPropertyNoPlugin() {
		$mock = $this->getMock('Cake\ORM\Table', [], [], '', false);
		$config = [
			'sourceTable' => $this->author,
			'targetTable' => $mock,
		];
		$association = new HasMany('Contacts.Addresses', $config);
		$this->assertEquals('addresses', $association->property());
	}

/**
 * Tests that attaching an association to a query will trigger beforeFind
 * for the target table
 *
 * @return void
 */
	public function testAttachToBeforeFind() {
		$query = $this->getMock('\Cake\ORM\Query', ['join', 'select'], [null, null]);
		$config = [
			'sourceTable' => $this->author,
			'targetTable' => $this->article,
		];

		$listener = $this->getMock('stdClass', ['__invoke']);
		$association = new HasMany('Articles', $config);
		$this->article->getEventManager()->attach($listener, 'Model.beforeFind');
		$listener->expects($this->once())->method('__invoke')
			->with(
				$this->isInstanceOf('\Cake\Event\Event'),
				$this->isInstanceOf('\Cake\ORM\Query'),
				[],
				false
			);
		$association->attachTo($query);
	}

/**
 * Tests that attaching an association to a query will trigger beforeFind
 * for the target table
 *
 * @return void
 */
	public function testAttachToBeforeFindExtraOptions() {
		$query = $this->getMock('\Cake\ORM\Query', ['join', 'select'], [null, null]);
		$config = [
			'sourceTable' => $this->author,
			'targetTable' => $this->article,
		];

		$listener = $this->getMock('stdClass', ['__invoke']);
		$association = new HasMany('Articles', $config);
		$this->article->getEventManager()->attach($listener, 'Model.beforeFind');
		$opts = ['something' => 'more'];
		$listener->expects($this->once())->method('__invoke')
			->with(
				$this->isInstanceOf('\Cake\Event\Event'),
				$this->isInstanceOf('\Cake\ORM\Query'),
				$opts,
				false
			);
		$association->attachTo($query, ['queryBuilder' => function($q) {
			return $q->applyOptions(['something' => 'more']);
		}]);
	}

}
