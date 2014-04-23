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
use Cake\Datasource\ConnectionManager;
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
			'_constraints' => [
				'primary' => ['type' => 'primary', 'columns' => ['id']]
			]
		]);
		$this->article = $this->getMock(
			'Cake\ORM\Table', ['find', 'delete'], [['alias' => 'Articles', 'table' => 'articles']]
		);
		$this->article->schema([
			'id' => ['type' => 'integer'],
			'name' => ['type' => 'string'],
			'_constraints' => [
				'primary' => ['type' => 'primary', 'columns' => ['id']]
			]
		]);
		TableRegistry::set('Articles', $this->article);
		TableRegistry::get('ArticlesTags', [
			'table' => 'articles_tags',
			'schema' => [
				'article_id' => ['type' => 'integer'],
				'tag_id' => ['type' => 'integer'],
				'_constraints' => [
					'primary' => ['type' => 'primary', 'columns' => ['article_id', 'tag_id']]
				]
			]
		]);
		$this->tagsTypeMap = new TypeMap([
			'Tags.id' => 'integer',
			'id' => 'integer',
			'Tags.name' => 'string',
			'name' => 'string',
		]);
		$this->articlesTagsTypeMap = new TypeMap([
			'ArticlesTags.article_id' => 'integer',
			'article_id' => 'integer',
			'ArticlesTags.tag_id' => 'integer',
			'tag_id' => 'integer',
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
 * Tests the junction method
 *
 * @return void
 */
	public function testJunction() {
		$assoc = new BelongsToMany('Test', [
			'sourceTable' => $this->article,
			'targetTable' => $this->tag
		]);
		$junction = $assoc->junction();
		$this->assertInstanceOf('\Cake\ORM\Table', $junction);
		$this->assertEquals('ArticlesTags', $junction->alias());
		$this->assertEquals('articles_tags', $junction->table());
		$this->assertSame($this->article, $junction->association('Articles')->target());
		$this->assertSame($this->tag, $junction->association('Tags')->target());

		$belongsTo = '\Cake\ORM\Association\BelongsTo';
		$this->assertInstanceOf($belongsTo, $junction->association('Articles'));
		$this->assertInstanceOf($belongsTo, $junction->association('Tags'));

		$this->assertSame($junction, $this->tag->association('ArticlesTags')->target());
		$this->assertSame($this->article, $this->tag->association('Articles')->target());

		$hasMany = '\Cake\ORM\Association\HasMany';
		$belongsToMany = '\Cake\ORM\Association\BelongsToMany';
		$this->assertInstanceOf($belongsToMany, $this->tag->association('Articles'));
		$this->assertInstanceOf($hasMany, $this->tag->association('ArticlesTags'));

		$this->assertSame($junction, $assoc->junction());
		$junction2 = TableRegistry::get('Foos');
		$assoc->junction($junction2);
		$this->assertSame($junction2, $assoc->junction());

		$assoc->junction('ArticlesTags');
		$this->assertSame($junction, $assoc->junction());
	}

/**
 * Tests it is possible to set the table name for the join table
 *
 * @return void
 */
	public function testJunctionWithDefaultTableName() {
		$assoc = new BelongsToMany('Test', [
			'sourceTable' => $this->article,
			'targetTable' => $this->tag,
			'joinTable' => 'tags_articles'
		]);
		$junction = $assoc->junction();
		$this->assertEquals('TagsArticles', $junction->alias());
		$this->assertEquals('tags_articles', $junction->table());
	}

/**
 * Tests saveStrategy
 *
 * @return void
 */
	public function testSaveStrategy() {
		$assoc = new BelongsToMany('Test');
		$this->assertEquals(BelongsToMany::SAVE_REPLACE, $assoc->saveStrategy());
		$assoc->saveStrategy(BelongsToMany::SAVE_APPEND);
		$this->assertEquals(BelongsToMany::SAVE_APPEND, $assoc->saveStrategy());
		$assoc->saveStrategy(BelongsToMany::SAVE_REPLACE);
		$this->assertEquals(BelongsToMany::SAVE_REPLACE, $assoc->saveStrategy());
	}

/**
 * Tests that it is possible to pass the save strategy in the constructor
 *
 * @return void
 */
	public function testSaveStrategyInOptions() {
		$assoc = new BelongsToMany('Test', ['saveStrategy' => BelongsToMany::SAVE_APPEND]);
		$this->assertEquals(BelongsToMany::SAVE_APPEND, $assoc->saveStrategy());
	}

/**
 * Tests that passing an invalid strategy will throw an exception
 *
 * @expectedException \InvalidArgumentException
 * @expectedExceptionMessage Invalid save strategy "depsert"
 * @return void
 */
	public function testSaveStrategyInvalid() {
		$assoc = new BelongsToMany('Test', ['saveStrategy' => 'depsert']);
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
		$association = new BelongsToMany('Tags', $config);
		$query->expects($this->at(0))->method('join')->with([
			'Tags' => [
				'conditions' => new QueryExpression([
					'Tags.name' => 'cake'
				], $this->tagsTypeMap),
				'type' => 'INNER',
				'table' => 'tags'
			]
		]);

		$field1 = new IdentifierExpression('ArticlesTags.article_id');
		$field2 = new IdentifierExpression('ArticlesTags.tag_id');

		$query->expects($this->at(2))->method('join')->with([
			'ArticlesTags' => [
				'conditions' => new QueryExpression([
					['Articles.id' => $field1],
					['Tags.id' => $field2]
				], $this->articlesTagsTypeMap),
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
		$association = new BelongsToMany('Tags', $config);
		$query->expects($this->at(0))->method('join')->with([
			'Tags' => [
				'conditions' => new QueryExpression([
					'Tags.name' => 'cake'
				], $this->tagsTypeMap),
				'type' => 'INNER',
				'table' => 'tags'
			]
		]);

		$field1 = new IdentifierExpression('ArticlesTags.article_id');
		$field2 = new IdentifierExpression('ArticlesTags.tag_id');

		$query->expects($this->at(1))->method('join')->with([
			'ArticlesTags' => [
				'conditions' => new QueryExpression([
					['Articles.id' => $field1],
					['Tags.id' => $field2]
				], $this->articlesTagsTypeMap),
				'type' => 'INNER',
				'table' => 'articles_tags'
			]
		]);
		$query->expects($this->never())->method('select');
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
			'sourceTable' => $this->article,
			'targetTable' => $this->tag,
			'conditions' => ['Tags.name' => 'cake']
		];
		$association = new BelongsToMany('Tags', $config);
		$query->expects($this->at(0))->method('join')->with([
			'Tags' => [
				'conditions' => new QueryExpression([
					'a' => 1,
					'Tags.name' => 'cake',
				], $this->tagsTypeMap),
				'type' => 'INNER',
				'table' => 'tags'
			]
		]);

		$field1 = new IdentifierExpression('ArticlesTags.article_id');
		$field2 = new IdentifierExpression('ArticlesTags.tag_id');

		$query->expects($this->at(2))->method('join')->with([
			'ArticlesTags' => [
				'conditions' => new QueryExpression([
					['Articles.id' => $field1],
					['Tags.id' => $field2]
				], $this->articlesTagsTypeMap),
				'type' => 'INNER',
				'table' => 'articles_tags'
			]
		]);

		$query->expects($this->once())->method('select')
			->with([
				'Tags__a' => 'Tags.a',
				'Tags__b' => 'Tags.b'
			]);
		$builder = function($q) {
			return $q->select(['a', 'b'])->where(['a' => 1]);
		};
		$association->attachTo($query, [
			'includeFields' => false,
			'queryBuilder' => $builder
		]);
	}

/**
 * Tests that using belongsToMany with a table having a multi column primary
 * key will work if the foreign key is passed
 *
 * @return void
 */
	public function testAttachToMultiPrimaryKey() {
		$query = $this->getMock('\Cake\ORM\Query', ['join', 'select'], [null, null]);
		$config = [
			'sourceTable' => $this->article,
			'targetTable' => $this->tag,
			'conditions' => ['Tags.name' => 'cake'],
			'foreignKey' => ['article_id', 'article_site_id'],
			'targetForeignKey' => ['tag_id', 'tag_site_id']
		];
		$this->article->primaryKey(['id', 'site_id']);
		$this->tag->primaryKey(['id', 'my_site_id']);
		$association = new BelongsToMany('Tags', $config);
		$query->expects($this->at(0))->method('join')->with([
			'Tags' => [
				'conditions' => new QueryExpression([
					'Tags.name' => 'cake'
				], $this->tagsTypeMap),
				'type' => 'INNER',
				'table' => 'tags'
			]
		]);

		$fieldA = new IdentifierExpression('ArticlesTags.article_id');
		$fieldB = new IdentifierExpression('ArticlesTags.article_site_id');
		$fieldC = new IdentifierExpression('ArticlesTags.tag_id');
		$fieldD = new IdentifierExpression('ArticlesTags.tag_site_id');

		$query->expects($this->at(1))->method('join')->with([
			'ArticlesTags' => [
				'conditions' => new QueryExpression([
					['Articles.id' => $fieldA, 'Articles.site_id' => $fieldB],
					['Tags.id' => $fieldC, 'Tags.my_site_id' => $fieldD]
				], $this->articlesTagsTypeMap),
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
		$association = new BelongsToMany('Tags', $config);
		$keys = [1, 2, 3, 4];
		$query = $this->getMock('Cake\ORM\Query', ['all', 'matching'], [null, null]);

		$this->tag->expects($this->once())
			->method('find')
			->with('all')
			->will($this->returnValue($query));

		$results = [
			['id' => 1, 'name' => 'foo', 'articles_tags' => ['article_id' => 1]],
			['id' => 2, 'name' => 'bar', 'articles_tags' => ['article_id' => 2]]
		];
		$query->expects($this->once())
			->method('all')
			->will($this->returnValue($results));

		$query->expects($this->once())->method('matching')
			->will($this->returnCallback(function($alias, $callable) use ($query, $keys) {
				$this->assertEquals('ArticlesTags', $alias);
				$q = $this->getMock('Cake\ORM\Query', [], [null, null]);

				$q->expects($this->once())->method('andWhere')
					->with(['ArticlesTags.article_id IN' => $keys])
					->will($this->returnSelf());

				$this->assertSame($q, $callable($q));
				return $query;
			}));

		$query->hydrate(false);

		$callable = $association->eagerLoader(compact('keys', 'query'));
		$row = ['Articles__id' => 1, 'title' => 'article 1'];
		$result = $callable($row);
		$row['Tags'] = [
			['id' => 1, 'name' => 'foo', '_joinData' => ['article_id' => 1]]
		];
		$this->assertEquals($row, $result);

		$row = ['Articles__id' => 2, 'title' => 'article 2'];
		$result = $callable($row);
		$row['Tags'] = [
			['id' => 2, 'name' => 'bar', '_joinData' => ['article_id' => 2]]
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
		$association = new BelongsToMany('Tags', $config);
		$keys = [1, 2, 3, 4];
		$methods = ['all', 'matching', 'where', 'order'];
		$query = $this->getMock('Cake\ORM\Query', $methods, [null, null]);
		$this->tag->expects($this->once())
			->method('find')
			->with('all')
			->will($this->returnValue($query));
		$results = [
			['id' => 1, 'name' => 'foo', 'articles_tags' => ['article_id' => 1]],
			['id' => 2, 'name' => 'bar', 'articles_tags' => ['article_id' => 2]]
		];
		$query->expects($this->once())
			->method('all')
			->will($this->returnValue($results));

		$query->expects($this->once())->method('matching')
			->will($this->returnCallback(function($alias, $callable) use ($query, $keys) {
				$this->assertEquals('ArticlesTags', $alias);
				$q = $this->getMock('Cake\ORM\Query', [], [null, null]);

				$q->expects($this->once())->method('andWhere')
					->with(['ArticlesTags.article_id IN' => $keys])
					->will($this->returnSelf());

				$this->assertSame($q, $callable($q));
				return $query;
			}));

		$query->expects($this->at(0))->method('where')
			->with(['Tags.name' => 'foo'])
			->will($this->returnSelf());
		$query->expects($this->at(1))->method('where')
			->with([])
			->will($this->returnSelf());

		$query->expects($this->once())->method('order')
			->with(['id' => 'ASC'])
			->will($this->returnSelf());

		$query->hydrate(false);

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
		$association = new BelongsToMany('Tags', $config);
		$keys = [1, 2, 3, 4];
		$methods = ['all', 'matching', 'where', 'order', 'select'];
		$query = $this->getMock('Cake\ORM\Query', $methods, [null, null]);
		$this->tag->expects($this->once())->method('find')->with('all')
			->will($this->returnValue($query));
		$results = [
			['id' => 1, 'name' => 'foo', 'articles_tags' => ['article_id' => 1]],
			['id' => 2, 'name' => 'bar', 'articles_tags' => ['article_id' => 2]]
		];
		$query->expects($this->once())->method('all')
			->will($this->returnValue($results));

		$query->expects($this->once())->method('matching')
			->will($this->returnCallback(function($alias, $callable) use ($query, $keys) {
				$this->assertEquals('ArticlesTags', $alias);
				$q = $this->getMock('Cake\ORM\Query', [], [null, null]);

				$q->expects($this->once())->method('andWhere')
					->with(['ArticlesTags.article_id IN' => $keys])
					->will($this->returnSelf());

				$this->assertSame($q, $callable($q));
				return $query;
			}));

		$query->expects($this->at(0))->method('where')
			->with(['Tags.name' => 'foo'])
			->will($this->returnSelf());

		$query->expects($this->at(1))->method('where')
			->with(['Tags.id !=' => 3])
			->will($this->returnSelf());

		$query->expects($this->once())->method('order')
			->with(['name' => 'DESC'])
			->will($this->returnSelf());

		$query->expects($this->once())->method('select')
			->with([
				'Tags__name' => 'Tags.name',
				'ArticlesTags__article_id' => 'ArticlesTags.article_id'
			])
			->will($this->returnSelf());

		$query->hydrate(false);

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
		$association = new BelongsToMany('Tags', $config);
		$keys = [1, 2, 3, 4];
		$methods = ['all', 'contain', 'where', 'order', 'select'];
		$query = $this->getMock('Cake\ORM\Query', $methods, [null, null]);
		$this->tag->expects($this->once())
			->method('find')
			->with('all')
			->will($this->returnValue($query));
		$query->expects($this->any())->method('contain')->will($this->returnSelf());

		$query->expects($this->exactly(2))->method('where')->will($this->returnSelf());

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
		$association = new BelongsToMany('Tags', $config);
		$parent = (new Query(null, $this->article))
			->join(['foo' => ['table' => 'foo', 'type' => 'inner', 'conditions' => []]])
			->join(['bar' => ['table' => 'bar', 'type' => 'left', 'conditions' => []]]);
		$parent->hydrate(false);

		$query = $this->getMock(
			'Cake\ORM\Query',
			['all', 'where', 'andWhere', 'order', 'select', 'matching'],
			[null, $this->article]
		);

		$query->hydrate(false);

		$this->tag->expects($this->once())
			->method('find')
			->with('all')
			->will($this->returnValue($query));
		$results = [
			['id' => 1, 'name' => 'foo', 'articles_tags' => ['article_id' => 1]],
			['id' => 2, 'name' => 'bar', 'articles_tags' => ['article_id' => 2]]
		];
		$query->expects($this->once())
			->method('all')
			->will($this->returnValue($results));

		$expected = clone $parent;
		$joins = $expected->join();
		unset($joins['bar']);
		$expected
			->contain([], true)
			->select(['Articles__id' => 'Articles.id'], true)
			->join($joins, [], true);

		$query->expects($this->at(0))->method('where')
			->with(['Tags.name' => 'foo'])
			->will($this->returnSelf());

		$query->expects($this->at(1))->method('where')
			->with([])
			->will($this->returnSelf());

		$query->expects($this->once())->method('matching')
			->will($this->returnCallback(function($alias, $callable) use ($query, $expected) {
				$this->assertEquals('ArticlesTags', $alias);
				$q = $this->getMock('Cake\ORM\Query', [], [null, null]);

				$q->expects($this->once())->method('andWhere')
					->with(['ArticlesTags.article_id IN' => $expected])
					->will($this->returnSelf());

				$this->assertSame($q, $callable($q));
				return $query;
			}));

		$callable = $association->eagerLoader([
			'query' => $parent, 'strategy' => BelongsToMany::STRATEGY_SUBQUERY,
			'keys' => []
		]);

		$row['Tags'] = [
			['id' => 1, 'name' => 'foo', '_joinData' => ['article_id' => 1]]
		];
		$row['Articles__id'] = 1;
		$result = $callable($row);
		$this->assertEquals($row, $result);

		$row['Tags'] = [
			['id' => 2, 'name' => 'bar', '_joinData' => ['article_id' => 2]]
		];
		$row['Articles__id'] = 2;
		$result = $callable($row);
		$this->assertEquals($row, $result);
	}

/**
 * Tests eagerLoader with queryBuilder
 *
 * @return void
 */
	public function testEagerLoaderWithQueryBuilder() {
		$config = [
			'sourceTable' => $this->article,
			'targetTable' => $this->tag,
		];
		$association = new BelongsToMany('Tags', $config);
		$keys = [1, 2, 3, 4];
		$query = $this->getMock(
			'Cake\ORM\Query',
			['all', 'matching', 'andWhere', 'limit'],
			[null, null]
		);

		$this->tag->expects($this->once())
			->method('find')
			->with('all')
			->will($this->returnValue($query));

		$results = [
			['id' => 1, 'name' => 'foo', 'articles_tags' => ['article_id' => 1]],
			['id' => 2, 'name' => 'bar', 'articles_tags' => ['article_id' => 2]]
		];
		$query->expects($this->once())
			->method('all')
			->will($this->returnValue($results));

		$query->expects($this->once())->method('matching')
			->will($this->returnCallback(function($alias, $callable) use ($query, $keys) {
				$this->assertEquals('ArticlesTags', $alias);
				$q = $this->getMock('Cake\ORM\Query', [], [null, null]);

				$q->expects($this->once())->method('andWhere')
					->with(['ArticlesTags.article_id IN' => $keys])
					->will($this->returnSelf());

				$this->assertSame($q, $callable($q));
				return $query;
			}));

		$query->hydrate(false);

		$query->expects($this->once())
			->method('andWhere')
			->with(['foo' => 1])
			->will($this->returnSelf());

		$query->expects($this->once())
			->method('limit')
			->with(1)
			->will($this->returnSelf());

		$queryBuilder = function($q) {
			return $q->andWhere(['foo' => 1])->limit(1);
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
			'sourceTable' => $this->article,
			'targetTable' => $this->tag,
			'foreignKey' => ['article_id', 'site_id'],
			'targetForeignKey' => ['tag_id', 'site_id']
		];
		$this->article->primaryKey(['id', 'site_id']);
		$this->tag->primaryKey(['id', 'site_id']);

		$table = TableRegistry::get('ArticlesTags');
		$table->schema([
			'article_id' => ['type' => 'integer'],
			'tag_id' => ['type' => 'integer'],
			'site_id' => ['type' => 'integer'],
		]);
		$association = new BelongsToMany('Tags', $config);
		$keys = [[1, 10], [2, 20], [3, 30], [4, 40]];
		$query = $this->getMock('Cake\ORM\Query', ['all', 'matching'], [null, null]);

		$this->tag->expects($this->once())
			->method('find')
			->with('all')
			->will($this->returnValue($query));

		$results = [
			[
				'id' => 1,
				'name' => 'foo',
				'site_id' => 1,
				'articles_tags' => [
					'article_id' => 1,
					'site_id' => 1
				]
			],
			[
				'id' => 2,
				'name' => 'bar',
				'site_id' => 2,
				'articles_tags' => [
					'article_id' => 2,
					'site_id' => 2
				]
			]
		];
		$query->expects($this->once())
			->method('all')
			->will($this->returnValue($results));

		$tuple = new TupleComparison(
			['ArticlesTags.article_id', 'ArticlesTags.site_id'], $keys, [], 'IN'
		);
		$query->expects($this->once())->method('matching')
			->will($this->returnCallback(function($alias, $callable) use ($query, $tuple) {
				$this->assertEquals('ArticlesTags', $alias);
				$q = $this->getMock('Cake\ORM\Query', [], [null, null]);

				$q->expects($this->once())->method('andWhere')
					->with($tuple)
					->will($this->returnSelf());

				$this->assertSame($q, $callable($q));
				return $query;
			}));

		$query->hydrate(false);

		$callable = $association->eagerLoader(compact('keys', 'query'));
		$row = ['Articles__id' => 1, 'title' => 'article 1', 'Articles__site_id' => 1];
		$result = $callable($row);
		$row['Tags'] = [
			[
				'id' => 1,
				'name' => 'foo',
				'site_id' => 1,
				'_joinData' => ['article_id' => 1, 'site_id' => 1]
			]
		];
		$this->assertEquals($row, $result);

		$row = ['Articles__id' => 2, 'title' => 'article 2', 'Articles__site_id' => 2];
		$result = $callable($row);
		$row['Tags'] = [
			[
				'id' => 2,
				'name' => 'bar',
				'site_id' => 2,
				'_joinData' => ['article_id' => 2, 'site_id' => 2]
			]
		];
		$this->assertEquals($row, $result);
	}

/**
 * Test cascading deletes.
 *
 * @return void
 */
	public function testCascadeDelete() {
		$articleTag = $this->getMock('Cake\ORM\Table', ['deleteAll'], []);
		$config = [
			'sourceTable' => $this->article,
			'targetTable' => $this->tag,
			'sort' => ['id' => 'ASC'],
		];
		$association = new BelongsToMany('Tags', $config);
		$association->junction($articleTag);
		$this->article
			->association($articleTag->alias())
			->conditions(['click_count' => 3]);

		$articleTag->expects($this->once())
			->method('deleteAll')
			->with([
				'click_count' => 3,
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
		$articleTag = $this->getMock('Cake\ORM\Table', ['find', 'delete'], []);
		$config = [
			'sourceTable' => $this->article,
			'targetTable' => $this->tag,
			'cascadeCallbacks' => true,
		];
		$association = new BelongsToMany('Tag', $config);
		$association->junction($articleTag);
		$this->article
			->association($articleTag->alias())
			->conditions(['click_count' => 3]);

		$articleTagOne = new Entity(['article_id' => 1, 'tag_id' => 2]);
		$articleTagTwo = new Entity(['article_id' => 1, 'tag_id' => 4]);
		$iterator = new \ArrayIterator([
			$articleTagOne,
			$articleTagTwo
		]);

		$query = $this->getMock('\Cake\ORM\Query', [], [], '', false);
		$query->expects($this->at(0))
			->method('where')
			->with(['click_count' => 3])
			->will($this->returnSelf());
		$query->expects($this->at(1))
			->method('where')
			->with(['article_id' => 1])
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

/**
 * Test linking entities having a non persisted source entity
 *
 * @expectedException \InvalidArgumentException
 * @expectedExceptionMessage Source entity needs to be persisted before proceeding
 * @return void
 */
	public function testLinkWithNotPersistedSource() {
		$config = [
			'sourceTable' => $this->article,
			'targetTable' => $this->tag,
			'joinTable' => 'tags_articles'
		];
		$assoc = new BelongsToMany('Test', $config);
		$entity = new Entity(['id' => 1]);
		$tags = [new Entity(['id' => 2]), new Entity(['id' => 3])];
		$assoc->link($entity, $tags);
	}

/**
 * Test liking entities having a non persited target entity
 *
 * @expectedException \InvalidArgumentException
 * @expectedExceptionMessage Cannot link not persisted entities
 * @return void
 */
	public function testLinkWithNotPersistedTarget() {
		$config = [
			'sourceTable' => $this->article,
			'targetTable' => $this->tag,
			'joinTable' => 'tags_articles'
		];
		$assoc = new BelongsToMany('Test', $config);
		$entity = new Entity(['id' => 1], ['markNew' => false]);
		$tags = [new Entity(['id' => 2]), new Entity(['id' => 3])];
		$assoc->link($entity, $tags);
	}

/**
 * Tests that liking entities will validate data and pass on to _saveLinks
 *
 * @return void
 */
	public function testLinkSuccess() {
		$connection = ConnectionManager::get('test');
		$joint = $this->getMock(
			'\Cake\ORM\Table',
			['save'],
			[['alias' => 'ArticlesTags', 'connection' => $connection]]
		);
		$config = [
			'sourceTable' => $this->article,
			'targetTable' => $this->tag,
			'through' => $joint,
			'joinTable' => 'tags_articles'
		];

		$assoc = new BelongsToMany('Test', $config);
		$opts = ['markNew' => false];
		$entity = new Entity(['id' => 1], $opts);
		$tags = [new Entity(['id' => 2], $opts), new Entity(['id' => 3], $opts)];
		$saveOptions = ['foo' => 'bar'];

		$joint->expects($this->at(0))
			->method('save')
			->will($this->returnCallback(function($e, $opts) use ($entity) {
				$expected = ['article_id' => 1, 'tag_id' => 2];
				$this->assertEquals($expected, $e->toArray());
				$this->assertEquals(['foo' => 'bar'], $opts);
				$this->assertTrue($e->isNew());
				return $entity;
			}));

		$joint->expects($this->at(1))
			->method('save')
			->will($this->returnCallback(function($e, $opts) use ($entity) {
				$expected = ['article_id' => 1, 'tag_id' => 3];
				$this->assertEquals($expected, $e->toArray());
				$this->assertEquals(['foo' => 'bar'], $opts);
				$this->assertTrue($e->isNew());
				return $entity;
			}));

		$this->assertTrue($assoc->link($entity, $tags, $saveOptions));
		$this->assertSame($entity->test, $tags);
	}

/**
 * Test liking entities having a non persited source entity
 *
 * @expectedException \InvalidArgumentException
 * @expectedExceptionMessage Source entity needs to be persisted before proceeding
 * @return void
 */
	public function testUnlinkWithNotPersistedSource() {
		$config = [
			'sourceTable' => $this->article,
			'targetTable' => $this->tag,
			'joinTable' => 'tags_articles'
		];
		$assoc = new BelongsToMany('Test', $config);
		$entity = new Entity(['id' => 1]);
		$tags = [new Entity(['id' => 2]), new Entity(['id' => 3])];
		$assoc->unlink($entity, $tags);
	}

/**
 * Test liking entities having a non persited target entity
 *
 * @expectedException \InvalidArgumentException
 * @expectedExceptionMessage Cannot link not persisted entities
 * @return void
 */
	public function testUnlinkWithNotPersistedTarget() {
		$config = [
			'sourceTable' => $this->article,
			'targetTable' => $this->tag,
			'joinTable' => 'tags_articles'
		];
		$assoc = new BelongsToMany('Test', $config);
		$entity = new Entity(['id' => 1], ['markNew' => false]);
		$tags = [new Entity(['id' => 2]), new Entity(['id' => 3])];
		$assoc->unlink($entity, $tags);
	}

/**
 * Tests that unlinking calls the right methods
 *
 * @return void
 */
	public function testUnlinkSuccess() {
		$connection = ConnectionManager::get('test');
		$joint = $this->getMock(
			'\Cake\ORM\Table',
			['delete', 'find'],
			[['alias' => 'ArticlesTags', 'connection' => $connection]]
		);
		$config = [
			'sourceTable' => $this->article,
			'targetTable' => $this->tag,
			'through' => $joint,
			'joinTable' => 'tags_articles'
		];
		$assoc = $this->article->belongsToMany('Test', $config);
		$assoc->junction();
		$this->article->association('ArticlesTags')
			->conditions(['foo' => 1]);

		$query1 = $this->getMock('\Cake\ORM\Query', [], [$connection, $joint]);
		$query2 = $this->getMock('\Cake\ORM\Query', [], [$connection, $joint]);

		$joint->expects($this->at(0))->method('find')
			->with('all')
			->will($this->returnValue($query1));

		$joint->expects($this->at(1))->method('find')
			->with('all')
			->will($this->returnValue($query2));

		$query1->expects($this->at(0))
			->method('where')
			->with(['foo' => 1])
			->will($this->returnSelf());
		$query1->expects($this->at(1))
			->method('where')
			->with(['article_id' => 1])
			->will($this->returnSelf());
		$query1->expects($this->at(2))
			->method('andWhere')
			->with(['tag_id' => 2])
			->will($this->returnSelf());
		$query1->expects($this->once())
			->method('union')
			->with($query2)
			->will($this->returnSelf());

		$query2->expects($this->at(0))
			->method('where')
			->with(['foo' => 1])
			->will($this->returnSelf());
		$query2->expects($this->at(1))
			->method('where')
			->with(['article_id' => 1])
			->will($this->returnSelf());
		$query2->expects($this->at(2))
			->method('andWhere')
			->with(['tag_id' => 3])
			->will($this->returnSelf());

		$jointEntities = [
			new Entity(['article_id' => 1, 'tag_id' => 2]),
			new Entity(['article_id' => 1, 'tag_id' => 3])
		];

		$query1->expects($this->once())
			->method('toArray')
			->will($this->returnValue($jointEntities));

		$opts = ['markNew' => false];
		$tags = [new Entity(['id' => 2], $opts), new Entity(['id' => 3], $opts)];
		$entity = new Entity(['id' => 1, 'test' => $tags], $opts);

		$joint->expects($this->at(2))
			->method('delete')
			->with($jointEntities[0]);

		$joint->expects($this->at(3))
			->method('delete')
			->with($jointEntities[1]);

		$assoc->unlink($entity, $tags);
		$this->assertEmpty($entity->get('test'));
	}

/**
 * Tests that unlinking with last parameter set to false
 * will not remove entities from the association property
 *
 * @return void
 */
	public function testUnlinkWithoutPropertyClean() {
		$connection = ConnectionManager::get('test');
		$joint = $this->getMock(
			'\Cake\ORM\Table',
			['delete', 'find'],
			[['alias' => 'ArticlesTags', 'connection' => $connection]]
		);
		$config = [
			'sourceTable' => $this->article,
			'targetTable' => $this->tag,
			'through' => $joint,
			'joinTable' => 'tags_articles'
		];
		$assoc = new BelongsToMany('Test', $config);
		$assoc
			->junction()
			->association('tags')
			->conditions(['foo' => 1]);

		$joint->expects($this->never())->method('find');
		$opts = ['markNew' => false];
		$jointEntities = [
			new Entity(['article_id' => 1, 'tag_id' => 2]),
			new Entity(['article_id' => 1, 'tag_id' => 3])
		];
		$tags = [
			new Entity(['id' => 2, '_joinData' => $jointEntities[0]], $opts),
			new Entity(['id' => 3, '_joinData' => $jointEntities[1]], $opts)
		];
		$entity = new Entity(['id' => 1, 'test' => $tags], $opts);

		$joint->expects($this->at(0))
			->method('delete')
			->with($jointEntities[0]);

		$joint->expects($this->at(1))
			->method('delete')
			->with($jointEntities[1]);

		$assoc->unlink($entity, $tags, false);
		$this->assertEquals($tags, $entity->get('test'));
	}

/**
 * Tests that replaceLink requires the sourceEntity to have primaryKey values
 * for the source entity
 *
 * @expectedException \InvalidArgumentException
 * @expectedExceptionMessage Could not find primary key value for source entity
 * @return void
 */
	public function testReplaceWithMissingPrimaryKey() {
		$config = [
			'sourceTable' => $this->article,
			'targetTable' => $this->tag,
			'joinTable' => 'tags_articles'
		];
		$assoc = new BelongsToMany('Test', $config);
		$entity = new Entity(['foo' => 1], ['markNew' => false]);
		$tags = [new Entity(['id' => 2]), new Entity(['id' => 3])];
		$assoc->replaceLinks($entity, $tags);
	}

/**
 * Tests that replaceLinks will delete entities not present in the passed
 * array, maintain those are already persisted and were passed and also
 * insert the rest.
 *
 * @return void
 */
	public function testReplaceLinkSuccess() {
		$connection = ConnectionManager::get('test');
		$joint = $this->getMock(
			'\Cake\ORM\Table',
			['delete', 'find'],
			[['alias' => 'ArticlesTags', 'connection' => $connection]]
		);
		$config = [
			'sourceTable' => $this->article,
			'targetTable' => $this->tag,
			'through' => $joint,
			'joinTable' => 'tags_articles'
		];
		$assoc = $this->getMock(
			'\Cake\ORM\Association\BelongsToMany',
			['_collectJointEntities', '_saveTarget'],
			['tags', $config]
		);
		$assoc->junction();

		$this->article
			->association('ArticlesTags')
			->conditions(['foo' => 1]);

		$query1 = $this->getMock(
			'\Cake\ORM\Query',
			['where', 'andWhere', 'addDefaultTypes'],
			[$connection, $joint]
		);

		$joint->expects($this->at(0))->method('find')
			->with('all')
			->will($this->returnValue($query1));

		$query1->expects($this->at(0))
			->method('where')
			->with(['foo' => 1])
			->will($this->returnSelf());
		$query1->expects($this->at(1))
			->method('where')
			->with(['article_id' => 1])
			->will($this->returnSelf());

		$existing = [
			new Entity(['article_id' => 1, 'tag_id' => 2]),
			new Entity(['article_id' => 1, 'tag_id' => 4]),
			new Entity(['article_id' => 1, 'tag_id' => 5]),
			new Entity(['article_id' => 1, 'tag_id' => 6])
		];
		$query1->setResult(new \ArrayIterator($existing));

		$opts = ['markNew' => false];
		$tags = [
			new Entity(['id' => 2], $opts),
			new Entity(['id' => 3], $opts),
			new Entity(['id' => 6])
		];
		$entity = new Entity(['id' => 1, 'test' => $tags], $opts);

		$jointEntities = [
			new Entity(['article_id' => 1, 'tag_id' => 2])
		];
		$assoc->expects($this->once())->method('_collectJointEntities')
			->with($entity, $tags)
			->will($this->returnValue($jointEntities));

		$joint->expects($this->at(1))
			->method('delete')
			->with($existing[1]);
		$joint->expects($this->at(2))
			->method('delete')
			->with($existing[2]);

		$options = ['foo' => 'bar'];
		$assoc->expects($this->once())
			->method('_saveTarget')
			->with($entity, [1 => $tags[1], 2 => $tags[2]], $options + ['associated' => false])
			->will($this->returnCallback(function($entity, $inserts) use ($tags) {
				$this->assertSame([1 => $tags[1], 2 => $tags[2]], $inserts);
				$entity->tags = $inserts;
				return true;
			}));

		$assoc->replaceLinks($entity, $tags, $options);
		$this->assertSame($tags, $entity->tags);
		$this->assertFalse($entity->dirty('tags'));
	}

/**
 * Tests saving with replace strategy returning true
 *
 * @return void
 */
	public function testSaveWithReplace() {
		$assoc = $this->getMock(
			'\Cake\ORM\Association\BelongsToMany',
			['replaceLinks'],
			['tags']
		);
		$entity = new Entity([
			'id' => 1,
			'tags' => [
				new Entity(['name' => 'foo'])
			]
		]);

		$options = ['foo' => 'bar'];
		$assoc->saveStrategy(BelongsToMany::SAVE_REPLACE);
		$assoc->expects($this->once())->method('replaceLinks')
			->with($entity, $entity->tags, $options)
			->will($this->returnValue(true));
		$this->assertSame($entity, $assoc->save($entity, $options));
	}

/**
 * Tests saving with replace strategy returning true
 *
 * @return void
 */
	public function testSaveWithReplaceReturnFalse() {
		$assoc = $this->getMock(
			'\Cake\ORM\Association\BelongsToMany',
			['replaceLinks'],
			['tags']
		);
		$entity = new Entity([
			'id' => 1,
			'tags' => [
				new Entity(['name' => 'foo'])
			]
		]);

		$options = ['foo' => 'bar'];
		$assoc->saveStrategy(BelongsToMany::SAVE_REPLACE);
		$assoc->expects($this->once())->method('replaceLinks')
			->with($entity, $entity->tags, $options)
			->will($this->returnValue(false));
		$this->assertFalse($assoc->save($entity, $options));
	}

/**
 * Test that save() ignores non entity values.
 *
 * @return void
 */
	public function testSaveOnlyEntities() {
		$connection = ConnectionManager::get('test');
		$mock = $this->getMock(
			'Cake\ORM\Table',
			['save', 'schema'],
			[['table' => 'tags', 'connection' => $connection]]
		);
		$mock->primaryKey('id');

		$config = [
			'sourceTable' => $this->article,
			'targetTable' => $mock,
			'saveStrategy' => BelongsToMany::SAVE_APPEND,
		];

		$entity = new Entity([
			'id' => 1,
			'title' => 'First Post',
			'tags' => [
				['tag' => 'nope'],
				new Entity(['tag' => 'cakephp']),
			]
		]);

		$mock->expects($this->never())
			->method('save');

		$association = new BelongsToMany('Tags', $config);
		$association->save($entity);
	}

/**
 * Tests that targetForeignKey() returns the correct configured value
 *
 * @return void
 */
	public function testTargetForeignKey() {
		$assoc = new BelongsToMany('Test', [
			'sourceTable' => $this->article,
			'targetTable' => $this->tag
		]);
		$this->assertEquals('tag_id', $assoc->targetForeignKey());
		$assoc->targetForeignKey('another_key');
		$this->assertEquals('another_key', $assoc->targetForeignKey());

		$assoc = new BelongsToMany('Test', [
			'sourceTable' => $this->article,
			'targetTable' => $this->tag,
			'targetForeignKey' => 'foo'
		]);
		$this->assertEquals('foo', $assoc->targetForeignKey());
	}

/**
 * Tests that custom foreignKeys are properly trasmitted to involved associations
 * when they are customized
 *
 * @return void
 */
	public function testJunctionWithCustomForeignKeys() {
		$assoc = new BelongsToMany('Test', [
			'sourceTable' => $this->article,
			'targetTable' => $this->tag,
			'foreignKey' => 'Art',
			'targetForeignKey' => 'Tag'
		]);
		$junction = $assoc->junction();
		$this->assertEquals('Art', $junction->association('Articles')->foreignKey());
		$this->assertEquals('Tag', $junction->association('Tags')->foreignKey());

		$inverseRelation = $this->tag->association('Articles');
		$this->assertEquals('Tag', $inverseRelation->foreignKey());
		$this->assertEquals('Art', $inverseRelation->targetForeignKey());
	}

/**
 * Tests that property is being set using the constructor options.
 *
 * @return void
 */
	public function testPropertyOption() {
		$config = ['propertyName' => 'thing_placeholder'];
		$association = new BelongsToMany('Thing', $config);
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
			'sourceTable' => $this->article,
			'targetTable' => $mock,
		];
		$association = new BelongsToMany('Contacts.Tags', $config);
		$this->assertEquals('tags', $association->property());
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
			'sourceTable' => $this->article,
			'targetTable' => $this->tag,
		];
		$table = TableRegistry::get('ArticlesTags');
		$association = new BelongsToMany('Tags', $config);
		$listener = $this->getMock('stdClass', ['__invoke']);
		$this->tag->getEventManager()->attach($listener, 'Model.beforeFind');
		$listener->expects($this->once())->method('__invoke')
			->with(
				$this->isInstanceOf('\Cake\Event\Event'),
				$this->isInstanceOf('\Cake\ORM\Query'),
				[],
				false
			);

		$listener2 = $this->getMock('stdClass', ['__invoke']);
		$table->getEventManager()->attach($listener2, 'Model.beforeFind');
		$listener2->expects($this->once())->method('__invoke')
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
			'sourceTable' => $this->article,
			'targetTable' => $this->tag,
		];
		$table = TableRegistry::get('ArticlesTags');
		$association = new BelongsToMany('Tags', $config);
		$listener = $this->getMock('stdClass', ['__invoke']);
		$this->tag->getEventManager()->attach($listener, 'Model.beforeFind');
		$opts = ['something' => 'more'];
		$listener->expects($this->once())->method('__invoke')
			->with(
				$this->isInstanceOf('\Cake\Event\Event'),
				$this->isInstanceOf('\Cake\ORM\Query'),
				$opts,
				false
			);

		$listener2 = $this->getMock('stdClass', ['__invoke']);
		$table->getEventManager()->attach($listener2, 'Model.beforeFind');
		$listener2->expects($this->once())->method('__invoke')
			->with(
				$this->isInstanceOf('\Cake\Event\Event'),
				$this->isInstanceOf('\Cake\ORM\Query'),
				[],
				false
			);

		$association->attachTo($query, ['queryBuilder' => function($q) {
			return $q->applyOptions(['something' => 'more']);
		}]);
	}

}
