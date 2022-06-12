<?php
declare(strict_types=1);

/**
 * CakePHP(tm) : Rapid Development Framework (https://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 * @link          https://cakephp.org CakePHP(tm) Project
 * @since         3.0.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Test\TestCase\ORM;

use Cake\Cache\Engine\FileEngine;
use Cake\Collection\Collection;
use Cake\Collection\Iterator\BufferedIterator;
use Cake\Database\Driver\Mysql;
use Cake\Database\Driver\Sqlite;
use Cake\Database\DriverInterface;
use Cake\Database\Expression\CommonTableExpression;
use Cake\Database\Expression\IdentifierExpression;
use Cake\Database\Expression\OrderByExpression;
use Cake\Database\Expression\QueryExpression;
use Cake\Database\StatementInterface;
use Cake\Database\TypeMap;
use Cake\Database\ValueBinder;
use Cake\Datasource\ConnectionManager;
use Cake\Event\EventInterface;
use Cake\I18n\FrozenTime;
use Cake\ORM\Association\BelongsTo;
use Cake\ORM\Query;
use Cake\ORM\ResultSet;
use Cake\TestSuite\TestCase;
use InvalidArgumentException;
use ReflectionProperty;
use RuntimeException;

/**
 * Tests Query class
 */
class QueryTest extends TestCase
{
    /**
     * Fixture to be used
     *
     * @var array<string>
     */
    protected $fixtures = [
        'core.Articles',
        'core.Tags',
        'core.ArticlesTags',
        'core.ArticlesTranslations',
        'core.Authors',
        'core.Comments',
        'core.Datatypes',
        'core.Posts',
    ];

    /**
     * @var \Cake\Database\Connection
     */
    protected $connection;

    /**
     * @var \Cake\ORM\Table
     */
    protected $table;

    /**
     * setUp method
     */
    public function setUp(): void
    {
        parent::setUp();
        $this->connection = ConnectionManager::get('test');
        $schema = [
            'id' => ['type' => 'integer'],
            '_constraints' => [
                'primary' => ['type' => 'primary', 'columns' => ['id']],
            ],
        ];
        $schema1 = [
            'id' => ['type' => 'integer'],
            'name' => ['type' => 'string'],
            'phone' => ['type' => 'string'],
            '_constraints' => [
                'primary' => ['type' => 'primary', 'columns' => ['id']],
            ],
        ];
        $schema2 = [
            'id' => ['type' => 'integer'],
            'total' => ['type' => 'string'],
            'placed' => ['type' => 'datetime'],
            '_constraints' => [
                'primary' => ['type' => 'primary', 'columns' => ['id']],
            ],
        ];

        $this->table = $this->getTableLocator()->get('foo', ['schema' => $schema]);
        $clients = $this->getTableLocator()->get('clients', ['schema' => $schema1]);
        $orders = $this->getTableLocator()->get('orders', ['schema' => $schema2]);
        $companies = $this->getTableLocator()->get('companies', ['schema' => $schema, 'table' => 'organizations']);
        $orderTypes = $this->getTableLocator()->get('orderTypes', ['schema' => $schema]);
        $stuff = $this->getTableLocator()->get('stuff', ['schema' => $schema, 'table' => 'things']);
        $stuffTypes = $this->getTableLocator()->get('stuffTypes', ['schema' => $schema]);
        $categories = $this->getTableLocator()->get('categories', ['schema' => $schema]);

        $this->table->belongsTo('clients');
        $clients->hasOne('orders');
        $clients->belongsTo('companies');
        $orders->belongsTo('orderTypes');
        $orders->hasOne('stuff');
        $stuff->belongsTo('stuffTypes');
        $companies->belongsTo('categories');
    }

    /**
     * Data provider for the two types of strategies HasMany implements
     *
     * @return array
     */
    public function strategiesProviderHasMany(): array
    {
        return [['subquery'], ['select']];
    }

    /**
     * Data provider for the two types of strategies BelongsTo implements
     *
     * @return array
     */
    public function strategiesProviderBelongsTo(): array
    {
        return [['join'], ['select']];
    }

    /**
     * Data provider for the two types of strategies BelongsToMany implements
     *
     * @return array
     */
    public function strategiesProviderBelongsToMany(): array
    {
        return [['subquery'], ['select']];
    }

    /**
     * Test getRepository() method.
     */
    public function testGetRepository(): void
    {
        $query = new Query($this->connection, $this->table);

        $result = $query->getRepository();
        $this->assertSame($this->table, $result);
    }

    /**
     * Tests that results are grouped correctly when using contain()
     * and results are not hydrated
     *
     * @dataProvider strategiesProviderBelongsTo
     */
    public function testContainResultFetchingOneLevel(string $strategy): void
    {
        $table = $this->getTableLocator()->get('articles', ['table' => 'articles']);
        $table->belongsTo('authors', ['strategy' => $strategy]);

        $query = new Query($this->connection, $table);
        $results = $query->select()
            ->contain('authors')
            ->enableHydration(false)
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
                    'name' => 'mariano',
                ],
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
                ],
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
                ],
            ],
        ];
        $this->assertEquals($expected, $results);
    }

    /**
     * Tests that HasMany associations are correctly eager loaded and results
     * correctly nested when no hydration is used
     * Also that the query object passes the correct parent model keys to the
     * association objects in order to perform eager loading with select strategy
     *
     * @dataProvider strategiesProviderHasMany
     */
    public function testHasManyEagerLoadingNoHydration(string $strategy): void
    {
        $table = $this->getTableLocator()->get('authors');
        $this->getTableLocator()->get('articles');
        $table->hasMany('articles', [
            'propertyName' => 'articles',
            'strategy' => $strategy,
            'sort' => ['articles.id' => 'asc'],
        ]);
        $query = new Query($this->connection, $table);

        $results = $query->select()
            ->contain('articles')
            ->enableHydration(false)
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
                ],
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
                        'published' => 'Y',
                    ],
                ],
            ],
            [
                'id' => 4,
                'name' => 'garrett',
                'articles' => [],
            ],
        ];
        $this->assertEquals($expected, $results);

        $results = $query->repository($table)
            ->select()
            ->contain(['articles' => ['conditions' => ['articles.id' => 2]]])
            ->enableHydration(false)
            ->toArray();
        $expected[0]['articles'] = [];
        $this->assertEquals($expected, $results);
        $this->assertEquals($table->getAssociation('articles')->getStrategy(), $strategy);
    }

    /**
     * Tests that it is possible to count results containing hasMany associations
     * both hydrating and not hydrating the results.
     *
     * @dataProvider strategiesProviderHasMany
     */
    public function testHasManyEagerLoadingCount(string $strategy): void
    {
        $table = $this->getTableLocator()->get('authors');
        $this->getTableLocator()->get('articles');
        $table->hasMany('articles', [
            'property' => 'articles',
            'strategy' => $strategy,
            'sort' => ['articles.id' => 'asc'],
        ]);
        $query = new Query($this->connection, $table);

        $query = $query->select()
            ->contain('articles');

        $expected = 4;

        $results = $query->enableHydration(false)
            ->count();
        $this->assertEquals($expected, $results);

        $results = $query->enableHydration(true)
            ->count();
        $this->assertEquals($expected, $results);
    }

    /**
     * Tests that it is possible to set fields & order in a hasMany result set
     *
     * @dataProvider strategiesProviderHasMany
     */
    public function testHasManyEagerLoadingFieldsAndOrderNoHydration(string $strategy): void
    {
        $table = $this->getTableLocator()->get('authors');
        $this->getTableLocator()->get('articles');
        $table->hasMany('articles', ['propertyName' => 'articles'] + compact('strategy'));

        $query = new Query($this->connection, $table);
        $results = $query->select()
            ->contain([
                'articles' => [
                    'fields' => ['title', 'author_id'],
                    'sort' => ['articles.id' => 'DESC'],
                ],
            ])
            ->enableHydration(false)
            ->toArray();
        $expected = [
            [
                'id' => 1,
                'name' => 'mariano',
                'articles' => [
                    ['title' => 'Third Article', 'author_id' => 1],
                    ['title' => 'First Article', 'author_id' => 1],
                ],
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
                ],
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
     * @dataProvider strategiesProviderHasMany
     */
    public function testHasManyEagerLoadingDeep(string $strategy): void
    {
        $table = $this->getTableLocator()->get('authors');
        $article = $this->getTableLocator()->get('articles');
        $table->hasMany('articles', [
            'propertyName' => 'articles',
            'strategy' => $strategy,
            'sort' => ['articles.id' => 'asc'],
        ]);
        $article->belongsTo('authors');
        $query = new Query($this->connection, $table);

        $results = $query->select()
            ->contain(['articles' => ['authors']])
            ->enableHydration(false)
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
                        'author' => ['id' => 1, 'name' => 'mariano'],
                    ],
                    [
                        'id' => 3,
                        'title' => 'Third Article',
                        'author_id' => 1,
                        'body' => 'Third Article Body',
                        'published' => 'Y',
                        'author' => ['id' => 1, 'name' => 'mariano'],
                    ],
                ],
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
                        'author' => ['id' => 3, 'name' => 'larry'],
                    ],
                ],
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
     * Tests that hasMany associations can be loaded even when related to a secondary
     * model in the query
     *
     * @dataProvider strategiesProviderHasMany
     */
    public function testHasManyEagerLoadingFromSecondaryTable(string $strategy): void
    {
        $author = $this->getTableLocator()->get('authors');
        $article = $this->getTableLocator()->get('articles');
        $post = $this->getTableLocator()->get('posts');

        $author->hasMany('posts', [
            'sort' => ['posts.id' => 'ASC'],
            'strategy' => $strategy,
        ]);
        $article->belongsTo('authors');

        $query = new Query($this->connection, $article);

        $results = $query->select()
            ->contain(['authors' => ['posts']])
            ->order(['articles.id' => 'ASC'])
            ->enableHydration(false)
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
                    ],
                ],
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
                        ],
                    ],
                ],
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
                    ],
                ],
            ],
        ];
        $this->assertEquals($expected, $results);
    }

    /**
     * Tests that BelongsToMany associations are correctly eager loaded.
     * Also that the query object passes the correct parent model keys to the
     * association objects in order to perform eager loading with select strategy
     *
     * @dataProvider strategiesProviderBelongsToMany
     */
    public function testBelongsToManyEagerLoadingNoHydration(string $strategy): void
    {
        $table = $this->getTableLocator()->get('Articles');
        $this->getTableLocator()->get('Tags');
        $this->getTableLocator()->get('ArticlesTags', [
            'table' => 'articles_tags',
        ]);
        $table->belongsToMany('Tags', [
            'strategy' => $strategy,
            'sort' => 'tag_id',
        ]);
        $query = new Query($this->connection, $table);

        $results = $query->select()->contain('Tags')->disableHydration()->toArray();
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
                        '_joinData' => ['article_id' => 1, 'tag_id' => 1],
                        'description' => 'A big description',
                        'created' => new FrozenTime('2016-01-01 00:00'),
                    ],
                    [
                        'id' => 2,
                        'name' => 'tag2',
                        '_joinData' => ['article_id' => 1, 'tag_id' => 2],
                        'description' => 'Another big description',
                        'created' => new FrozenTime('2016-01-01 00:00'),
                    ],
                ],
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
                        '_joinData' => ['article_id' => 2, 'tag_id' => 1],
                        'description' => 'A big description',
                        'created' => new FrozenTime('2016-01-01 00:00'),
                    ],
                    [
                        'id' => 3,
                        'name' => 'tag3',
                        '_joinData' => ['article_id' => 2, 'tag_id' => 3],
                        'description' => 'Yet another one',
                        'created' => new FrozenTime('2016-01-01 00:00'),
                    ],
                ],
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
            ->disableHydration()
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
                        '_joinData' => ['article_id' => 2, 'tag_id' => 3],
                        'description' => 'Yet another one',
                        'created' => new FrozenTime('2016-01-01 00:00'),
                    ],
                ],
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
        $this->assertEquals($table->getAssociation('Tags')->getStrategy(), $strategy);
    }

    /**
     * Tests that tables results can be filtered by the result of a HasMany
     */
    public function testFilteringByHasManyNoHydration(): void
    {
        $query = new Query($this->connection, $this->table);
        $table = $this->getTableLocator()->get('Articles');
        $table->hasMany('Comments');

        $results = $query->repository($table)
            ->select()
            ->disableHydration()
            ->matching('Comments', function ($q) {
                return $q->where(['Comments.user_id' => 4]);
            })
            ->toArray();
        $expected = [
            [
                'id' => 1,
                'title' => 'First Article',
                'body' => 'First Article Body',
                'author_id' => 1,
                'published' => 'Y',
                '_matchingData' => [
                    'Comments' => [
                        'id' => 2,
                        'article_id' => 1,
                        'user_id' => 4,
                        'comment' => 'Second Comment for First Article',
                        'published' => 'Y',
                        'created' => new FrozenTime('2007-03-18 10:47:23'),
                        'updated' => new FrozenTime('2007-03-18 10:49:31'),
                    ],
                ],
            ],
        ];
        $this->assertEquals($expected, $results);
    }

    /**
     * Tests that tables results can be filtered by the result of a HasMany
     */
    public function testFilteringByHasManyHydration(): void
    {
        $table = $this->getTableLocator()->get('Articles');
        $query = new Query($this->connection, $table);
        $table->hasMany('Comments');

        $result = $query->repository($table)
            ->matching('Comments', function ($q) {
                return $q->where(['Comments.user_id' => 4]);
            })
            ->first();
        $this->assertInstanceOf('Cake\ORM\Entity', $result);
        $this->assertInstanceOf('Cake\ORM\Entity', $result->_matchingData['Comments']);
        $this->assertIsInt($result->_matchingData['Comments']->id);
        $this->assertInstanceOf(FrozenTime::class, $result->_matchingData['Comments']->created);
    }

    /**
     * Tests that BelongsToMany associations are correctly eager loaded.
     * Also that the query object passes the correct parent model keys to the
     * association objects in order to perform eager loading with select strategy
     */
    public function testFilteringByBelongsToManyNoHydration(): void
    {
        $query = new Query($this->connection, $this->table);
        $table = $this->getTableLocator()->get('Articles');
        $this->getTableLocator()->get('Tags');
        $this->getTableLocator()->get('ArticlesTags', [
            'table' => 'articles_tags',
        ]);
        $table->belongsToMany('Tags');

        $results = $query->repository($table)->select()
            ->matching('Tags', function ($q) {
                return $q->where(['Tags.id' => 3]);
            })
            ->enableHydration(false)
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
                        'description' => 'Yet another one',
                        'created' => new FrozenTime('2016-01-01 00:00'),
                    ],
                    'ArticlesTags' => ['article_id' => 2, 'tag_id' => 3],
                ],
            ],
        ];
        $this->assertEquals($expected, $results);

        $query = new Query($this->connection, $table);
        $results = $query->select()
            ->matching('Tags', function ($q) {
                return $q->where(['Tags.name' => 'tag2']);
            })
            ->enableHydration(false)
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
                        'description' => 'Another big description',
                        'created' => new FrozenTime('2016-01-01 00:00'),
                    ],
                    'ArticlesTags' => ['article_id' => 1, 'tag_id' => 2],
                ],
            ],
        ];
        $this->assertEquals($expected, $results);
    }

    /**
     * Tests that it is possible to filter by deep associations
     */
    public function testMatchingDotNotation(): void
    {
        $query = new Query($this->connection, $this->table);
        $table = $this->getTableLocator()->get('authors');
        $this->getTableLocator()->get('articles');
        $table->hasMany('articles');
        $this->getTableLocator()->get('articles')->belongsToMany('tags');

        $results = $query->repository($table)
            ->select()
            ->enableHydration(false)
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
                        'description' => 'Another big description',
                        'created' => new FrozenTime('2016-01-01 00:00'),
                    ],
                    'articles' => [
                        'id' => 1,
                        'author_id' => 1,
                        'title' => 'First Article',
                        'body' => 'First Article Body',
                        'published' => 'Y',
                    ],
                    'ArticlesTags' => [
                        'article_id' => 1,
                        'tag_id' => 2,
                    ],
                ],
            ],
        ];
        $this->assertEquals($expected, $results);
    }

    /**
     * Test setResult()
     */
    public function testSetResult(): void
    {
        $query = new Query($this->connection, $this->table);

        $stmt = $this->getMockBuilder(StatementInterface::class)->getMock();
        $stmt->method('rowCount')
            ->will($this->returnValue(9));
        $results = new ResultSet($query, $stmt);
        $query->setResult($results);
        $this->assertSame($results, $query->all());
    }

    /**
     * Test clearResult()
     */
    public function testClearResult(): void
    {
        $article = $this->getTableLocator()->get('articles');
        $query = new Query($this->connection, $article);

        $firstCount = $query->count();
        $firstResults = $query->toArray();

        $this->assertEquals(3, $firstCount);
        $this->assertCount(3, $firstResults);

        $article->delete(reset($firstResults));
        $return = $query->clearResult();

        $this->assertSame($return, $query);

        $secondCount = $query->count();
        $secondResults = $query->toArray();

        $this->assertEquals(2, $secondCount);
        $this->assertCount(2, $secondResults);
    }

    /**
     * Tests that applying array options to a query will convert them
     * to equivalent function calls with the correspondent array values
     */
    public function testApplyOptions(): void
    {
        $this->table->belongsTo('articles');
        $typeMap = new TypeMap([
            'foo.id' => 'integer',
            'id' => 'integer',
            'foo__id' => 'integer',
            'articles.id' => 'integer',
            'articles__id' => 'integer',
            'articles.author_id' => 'integer',
            'articles__author_id' => 'integer',
            'author_id' => 'integer',
            'articles.title' => 'string',
            'articles__title' => 'string',
            'title' => 'string',
            'articles.body' => 'text',
            'articles__body' => 'text',
            'body' => 'text',
            'articles.published' => 'string',
            'articles__published' => 'string',
            'published' => 'string',
        ]);

        $options = [
            'fields' => ['field_a', 'field_b'],
            'conditions' => ['field_a' => 1, 'field_b' => 'something'],
            'limit' => 1,
            'order' => ['a' => 'ASC'],
            'offset' => 5,
            'group' => ['field_a'],
            'having' => ['field_a >' => 100],
            'contain' => ['articles'],
            'join' => ['table_a' => ['conditions' => ['a > b']]],
        ];
        $query = new Query($this->connection, $this->table);
        $query->applyOptions($options);

        $this->assertEquals(['field_a', 'field_b'], $query->clause('select'));

        $expected = new QueryExpression($options['conditions'], $typeMap);
        $result = $query->clause('where');
        $this->assertEquals($expected, $result);

        $this->assertEquals(1, $query->clause('limit'));

        $expected = new QueryExpression(['a > b'], $typeMap);
        $result = $query->clause('join');
        $this->assertEquals([
            'table_a' => ['alias' => 'table_a', 'type' => 'INNER', 'conditions' => $expected],
        ], $result);

        $expected = new OrderByExpression(['a' => 'ASC']);
        $this->assertEquals($expected, $query->clause('order'));

        $this->assertEquals(5, $query->clause('offset'));
        $this->assertEquals(['field_a'], $query->clause('group'));

        $expected = new QueryExpression($options['having'], $typeMap);
        $this->assertEquals($expected, $query->clause('having'));

        $expected = ['articles' => []];
        $this->assertEquals($expected, $query->getContain());
    }

    /**
     * Test that page is applied after limit.
     */
    public function testApplyOptionsPageIsLast(): void
    {
        $query = new Query($this->connection, $this->table);
        $opts = [
            'page' => 3,
            'limit' => 5,
        ];
        $query->applyOptions($opts);
        $this->assertEquals(5, $query->clause('limit'));
        $this->assertEquals(10, $query->clause('offset'));
    }

    /**
     * ApplyOptions should ignore null values.
     */
    public function testApplyOptionsIgnoreNull(): void
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
     */
    public function testGetOptions(): void
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
     */
    public function testMapReduceOnlyMapper(): void
    {
        $mapper1 = function (): void {
        };
        $mapper2 = function (): void {
        };
        $query = new Query($this->connection, $this->table);
        $this->assertSame($query, $query->mapReduce($mapper1));
        $this->assertEquals(
            [['mapper' => $mapper1, 'reducer' => null]],
            $query->getMapReducers()
        );

        $this->assertEquals($query, $query->mapReduce($mapper2));
        $result = $query->getMapReducers();
        $this->assertSame(
            [
                ['mapper' => $mapper1, 'reducer' => null],
                ['mapper' => $mapper2, 'reducer' => null],
            ],
            $result
        );
    }

    /**
     * Tests registering mappers and reducers with mapReduce()
     */
    public function testMapReduceBothMethods(): void
    {
        $mapper1 = function (): void {
        };
        $mapper2 = function (): void {
        };
        $reducer1 = function (): void {
        };
        $reducer2 = function (): void {
        };
        $query = new Query($this->connection, $this->table);
        $this->assertSame($query, $query->mapReduce($mapper1, $reducer1));
        $this->assertEquals(
            [['mapper' => $mapper1, 'reducer' => $reducer1]],
            $query->getMapReducers()
        );

        $this->assertSame($query, $query->mapReduce($mapper2, $reducer2));
        $this->assertEquals(
            [
                ['mapper' => $mapper1, 'reducer' => $reducer1],
                ['mapper' => $mapper2, 'reducer' => $reducer2],
            ],
            $query->getMapReducers()
        );
    }

    /**
     * Tests that it is possible to overwrite previous map reducers
     */
    public function testOverwriteMapReduce(): void
    {
        $mapper1 = function (): void {
        };
        $mapper2 = function (): void {
        };
        $reducer1 = function (): void {
        };
        $reducer2 = function (): void {
        };
        $query = new Query($this->connection, $this->table);
        $this->assertEquals($query, $query->mapReduce($mapper1, $reducer1));
        $this->assertEquals(
            [['mapper' => $mapper1, 'reducer' => $reducer1]],
            $query->getMapReducers()
        );

        $this->assertEquals($query, $query->mapReduce($mapper2, $reducer2, true));
        $this->assertEquals(
            [['mapper' => $mapper2, 'reducer' => $reducer2]],
            $query->getMapReducers()
        );
    }

    /**
     * Tests that multiple map reducers can be stacked
     */
    public function testResultsAreWrappedInMapReduce(): void
    {
        $table = $this->getTableLocator()->get('articles', ['table' => 'articles']);
        $query = new Query($this->connection, $table);
        $query->select(['a' => 'id'])->limit(2)->order(['id' => 'ASC']);
        $query->mapReduce(function ($v, $k, $mr): void {
            $mr->emit($v['a']);
        });
        $query->mapReduce(
            function ($v, $k, $mr): void {
                $mr->emitIntermediate($v, $k);
            },
            function ($v, $k, $mr): void {
                $mr->emit($v[0] + 1);
            }
        );

        $this->assertEquals([2, 3], iterator_to_array($query->all()));
    }

    /**
     * Tests first() method when the query has not been executed before
     */
    public function testFirstDirtyQuery(): void
    {
        $table = $this->getTableLocator()->get('articles', ['table' => 'articles']);
        $query = new Query($this->connection, $table);
        $result = $query->select(['id'])->enableHydration(false)->first();
        $this->assertEquals(['id' => 1], $result);
        $this->assertEquals(1, $query->clause('limit'));
        $result = $query->select(['id'])->first();
        $this->assertEquals(['id' => 1], $result);
    }

    /**
     * Tests that first can be called again on an already executed query
     */
    public function testFirstCleanQuery(): void
    {
        $table = $this->getTableLocator()->get('articles', ['table' => 'articles']);
        $query = new Query($this->connection, $table);
        $query->select(['id'])->toArray();

        $first = $query->enableHydration(false)->first();
        $this->assertEquals(['id' => 1], $first);
        $this->assertEquals(1, $query->clause('limit'));
    }

    /**
     * Tests that first() will not execute the same query twice
     */
    public function testFirstSameResult(): void
    {
        $table = $this->getTableLocator()->get('articles', ['table' => 'articles']);
        $query = new Query($this->connection, $table);
        $query->select(['id'])->toArray();

        $first = $query->enableHydration(false)->first();
        $resultSet = $query->all();
        $this->assertEquals(['id' => 1], $first);
        $this->assertSame($resultSet, $query->all());
    }

    /**
     * Tests that first can be called against a query with a mapReduce
     */
    public function testFirstMapReduce(): void
    {
        $map = function ($row, $key, $mapReduce): void {
            $mapReduce->emitIntermediate($row['id'], 'id');
        };
        $reduce = function ($values, $key, $mapReduce): void {
            $mapReduce->emit(array_sum($values));
        };

        $table = $this->getTableLocator()->get('articles', ['table' => 'articles']);
        $query = new Query($this->connection, $table);
        $query->select(['id'])
            ->enableHydration(false)
            ->mapReduce($map, $reduce);

        $first = $query->first();
        $this->assertEquals(1, $first);
    }

    /**
     * Tests that first can be called on an unbuffered query
     */
    public function testFirstUnbuffered(): void
    {
        $table = $this->getTableLocator()->get('Articles');
        $query = new Query($this->connection, $table);
        $query->select(['id']);

        $first = $query->enableHydration(false)
            ->enableBufferedResults(false)->first();

        $this->assertEquals(['id' => 1], $first);
    }

    /**
     * Test to show that when results bufferring is enabled if ResultSet gets
     * decorated by ResultSetDecorator it gets wrapped in a BufferedIterator instance.
     */
    public function testBufferedDecoratedResultSet(): void
    {
        $table = $this->getTableLocator()->get('Articles');
        $query = new Query($this->connection, $table);
        $query
            ->select(['id'])
            // This causes ResultSet to be decorated by a ResultSetDecorator instance
            ->formatResults(function ($results) {
                return $results;
            });

        $results = $query->all();

        $this->assertInstanceOf(BufferedIterator::class, $results->getInnerIterator());
    }

    /**
     * Testing hydrating a result set into Entity objects
     */
    public function testHydrateSimple(): void
    {
        $table = $this->getTableLocator()->get('articles', ['table' => 'articles']);
        $query = new Query($this->connection, $table);
        $results = $query->select()->toArray();

        $this->assertCount(3, $results);
        foreach ($results as $r) {
            $this->assertInstanceOf('Cake\ORM\Entity', $r);
        }

        $first = $results[0];
        $this->assertEquals(1, $first->id);
        $this->assertEquals(1, $first->author_id);
        $this->assertSame('First Article', $first->title);
        $this->assertSame('First Article Body', $first->body);
        $this->assertSame('Y', $first->published);
    }

    /**
     * Tests that has many results are also hydrated correctly
     */
    public function testHydrateHasMany(): void
    {
        $table = $this->getTableLocator()->get('authors');
        $this->getTableLocator()->get('articles');
        $table->hasMany('articles', [
            'propertyName' => 'articles',
            'sort' => ['articles.id' => 'asc'],
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
     */
    public function testHydrateBelongsToMany(): void
    {
        $table = $this->getTableLocator()->get('Articles');
        $this->getTableLocator()->get('Tags');
        $this->getTableLocator()->get('ArticlesTags', [
            'table' => 'articles_tags',
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
            '_joinData' => ['article_id' => 1, 'tag_id' => 1],
            'description' => 'A big description',
            'created' => new FrozenTime('2016-01-01 00:00'),
        ];
        $this->assertEquals($expected, $first->tags[0]->toArray());
        $this->assertInstanceOf(FrozenTime::class, $first->tags[0]->created);

        $expected = [
            'id' => 2,
            'name' => 'tag2',
            '_joinData' => ['article_id' => 1, 'tag_id' => 2],
            'description' => 'Another big description',
            'created' => new FrozenTime('2016-01-01 00:00'),
        ];
        $this->assertEquals($expected, $first->tags[1]->toArray());
        $this->assertInstanceOf(FrozenTime::class, $first->tags[1]->created);
    }

    /**
     * Tests that belongsToMany associations are also correctly hydrated
     */
    public function testFormatResultsBelongsToMany(): void
    {
        $table = $this->getTableLocator()->get('Articles');
        $this->getTableLocator()->get('Tags');
        $articlesTags = $this->getTableLocator()->get('ArticlesTags', [
            'table' => 'articles_tags',
        ]);
        $table->belongsToMany('Tags');

        $articlesTags
            ->getEventManager()
            ->on('Model.beforeFind', function (EventInterface $event, $query): void {
                $query->formatResults(function ($results) {
                    foreach ($results as $result) {
                        $result->beforeFind = true;
                    }

                    return $results;
                });
            });

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
            '_joinData' => [
                'article_id' => 1,
                'tag_id' => 1,
                'beforeFind' => true,
            ],
            'description' => 'A big description',
            'created' => new FrozenTime('2016-01-01 00:00'),
        ];
        $this->assertEquals($expected, $first->tags[0]->toArray());
        $this->assertInstanceOf(FrozenTime::class, $first->tags[0]->created);

        $expected = [
            'id' => 2,
            'name' => 'tag2',
            '_joinData' => [
                'article_id' => 1,
                'tag_id' => 2,
                'beforeFind' => true,
            ],
            'description' => 'Another big description',
            'created' => new FrozenTime('2016-01-01 00:00'),
        ];
        $this->assertEquals($expected, $first->tags[1]->toArray());
        $this->assertInstanceOf(FrozenTime::class, $first->tags[0]->created);
    }

    /**
     * Tests that belongsTo relations are correctly hydrated
     *
     * @dataProvider strategiesProviderBelongsTo
     */
    public function testHydrateBelongsTo(string $strategy): void
    {
        $table = $this->getTableLocator()->get('articles');
        $this->getTableLocator()->get('authors');
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
     * @dataProvider strategiesProviderBelongsTo
     */
    public function testHydrateDeep(string $strategy): void
    {
        $table = $this->getTableLocator()->get('authors');
        $article = $this->getTableLocator()->get('articles');
        $table->hasMany('articles', [
            'propertyName' => 'articles',
            'sort' => ['articles.id' => 'asc'],
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
     */
    public function testHydrateCustomObject(): void
    {
        $class = $this->getMockClass('Cake\ORM\Entity', ['fakeMethod']);
        $table = $this->getTableLocator()->get('articles', [
            'table' => 'articles',
            'entityClass' => '\\' . $class,
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
        $this->assertSame('First Article', $first->title);
        $this->assertSame('First Article Body', $first->body);
        $this->assertSame('Y', $first->published);
    }

    /**
     * Tests that has many results are also hydrated correctly
     * when specified a custom entity class
     */
    public function testHydrateHasManyCustomEntity(): void
    {
        $authorEntity = $this->getMockClass('Cake\ORM\Entity', ['foo']);
        $articleEntity = $this->getMockClass('Cake\ORM\Entity', ['foo']);
        $table = $this->getTableLocator()->get('authors', [
            'entityClass' => '\\' . $authorEntity,
        ]);
        $this->getTableLocator()->get('articles', [
            'entityClass' => '\\' . $articleEntity,
        ]);
        $table->hasMany('articles', [
            'propertyName' => 'articles',
            'sort' => ['articles.id' => 'asc'],
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
     */
    public function testHydrateBelongsToCustomEntity(): void
    {
        $authorEntity = $this->getMockClass('Cake\ORM\Entity', ['foo']);
        $table = $this->getTableLocator()->get('articles');
        $this->getTableLocator()->get('authors', [
            'entityClass' => '\\' . $authorEntity,
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
     */
    public function testCount(): void
    {
        $table = $this->getTableLocator()->get('articles');
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
     * Test getting counts from queries with contain.
     */
    public function testCountWithContain(): void
    {
        $table = $this->getTableLocator()->get('Articles');
        $table->belongsTo('Authors');

        $result = $table->find('all')
            ->contain([
                'Authors' => [
                    'fields' => ['name'],
                ],
            ])
            ->count();
        $this->assertSame(3, $result);
    }

    /**
     * Test getting counts from queries with contain.
     */
    public function testCountWithSubselect(): void
    {
        $table = $this->getTableLocator()->get('Articles');
        $table->belongsTo('Authors');
        $table->hasMany('ArticlesTags');

        $counter = $table->ArticlesTags->find();
        $counter->select([
            'total' => $counter->func()->count('*'),
        ])
            ->where([
                'ArticlesTags.tag_id' => 1,
                'ArticlesTags.article_id' => new IdentifierExpression('Articles.id'),
            ]);

        $result = $table->find('all')
            ->select([
                'Articles.title',
                'tag_count' => $counter,
            ])
            ->matching('Authors', function ($q) {
                return $q->where(['Authors.id' => 1]);
            })
            ->count();
        $this->assertSame(2, $result);
    }

    /**
     * Test getting counts with complex fields.
     */
    public function testCountWithExpressions(): void
    {
        $table = $this->getTableLocator()->get('Articles');
        $query = $table->find();
        $query->select([
            'title' => $query->func()->concat(
                ['title' => 'identifier', 'test'],
                ['string']
            ),
        ]);
        $query->where(['id' => 1]);
        $this->assertCount(1, $query->all());
        $this->assertEquals(1, $query->count());
    }

    /**
     * test count with a beforeFind.
     */
    public function testCountBeforeFind(): void
    {
        $table = $this->getTableLocator()->get('Articles');
        $table->hasMany('Comments');
        $table->getEventManager()
            ->on('Model.beforeFind', function (EventInterface $event, $query): void {
                $query
                    ->limit(1)
                    ->order(['Articles.title' => 'DESC']);
            });

        $query = $table->find();
        $result = $query->count();
        $this->assertSame(3, $result);
    }

    /**
     * Tests that beforeFind is only ever called once, even if you trigger it again in the beforeFind
     */
    public function testBeforeFindCalledOnce(): void
    {
        $callCount = 0;
        $table = $this->getTableLocator()->get('Articles');
        $table->getEventManager()
            ->on('Model.beforeFind', function (EventInterface $event, $query) use (&$callCount): void {
                $valueBinder = new ValueBinder();
                $query->sql($valueBinder);
                $callCount++;
            });

        $query = $table->find();
        $valueBinder = new ValueBinder();
        $query->sql($valueBinder);
        $this->assertSame(1, $callCount);
    }

    /**
     * Test that count() returns correct results with group by.
     */
    public function testCountWithGroup(): void
    {
        $table = $this->getTableLocator()->get('articles');
        $query = $table->find('all');
        $query->select(['author_id', 's' => $query->func()->sum('id')])
            ->group(['author_id']);
        $result = $query->count();
        $this->assertEquals(2, $result);
    }

    /**
     * Tests that it is possible to provide a callback for calculating the count
     * of a query
     */
    public function testCountWithCustomCounter(): void
    {
        $table = $this->getTableLocator()->get('articles');
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
     * Test that RAND() returns correct results.
     */
    public function testSelectRandom(): void
    {
        $table = $this->getTableLocator()->get('articles');
        $query = $table
            ->query();

        $query->select(['s' => $query->func()->rand()]);
        $result = $query
            ->all()
            ->extract('s')
            ->first();

        $this->assertGreaterThanOrEqual(0, $result);
        $this->assertLessThan(1, $result);
    }

    /**
     * Test update method.
     */
    public function testUpdate(): void
    {
        $table = $this->getTableLocator()->get('articles');

        $result = $table->query()
            ->update()
            ->set(['title' => 'First'])
            ->execute();

        $this->assertInstanceOf('Cake\Database\StatementInterface', $result);
        $this->assertGreaterThan(0, $result->rowCount());
    }

    /**
     * Test update method.
     */
    public function testUpdateWithTableExpression(): void
    {
        $this->skipIf(!$this->connection->getDriver() instanceof Mysql);
        $table = $this->getTableLocator()->get('articles');

        $query = $table->query();
        $result = $query->update($query->newExpr('articles, authors'))
            ->set(['title' => 'First'])
            ->where(['articles.author_id = authors.id'])
            ->andWhere(['authors.name' => 'mariano'])
            ->execute();

        $this->assertInstanceOf('Cake\Database\StatementInterface', $result);
        $this->assertGreaterThan(0, $result->rowCount());
    }

    /**
     * Test insert method.
     */
    public function testInsert(): void
    {
        $table = $this->getTableLocator()->get('articles');

        $result = $table->query()
            ->insert(['title'])
            ->values(['title' => 'First'])
            ->values(['title' => 'Second'])
            ->execute();

        $result->closeCursor();

        $this->assertInstanceOf('Cake\Database\StatementInterface', $result);
        $this->assertEquals(2, $result->rowCount());
    }

    /**
     * Test delete method.
     */
    public function testDelete(): void
    {
        $table = $this->getTableLocator()->get('articles');

        $result = $table->query()
            ->delete()
            ->where(['id >=' => 1])
            ->execute();

        $this->assertInstanceOf('Cake\Database\StatementInterface', $result);
        $this->assertGreaterThan(0, $result->rowCount());
    }

    /**
     * Provides a list of collection methods that can be proxied
     * from the query
     *
     * @return array
     */
    public function collectionMethodsProvider(): array
    {
        $identity = function ($a) {
            return $a;
        };
        $collection = new Collection([]);

        return [
            ['filter', $identity, $collection],
            ['reject', $identity, $collection],
            ['every', $identity, false],
            ['some', $identity, false],
            ['contains', $identity, true],
            ['map', $identity, $collection],
            ['reduce', $identity, $collection],
            ['extract', $identity, $collection],
            ['max', $identity, 9],
            ['min', $identity, 1],
            ['sortBy', $identity, $collection],
            ['groupBy', $identity, $collection],
            ['countBy', $identity, $collection],
            ['shuffle', $identity, $collection],
            ['sample', 10, $collection],
            ['take', 1, $collection],
            ['append', new \ArrayIterator(), $collection],
            ['compile', true, $collection],
            ['isEmpty', true, true],
        ];
    }

    /**
     * testClearContain
     */
    public function testClearContain(): void
    {
        /** @var \Cake\ORM\Query $query */
        $query = $this->getMockBuilder('Cake\ORM\Query')
            ->onlyMethods(['all'])
            ->setConstructorArgs([$this->connection, $this->table])
            ->getMock();

        $query->contain([
            'Articles',
        ]);

        $result = $query->getContain();
        $this->assertIsArray($result);
        $this->assertNotEmpty($result);

        $result = $query->clearContain();
        $this->assertInstanceOf(Query::class, $result);

        $result = $query->getContain();
        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }

    /**
     * Tests that query can proxy collection methods
     *
     * @dataProvider collectionMethodsProvider
     * @param mixed $arg
     * @param mixed $return
     */
    public function testDeprecatedCollectionProxy(string $method, $arg, $return): void
    {
        $query = $this->getMockBuilder('Cake\ORM\Query')
            ->onlyMethods(['all'])
            ->setConstructorArgs([$this->connection, $this->table])
            ->getMock();
        $query->select();
        $resultSet = $this->getMockbuilder('Cake\ORM\ResultSet')
            ->onlyMethods([$method])
            ->setConstructorArgs([$query, $this->getMockBuilder(StatementInterface::class)->getMock()])
            ->getMock();
        $query->expects($this->once())
            ->method('all')
            ->will($this->returnValue($resultSet));
        $resultSet->expects($this->once())
            ->method($method)
            ->with($arg, 99)
            ->will($this->returnValue($return));

        $this->deprecated(function () use ($return, $query, $method, $arg) {
            $this->assertSame($return, $query->{$method}($arg, 99));
        });
    }

    /**
     * Tests deprecation path for proxy collection methods.
     *
     * @dataProvider collectionMethodsProvider
     */
    public function testDeprecatedPathCollectionProxy(string $method, $arg, $return): void
    {
        $this->expectDeprecation();
        $this->expectDeprecationMessage("Calling `Cake\Datasource\ResultSetInterface` methods, such as `$method()`, on queries is deprecated.");

        $query = $this->getMockBuilder('Cake\ORM\Query')
            ->onlyMethods(['all'])
            ->setConstructorArgs([$this->connection, $this->table])
            ->getMock();
        $query->select();

        $this->assertSame($return, $query->{$method}($arg, 99));
    }

    /**
     * Tests that calling an nonexistent method in query throws an
     * exception
     */
    public function testCollectionProxyBadMethod(): void
    {
        $this->expectException(\BadMethodCallException::class);
        $this->expectExceptionMessage('Unknown method "derpFilter"');
        $this->getTableLocator()->get('articles')->find('all')->derpFilter();
    }

    /**
     * cache() should fail on non select queries.
     */
    public function testCacheErrorOnNonSelect(): void
    {
        $this->expectException(RuntimeException::class);
        $table = $this->getTableLocator()->get('articles', ['table' => 'articles']);
        $query = new Query($this->connection, $table);
        $query->insert(['test']);
        $query->cache('my_key');
    }

    /**
     * Integration test for query caching.
     */
    public function testCacheReadIntegration(): void
    {
        $query = $this->getMockBuilder('Cake\ORM\Query')
            ->onlyMethods(['execute'])
            ->setConstructorArgs([$this->connection, $this->table])
            ->getMock();
        $resultSet = $this->getMockBuilder('Cake\ORM\ResultSet')
            ->setConstructorArgs([$query, $this->getMockBuilder(StatementInterface::class)->getMock()])
            ->getMock();

        $query->expects($this->never())
            ->method('execute');

        $cacher = $this->getMockBuilder('Cake\Cache\CacheEngine')->getMock();
        $cacher->expects($this->once())
            ->method('get')
            ->with('my_key')
            ->will($this->returnValue($resultSet));

        $query->cache('my_key', $cacher)
            ->where(['id' => 1]);

        $results = $query->all();
        $this->assertSame($resultSet, $results);
    }

    /**
     * Integration test for query caching.
     */
    public function testCacheWriteIntegration(): void
    {
        $table = $this->getTableLocator()->get('Articles');
        $query = new Query($this->connection, $table);

        $query->select(['id', 'title']);

        $cacher = $this->getMockBuilder('Cake\Cache\CacheEngine')->getMock();
        $cacher->expects($this->once())
            ->method('set')
            ->with(
                'my_key',
                $this->isInstanceOf('Cake\Datasource\ResultSetInterface')
            );

        $query->cache('my_key', $cacher)
            ->where(['id' => 1]);

        $query->all();
    }

    /**
     * Integration test for query caching using a real cache engine and
     * a formatResults callback
     */
    public function testCacheIntegrationWithFormatResults(): void
    {
        $table = $this->getTableLocator()->get('Articles');
        $query = new Query($this->connection, $table);
        $cacher = new FileEngine();
        $cacher->init();

        $query
            ->select(['id', 'title'])
            ->formatResults(function ($results) {
                return $results->combine('id', 'title');
            })
            ->cache('my_key', $cacher);

        $expected = $query->toArray();
        $query = new Query($this->connection, $table);
        $results = $query->cache('my_key', $cacher)->toArray();
        $this->assertSame($expected, $results);
    }

    /**
     * Test overwriting the contained associations.
     */
    public function testContainOverwrite(): void
    {
        $table = $this->getTableLocator()->get('Articles');
        $table->hasMany('Comments');
        $table->belongsTo('Authors');

        $query = $table->find();
        $query->contain(['Comments']);
        $this->assertEquals(['Comments'], array_keys($query->getContain()));

        $query->contain(['Authors'], true);
        $this->assertEquals(['Authors'], array_keys($query->getContain()));

        $query->contain(['Comments', 'Authors'], true);
        $this->assertEquals(['Comments', 'Authors'], array_keys($query->getContain()));
    }

    /**
     * Integration test to show filtering associations using contain and a closure
     */
    public function testContainWithClosure(): void
    {
        $table = $this->getTableLocator()->get('authors');
        $table->hasMany('articles');
        $query = new Query($this->connection, $table);
        $query
            ->select()
            ->contain([
                'articles' => function ($q) {
                    return $q->where(['articles.id' => 1]);
                },
            ]);

        $ids = [];
        foreach ($query as $entity) {
            foreach ((array)$entity->articles as $article) {
                $ids[] = $article->id;
            }
        }
        $this->assertEquals([1], array_unique($ids));
    }

    /**
     * Integration test that uses the contain signature that is the same as the
     * matching signature
     */
    public function testContainClosureSignature(): void
    {
        $table = $this->getTableLocator()->get('authors');
        $table->hasMany('articles');
        $query = new Query($this->connection, $table);
        $query
            ->select()
            ->contain('articles', function ($q) {
                return $q->where(['articles.id' => 1]);
            });

        $ids = [];
        foreach ($query as $entity) {
            foreach ((array)$entity->articles as $article) {
                $ids[] = $article->id;
            }
        }
        $this->assertEquals([1], array_unique($ids));
    }

    public function testContainAutoFields(): void
    {
        $table = $this->getTableLocator()->get('authors');
        $table->hasMany('articles');
        $query = new Query($this->connection, $table);
        $query
            ->select()
            ->contain('articles', function ($q) {
                return $q->select(['test' => '(SELECT 20)'])
                    ->enableAutoFields(true);
            });
        $results = $query->toArray();
        $this->assertNotEmpty($results);
    }

    /**
     * Integration test to ensure that filtering associations with the queryBuilder
     * option works.
     */
    public function testContainWithQueryBuilderHasManyError(): void
    {
        $this->expectException(RuntimeException::class);
        $table = $this->getTableLocator()->get('Authors');
        $table->hasMany('Articles');
        $query = new Query($this->connection, $table);
        $query->select()
            ->contain([
                'Articles' => [
                    'foreignKey' => false,
                    'queryBuilder' => function ($q) {
                        return $q->where(['articles.id' => 1]);
                    },
                ],
            ]);
        $query->toArray();
    }

    /**
     * Integration test to ensure that filtering associations with the queryBuilder
     * option works.
     */
    public function testContainWithQueryBuilderJoinableAssociation(): void
    {
        $table = $this->getTableLocator()->get('Authors');
        $table->hasOne('Articles');
        $query = new Query($this->connection, $table);
        $query->select()
            ->contain([
                'Articles' => [
                    'foreignKey' => false,
                    'queryBuilder' => function ($q) {
                        return $q->where(['Articles.id' => 1]);
                    },
                ],
            ]);
        $result = $query->toArray();
        $this->assertEquals(1, $result[0]->article->id);
        $this->assertEquals(1, $result[1]->article->id);

        $articles = $this->getTableLocator()->get('Articles');
        $articles->belongsTo('Authors');
        $query = new Query($this->connection, $articles);
        $query->select()
            ->contain([
                'Authors' => [
                    'foreignKey' => false,
                    'queryBuilder' => function ($q) {
                        return $q->where(['Authors.id' => 1]);
                    },
                ],
            ]);
        $result = $query->toArray();
        $this->assertEquals(1, $result[0]->author->id);
    }

    /**
     * Test containing associations that have empty conditions.
     */
    public function testContainAssociationWithEmptyConditions(): void
    {
        $articles = $this->getTableLocator()->get('Articles');
        $articles->belongsTo('Authors', [
            'conditions' => function ($exp, $query) {
                return $exp;
            },
        ]);
        $query = $articles->find('all')->contain(['Authors']);
        $result = $query->toArray();
        $this->assertCount(3, $result);
    }

    /**
     * Tests the formatResults method
     */
    public function testFormatResults(): void
    {
        $callback1 = function (): void {
        };
        $callback2 = function (): void {
        };
        $table = $this->getTableLocator()->get('authors');
        $query = new Query($this->connection, $table);
        $this->assertSame($query, $query->formatResults($callback1));
        $this->assertSame([$callback1], $query->getResultFormatters());
        $this->assertSame($query, $query->formatResults($callback2));
        $this->assertSame([$callback1, $callback2], $query->getResultFormatters());
        $query->formatResults($callback2, true);
        $this->assertSame([$callback2], $query->getResultFormatters());
        $query->formatResults(null, true);
        $this->assertSame([], $query->getResultFormatters());

        $query->formatResults($callback1);
        $query->formatResults($callback2, $query::PREPEND);
        $this->assertSame([$callback2, $callback1], $query->getResultFormatters());
    }

    /**
     * Tests that results formatters do receive the query object.
     */
    public function testResultFormatterReceivesTheQueryObject(): void
    {
        $resultFormatterQuery = null;

        $query = $this->getTableLocator()->get('Authors')
            ->find()
            ->formatResults(function ($results, $query) use (&$resultFormatterQuery) {
                $resultFormatterQuery = $query;

                return $results;
            });
        $query->firstOrFail();

        $this->assertSame($query, $resultFormatterQuery);
    }

    /**
     * Tests that when using `beforeFind` events, results formatters for
     * queries of joined associations do receive the source query, not the
     * association target query.
     */
    public function testResultFormatterReceivesTheSourceQueryForJoinedAssociationsWhenUsingBeforeFind(): void
    {
        $articles = $this->getTableLocator()->get('Articles');
        $authors = $articles->belongsTo('Authors');

        $resultFormatterTargetQuery = null;
        $resultFormatterSourceQuery = null;

        $authors->getEventManager()->on(
            'Model.beforeFind',
            function ($event, Query $targetQuery) use (&$resultFormatterTargetQuery, &$resultFormatterSourceQuery): void {
                $resultFormatterTargetQuery = $targetQuery;

                $targetQuery->formatResults(function ($results, $query) use (&$resultFormatterSourceQuery) {
                    $resultFormatterSourceQuery = $query;

                    return $results;
                });
            }
        );

        $sourceQuery = $articles
            ->find()
            ->contain('Authors');

        $sourceQuery->firstOrFail();

        $this->assertNotSame($resultFormatterTargetQuery, $resultFormatterSourceQuery);
        $this->assertNotSame($sourceQuery, $resultFormatterTargetQuery);
        $this->assertSame($sourceQuery, $resultFormatterSourceQuery);
    }

    /**
     * Tests that when using `contain()` callables, results formatters for
     * queries of joined associations do receive the source query, not the
     * association target query.
     */
    public function testResultFormatterReceivesTheSourceQueryForJoinedAssociationWhenUsingContainCallables(): void
    {
        $articles = $this->getTableLocator()->get('Articles');
        $articles->belongsTo('Authors');

        $resultFormatterTargetQuery = null;
        $resultFormatterSourceQuery = null;

        $sourceQuery = $articles
            ->find()
            ->contain('Authors', function (Query $targetQuery) use (
                &$resultFormatterTargetQuery,
                &$resultFormatterSourceQuery
            ) {
                $resultFormatterTargetQuery = $targetQuery;

                return $targetQuery->formatResults(function ($results, $query) use (&$resultFormatterSourceQuery) {
                    $resultFormatterSourceQuery = $query;

                    return $results;
                });
            });

        $sourceQuery->firstOrFail();

        $this->assertNotSame($resultFormatterTargetQuery, $resultFormatterSourceQuery);
        $this->assertNotSame($sourceQuery, $resultFormatterTargetQuery);
        $this->assertSame($sourceQuery, $resultFormatterSourceQuery);
    }

    /**
     * Tests that when using `beforeFind` events, results formatters for
     * queries of non-joined associations do receive the association target
     * query, not the source query.
     */
    public function testResultFormatterReceivesTheTargetQueryForNonJoinedAssociationsWhenUsingBeforeFind(): void
    {
        $articles = $this->getTableLocator()->get('Articles');
        $tags = $articles->belongsToMany('Tags');

        $resultFormatterTargetQuery = null;
        $resultFormatterSourceQuery = null;

        $tags->getEventManager()->on(
            'Model.beforeFind',
            function ($event, Query $targetQuery) use (&$resultFormatterTargetQuery, &$resultFormatterSourceQuery): void {
                $resultFormatterTargetQuery = $targetQuery;

                $targetQuery->formatResults(function ($results, $query) use (&$resultFormatterSourceQuery) {
                    $resultFormatterSourceQuery = $query;

                    return $results;
                });
            }
        );

        $sourceQuery = $articles
            ->find('all')
            ->contain('Tags');

        $sourceQuery->firstOrFail();

        $this->assertNotSame($sourceQuery, $resultFormatterTargetQuery);
        $this->assertNotSame($sourceQuery, $resultFormatterSourceQuery);
        $this->assertSame($resultFormatterTargetQuery, $resultFormatterSourceQuery);
    }

    /**
     * Tests that when using `contain()` callables, results formatters for
     * queries of non-joined associations do receive the association target
     * query, not the source query.
     */
    public function testResultFormatterReceivesTheTargetQueryForNonJoinedAssociationsWhenUsingContainCallables(): void
    {
        $articles = $this->getTableLocator()->get('Articles');
        $articles->belongsToMany('Tags');

        $resultFormatterTargetQuery = null;
        $resultFormatterSourceQuery = null;

        $sourceQuery = $articles
            ->find()
            ->contain('Tags', function (Query $targetQuery) use (
                &$resultFormatterTargetQuery,
                &$resultFormatterSourceQuery
            ) {
                $resultFormatterTargetQuery = $targetQuery;

                return $targetQuery->formatResults(function ($results, $query) use (&$resultFormatterSourceQuery) {
                    $resultFormatterSourceQuery = $query;

                    return $results;
                });
            });

        $sourceQuery->firstOrFail();

        $this->assertNotSame($sourceQuery, $resultFormatterTargetQuery);
        $this->assertNotSame($sourceQuery, $resultFormatterSourceQuery);
        $this->assertSame($resultFormatterTargetQuery, $resultFormatterSourceQuery);
    }

    /**
     * Test fetching results from a qurey with a custom formatter
     */
    public function testQueryWithFormatter(): void
    {
        $table = $this->getTableLocator()->get('authors');
        $query = new Query($this->connection, $table);
        $query->select()->formatResults(function ($results) {
            $this->assertInstanceOf('Cake\ORM\ResultSet', $results);

            return $results->indexBy('id');
        });
        $this->assertEquals([1, 2, 3, 4], array_keys($query->toArray()));
    }

    /**
     * Test fetching results from a qurey with a two custom formatters
     */
    public function testQueryWithStackedFormatters(): void
    {
        $table = $this->getTableLocator()->get('authors');
        $query = new Query($this->connection, $table);
        $query->select()->formatResults(function ($results) {
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
            4 => 'garrett',
        ];
        $this->assertEquals($expected, $query->toArray());
    }

    /**
     * Tests that getting results from a query having a contained association
     * will not attach joins twice if count() is called on it afterwards
     */
    public function testCountWithContainCallingAll(): void
    {
        $table = $this->getTableLocator()->get('articles');
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
     * Verify that only one count query is issued
     * A subsequent request for the count will take the previously
     * returned value
     */
    public function testCountCache(): void
    {
        $query = $this->getMockBuilder('Cake\ORM\Query')
            ->disableOriginalConstructor()
            ->onlyMethods(['_performCount'])
            ->getMock();

        $query->expects($this->once())
            ->method('_performCount')
            ->will($this->returnValue(1));

        $result = $query->count();
        $this->assertSame(1, $result, 'The result of the sql query should be returned');

        $resultAgain = $query->count();
        $this->assertSame(1, $resultAgain, 'No query should be issued and the cached value returned');
    }

    /**
     * If the query is dirty the cached value should be ignored
     * and a new count query issued
     */
    public function testCountCacheDirty(): void
    {
        $query = $this->getMockBuilder('Cake\ORM\Query')
            ->disableOriginalConstructor()
            ->onlyMethods(['_performCount'])
            ->getMock();

        $query->expects($this->exactly(2))
            ->method('_performCount')
            ->will($this->onConsecutiveCalls(1, 2));

        $result = $query->count();
        $this->assertSame(1, $result, 'The result of the sql query should be returned');

        $query->where(['dirty' => 'cache']);

        $secondResult = $query->count();
        $this->assertSame(2, $secondResult, 'The query cache should be dropped with any modification');

        $thirdResult = $query->count();
        $this->assertSame(2, $thirdResult, 'The query has not been modified, the cached value is valid');
    }

    /**
     * Tests that it is possible to apply formatters inside the query builder
     * for belongsTo associations
     */
    public function testFormatBelongsToRecords(): void
    {
        $table = $this->getTableLocator()->get('articles');
        $table->belongsTo('authors');

        $query = $table->find()
            ->contain([
                'authors' => function ($q) {
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
                },
            ]);

        $query->formatResults(function ($results) {
            return $results->combine('id', 'author.idCopy');
        });
        $results = $query->toArray();
        $expected = [1 => 3, 2 => 5, 3 => 3];
        $this->assertEquals($expected, $results);
    }

    /**
     * Tests it is possible to apply formatters to deep relations.
     */
    public function testFormatDeepAssociationRecords(): void
    {
        $table = $this->getTableLocator()->get('ArticlesTags');
        $table->belongsTo('Articles');
        $table->getAssociation('Articles')->getTarget()->belongsTo('Authors');

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
            ->order(['ArticlesTags.article_id' => 'ASC']);

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
     */
    public function testFormatDeepDistantAssociationRecords(): void
    {
        $table = $this->getTableLocator()->get('authors');
        $table->hasMany('articles');
        $articles = $table->getAssociation('articles')->getTarget();
        $articles->hasMany('articlesTags');
        $articles->getAssociation('articlesTags')->getTarget()->belongsTo('tags');

        $query = $table->find()->contain([
            'articles.articlesTags.tags' => function ($q) {
                return $q->formatResults(function ($results) {
                    return $results->map(function ($tag) {
                        $tag->name .= ' - visited';

                        return $tag;
                    });
                });
            },
        ]);

        $query->mapReduce(function ($row, $key, $mr): void {
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
     */
    public function testCustomFinderInBelongsTo(): void
    {
        $table = $this->getTableLocator()->get('ArticlesTags');
        $table->belongsTo('Articles', [
            'className' => 'TestApp\Model\Table\ArticlesTable',
            'finder' => 'published',
        ]);
        $result = $table->find()->contain('Articles');
        $this->assertCount(4, $result->all()->extract('article')->filter()->toArray());
        $table->Articles->updateAll(['published' => 'N'], ['1 = 1']);

        $result = $table->find()->contain('Articles');
        $this->assertCount(0, $result->all()->extract('article')->filter()->toArray());
    }

    /**
     * Test finding fields on the non-default table that
     * have the same name as the primary table.
     */
    public function testContainSelectedFields(): void
    {
        $table = $this->getTableLocator()->get('Articles');
        $table->belongsTo('Authors');

        $query = $table->find()
            ->contain(['Authors'])
            ->order(['Authors.id' => 'asc'])
            ->select(['Authors.id']);
        $results = $query->all()->extract('author.id')->toList();
        $expected = [1, 1, 3];
        $this->assertEquals($expected, $results);
    }

    /**
     * Tests that it is possible to attach more association when using a query
     * builder for other associations
     */
    public function testContainInAssociationQuery(): void
    {
        $table = $this->getTableLocator()->get('ArticlesTags');
        $table->belongsTo('Articles');
        $table->getAssociation('Articles')->getTarget()->belongsTo('Authors');

        $query = $table->find()
            ->order(['Articles.id' => 'ASC'])
            ->contain([
                'Articles' => function ($q) {
                    return $q->contain('Authors');
                },
            ]);
        $results = $query->all()->extract('article.author.name')->toArray();
        $expected = ['mariano', 'mariano', 'larry', 'larry'];
        $this->assertEquals($expected, $results);
    }

    /**
     * Tests that it is possible to apply more `matching` conditions inside query
     * builders for associations
     */
    public function testContainInAssociationMatching(): void
    {
        $table = $this->getTableLocator()->get('authors');
        $table->hasMany('articles');
        $articles = $table->getAssociation('articles')->getTarget();
        $articles->hasMany('articlesTags');
        $articles->getAssociation('articlesTags')->getTarget()->belongsTo('tags');

        $query = $table->find()->matching('articles.articlesTags', function ($q) {
            return $q->matching('tags', function ($q) {
                return $q->where(['tags.name' => 'tag3']);
            });
        });

        $results = $query->toArray();
        $this->assertCount(1, $results);
        $this->assertSame('tag3', $results[0]->_matchingData['tags']->name);
    }

    /**
     * Tests __debugInfo
     */
    public function testDebugInfo(): void
    {
        $table = $this->getTableLocator()->get('authors');
        $table->hasMany('articles');
        $query = $table->find()
            ->where(['id > ' => 1])
            ->enableBufferedResults(false)
            ->enableHydration(false)
            ->matching('articles')
            ->applyOptions(['foo' => 'bar'])
            ->formatResults(function ($results) {
                return $results;
            })
            ->mapReduce(function ($item, $key, $mr): void {
                $mr->emit($item);
            });

        $expected = [
            '(help)' => 'This is a Query object, to get the results execute or iterate it.',
            'sql' => $query->sql(),
            'params' => $query->getValueBinder()->bindings(),
            'defaultTypes' => [
                'authors__id' => 'integer',
                'authors.id' => 'integer',
                'id' => 'integer',
                'authors__name' => 'string',
                'authors.name' => 'string',
                'name' => 'string',
                'articles__id' => 'integer',
                'articles.id' => 'integer',
                'articles__author_id' => 'integer',
                'articles.author_id' => 'integer',
                'author_id' => 'integer',
                'articles__title' => 'string',
                'articles.title' => 'string',
                'title' => 'string',
                'articles__body' => 'text',
                'articles.body' => 'text',
                'body' => 'text',
                'articles__published' => 'string',
                'articles.published' => 'string',
                'published' => 'string',
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
                    'matching' => true,
                    'queryBuilder' => null,
                    'joinType' => 'INNER',
                ],
            ],
            'extraOptions' => ['foo' => 'bar'],
            'repository' => $table,
        ];
        $this->assertSame($expected, $query->__debugInfo());
    }

    /**
     * Tests that the eagerLoaded function works and is transmitted correctly to eagerly
     * loaded associations
     */
    public function testEagerLoaded(): void
    {
        $table = $this->getTableLocator()->get('authors');
        $table->hasMany('articles');
        $query = $table->find()->contain([
            'articles' => function ($q) {
                $this->assertTrue($q->isEagerLoaded());

                return $q;
            },
        ]);
        $this->assertFalse($query->isEagerLoaded());

        $table->getEventManager()->on('Model.beforeFind', function ($e, $q, $o, $primary): void {
            $this->assertTrue($primary);
        });

        $this->getTableLocator()->get('articles')
            ->getEventManager()->on('Model.beforeFind', function ($e, $q, $o, $primary): void {
                $this->assertFalse($primary);
            });
        $query->all();
    }

    /**
     * Tests that the isEagerLoaded function works and is transmitted correctly to eagerly
     * loaded associations
     */
    public function testIsEagerLoaded(): void
    {
        $table = $this->getTableLocator()->get('authors');
        $table->hasMany('articles');
        $query = $table->find()->contain([
            'articles' => function ($q) {
                $this->assertTrue($q->isEagerLoaded());

                return $q;
            },
        ]);
        $this->assertFalse($query->isEagerLoaded());

        $table->getEventManager()->on('Model.beforeFind', function ($e, $q, $o, $primary): void {
            $this->assertTrue($primary);
        });

        $this->getTableLocator()->get('articles')
            ->getEventManager()->on('Model.beforeFind', function ($e, $q, $o, $primary): void {
                $this->assertFalse($primary);
            });
        $query->all();
    }

    /**
     * Tests that columns from manual joins are also contained in the result set
     */
    public function testColumnsFromJoin(): void
    {
        $table = $this->getTableLocator()->get('articles');
        $query = $table->find();
        $results = $query
            ->select(['title', 'person.name'])
            ->join([
                'person' => [
                    'table' => 'authors',
                    'conditions' => [$query->newExpr()->equalFields('person.id', 'articles.author_id')],
                ],
            ])
            ->order(['articles.id' => 'ASC'])
            ->enableHydration(false)
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
     * @dataProvider strategiesProviderBelongsTo
     */
    public function testRepeatedAssociationAliases(string $strategy): void
    {
        $table = $this->getTableLocator()->get('ArticlesTags');
        $table->belongsTo('Articles', ['strategy' => $strategy]);
        $table->belongsTo('Tags', ['strategy' => $strategy]);
        $this->getTableLocator()->get('Tags')->belongsToMany('Articles');
        $results = $table
            ->find()
            ->contain(['Articles', 'Tags.Articles'])
            ->enableHydration(false)
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
     */
    public function testAssociationKeyPresent(): void
    {
        $table = $this->getTableLocator()->get('Articles');
        $table->hasOne('ArticlesTags', ['strategy' => 'select']);
        $article = $table->find()->where(['id' => 3])
            ->enableHydration(false)
            ->contain('ArticlesTags')
            ->first();

        $this->assertNull($article['articles_tag']);
    }

    /**
     * Tests that queries can be serialized to JSON to get the results
     */
    public function testJsonSerialize(): void
    {
        $table = $this->getTableLocator()->get('Articles');
        $this->assertEquals(
            json_encode($table->find()),
            json_encode($table->find()->toArray())
        );
    }

    /**
     * Test that addFields() works in the basic case.
     */
    public function testAutoFields(): void
    {
        $table = $this->getTableLocator()->get('Articles');
        $result = $table->find('all')
            ->select(['myField' => '(SELECT 20)'])
            ->enableAutoFields()
            ->enableHydration(false)
            ->first();

        $this->assertArrayHasKey('myField', $result);
        $this->assertArrayHasKey('id', $result);
        $this->assertArrayHasKey('title', $result);
    }

    /**
     * Test autoFields with auto fields.
     */
    public function testAutoFieldsWithAssociations(): void
    {
        $table = $this->getTableLocator()->get('Articles');
        $table->belongsTo('Authors');

        $result = $table->find()
            ->select(['myField' => '(SELECT 2 + 2)'])
            ->enableAutoFields()
            ->enableHydration(false)
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
     */
    public function testAutoFieldsWithContainQueryBuilder(): void
    {
        $table = $this->getTableLocator()->get('Articles');
        $table->belongsTo('Authors');

        $result = $table->find()
            ->select(['myField' => '(SELECT 2 + 2)'])
            ->enableAutoFields()
            ->enableHydration(false)
            ->contain([
                'Authors' => function ($q) {
                    return $q->select(['computed' => '(SELECT 2 + 20)'])
                        ->enableAutoFields();
                },
            ])
            ->first();

        $this->assertArrayHasKey('myField', $result);
        $this->assertArrayHasKey('title', $result);
        $this->assertArrayHasKey('author', $result);
        $this->assertNotNull($result['author']);
        $this->assertArrayHasKey('name', $result['author']);
        $this->assertArrayHasKey('computed', $result);
    }

    /**
     * Test that autofields works with count()
     */
    public function testAutoFieldsCount(): void
    {
        $table = $this->getTableLocator()->get('Articles');

        $result = $table->find()
            ->select(['myField' => '(SELECT (2 + 2))'])
            ->enableAutoFields()
            ->count();

        $this->assertEquals(3, $result);
    }

    /**
     * test that cleanCopy makes a cleaned up clone.
     */
    public function testCleanCopy(): void
    {
        $table = $this->getTableLocator()->get('Articles');
        $table->hasMany('Comments');

        $query = $table->find();
        $query->offset(10)
            ->limit(1)
            ->order(['Articles.id' => 'DESC'])
            ->contain(['Comments'])
            ->matching('Comments');
        $copy = $query->cleanCopy();

        $this->assertNotSame($copy, $query);
        $copyLoader = $copy->getEagerLoader();
        $loader = $query->getEagerLoader();
        $this->assertEquals($copyLoader, $loader, 'should be equal');
        $this->assertNotSame($copyLoader, $loader, 'should be clones');

        $reflect = new ReflectionProperty($loader, '_matching');
        $reflect->setAccessible(true);
        $this->assertNotSame(
            $reflect->getValue($copyLoader),
            $reflect->getValue($loader),
            'should be clones'
        );
        $this->assertNull($copy->clause('offset'));
        $this->assertNull($copy->clause('limit'));
        $this->assertNull($copy->clause('order'));
    }

    /**
     * test that cleanCopy retains bindings
     */
    public function testCleanCopyRetainsBindings(): void
    {
        $table = $this->getTableLocator()->get('Articles');
        $query = $table->find();
        $query->offset(10)
            ->limit(1)
            ->where(['Articles.id BETWEEN :start AND :end'])
            ->order(['Articles.id' => 'DESC'])
            ->bind(':start', 1)
            ->bind(':end', 2);
        $copy = $query->cleanCopy();

        $this->assertNotEmpty($copy->getValueBinder()->bindings());
    }

    /**
     * test that cleanCopy makes a cleaned up clone with a beforeFind.
     */
    public function testCleanCopyBeforeFind(): void
    {
        $table = $this->getTableLocator()->get('Articles');
        $table->hasMany('Comments');
        $table->getEventManager()
            ->on('Model.beforeFind', function (EventInterface $event, $query): void {
                $query
                    ->limit(5)
                    ->order(['Articles.title' => 'DESC']);
            });

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
     * Test that finder options sent through via contain are sent to custom finder for belongsTo associations.
     */
    public function testContainFinderBelongsTo(): void
    {
        $table = $this->getTableLocator()->get('Articles');
        $table->belongsTo(
            'Authors',
            ['className' => 'TestApp\Model\Table\AuthorsTable']
        );
        $authorId = 1;

        $resultWithoutAuthor = $table->find('all')
            ->where(['Articles.author_id' => $authorId])
            ->contain([
                'Authors' => [
                    'finder' => ['byAuthor' => ['author_id' => 2]],
                ],
            ]);

        $resultWithAuthor = $table->find('all')
            ->where(['Articles.author_id' => $authorId])
            ->contain([
                'Authors' => [
                    'finder' => ['byAuthor' => ['author_id' => $authorId]],
                ],
            ]);

        $this->assertEmpty($resultWithoutAuthor->first()['author']);
        $this->assertEquals($authorId, $resultWithAuthor->first()['author']['id']);
    }

    /**
     * Test that finder options sent through via contain are sent to custom finder for hasMany associations.
     */
    public function testContainFinderHasMany(): void
    {
        $table = $this->getTableLocator()->get('Authors');
        $table->hasMany(
            'Articles',
            ['className' => 'TestApp\Model\Table\ArticlesTable']
        );

        $newArticle = $table->newEntity([
            'author_id' => 1,
            'title' => 'Fourth Article',
            'body' => 'Fourth Article Body',
            'published' => 'N',
        ]);
        $table->save($newArticle);

        $resultWithArticles = $table->find('all')
            ->where(['id' => 1])
            ->contain([
                'Articles' => [
                    'finder' => 'published',
                ],
            ]);

        $resultWithArticlesArray = $table->find('all')
            ->where(['id' => 1])
            ->contain([
                'Articles' => [
                    'finder' => ['published' => []],
                ],
            ]);

        $resultWithArticlesArrayOptions = $table->find('all')
            ->where(['id' => 1])
            ->contain([
                'Articles' => [
                    'finder' => [
                        'published' => [
                            'title' => 'First Article',
                        ],
                    ],
                ],
            ]);

        $resultWithoutArticles = $table->find('all')
            ->where(['id' => 1])
            ->contain([
                'Articles' => [
                    'finder' => [
                        'published' => [
                            'title' => 'Foo',
                        ],
                    ],
                ],
            ]);

        $this->assertCount(2, $resultWithArticles->first()->articles);
        $this->assertCount(2, $resultWithArticlesArray->first()->articles);

        $this->assertCount(1, $resultWithArticlesArrayOptions->first()->articles);
        $this->assertSame(
            'First Article',
            $resultWithArticlesArrayOptions->first()->articles[0]->title
        );

        $this->assertCount(0, $resultWithoutArticles->first()->articles);
    }

    /**
     * Test that using a closure for a custom finder for contain works.
     */
    public function testContainFinderHasManyClosure(): void
    {
        $table = $this->getTableLocator()->get('Authors');
        $table->hasMany(
            'Articles',
            ['className' => 'TestApp\Model\Table\ArticlesTable']
        );

        $newArticle = $table->newEntity([
            'author_id' => 1,
            'title' => 'Fourth Article',
            'body' => 'Fourth Article Body',
            'published' => 'N',
        ]);
        $table->save($newArticle);

        $resultWithArticles = $table->find('all')
            ->where(['id' => 1])
            ->contain([
                'Articles' => function ($q) {
                    return $q->find('published');
                },
            ]);

        $this->assertCount(2, $resultWithArticles->first()->articles);
    }

    /**
     * Tests that it is possible to bind arguments to a query and it will return the right
     * results
     */
    public function testCustomBindings(): void
    {
        $table = $this->getTableLocator()->get('Articles');
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
     */
    public function testContainWithCustomJoinType(): void
    {
        $table = $this->getTableLocator()->get('Articles');
        $table->belongsTo('Authors');

        $articles = $table->find()
            ->contain([
                'Authors' => [
                    'joinType' => 'inner',
                    'conditions' => ['Authors.id' => 3],
                ],
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
     */
    public function testContainWithStrategyOverride(): void
    {
        $table = $this->getTableLocator()->get('Articles');
        $table->belongsTo('Authors', [
            'joinType' => 'INNER',
        ]);
        $articles = $table->find()
            ->contain([
                'Authors' => [
                    'strategy' => 'select',
                    'conditions' => ['Authors.id' => 3],
                ],
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
     */
    public function testMatchingWithContain(): void
    {
        $query = new Query($this->connection, $this->table);
        $table = $this->getTableLocator()->get('authors');
        $table->hasMany('articles');
        $this->getTableLocator()->get('articles')->belongsToMany('tags');

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
     */
    public function testNotSoFarMatchingWithContainOnTheSameAssociation(): void
    {
        $table = $this->getTableLocator()->get('articles');
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

    /**
     * Tests that it is possible to find large numeric values.
     */
    public function testSelectLargeNumbers(): void
    {
        // Sqlite only supports maximum 16 digits for decimals.
        $this->skipIf($this->connection->getDriver() instanceof Sqlite);

        $big = '1234567890123456789.2';
        $table = $this->getTableLocator()->get('Datatypes');
        $entity = $table->newEntity([]);
        $entity->cost = $big;
        $entity->tiny = 1;
        $entity->small = 10;

        $table->save($entity);
        $out = $table->find()
            ->where([
                'cost' => $big,
            ])
            ->first();
        $this->assertNotEmpty($out, 'Should get a record');
        $this->assertSame($big, $out->cost);

        $small = '0.1234567890123456789';
        $entity = $table->newEntity(['fraction' => $small]);

        $table->save($entity);
        $out = $table->find()
            ->where([
                'fraction' => $small,
            ])
            ->first();
        $this->assertNotEmpty($out, 'Should get a record');
        $this->assertMatchesRegularExpression('/^0?\.1234567890123456789$/', $out->fraction);

        $small = 0.1234567890123456789;
        $entity = $table->newEntity(['fraction' => $small]);

        $table->save($entity);
        $out = $table->find()
            ->where([
                'fraction' => $small,
            ])
            ->first();
        $this->assertNotEmpty($out, 'Should get a record');
        // There will be loss of precision if too large/small value is set as float instead of string.
        $this->assertMatchesRegularExpression('/^0?\.123456789012350+$/', $out->fraction);
    }

    /**
     * Tests that select() can be called with Table and Association
     * instance
     */
    public function testSelectWithTableAndAssociationInstance(): void
    {
        $table = $this->getTableLocator()->get('articles');
        $table->belongsTo('authors');
        $result = $table
            ->find()
            ->select(function ($q) {
                return ['foo' => $q->newExpr('1 + 1')];
            })
            ->select($table)
            ->select($table->authors)
            ->contain(['authors'])
            ->first();

        $expected = $table
            ->find()
            ->select(function ($q) {
                return ['foo' => $q->newExpr('1 + 1')];
            })
            ->enableAutoFields()
            ->contain(['authors'])
            ->first();

        $this->assertNotEmpty($result);
        $this->assertEquals($expected, $result);
    }

    /**
     * Test that simple aliased field have results typecast.
     */
    public function testSelectTypeInferSimpleAliases(): void
    {
        $table = $this->getTableLocator()->get('comments');
        $result = $table
            ->find()
            ->select(['created', 'updated_time' => 'updated'])
            ->first();
        $this->assertInstanceOf(FrozenTime::class, $result->created);
        $this->assertInstanceOf(FrozenTime::class, $result->updated_time);
    }

    /**
     * Tests that leftJoinWith() creates a left join with a given association and
     * that no fields from such association are loaded.
     */
    public function testLeftJoinWith(): void
    {
        $table = $this->getTableLocator()->get('authors');
        $table->hasMany('articles');
        $table->articles->deleteAll(['author_id' => 4]);
        $results = $table
            ->find()
            ->select(['total_articles' => 'count(articles.id)'])
            ->enableAutoFields()
            ->leftJoinWith('articles')
            ->group(['authors.id', 'authors.name']);

        $expected = [
            1 => 2,
            2 => 0,
            3 => 1,
            4 => 0,
        ];
        $this->assertEquals($expected, $results->all()->combine('id', 'total_articles')->toArray());
        $fields = ['total_articles', 'id', 'name'];
        $this->assertEquals($fields, array_keys($results->first()->toArray()));

        $results = $table
            ->find()
            ->leftJoinWith('articles')
            ->where(['articles.id IS' => null]);

        $this->assertEquals([2, 4], $results->all()->extract('id')->toList());
        $this->assertEquals(['id', 'name'], array_keys($results->first()->toArray()));

        $results = $table
            ->find()
            ->leftJoinWith('articles')
            ->where(['articles.id IS NOT' => null])
            ->order(['authors.id']);

        $this->assertEquals([1, 1, 3], $results->all()->extract('id')->toList());
        $this->assertEquals(['id', 'name'], array_keys($results->first()->toArray()));
    }

    /**
     * Tests that leftJoinWith() creates a left join with a given association and
     * that no fields from such association are loaded.
     */
    public function testLeftJoinWithNested(): void
    {
        $table = $this->getTableLocator()->get('authors');
        $articles = $table->hasMany('articles');
        $articles->belongsToMany('tags');

        $results = $table
            ->find()
            ->select([
                'authors.id',
                'tagged_articles' => 'count(tags.id)',
            ])
            ->leftJoinWith('articles.tags', function ($q) {
                return $q->where(['tags.name' => 'tag3']);
            })
            ->group(['authors.id']);

        $expected = [
            1 => 0,
            2 => 0,
            3 => 1,
            4 => 0,
        ];
        $this->assertEquals($expected, $results->all()->combine('id', 'tagged_articles')->toArray());
    }

    /**
     * Tests that leftJoinWith() can be used with select()
     */
    public function testLeftJoinWithSelect(): void
    {
        $table = $this->getTableLocator()->get('authors');
        $articles = $table->hasMany('articles');
        $articles->belongsToMany('tags');
        $results = $table
            ->find()
            ->leftJoinWith('articles.tags', function ($q) {
                return $q
                    ->select(['articles.id', 'articles.title', 'tags.name'])
                    ->where(['tags.name' => 'tag3']);
            })
            ->enableAutoFields()
            ->where(['ArticlesTags.tag_id' => 3])
            ->all();

        $expected = ['id' => 2, 'title' => 'Second Article'];
        $this->assertEquals(
            $expected,
            $results->first()->_matchingData['articles']->toArray()
        );
        $this->assertEquals(
            ['name' => 'tag3'],
            $results->first()->_matchingData['tags']->toArray()
        );
    }

    /**
     * Tests that leftJoinWith() can be used with autofields()
     */
    public function testLeftJoinWithAutoFields(): void
    {
        $table = $this->getTableLocator()->get('articles');
        $table->belongsTo('authors');

        $results = $table
            ->find()
            ->leftJoinWith('authors', function ($q) {
                return $q->enableAutoFields();
            })
            ->all();
        $this->assertCount(3, $results);
    }

    /**
     * Test leftJoinWith and contain on optional association
     */
    public function testLeftJoinWithAndContainOnOptionalAssociation(): void
    {
        $table = $this->getTableLocator()->get('Articles', ['table' => 'articles']);
        $table->belongsTo('Authors');
        $newArticle = $table->newEntity([
            'title' => 'Fourth Article',
            'body' => 'Fourth Article Body',
            'published' => 'N',
        ]);
        $table->save($newArticle);
        $results = $table
            ->find()
            ->disableHydration()
            ->contain('Authors')
            ->leftJoinWith('Authors')
            ->all();
        $expected = [
            [
                'id' => 1,
                'author_id' => 1,
                'title' => 'First Article',
                'body' => 'First Article Body',
                'published' => 'Y',
                'author' => [
                    'id' => 1,
                    'name' => 'mariano',
                ],
            ],
            [
                'id' => 2,
                'author_id' => 3,
                'title' => 'Second Article',
                'body' => 'Second Article Body',
                'published' => 'Y',
                'author' => [
                    'id' => 3,
                    'name' => 'larry',
                ],
            ],
            [
                'id' => 3,
                'author_id' => 1,
                'title' => 'Third Article',
                'body' => 'Third Article Body',
                'published' => 'Y',
                'author' => [
                    'id' => 1,
                    'name' => 'mariano',
                ],
            ],
            [
                'id' => 4,
                'author_id' => null,
                'title' => 'Fourth Article',
                'body' => 'Fourth Article Body',
                'published' => 'N',
                'author' => null,
            ],
        ];
        $this->assertEquals($expected, $results->toList());
        $results = $table
            ->find()
            ->disableHydration()
            ->contain('Authors')
            ->leftJoinWith('Authors')
            ->where(['Articles.author_id is' => null])
            ->all();
        $expected = [
            [
                'id' => 4,
                'author_id' => null,
                'title' => 'Fourth Article',
                'body' => 'Fourth Article Body',
                'published' => 'N',
                'author' => null,
            ],
        ];
        $this->assertEquals($expected, $results->toList());
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The `Tags` association is not defined on `Articles`.');
        $table
            ->find()
            ->disableHydration()
            ->contain('Tags')
            ->leftJoinWith('Tags')
            ->all();
    }

    /**
     * Tests innerJoinWith()
     */
    public function testInnerJoinWith(): void
    {
        $table = $this->getTableLocator()->get('authors');
        $table->hasMany('articles');
        $results = $table
            ->find()
            ->innerJoinWith('articles', function ($q) {
                return $q->where(['articles.title' => 'Third Article']);
            });
        $expected = [
            [
                'id' => 1,
                'name' => 'mariano',
            ],
        ];
        $this->assertEquals($expected, $results->enableHydration(false)->toArray());
    }

    /**
     * Tests innerJoinWith() with nested associations
     */
    public function testInnerJoinWithNested(): void
    {
        $table = $this->getTableLocator()->get('authors');
        $articles = $table->hasMany('articles');
        $articles->belongsToMany('tags');
        $results = $table
            ->find()
            ->innerJoinWith('articles.tags', function ($q) {
                return $q->where(['tags.name' => 'tag3']);
            });
        $expected = [
            [
                'id' => 3,
                'name' => 'larry',
            ],
        ];
        $this->assertEquals($expected, $results->enableHydration(false)->toArray());
    }

    /**
     * Tests innerJoinWith() with select
     */
    public function testInnerJoinWithSelect(): void
    {
        $table = $this->getTableLocator()->get('authors');
        $table->hasMany('articles');
        $results = $table
            ->find()
            ->enableAutoFields()
            ->innerJoinWith('articles', function ($q) {
                return $q->select(['id', 'author_id', 'title', 'body', 'published']);
            })
            ->toArray();

        $expected = $table
            ->find()
            ->matching('articles')
            ->toArray();
        $this->assertEquals($expected, $results);
    }

    /**
     * Tests contain() in query returned by innerJoinWith throws exception.
     */
    public function testInnerJoinWithContain(): void
    {
        $comments = $this->getTableLocator()->get('Comments');
        $articles = $comments->belongsTo('Articles');
        $articles->hasOne('ArticlesTranslations');

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('`Articles` association cannot contain() associations when using JOIN strategy');
        $comments->find()
            ->innerJoinWith('Articles', function (Query $q) {
                return $q
                    ->contain('ArticlesTranslations')
                    ->where(['ArticlesTranslations.title' => 'Titel #1']);
            })
            ->sql();
    }

    /**
     * Tests notMatching() with and without conditions
     */
    public function testNotMatching(): void
    {
        $table = $this->getTableLocator()->get('authors');
        $table->hasMany('articles');

        $results = $table->find()
            ->enableHydration(false)
            ->notMatching('articles')
            ->order(['authors.id'])
            ->toArray();

        $expected = [
            ['id' => 2, 'name' => 'nate'],
            ['id' => 4, 'name' => 'garrett'],
        ];
        $this->assertEquals($expected, $results);

        $results = $table->find()
            ->enableHydration(false)
            ->notMatching('articles', function ($q) {
                return $q->where(['articles.author_id' => 1]);
            })
            ->order(['authors.id'])
            ->toArray();
        $expected = [
            ['id' => 2, 'name' => 'nate'],
            ['id' => 3, 'name' => 'larry'],
            ['id' => 4, 'name' => 'garrett'],
        ];
        $this->assertEquals($expected, $results);
    }

    /**
     * Tests notMatching() with a belongsToMany association
     */
    public function testNotMatchingBelongsToMany(): void
    {
        $table = $this->getTableLocator()->get('articles');
        $table->belongsToMany('tags');

        $results = $table->find()
            ->enableHydration(false)
            ->notMatching('tags', function ($q) {
                return $q->where(['tags.name' => 'tag2']);
            });

        $results = $results->toArray();

        $expected = [
            [
                'id' => 2,
                'author_id' => 3,
                'title' => 'Second Article',
                'body' => 'Second Article Body',
                'published' => 'Y',
            ],
            [
                'id' => 3,
                'author_id' => 1,
                'title' => 'Third Article',
                'body' => 'Third Article Body',
                'published' => 'Y',
            ],
        ];
        $this->assertEquals($expected, $results);
    }

    /**
     * Tests notMatching() with a deeply nested belongsToMany association.
     */
    public function testNotMatchingDeep(): void
    {
        $table = $this->getTableLocator()->get('authors');
        $articles = $table->hasMany('articles');
        $articles->belongsToMany('tags');

        $results = $table->find()
            ->enableHydration(false)
            ->select('authors.id')
            ->notMatching('articles.tags', function ($q) {
                return $q->where(['tags.name' => 'tag3']);
            })
            ->distinct(['authors.id']);

        $this->assertEquals([1, 2, 4], $results->all()->extract('id')->toList());

        $results = $table->find()
            ->enableHydration(false)
            ->notMatching('articles.tags', function ($q) {
                return $q->where(['tags.name' => 'tag3']);
            })
            ->matching('articles')
            ->distinct(['authors.id']);

        $this->assertEquals([1], $results->all()->extract('id')->toList());
    }

    /**
     * Tests that it is possible to nest a notMatching call inside another
     * eagerloader function.
     */
    public function testNotMatchingNested(): void
    {
        $table = $this->getTableLocator()->get('authors');
        $articles = $table->hasMany('articles');
        $articles->belongsToMany('tags');

        $results = $table->find()
            ->enableHydration(false)
            ->matching('articles', function (Query $q) {
                return $q->notMatching('tags', function (Query $q) {
                    return $q->where(['tags.name' => 'tag3']);
                });
            })
            ->order(['authors.id' => 'ASC', 'articles.id' => 'ASC']);

        $expected = [
            'id' => 1,
            'name' => 'mariano',
            '_matchingData' => [
                'articles' => [
                    'id' => 1,
                    'author_id' => 1,
                    'title' => 'First Article',
                    'body' => 'First Article Body',
                    'published' => 'Y',
                ],
            ],
        ];
        $this->assertSame($expected, $results->first());
    }

    /**
     * Test to see that the excluded fields are not in the select clause
     */
    public function testSelectAllExcept(): void
    {
        $table = $this->getTableLocator()->get('Articles');
        $result = $table
            ->find()
            ->selectAllExcept($table, ['body']);
        $selectedFields = $result->clause('select');
        $expected = [
            'Articles__id' => 'Articles.id',
            'Articles__author_id' => 'Articles.author_id',
            'Articles__title' => 'Articles.title',
            'Articles__published' => 'Articles.published',
        ];
        $this->assertEquals($expected, $selectedFields);
    }

    /**
     * Test that the excluded fields are not included
     * in the final query result.
     */
    public function testSelectAllExceptWithContains(): void
    {
        $table = $this->getTableLocator()->get('Articles');
        $table->hasMany('Comments');
        $table->belongsTo('Authors');

        $result = $table
            ->find()
            ->contain([
                'Comments' => function (Query $query) use ($table) {
                    return $query->selectAllExcept($table->Comments, ['published']);
                },
            ])
            ->selectAllExcept($table, ['body'])
            ->first();
        $this->assertNull($result->comments[0]->published);
        $this->assertNull($result->body);
        $this->assertNotEmpty($result->id);
        $this->assertNotEmpty($result->comments[0]->id);
    }

    /**
     * Test what happens if you call selectAllExcept() more
     * than once.
     */
    public function testSelectAllExceptWithMulitpleCalls(): void
    {
        $table = $this->getTableLocator()->get('Articles');

        $result = $table
            ->find()
            ->selectAllExcept($table, ['body'])
            ->selectAllExcept($table, ['published']);
        $selectedFields = $result->clause('select');
        $expected = [
            'Articles__id' => 'Articles.id',
            'Articles__author_id' => 'Articles.author_id',
            'Articles__title' => 'Articles.title',
            'Articles__published' => 'Articles.published',
            'Articles__body' => 'Articles.body',
        ];
        $this->assertEquals($expected, $selectedFields);

        $result = $table
            ->find()
            ->selectAllExcept($table, ['body'])
            ->selectAllExcept($table, ['published', 'body']);
        $selectedFields = $result->clause('select');
        $expected = [
            'Articles__id' => 'Articles.id',
            'Articles__author_id' => 'Articles.author_id',
            'Articles__title' => 'Articles.title',
            'Articles__published' => 'Articles.published',
        ];
        $this->assertEquals($expected, $selectedFields);

        $result = $table
            ->find()
            ->selectAllExcept($table, ['body'])
            ->selectAllExcept($table, ['published', 'body'], true);
        $selectedFields = $result->clause('select');
        $expected = [
            'Articles__id' => 'Articles.id',
            'Articles__author_id' => 'Articles.author_id',
            'Articles__title' => 'Articles.title',
        ];
        $this->assertEquals($expected, $selectedFields);
    }

    /**
     * Test that given the wrong first parameter, Invalid argument exception is thrown
     */
    public function testSelectAllExceptThrowsInvalidArgument(): void
    {
        $table = $this->getTableLocator()->get('Articles');
        $this->expectException(\InvalidArgumentException::class);
            $table
                ->find()
                ->selectAllExcept([], ['body']);
    }

    /**
     * Tests that using Having on an aggregated field returns the correct result
     * model in the query
     */
    public function testHavingOnAnAggregatedField(): void
    {
        $post = $this->getTableLocator()->get('posts');

        $query = new Query($this->connection, $post);

        $results = $query
            ->select([
                'posts.author_id',
                'post_count' => $query->func()->count('posts.id'),
            ])
            ->group(['posts.author_id'])
            ->having([$query->newExpr()->gte('post_count', 2, 'integer')])
            ->enableHydration(false)
            ->toArray();

        $expected = [
            [
                'author_id' => 1,
                'post_count' => 2,
            ],
        ];

        $this->assertEquals($expected, $results);
    }

    /**
     * Tests ORM query using with CTE.
     */
    public function testWith(): void
    {
        $this->skipIf(
            !$this->connection->getDriver()->supports(DriverInterface::FEATURE_CTE),
            'The current driver does not support common table expressions.'
        );
        $this->skipIf(
            (
                $this->connection->getDriver() instanceof Mysql ||
                $this->connection->getDriver() instanceof Sqlite
            ) &&
            !$this->connection->getDriver()->supports(DriverInterface::FEATURE_WINDOW),
            'The current driver does not support window functions.'
        );

        $table = $this->getTableLocator()->get('Articles');

        $cteQuery = $table
            ->find()
            ->select(function (Query $query) use ($table) {
                $columns = $table->getSchema()->columns();

                return array_combine($columns, $columns) + [
                        'row_num' => $query
                            ->func()
                            ->rowNumber()
                            ->over()
                            ->partition('author_id')
                            ->order(['id' => 'ASC']),
                    ];
            });

        $query = $table
            ->find()
            ->with(function (CommonTableExpression $cte) use ($cteQuery) {
                return $cte
                    ->name('cte')
                    ->query($cteQuery);
            })
            ->select(['row_num'])
            ->enableAutoFields()
            ->from([$table->getAlias() => 'cte'])
            ->where(['row_num' => 1], ['row_num' => 'integer'])
            ->order(['id' => 'ASC'])
            ->disableHydration();

        $expected = [
            [
                'id' => 1,
                'author_id' => 1,
                'title' => 'First Article',
                'body' => 'First Article Body',
                'published' => 'Y',
                'row_num' => '1',
            ],
            [
                'id' => 2,
                'author_id' => 3,
                'title' => 'Second Article',
                'body' => 'Second Article Body',
                'published' => 'Y',
                'row_num' => '1',
            ],
        ];

        $this->assertEquals($expected, $query->toArray());
    }

    /**
     * Tests subquery() copies connection by default.
     */
    public function testSubqueryConnection(): void
    {
        $subquery = Query::subquery($this->table);
        $this->assertEquals($this->table->getConnection(), $subquery->getConnection());
    }

    /**
     * Tests subquery() disables aliasing.
     */
    public function testSubqueryAliasing(): void
    {
        $articles = $this->getTableLocator()->get('Articles');
        $subquery = Query::subquery($articles);

        $subquery->select('Articles.field1');
        $this->assertRegExpSql(
            'SELECT <Articles>.<field1> FROM <articles> <Articles>',
            $subquery->sql(),
            !$this->connection->getDriver()->isAutoQuotingEnabled()
        );

        $subquery->select($articles, true);
        $this->assertEqualsSql('SELECT id, author_id, title, body, published FROM articles Articles', $subquery->sql());

        $subquery->selectAllExcept($articles, ['author_id'], true);
        $this->assertEqualsSql('SELECT id, title, body, published FROM articles Articles', $subquery->sql());
    }

    /**
     * Tests subquery() in where clause.
     */
    public function testSubqueryWhereClause(): void
    {
        $subquery = Query::subquery($this->getTableLocator()->get('Authors'))
            ->select(['Authors.id'])
            ->where(['Authors.name' => 'mariano']);

        $query = $this->getTableLocator()->get('Articles')->find()
            ->where(['Articles.author_id IN' => $subquery])
            ->order(['Articles.id' => 'ASC']);

        $results = $query->all()->toList();
        $this->assertCount(2, $results);
        $this->assertEquals([1, 3], array_column($results, 'id'));
    }

    /**
     * Tests subquery() in join clause.
     */
    public function testSubqueryJoinClause(): void
    {
        $subquery = Query::subquery($this->getTableLocator()->get('Articles'))
            ->select(['author_id']);

        $query = $this->getTableLocator()->get('Authors')->find();
        $query
            ->select(['Authors.id', 'total_articles' => $query->func()->count('articles.author_id')])
            ->leftJoin(['articles' => $subquery], ['articles.author_id' => new IdentifierExpression('Authors.id')])
            ->group(['Authors.id'])
            ->order(['Authors.id' => 'ASC']);

        $results = $query->all()->toList();
        $this->assertEquals(1, $results[0]->id);
        $this->assertEquals(2, $results[0]->total_articles);
    }

    /**
     * Tests that queries that fetch associated data in separate queries do properly
     * inherit the hydration and results casting mode of the parent query.
     */
    public function testSelectLoaderAssociationsInheritHydrationAndResultsCastingMode(): void
    {
        $articles = $this->getTableLocator()->get('Articles');

        $tags = $articles->belongsToMany('Tags');
        $tags->belongsToMany('Articles');

        $comments = $articles->hasMany('Comments');
        $comments
            ->belongsTo('Articles')
            ->setStrategy(BelongsTo::STRATEGY_SELECT);

        $articles
            ->find()
            ->contain('Comments', function (Query $query) {
                $this->assertFalse($query->isHydrationEnabled());
                $this->assertFalse($query->isResultsCastingEnabled());

                return $query;
            })
            ->contain('Comments.Articles', function (Query $query) {
                $this->assertFalse($query->isHydrationEnabled());
                $this->assertFalse($query->isResultsCastingEnabled());

                return $query;
            })
            ->contain('Comments.Articles.Tags', function (Query $query) {
                $this->assertFalse($query->isHydrationEnabled());
                $this->assertFalse($query->isResultsCastingEnabled());

                return $query
                    ->enableHydration()
                    ->enableResultsCasting();
            })
            ->contain('Comments.Articles.Tags.Articles', function (Query $query) {
                $this->assertTrue($query->isHydrationEnabled());
                $this->assertTrue($query->isResultsCastingEnabled());

                return $query;
            })
            ->disableHydration()
            ->disableResultsCasting()
            ->firstOrFail();
    }
}
