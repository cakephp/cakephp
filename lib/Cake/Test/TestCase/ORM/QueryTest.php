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
use Cake\Model\ConnectionManager;
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
		$this->connection = ConnectionManager::getDataSource('test');
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
		$results = $query->repository($table)
			->select()
			->contain('author')
			->order(['article.id' => 'asc'])
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
					'name' => 'mariano'
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
					'name' => 'larry'
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
					'name' => 'mariano'
				]
			],
		];
		$this->assertEquals($expected, $results);
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
		$table->hasMany('article', [
			'property' => 'articles',
			'strategy' => $strategy,
			'sort' => ['article.id' => 'asc']
		]);

		$results = $query->repository($table)->select()->contain('article')->toArray();
		$expected = [
			[
				'id' => 1,
				'name' => 'mariano',
				'articles' => [
					[
						'id' => 1,
						'title' => 'First Article',
						'body' => 'First Article Body',
						'author_id' => 1,
						'published' => 'Y',
					],
					[
						'id' => 3,
						'title' => 'Third Article',
						'body' => 'Third Article Body',
						'author_id' => 1,
						'published' => 'Y',
					],
				]
			],
			[
				'id' => 2,
				'name' => 'nate',
			],
			[
				'id' => 3,
				'name' => 'larry',
				'articles' => [
					[
						'id' => 2,
						'title' => 'Second Article',
						'body' => 'Second Article Body',
						'author_id' => 3,
						'published' => 'Y'
					]
				]
			],
			[
				'id' => 4,
				'name' => 'garrett',
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
 * Tests that it is possible to set fields & order in a hasMany result set
 *
 * @dataProvider strategiesProvider
 * @return void
 **/
	public function testHasManyEagerLoadingFieldsAndOrder($strategy) {
		$this->_createTables();

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
				'name' => 'mariano',
				'articles' => [
					['title' => 'Third Article', 'author_id' => 1],
					['title' => 'First Article', 'author_id' => 1],
				]
			],
			[
				'id' => 2,
				'name' => 'nate',
			],
			[
				'id' => 3,
				'name' => 'larry',
				'articles' => [
					['title' => 'Second Article', 'author_id' => 3],
				]
			],
			[
				'id' => 4,
				'name' => 'garrett',
			],
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
		$table->hasMany('article', [
			'property' => 'articles',
			'stratgey' => $strategy,
			'sort' => ['article.id' => 'asc']
		]);
		$article->belongsTo('author');

		$results = $query->repository($table)
			->select()
			->contain(['article' => ['author']])
			->toArray();
		$expected = [
			[
				'id' => 1,
				'name' => 'mariano',
				'articles' => [
					[
						'id' => 1,
						'title' => 'First Article',
						'author_id' => 1,
						'body' => 'First Article Body',
						'published' => 'Y',
						'author' => ['id' => 1 , 'name' => 'mariano']
					],
					[
						'id' => 3,
						'title' => 'Third Article',
						'author_id' => 1,
						'body' => 'Third Article Body',
						'published' => 'Y',
						'author' => ['id' => 1, 'name' => 'mariano']
					],
				]
			],
			[
				'id' => 2,
				'name' => 'nate',
				'articles' => [
					'author' => ['id' => 2, 'name' => 'nate']
				]
			],
			[
				'id' => 3,
				'name' => 'larry',
				'articles' => [
					[
						'id' => 2,
						'title' => 'Second Article',
						'author_id' => 3,
						'body' => 'Second Article Body',
						'published' => 'Y',
						'author' => ['id' => 3, 'name' => 'larry']
					],
				]
			],
			[
				'id' => 4,
				'name' => 'garrett',
				'articles' => [
					'author' => ['id' => 4, 'name' => 'garrett']
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
			->order(['article.id' => 'ASC'])
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
		$table = Table::build('Article', ['connection' => $this->connection]);
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
				'author_id' => 1,
				'title' => 'First Article',
				'body' => 'First Article Body',
				'published' => 'Y',
				'tags' => [
					[
						'id' => 1,
						'name' => 'tag1',
						'ArticleTag' => ['article_id' => 1, 'tag_id' => 1]
					],
					[
						'id' => 2,
						'name' => 'tag2',
						'ArticleTag' => ['article_id' => 1, 'tag_id' => 2]
					]
				]
			],
			[
				'id' => 2,
				'title' => 'Second Article',
				'body' => 'Second Article Body',
				'author_id' => 3,
				'published' => 'Y',
				'tags' => [
					[
						'id' => 1,
						'name' => 'tag1',
						'ArticleTag' => ['article_id' => 2, 'tag_id' => 1]
					],
					[
						'id' => 3,
						'name' => 'tag3',
						'ArticleTag' => ['article_id' => 2, 'tag_id' => 3]
					]
				]
			],
			[
				'id' => 3,
				'title' => 'Third Article',
				'body' => 'Third Article Body',
				'author_id' => 1,
				'published' => 'Y',
			],
		];
		$this->assertEquals($expected, $results);

		$results = $query->repository($table)
			->select()
			->contain(['Tag' => ['conditions' => ['id' => 3]]])
			->toArray();
		$expected = [
			[
				'id' => 1,
				'author_id' => 1,
				'title' => 'First Article',
				'body' => 'First Article Body',
				'published' => 'Y',
			],
			[
				'id' => 2,
				'title' => 'Second Article',
				'body' => 'Second Article Body',
				'author_id' => 3,
				'published' => 'Y',
				'tags' => [
					[
						'id' => 3,
						'name' => 'tag3',
						'ArticleTag' => ['article_id' => 2, 'tag_id' => 3]
					]
				]
			],
			[
				'id' => 3,
				'title' => 'Third Article',
				'body' => 'Third Article Body',
				'author_id' => 1,
				'published' => 'Y',
			],
		];
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
				'conditions' => ['article.id' => 2]
			]])
			->toArray();
		$expected = [
			[
				'id' => 3,
				'name' => 'larry',
				'articles' => [
					'id' => 2,
					'title' => 'Second Article',
					'body' => 'Second Article Body',
					'author_id' => 3,
					'published' => 'Y',
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
		$table = Table::build('Article', ['connection' => $this->connection]);
		Table::build('Tag', ['connection' => $this->connection]);
		Table::build('ArticleTag', [
			'connection' => $this->connection,
			'table' => 'articles_tags'
		]);
		$table->belongsToMany('Tag', ['property' => 'tags']);

		$results = $query->repository($table)->select()
			->contain(['Tag' => [
				'matching' => true,
				'conditions' => ['Tag.id' => 3]
			]])
			->toArray();
		$expected = [
			[
				'id' => 2,
				'author_id' => 3,
				'title' => 'Second Article',
				'body' => 'Second Article Body',
				'published' => 'Y',
				'tags' => [
					'id' => 3,
					'name' => 'tag3'
				]
			]
		];
		$this->assertEquals($expected, $results);

		$query = new Query($this->connection);
		$results = $query->repository($table)
			->select()
			->contain(['Tag' => [
				'matching' => true,
				'conditions' => ['Tag.name' => 'tag2']]
			])
			->toArray();
		$expected = [
			[
				'id' => 1,
				'title' => 'First Article',
				'body' => 'First Article Body',
				'author_id' => 1,
				'published' => 'Y',
				'tags' => [
					'id' => 2,
					'name' => 'tag2'
				]
			]
		];
		$this->assertEquals($expected, $results);
	}

}
