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

use Cake\Database\Driver\Sqlite;
use Cake\Database\Driver\Sqlserver;
use Cake\Datasource\ConnectionManager;
use Cake\Datasource\ResultSetDecorator;
use Cake\ORM\Entity;
use Cake\ORM\Marshaller;
use Cake\ORM\Query;
use Cake\ORM\Table;
use Cake\TestSuite\TestCase;
use PDO;
use RuntimeException;
use TestApp\Model\Entity\OpenArticleEntity;

/**
 * Integration tests for table operations involving composite keys
 */
class CompositeKeysTest extends TestCase
{
    /**
     * Fixture to be used
     *
     * @var array<string>
     */
    protected $fixtures = [
        'core.CompositeIncrements',
        'core.SiteArticles',
        'core.SiteArticlesTags',
        'core.SiteAuthors',
        'core.SiteTags',
    ];

    /**
     * @var \Cake\Datasource\ConnectionInterface
     */
    protected $connection;

    /**
     * setUp method
     */
    public function setUp(): void
    {
        parent::setUp();
        $this->connection = ConnectionManager::get('test');
    }

    /**
     * Data provider for the two types of strategies HasOne implements
     *
     * @return array
     */
    public function strategiesProviderHasOne(): array
    {
        return [['join'], ['select']];
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
     * Test that you cannot save rows with composite keys if some columns are missing.
     *
     * @group save
     */
    public function testSaveNewErrorCompositeKeyNoIncrement(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Cannot insert row, some of the primary key values are missing');
        $articles = $this->getTableLocator()->get('SiteArticles');
        $article = $articles->newEntity(['site_id' => 1, 'author_id' => 1, 'title' => 'testing']);
        $articles->save($article);
    }

    /**
     * Test that saving into composite primary keys where one column is missing & autoIncrement works.
     *
     * SQLite is skipped because it doesn't support autoincrement composite keys.
     *
     * @group save
     */
    public function testSaveNewCompositeKeyIncrement(): void
    {
        $this->skipIfSqlite();
        $table = $this->getTableLocator()->get('CompositeIncrements');
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
     */
    public function testHasManyEager(string $strategy): void
    {
        $table = $this->getTableLocator()->get('SiteAuthors');
        $table->hasMany('SiteArticles', [
            'propertyName' => 'articles',
            'strategy' => $strategy,
            'sort' => ['SiteArticles.id' => 'asc'],
            'foreignKey' => ['author_id', 'site_id'],
        ]);
        $query = new Query($this->connection, $table);

        $results = $query->select()
            ->contain('SiteArticles')
            ->enableHydration(false)
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
                        'site_id' => 1,
                    ],
                ],
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
                        'site_id' => 2,
                    ],
                ],
            ],
            [
                'id' => 4,
                'name' => 'andy',
                'site_id' => 1,
                'articles' => [],
            ],
        ];
        $this->assertEquals($expected, $results);

        $results = $query->setRepository($table)
            ->select()
            ->contain(['SiteArticles' => ['conditions' => ['SiteArticles.id' => 2]]])
            ->enableHydration(false)
            ->toArray();
        $expected[0]['articles'] = [];
        $this->assertEquals($expected, $results);
        $this->assertSame($table->getAssociation('SiteArticles')->getStrategy(), $strategy);
    }

    /**
     * Tests that BelongsToMany associations are correctly eager loaded when multiple
     * foreignKeys are used
     *
     * @dataProvider strategiesProviderBelongsToMany
     */
    public function testBelongsToManyEager(string $strategy): void
    {
        $articles = $this->getTableLocator()->get('SiteArticles');
        $tags = $this->getTableLocator()->get('SiteTags');
        $junction = $this->getTableLocator()->get('SiteArticlesTags');
        $articles->belongsToMany('SiteTags', [
            'strategy' => $strategy,
            'targetTable' => $tags,
            'propertyName' => 'tags',
            'through' => 'SiteArticlesTags',
            'sort' => ['SiteTags.id' => 'asc'],
            'foreignKey' => ['article_id', 'site_id'],
            'targetForeignKey' => ['tag_id', 'site_id'],
        ]);
        $query = new Query($this->connection, $articles);

        $results = $query->select()->contain('SiteTags')->enableHydration(false)->toArray();
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
                        'site_id' => 1,
                    ],
                    [
                        'id' => 3,
                        'name' => 'tag3',
                        '_joinData' => ['article_id' => 1, 'tag_id' => 3, 'site_id' => 1],
                        'site_id' => 1,
                    ],
                ],
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
                        'site_id' => 2,
                    ],
                ],
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
                        'site_id' => 1,
                    ],
                ],
            ],
        ];
        $this->assertEquals($expected, $results);
        $this->assertSame($articles->getAssociation('SiteTags')->getStrategy(), $strategy);
    }

    /**
     * Tests loading belongsTo with composite keys
     *
     * @dataProvider strategiesProviderBelongsTo
     */
    public function testBelongsToEager(string $strategy): void
    {
        $table = $this->getTableLocator()->get('SiteArticles');
        $table->belongsTo('SiteAuthors', [
            'propertyName' => 'author',
            'strategy' => $strategy,
            'foreignKey' => ['author_id', 'site_id'],
        ]);
        $query = new Query($this->connection, $table);
        $results = $query->select()
            ->where(['SiteArticles.id IN' => [1, 2]])
            ->contain('SiteAuthors')
            ->enableHydration(false)
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
                    'site_id' => 1,
                ],
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
                    'site_id' => 2,
                ],
            ],
        ];
        $this->assertEquals($expected, $results);
    }

    /**
     * Tests loading hasOne with composite keys
     *
     * @dataProvider strategiesProviderHasOne
     */
    public function testHasOneEager(string $strategy): void
    {
        $table = $this->getTableLocator()->get('SiteAuthors');
        $table->hasOne('SiteArticles', [
            'propertyName' => 'first_article',
            'strategy' => $strategy,
            'foreignKey' => ['author_id', 'site_id'],
        ]);
        $query = new Query($this->connection, $table);
        $results = $query->select()
            ->where(['SiteAuthors.id IN' => [1, 3]])
            ->contain('SiteArticles')
            ->enableHydration(false)
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
                    'body' => 'First Article Body',
                ],
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
                    'body' => 'Second Article Body',
                ],
            ],
        ];
        $this->assertEquals($expected, $results);
    }

    /**
     * Tests that it is possible to insert a new row using the save method
     * if the entity has composite primary key
     *
     * @group save
     */
    public function testSaveNewEntity(): void
    {
        $entity = new Entity([
            'id' => 5,
            'site_id' => 1,
            'title' => 'Fifth Article',
            'body' => 'Fifth Article Body',
            'author_id' => 3,
        ]);
        $table = $this->getTableLocator()->get('SiteArticles');
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
     */
    public function testSaveNewEntityMissingKey(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Cannot insert row, some of the primary key values are missing. Got (5, ), expecting (id, site_id)');
        $entity = new Entity([
            'id' => 5,
            'title' => 'Fifth Article',
            'body' => 'Fifth Article Body',
            'author_id' => 3,
        ]);
        $table = $this->getTableLocator()->get('SiteArticles');
        $table->save($entity);
    }

    /**
     * Test simple delete with composite primary key
     */
    public function testDelete(): void
    {
        $table = $this->getTableLocator()->get('SiteAuthors');
        $table->save(new Entity(['id' => 1, 'site_id' => 2]));
        $entity = $table->get([1, 1]);
        $result = $table->delete($entity);
        $this->assertTrue($result);

        $this->assertSame(4, $table->find('all')->count());
        $this->assertEmpty($table->find()->where(['id' => 1, 'site_id' => 1])->first());
    }

    /**
     * Test delete with dependent records having composite keys
     */
    public function testDeleteDependent(): void
    {
        $table = $this->getTableLocator()->get('SiteAuthors');
        $table->hasMany('SiteArticles', [
            'foreignKey' => ['author_id', 'site_id'],
            'dependent' => true,
        ]);

        $entity = $table->get([3, 2]);
        $result = $table->delete($entity);

        $query = $table->getAssociation('SiteArticles')->find('all', [
            'conditions' => [
                'author_id' => $entity->id,
                'site_id' => $entity->site_id,
            ],
        ]);
        $this->assertNull($query->all()->first(), 'Should not find any rows.');
    }

    /**
     * Test generating a list of entities from a list of composite ids
     */
    public function testOneGenerateBelongsToManyEntitiesFromIds(): void
    {
        $articles = $this->getTableLocator()->get('SiteArticles');
        $articles->setEntityClass(OpenArticleEntity::class);
        $tags = $this->getTableLocator()->get('SiteTags');
        $junction = $this->getTableLocator()->get('SiteArticlesTags');
        $articles->belongsToMany('SiteTags', [
            'targetTable' => $tags,
            'propertyName' => 'tags',
            'through' => 'SiteArticlesTags',
            'foreignKey' => ['article_id', 'site_id'],
            'targetForeignKey' => ['tag_id', 'site_id'],
        ]);

        $data = [
            'title' => 'Haz tags',
            'body' => 'Some content here',
            'tags' => ['_ids' => [[1, 1], [2, 2], [3, 1]]],
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
            'tags' => ['_ids' => [1, 2, 3]],
        ];
        $marshall = new Marshaller($articles);
        $result = $marshall->one($data, ['associated' => ['SiteTags']]);
        $this->assertEmpty($result->tags);
    }

    /**
     * Tests find('list') with composite keys
     */
    public function testFindListCompositeKeys(): void
    {
        $table = new Table([
            'table' => 'site_authors',
            'connection' => $this->connection,
        ]);
        $table->setDisplayField('name');
        $query = $table->find('list')
            ->enableHydration(false)
            ->order('id');
        $expected = [
            '1;1' => 'mark',
            '2;2' => 'juan',
            '3;2' => 'jose',
            '4;1' => 'andy',
        ];
        $this->assertEquals($expected, $query->toArray());

        $table->setDisplayField(['name', 'site_id']);
        $query = $table->find('list')
            ->enableHydration(false)
            ->order('id');
        $expected = [
            '1;1' => 'mark;1',
            '2;2' => 'juan;2',
            '3;2' => 'jose;2',
            '4;1' => 'andy;1',
        ];
        $this->assertEquals($expected, $query->toArray());

        $query = $table->find('list', ['groupField' => ['site_id', 'site_id']])
            ->enableHydration(false)
            ->order('id');
        $expected = [
            '1;1' => [
                '1;1' => 'mark;1',
                '4;1' => 'andy;1',
            ],
            '2;2' => [
                '2;2' => 'juan;2',
                '3;2' => 'jose;2',
            ],
        ];
        $this->assertEquals($expected, $query->toArray());
    }

    /**
     * Tests find('threaded') with composite keys
     */
    public function testFindThreadedCompositeKeys(): void
    {
        $table = $this->getTableLocator()->get('SiteAuthors');
        $query = $this->getMockBuilder('Cake\ORM\Query')
            ->onlyMethods(['_addDefaultFields', 'execute'])
            ->setConstructorArgs([$table->getConnection(), $table])
            ->getMock();

        $items = new ResultSetDecorator([
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
        $formatter = $query->getResultFormatters()[0];

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
                                'children' => [],
                            ],
                        ],
                    ],
                ],
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
                                'children' => [],
                            ],
                            [
                                'id' => 8,
                                'name' => 'a',
                                'site_id' => 2,
                                'parent_id' => 4,
                                'children' => [],
                            ],
                        ],
                    ],
                ],
            ],
            [
                'id' => 6,
                'name' => 'a',
                'site_id' => 1,
                'parent_id' => 2,
                'children' => [],
            ],
        ];
        $this->assertEquals($expected, $formatter($items)->toArray());
    }

    /**
     * Tests that loadInto() is capable of handling composite primary keys
     */
    public function testLoadInto(): void
    {
        $table = $this->getTableLocator()->get('SiteAuthors');
        $tags = $this->getTableLocator()->get('SiteTags');
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
     * Tests that loadInto() is capable of handling composite primary keys
     * when loading belongsTo associations
     */
    public function testLoadIntoWithBelongsTo(): void
    {
        $table = $this->getTableLocator()->get('SiteArticles');
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
     * Tests that loadInto() is capable of handling composite primary keys
     * when loading into multiple entities
     */
    public function testLoadIntoMany(): void
    {
        $table = $this->getTableLocator()->get('SiteAuthors');
        $table->hasMany('SiteArticles', [
            'foreignKey' => ['author_id', 'site_id'],
        ]);

        /** @var \Cake\Datasource\EntityInterface[] $authors */
        $authors = $table->find()->toArray();
        $result = $table->loadInto($authors, ['SiteArticles']);

        foreach ($authors as $k => $v) {
            $this->assertSame($result[$k], $v);
        }

        /** @var \Cake\Datasource\EntityInterface[] $expected */
        $expected = $table->find('all', ['contain' => ['SiteArticles']])->toArray();
        $this->assertEquals($expected, $result);
    }

    /**
     * Tests notMatching() with a belongsToMany association
     */
    public function testNotMatchingBelongsToMany(): void
    {
        $driver = $this->connection->getDriver();

        if ($driver instanceof Sqlserver) {
            $this->markTestSkipped('Sqlserver does not support the requirements of this test.');
        } elseif ($driver instanceof Sqlite) {
            $serverVersion = $driver->getConnection()->getAttribute(PDO::ATTR_SERVER_VERSION);
            if (version_compare($serverVersion, '3.15.0', '<')) {
                $this->markTestSkipped("Sqlite ($serverVersion) does not support the requirements of this test.");
            }
        }

        $articles = $this->getTableLocator()->get('SiteArticles');
        $articles->belongsToMany('SiteTags', [
            'through' => 'SiteArticlesTags',
            'foreignKey' => ['article_id', 'site_id'],
            'targetForeignKey' => ['tag_id', 'site_id'],
        ]);

        $results = $articles->find()
            ->enableHydration(false)
            ->notMatching('SiteTags')
            ->toArray();

        $expected = [
            [
                'id' => 3,
                'author_id' => 1,
                'site_id' => 2,
                'title' => 'Third Article',
                'body' => 'Third Article Body',
            ],
        ];

        $this->assertEquals($expected, $results);
    }

    /**
     * Helper method to skip tests when connection is SQLite.
     */
    public function skipIfSqlite(): void
    {
        $this->skipIf(
            $this->connection->getDriver() instanceof Sqlite,
            'SQLite does not support the requirements of this test.'
        );
    }
}
