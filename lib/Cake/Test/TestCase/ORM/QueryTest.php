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
use Cake\Database\Connection;
use Cake\ORM\Query;
use Cake\ORM\Table;

/**
 * Tests Query class
 *
 */
class QueryTest extends \Cake\TestSuite\TestCase {

	public $fixtures = ['core.article', 'core.author', 'core.tag',
		'core.articles_tag', 'core.post'];

	public function setUp() {
		parent::setUp();
		$this->connection = new Connection(Configure::read('Datasource.test'));
		$schema = ['id' => ['type' => 'integer']];
		$schema1 = [
			'id' => ['type' => 'integer'],
			'name' => ['type' => 'string'],
			'phone' => ['type' => 'string']
		];
		$schema2 = [
			'id' => ['type' => 'integer'],
			'total' => ['type' => 'string'],
			'placed' => ['type' => 'datetime']
		];

		$this->table = $table = Table::build('foo', ['schema' => $schema]);
		$clients = Table::build('client', ['schema' => $schema1]);
		$orders = Table::build('order', ['schema' => $schema2]);
		$companies = Table::build('company', ['schema' => $schema, 'table' => 'organizations']);
		$orderTypes = Table::build('orderType', ['schema' => $schema]);
		$stuff = Table::build('stuff', ['schema' => $schema, 'table' => 'things']);
		$stuffTypes = Table::build('stuffType', ['schema' => $schema]);
		$categories = Table::build('category', ['schema' => $schema]);

		$table->belongsTo('client');
		$clients->hasOne('order');
		$clients->belongsTo('company');
		$orders->belongsTo('orderType');
		$orders->hasOne('stuff');
		$stuff->belongsTo('stuffType');
		$companies->belongsTo('category');
	}

	public function tearDown() {
		parent::tearDown();
		Table::clearRegistry();
	}

/**
 * Test helper for creating tables.
 *
 * @return void
 */
	protected function _createTables() {
		Table::config('authors', ['connection' => $this->connection]);
		Table::config('articles', ['connection' => $this->connection]);
		Table::config('publications', ['connection' => $this->connection]);
		Table::config('tags', ['connection' => $this->connection]);
		Table::config('articles_tags', ['connection' => $this->connection]);
	}

/**
 * Tests that fully defined belongsTo and hasOne relationships are joined correctly
 *
 * @return void
 **/
	public function testContainToJoinsOneLevel() {
		$contains = [
			'client' => [
			'order' => [
					'orderType',
					'stuff' => ['stuffType']
				],
				'company' => [
					'foreignKey' => 'organization_id',
					'category'
				]
			]
		];

		$query = $this->getMock('\Cake\ORM\Query', ['join'], [$this->connection]);

		$query->expects($this->at(0))->method('join')
			->with(['client' => [
				'table' => 'clients',
				'type' => 'LEFT',
				'conditions' => ['client.id = foo.client_id']
			]])
			->will($this->returnValue($query));

		$query->expects($this->at(1))->method('join')
			->with(['order' => [
				'table' => 'orders',
				'type' => 'INNER',
				'conditions' => ['client.id = order.client_id']
			]])
			->will($this->returnValue($query));

		$query->expects($this->at(2))->method('join')
			->with(['orderType' => [
				'table' => 'order_types',
				'type' => 'LEFT',
				'conditions' => ['orderType.id = order.order_type_id']
			]])
			->will($this->returnValue($query));

		$query->expects($this->at(3))->method('join')
			->with(['stuff' => [
				'table' => 'things',
				'type' => 'INNER',
				'conditions' => ['order.id = stuff.order_id']
			]])
			->will($this->returnValue($query));


		$query->expects($this->at(4))->method('join')
			->with(['stuffType' => [
				'table' => 'stuff_types',
				'type' => 'LEFT',
				'conditions' => ['stuffType.id = stuff.stuff_type_id']
			]])
			->will($this->returnValue($query));

		$query->expects($this->at(5))->method('join')
			->with(['company' => [
				'table' => 'organizations',
				'type' => 'LEFT',
				'conditions' => ['company.id = client.organization_id']
			]])
			->will($this->returnValue($query));

		$query->expects($this->at(6))->method('join')
			->with(['category' => [
				'table' => 'categories',
				'type' => 'LEFT',
				'conditions' => ['category.id = company.category_id']
			]])
			->will($this->returnValue($query));

		$s = $query
			->select('foo.id')
			->repository($this->table)
			->contain($contains)->sql();
	}

/**
 * Test that fields for contained models are aliased and added to the select clause
 *
 * @return void
 **/
	public function testContainToFieldsPredefined() {
		$contains = [
			'client' => [
				'fields' => ['name', 'company_id', 'client.telephone'],
				'order' => [
					'fields' => ['total', 'placed']
				]
			]
		];

		$table = Table::build('foo', ['schema' => ['id' => ['type' => 'integer']]]);
		$query = new Query($this->connection);

		$query->select('foo.id')->repository($table)->contain($contains)->sql();
		$select = $query->clause('select');
		$expected = [
			'foo__id' => 'foo.id', 'client__name' => 'client.name',
			'client__company_id' => 'client.company_id',
			'client__telephone' => 'client.telephone',
			'order__total' => 'order.total', 'order__placed' => 'order.placed'
		];
		$this->assertEquals($expected, $select);
	}

/**
 * Tests that default fields for associations are added to the select clause when
 * none is specified
 *
 * @return void
 **/
	public function testContainToFieldsDefault() {
		$contains = ['client' => ['order']];

		$query = new Query($this->connection);
		$query->select()->repository($this->table)->contain($contains)->sql();
		$select = $query->clause('select');
		$expected = [
			'foo__id' => 'foo.id', 'client__name' => 'client.name',
			'client__id' => 'client.id', 'client__phone' => 'client.phone',
			'order__id' => 'order.id', 'order__total' => 'order.total',
			'order__placed' => 'order.placed'
		];
		$this->assertEquals($expected, $select);

		$contains['client']['fields'] = ['name'];
		$query = new Query($this->connection);
		$query->select('foo.id')->repository($this->table)->contain($contains)->sql();
		$select = $query->clause('select');
		$expected = ['foo__id' => 'foo.id', 'client__name' => 'client.name'];
		$this->assertEquals($expected, $select);

		$contains['client']['fields'] = [];
		$contains['client']['order']['fields'] = false;
		$query = new Query($this->connection);
		$query->select()->repository($this->table)->contain($contains)->sql();
		$select = $query->clause('select');
		$expected = [
			'foo__id' => 'foo.id',
			'client__id' => 'client.id',
			'client__name' => 'client.name',
			'client__phone' => 'client.phone',
		];
		$this->assertEquals($expected, $select);
	}

/**
 * Tests that results are grouped correctly when using contain()
 *
 * @return void
 **/
	public function testContainResultFetchingOneLevel() {
		$this->_createTables();

		$query = new Query($this->connection);
		$table = Table::build('article', ['table' => 'articles']);
		Table::build('author', ['connection' => $this->connection]);
		$table->belongsTo('author');
		$results = $query->repository($table)->select()->contain('author')->toArray();
		$expected = [
			[
				'id' => 1,
				'title' => 'a title',
				'body' => 'a body',
				'author_id' => 1,
				'author' => [
					'id' => 1,
					'name' => 'Chuck Norris'
				]
			],
			[
				'id' => 2,
				'title' => 'another title',
				'body' => 'another body',
				'author_id' => 2,
				'author' => [
					'id' => 2,
					'name' => 'Bruce Lee'
				]
			]
		];
		$this->assertSame($expected, $results);
	}

/**
 * Data provider for the two types of strategies HasMany implements
 *
 * @return void
 */
	public function strategiesProvider() {
		return [['subquery'], ['select']];
	}

/**
 * Tests that HasMany associations are correctly eager loaded.
 * Also that the query object passes the correct parent model keys to the
 * association objects in order to perform eager loading with select strategy
 *
 * @dataProvider strategiesProvider
 * @return void
 **/
	public function testHasManyEagerLoading($strategy) {
		$this->_createTables();

		$query = new Query($this->connection);
		$table = Table::build('author', ['connection' => $this->connection]);
		Table::build('article', ['connection' => $this->connection]);
		$table->hasMany('article', ['property' => 'articles', 'strategy' => $strategy]);

		$results = $query->repository($table)->select()->contain('article')->toArray();
		$expected = [
			[
				'id' => 1,
				'name' => 'Chuck Norris',
				'articles' => [
					[
						'id' => 1,
						'title' => 'a title',
						'body' => 'a body',
						'author_id' => 1
					]
				]
			],
			[
				'id' => 2,
				'name' => 'Bruce Lee',
				'articles' => [
					[
						'id' => 2,
						'title' => 'another title',
						'body' => 'another body',
						'author_id' => 2
					]
				]
			]
		];
		$this->assertEquals($expected, $results);

		$results = $query->repository($table)
			->select()
			->contain(['article' => ['conditions' => ['id' => 2]]])
			->toArray();
		unset($expected[0]['articles']);
		$this->assertEquals($expected, $results);
		$this->assertEquals($table->association('article')->strategy(), $strategy);
	}

/**
 * Tests that it is possible to select only certain fields on
 * eagerly loaded has many associations
 *
 * @dataProvider strategiesProvider
 * @return void
 **/
	public function testHasManyEagerLoadingFields($strategy) {
		$this->_createTables();

		$query = new Query($this->connection);
		$table = Table::build('author', ['connection' => $this->connection]);
		Table::build('article', ['connection' => $this->connection]);
		$table->hasMany('article', ['property' => 'articles'] + compact('strategy'));

		$results = $query->repository($table)
			->select()
			->contain(['article' => ['fields' => ['title', 'author_id']]])
			->toArray();
		$expected = [
			[
				'id' => 1,
				'name' => 'Chuck Norris',
				'articles' => [
					['title' => 'a title', 'author_id' => 1]
				]
			],
			[
				'id' => 2,
				'name' => 'Bruce Lee',
				'articles' => [
					['title' => 'another title', 'author_id' => 2]
				]
			]
		];
		$this->assertEquals($expected, $results);
	}

/**
 * Tests that it is possible to set an order in a hasMany result set
 *
 * @dataProvider strategiesProvider
 * @return void
 **/
	public function testHasManyEagerLoadingOrder($strategy) {
		$statement = $this->_createTables();
		$statement->bindValue(1, 3, 'integer');
		$statement->bindValue(2, 'a fine title');
		$statement->bindValue(3, 'a fine body');
		$statement->bindValue(4, 2);
		$statement->execute();

		$query = new Query($this->connection);
		$table = Table::build('author', ['connection' => $this->connection]);
		Table::build('article', ['connection' => $this->connection]);
		$table->hasMany('article', ['property' => 'articles'] + compact('strategy'));

		$results = $query->repository($table)
			->select()
			->contain([
				'article' => [
					'fields' => ['title', 'author_id'],
					'sort' => ['id' => 'DESC']
				]
			])
			->toArray();
		$expected = [
			[
				'id' => 1,
				'name' => 'Chuck Norris',
				'articles' => [
					['title' => 'a title', 'author_id' => 1]
				]
			],
			[
				'id' => 2,
				'name' => 'Bruce Lee',
				'articles' => [
					['title' => 'a fine title', 'author_id' => 2],
					['title' => 'another title', 'author_id' => 2],
				]
			]
		];
		$this->assertEquals($expected, $results);
	}

/**
 * Tests that deep associations can be eagerly loaded
 *
 * @dataProvider strategiesProvider
 * @return void
 **/
	public function testHasManyEagerLoadingDeep($strategy) {
		$this->_createTables();

		$query = new Query($this->connection);
		$table = Table::build('author', ['connection' => $this->connection]);
		$article = Table::build('article', ['connection' => $this->connection]);
		$table->hasMany('article', ['property' => 'articles'] + compact('strategy'));
		$article->belongsTo('author');

		$results = $query->repository($table)
			->select()
			->contain(['article' => ['author']])
			->toArray();
		$expected = [
			[
				'id' => 1,
				'name' => 'Chuck Norris',
				'articles' => [
					[
						'id' => 1, 'title' => 'a title', 'author_id' => 1, 'body' => 'a body',
						'author' => ['id' => 1 , 'name' => 'Chuck Norris']
					]
				]
			],
			[
				'id' => 2,
				'name' => 'Bruce Lee',
				'articles' => [
					[
						'id' => 2, 'title' => 'another title',
						'author_id' => 2,
						'body' => 'another body',
						'author' => ['id' => 2 , 'name' => 'Bruce Lee']
					]
				]
			]
		];
		$this->assertEquals($expected, $results);
	}

/**
 * Tests that hasMany associations can be loaded even when related to a secondary
 * model in the query
 *
 * @dataProvider strategiesProvider
 * @return void
 **/
	public function testHasManyEagerLoadingFromSecondaryTable($strategy) {
		$this->_createTables();

		$query = new Query($this->connection);
		$author = Table::build('author', ['connection' => $this->connection]);
		$article = Table::build('article', ['connection' => $this->connection]);
		$post = Table::build('post', ['connection' => $this->connection]);

		$author->hasMany('post', ['property' => 'posts'] + compact('strategy'));
		$article->belongsTo('author');

		$results = $query->repository($article)
			->select()
			->contain(['author' => ['post']])
			->toArray();
		$expected = [
			[
				'id' => 1,
				'title' => 'First Article',
				'body' => 'First Article Body',
				'author_id' => 1,
				'published' => 'Y',
				'author' => [
					'id' => 1,
					'name' => 'mariano',
					'posts' => [
						[
							'id' => '1',
							'title' => 'First Post',
							'body' => 'First Post Body',
							'author_id' => 1,
							'published' => 'Y',
						],
						[
							'id' => '3',
							'title' => 'Third Post',
							'body' => 'Third Post Body',
							'author_id' => 1,
							'published' => 'Y',
						],
					]
				]
			],
			[
				'id' => 2,
				'title' => 'Second Article',
				'body' => 'Second Article Body',
				'author_id' => 3,
				'published' => 'Y',
				'author' => [
					'id' => 3,
					'name' => 'larry',
					'posts' => [
						[
							'id' => 2,
							'title' => 'Second Post',
							'body' => 'Second Post Body',
							'author_id' => 3,
							'published' => 'Y',
						]
					]
				]
			],
			[
				'id' => 3,
				'title' => 'Third Article',
				'body' => 'Third Article Body',
				'author_id' => 1,
				'published' => 'Y',
				'author' => [
					'id' => 1,
					'name' => 'mariano',
					'posts' => [
						[
							'id' => '1',
							'title' => 'First Post',
							'body' => 'First Post Body',
							'author_id' => 1,
							'published' => 'Y',
						],
						[
							'id' => '3',
							'title' => 'Third Post',
							'body' => 'Third Post Body',
							'author_id' => 1,
							'published' => 'Y',
						],
					]
				]
			],
		];
		$this->assertEquals($expected, $results);
	}

/**
 * Tests that BelongsToMany associations are correctly eager loaded.
 * Also that the query object passes the correct parent model keys to the
 * association objects in order to perform eager loading with select strategy
 *
 * @dataProvider strategiesProvider
 * @return void
 **/
	public function testBelongsToManyEagerLoading($strategy) {
		$this->_createTables();

		$query = new Query($this->connection);
		$table =  Table::build('Article', ['connection' => $this->connection]);
		Table::build('Tag', ['connection' => $this->connection]);
		Table::build('ArticleTag', [
			'connection' => $this->connection,
			'table' => 'articles_tags'
		]);
		$table->belongsToMany('Tag', ['property' => 'tags', 'strategy' => $strategy]);

		$results = $query->repository($table)->select()->contain('Tag')->toArray();
		$expected = [
			[
				'id' => 1,
				'title' => 'a title',
				'body' => 'a body',
				'author_id' => 1,
				'tags' => [
					[
						'id' => 5,
						'name' => 'one',
						'ArticleTag' => ['article_id' => 1, 'tag_id' => 5]
					]
				]
			],
			[
				'id' => 2,
				'title' => 'another title',
				'body' => 'another body',
				'author_id' => 2,
				'tags' => [
					[
						'id' => 6,
						'name' => 'two',
						'ArticleTag' => ['article_id' => 2, 'tag_id' => 6]
					]
				]
			]
		];
		$this->assertEquals($expected, $results);

		$results = $query->repository($table)
			->select()
			->contain(['Tag' => ['conditions' => ['id' => 6]]])
			->toArray();
		unset($expected[0]['tags']);
		$this->assertEquals($expected, $results);
		$this->assertEquals($table->association('Tag')->strategy(), $strategy);
	}

/**
 * Tests that tables results can be filtered by the result of a HasMany
 *
 * @return void
 */
	public function testFilteringByHasMany() {
		$this->_createTables();

		$query = new Query($this->connection);
		$table = Table::build('author', ['connection' => $this->connection]);
		Table::build('article', ['connection' => $this->connection]);
		$table->hasMany('article', ['property' => 'articles']);

		$results = $query->repository($table)
			->select()
			->contain(['article' => [
				'matching' => true,
				'conditions' => ['Article.id' => 2]
			]])
			->toArray();
		$expected = [
			[
				'id' => 2,
				'name' => 'Bruce Lee',
				'articles' => [
					'id' => 2,
					'title' => 'another title',
					'body' => 'another body',
					'author_id' => 2
				]
			]
		];
		$this->assertEquals($expected, $results);
	}

/**
 * Tests that BelongsToMany associations are correctly eager loaded.
 * Also that the query object passes the correct parent model keys to the
 * association objects in order to perform eager loading with select strategy
 *
 * @return void
 **/
	public function testFilteringByBelongsToMany() {
		$this->_createTables();

		$query = new Query($this->connection);
		$table =  Table::build('Article', ['connection' => $this->connection]);
		Table::build('Tag', ['connection' => $this->connection]);
		Table::build('ArticleTag', [
			'connection' => $this->connection,
			'table' => 'articles_tags'
		]);
		$table->belongsToMany('Tag', ['property' => 'tags']);

		$results = $query->repository($table)->select()
			->contain(['Tag' => [
				'matching' => true,
				'conditions' => ['Tag.id' => 5]
			]])
			->toArray();
		$expected = [
			[
				'id' => 1,
				'title' => 'a title',
				'body' => 'a body',
				'author_id' => 1,
				'tags' => [
					'id' => 5,
					'name' => 'one'
				]
			]
		];
		$this->assertEquals($expected, $results);

		$query = new Query($this->connection);
		$results = $query->repository($table)
			->select()
			->contain(['Tag' => [
				'matching' => true,
				'conditions' => ['Tag.name' => 'two']]
			])
			->toArray();
		$expected = [
			[
				'id' => 2,
				'title' => 'another title',
				'body' => 'another body',
				'author_id' => 2,
				'tags' => [
					'id' => 6,
					'name' => 'two'
				]
			]
		];
		$this->assertEquals($expected, $results);
	}

}
