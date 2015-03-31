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

use Cake\Core\Plugin;
use Cake\I18n\Time;
use Cake\ORM\Query;
use Cake\ORM\Table;
use Cake\ORM\TableRegistry;
use Cake\TestSuite\TestCase;

/**
 * Contains regression test for the Query builder
 *
 */
class QueryRegressionTest extends TestCase
{

    /**
     * Fixture to be used
     *
     * @var array
     */
    public $fixtures = [
        'core.users',
        'core.articles',
        'core.comments',
        'core.tags',
        'core.articles_tags',
        'core.authors',
        'core.special_tags',
        'core.translates',
        'core.authors_tags',
    ];

    /**
     * Tear down
     *
     * @return void
     */
    public function tearDown()
    {
        parent::tearDown();

        TableRegistry::clear();
    }

    /**
     * Test for https://github.com/cakephp/cakephp/issues/3087
     *
     * @return void
     */
    public function testSelectTimestampColumn()
    {
        $table = TableRegistry::get('users');
        $user = $table->find()->where(['id' => 1])->first();
        $this->assertEquals(new Time('2007-03-17 01:16:23'), $user->created);
        $this->assertEquals(new Time('2007-03-17 01:18:31'), $user->updated);
    }

    /**
     * Tests that EagerLoader does not try to create queries for associations having no
     * keys to compare against
     *
     * @return void
     */
    public function testEagerLoadingFromEmptyResults()
    {
        $table = TableRegistry::get('Articles');
        $table->belongsToMany('ArticlesTags');
        $results = $table->find()->where(['id >' => 100])->contain('ArticlesTags')->toArray();
        $this->assertEmpty($results);
    }

    /**
     * Tests that eagerloading belongsToMany with find list fails with a helpful message.
     *
     * @expectedException \RuntimeException
     * @return void
     */
    public function testEagerLoadingBelongsToManyList()
    {
        $table = TableRegistry::get('Articles');
        $table->belongsToMany('Tags', [
            'finder' => 'list'
        ]);
        $table->find()->contain('Tags')->toArray();
    }

    /**
     * Tests that duplicate aliases in contain() can be used, even when they would
     * naturally be attached to the query instead of eagerly loaded. What should
     * happen here is that One of the duplicates will be changed to be loaded using
     * an extra query, but yielding the same results
     *
     * @return void
     */
    public function testDuplicateAttachableAliases()
    {
        TableRegistry::get('Stuff', ['table' => 'tags']);
        TableRegistry::get('Things', ['table' => 'articles_tags']);

        $table = TableRegistry::get('Articles');
        $table->belongsTo('Authors');
        $table->hasOne('Things', ['propertyName' => 'articles_tag']);
        $table->Authors->target()->hasOne('Stuff', [
            'foreignKey' => 'id',
            'propertyName' => 'favorite_tag'
        ]);
        $table->Things->target()->belongsTo('Stuff', [
            'foreignKey' => 'tag_id',
            'propertyName' => 'foo'
        ]);

        $results = $table->find()
            ->contain(['Authors.Stuff', 'Things.Stuff'])
            ->order(['Articles.id' => 'ASC'])
            ->toArray();

        $this->assertEquals(1, $results[0]->articles_tag->foo->id);
        $this->assertEquals(1, $results[0]->author->favorite_tag->id);
        $this->assertEquals(2, $results[1]->articles_tag->foo->id);
        $this->assertEquals(1, $results[0]->author->favorite_tag->id);
        $this->assertEquals(1, $results[2]->articles_tag->foo->id);
        $this->assertEquals(3, $results[2]->author->favorite_tag->id);
        $this->assertEquals(3, $results[3]->articles_tag->foo->id);
        $this->assertEquals(3, $results[3]->author->favorite_tag->id);
    }

    /**
     * Test for https://github.com/cakephp/cakephp/issues/3410
     *
     * @return void
     */
    public function testNullableTimeColumn()
    {
        $table = TableRegistry::get('users');
        $entity = $table->newEntity(['username' => 'derp', 'created' => null]);
        $this->assertSame($entity, $table->save($entity));
        $this->assertNull($entity->created);
    }

    /**
     * Test for https://github.com/cakephp/cakephp/issues/3626
     *
     * Checks that join data is actually created and not tried to be updated every time
     * @return void
     */
    public function testCreateJointData()
    {
        $articles = TableRegistry::get('Articles');
        $articles->belongsToMany('Highlights', [
            'className' => 'TestApp\Model\Table\TagsTable',
            'foreignKey' => 'article_id',
            'targetForeignKey' => 'tag_id',
            'through' => 'SpecialTags'
        ]);
        $entity = $articles->get(2);
        $data = [
            'id' => 2,
            'highlights' => [
                [
                    'name' => 'New Special Tag',
                    '_joinData' => ['highlighted' => true, 'highlighted_time' => '2014-06-01 10:10:00']
                ]
            ]
        ];
        $entity = $articles->patchEntity($entity, $data, ['Highlights._joinData']);
        $articles->save($entity);
        $entity = $articles->get(2, ['contain' => ['Highlights']]);
        $this->assertEquals(4, $entity->highlights[0]->_joinData->tag_id);
        $this->assertEquals('2014-06-01', $entity->highlights[0]->_joinData->highlighted_time->format('Y-m-d'));
    }

    /**
     * Tests that the junction table instance taken from both sides of a belongsToMany
     * relationship is actually the same object.
     *
     * @return void
     */
    public function testReciprocalBelongsToMany()
    {
        $articles = TableRegistry::get('Articles');
        $tags = TableRegistry::get('Tags');

        $articles->belongsToMany('Tags');
        $tags->belongsToMany('Articles');

        $left = $articles->Tags->junction();
        $right = $tags->Articles->junction();
        $this->assertSame($left, $right);
    }

    /**
     * Test for https://github.com/cakephp/cakephp/issues/4253
     *
     * Makes sure that the belongsToMany association is not overwritten with conflicting information
     * by any of the sides when the junction() function is invoked
     *
     * @return void
     */
    public function testReciprocalBelongsToMany2()
    {
        $articles = TableRegistry::get('Articles');
        $tags = TableRegistry::get('Tags');

        $articles->belongsToMany('Tags');
        $tags->belongsToMany('Articles');

        $sub = $articles->Tags->find()->select(['id'])->matching('Articles', function ($q) {
            return $q->where(['Articles.id' => 1]);
        });

        $query = $articles->Tags->find()->where(['id NOT IN' => $sub]);
        $this->assertEquals(1, $query->count());
    }

    /**
     * Returns an array with the saving strategies for a belongsTo association
     *
     * @return array
     */
    public function strategyProvider()
    {
        return [['append', 'replace']];
    }

    /**
     * Test for https://github.com/cakephp/cakephp/issues/3677 and
     * https://github.com/cakephp/cakephp/issues/3714
     *
     * Checks that only relevant associations are passed when saving _joinData
     * Tests that _joinData can also save deeper associations
     *
     * @dataProvider strategyProvider
     * @param string $strategy
     * @return void
     */
    public function testBelongsToManyDeepSave($strategy)
    {
        $articles = TableRegistry::get('Articles');
        $articles->belongsToMany('Highlights', [
            'className' => 'TestApp\Model\Table\TagsTable',
            'foreignKey' => 'article_id',
            'targetForeignKey' => 'tag_id',
            'through' => 'SpecialTags',
            'saveStrategy' => $strategy
        ]);
        $articles->Highlights->junction()->belongsTo('Authors');
        $articles->Highlights->hasOne('Authors', [
            'foreignKey' => 'id'
        ]);
        $entity = $articles->get(2, ['contain' => ['Highlights']]);

        $data = [
            'highlights' => [
                [
                    'name' => 'New Special Tag',
                    '_joinData' => [
                        'highlighted' => true,
                        'highlighted_time' => '2014-06-01 10:10:00',
                        'author' => [
                            'name' => 'mariano'
                        ]
                    ],
                    'author' => ['name' => 'mark']
                ]
            ]
        ];
        $options = [
            'associated' => [
                'Highlights._joinData.Authors', 'Highlights.Authors'
            ]
        ];
        $entity = $articles->patchEntity($entity, $data, $options);
        $articles->save($entity, $options);
        $entity = $articles->get(2, [
            'contain' => [
                'SpecialTags' => ['sort' => ['SpecialTags.id' => 'ASC']],
                'SpecialTags.Authors',
                'Highlights.Authors'
            ]
        ]);
        $this->assertEquals('mark', end($entity->highlights)->author->name);

        $lastTag = end($entity->special_tags);
        $this->assertTrue($lastTag->highlighted);
        $this->assertEquals('2014-06-01 10:10:00', $lastTag->highlighted_time->format('Y-m-d H:i:s'));
        $this->assertEquals('mariano', $lastTag->author->name);
    }

    /**
     * Tests that no exceptions are generated becuase of ambiguous column names in queries
     * during a  save operation
     *
     * @see https://github.com/cakephp/cakephp/issues/3803
     * @return void
     */
    public function testSaveWithCallbacks()
    {
        $articles = TableRegistry::get('Articles');
        $articles->belongsTo('Authors');

        $articles->eventManager()->attach(function ($event, $query) {
            return $query->contain('Authors');
        }, 'Model.beforeFind');

        $article = $articles->newEntity();
        $article->title = 'Foo';
        $article->body = 'Bar';
        $this->assertSame($article, $articles->save($article));
    }

    /**
     * Test that save() works with entities containing expressions
     * as properties.
     *
     * @return void
     */
    public function testSaveWithExpressionProperty()
    {
        $articles = TableRegistry::get('Articles');
        $article = $articles->newEntity();
        $article->title = new \Cake\Database\Expression\QueryExpression("SELECT 'jose'");
        $this->assertSame($article, $articles->save($article));
    }

    /**
     * Tests that whe saving deep associations for a belongsToMany property,
     * data is not removed becuase of excesive associations filtering.
     *
     * @see https://github.com/cakephp/cakephp/issues/4009
     * @return void
     */
    public function testBelongsToManyDeepSave2()
    {
        $articles = TableRegistry::get('Articles');
        $articles->belongsToMany('Highlights', [
            'className' => 'TestApp\Model\Table\TagsTable',
            'foreignKey' => 'article_id',
            'targetForeignKey' => 'tag_id',
            'through' => 'SpecialTags',
        ]);
        $articles->Highlights->hasMany('TopArticles', [
            'className' => 'TestApp\Model\Table\ArticlesTable',
            'foreignKey' => 'author_id',
        ]);
        $entity = $articles->get(2, ['contain' => ['Highlights']]);

        $data = [
            'highlights' => [
                [
                    'name' => 'New Special Tag',
                    '_joinData' => [
                        'highlighted' => true,
                        'highlighted_time' => '2014-06-01 10:10:00',
                    ],
                    'top_articles' => [
                        ['title' => 'First top article'],
                        ['title' => 'Second top article'],
                    ]
                ]
            ]
        ];
        $options = [
            'associated' => [
                'Highlights._joinData', 'Highlights.TopArticles'
            ]
        ];
        $entity = $articles->patchEntity($entity, $data, $options);
        $articles->save($entity, $options);
        $entity = $articles->get(2, [
            'contain' => [
                'Highlights.TopArticles'
            ]
        ]);
        $highlights = $entity->highlights[0];
        $this->assertEquals('First top article', $highlights->top_articles[0]->title);
        $this->assertEquals('Second top article', $highlights->top_articles[1]->title);
        $this->assertEquals(
            new Time('2014-06-01 10:10:00'),
            $highlights->_joinData->highlighted_time
        );
    }

    /**
     * An integration test that spot checks that associations use the
     * correct alias names to generate queries.
     *
     * @return void
     */
    public function testPluginAssociationQueryGeneration()
    {
        Plugin::load('TestPlugin');
        $articles = TableRegistry::get('Articles');
        $articles->hasMany('TestPlugin.Comments');
        $articles->belongsTo('TestPlugin.Authors');

        $result = $articles->find()
            ->where(['Articles.id' => 2])
            ->contain(['Comments', 'Authors'])
            ->first();

        $this->assertNotEmpty(
            $result->comments[0]->id,
            'No SQL error and comment exists.'
        );
        $this->assertNotEmpty(
            $result->author->id,
            'No SQL error and author exists.'
        );
    }

    /**
     * Tests that loading associations having the same alias in the
     * joinable associations chain is not sensitive to the order in which
     * the associations are selected.
     *
     * @see https://github.com/cakephp/cakephp/issues/4454
     * @return void
     */
    public function testAssociationChainOrder()
    {
        $articles = TableRegistry::get('Articles');
        $articles->belongsTo('Authors');
        $articles->hasOne('ArticlesTags');

        $articlesTags = TableRegistry::get('ArticlesTags');
        $articlesTags->belongsTo('Authors', [
            'foreignKey' => 'tag_id'
        ]);

        $resultA = $articles->find()
            ->contain(['ArticlesTags.Authors', 'Authors'])
            ->first();

        $resultB = $articles->find()
            ->contain(['Authors', 'ArticlesTags.Authors'])
            ->first();

        $this->assertEquals($resultA, $resultB);
        $this->assertNotEmpty($resultA->author);
        $this->assertNotEmpty($resultA->articles_tag->author);
    }

    /**
     * Test that offset/limit are elided from subquery loads.
     *
     * @return void
     */
    public function testAssociationSubQueryNoOffset()
    {
        $table = TableRegistry::get('Articles');
        $table->addBehavior('Translate', ['fields' => ['title', 'body']]);
        $table->locale('eng');
        $query = $table->find('translations')
            ->order(['Articles.id' => 'ASC'])
            ->limit(10)
            ->offset(1);
        $result = $query->toArray();
        $this->assertCount(2, $result);
    }

    /**
     * Tests that using the subquery strategy in a deep association returns the right results
     *
     * @see https://github.com/cakephp/cakephp/issues/4484
     * @return void
     */
    public function testDeepBelongsToManySubqueryStrategy()
    {
        $table = TableRegistry::get('Authors');
        $table->hasMany('Articles');
        $table->Articles->belongsToMany('Tags', [
            'strategy' => 'subquery'
        ]);

        $result = $table->find()->contain(['Articles.Tags'])->toArray();
        $this->assertEquals(
            ['tag1', 'tag3'],
            collection($result[2]->articles[0]->tags)->extract('name')->toArray()
        );
    }

    /**
     * Tests that using the subquery strategy in a deep association returns the right results
     *
     * @see https://github.com/cakephp/cakephp/issues/5769
     * @return void
     */
    public function testDeepBelongsToManySubqueryStrategy2()
    {
        $table = TableRegistry::get('Authors');
        $table->hasMany('Articles');
        $table->Articles->belongsToMany('Tags', [
            'strategy' => 'subquery'
        ]);
        $table->belongsToMany('Tags', [
            'strategy' => 'subquery',
        ]);
        $table->Articles->belongsTo('Authors');

        $result = $table->Articles->find()
            ->where(['Authors.id >' => 1])
            ->contain(['Authors.Tags'])
            ->toArray();
        $this->assertEquals(
            ['tag1', 'tag2'],
            collection($result[0]->author->tags)->extract('name')->toArray()
        );
        $this->assertEquals(3, $result[0]->author->id);
    }

    /**
     * Tests that getting the count of a query having containments return
     * the correct results
     *
     * @see https://github.com/cakephp/cakephp/issues/4511
     * @return void
     */
    public function testCountWithContain()
    {
        $table = TableRegistry::get('Articles');
        $table->belongsTo('Authors', ['joinType' => 'inner']);
        $count = $table
            ->find()
            ->contain(['Authors' => function ($q) {
                return $q->where(['Authors.id' => 1]);
            }])
            ->count();
        $this->assertEquals(2, $count);
    }

    /**
     * Test that deep containments don't generate empty entities for
     * intermediary relations.
     *
     * @return void
     */
    public function testContainNoEmptyAssociatedObjects()
    {
        $comments = TableRegistry::get('Comments');
        $comments->belongsTo('Users');
        $users = TableRegistry::get('Users');
        $users->hasMany('Articles', [
            'foreignKey' => 'author_id'
        ]);

        $comments->updateAll(['user_id' => 99], ['id' => 1]);

        $result = $comments->find()
            ->contain(['Users'])
            ->where(['Comments.id' => 1])
            ->first();
        $this->assertNull($result->user, 'No record should be null.');

        $result = $comments->find()
            ->contain(['Users', 'Users.Articles'])
            ->where(['Comments.id' => 1])
            ->first();
        $this->assertNull($result->user, 'No record should be null.');
    }

    /**
     * Tests that using a comparison expression inside an OR condition works
     *
     * @see https://github.com/cakephp/cakephp/issues/5081
     * @return void
     */
    public function testOrConditionsWithExpression()
    {
        $table = TableRegistry::get('Articles');
        $query = $table->find();
        $query->where([
            'OR' => [
                new \Cake\Database\Expression\Comparison('id', 1, 'integer', '>'),
                new \Cake\Database\Expression\Comparison('id', 3, 'integer', '<')
            ]
        ]);

        $results = $query->toArray();
        $this->assertCount(3, $results);
    }

    /**
     * Tests that calling count on a query having a union works correctly
     *
     * @see https://github.com/cakephp/cakephp/issues/5107
     * @return void
     */
    public function testCountWithUnionQuery()
    {
        $table = TableRegistry::get('Articles');
        $query = $table->find()->where(['id' => 1]);
        $query2 = $table->find()->where(['id' => 2]);
        $query->union($query2);
        $this->assertEquals(2, $query->count());
    }

    /**
     * Integration test when selecting no fields on the primary table.
     *
     * @return void
     */
    public function testSelectNoFieldsOnPrimaryAlias()
    {
        $table = TableRegistry::get('Articles');
        $table->belongsTo('Users');
        $query = $table->find()
            ->select(['Users__id' => 'id']);
        $results = $query->toArray();
        $this->assertCount(3, $results);
    }

    /**
     * Tests that calling first on the query results will not remove all other results
     * from the set.
     *
     * @return void
     */
    public function testFirstOnResultSet()
    {
        $results = TableRegistry::get('Articles')->find()->all();
        $this->assertEquals(3, $results->count());
        $this->assertNotNull($results->first());
        $this->assertCount(3, $results->toArray());
    }

    /**
     * Checks that matching and contain can be called for the same belongsTo association
     *
     * @see https://github.com/cakephp/cakephp/issues/5463
     * @return void
     */
    public function testFindMatchingAndContain()
    {
        $table = TableRegistry::get('Articles');
        $table->belongsTo('Authors');
        $article = $table->find()
            ->contain('Authors')
            ->matching('Authors', function ($q) {
                return $q->where(['Authors.id' => 1]);
            })
            ->first();
        $this->assertNotNull($article->author);
        $this->assertEquals($article->author, $article->_matchingData['Authors']);
    }

    /**
     * Checks that matching and contain can be called for the same belongsTo association
     *
     * @see https://github.com/cakephp/cakephp/issues/5463
     * @return void
     */
    public function testFindMatchingAndContainWithSubquery()
    {
        $table = TableRegistry::get('authors');
        $table->hasMany('articles', ['strategy' => 'subquery']);
        $table->articles->belongsToMany('tags');

        $result = $table->find()
            ->matching('articles.tags', function ($q) {
                return $q->where(['tags.id' => 2]);
            })
            ->contain('articles');

        $this->assertCount(2, $result->first()->articles);
    }

    /**
     * Tests that matching does not overwrite associations in contain
     *
     * @see https://github.com/cakephp/cakephp/issues/5584
     * @return void
     */
    public function testFindMatchingOverwrite()
    {
        $comments = TableRegistry::get('Comments');
        $comments->belongsTo('Articles');

        $articles = TableRegistry::get('Articles');
        $articles->belongsToMany('Tags');

        $result = $comments
            ->find()
            ->matching('Articles.Tags', function ($q) {
                return $q->where(['Tags.id' => 2]);
            })
            ->contain('Articles')
            ->first();

        $this->assertEquals(1, $result->id);
        $this->assertEquals(1, $result->_matchingData['Articles']->id);
        $this->assertEquals(2, $result->_matchingData['Tags']->id);
        $this->assertNotNull($result->article);
        $this->assertEquals($result->article, $result->_matchingData['Articles']);
    }

    /**
     * Tests that matching does not overwrite associations in contain
     *
     * @see https://github.com/cakephp/cakephp/issues/5584
     * @return void
     */
    public function testFindMatchingOverwrite2()
    {
        $comments = TableRegistry::get('Comments');
        $comments->belongsTo('Articles');

        $articles = TableRegistry::get('Articles');
        $articles->belongsTo('Authors');
        $articles->belongsToMany('Tags');

        $result = $comments
            ->find()
            ->matching('Articles.Tags', function ($q) {
                return $q->where(['Tags.id' => 2]);
            })
            ->contain('Articles.Authors')
            ->first();

        $this->assertNotNull($result->article->author);
    }

    /**
     * Tests that trying to contain an inexistent association
     * throws an exception and not a fatal error.
     *
     * @expectedException InvalidArgumentException
     * @return void
     */
    public function testQueryNotFatalError()
    {
        $comments = TableRegistry::get('Comments');
        $comments->find()->contain('Deprs')->all();
    }

    /**
     * Tests that using matching and contain on belongsTo associations
     * works correctly.
     *
     * @see https://github.com/cakephp/cakephp/issues/5721
     * @return void
     */
    public function testFindMatchingWithContain()
    {
        $comments = TableRegistry::get('Comments');
        $comments->belongsTo('Articles');
        $comments->belongsTo('Users');

        $result = $comments->find()
            ->contain(['Articles', 'Users'])
            ->matching('Articles', function ($q) {
                return $q->where(['Articles.id >=' => 1]);
            })
            ->matching('Users', function ($q) {
                return $q->where(['Users.id >=' => 1]);
            })
            ->order(['Comments.id' => 'ASC'])
            ->first();
        $this->assertInstanceOf('Cake\ORM\Entity', $result->article);
        $this->assertInstanceOf('Cake\ORM\Entity', $result->user);
        $this->assertEquals(2, $result->user->id);
        $this->assertEquals(1, $result->article->id);
    }

    /**
     * Tests that HasMany associations don't use duplicate PK values.
     *
     * @return void
     */
    public function testHasManyEagerLoadingUniqueKey()
    {
        $table = TableRegistry::get('ArticlesTags');
        $table->belongsTo('Articles', [
            'strategy' => 'select'
        ]);

        $result = $table->find()
            ->contain(['Articles' => function ($q) {
                $result = $q->sql();
                $this->assertNotContains(':c2', $result, 'Only 2 bindings as there are only 2 rows.');
                $this->assertNotContains(':c3', $result, 'Only 2 bindings as there are only 2 rows.');
                return $q;
            }])
            ->toArray();
        $this->assertNotEmpty($result[0]->article);
    }

    /**
     * Tests that using contain but selecting no fields from the association
     * does not trigger any errors and fetches the right results.
     *
     * @see https://github.com/cakephp/cakephp/issues/6214
     * @return void
     */
    public function testContainWithNoFields()
    {
        $table = TableRegistry::get('Comments');
        $table->belongsTo('Users');
        $results = $table->find()
            ->select(['Comments.id', 'Comments.user_id'])
            ->contain(['Users'])
            ->where(['Users.id' => 1])
            ->combine('id', 'user_id');

        $this->assertEquals([3 => 1, 4 => 1, 5 => 1], $results->toArray());
    }

    /**
     * Tests that using matching and selecting no fields for that association
     * will no trigger any errors and fetch the right results
     *
     * @see https://github.com/cakephp/cakephp/issues/6223
     * @return void
     */
    public function testMatchingWithNoFields()
    {
        $table = TableRegistry::get('Users');
        $table->hasMany('Comments');
        $results = $table->find()
            ->select(['Users.id'])
            ->matching('Comments', function ($q) {
                return $q->where(['Comments.id' => 1]);
            })
            ->extract('id')
            ->toList();
        $this->assertEquals([2], $results);
    }
}
