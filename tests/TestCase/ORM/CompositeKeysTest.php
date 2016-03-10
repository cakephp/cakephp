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

use Cake\Datasource\ConnectionManager;
use Cake\ORM\Entity;
use Cake\ORM\Marshaller;
use Cake\ORM\Query;
use Cake\ORM\Table;
use Cake\ORM\TableRegistry;
use Cake\TestSuite\TestCase;

/**
 * Test entity for mass assignment.
 */
class OpenArticleEntity extends Entity
{

    protected $_accessible = [
        '*' => true
    ];
}

/**
 * Integration tetss for table operations involving composite keys
 */
class CompositeKeyTest extends TestCase
{

    /**
     * Fixture to be used
     *
     * @var array
     */
    public $fixtures = [
        'core.composite_increments',
        'core.site_articles',
        'core.site_articles_tags',
        'core.site_authors',
        'core.site_tags'
    ];

    /**
     * setUp method
     *
     * @return void
     */
    public function setUp()
    {
        parent::setUp();
        $this->connection = ConnectionManager::get('test');
    }

    /**
     * Data provider for the two types of strategies HasOne implements
     *
     * @return void
     */
    public function strategiesProviderHasOne()
    {
        return [['join'], ['select']];
    }

    /**
     * Data provider for the two types of strategies HasMany implements
     *
     * @return void
     */
    public function strategiesProviderHasMany()
    {
        return [['subquery'], ['select']];
    }

    /**
     * Data provider for the two types of strategies BelongsTo implements
     *
     * @return void
     */
    public function strategiesProviderBelongsTo()
    {
        return [['join'], ['select']];
    }

    /**
     * Data provider for the two types of strategies BelongsToMany implements
     *
     * @return void
     */
    public function strategiesProviderBelongsToMany()
    {
        return [['subquery'], ['select']];
    }

    /**
     * Test that you cannot save rows with composite keys if some columns are missing.
     *
     * @group save
     * @expectedException \RuntimeException
     * @expectedExceptionMessage Cannot insert row, some of the primary key values are missing
     * @return void
     */
    public function testSaveNewErrorCompositeKeyNoIncrement()
    {
        $articles = TableRegistry::get('SiteArticles');
        $article = $articles->newEntity(['site_id' => 1, 'author_id' => 1, 'title' => 'testing']);
        $articles->save($article);
    }

    /**
     * Test that saving into composite primary keys where one column is missing & autoIncrement works.
     *
     * SQLite is skipped because it doesn't support autoincrement composite keys.
     *
     * @group save
     * @return void
     */
    public function testSaveNewCompositeKeyIncrement()
    {
        $this->skipIfSqlite();
        $table = TableRegistry::get('CompositeIncrements');
        $thing = $table->newEntity(['account_id' => 3, 'name' => 'new guy']);
        $this->assertSame($thing, $table->save($thing));
        $this->assertNotEmpty($thing->id, 'Primary key should have been populated');
        $this->assertSame(3, $thing->account_id);
    }

    /**
     * Tests that HasMany associations are correctly eager loaded and results
     * correctly nested when multiple foreignKeys are used
     *
     * @dataProvider strategiesProviderHasMany
     * @return void
     */
    public function testHasManyEager($strategy)
    {
        $table = TableRegistry::get('SiteAuthors');
        $table->hasMany('SiteArticles', [
            'propertyName' => 'articles',
            'strategy' => $strategy,
            'sort' => ['SiteArticles.id' => 'asc'],
            'foreignKey' => ['author_id', 'site_id']
        ]);
        $query = new Query($this->connection, $table);

        $results = $query->select()
            ->contain('SiteArticles')
            ->hydrate(false)
            ->toArray();
        $expected = [
            [
                'id' => 1,
                'name' => 'mark',
                'site_id' => 1,
                'articles' => [
                    [
                        'id' => 1,
                        'title' => 'First Article',
                        'body' => 'First Article Body',
                        'author_id' => 1,
                        'site_id' => 1
                    ]
                ]
            ],
            [
                'id' => 2,
                'name' => 'juan',
                'site_id' => 2,
                'articles' => [],
            ],
            [
                'id' => 3,
                'name' => 'jose',
                'site_id' => 2,
                'articles' => [
                    [
                        'id' => 2,
                        'title' => 'Second Article',
                        'body' => 'Second Article Body',
                        'author_id' => 3,
                        'site_id' => 2
                    ]
                ]
            ],
            [
                'id' => 4,
                'name' => 'andy',
                'site_id' => 1,
                'articles' => [],
            ]
        ];
        $this->assertEquals($expected, $results);

        $results = $query->repository($table)
            ->select()
            ->contain(['SiteArticles' => ['conditions' => ['SiteArticles.id' => 2]]])
            ->hydrate(false)
            ->toArray();
        $expected[0]['articles'] = [];
        $this->assertEquals($expected, $results);
        $this->assertEquals($table->association('SiteArticles')->strategy(), $strategy);
    }

    /**
     * Tests that BelongsToMany associations are correctly eager loaded when multiple
     * foreignKeys are used
     *
     * @dataProvider strategiesProviderBelongsToMany
     * @return void
     */
    public function testBelongsToManyEager($strategy)
    {
        $articles = TableRegistry::get('SiteArticles');
        $tags = TableRegistry::get('SiteTags');
        $junction = TableRegistry::get('SiteArticlesTags');
        $articles->belongsToMany('SiteTags', [
            'strategy' => $strategy,
            'targetTable' => $tags,
            'propertyName' => 'tags',
            'through' => 'SiteArticlesTags',
            'sort' => ['SiteTags.id' => 'asc'],
            'foreignKey' => ['article_id', 'site_id'],
            'targetForeignKey' => ['tag_id', 'site_id']
        ]);
        $query = new Query($this->connection, $articles);

        $results = $query->select()->contain('SiteTags')->hydrate(false)->toArray();
        $expected = [
            [
                'id' => 1,
                'author_id' => 1,
                'title' => 'First Article',
                'body' => 'First Article Body',
                'site_id' => 1,
                'tags' => [
                    [
                        'id' => 1,
                        'name' => 'tag1',
                        '_joinData' => ['article_id' => 1, 'tag_id' => 1, 'site_id' => 1],
                        'site_id' => 1
                    ],
                    [
                        'id' => 3,
                        'name' => 'tag3',
                        '_joinData' => ['article_id' => 1, 'tag_id' => 3, 'site_id' => 1],
                        'site_id' => 1
                    ]
                ]
            ],
            [
                'id' => 2,
                'title' => 'Second Article',
                'body' => 'Second Article Body',
                'author_id' => 3,
                'site_id' => 2,
                'tags' => [
                    [
                        'id' => 4,
                        'name' => 'tag4',
                        '_joinData' => ['article_id' => 2, 'tag_id' => 4, 'site_id' => 2],
                        'site_id' => 2
                    ]
                ]
            ],
            [
                'id' => 3,
                'title' => 'Third Article',
                'body' => 'Third Article Body',
                'author_id' => 1,
                'site_id' => 2,
                'tags' => [],
            ],
            [
                'id' => 4,
                'title' => 'Fourth Article',
                'body' => 'Fourth Article Body',
                'author_id' => 3,
                'site_id' => 1,
                'tags' => [
                    [
                        'id' => 1,
                        'name' => 'tag1',
                        '_joinData' => ['article_id' => 4, 'tag_id' => 1, 'site_id' => 1],
                        'site_id' => 1
                    ]
                ]
            ],
        ];
        $this->assertEquals($expected, $results);
        $this->assertEquals($articles->association('SiteTags')->strategy(), $strategy);
    }

    /**
     * Tests loding belongsTo with composite keys
     *
     * @dataProvider strategiesProviderBelongsTo
     * @return void
     */
    public function testBelongsToEager($strategy)
    {
        $table = TableRegistry::get('SiteArticles');
        $table->belongsTo('SiteAuthors', [
            'propertyName' => 'author',
            'strategy' => $strategy,
            'foreignKey' => ['author_id', 'site_id']
        ]);
        $query = new Query($this->connection, $table);
        $results = $query->select()
            ->where(['SiteArticles.id IN' => [1, 2]])
            ->contain('SiteAuthors')
            ->hydrate(false)
            ->toArray();
        $expected = [
            [
                'id' => 1,
                'author_id' => 1,
                'site_id' => 1,
                'title' => 'First Article',
                'body' => 'First Article Body',
                'author' => [
                    'id' => 1,
                    'name' => 'mark',
                    'site_id' => 1
                ]
            ],
            [
                'id' => 2,
                'author_id' => 3,
                'site_id' => 2,
                'title' => 'Second Article',
                'body' => 'Second Article Body',
                'author' => [
                    'id' => 3,
                    'name' => 'jose',
                    'site_id' => 2
                ]
            ]
        ];
        $this->assertEquals($expected, $results);
    }

    /**
     * Tests loding hasOne with composite keys
     *
     * @dataProvider strategiesProviderHasOne
     * @return void
     */
    public function testHasOneEager($strategy)
    {
        $table = TableRegistry::get('SiteAuthors');
        $table->hasOne('SiteArticles', [
            'propertyName' => 'first_article',
            'strategy' => $strategy,
            'foreignKey' => ['author_id', 'site_id']
        ]);
        $query = new Query($this->connection, $table);
        $results = $query->select()
            ->where(['SiteAuthors.id IN' => [1, 3]])
            ->contain('SiteArticles')
            ->hydrate(false)
            ->toArray();

        $expected = [
            [
                'id' => 1,
                'name' => 'mark',
                'site_id' => 1,
                'first_article' => [
                    'id' => 1,
                    'author_id' => 1,
                    'site_id' => 1,
                    'title' => 'First Article',
                    'body' => 'First Article Body'
                ]
            ],
            [
                'id' => 3,
                'name' => 'jose',
                'site_id' => 2,
                'first_article' => [
                    'id' => 2,
                    'author_id' => 3,
                    'site_id' => 2,
                    'title' => 'Second Article',
                    'body' => 'Second Article Body'
                ]
            ]
        ];
        $this->assertEquals($expected, $results);
    }

    /**
     * Tests that it is possible to insert a new row using the save method
     * if the entity has composite primary key
     *
     * @group save
     * @return void
     */
    public function testSaveNewEntity()
    {
        $entity = new \Cake\ORM\Entity([
            'id' => 5,
            'site_id' => 1,
            'title' => 'Fifth Article',
            'body' => 'Fifth Article Body',
            'author_id' => 3,
        ]);
        $table = TableRegistry::get('SiteArticles');
        $this->assertSame($entity, $table->save($entity));
        $this->assertEquals($entity->id, 5);

        $row = $table->find('all')->where(['id' => 5, 'site_id' => 1])->first();
        $this->assertEquals($entity->toArray(), $row->toArray());
    }

    /**
     * Tests that it is possible to insert a new row using the save method
     * if the entity has composite primary key
     *
     * @group save
     * @expectedException \RuntimeException
     * @expectedExceptionMessage Cannot insert row, some of the primary key values are missing. Got (5, ), expecting (id, site_id)
     * @return void
     */
    public function testSaveNewEntityMissingKey()
    {
        $entity = new \Cake\ORM\Entity([
            'id' => 5,
            'title' => 'Fifth Article',
            'body' => 'Fifth Article Body',
            'author_id' => 3,
        ]);
        $table = TableRegistry::get('SiteArticles');
        $table->save($entity);
    }

    /**
     * Test simple delete with composite primary key
     *
     * @return void
     */
    public function testDelete()
    {
        $table = TableRegistry::get('SiteAuthors');
        $table->save(new \Cake\ORM\Entity(['id' => 1, 'site_id' => 2]));
        $entity = $table->get([1, 1]);
        $result = $table->delete($entity);
        $this->assertTrue($result);

        $this->assertEquals(4, $table->find('all')->count());
        $this->assertEmpty($table->find()->where(['id' => 1, 'site_id' => 1])->first());
    }

    /**
     * Test delete with dependent records having composite keys
     *
     * @return void
     */
    public function testDeleteDependent()
    {
        $table = TableRegistry::get('SiteAuthors');
        $table->hasMany('SiteArticles', [
            'foreignKey' => ['author_id', 'site_id'],
            'dependent' => true,
        ]);

        $entity = $table->get([3, 2]);
        $result = $table->delete($entity);

        $query = $table->association('SiteArticles')->find('all', [
            'conditions' => [
                'author_id' => $entity->id,
                'site_id' => $entity->site_id
            ]
        ]);
        $this->assertNull($query->all()->first(), 'Should not find any rows.');
    }

    /**
     * Test generating a list of entities from a list of composite ids
     *
     * @return void
     */
    public function testOneGenerateBelongsToManyEntitiesFromIds()
    {
        $articles = TableRegistry::get('SiteArticles');
        $articles->entityClass(__NAMESPACE__ . '\OpenArticleEntity');
        $tags = TableRegistry::get('SiteTags');
        $junction = TableRegistry::get('SiteArticlesTags');
        $articles->belongsToMany('SiteTags', [
            'targetTable' => $tags,
            'propertyName' => 'tags',
            'through' => 'SiteArticlesTags',
            'foreignKey' => ['article_id', 'site_id'],
            'targetForeignKey' => ['tag_id', 'site_id']
        ]);

        $data = [
            'title' => 'Haz tags',
            'body' => 'Some content here',
            'tags' => ['_ids' => [[1, 1], [2, 2], [3, 1]]]
        ];
        $marshall = new Marshaller($articles);
        $result = $marshall->one($data, ['associated' => ['SiteTags']]);

        $this->assertCount(3, $result->tags);
        $this->assertInstanceOf('Cake\ORM\Entity', $result->tags[0]);
        $this->assertInstanceOf('Cake\ORM\Entity', $result->tags[1]);
        $this->assertInstanceOf('Cake\ORM\Entity', $result->tags[2]);

        $data = [
            'title' => 'Haz tags',
            'body' => 'Some content here',
            'tags' => ['_ids' => [1, 2, 3]]
        ];
        $marshall = new Marshaller($articles);
        $result = $marshall->one($data, ['associated' => ['SiteTags']]);
        $this->assertEmpty($result->tags);
    }

    /**
     * Tests find('list') with composite keys
     *
     * @return void
     */
    public function testFindListCompositeKeys()
    {
        $table = new Table([
            'table' => 'site_authors',
            'connection' => $this->connection,
        ]);
        $table->displayField('name');
        $query = $table->find('list')
            ->hydrate(false)
            ->order('id');
        $expected = [
            '1;1' => 'mark',
            '2;2' => 'juan',
            '3;2' => 'jose',
            '4;1' => 'andy'
        ];
        $this->assertEquals($expected, $query->toArray());

        $table->displayField(['name', 'site_id']);
        $query = $table->find('list')
            ->hydrate(false)
            ->order('id');
        $expected = [
            '1;1' => 'mark;1',
            '2;2' => 'juan;2',
            '3;2' => 'jose;2',
            '4;1' => 'andy;1'
        ];
        $this->assertEquals($expected, $query->toArray());

        $query = $table->find('list', ['groupField' => ['site_id', 'site_id']])
            ->hydrate(false)
            ->order('id');
        $expected = [
            '1;1' => [
                '1;1' => 'mark;1',
                '4;1' => 'andy;1'
            ],
            '2;2' => [
                '2;2' => 'juan;2',
                '3;2' => 'jose;2'
            ]
        ];
        $this->assertEquals($expected, $query->toArray());
    }

    /**
     * Tests find('threaded') with composite keys
     *
     * @return void
     */
    public function testFindThreadedCompositeKeys()
    {
        $table = TableRegistry::get('SiteAuthors');
        $query = $this->getMock(
            '\Cake\ORM\Query',
            ['_addDefaultFields', 'execute'],
            [$this->connection, $table]
        );

        $items = new \Cake\Datasource\ResultSetDecorator([
            ['id' => 1, 'name' => 'a', 'site_id' => 1, 'parent_id' => null],
            ['id' => 2, 'name' => 'a', 'site_id' => 2, 'parent_id' => null],
            ['id' => 3, 'name' => 'a', 'site_id' => 1, 'parent_id' => 1],
            ['id' => 4, 'name' => 'a', 'site_id' => 2, 'parent_id' => 2],
            ['id' => 5, 'name' => 'a', 'site_id' => 2, 'parent_id' => 4],
            ['id' => 6, 'name' => 'a', 'site_id' => 1, 'parent_id' => 2],
            ['id' => 7, 'name' => 'a', 'site_id' => 1, 'parent_id' => 3],
            ['id' => 8, 'name' => 'a', 'site_id' => 2, 'parent_id' => 4],
        ]);
        $query->find('threaded', ['parentField' => ['parent_id', 'site_id']]);
        $formatter = $query->formatResults()[0];

        $expected = [
            [
                'id' => 1,
                'name' => 'a',
                'site_id' => 1,
                'parent_id' => null,
                'children' => [
                    [
                        'id' => 3,
                        'name' => 'a',
                        'site_id' => 1,
                        'parent_id' => 1,
                        'children' => [
                            [
                                'id' => 7,
                                'name' => 'a',
                                'site_id' => 1,
                                'parent_id' => 3,
                                'children' => []
                            ]
                        ]
                    ]
                ]
            ],
            [
                'id' => 2,
                'name' => 'a',
                'site_id' => 2,
                'parent_id' => null,
                'children' => [
                    [
                        'id' => 4,
                        'name' => 'a',
                        'site_id' => 2,
                        'parent_id' => 2,
                        'children' => [
                            [
                                'id' => 5,
                                'name' => 'a',
                                'site_id' => 2,
                                'parent_id' => 4,
                                'children' => []
                            ],
                            [
                                'id' => 8,
                                'name' => 'a',
                                'site_id' => 2,
                                'parent_id' => 4,
                                'children' => []
                            ]
                        ]
                    ]
                ]
            ],
            [
                'id' => 6,
                'name' => 'a',
                'site_id' => 1,
                'parent_id' => 2,
                'children' => []
            ]
        ];
        $this->assertEquals($expected, $formatter($items)->toArray());
    }

    /**
     * Tets that loadInto() is capable of handling composite primary keys
     *
     * @return void
     */
    public function testLoadInto()
    {
        $table = TableRegistry::get('SiteAuthors');
        $tags = TableRegistry::get('SiteTags');
        $table->hasMany('SiteArticles', [
            'foreignKey' => ['author_id', 'site_id'],
        ]);

        $author = $table->get([1, 1]);
        $result = $table->loadInto($author, ['SiteArticles']);
        $this->assertSame($author, $result);
        $this->assertNotEmpty($result->site_articles);

        $expected = $table->get([1, 1], ['contain' => ['SiteArticles']]);
        $this->assertEquals($expected, $result);
    }

    /**
     * Tets that loadInto() is capable of handling composite primary keys
     * when loading belongsTo assocaitions
     *
     * @return void
     */
    public function testLoadIntoWithBelongsTo()
    {
        $table = TableRegistry::get('SiteArticles');
        $table->belongsTo('SiteAuthors', [
            'foreignKey' => ['author_id', 'site_id'],
        ]);

        $author = $table->get([2, 2]);
        $result = $table->loadInto($author, ['SiteAuthors']);
        $this->assertSame($author, $result);
        $this->assertNotEmpty($result->site_author);

        $expected = $table->get([2, 2], ['contain' => ['SiteAuthors']]);
        $this->assertEquals($expected, $result);
    }

    /**
     * Tets that loadInto() is capable of handling composite primary keys
     * when loading into multiple entities
     *
     * @return void
     */
    public function testLoadIntoMany()
    {
        $table = TableRegistry::get('SiteAuthors');
        $tags = TableRegistry::get('SiteTags');
        $table->hasMany('SiteArticles', [
            'foreignKey' => ['author_id', 'site_id'],
        ]);

        $authors = $table->find()->toList();
        $result = $table->loadInto($authors, ['SiteArticles']);

        foreach ($authors as $k => $v) {
            $this->assertSame($result[$k], $v);
        }

        $expected = $table->find('all', ['contain' => ['SiteArticles']])->toList();
        $this->assertEquals($expected, $result);
    }

    /**
     * Helper method to skip tests when connection is SQLite.
     *
     * @return void
     */
    public function skipIfSqlite()
    {
        $this->skipIf(
            $this->connection->driver() instanceof \Cake\Database\Driver\Sqlite,
            'SQLite does not support the requrirements of this test.'
        );
    }
}
