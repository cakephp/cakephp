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
 * @since         CakePHP(tm) v 3.0.0
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
namespace Cake\Test\TestCase\ORM;

use Cake\Core\Configure;
use Cake\Database\ConnectionManager;
use Cake\Database\Expression\IdentifierExpression;
use Cake\Database\Expression\OrderByExpression;
use Cake\Database\Expression\QueryExpression;
use Cake\ORM\Query;
use Cake\ORM\ResultSet;
use Cake\ORM\Table;
use Cake\ORM\TableRegistry;
use Cake\TestSuite\TestCase;

/**
 * Tests Query class
 *
 */
class QueryTest extends TestCase {

/**
 * Fixture to be used
 *
 * @var array
 */
	public $fixtures = ['core.article', 'core.author', 'core.tag',
		'core.articles_tag', 'core.post'];

/**
 * setUp method
 *
 * @return void
 */
	public function setUp() {
		parent::setUp();
		$this->connection = ConnectionManager::get('test');
		$schema = [
			'id' => ['type' => 'integer'],
			'_constraints' => [
				'primary' => ['type' => 'primary', 'columns' => ['id']]
			]
		];
		$schema1 = [
			'id' => ['type' => 'integer'],
			'name' => ['type' => 'string'],
			'phone' => ['type' => 'string'],
			'_constraints' => [
				'primary' => ['type' => 'primary', 'columns' => ['id']]
			]
		];
		$schema2 = [
			'id' => ['type' => 'integer'],
			'total' => ['type' => 'string'],
			'placed' => ['type' => 'datetime'],
			'_constraints' => [
				'primary' => ['type' => 'primary', 'columns' => ['id']]
			]
		];

		$this->table = $table = TableRegistry::get('foo', ['schema' => $schema]);
		$clients = TableRegistry::get('clients', ['schema' => $schema1]);
		$orders = TableRegistry::get('orders', ['schema' => $schema2]);
		$companies = TableRegistry::get('companies', ['schema' => $schema, 'table' => 'organizations']);
		$orderTypes = TableRegistry::get('orderTypes', ['schema' => $schema]);
		$stuff = TableRegistry::get('stuff', ['schema' => $schema, 'table' => 'things']);
		$stuffTypes = TableRegistry::get('stuffTypes', ['schema' => $schema]);
		$categories = TableRegistry::get('categories', ['schema' => $schema]);

		$table->belongsTo('clients');
		$clients->hasOne('orders');
		$clients->belongsTo('companies');
		$orders->belongsTo('orderTypes');
		$orders->hasOne('stuff');
		$stuff->belongsTo('stuffTypes');
		$companies->belongsTo('categories');
	}

/**
 * tearDown method
 *
 * @return void
 */
	public function tearDown() {
		parent::tearDown();
		TableRegistry::clear();
	}

/**
 * Tests that fully defined belongsTo and hasOne relationships are joined correctly
 *
 * @return void
 **/
	public function testContainToJoinsOneLevel() {
		$contains = [
			'clients' => [
			'orders' => [
					'orderTypes',
					'stuff' => ['stuffTypes']
				],
				'companies' => [
					'foreignKey' => 'organization_id',
					'categories'
				]
			]
		];

		$query = $this->getMock('\Cake\ORM\Query', ['join'], [$this->connection, $this->table]);

		$query->expects($this->at(0))->method('join')
			->with(['clients' => [
				'table' => 'clients',
				'type' => 'LEFT',
				'conditions' => new QueryExpression([
					['clients.id' => new IdentifierExpression('foo.client_id')]
				])
			]])
			->will($this->returnValue($query));

		$query->expects($this->at(1))->method('join')
			->with(['orders' => [
				'table' => 'orders',
				'type' => 'INNER',
				'conditions' => new QueryExpression([
					['clients.id' => new IdentifierExpression('orders.client_id')]
				])
			]])
			->will($this->returnValue($query));

		$query->expects($this->at(2))->method('join')
			->with(['orderTypes' => [
				'table' => 'order_types',
				'type' => 'LEFT',
				'conditions' => new QueryExpression([
					['orderTypes.id' => new IdentifierExpression('orders.order_type_id')]
				])
			]])
			->will($this->returnValue($query));

		$query->expects($this->at(3))->method('join')
			->with(['stuff' => [
				'table' => 'things',
				'type' => 'INNER',
				'conditions' => new QueryExpression([
					['orders.id' => new IdentifierExpression('stuff.order_id')]
				])
			]])
			->will($this->returnValue($query));

		$query->expects($this->at(4))->method('join')
			->with(['stuffTypes' => [
				'table' => 'stuff_types',
				'type' => 'LEFT',
				'conditions' => new QueryExpression([
					['stuffTypes.id' => new IdentifierExpression('stuff.stuff_type_id')]
				])
			]])
			->will($this->returnValue($query));

		$query->expects($this->at(5))->method('join')
			->with(['companies' => [
				'table' => 'organizations',
				'type' => 'LEFT',
				'conditions' => new QueryExpression([
					['companies.id' => new IdentifierExpression('clients.organization_id')]
				])
			]])
			->will($this->returnValue($query));

		$query->expects($this->at(6))->method('join')
			->with(['categories' => [
				'table' => 'categories',
				'type' => 'LEFT',
				'conditions' => new QueryExpression([
					['categories.id' => new IdentifierExpression('companies.category_id')]
				])
			]])
			->will($this->returnValue($query));

		$s = $query
			->select('foo.id')
			->contain($contains)->sql();
	}

/**
 * Tests setting containments using dot notation, additionally proves that options
 * are not overwritten when combining dot notation and array notation
 *
 * @return void
 */
	public function testContainDotNotation() {
		$query = $this->getMock('\Cake\ORM\Query', ['join'], [$this->connection, $this->table]);
		$query->contain([
			'clients.orders.stuff',
			'clients.companies.categories' => ['conditions' => ['a >' => 1]]
		]);
		$expected = new \ArrayObject([
			'clients' => [
				'orders' => [
					'stuff' => []
				],
				'companies' => [
					'categories' => [
						'conditions' => ['a >' => 1]
					]
				]
			]
		]);
		$this->assertEquals($expected, $query->contain());
		$query->contain([
			'clients.orders' => ['fields' => ['a', 'b']],
			'clients' => ['sort' => ['a' => 'desc']],
		]);

		$expected['clients']['orders'] += ['fields' => ['a', 'b']];
		$expected['clients'] += ['sort' => ['a' => 'desc']];
		$this->assertEquals($expected, $query->contain());
	}

/**
 * Tests that it is possible to pass a function as the array value for contain
 *
 * @return void
 */
	public function testContainClosure() {
		$builder = function($query) {
		};
		$query = $this->getMock('\Cake\ORM\Query', ['join'], [$this->connection, $this->table]);
		$query->contain([
			'clients.orders.stuff' => ['fields' => ['a']],
			'clients' => $builder
		]);

		$expected = new \ArrayObject([
			'clients' => [
				'orders' => [
					'stuff' => ['fields' => ['a']]
				],
				'queryBuilder' => $builder
			]
		]);
		$this->assertEquals($expected, $query->contain());

		$query = $this->getMock('\Cake\ORM\Query', ['join'], [$this->connection, $this->table]);
		$query->contain([
			'clients.orders.stuff' => ['fields' => ['a']],
			'clients' => ['queryBuilder' => $builder]
		]);
		$this->assertEquals($expected, $query->contain());
	}

/**
 * Test that fields for contained models are aliased and added to the select clause
 *
 * @return void
 **/
	public function testContainToFieldsPredefined() {
		$contains = [
			'clients' => [
				'fields' => ['name', 'company_id', 'clients.telephone'],
				'orders' => [
					'fields' => ['total', 'placed']
				]
			]
		];

		$table = TableRegistry::get('foo');
		$query = new Query($this->connection, $table);

		$query->select('foo.id')->contain($contains)->sql();
		$select = $query->clause('select');
		$expected = [
			'foo__id' => 'foo.id', 'clients__name' => 'clients.name',
			'clients__company_id' => 'clients.company_id',
			'clients__telephone' => 'clients.telephone',
			'orders__total' => 'orders.total', 'orders__placed' => 'orders.placed'
		];
		$expected = $this->_quoteArray($expected);
		$this->assertEquals($expected, $select);
	}

/**
 * Tests that default fields for associations are added to the select clause when
 * none is specified
 *
 * @return void
 **/
	public function testContainToFieldsDefault() {
		$contains = ['clients' => ['orders']];

		$query = new Query($this->connection, $this->table);
		$query->select()->contain($contains)->sql();
		$select = $query->clause('select');
		$expected = [
			'foo__id' => 'foo.id', 'clients__name' => 'clients.name',
			'clients__id' => 'clients.id', 'clients__phone' => 'clients.phone',
			'orders__id' => 'orders.id', 'orders__total' => 'orders.total',
			'orders__placed' => 'orders.placed'
		];
		$expected = $this->_quoteArray($expected);
		$this->assertEquals($expected, $select);

		$contains['clients']['fields'] = ['name'];
		$query = new Query($this->connection, $this->table);
		$query->select('foo.id')->contain($contains)->sql();
		$select = $query->clause('select');
		$expected = ['foo__id' => 'foo.id', 'clients__name' => 'clients.name'];
		$expected = $this->_quoteArray($expected);
		$this->assertEquals($expected, $select);

		$contains['clients']['fields'] = [];
		$contains['clients']['orders']['fields'] = false;
		$query = new Query($this->connection, $this->table);
		$query->select()->contain($contains)->sql();
		$select = $query->clause('select');
		$expected = [
			'foo__id' => 'foo.id',
			'clients__id' => 'clients.id',
			'clients__name' => 'clients.name',
			'clients__phone' => 'clients.phone',
		];
		$expected = $this->_quoteArray($expected);
		$this->assertEquals($expected, $select);
	}

/**
 * Helper function sued to quoted both keys and values in an array in case
 * the test suite is running with auto quoting enabled
 *
 * @param array $elements
 * @return array
 */
	protected function _quoteArray($elements) {
		if ($this->connection->driver()->autoQuoting()) {
			$quoter = function($e) {
				return $this->connection->driver()->quoteIdentifier($e);
			};
			return array_combine(
				array_map($quoter, array_keys($elements)),
				array_map($quoter, array_values($elements))
			);
		}
		return $elements;
	}

/**
 * Tests that results are grouped correctly when using contain()
 * and results are not hydrated
 *
 * @return void
 */
	public function testContainResultFetchingOneLevel() {
		$table = TableRegistry::get('articles', ['table' => 'articles']);
		$table->belongsTo('authors');

		$query = new Query($this->connection, $table);
		$results = $query->select()
			->contain('authors')
			->hydrate(false)
			->order(['articles.id' => 'asc'])
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
 * Tests that HasMany associations are correctly eager loaded and results
 * correctly nested when no hydration is used
 * Also that the query object passes the correct parent model keys to the
 * association objects in order to perform eager loading with select strategy
 *
 * @dataProvider strategiesProvider
 * @return void
 */
	public function testHasManyEagerLoadingNoHydration($strategy) {
		$table = TableRegistry::get('authors');
		TableRegistry::get('articles');
		$table->hasMany('articles', [
			'property' => 'articles',
			'strategy' => $strategy,
			'sort' => ['articles.id' => 'asc']
		]);
		$query = new Query($this->connection, $table);

		$results = $query->select()
			->contain('articles')
			->hydrate(false)
			->toArray();
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
			->contain(['articles' => ['conditions' => ['id' => 2]]])
			->hydrate(false)
			->toArray();
		unset($expected[0]['articles']);
		$this->assertEquals($expected, $results);
		$this->assertEquals($table->association('articles')->strategy(), $strategy);
	}

/**
 * Tests that it is possible to set fields & order in a hasMany result set
 *
 * @dataProvider strategiesProvider
 * @return void
 **/
	public function testHasManyEagerLoadingFieldsAndOrderNoHydration($strategy) {
		$table = TableRegistry::get('authors');
		TableRegistry::get('articles');
		$table->hasMany('articles', ['property' => 'articles'] + compact('strategy'));

		$query = new Query($this->connection, $table);
		$results = $query->select()
			->contain([
				'articles' => [
					'fields' => ['title', 'author_id'],
					'sort' => ['id' => 'DESC']
				]
			])
			->hydrate(false)
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
 */
	public function testHasManyEagerLoadingDeep($strategy) {
		$table = TableRegistry::get('authors');
		$article = TableRegistry::get('articles');
		$table->hasMany('articles', [
			'property' => 'articles',
			'stratgey' => $strategy,
			'sort' => ['articles.id' => 'asc']
		]);
		$article->belongsTo('authors');
		$query = new Query($this->connection, $table);

		$results = $query->select()
			->contain(['articles' => ['authors']])
			->hydrate(false)
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
						'author' => ['id' => 1, 'name' => 'mariano']
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
				'name' => 'nate'
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
				'name' => 'garrett'
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
 */
	public function testHasManyEagerLoadingFromSecondaryTable($strategy) {
		$author = TableRegistry::get('authors');
		$article = TableRegistry::get('articles');
		$post = TableRegistry::get('posts');

		$author->hasMany('posts', compact('strategy'));
		$article->belongsTo('authors');

		$query = new Query($this->connection, $article);

		$results = $query->select()
			->contain(['authors' => ['posts']])
			->order(['articles.id' => 'ASC'])
			->hydrate(false)
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
	public function testBelongsToManyEagerLoadingNoHydration($strategy) {
		$table = TableRegistry::get('Articles');
		TableRegistry::get('Tags');
		TableRegistry::get('ArticlesTags', [
			'table' => 'articles_tags'
		]);
		$table->belongsToMany('Tags', ['strategy' => $strategy]);
		$query = new Query($this->connection, $table);

		$results = $query->select()->contain('Tags')->hydrate(false)->toArray();
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
						'_joinData' => ['article_id' => 1, 'tag_id' => 1]
					],
					[
						'id' => 2,
						'name' => 'tag2',
						'_joinData' => ['article_id' => 1, 'tag_id' => 2]
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
						'_joinData' => ['article_id' => 2, 'tag_id' => 1]
					],
					[
						'id' => 3,
						'name' => 'tag3',
						'_joinData' => ['article_id' => 2, 'tag_id' => 3]
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

		$results = $query->select()
			->contain(['Tags' => ['conditions' => ['id' => 3]]])
			->hydrate(false)
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
						'_joinData' => ['article_id' => 2, 'tag_id' => 3]
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
		$this->assertEquals($table->association('Tags')->strategy(), $strategy);
	}

/**
 * Tests that tables results can be filtered by the result of a HasMany
 *
 * @return void
 */
	public function testFilteringByHasManyNoHydration() {
		$query = new Query($this->connection, $this->table);
		$table = TableRegistry::get('authors');
		TableRegistry::get('articles');
		$table->hasMany('articles');

		$results = $query->repository($table)
			->select()
			->hydrate(false)
			->matching('articles', function($q) {
				return $q->where(['articles.id' => 2]);
			})
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
	public function testFilteringByBelongsToManyNoHydration() {
		$query = new Query($this->connection, $this->table);
		$table = TableRegistry::get('Articles');
		TableRegistry::get('Tags');
		TableRegistry::get('ArticlesTags', [
			'table' => 'articles_tags'
		]);
		$table->belongsToMany('Tags');

		$results = $query->repository($table)->select()
			->matching('Tags', function($q) {
				return $q->where(['Tags.id' => 3]);
			})
			->hydrate(false)
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

		$query = new Query($this->connection, $table);
		$results = $query->select()
			->matching('Tags', function($q) {
				return $q->where(['Tags.name' => 'tag2']);
			})
			->hydrate(false)
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

/**
 * Tests that it is possible to filter by deep associations
 *
 * @return void
 */
	public function testMatchingDotNotation() {
		$query = new Query($this->connection, $this->table);
		$table = TableRegistry::get('authors');
		TableRegistry::get('articles');
		$table->hasMany('articles');
		TableRegistry::get('articles')->belongsToMany('tags');

		$results = $query->repository($table)
			->select()
			->hydrate(false)
			->matching('articles.tags', function($q) {
				return $q->where(['tags.id' => 2]);
			})
			->toArray();
		$expected = [
			[
				'id' => 1,
				'name' => 'mariano',
				'articles' => [
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
			]
		];
		$this->assertEquals($expected, $results);
	}

/**
 * Test setResult()
 *
 * @return void
 */
	public function testSetResult() {
		$query = new Query($this->connection, $this->table);

		$stmt = $this->getMock('Cake\Database\StatementInterface');
		$results = new ResultSet($query, $stmt);
		$query->setResult($results);
		$this->assertSame($results, $query->all());
	}

/**
 * Tests that applying array options to a query will convert them
 * to equivalent function calls with the correspondent array values
 *
 * @return void
 */
	public function testApplyOptions() {
		$options = [
			'fields' => ['field_a', 'field_b'],
			'conditions' => ['field_a' => 1, 'field_b' => 'something'],
			'limit' => 1,
			'order' => ['a' => 'ASC'],
			'offset' => 5,
			'group' => ['field_a'],
			'having' => ['field_a >' => 100],
			'contain' => ['table_a' => ['table_b']],
			'join' => ['table_a' => ['conditions' => ['a > b']]]
		];
		$query = new Query($this->connection, $this->table);
		$query->applyOptions($options);

		$this->assertEquals(['field_a', 'field_b'], $query->clause('select'));

		$expected = new QueryExpression($options['conditions']);
		$result = $query->clause('where');
		$this->assertEquals($expected, $result);

		$this->assertEquals(1, $query->clause('limit'));

		$expected = new QueryExpression(['a > b']);
		$result = $query->clause('join');
		$this->assertEquals([
			['alias' => 'table_a', 'type' => 'INNER', 'conditions' => $expected]
		], $result);

		$expected = new OrderByExpression(['a' => 'ASC']);
		$this->assertEquals($expected, $query->clause('order'));

		$this->assertEquals(5, $query->clause('offset'));
		$this->assertEquals(['field_a'], $query->clause('group'));

		$expected = new QueryExpression($options['having']);
		$this->assertEquals($expected, $query->clause('having'));

		$expected = new \ArrayObject(['table_a' => ['table_b' => []]]);
		$this->assertEquals($expected, $query->contain());
	}

/**
 * ApplyOptions should ignore null values.
 *
 * @return void
 */
	public function testApplyOptionsIgnoreNull() {
		$options = [
			'fields' => null,
		];
		$query = new Query($this->connection, $this->table);
		$query->applyOptions($options);
		$this->assertEquals([], $query->clause('select'));
	}

/**
 * Tests getOptions() method
 *
 * @return void
 */
	public function testGetOptions() {
		$options = ['doABarrelRoll' => true, 'fields' => ['id', 'name']];
		$query = new Query($this->connection, $this->table);
		$query->applyOptions($options);
		$expected = ['doABarrelRoll' => true];
		$this->assertEquals($expected, $query->getOptions());

		$expected = ['doABarrelRoll' => false, 'doAwesome' => true];
		$query->applyOptions($expected);
		$this->assertEquals($expected, $query->getOptions());
	}

/**
 * Tests registering mappers with mapReduce()
 *
 * @return void
 */
	public function testMapReduceOnlyMapper() {
		$mapper1 = function() {
		};
		$mapper2 = function() {
		};
		$query = new Query($this->connection, $this->table);
		$this->assertSame($query, $query->mapReduce($mapper1));
		$this->assertEquals(
			[['mapper' => $mapper1, 'reducer' => null]],
			$query->mapReduce()
		);

		$this->assertEquals($query, $query->mapReduce($mapper1));
		$result = $query->mapReduce();
		$this->assertEquals(
			[
				['mapper' => $mapper1, 'reducer' => null],
				['mapper' => $mapper2, 'reducer' => null]
			],
			$result
		);
	}

/**
 * Tests registering mappers and reducers with mapReduce()
 *
 * @return void
 */
	public function testMapReduceBothMethods() {
		$mapper1 = function() {
		};
		$mapper2 = function() {
		};
		$reducer1 = function() {
		};
		$reducer2 = function() {
		};
		$query = new Query($this->connection, $this->table);
		$this->assertSame($query, $query->mapReduce($mapper1, $reducer1));
		$this->assertEquals(
			[['mapper' => $mapper1, 'reducer' => $reducer1]],
			$query->mapReduce()
		);

		$this->assertSame($query, $query->mapReduce($mapper2, $reducer2));
		$this->assertEquals(
			[
				['mapper' => $mapper1, 'reducer' => $reducer1],
				['mapper' => $mapper2, 'reducer' => $reducer2]
			],
			$query->mapReduce()
		);
	}

/**
 * Tests that it is possible to overwrite previous map reducers
 *
 * @return void
 */
	public function testOverwriteMapReduce() {
		$mapper1 = function() {
		};
		$mapper2 = function() {
		};
		$reducer1 = function() {
		};
		$reducer2 = function() {
		};
		$query = new Query($this->connection, $this->table);
		$this->assertEquals($query, $query->mapReduce($mapper1, $reducer1));
		$this->assertEquals(
			[['mapper' => $mapper1, 'reducer' => $reducer1]],
			$query->mapReduce()
		);

		$this->assertEquals($query, $query->mapReduce($mapper2, $reducer2, true));
		$this->assertEquals(
			[['mapper' => $mapper2, 'reducer' => $reducer2]],
			$query->mapReduce()
		);
	}

/**
 * Tests that multiple map reducers can be stacked
 *
 * @return void
 */
	public function testResultsAreWrappedInMapReduce() {
		$params = [$this->connection, $this->table];
		$query = $this->getMock('\Cake\ORM\Query', ['execute'], $params);

		$statement = $this->getMock('\Database\StatementInterface', ['fetch', 'closeCursor']);
		$statement->expects($this->exactly(3))
			->method('fetch')
			->will($this->onConsecutiveCalls(['a' => 1], ['a' => 2], false));

		$query->expects($this->once())
			->method('execute')
			->will($this->returnValue($statement));

		$query->mapReduce(function($v, $k, $mr) {
			$mr->emit($v['a']);
		});
		$query->mapReduce(
			function($v, $k, $mr) {
				$mr->emitIntermediate($v, $k);
			},
			function($v, $k, $mr) {
				$mr->emit($v[0] + 1);
			}
		);

		$this->assertEquals([2, 3], iterator_to_array($query->all()));
	}

/**
 * Tests first() method when the query has not been executed before
 *
 * @return void
 */
	public function testFirstDirtyQuery() {
		$table = TableRegistry::get('articles', ['table' => 'articles']);
		$query = new Query($this->connection, $table);
		$result = $query->select(['id'])->hydrate(false)->first();
		$this->assertEquals(['id' => 1], $result);
		$this->assertEquals(1, $query->clause('limit'));
		$result = $query->select(['id'])->first();
		$this->assertEquals(['id' => 1], $result);
	}

/**
 * Tests that first can be called again on an already executed query
 *
 * @return void
 */
	public function testFirstCleanQuery() {
		$table = TableRegistry::get('articles', ['table' => 'articles']);
		$query = new Query($this->connection, $table);
		$query->select(['id'])->toArray();

		$first = $query->hydrate(false)->first();
		$this->assertEquals(['id' => 1], $first);
		$this->assertEquals(1, $query->clause('limit'));
	}

/**
 * Tests that first() will not execute the same query twice
 *
 * @return void
 */
	public function testFirstSameResult() {
		$table = TableRegistry::get('articles', ['table' => 'articles']);
		$query = new Query($this->connection, $table);
		$query->select(['id'])->toArray();

		$first = $query->hydrate(false)->first();
		$resultSet = $query->all();
		$this->assertEquals(['id' => 1], $first);
		$this->assertSame($resultSet, $query->all());
	}

/**
 * Tests that first can be called against a query with a mapReduce
 *
 * @return void
 */
	public function testFirstMapReduce() {
		$map = function($row, $key, $mapReduce) {
			$mapReduce->emitIntermediate($row['id'], 'id');
		};
		$reduce = function($values, $key, $mapReduce) {
			$mapReduce->emit(array_sum($values));
		};

		$table = TableRegistry::get('articles', ['table' => 'articles']);
		$query = new Query($this->connection, $table);
		$query->select(['id'])
			->hydrate(false)
			->mapReduce($map, $reduce);

		$first = $query->first();
		$this->assertEquals(1, $first);
	}

/**
 * Testing hydrating a result set into Entity objects
 *
 * @return void
 */
	public function testHydrateSimple() {
		$table = TableRegistry::get('articles', ['table' => 'articles']);
		$query = new Query($this->connection, $table);
		$results = $query->select()->toArray();

		$this->assertCount(3, $results);
		foreach ($results as $r) {
			$this->assertInstanceOf('\Cake\ORM\Entity', $r);
		}

		$first = $results[0];
		$this->assertEquals(1, $first->id);
		$this->assertEquals(1, $first->author_id);
		$this->assertEquals('First Article', $first->title);
		$this->assertEquals('First Article Body', $first->body);
		$this->assertEquals('Y', $first->published);
	}

/**
 * Tests that has many results are also hydrated correctly
 *
 * @return void
 */
	public function testHydrateWithHasMany() {
		$table = TableRegistry::get('authors');
		TableRegistry::get('articles');
		$table->hasMany('articles', [
			'property' => 'articles',
			'sort' => ['articles.id' => 'asc']
		]);
		$query = new Query($this->connection, $table);
		$results = $query->select()
			->contain('articles')
			->toArray();

		$first = $results[0];
		foreach ($first->articles as $r) {
			$this->assertInstanceOf('\Cake\ORM\Entity', $r);
		}

		$this->assertCount(2, $first->articles);
		$expected = [
			'id' => 1,
			'title' => 'First Article',
			'body' => 'First Article Body',
			'author_id' => 1,
			'published' => 'Y',
		];
		$this->assertEquals($expected, $first->articles[0]->toArray());
		$expected = [
			'id' => 3,
			'title' => 'Third Article',
			'author_id' => 1,
			'body' => 'Third Article Body',
			'published' => 'Y',
		];
		$this->assertEquals($expected, $first->articles[1]->toArray());
	}

/**
 * Tests that belongsToMany associations are also correctly hydrated
 *
 * @return void
 */
	public function testHydrateBelongsToMany() {
		$table = TableRegistry::get('Articles');
		TableRegistry::get('Tags');
		TableRegistry::get('ArticlesTags', [
			'table' => 'articles_tags'
		]);
		$table->belongsToMany('Tags');
		$query = new Query($this->connection, $table);

		$results = $query
			->select()
			->contain('Tags')
			->toArray();

		$first = $results[0];
		foreach ($first->tags as $r) {
			$this->assertInstanceOf('\Cake\ORM\Entity', $r);
		}

		$this->assertCount(2, $first->tags);
		$expected = [
			'id' => 1,
			'name' => 'tag1',
			'_joinData' => ['article_id' => 1, 'tag_id' => 1]
		];
		$this->assertEquals($expected, $first->tags[0]->toArray());

		$expected = [
			'id' => 2,
			'name' => 'tag2',
			'_joinData' => ['article_id' => 1, 'tag_id' => 2]
		];
		$this->assertEquals($expected, $first->tags[1]->toArray());
	}

/**
 * Tests that belongsTo relations are correctly hydrated
 *
 * @return void
 */
	public function testHydrateBelongsTo() {
		$table = TableRegistry::get('articles');
		TableRegistry::get('authors');
		$table->belongsTo('authors');

		$query = new Query($this->connection, $table);
		$results = $query->select()
			->contain('authors')
			->order(['articles.id' => 'asc'])
			->toArray();

		$this->assertCount(3, $results);
		$first = $results[0];
		$this->assertInstanceOf('\Cake\ORM\Entity', $first->author);
		$expected = ['id' => 1, 'name' => 'mariano'];
		$this->assertEquals($expected, $first->author->toArray());
	}

/**
 * Tests that deeply nested associations are also hydrated correctly
 *
 * @return void
 */
	public function testHydrateDeep() {
		$table = TableRegistry::get('authors');
		$article = TableRegistry::get('articles');
		$table->hasMany('articles', [
			'property' => 'articles',
			'sort' => ['articles.id' => 'asc']
		]);
		$article->belongsTo('authors');
		$query = new Query($this->connection, $table);

		$results = $query->select()
			->contain(['articles' => ['authors']])
			->toArray();

		$this->assertCount(4, $results);
		$first = $results[0];
		$this->assertInstanceOf('\Cake\ORM\Entity', $first->articles[0]->author);
		$expected = ['id' => 1, 'name' => 'mariano'];
		$this->assertEquals($expected, $first->articles[0]->author->toArray());
		$this->assertFalse(isset($results[3]->articles));
	}

/**
 * Tests that it is possible to use a custom entity class
 *
 * @return void
 */
	public function testHydrateCustomObject() {
		$class = $this->getMockClass('\Cake\ORM\Entity', ['fakeMethod']);
		$table = TableRegistry::get('articles', [
			'table' => 'articles',
			'entityClass' => '\\' . $class
		]);
		$query = new Query($this->connection, $table);
		$results = $query->select()->toArray();

		$this->assertCount(3, $results);
		foreach ($results as $r) {
			$this->assertInstanceOf($class, $r);
		}

		$first = $results[0];
		$this->assertEquals(1, $first->id);
		$this->assertEquals(1, $first->author_id);
		$this->assertEquals('First Article', $first->title);
		$this->assertEquals('First Article Body', $first->body);
		$this->assertEquals('Y', $first->published);
	}

/**
 * Tests that has many results are also hydrated correctly
 * when specified a custom entity class
 *
 * @return void
 */
	public function testHydrateWithHasManyCustomEntity() {
		$authorEntity = $this->getMockClass('\Cake\ORM\Entity', ['foo']);
		$articleEntity = $this->getMockClass('\Cake\ORM\Entity', ['foo']);
		$table = TableRegistry::get('authors', [
			'entityClass' => '\\' . $authorEntity
		]);
		TableRegistry::get('articles', [
			'entityClass' => '\\' . $articleEntity
		]);
		$table->hasMany('articles', [
			'property' => 'articles',
			'sort' => ['articles.id' => 'asc']
		]);
		$query = new Query($this->connection, $table);
		$results = $query->select()
			->contain('articles')
			->toArray();

		$first = $results[0];
		$this->assertInstanceOf($authorEntity, $first);
		foreach ($first->articles as $r) {
			$this->assertInstanceOf($articleEntity, $r);
		}

		$this->assertCount(2, $first->articles);
		$expected = [
			'id' => 1,
			'title' => 'First Article',
			'body' => 'First Article Body',
			'author_id' => 1,
			'published' => 'Y',
		];
		$this->assertEquals($expected, $first->articles[0]->toArray());
	}

/**
 * Tests that belongsTo relations are correctly hydrated into a custom entity class
 *
 * @return void
 */
	public function testHydrateBelongsToCustomEntity() {
		$authorEntity = $this->getMockClass('\Cake\ORM\Entity', ['foo']);
		$table = TableRegistry::get('articles');
		TableRegistry::get('authors', [
			'entityClass' => '\\' . $authorEntity
		]);
		$table->belongsTo('authors');

		$query = new Query($this->connection, $table);
		$results = $query->select()
			->contain('authors')
			->order(['articles.id' => 'asc'])
			->toArray();

		$first = $results[0];
		$this->assertInstanceOf($authorEntity, $first->author);
	}

/**
 * Test getting counts from queries.
 *
 * @return void
 */
	public function testCount() {
		$table = TableRegistry::get('articles');
		$result = $table->find('all')->count();
		$this->assertSame(3, $result);

		$query = $table->find('all')
			->where(['id >' => 1]);
		$result = $query->count();
		$this->assertSame(2, $result);

		$result = $query->all();
		$this->assertEquals(['count' => 2], $result->first());
	}

/**
 * Test that count() returns correct results with group by.
 *
 * @return void
 */
	public function testCountWithGroup() {
		$table = TableRegistry::get('articles');
		$query = $table->find('all')
			->group(['published']);
		$result = $query->count();
		$this->assertEquals(1, $result);
	}

/**
 * Test update method.
 *
 * @return void
 */
	public function testUpdate() {
		$table = TableRegistry::get('articles');

		$result = $table->query()
			->update()
			->set(['title' => 'First'])
			->execute();

		$this->assertInstanceOf('Cake\Database\StatementInterface', $result);
		$this->assertTrue($result->rowCount() > 0);
	}

/**
 * Test insert method.
 *
 * @return void
 */
	public function testInsert() {
		$table = TableRegistry::get('articles');

		$result = $table->query()
			->insert(['title'])
			->values(['title' => 'First'])
			->values(['title' => 'Second'])
			->execute();

		$this->assertInstanceOf('Cake\Database\StatementInterface', $result);
		$this->assertEquals(2, $result->rowCount());
	}

/**
 * Test delete method.
 *
 * @return void
 */
	public function testDelete() {
		$table = TableRegistry::get('articles');

		$result = $table->query()
			->delete()
			->where(['id >=' => 1])
			->execute();

		$this->assertInstanceOf('Cake\Database\StatementInterface', $result);
		$this->assertTrue($result->rowCount() > 0);
	}

/**
 * Provides a list of collection methods that can be proxied
 * from the query
 *
 * @return array
 */
	public function collectionMethodsProvider() {
		$identity = function($a) {
			return $a;
		};
		return [
			['filter', $identity],
			['reject', $identity],
			['every', $identity],
			['some', $identity],
			['contains', $identity],
			['map', $identity],
			['reduce', $identity],
			['extract', $identity],
			['max', $identity],
			['min', $identity],
			['sortBy', $identity],
			['groupBy', $identity],
			['countBy', $identity],
			['shuffle', $identity],
			['sample', $identity],
			['take', 1],
			['append', new \ArrayIterator],
			['compile', 1],
		];
	}

/**
 * Tests that query can proxy collection methods
 *
 * @dataProvider collectionMethodsProvider
 * @return void
 */
	public function testCollectionProxy($method, $arg) {
		$query = $this->getMock(
			'\Cake\ORM\Query', ['getResults'],
			[$this->connection, $this->table]
		);
		$query->select();
		$resultSet = $this->getMock('\Cake\ORM\ResultSet', [], [$query, null]);
		$query->expects($this->once())
			->method('getResults')
			->will($this->returnValue($resultSet));
		$resultSet->expects($this->once())
			->method($method)
			->with($arg, 'extra')
			->will($this->returnValue(new \Cake\Collection\Collection([])));
		$this->assertInstanceOf(
			'\Cake\Collection\Collection',
			$query->{$method}($arg, 'extra')
		);
	}

/**
 * Tests that calling an inexistent method in query throws an
 * exception
 *
 * @expectedException \BadMethodCallException
 * @expectedExceptionMessage Unknown method "derpFilter"
 * @return void
 */
	public function testCollectionProxyBadMethod() {
		TableRegistry::get('articles')->find('all')->derpFilter();
	}

/**
 * cache() should fail on non select queries.
 *
 * @expectedException RuntimeException
 * @return void
 */
	public function testCacheErrorOnNonSelect() {
		$table = TableRegistry::get('articles', ['table' => 'articles']);
		$query = new Query($this->connection, $table);
		$query->insert(['test']);
		$query->cache('my_key');
	}

/**
 * Integration test for query caching.
 *
 * @return void
 */
	public function testCacheReadIntegration() {
		$query = $this->getMock(
			'\Cake\ORM\Query', ['execute'],
			[$this->connection, $this->table]
		);
		$resultSet = $this->getMock('\Cake\ORM\ResultSet', [], [$query, null]);

		$query->expects($this->never())
			->method('execute');

		$cacher = $this->getMock('Cake\Cache\CacheEngine');
		$cacher->expects($this->once())
			->method('read')
			->with('my_key')
			->will($this->returnValue($resultSet));

		$query->cache('my_key', $cacher)
			->where(['id' => 1]);

		$results = $query->all();
		$this->assertSame($resultSet, $results);
	}

/**
 * Integration test for query caching.
 *
 * @return void
 */
	public function testCacheWriteIntegration() {
		$table = TableRegistry::get('Articles');
		$query = new Query($this->connection, $table);

		$query->select(['id', 'title']);

		$cacher = $this->getMock('Cake\Cache\CacheEngine');
		$cacher->expects($this->once())
			->method('write')
			->with(
				'my_key',
				$this->isInstanceOf('Cake\ORM\ResultSet')
			);

		$query->cache('my_key', $cacher)
			->where(['id' => 1]);

		$query->all();
	}

/**
 * Integration test to show filtering associations using contain and a closure
 *
 * @return void
 */
	public function testContainWithClosure() {
		$table = TableRegistry::get('authors');
		$table->hasMany('articles');
		$query = new Query($this->connection, $table);
		$query
			->select()
			->contain(['articles' => function($q) {
				return $q->where(['articles.id' => 1]);
			}]);

		$ids = [];
		foreach ($query as $entity) {
			foreach ((array)$entity->articles as $article) {
				$ids[] = $article->id;
			}
		}
		$this->assertEquals([1], array_unique($ids));
	}

}
