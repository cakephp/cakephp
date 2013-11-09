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

use Cake\Database\Expression\IdentifierExpression;
use Cake\ORM\Association\BelongsToMany;
use Cake\ORM\Entity;
use Cake\ORM\Query;
use Cake\ORM\Table;
use Cake\ORM\TableRegistry;
use Cake\TestSuite\TestCase;

/**
 * Tests BelongsToMany class
 *
 */
class BelongsToManyTest extends TestCase {

/**
 * Set up
 *
 * @return void
 */
	public function setUp() {
		parent::setUp();
		$this->tag = $this->getMock(
			'Cake\ORM\Table', ['find', 'delete'], [['alias' => 'Tags', 'table' => 'tags']]
		);
		$this->tag->schema([
			'id' => ['type' => 'integer'],
			'name' => ['type' => 'string'],
		]);
		$this->article = $this->getMock(
			'Cake\ORM\Table', ['find', 'delete'], [['alias' => 'Articles', 'table' => 'articles']]
		);
		$this->article->schema([
			'id' => ['type' => 'integer'],
			'name' => ['type' => 'string'],
		]);
		TableRegistry::set('Articles', $this->article);
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
		$assoc = new BelongsToMany('Test');
		$this->assertFalse($assoc->canBeJoined());
	}

/**
 * Tests sort() method
 *
 * @return void
 */
	public function testSort() {
		$assoc = new BelongsToMany('Test');
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
		$assoc = new BelongsToMany('Test');
		$this->assertTrue($assoc->requiresKeys());
		$assoc->strategy(BelongsToMany::STRATEGY_SUBQUERY);
		$this->assertFalse($assoc->requiresKeys());
		$assoc->strategy(BelongsToMany::STRATEGY_SELECT);
		$this->assertTrue($assoc->requiresKeys());
	}

/**
 * Tests the pivot method
 *
 * @return void
 */
	public function testPivot() {
		$assoc = new BelongsToMany('Test', [
			'sourceTable' => $this->article,
			'targetTable' => $this->tag
		]);
		$pivot = $assoc->pivot();
		$this->assertInstanceOf('\Cake\ORM\Table', $pivot);
		$this->assertEquals('ArticlesTags', $pivot->alias());
		$this->assertEquals('articles_tags', $pivot->table());
		$this->assertSame($this->article, $pivot->association('Articles')->target());
		$this->assertSame($this->tag, $pivot->association('Tags')->target());

		$belongsTo = '\Cake\ORM\Association\BelongsTo';
		$this->assertInstanceOf($belongsTo, $pivot->association('Articles'));
		$this->assertInstanceOf($belongsTo, $pivot->association('Tags'));

		$this->assertSame($pivot, $this->tag->association('ArticlesTags')->target());
		$this->assertSame($this->article, $this->tag->association('Articles')->target());

		$hasMany = '\Cake\ORM\Association\HasMany';
		$belongsToMany = '\Cake\ORM\Association\BelongsToMany';
		$this->assertInstanceOf($belongsToMany, $this->tag->association('Articles'));
		$this->assertInstanceOf($hasMany, $this->tag->association('ArticlesTags'));

		$this->assertSame($pivot, $assoc->pivot());
		$pivot2 = TableRegistry::get('Foos');
		$assoc->pivot($pivot2);
		$this->assertSame($pivot2, $assoc->pivot());

		$assoc->pivot('ArticlesTags');
		$this->assertSame($pivot, $assoc->pivot());
	}

/**
 * Tests it is possible to set the table name for the join table
 *
 * @return void
 */
	public function testPivotWithDefaultTableName() {
		$assoc = new BelongsToMany('Test', [
			'sourceTable' => $this->article,
			'targetTable' => $this->tag,
			'joinTable' => 'tags_articles'
		]);
		$pivot = $assoc->pivot();
		$this->assertEquals('TagsArticles', $pivot->alias());
		$this->assertEquals('tags_articles', $pivot->table());
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
			'sourceTable' => $this->article,
			'targetTable' => $this->tag,
			'conditions' => ['Tags.name' => 'cake']
		];
		TableRegistry::get('ArticlesTags', [
			'table' => 'articles_tags',
			'schema' => [
				'article_id' => ['type' => 'integer'],
				'tag_id' => ['type' => 'integer']
			]
		]);
		$association = new BelongsToMany('Tags', $config);
		$query->expects($this->at(0))->method('join')->with([
			'Tags' => [
				'conditions' => [
					'Tags.name' => 'cake'
				],
				'type' => 'INNER',
				'table' => 'tags'
			]
		]);

		$field1 = new IdentifierExpression('ArticlesTags.article_id');
		$field2 = new IdentifierExpression('ArticlesTags.tag_id');

		$query->expects($this->at(2))->method('join')->with([
			'ArticlesTags' => [
				'conditions' => [
					['Articles.id' => $field1],
					['Tags.id' => $field2]
				],
				'type' => 'INNER',
				'table' => 'articles_tags'
			]
		]);
		$query->expects($this->at(1))->method('select')->with([
			'Tags__id' => 'Tags.id',
			'Tags__name' => 'Tags.name',
		]);
		$query->expects($this->at(3))->method('select')->with([
			'ArticlesTags__article_id' => 'ArticlesTags.article_id',
			'ArticlesTags__tag_id' => 'ArticlesTags.tag_id',
		]);
		$association->attachTo($query);
	}

/**
 * Tests that it is possible to avoid fields inclusion for the associated table
 *
 * @return void
 */
	public function testAttachToNoFields() {
		$query = $this->getMock('\Cake\ORM\Query', ['join', 'select'], [null, null]);
		$config = [
			'sourceTable' => $this->article,
			'targetTable' => $this->tag,
			'conditions' => ['Tags.name' => 'cake']
		];
		TableRegistry::get('ArticlesTags', [
			'table' => 'articles_tags',
			'schema' => [
				'article_id' => ['type' => 'integer'],
				'tag_id' => ['type' => 'integer']
			]
		]);
		$association = new BelongsToMany('Tags', $config);
		$query->expects($this->at(0))->method('join')->with([
			'Tags' => [
				'conditions' => [
					'Tags.name' => 'cake'
				],
				'type' => 'INNER',
				'table' => 'tags'
			]
		]);

		$field1 = new IdentifierExpression('ArticlesTags.article_id');
		$field2 = new IdentifierExpression('ArticlesTags.tag_id');

		$query->expects($this->at(1))->method('join')->with([
			'ArticlesTags' => [
				'conditions' => [
					['Articles.id' => $field1],
					['Tags.id' => $field2]
				],
				'type' => 'INNER',
				'table' => 'articles_tags'
			]
		]);
		$query->expects($this->never())->method('select');
		$association->attachTo($query, ['includeFields' => false]);
	}

/**
 * Test the eager loader method with no extra options
 *
 * @return void
 */
	public function testEagerLoader() {
		$config = [
			'sourceTable' => $this->article,
			'targetTable' => $this->tag,
		];
		TableRegistry::get('ArticlesTags', [
			'table' => 'articles_tags',
			'schema' => [
				'article_id' => ['type' => 'integer'],
				'tag_id' => ['type' => 'integer']
			]
		]);
		$association = new BelongsToMany('Tags', $config);
		$keys = [1, 2, 3, 4];
		$query = $this->getMock('Cake\ORM\Query', ['execute', 'contain'], [null, null]);
		$this->tag->expects($this->once())->method('find')->with('all')
			->will($this->returnValue($query));
		$results = [
			['id' => 1, 'name' => 'foo', 'articles_tags' => ['article_id' => 1]],
			['id' => 2, 'name' => 'bar', 'articles_tags' => ['article_id' => 2]]
		];
		$query->expects($this->once())->method('execute')
			->will($this->returnValue($results));

		$query->expects($this->once())->method('contain')
			->with([
				'ArticlesTags' => [
					'conditions' => ['ArticlesTags.article_id in' => $keys],
					'matching' => true
				]
			])
			->will($this->returnSelf());

		$callable = $association->eagerLoader(compact('keys', 'query'));
		$row = ['Articles__id' => 1, 'title' => 'article 1'];
		$result = $callable($row);
		$row['Tags__Tags'] = [
			['id' => 1, 'name' => 'foo', 'articles_tags' => ['article_id' => 1]]
		];
		$this->assertEquals($row, $result);

		$row = ['Articles__id' => 2, 'title' => 'article 2'];
		$result = $callable($row);
		$row['Tags__Tags'] = [
			['id' => 2, 'name' => 'bar', 'articles_tags' => ['article_id' => 2]]
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
			'sourceTable' => $this->article,
			'targetTable' => $this->tag,
			'conditions' => ['Tags.name' => 'foo'],
			'sort' => ['id' => 'ASC'],
		];
		TableRegistry::get('ArticlesTags', [
			'table' => 'articles_tags',
			'schema' => [
				'article_id' => ['type' => 'integer'],
				'tag_id' => ['type' => 'integer']
			]
		]);
		$association = new BelongsToMany('Tags', $config);
		$keys = [1, 2, 3, 4];
		$methods = ['execute', 'contain', 'where', 'order'];
		$query = $this->getMock('Cake\ORM\Query', $methods, [null, null]);
		$this->tag->expects($this->once())->method('find')->with('all')
			->will($this->returnValue($query));
		$results = [
			['id' => 1, 'name' => 'foo', 'articles_tags' => ['article_id' => 1]],
			['id' => 2, 'name' => 'bar', 'articles_tags' => ['article_id' => 2]]
		];
		$query->expects($this->once())->method('execute')
			->will($this->returnValue($results));

		$query->expects($this->once())->method('contain')
			->with([
				'ArticlesTags' => [
					'conditions' => ['ArticlesTags.article_id in' => $keys],
					'matching' => true
				]
			])
			->will($this->returnSelf());

		$query->expects($this->once())->method('where')
			->with(['Tags.name' => 'foo'])
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
			'sourceTable' => $this->article,
			'targetTable' => $this->tag,
			'conditions' => ['Tags.name' => 'foo'],
			'sort' => ['id' => 'ASC'],
		];
		TableRegistry::get('ArticlesTags', [
			'table' => 'articles_tags',
			'schema' => [
				'article_id' => ['type' => 'integer'],
				'tag_id' => ['type' => 'integer']
			]
		]);
		$association = new BelongsToMany('Tags', $config);
		$keys = [1, 2, 3, 4];
		$methods = ['execute', 'contain', 'where', 'order', 'select'];
		$query = $this->getMock('Cake\ORM\Query', $methods, [null, null]);
		$this->tag->expects($this->once())->method('find')->with('all')
			->will($this->returnValue($query));
		$results = [
			['id' => 1, 'name' => 'foo', 'articles_tags' => ['article_id' => 1]],
			['id' => 2, 'name' => 'bar', 'articles_tags' => ['article_id' => 2]]
		];
		$query->expects($this->once())->method('execute')
			->will($this->returnValue($results));

		$query->expects($this->once())->method('contain')
			->with([
				'ArticlesTags' => [
					'conditions' => ['ArticlesTags.article_id in' => $keys],
					'matching' => true
				]
			])
			->will($this->returnSelf());

		$query->expects($this->once())->method('where')
			->with([
				'Tags.name' => 'foo',
				'Tags.id !=' => 3
			])
			->will($this->returnValue($query));

		$query->expects($this->once())->method('order')
			->with(['name' => 'DESC'])
			->will($this->returnValue($query));

		$query->expects($this->once())->method('select')
			->with([
				'Tags__name' => 'Tags.name',
				'ArticlesTags__article_id' => 'ArticlesTags.article_id'
			])
			->will($this->returnValue($query));

		$association->eagerLoader([
			'conditions' => ['Tags.id !=' => 3],
			'sort' => ['name' => 'DESC'],
			'fields' => ['name', 'ArticlesTags.article_id'],
			'keys' => $keys,
			'query' => $query
		]);
	}

/**
 * Test the eager loader method with default query clauses
 *
 * @expectedException \InvalidArgumentException
 * @expectedExceptionMessage You are required to select the "ArticlesTags.article_id"
 * @return void
 */
	public function testEagerLoaderFieldsException() {
		$config = [
			'sourceTable' => $this->article,
			'targetTable' => $this->tag,
			'conditions' => ['Tags.name' => 'foo'],
			'sort' => ['id' => 'ASC'],
		];
		TableRegistry::get('ArticlesTags', [
			'table' => 'articles_tags',
			'schema' => [
				'article_id' => ['type' => 'integer'],
				'tag_id' => ['type' => 'integer']
			]
		]);
		$association = new BelongsToMany('Tags', $config);
		$keys = [1, 2, 3, 4];
		$methods = ['execute', 'contain', 'where', 'order', 'select'];
		$query = $this->getMock('Cake\ORM\Query', $methods, [null, null]);
		$this->tag->expects($this->once())->method('find')->with('all')
			->will($this->returnValue($query));
		$query->expects($this->any())->method('contain')->will($this->returnSelf());

		$query->expects($this->once())->method('where')->will($this->returnSelf());

		$association->eagerLoader([
			'keys' => $keys,
			'fields' => ['name'],
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
			'sourceTable' => $this->article,
			'targetTable' => $this->tag,
			'conditions' => ['Tags.name' => 'foo'],
			'sort' => ['id' => 'ASC'],
		];
		TableRegistry::get('ArticlesTags', [
			'table' => 'articles_tags',
			'schema' => [
				'article_id' => ['type' => 'integer'],
				'tag_id' => ['type' => 'integer']
			]
		]);
		$association = new BelongsToMany('Tags', $config);
		$parent = (new Query(null, null))
			->join(['foo' => ['table' => 'foo', 'type' => 'inner', 'conditions' => []]])
			->join(['bar' => ['table' => 'bar', 'type' => 'left', 'conditions' => []]]);

		$query = $this->getMock(
			'Cake\ORM\Query',
			['execute', 'where', 'andWhere', 'order', 'select', 'contain'],
			[null, null]
		);

		$this->tag->expects($this->once())->method('find')->with('all')
			->will($this->returnValue($query));
		$results = [
			['id' => 1, 'name' => 'foo', 'articles_tags' => ['article_id' => 1]],
			['id' => 2, 'name' => 'bar', 'articles_tags' => ['article_id' => 2]]
		];
		$query->expects($this->once())->method('execute')
			->will($this->returnValue($results));

		$query->expects($this->once())->method('where')
			->with(['Tags.name' => 'foo'])
			->will($this->returnSelf());

		$expected = clone $parent;
		$joins = $expected->join();
		unset($joins[1]);
		$expected
			->contain([], true)
			->select('ArticlesTags.article_id', true)
			->join($joins, [], true);

		$query->expects($this->once())->method('where')
			->with(['Tags.name' => 'foo'])
			->will($this->returnValue($query));

		$query->expects($this->once())->method('contain')
			->with([
				'ArticlesTags' => [
					'conditions' => ['ArticlesTags.article_id in' => $expected],
					'matching' => true
				]
			])
			->will($this->returnSelf());

		$callable = $association->eagerLoader([
			'query' => $parent, 'strategy' => BelongsToMany::STRATEGY_SUBQUERY,
			'keys' => []
		]);

		$row['Tags__Tags'] = [
			['id' => 1, 'name' => 'foo', 'articles_tags' => ['article_id' => 1]]
		];
		$row['Articles__id'] = 1;
		$result = $callable($row);
		$this->assertEquals($row, $result);

		$row['Tags__Tags'] = [
			['id' => 2, 'name' => 'bar', 'articles_tags' => ['article_id' => 2]]
		];
		$row['Articles__id'] = 2;
		$result = $callable($row);
		$this->assertEquals($row, $result);
	}

/**
 * Test cascading deletes.
 *
 * @return void
 */
	public function testCascadeDelete() {
		$articleTag = $this->getMock('Cake\ORM\Table', ['deleteAll'], [], '', false);
		$config = [
			'sourceTable' => $this->article,
			'targetTable' => $this->tag,
			'conditions' => ['Tags.name' => 'foo'],
			'sort' => ['id' => 'ASC'],
		];
		$association = new BelongsToMany('Tags', $config);
		$association->pivot($articleTag);

		$articleTag->expects($this->once())
			->method('deleteAll')
			->with([
				'Tags.name' => 'foo',
				'article_id' => 1
			]);

		$entity = new Entity(['id' => 1, 'name' => 'PHP']);
		$association->cascadeDelete($entity);
	}

/**
 * Test cascading deletes with callbacks.
 *
 * @return void
 */
	public function testCascadeDeleteWithCallbacks() {
		$articleTag = $this->getMock('Cake\ORM\Table', ['find', 'delete'], [], '', false);
		$config = [
			'sourceTable' => $this->article,
			'targetTable' => $this->tag,
			'conditions' => ['Tags.name' => 'foo'],
			'cascadeCallbacks' => true,
		];
		$association = new BelongsToMany('Tag', $config);
		$association->pivot($articleTag);

		$articleTagOne = new Entity(['article_id' => 1, 'tag_id' => 2]);
		$articleTagTwo = new Entity(['article_id' => 1, 'tag_id' => 4]);
		$iterator = new \ArrayIterator([
			$articleTagOne,
			$articleTagTwo
		]);

		$query = $this->getMock('\Cake\ORM\Query', [], [], '', false);
		$query->expects($this->once())
			->method('where')
			->with(['Tags.name' => 'foo', 'article_id' => 1])
			->will($this->returnSelf());

		$query->expects($this->any())
			->method('getIterator')
			->will($this->returnValue($iterator));

		$articleTag->expects($this->once())
			->method('find')
			->will($this->returnValue($query));

		$articleTag->expects($this->at(1))
			->method('delete')
			->with($articleTagOne, []);
		$articleTag->expects($this->at(2))
			->method('delete')
			->with($articleTagTwo, []);

		$articleTag->expects($this->never())
			->method('deleteAll');

		$entity = new Entity(['id' => 1, 'name' => 'PHP']);
		$association->cascadeDelete($entity);
	}

}
