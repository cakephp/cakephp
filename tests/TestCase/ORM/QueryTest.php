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

use Cake\Database\Expression\IdentifierExpression;
use Cake\Database\Expression\OrderByExpression;
use Cake\Database\Expression\QueryExpression;
use Cake\Database\TypeMap;
use Cake\Datasource\ConnectionManager;
use Cake\ORM\Query;
use Cake\ORM\ResultSet;
use Cake\ORM\Table;
use Cake\ORM\TableRegistry;
use Cake\TestSuite\TestCase;

/**
 * Tests Query class
 */
class QueryTest extends TestCase
{

    /**
     * Fixture to be used
     *
     * @var array
     */
    public $fixtures = ['core.articles', 'core.authors', 'core.tags',
        'core.articles_tags', 'core.posts'];

    /**
     * setUp method
     *
     * @return void
     */
    public function setUp()
    {
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

        $this->fooTypeMap = new TypeMap(['foo.id' => 'integer', 'id' => 'integer']);
    }

    /**
     * tearDown method
     *
     * @return void
     */
    public function tearDown()
    {
        parent::tearDown();
        TableRegistry::clear();
    }

    /**
     * Provides strategies for associations that can be joined
     *
     * @return void
     */
    public function internalStategiesProvider()
    {
        return [['join'], ['select'], ['subquery']];
    }

    /**
     * Tests that results are grouped correctly when using contain()
     * and results are not hydrated
     *
     * @dataProvider internalStategiesProvider
     * @return void
     */
    public function testContainResultFetchingOneLevel($strategy)
    {
        $table = TableRegistry::get('articles', ['table' => 'articles']);
        $table->belongsTo('authors', ['strategy' => $strategy]);

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
    public function strategiesProvider()
    {
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
    public function testHasManyEagerLoadingNoHydration($strategy)
    {
        $table = TableRegistry::get('authors');
        TableRegistry::get('articles');
        $table->hasMany('articles', [
            'propertyName' => 'articles',
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
                'articles' => [],
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
                'articles' => [],
            ]
        ];
        $this->assertEquals($expected, $results);

        $results = $query->repository($table)
            ->select()
            ->contain(['articles' => ['conditions' => ['articles.id' => 2]]])
            ->hydrate(false)
            ->toArray();
        $expected[0]['articles'] = [];
        $this->assertEquals($expected, $results);
        $this->assertEquals($table->association('articles')->strategy(), $strategy);
    }

    /**
     * Tests that it is possible to count results containing hasMany associations
     * both hydrating and not hydrating the results.
     *
     * @dataProvider strategiesProvider
     * @return void
     */
    public function testHasManyEagerLoadingCount($strategy)
    {
        $table = TableRegistry::get('authors');
        TableRegistry::get('articles');
        $table->hasMany('articles', [
            'property' => 'articles',
            'strategy' => $strategy,
            'sort' => ['articles.id' => 'asc']
        ]);
        $query = new Query($this->connection, $table);

        $query = $query->select()
            ->contain('articles');

        $expected = 4;

        $results = $query->hydrate(false)
            ->count();
        $this->assertEquals($expected, $results);

        $results = $query->hydrate(true)
            ->count();
        $this->assertEquals($expected, $results);
    }

    /**
     * Tests that it is possible to set fields & order in a hasMany result set
     *
     * @dataProvider strategiesProvider
     * @return void
     */
    public function testHasManyEagerLoadingFieldsAndOrderNoHydration($strategy)
    {
        $table = TableRegistry::get('authors');
        TableRegistry::get('articles');
        $table->hasMany('articles', ['propertyName' => 'articles'] + compact('strategy'));

        $query = new Query($this->connection, $table);
        $results = $query->select()
            ->contain([
                'articles' => [
                    'fields' => ['title', 'author_id'],
                    'sort' => ['articles.id' => 'DESC']
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
                'articles' => [],
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
                'articles' => [],
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
    public function testHasManyEagerLoadingDeep($strategy)
    {
        $table = TableRegistry::get('authors');
        $article = TableRegistry::get('articles');
        $table->hasMany('articles', [
            'propertyName' => 'articles',
            'strategy' => $strategy,
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
                'name' => 'nate',
                'articles' => [],
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
                'articles' => [],
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
    public function testHasManyEagerLoadingFromSecondaryTable($strategy)
    {
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
     */
    public function testBelongsToManyEagerLoadingNoHydration($strategy)
    {
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
                'tags' => [],
            ],
        ];
        $this->assertEquals($expected, $results);

        $results = $query->select()
            ->contain(['Tags' => ['conditions' => ['Tags.id' => 3]]])
            ->hydrate(false)
            ->toArray();
        $expected = [
            [
                'id' => 1,
                'author_id' => 1,
                'title' => 'First Article',
                'body' => 'First Article Body',
                'published' => 'Y',
                'tags' => [],
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
                'tags' => [],
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
    public function testFilteringByHasManyNoHydration()
    {
        $query = new Query($this->connection, $this->table);
        $table = TableRegistry::get('authors');
        TableRegistry::get('articles');
        $table->hasMany('articles');

        $results = $query->repository($table)
            ->select()
            ->hydrate(false)
            ->matching('articles', function ($q) {
                return $q->where(['articles.id' => 2]);
            })
            ->toArray();
        $expected = [
            [
                'id' => 3,
                'name' => 'larry',
                '_matchingData' => [
                    'articles' => [
                        'id' => 2,
                        'title' => 'Second Article',
                        'body' => 'Second Article Body',
                        'author_id' => 3,
                        'published' => 'Y',
                    ]
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
     */
    public function testFilteringByBelongsToManyNoHydration()
    {
        $query = new Query($this->connection, $this->table);
        $table = TableRegistry::get('Articles');
        TableRegistry::get('Tags');
        TableRegistry::get('ArticlesTags', [
            'table' => 'articles_tags'
        ]);
        $table->belongsToMany('Tags');

        $results = $query->repository($table)->select()
            ->matching('Tags', function ($q) {
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
                '_matchingData' => [
                    'Tags' => [
                        'id' => 3,
                        'name' => 'tag3',
                    ],
                    'ArticlesTags' => ['article_id' => 2, 'tag_id' => 3]
                ]
            ]
        ];
        $this->assertEquals($expected, $results);

        $query = new Query($this->connection, $table);
        $results = $query->select()
            ->matching('Tags', function ($q) {
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
                '_matchingData' => [
                    'Tags' => [
                        'id' => 2,
                        'name' => 'tag2',
                    ],
                    'ArticlesTags' => ['article_id' => 1, 'tag_id' => 2]
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
    public function testMatchingDotNotation()
    {
        $query = new Query($this->connection, $this->table);
        $table = TableRegistry::get('authors');
        TableRegistry::get('articles');
        $table->hasMany('articles');
        TableRegistry::get('articles')->belongsToMany('tags');

        $results = $query->repository($table)
            ->select()
            ->hydrate(false)
            ->matching('articles.tags', function ($q) {
                return $q->where(['tags.id' => 2]);
            })
            ->toArray();
        $expected = [
            [
                'id' => 1,
                'name' => 'mariano',
                '_matchingData' => [
                    'tags' => [
                        'id' => 2,
                        'name' => 'tag2',
                    ],
                    'articles' => [
                        'id' => 1,
                        'author_id' => 1,
                        'title' => 'First Article',
                        'body' => 'First Article Body',
                        'published' => 'Y'
                    ],
                    'ArticlesTags' => [
                            'article_id' => 1,
                            'tag_id' => 2
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
    public function testSetResult()
    {
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
    public function testApplyOptions()
    {
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

        $expected = new QueryExpression($options['conditions'], $this->fooTypeMap);
        $result = $query->clause('where');
        $this->assertEquals($expected, $result);

        $this->assertEquals(1, $query->clause('limit'));

        $expected = new QueryExpression(['a > b']);
        $expected->typeMap($this->fooTypeMap);
        $result = $query->clause('join');
        $this->assertEquals([
            'table_a' => ['alias' => 'table_a', 'type' => 'INNER', 'conditions' => $expected]
        ], $result);

        $expected = new OrderByExpression(['a' => 'ASC']);
        $this->assertEquals($expected, $query->clause('order'));

        $this->assertEquals(5, $query->clause('offset'));
        $this->assertEquals(['field_a'], $query->clause('group'));

        $expected = new QueryExpression($options['having']);
        $expected->typeMap($this->fooTypeMap);
        $this->assertEquals($expected, $query->clause('having'));

        $expected = ['table_a' => ['table_b' => []]];
        $this->assertEquals($expected, $query->contain());
    }

    /**
     * Test that page is applied after limit.
     *
     * @return void
     */
    public function testApplyOptionsPageIsLast()
    {
        $query = new Query($this->connection, $this->table);
        $opts = [
            'page' => 3,
            'limit' => 5
        ];
        $query->applyOptions($opts);
        $this->assertEquals(5, $query->clause('limit'));
        $this->assertEquals(10, $query->clause('offset'));
    }

    /**
     * ApplyOptions should ignore null values.
     *
     * @return void
     */
    public function testApplyOptionsIgnoreNull()
    {
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
    public function testGetOptions()
    {
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
    public function testMapReduceOnlyMapper()
    {
        $mapper1 = function () {
        };
        $mapper2 = function () {
        };
        $query = new Query($this->connection, $this->table);
        $this->assertSame($query, $query->mapReduce($mapper1));
        $this->assertEquals(
            [['mapper' => $mapper1, 'reducer' => null]],
            $query->mapReduce()
        );

        $this->assertEquals($query, $query->mapReduce($mapper2));
        $result = $query->mapReduce();
        $this->assertSame(
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
    public function testMapReduceBothMethods()
    {
        $mapper1 = function () {
        };
        $mapper2 = function () {
        };
        $reducer1 = function () {
        };
        $reducer2 = function () {
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
    public function testOverwriteMapReduce()
    {
        $mapper1 = function () {
        };
        $mapper2 = function () {
        };
        $reducer1 = function () {
        };
        $reducer2 = function () {
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
    public function testResultsAreWrappedInMapReduce()
    {
        $params = [$this->connection, $this->table];
        $query = $this->getMock('\Cake\ORM\Query', ['execute'], $params);

        $statement = $this->getMock(
            '\Database\StatementInterface',
            ['fetch', 'closeCursor', 'rowCount']
        );
        $statement->expects($this->exactly(3))
            ->method('fetch')
            ->will($this->onConsecutiveCalls(['a' => 1], ['a' => 2], false));

        $statement->expects($this->once())
            ->method('rowCount')
            ->will($this->returnValue(2));

        $query->expects($this->once())
            ->method('execute')
            ->will($this->returnValue($statement));

        $query->mapReduce(function ($v, $k, $mr) {
            $mr->emit($v['a']);
        });
        $query->mapReduce(
            function ($v, $k, $mr) {
                $mr->emitIntermediate($v, $k);
            },
            function ($v, $k, $mr) {
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
    public function testFirstDirtyQuery()
    {
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
    public function testFirstCleanQuery()
    {
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
    public function testFirstSameResult()
    {
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
    public function testFirstMapReduce()
    {
        $map = function ($row, $key, $mapReduce) {
            $mapReduce->emitIntermediate($row['id'], 'id');
        };
        $reduce = function ($values, $key, $mapReduce) {
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
    public function testHydrateSimple()
    {
        $table = TableRegistry::get('articles', ['table' => 'articles']);
        $query = new Query($this->connection, $table);
        $results = $query->select()->toArray();

        $this->assertCount(3, $results);
        foreach ($results as $r) {
            $this->assertInstanceOf('Cake\ORM\Entity', $r);
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
    public function testHydrateHasMany()
    {
        $table = TableRegistry::get('authors');
        TableRegistry::get('articles');
        $table->hasMany('articles', [
            'propertyName' => 'articles',
            'sort' => ['articles.id' => 'asc']
        ]);
        $query = new Query($this->connection, $table);
        $results = $query->select()
            ->contain('articles')
            ->toArray();

        $first = $results[0];
        foreach ($first->articles as $r) {
            $this->assertInstanceOf('Cake\ORM\Entity', $r);
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
    public function testHydrateBelongsToMany()
    {
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
            $this->assertInstanceOf('Cake\ORM\Entity', $r);
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
     * @dataProvider internalStategiesProvider
     * @return void
     */
    public function testHydrateBelongsTo($strategy)
    {
        $table = TableRegistry::get('articles');
        TableRegistry::get('authors');
        $table->belongsTo('authors', ['strategy' => $strategy]);

        $query = new Query($this->connection, $table);
        $results = $query->select()
            ->contain('authors')
            ->order(['articles.id' => 'asc'])
            ->toArray();

        $this->assertCount(3, $results);
        $first = $results[0];
        $this->assertInstanceOf('Cake\ORM\Entity', $first->author);
        $expected = ['id' => 1, 'name' => 'mariano'];
        $this->assertEquals($expected, $first->author->toArray());
    }

    /**
     * Tests that deeply nested associations are also hydrated correctly
     *
     * @dataProvider internalStategiesProvider
     * @return void
     */
    public function testHydrateDeep($strategy)
    {
        $table = TableRegistry::get('authors');
        $article = TableRegistry::get('articles');
        $table->hasMany('articles', [
            'propertyName' => 'articles',
            'sort' => ['articles.id' => 'asc']
        ]);
        $article->belongsTo('authors', ['strategy' => $strategy]);
        $query = new Query($this->connection, $table);

        $results = $query->select()
            ->contain(['articles' => ['authors']])
            ->toArray();

        $this->assertCount(4, $results);
        $first = $results[0];
        $this->assertInstanceOf('Cake\ORM\Entity', $first->articles[0]->author);
        $expected = ['id' => 1, 'name' => 'mariano'];
        $this->assertEquals($expected, $first->articles[0]->author->toArray());
        $this->assertTrue(isset($results[3]->articles));
    }

    /**
     * Tests that it is possible to use a custom entity class
     *
     * @return void
     */
    public function testHydrateCustomObject()
    {
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
    public function testHydrateHasManyCustomEntity()
    {
        $authorEntity = $this->getMockClass('\Cake\ORM\Entity', ['foo']);
        $articleEntity = $this->getMockClass('\Cake\ORM\Entity', ['foo']);
        $table = TableRegistry::get('authors', [
            'entityClass' => '\\' . $authorEntity
        ]);
        TableRegistry::get('articles', [
            'entityClass' => '\\' . $articleEntity
        ]);
        $table->hasMany('articles', [
            'propertyName' => 'articles',
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
    public function testHydrateBelongsToCustomEntity()
    {
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
    public function testCount()
    {
        $table = TableRegistry::get('articles');
        $result = $table->find('all')->count();
        $this->assertSame(3, $result);

        $query = $table->find('all')
            ->where(['id >' => 1])
            ->limit(1);
        $result = $query->count();
        $this->assertSame(2, $result);

        $result = $query->all();
        $this->assertCount(1, $result);
        $this->assertEquals(2, $result->first()->id);
    }

    /**
     * test count with a beforeFind.
     *
     * @return void
     */
    public function testCountBeforeFind()
    {
        $table = TableRegistry::get('Articles');
        $table->hasMany('Comments');
        $table->eventManager()
            ->attach(function ($event, $query) {
                $query
                    ->limit(1)
                    ->order(['Articles.title' => 'DESC']);
            }, 'Model.beforeFind');

        $query = $table->find();
        $result = $query->count();
        $this->assertSame(3, $result);
    }

    /**
     * Test that count() returns correct results with group by.
     *
     * @return void
     */
    public function testCountWithGroup()
    {
        $table = TableRegistry::get('articles');
        $query = $table->find('all');
        $query->select(['author_id', 's' => $query->func()->sum('id')])
            ->group(['author_id']);
        $result = $query->count();
        $this->assertEquals(2, $result);
    }

    /**
     * Tests that it is possible to provide a callback for calculating the count
     * of a query
     *
     * @return void
     */
    public function testCountWithCustomCounter()
    {
        $table = TableRegistry::get('articles');
        $query = $table->find('all');
        $query
            ->select(['author_id', 's' => $query->func()->sum('id')])
            ->where(['id >' => 2])
            ->group(['author_id'])
            ->counter(function ($q) use ($query) {
                $this->assertNotSame($q, $query);
                return $q->select([], true)->group([], true)->count();
            });

        $result = $query->count();
        $this->assertEquals(1, $result);
    }

    /**
     * Test update method.
     *
     * @return void
     */
    public function testUpdate()
    {
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
    public function testInsert()
    {
        $table = TableRegistry::get('articles');

        $result = $table->query()
            ->insert(['title'])
            ->values(['title' => 'First'])
            ->values(['title' => 'Second'])
            ->execute();

        $result->closeCursor();

        $this->assertInstanceOf('Cake\Database\StatementInterface', $result);
        //PDO_SQLSRV returns -1 for successful inserts when using INSERT ... OUTPUT
        if (!$this->connection->driver() instanceof \Cake\Database\Driver\Sqlserver) {
            $this->assertEquals(2, $result->rowCount());
        } else {
            $this->assertEquals(-1, $result->rowCount());
        }
    }

    /**
     * Test delete method.
     *
     * @return void
     */
    public function testDelete()
    {
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
    public function collectionMethodsProvider()
    {
        $identity = function ($a) {
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
    public function testCollectionProxy($method, $arg)
    {
        $query = $this->getMock(
            '\Cake\ORM\Query',
            ['all'],
            [$this->connection, $this->table]
        );
        $query->select();
        $resultSet = $this->getMock('\Cake\ORM\ResultSet', [], [$query, null]);
        $query->expects($this->once())
            ->method('all')
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
    public function testCollectionProxyBadMethod()
    {
        TableRegistry::get('articles')->find('all')->derpFilter();
    }

    /**
     * cache() should fail on non select queries.
     *
     * @expectedException \RuntimeException
     * @return void
     */
    public function testCacheErrorOnNonSelect()
    {
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
    public function testCacheReadIntegration()
    {
        $query = $this->getMock(
            '\Cake\ORM\Query',
            ['execute'],
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
    public function testCacheWriteIntegration()
    {
        $table = TableRegistry::get('Articles');
        $query = new Query($this->connection, $table);

        $query->select(['id', 'title']);

        $cacher = $this->getMock('Cake\Cache\CacheEngine');
        $cacher->expects($this->once())
            ->method('write')
            ->with(
                'my_key',
                $this->isInstanceOf('Cake\Datasource\ResultSetInterface')
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
    public function testContainWithClosure()
    {
        $table = TableRegistry::get('authors');
        $table->hasMany('articles');
        $query = new Query($this->connection, $table);
        $query
            ->select()
            ->contain(['articles' => function ($q) {
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

    /**
     * Tests the formatResults method
     *
     * @return void
     */
    public function testFormatResults()
    {
        $callback1 = function () {
        };
        $callback2 = function () {
        };
        $table = TableRegistry::get('authors');
        $query = new Query($this->connection, $table);
        $this->assertSame($query, $query->formatResults($callback1));
        $this->assertSame([$callback1], $query->formatResults());
        $this->assertSame($query, $query->formatResults($callback2));
        $this->assertSame([$callback1, $callback2], $query->formatResults());
        $query->formatResults($callback2, true);
        $this->assertSame([$callback2], $query->formatResults());
        $query->formatResults(null, true);
        $this->assertSame([], $query->formatResults());

        $query->formatResults($callback1);
        $query->formatResults($callback2, $query::PREPEND);
        $this->assertSame([$callback2, $callback1], $query->formatResults());
    }

    /**
     * Test fetching results from a qurey with a custom formatter
     *
     * @return void
     */
    public function testQueryWithFormatter()
    {
        $table = TableRegistry::get('authors');
        $query = new Query($this->connection, $table);
        $query->select()->formatResults(function ($results, $q) use ($query) {
            $this->assertSame($query, $q);
            $this->assertInstanceOf('Cake\ORM\ResultSet', $results);
            return $results->indexBy('id');
        });
        $this->assertEquals([1, 2, 3, 4], array_keys($query->toArray()));
    }

    /**
     * Test fetching results from a qurey with a two custom formatters
     *
     * @return void
     */
    public function testQueryWithStackedFormatters()
    {
        $table = TableRegistry::get('authors');
        $query = new Query($this->connection, $table);
        $query->select()->formatResults(function ($results, $q) use ($query) {
            $this->assertSame($query, $q);
            $this->assertInstanceOf('Cake\ORM\ResultSet', $results);
            return $results->indexBy('id');
        });

        $query->formatResults(function ($results) {
            return $results->extract('name');
        });

        $expected = [
            1 => 'mariano',
            2 => 'nate',
            3 => 'larry',
            4 => 'garrett'
        ];
        $this->assertEquals($expected, $query->toArray());
    }

    /**
     * Tests that getting results from a query having a contained association
     * will no attach joins twice if count() is called on it afterwards
     *
     * @return void
     */
    public function testCountWithContainCallingAll()
    {
        $table = TableRegistry::get('articles');
        $table->belongsTo('authors');
        $query = $table->find()
            ->select(['id', 'title'])
            ->contain('authors')
            ->limit(2);

        $results = $query->all();
        $this->assertCount(2, $results);
        $this->assertEquals(3, $query->count());
    }

    /**
     * Tests that it is possible to apply formatters inside the query builder
     * for belongsTo associations
     *
     * @return void
     */
    public function testFormatBelongsToRecords()
    {
        $table = TableRegistry::get('articles');
        $table->belongsTo('authors');

        $query = $table->find()
            ->contain(['authors' => function ($q) {
                return $q
                    ->formatResults(function ($authors) {
                        return $authors->map(function ($author) {
                            $author->idCopy = $author->id;
                            return $author;
                        });
                    })
                    ->formatResults(function ($authors) {
                        return $authors->map(function ($author) {
                            $author->idCopy = $author->idCopy + 2;
                            return $author;
                        });
                    });
            }]);

        $query->formatResults(function ($results) {
            return $results->combine('id', 'author.idCopy');
        });
        $results = $query->toArray();
        $expected = [1 => 3, 2 => 5, 3 => 3];
        $this->assertEquals($expected, $results);
    }

    /**
     * Tests it is possible to apply formatters to deep relations.
     *
     * @return void
     */
    public function testFormatDeepAssocationRecords()
    {
        $table = TableRegistry::get('ArticlesTags');
        $table->belongsTo('Articles');
        $table->association('Articles')->target()->belongsTo('Authors');

        $builder = function ($q) {
            return $q
                ->formatResults(function ($results) {
                    return $results->map(function ($result) {
                        $result->idCopy = $result->id;
                        return $result;
                    });
                })
                ->formatResults(function ($results) {
                    return $results->map(function ($result) {
                        $result->idCopy = $result->idCopy + 2;
                        return $result;
                    });
                });
        };
        $query = $table->find()
            ->contain(['Articles' => $builder, 'Articles.Authors' => $builder])
            ->order(['Articles.id' => 'ASC']);

        $query->formatResults(function ($results) {
            return $results->map(function ($row) {
                return sprintf(
                    '%s - %s - %s',
                    $row->tag_id,
                    $row->article->idCopy,
                    $row->article->author->idCopy
                );
            });
        });

        $expected = ['1 - 3 - 3', '2 - 3 - 3', '1 - 4 - 5', '3 - 4 - 5'];
        $this->assertEquals($expected, $query->toArray());
    }

    /**
     * Tests that formatters cna be applied to deep associations that are fetched using
     * additional queries
     *
     * @return void
     */
    public function testFormatDeepDistantAssociationRecords()
    {
        $table = TableRegistry::get('authors');
        $table->hasMany('articles');
        $articles = $table->association('articles')->target();
        $articles->hasMany('articlesTags');
        $articles->association('articlesTags')->target()->belongsTo('tags');

        $query = $table->find()->contain(['articles.articlesTags.tags' => function ($q) {
            return $q->formatResults(function ($results) {
                return $results->map(function ($tag) {
                    $tag->name .= ' - visited';
                    return $tag;
                });
            });
        }]);

        $query->mapReduce(function ($row, $key, $mr) {
            foreach ((array)$row->articles as $article) {
                foreach ((array)$article->articles_tags as $articleTag) {
                    $mr->emit($articleTag->tag->name);
                }
            }
        });

        $expected = ['tag1 - visited', 'tag2 - visited', 'tag1 - visited', 'tag3 - visited'];
        $this->assertEquals($expected, $query->toArray());
    }

    /**
     * Tests that custom finders are applied to associations when using the proxies
     *
     * @return void
     */
    public function testCustomFinderInBelongsTo()
    {
        $table = TableRegistry::get('ArticlesTags');
        $table->belongsTo('Articles', [
            'className' => 'TestApp\Model\Table\ArticlesTable',
            'finder' => 'published'
        ]);
        $result = $table->find()->contain('Articles');
        $this->assertCount(4, $result->extract('article')->filter()->toArray());
        $table->Articles->updateAll(['published' => 'N'], ['1 = 1']);

        $result = $table->find()->contain('Articles');
        $this->assertCount(0, $result->extract('article')->filter()->toArray());
    }

    /**
     * Tests that it is possible to attach more association when using a query
     * builder for other associations
     *
     * @return void
     */
    public function testContainInAssociationQuery()
    {
        $table = TableRegistry::get('ArticlesTags');
        $table->belongsTo('Articles');
        $table->association('Articles')->target()->belongsTo('Authors');

        $query = $table->find()
            ->order(['Articles.id' => 'ASC'])
            ->contain(['Articles' => function ($q) {
                return $q->contain('Authors');
            }]);
        $results = $query->extract('article.author.name')->toArray();
        $expected = ['mariano', 'mariano', 'larry', 'larry'];
        $this->assertEquals($expected, $results);
    }

    /**
     * Tests that it is possible to apply more `matching` conditions inside query
     * builders for associations
     *
     * @return void
     */
    public function testContainInAssociationMatching()
    {
        $table = TableRegistry::get('authors');
        $table->hasMany('articles');
        $articles = $table->association('articles')->target();
        $articles->hasMany('articlesTags');
        $articles->association('articlesTags')->target()->belongsTo('tags');

        $query = $table->find()->matching('articles.articlesTags', function ($q) {
            return $q->matching('tags', function ($q) {
                return $q->where(['tags.name' => 'tag3']);
            });
        });

        $results = $query->toArray();
        $this->assertCount(1, $results);
        $this->assertEquals('tag3', $results[0]->_matchingData['tags']->name);
    }

    /**
     * Tests __debugInfo
     *
     * @return void
     */
    public function testDebugInfo()
    {
        $table = TableRegistry::get('authors');
        $table->hasMany('articles');
        $query = $table->find()
            ->where(['id > ' => 1])
            ->bufferResults(false)
            ->hydrate(false)
            ->matching('articles')
            ->applyOptions(['foo' => 'bar'])
            ->formatResults(function ($results) {
                return $results;
            })
            ->mapReduce(function ($item, $key, $mr) {
                $mr->emit($item);
            });

        $expected = [
            'sql' => $query->sql(),
            'params' => $query->valueBinder()->bindings(),
            'defaultTypes' => [
                'authors.id' => 'integer',
                'id' => 'integer',
                'authors.name' => 'string',
                'name' => 'string'
            ],
            'decorators' => 0,
            'executed' => false,
            'hydrate' => false,
            'buffered' => false,
            'formatters' => 1,
            'mapReducers' => 1,
            'contain' => [],
            'matching' => [
                'articles' => [
                    'queryBuilder' => null,
                    'matching' => true
                ]
            ],
            'extraOptions' => ['foo' => 'bar'],
            'repository' => $table
        ];
        $this->assertSame($expected, $query->__debugInfo());
    }

    /**
     * Tests that the eagerLoaded function works and is transmitted correctly to eagerly
     * loaded associations
     *
     * @return void
     */
    public function testEagerLoaded()
    {
        $table = TableRegistry::get('authors');
        $table->hasMany('articles');
        $query = $table->find()->contain(['articles' => function ($q) {
            $this->assertTrue($q->eagerLoaded());
            return $q;
        }]);
        $this->assertFalse($query->eagerLoaded());

        $table->eventManager()->attach(function ($e, $q, $o, $primary) {
            $this->assertTrue($primary);
        }, 'Model.beforeFind');

        TableRegistry::get('articles')
            ->eventManager()->attach(function ($e, $q, $o, $primary) {
                $this->assertFalse($primary);
            }, 'Model.beforeFind');
        $query->all();
    }

    /**
     * Tests that columns from manual joins are also contained in the result set
     *
     * @return void
     */
    public function testColumnsFromJoin()
    {
        $table = TableRegistry::get('articles');
        $results = $table->find()
            ->select(['title', 'person.name'])
            ->join([
                'person' => [
                    'table' => 'authors',
                    'conditions' => ['person.id = articles.author_id']
                ]
            ])
            ->order(['articles.id' => 'ASC'])
            ->hydrate(false)
            ->toArray();
        $expected = [
            ['title' => 'First Article', 'person' => ['name' => 'mariano']],
            ['title' => 'Second Article', 'person' => ['name' => 'larry']],
            ['title' => 'Third Article', 'person' => ['name' => 'mariano']],
        ];
        $this->assertSame($expected, $results);
    }

    /**
     * Tests that it is possible to use the same association aliases in the association
     * chain for contain
     *
     * @dataProvider internalStategiesProvider
     * @return void
     */
    public function testRepeatedAssociationAliases($strategy)
    {
        $table = TableRegistry::get('ArticlesTags');
        $table->belongsTo('Articles', ['strategy' => $strategy]);
        $table->belongsTo('Tags', ['strategy' => $strategy]);
        TableRegistry::get('Tags')->belongsToMany('Articles');
        $results = $table
            ->find()
            ->contain(['Articles', 'Tags.Articles'])
            ->hydrate(false)
            ->toArray();
        $this->assertNotEmpty($results[0]['tag']['articles']);
        $this->assertNotEmpty($results[0]['article']);
        $this->assertNotEmpty($results[1]['tag']['articles']);
        $this->assertNotEmpty($results[1]['article']);
        $this->assertNotEmpty($results[2]['tag']['articles']);
        $this->assertNotEmpty($results[2]['article']);
    }

    /**
     * Tests that a hasOne association using the select strategy will still have the
     * key present in the results when no match is found
     *
     * @return void
     */
    public function testAssociationKeyPresent()
    {
        $table = TableRegistry::get('Articles');
        $table->hasOne('ArticlesTags', ['strategy' => 'select']);
        $article = $table->find()->where(['id' => 3])
            ->hydrate(false)
            ->contain('ArticlesTags')
            ->first();

        $this->assertNull($article['articles_tag']);
    }

    /**
     * Tests that queries can be serialized to JSON to get the results
     *
     * @return void
     */
    public function testJsonSerialize()
    {
        $table = TableRegistry::get('Articles');
        $this->assertEquals(
            json_encode($table->find()),
            json_encode($table->find()->toArray())
        );
    }

    /**
     * Test that addFields() works in the basic case.
     *
     * @return void
     */
    public function testAutoFields()
    {
        $table = TableRegistry::get('Articles');
        $result = $table->find('all')
            ->select(['myField' => '(SELECT 20)'])
            ->autoFields(true)
            ->hydrate(false)
            ->first();

        $this->assertArrayHasKey('myField', $result);
        $this->assertArrayHasKey('id', $result);
        $this->assertArrayHasKey('title', $result);
    }

    /**
     * Test autoFields with auto fields.
     *
     * @return void
     */
    public function testAutoFieldsWithAssociations()
    {
        $table = TableRegistry::get('Articles');
        $table->belongsTo('Authors');

        $result = $table->find()
            ->select(['myField' => '(SELECT 2 + 2)'])
            ->autoFields(true)
            ->hydrate(false)
            ->contain('Authors')
            ->first();

        $this->assertArrayHasKey('myField', $result);
        $this->assertArrayHasKey('title', $result);
        $this->assertArrayHasKey('author', $result);
        $this->assertNotNull($result['author']);
        $this->assertArrayHasKey('name', $result['author']);
    }

    /**
     * Test autoFields in contain query builder
     *
     * @return void
     */
    public function testAutoFieldsWithContainQueryBuilder()
    {
        $table = TableRegistry::get('Articles');
        $table->belongsTo('Authors');

        $result = $table->find()
            ->select(['myField' => '(SELECT 2 + 2)'])
            ->autoFields(true)
            ->hydrate(false)
            ->contain(['Authors' => function ($q) {
                return $q->select(['compute' => '(SELECT 2 + 20)'])
                    ->autoFields(true);
            }])
            ->first();

        $this->assertArrayHasKey('myField', $result);
        $this->assertArrayHasKey('title', $result);
        $this->assertArrayHasKey('author', $result);
        $this->assertNotNull($result['author']);
        $this->assertArrayHasKey('name', $result['author']);
        $this->assertArrayHasKey('compute', $result);
    }

    /**
     * Test that autofields works with count()
     *
     * @return void
     */
    public function testAutoFieldsCount()
    {
        $table = TableRegistry::get('Articles');

        $result = $table->find()
            ->select(['myField' => '(SELECT (2 + 2))'])
            ->autoFields(true)
            ->count();

        $this->assertEquals(3, $result);
    }

    /**
     * test that cleanCopy makes a cleaned up clone.
     *
     * @return void
     */
    public function testCleanCopy()
    {
        $table = TableRegistry::get('Articles');
        $table->hasMany('Comments');

        $query = $table->find();
        $query->offset(10)
            ->limit(1)
            ->order(['Articles.id' => 'DESC'])
            ->contain(['Comments']);
        $copy = $query->cleanCopy();

        $this->assertNotSame($copy, $query);
        $this->assertNull($copy->clause('offset'));
        $this->assertNull($copy->clause('limit'));
        $this->assertNull($copy->clause('order'));
    }

    /**
     * test that cleanCopy makes a cleaned up clone with a beforeFind.
     *
     * @return void
     */
    public function testCleanCopyBeforeFind()
    {
        $table = TableRegistry::get('Articles');
        $table->hasMany('Comments');
        $table->eventManager()
            ->attach(function ($event, $query) {
                $query
                    ->limit(5)
                    ->order(['Articles.title' => 'DESC']);
            }, 'Model.beforeFind');

        $query = $table->find();
        $query->offset(10)
            ->limit(1)
            ->order(['Articles.id' => 'DESC'])
            ->contain(['Comments']);
        $copy = $query->cleanCopy();

        $this->assertNotSame($copy, $query);
        $this->assertNull($copy->clause('offset'));
        $this->assertNull($copy->clause('limit'));
        $this->assertNull($copy->clause('order'));
    }

    /**
     * Test that finder options sent through via contain are sent to custom finder.
     *
     * @return void
     */
    public function testContainFinderCanSpecifyOptions()
    {
        $table = TableRegistry::get('Articles');
        $table->belongsTo(
            'Authors',
            ['className' => 'TestApp\Model\Table\AuthorsTable']
        );
        $authorId = 1;

        $resultWithoutAuthor = $table->find('all')
            ->where(['Articles.author_id' => $authorId])
            ->contain([
                'Authors' => [
                    'finder' => ['byAuthor' => ['author_id' => 2]]
                ]
            ]);

        $resultWithAuthor = $table->find('all')
            ->where(['Articles.author_id' => $authorId])
            ->contain([
                'Authors' => [
                    'finder' => ['byAuthor' => ['author_id' => $authorId]]
                ]
            ]);

        $this->assertEmpty($resultWithoutAuthor->first()['author']);
        $this->assertEquals($authorId, $resultWithAuthor->first()['author']['id']);
    }

    /**
     * Tests that it is possible to bind arguments to a query and it will return the right
     * results
     *
     * @return void
     */
    public function testCustomBindings()
    {
        $table = TableRegistry::get('Articles');
        $query = $table->find()->where(['id >' => 1]);
        $query->where(function ($exp) {
            return $exp->add('author_id = :author');
        });
        $query->bind(':author', 1, 'integer');
        $this->assertEquals(1, $query->count());
        $this->assertEquals(3, $query->first()->id);
    }

    /**
     * Tests that it is possible to pass a custom join type for an association when
     * using contain
     *
     * @return void
     */
    public function testContainWithCustomJoinType()
    {
        $table = TableRegistry::get('Articles');
        $table->belongsTo('Authors');

        $articles = $table->find()
            ->contain([
                'Authors' => [
                    'joinType' => 'inner',
                    'conditions' => ['Authors.id' => 3]
                ]
            ])
            ->toArray();
        $this->assertCount(1, $articles);
        $this->assertEquals(3, $articles[0]->author->id);
    }

    /**
     * Tests that it is possible to override the contain strategy using the
     * containments array. In this case, no inner join will be made and for that
     * reason, the parent association will not be filtered as the strategy changed
     * from join to select.
     *
     * @return void
     */
    public function testContainWithStrategyOverride()
    {
        $table = TableRegistry::get('Articles');
        $table->belongsTo('Authors', [
            'joinType' => 'INNER'
        ]);
        $articles = $table->find()
            ->contain([
                'Authors' => [
                    'strategy' => 'select',
                    'conditions' => ['Authors.id' => 3]
                ]
            ])
            ->toArray();
        $this->assertCount(3, $articles);
        $this->assertEquals(3, $articles[1]->author->id);

        $this->assertNull($articles[0]->author);
        $this->assertNull($articles[2]->author);
    }

    /**
     * Tests that it is possible to call matching and contain on the same
     * association.
     *
     * @return void
     */
    public function testMatchingWithContain()
    {
        $query = new Query($this->connection, $this->table);
        $table = TableRegistry::get('authors');
        $table->hasMany('articles');
        TableRegistry::get('articles')->belongsToMany('tags');

        $result = $query->repository($table)
            ->select()
            ->matching('articles.tags', function ($q) {
                return $q->where(['tags.id' => 2]);
            })
            ->contain('articles')
            ->first();

        $this->assertEquals(1, $result->id);
        $this->assertCount(2, $result->articles);
        $this->assertEquals(2, $result->_matchingData['tags']->id);
    }

    /**
     * Tests that it is possible to call matching and contain on the same
     * association with only one level of depth.
     *
     * @return void
     */
    public function testNotSoFarMatchingWithContainOnTheSameAssociation()
    {
        $table = TableRegistry::get('articles');
        $table->belongsToMany('tags');

        $result = $table->find()
            ->matching('tags', function ($q) {
                return $q->where(['tags.id' => 2]);
            })
            ->contain('tags')
            ->first();

        $this->assertEquals(1, $result->id);
        $this->assertCount(2, $result->tags);
        $this->assertEquals(2, $result->_matchingData['tags']->id);
    }
}
