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
namespace Cake\Test\TestCase\ORM\Association;

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
		$this->author = TableRegistry::get('Author', [
			'schema' => [
				'id' => ['type' => 'integer'],
				'username' => ['type' => 'string'],
			]
		]);
		$this->article = $this->getMock(
			'Cake\ORM\Table', ['find', 'deleteAll', 'delete'], [['alias' => 'Article', 'table' => 'articles']]
		);
		$this->article->schema([
			'id' => ['type' => 'integer'],
			'title' => ['type' => 'string'],
			'author_id' => ['type' => 'integer'],
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
		$association = new HasMany('Article', $config);
		$keys = [1, 2, 3, 4];
		$query = $this->getMock('Cake\ORM\Query', ['execute'], [null, null]);
		$this->article->expects($this->once())->method('find')->with('all')
			->will($this->returnValue($query));
		$results = [
			['id' => 1, 'title' => 'article 1', 'author_id' => 2],
			['id' => 2, 'title' => 'article 2', 'author_id' => 1]
		];
		$query->expects($this->once())->method('execute')
			->will($this->returnValue($results));

		$callable = $association->eagerLoader(compact('keys', 'query'));
		$row = ['Author__id' => 1, 'username' => 'author 1'];
		$result = $callable($row);
		$row['Article__Article'] = [
			['id' => 2, 'title' => 'article 2', 'author_id' => 1]
			];
		$this->assertEquals($row, $result);

		$row = ['Author__id' => 2, 'username' => 'author 2'];
		$result = $callable($row);
		$row['Article__Article'] = [
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
			'conditions' => ['Article.is_active' => true],
			'sort' => ['id' => 'ASC'],
			'strategy' => 'select'
		];
		$association = new HasMany('Article', $config);
		$keys = [1, 2, 3, 4];
		$query = $this->getMock(
			'Cake\ORM\Query',
			['execute', 'where', 'andWhere', 'order'],
			[null, null]
		);
		$this->article->expects($this->once())->method('find')->with('all')
			->will($this->returnValue($query));
		$results = [
			['id' => 1, 'title' => 'article 1', 'author_id' => 2],
			['id' => 2, 'title' => 'article 2', 'author_id' => 1]
		];

		$query->expects($this->once())->method('execute')
			->will($this->returnValue($results));

		$query->expects($this->once())->method('where')
			->with(['Article.is_active' => true])
			->will($this->returnValue($query));

		$query->expects($this->once())->method('andWhere')
			->with(['Article.author_id IN' => $keys])
			->will($this->returnValue($query));

		$query->expects($this->once())->method('order')
			->with(['id' => 'ASC'])
			->will($this->returnValue($query));

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
			'conditions' => ['Article.is_active' => true],
			'sort' => ['id' => 'ASC'],
			'strategy' => 'select'
		];
		$association = new HasMany('Article', $config);
		$keys = [1, 2, 3, 4];
		$query = $this->getMock(
			'Cake\ORM\Query',
			['execute', 'where', 'andWhere', 'order', 'select', 'contain'],
			[null, null]
		);
		$this->article->expects($this->once())->method('find')->with('all')
			->will($this->returnValue($query));
		$results = [
			['id' => 1, 'title' => 'article 1', 'author_id' => 2],
			['id' => 2, 'title' => 'article 2', 'author_id' => 1]
		];

		$query->expects($this->once())->method('execute')
			->will($this->returnValue($results));

		$query->expects($this->once())->method('where')
			->with(['Article.is_active' => true, 'Article.id !=' => 3])
			->will($this->returnValue($query));

		$query->expects($this->once())->method('andWhere')
			->with(['Article.author_id IN' => $keys])
			->will($this->returnValue($query));

		$query->expects($this->once())->method('order')
			->with(['title' => 'DESC'])
			->will($this->returnValue($query));

		$query->expects($this->once())->method('select')
			->with([
				'Article__title' => 'Article.title',
				'Article__author_id' => 'Article.author_id'
			])
			->will($this->returnValue($query));

		$query->expects($this->once())->method('contain')
			->with([
				'Category' => ['fields' => ['a', 'b']],
			])
			->will($this->returnValue($query));

		$association->eagerLoader([
			'conditions' => ['Article.id !=' => 3],
			'sort' => ['title' => 'DESC'],
			'fields' => ['title', 'author_id'],
			'contain' => ['Category' => ['fields' => ['a', 'b']]],
			'keys' => $keys,
			'query' => $query
		]);
	}

/**
 * Test that failing to add the foreignKey to the list of fields will throw an
 * exception
 *
 * @expectedException \InvalidArgumentException
 * @expectedExceptionMessage You are required to select the "Article.author_id"
 * @return void
 */
	public function testEagerLoaderFieldsException() {
		$config = [
			'sourceTable' => $this->author,
			'targetTable' => $this->article,
			'strategy' => 'select'
		];
		$association = new HasMany('Article', $config);
		$keys = [1, 2, 3, 4];
		$query = $this->getMock(
			'Cake\ORM\Query',
			['execute'],
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
		$association = new HasMany('Article', $config);
		$parent = (new Query(null, null))
			->join(['foo' => ['table' => 'foo', 'type' => 'inner', 'conditions' => []]])
			->join(['bar' => ['table' => 'bar', 'type' => 'left', 'conditions' => []]]);

		$query = $this->getMock(
			'Cake\ORM\Query',
			['execute', 'where', 'andWhere', 'order', 'select', 'contain'],
			[null, null]
		);

		$this->article->expects($this->once())->method('find')->with('all')
			->will($this->returnValue($query));
		$results = [
			['id' => 1, 'title' => 'article 1', 'author_id' => 2],
			['id' => 2, 'title' => 'article 2', 'author_id' => 1]
		];
		$query->expects($this->once())->method('execute')
			->will($this->returnValue($results));

		$query->expects($this->once())->method('where')
			->with([])
			->will($this->returnValue($query));

		$expected = clone $parent;
		$joins = $expected->join();
		unset($joins[1]);
		$expected
			->contain([], true)
			->select('Article.author_id', true)
			->join($joins, [], true);
		$query->expects($this->once())->method('andWhere')
			->with(['Article.author_id IN' => $expected])
			->will($this->returnValue($query));

		$callable = $association->eagerLoader([
			'query' => $parent, 'strategy' => HasMany::STRATEGY_SUBQUERY, 'keys' => []
		]);
		$row = ['Author__id' => 1, 'username' => 'author 1'];
		$result = $callable($row);
		$row['Article__Article'] = [
			['id' => 2, 'title' => 'article 2', 'author_id' => 1]
		];
		$this->assertEquals($row, $result);

		$row = ['Author__id' => 2, 'username' => 'author 2'];
		$result = $callable($row);
		$row['Article__Article'] = [
			['id' => 1, 'title' => 'article 1', 'author_id' => 2]
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
			'conditions' => ['Article.is_active' => true]
		];
		$association = new HasMany('Article', $config);
		$query->expects($this->once())->method('join')->with([
			'Article' => [
				'conditions' => [
					'Article.is_active' => true,
					'Author.id = Article.author_id',
				],
				'type' => 'INNER',
				'table' => 'articles'
			]
		]);
		$query->expects($this->once())->method('select')->with([
			'Article__id' => 'Article.id',
			'Article__title' => 'Article.title',
			'Article__author_id' => 'Article.author_id'
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
			'conditions' => ['Article.is_active' => true]
		];
		$association = new HasMany('Article', $config);
		$query->expects($this->once())->method('join')->with([
			'Article' => [
				'conditions' => [
					'Article.is_active' => false
				],
				'type' => 'INNER',
				'table' => 'articles'
			]
		]);
		$query->expects($this->once())->method('select')->with([
			'Article__title' => 'Article.title'
		]);

		$override = [
			'conditions' => ['Article.is_active' => false],
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
			'conditions' => ['Article.is_active' => true]
		];
		$association = new HasMany('Article', $config);
		$query->expects($this->once())->method('join')->with([
			'Article' => [
				'conditions' => [
					'Article.is_active' => true,
					'Author.id = Article.author_id',
				],
				'type' => 'INNER',
				'table' => 'articles'
			]
		]);
		$query->expects($this->never())->method('select');
		$association->attachTo($query, ['includeFields' => false]);
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
			'conditions' => ['Article.is_active' => true],
		];
		$association = new HasMany('Article', $config);

		$this->article->expects($this->once())
			->method('deleteAll')
			->with([
				'Article.is_active' => true,
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
			'conditions' => ['Article.is_active' => true],
			'cascadeCallbacks' => true,
		];
		$association = new HasMany('Article', $config);

		$articleOne = new Entity(['id' => 2, 'title' => 'test']);
		$articleTwo = new Entity(['id' => 3, 'title' => 'testing']);
		$iterator = new \ArrayIterator([
			$articleOne,
			$articleTwo
		]);

		$query = $this->getMock('\Cake\ORM\Query', [], [], '', false);
		$query->expects($this->once())
			->method('where')
			->with(['Article.is_active' => true, 'author_id' => 1])
			->will($this->returnSelf());
		$query->expects($this->any())
			->method('getIterator')
			->will($this->returnValue($iterator));

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

}
