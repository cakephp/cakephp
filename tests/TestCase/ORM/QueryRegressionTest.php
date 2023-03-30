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

use Cake\Database\Driver\Sqlserver;
use Cake\Database\Expression\ComparisonExpression;
use Cake\Database\Expression\QueryExpression;
use Cake\Datasource\EntityInterface;
use Cake\Event\EventInterface;
use Cake\I18n\FrozenTime;
use Cake\ORM\Entity;
use Cake\ORM\Query;
use Cake\TestSuite\TestCase;
use DateTime as NativeDateTime;
use InvalidArgumentException;
use RuntimeException;
use function Cake\Collection\collection;

/**
 * Contains regression test for the Query builder
 */
class QueryRegressionTest extends TestCase
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
        'core.Authors',
        'core.AuthorsTags',
        'core.Comments',
        'core.FeaturedTags',
        'core.SpecialTags',
        'core.TagsTranslations',
        'core.Translates',
        'core.Users',
    ];

    /**
     * Test for https://github.com/cakephp/cakephp/issues/3087
     */
    public function testSelectTimestampColumn(): void
    {
        $table = $this->getTableLocator()->get('users');
        $user = $table->find()->where(['id' => 1])->first();
        $this->assertEquals(new FrozenTime('2007-03-17 01:16:23'), $user->created);
        $this->assertEquals(new FrozenTime('2007-03-17 01:18:31'), $user->updated);
    }

    /**
     * Tests that EagerLoader does not try to create queries for associations having no
     * keys to compare against
     */
    public function testEagerLoadingFromEmptyResults(): void
    {
        $table = $this->getTableLocator()->get('Articles');
        $table->belongsToMany('ArticlesTags');
        $results = $table->find()->where(['id >' => 100])->contain('ArticlesTags')->toArray();
        $this->assertEmpty($results);
    }

    /**
     * Tests that eagerloading associations with aliased fields works.
     */
    public function testEagerLoadingAliasedAssociationFields(): void
    {
        $table = $this->getTableLocator()->get('Articles');
        $table->belongsTo('Authors');
        $result = $table->find()
            ->contain(['Authors' => [
                'fields' => [
                    'id',
                    'Authors__aliased_name' => 'name',
                ],
            ]])
            ->where(['Articles.id' => 1])
            ->first();
        $this->assertInstanceOf(EntityInterface::class, $result);
        $this->assertInstanceOf(EntityInterface::class, $result->author);
        $this->assertSame('mariano', $result->author->aliased_name);
    }

    /**
     * Tests that eagerloading and hydration works for associations that have
     * different aliases in the association and targetTable
     */
    public function testEagerLoadingMismatchingAliasInBelongsTo(): void
    {
        $table = $this->getTableLocator()->get('Articles');
        $users = $this->getTableLocator()->get('Users');
        $table->belongsTo('Authors', [
            'targetTable' => $users,
            'foreignKey' => 'author_id',
        ]);
        $result = $table->find()->where(['Articles.id' => 1])->contain('Authors')->first();
        $this->assertInstanceOf(EntityInterface::class, $result);
        $this->assertInstanceOf(EntityInterface::class, $result->author);
        $this->assertSame('mariano', $result->author->username);
    }

    /**
     * Tests that eagerloading and hydration works for associations that have
     * different aliases in the association and targetTable
     */
    public function testEagerLoadingMismatchingAliasInHasOne(): void
    {
        $articles = $this->getTableLocator()->get('Articles');
        $users = $this->getTableLocator()->get('Users');
        $users->hasOne('Posts', [
            'targetTable' => $articles,
            'foreignKey' => 'author_id',
        ]);
        $result = $users->find()->where(['Users.id' => 1])->contain('Posts')->first();
        $this->assertInstanceOf(EntityInterface::class, $result);
        $this->assertInstanceOf(EntityInterface::class, $result->post);
        $this->assertSame('First Article', $result->post->title);
    }

    /**
     * Tests that eagerloading belongsToMany with find list fails with a helpful message.
     */
    public function testEagerLoadingBelongsToManyList(): void
    {
        $table = $this->getTableLocator()->get('Articles');
        $table->belongsToMany('Tags', [
            'finder' => 'list',
        ]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('"_joinData" is missing from the belongsToMany results');
        $table->find()->contain('Tags')->toArray();
    }

    /**
     * Tests that eagerloading and hydration works for associations that have
     * different aliases in the association and targetTable
     */
    public function testEagerLoadingNestedMatchingCalls(): void
    {
        $articles = $this->getTableLocator()->get('Articles');
        $articles->belongsToMany('Tags');
        $tags = $this->getTableLocator()->get('Tags');
        $tags->belongsToMany('Authors');

        $query = $articles->find()
            ->matching('Tags', function ($q) {
                return $q->matching('Authors', function ($q) {
                    return $q->where(['Authors.name' => 'larry']);
                });
            });
        $this->assertSame(3, $query->count());

        $result = $query->first();
        $this->assertInstanceOf(EntityInterface::class, $result);
        $this->assertInstanceOf(EntityInterface::class, $result->_matchingData['Tags']);
        $this->assertInstanceOf(EntityInterface::class, $result->_matchingData['Authors']);
    }

    /**
     * Tests that duplicate aliases in contain() can be used, even when they would
     * naturally be attached to the query instead of eagerly loaded. What should
     * happen here is that One of the duplicates will be changed to be loaded using
     * an extra query, but yielding the same results
     */
    public function testDuplicateAttachableAliases(): void
    {
        $this->getTableLocator()->get('Stuff', ['table' => 'tags']);
        $this->getTableLocator()->get('Things', ['table' => 'articles_tags']);

        $table = $this->getTableLocator()->get('Articles');
        $table->belongsTo('Authors');
        $table->hasOne('Things', ['propertyName' => 'articles_tag']);
        $table->Authors->getTarget()->hasOne('Stuff', [
            'foreignKey' => 'id',
            'propertyName' => 'favorite_tag',
        ]);
        $table->Things->getTarget()->belongsTo('Stuff', [
            'foreignKey' => 'tag_id',
            'propertyName' => 'foo',
        ]);

        $results = $table->find()
            ->contain(['Authors.Stuff', 'Things.Stuff'])
            ->order(['Articles.id' => 'ASC'])
            ->toArray();

        $this->assertCount(5, $results);
        $this->assertSame(1, $results[0]->articles_tag->foo->id);
        $this->assertSame(1, $results[0]->author->favorite_tag->id);
        $this->assertSame(2, $results[1]->articles_tag->foo->id);
        $this->assertSame(1, $results[2]->articles_tag->foo->id);
        $this->assertSame(3, $results[2]->author->favorite_tag->id);
        $this->assertSame(3, $results[3]->articles_tag->foo->id);
        $this->assertSame(3, $results[3]->author->favorite_tag->id);
    }

    /**
     * Test for https://github.com/cakephp/cakephp/issues/3410
     */
    public function testNullableTimeColumn(): void
    {
        $table = $this->getTableLocator()->get('users');
        $entity = $table->newEntity(['username' => 'derp', 'created' => null]);
        $this->assertSame($entity, $table->save($entity));
        $this->assertNull($entity->created);
    }

    /**
     * Test for https://github.com/cakephp/cakephp/issues/3626
     *
     * Checks that join data is actually created and not tried to be updated every time
     */
    public function testCreateJointData(): void
    {
        $articles = $this->getTableLocator()->get('Articles');
        $articles->belongsToMany('Highlights', [
            'className' => 'TestApp\Model\Table\TagsTable',
            'targetForeignKey' => 'tag_id',
            'through' => 'SpecialTags',
        ]);
        $entity = $articles->get(2);
        $data = [
            'id' => 2,
            'highlights' => [
                [
                    'name' => 'New Special Tag',
                    '_joinData' => ['highlighted' => true, 'highlighted_time' => '2014-06-01 10:10:00'],
                ],
            ],
        ];
        $entity = $articles->patchEntity($entity, $data, ['Highlights._joinData']);
        $articles->save($entity);
        $entity = $articles->get(2, ['contain' => ['Highlights']]);
        $this->assertSame(4, $entity->highlights[0]->_joinData->tag_id);
        $this->assertSame('2014-06-01', $entity->highlights[0]->_joinData->highlighted_time->format('Y-m-d'));
    }

    /**
     * Tests that the junction table instance taken from both sides of a belongsToMany
     * relationship is actually the same object.
     */
    public function testReciprocalBelongsToMany(): void
    {
        $articles = $this->getTableLocator()->get('Articles');
        $tags = $this->getTableLocator()->get('Tags');

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
     */
    public function testReciprocalBelongsToManyNoOverwrite(): void
    {
        $articles = $this->getTableLocator()->get('Articles');
        $tags = $this->getTableLocator()->get('Tags');

        $articles->belongsToMany('Tags');
        $tags->belongsToMany('Articles');

        $sub = $articles->Tags->find()->select(['Tags.id'])->matching('Articles', function ($q) {
            return $q->where(['Articles.id' => 1]);
        });

        $query = $articles->Tags->find()->where(['Tags.id NOT IN' => $sub]);
        $this->assertSame(1, $query->count());
    }

    /**
     * Returns an array with the saving strategies for a belongsTo association
     *
     * @return array
     */
    public function strategyProvider(): array
    {
        return [
            ['append'],
            ['replace'],
        ];
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
     */
    public function testBelongsToManyDeepSave($strategy): void
    {
        $articles = $this->getTableLocator()->get('Articles');
        $articles->belongsToMany('Highlights', [
            'className' => 'TestApp\Model\Table\TagsTable',
            'targetForeignKey' => 'tag_id',
            'through' => 'SpecialTags',
            'saveStrategy' => $strategy,
        ]);
        $articles->Highlights->junction()->belongsTo('Authors');
        $articles->Highlights->hasOne('Authors', [
            'foreignKey' => 'id',
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
                            'name' => 'mariano',
                        ],
                    ],
                    'author' => ['name' => 'mark'],
                ],
            ],
        ];
        $options = [
            'associated' => [
                'Highlights._joinData.Authors', 'Highlights.Authors',
            ],
        ];
        $entity = $articles->patchEntity($entity, $data, $options);
        $articles->save($entity, $options);
        $entity = $articles->get(2, [
            'contain' => [
                'SpecialTags' => ['sort' => ['SpecialTags.id' => 'ASC']],
                'SpecialTags.Authors',
                'Highlights.Authors',
            ],
        ]);
        $this->assertSame('mark', end($entity->highlights)->author->name);

        $lastTag = end($entity->special_tags);
        $this->assertTrue($lastTag->highlighted);
        $this->assertSame('2014-06-01 10:10:00', $lastTag->highlighted_time->format('Y-m-d H:i:s'));
        $this->assertSame('mariano', $lastTag->author->name);
    }

    /**
     * Tests that no exceptions are generated because of ambiguous column names in queries
     * during a  save operation
     *
     * @see https://github.com/cakephp/cakephp/issues/3803
     */
    public function testSaveWithCallbacks(): void
    {
        $articles = $this->getTableLocator()->get('Articles');
        $articles->belongsTo('Authors');

        $articles->getEventManager()->on('Model.beforeFind', function (EventInterface $event, $query) {
            return $query->contain('Authors');
        });

        $article = $articles->newEmptyEntity();
        $article->title = 'Foo';
        $article->body = 'Bar';
        $this->assertSame($article, $articles->save($article));
    }

    /**
     * Test that save() works with entities containing expressions
     * as properties.
     */
    public function testSaveWithExpressionProperty(): void
    {
        $articles = $this->getTableLocator()->get('Articles');
        $article = $articles->newEmptyEntity();
        $article->title = new QueryExpression("SELECT 'jose'");
        $this->assertSame($article, $articles->save($article));
    }

    /**
     * Tests that whe saving deep associations for a belongsToMany property,
     * data is not removed because of excessive associations filtering.
     *
     * @see https://github.com/cakephp/cakephp/issues/4009
     */
    public function testBelongsToManyDeepSave2(): void
    {
        $articles = $this->getTableLocator()->get('Articles');
        $articles->belongsToMany('Highlights', [
            'className' => 'TestApp\Model\Table\TagsTable',
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
                    ],
                ],
            ],
        ];
        $options = [
            'associated' => [
                'Highlights._joinData', 'Highlights.TopArticles',
            ],
        ];
        $entity = $articles->patchEntity($entity, $data, $options);
        $articles->save($entity, $options);
        $entity = $articles->get(2, [
            'contain' => [
                'Highlights.TopArticles',
            ],
        ]);
        $highlights = $entity->highlights[0];
        $this->assertSame('First top article', $highlights->top_articles[0]->title);
        $this->assertSame('Second top article', $highlights->top_articles[1]->title);
        $this->assertEquals(
            new FrozenTime('2014-06-01 10:10:00'),
            $highlights->_joinData->highlighted_time
        );
    }

    /**
     * An integration test that spot checks that associations use the
     * correct alias names to generate queries.
     */
    public function testPluginAssociationQueryGeneration(): void
    {
        $this->loadPlugins(['TestPlugin']);
        $articles = $this->getTableLocator()->get('Articles');
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
        $this->clearPlugins();
    }

    /**
     * Tests that loading associations having the same alias in the
     * joinable associations chain is not sensitive to the order in which
     * the associations are selected.
     *
     * @see https://github.com/cakephp/cakephp/issues/4454
     */
    public function testAssociationChainOrder(): void
    {
        $articles = $this->getTableLocator()->get('Articles');
        $articles->belongsTo('Authors');
        $articles->hasOne('ArticlesTags');

        $articlesTags = $this->getTableLocator()->get('ArticlesTags');
        $articlesTags->belongsTo('Authors', [
            'foreignKey' => 'tag_id',
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
     */
    public function testAssociationSubQueryNoOffset(): void
    {
        $table = $this->getTableLocator()->get('Articles');
        $table->addBehavior('Translate', ['fields' => ['title', 'body']]);
        $table->setLocale('eng');
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
     */
    public function testDeepBelongsToManySubqueryStrategy(): void
    {
        $table = $this->getTableLocator()->get('Authors');
        $table->hasMany('Articles');
        $table->Articles->belongsToMany('Tags', [
            'strategy' => 'subquery',
        ]);

        $result = $table->find()->contain(['Articles.Tags'])->toArray();

        $this->assertEquals(
            ['tag1', 'tag3'],
            collection($result[2]->articles[0]->tags)->sortBy('name')->extract('name')->toArray()
        );
    }

    /**
     * Tests that using the subquery strategy in a deep association returns the right results
     *
     * @see https://github.com/cakephp/cakephp/issues/5769
     */
    public function testDeepBelongsToManySubqueryStrategy2(): void
    {
        $table = $this->getTableLocator()->get('Authors');
        $table->hasMany('Articles');
        $table->Articles->belongsToMany('Tags', [
            'strategy' => 'subquery',
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
        $this->assertSame(3, $result[0]->author->id);
    }

    /**
     * Tests that finding on a table with a primary key other than `id` will work
     * seamlessly with either select or subquery.
     *
     * @see https://github.com/cakephp/cakephp/issues/6781
     */
    public function testDeepHasManyEitherStrategy(): void
    {
        $tags = $this->getTableLocator()->get('Tags');

        $this->skipIf(
            $tags->getConnection()->getDriver() instanceof Sqlserver,
            'SQL server is temporarily weird in this test, will investigate later'
        );
        $tags = $this->getTableLocator()->get('Tags');
        $featuredTags = $this->getTableLocator()->get('FeaturedTags');
        $featuredTags->belongsTo('Tags');

        $tags->hasMany('TagsTranslations', [
            'foreignKey' => 'id',
            'strategy' => 'select',
        ]);
        $findViaSelect = $featuredTags
            ->find()
            ->where(['FeaturedTags.tag_id' => 2])
            ->contain('Tags.TagsTranslations')
            ->all();

        $tags->hasMany('TagsTranslations', [
            'foreignKey' => 'id',
            'strategy' => 'subquery',
        ]);
        $findViaSubquery = $featuredTags
            ->find()
            ->where(['FeaturedTags.tag_id' => 2])
            ->contain('Tags.TagsTranslations')
            ->all();

        $expected = [2 => 'tag 2 translated into en_us'];

        $this->assertEquals($expected, $findViaSelect->combine('tag_id', 'tag.tags_translations.0.name')->toArray());
        $this->assertEquals($expected, $findViaSubquery->combine('tag_id', 'tag.tags_translations.0.name')->toArray());
    }

    /**
     * Tests that getting the count of a query having containments return
     * the correct results
     *
     * @see https://github.com/cakephp/cakephp/issues/4511
     */
    public function testCountWithContain(): void
    {
        $table = $this->getTableLocator()->get('Articles');
        $table->belongsTo('Authors', ['joinType' => 'inner']);
        $count = $table
            ->find()
            ->contain(['Authors' => function ($q) {
                return $q->where(['Authors.id' => 1]);
            }])
            ->count();
        $this->assertSame(2, $count);
    }

    /**
     * Tests that getting the count of a query with bind is correct
     *
     * @see https://github.com/cakephp/cakephp/issues/8466
     */
    public function testCountWithBind(): void
    {
        $table = $this->getTableLocator()->get('Articles');
        $query = $table
            ->find()
            ->select(['title', 'id'])
            ->where('title LIKE :val')
            ->group(['id', 'title'])
            ->bind(':val', '%Second%');
        $count = $query->count();
        $this->assertSame(1, $count);
    }

    /**
     * Test count() with inner join containments.
     */
    public function testCountWithInnerJoinContain(): void
    {
        $table = $this->getTableLocator()->get('Articles');
        $table->belongsTo('Authors')->setJoinType('INNER');

        $result = $table->save($table->newEntity([
            'author_id' => null,
            'title' => 'title',
            'body' => 'body',
            'published' => 'Y',
        ]));
        $this->assertNotFalse($result);

        $table->getEventManager()
            ->on('Model.beforeFind', function (EventInterface $event, $query): void {
                $query->contain(['Authors']);
            });

        $count = $table->find()->count();
        $this->assertSame(3, $count);
    }

    /**
     * Tests that bind in subqueries works.
     */
    public function testSubqueryBind(): void
    {
        $table = $this->getTableLocator()->get('Articles');
        $sub = $table->find()
            ->select(['id'])
            ->where('title LIKE :val')
            ->bind(':val', 'Second %');

        $query = $table
            ->find()
            ->select(['title'])
            ->where(['id NOT IN' => $sub]);
        $result = $query->toArray();
        $this->assertCount(2, $result);
        $this->assertSame('First Article', $result[0]->title);
        $this->assertSame('Third Article', $result[1]->title);
    }

    /**
     * Test that deep containments don't generate empty entities for
     * intermediary relations.
     */
    public function testContainNoEmptyAssociatedObjects(): void
    {
        $comments = $this->getTableLocator()->get('Comments');
        $comments->belongsTo('Users');
        $users = $this->getTableLocator()->get('Users');
        $users->hasMany('Articles', [
            'foreignKey' => 'author_id',
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
     */
    public function testOrConditionsWithExpression(): void
    {
        $table = $this->getTableLocator()->get('Articles');
        $query = $table->find();
        $query->where([
            'OR' => [
                new ComparisonExpression('id', 1, 'integer', '>'),
                new ComparisonExpression('id', 3, 'integer', '<'),
            ],
        ]);

        $results = $query->toArray();
        $this->assertCount(3, $results);
    }

    /**
     * Tests that calling count on a query having a union works correctly
     *
     * @see https://github.com/cakephp/cakephp/issues/5107
     */
    public function testCountWithUnionQuery(): void
    {
        $table = $this->getTableLocator()->get('Articles');
        $query = $table->find()->where(['id' => 1]);
        $query2 = $table->find()->where(['id' => 2]);
        $query->union($query2);
        $this->assertSame(2, $query->count());
    }

    /**
     * Integration test when selecting no fields on the primary table.
     */
    public function testSelectNoFieldsOnPrimaryAlias(): void
    {
        $table = $this->getTableLocator()->get('Articles');
        $table->belongsTo('Users');
        $query = $table->find()
            ->select(['Users__id' => 'id']);
        $results = $query->toArray();
        $this->assertCount(3, $results);
    }

    /**
     * Test selecting with aliased aggregates and identifier quoting
     * does not emit notice errors.
     *
     * @see https://github.com/cakephp/cakephp/issues/12766
     */
    public function testAliasedAggregateFieldTypeConversionSafe(): void
    {
        $articles = $this->getTableLocator()->get('Articles');

        $driver = $articles->getConnection()->getDriver();
        $restore = $driver->isAutoQuotingEnabled();

        $driver->enableAutoQuoting(true);
        $query = $articles->find();
        $query->select([
            'sumUsers' => $articles->find()->func()->sum('author_id'),
        ]);
        $driver->enableAutoQuoting($restore);

        $result = $query->execute()->fetchAll('assoc');
        $this->assertArrayHasKey('sumUsers', $result[0]);
    }

    /**
     * Tests that calling first on the query results will not remove all other results
     * from the set.
     */
    public function testFirstOnResultSet(): void
    {
        $results = $this->getTableLocator()->get('Articles')->find()->all();
        $this->assertSame(3, $results->count());
        $this->assertNotNull($results->first());
        $this->assertCount(3, $results->toArray());
    }

    /**
     * Checks that matching and contain can be called for the same belongsTo association
     *
     * @see https://github.com/cakephp/cakephp/issues/5463
     */
    public function testFindMatchingAndContain(): void
    {
        $table = $this->getTableLocator()->get('Articles');
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
     */
    public function testFindMatchingAndContainWithSubquery(): void
    {
        $table = $this->getTableLocator()->get('authors');
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
     */
    public function testFindMatchingOverwrite(): void
    {
        $comments = $this->getTableLocator()->get('Comments');
        $comments->belongsTo('Articles');

        $articles = $this->getTableLocator()->get('Articles');
        $articles->belongsToMany('Tags');

        $result = $comments
            ->find()
            ->matching('Articles.Tags', function ($q) {
                return $q->where(['Tags.id' => 2]);
            })
            ->contain('Articles')
            ->first();

        $this->assertSame(1, $result->id);
        $this->assertSame(1, $result->_matchingData['Articles']->id);
        $this->assertSame(2, $result->_matchingData['Tags']->id);
        $this->assertNotNull($result->article);
        $this->assertEquals($result->article, $result->_matchingData['Articles']);
    }

    /**
     * Tests that matching does not overwrite associations in contain
     *
     * @see https://github.com/cakephp/cakephp/issues/5584
     */
    public function testFindMatchingOverwrite2(): void
    {
        $comments = $this->getTableLocator()->get('Comments');
        $comments->belongsTo('Articles');

        $articles = $this->getTableLocator()->get('Articles');
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
     */
    public function testQueryNotFatalError(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $comments = $this->getTableLocator()->get('Comments');
        $comments->find()->contain('Deprs')->all();
    }

    /**
     * Tests that using matching and contain on belongsTo associations
     * works correctly.
     *
     * @see https://github.com/cakephp/cakephp/issues/5721
     */
    public function testFindMatchingWithContain(): void
    {
        $comments = $this->getTableLocator()->get('Comments');
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
        $this->assertSame(2, $result->user->id);
        $this->assertSame(1, $result->article->id);
    }

    /**
     * Tests that HasMany associations don't use duplicate PK values.
     */
    public function testHasManyEagerLoadingUniqueKey(): void
    {
        $table = $this->getTableLocator()->get('ArticlesTags');
        $table->belongsTo('Articles', [
            'strategy' => 'select',
        ]);

        $result = $table->find()
            ->contain(['Articles' => function ($q) {
                $result = $q->sql();
                $this->assertStringNotContainsString(':c2', $result, 'Only 2 bindings as there are only 2 rows.');
                $this->assertStringNotContainsString(':c3', $result, 'Only 2 bindings as there are only 2 rows.');

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
     */
    public function testContainWithNoFields(): void
    {
        $table = $this->getTableLocator()->get('Comments');
        $table->belongsTo('Users');
        $results = $table->find()
            ->select(['Comments.id', 'Comments.user_id'])
            ->contain(['Users'])
            ->where(['Users.id' => 1])
            ->all()
            ->combine('id', 'user_id');

        $this->assertEquals([3 => 1, 4 => 1, 5 => 1], $results->toArray());
    }

    /**
     * Tests that find() and contained associations using computed fields doesn't error out.
     *
     * @see https://github.com/cakephp/cakephp/issues/9326
     */
    public function testContainWithComputedField(): void
    {
        $table = $this->getTableLocator()->get('Users');
        $table->hasMany('Comments');

        $query = $table->find()->contain([
            'Comments' => function ($q) {
                return $q->select([
                    'concat' => $q->func()->concat(['red', 'blue']),
                    'user_id',
                ]);
            }])
            ->where(['Users.id' => 2]);

        $results = $query->toArray();
        $this->assertCount(1, $results);
        $this->assertSame('redblue', $results[0]->comments[0]->concat);
    }

    /**
     * Tests that using matching and selecting no fields for that association
     * will no trigger any errors and fetch the right results
     *
     * @see https://github.com/cakephp/cakephp/issues/6223
     */
    public function testMatchingWithNoFields(): void
    {
        $table = $this->getTableLocator()->get('Users');
        $table->hasMany('Comments');
        $results = $table->find()
            ->select(['Users.id'])
            ->matching('Comments', function ($q) {
                return $q->where(['Comments.id' => 1]);
            })
            ->all()
            ->extract('id')
            ->toList();
        $this->assertEquals([2], $results);
    }

    /**
     * Test that empty conditions in a matching clause don't cause errors.
     */
    public function testMatchingEmptyQuery(): void
    {
        $table = $this->getTableLocator()->get('Articles');
        $table->belongsToMany('Tags');

        $rows = $table->find()
            ->matching('Tags', function ($q) {
                return $q->where([]);
            })
            ->all();
        $this->assertNotEmpty($rows);

        $rows = $table->find()
            ->matching('Tags', function ($q) {
                return $q->where(null);
            })
            ->all();
        $this->assertNotEmpty($rows);
    }

    /**
     * Tests that using a subquery as part of an expression will not make invalid SQL
     */
    public function testSubqueryInSelectExpression(): void
    {
        $table = $this->getTableLocator()->get('Comments');
        $ratio = $table->find()
            ->select(function ($query) use ($table) {
                $allCommentsCount = $table->find()->select($query->func()->count('*'));
                $countToFloat = $query->newExpr([$query->func()->count('*'), '1.0'])->setConjunction('*');

                return [
                    'ratio' => $query
                        ->newExpr($countToFloat)
                        ->add($allCommentsCount)
                        ->setConjunction('/'),
                ];
            })
            ->where(['user_id' => 1])
            ->first()
            ->ratio;
        $this->assertSame(0.5, (float)$ratio);
    }

    /**
     * Tests calling contain in a nested closure
     *
     * @see https://github.com/cakephp/cakephp/issues/7591
     */
    public function testContainInNestedClosure(): void
    {
        $table = $this->getTableLocator()->get('Comments');
        $table->belongsTo('Articles');
        $table->Articles->belongsTo('Authors');
        $table->Articles->Authors->belongsToMany('Tags');

        $query = $table->find()->where(['Comments.id' => 5])->contain(['Articles' => function ($q) {
            return $q->contain(['Authors' => function ($q) {
                return $q->contain('Tags');
            }]);
        }]);
        $this->assertCount(2, $query->first()->article->author->tags);
    }

    /**
     * Test that the typemaps used in function expressions
     * create the correct results.
     */
    public function testTypemapInFunctions(): void
    {
        $table = $this->getTableLocator()->get('Comments');
        $table->updateAll(['published' => null], ['1 = 1']);
        $query = $table->find();
        $query->select([
            'id',
            'coalesced' => $query->func()->coalesce(
                ['published' => 'identifier', -1],
                ['integer']
            ),
        ]);
        $result = $query->all()->first();
        $this->assertSame(
            -1,
            $result['coalesced'],
            'Output values for functions should be casted'
        );
    }

    /**
     * Test that the typemaps used in function expressions
     * create the correct results.
     */
    public function testTypemapInFunctions2(): void
    {
        $table = $this->getTableLocator()->get('Comments');
        $query = $table->find();
        $query->select([
            'max' => $query->func()->max('created', ['datetime']),
        ]);
        $result = $query->all()->first();
        $this->assertEquals(new FrozenTime('2007-03-18 10:55:23'), $result['max']);
    }

    /**
     * Test that the type specified in function expressions takes priority over
     * default types set for columns.
     *
     * @see https://github.com/cakephp/cakephp/issues/13049
     * @return void
     */
    public function testTypemapInFunctions3(): void
    {
        $table = $this->getTableLocator()->get('Comments');
        $query = $table->find();

        $result = $query->select(['id' => $query->func()->min('id')])
            ->first();
        $this->assertSame(1.0, $result['id']);

        $query = $table->find();
        $result = $query->select(['id' => $query->func()->min('id', ['boolean'])])
            ->first();
        $this->assertTrue($result['id']);
    }

    /**
     * Test that contain queries map types correctly.
     */
    public function testBooleanConditionsInContain(): void
    {
        $table = $this->getTableLocator()->get('Articles');
        $table->belongsToMany('Tags', [
            'through' => 'SpecialTags',
        ]);
        $query = $table->find()
            ->contain(['Tags' => function ($q) {
                return $q->where(['SpecialTags.highlighted_time >' => new FrozenTime('2014-06-01 00:00:00')]);
            }])
            ->where(['Articles.id' => 2]);

        $result = $query->first();
        $this->assertSame(2, $result->id);
        $this->assertNotEmpty($result->tags, 'Missing tags');
        $this->assertNotEmpty($result->tags[0]->_joinData, 'Missing join data');
    }

    /**
     * Test that contain queries map types correctly.
     */
    public function testComplexTypesInJoinedWhere(): void
    {
        $table = $this->getTableLocator()->get('Users');
        $table->hasOne('Comments');
        $query = $table->find()
            ->contain('Comments')
            ->where([
                'Comments.updated >' => new NativeDateTime('2007-03-18 10:55:00'),
            ]);

        $result = $query->first();
        $this->assertNotEmpty($result);
        $this->assertInstanceOf(FrozenTime::class, $result->comment->updated);
    }

    /**
     * Test that nested contain queries map types correctly.
     */
    public function testComplexNestedTypesInJoinedWhere(): void
    {
        $table = $this->getTableLocator()->get('Users');
        $table->hasOne('Comments');
        $table->Comments->belongsTo('Articles');
        $table->Comments->Articles->belongsTo('Authors', [
            'className' => 'Users',
        ]);

        $query = $table->find()
            ->contain('Comments.Articles.Authors')
            ->where([
                'Authors.created >' => new NativeDateTime('2007-03-17 01:16:00'),
            ]);

        $result = $query->first();
        $this->assertNotEmpty($result);
        $this->assertInstanceOf(FrozenTime::class, $result->comment->article->author->updated);
    }

    /**
     * Test that matching queries map types correctly.
     */
    public function testComplexTypesInJoinedWhereWithMatching(): void
    {
        $table = $this->getTableLocator()->get('Users');
        $table->hasOne('Comments');
        $table->Comments->belongsTo('Articles');
        $table->Comments->Articles->belongsTo('Authors', [
            'className' => 'Users',
        ]);

        $query = $table->find()
            ->matching('Comments')
            ->where([
                'Comments.updated >' => new NativeDateTime('2007-03-18 10:55:00'),
            ]);

        $result = $query->first();
        $this->assertNotEmpty($result);
        $this->assertInstanceOf(FrozenTime::class, $result->_matchingData['Comments']->updated);

        $query = $table->find()
            ->matching('Comments.Articles.Authors')
            ->where([
                'Authors.created >' => new NativeDateTime('2007-03-17 01:16:00'),
            ]);

        $result = $query->first();
        $this->assertNotEmpty($result);
        $this->assertInstanceOf(FrozenTime::class, $result->_matchingData['Authors']->updated);
    }

    /**
     * Test that notMatching queries map types correctly.
     */
    public function testComplexTypesInJoinedWhereWithNotMatching(): void
    {
        $Tags = $this->getTableLocator()->get('Tags');
        $Tags->belongsToMany('Articles');

        $query = $Tags->find()
            ->notMatching('Articles', function ($q) {
                return $q ->where(['ArticlesTags.tag_id !=' => 3 ]);
            })
            ->where([
                'Tags.created <' => new NativeDateTime('2016-01-02 00:00:00'),
            ]);

        $result = $query->first();
        $this->assertNotEmpty($result);
        $this->assertSame(3, $result->id);
        $this->assertInstanceOf(FrozenTime::class, $result->created);
    }

    /**
     * Test that innerJoinWith queries map types correctly.
     */
    public function testComplexTypesInJoinedWhereWithInnerJoinWith(): void
    {
        $table = $this->getTableLocator()->get('Users');
        $table->hasOne('Comments');
        $table->Comments->belongsTo('Articles');
        $table->Comments->Articles->belongsTo('Authors', [
            'className' => 'Users',
        ]);

        $query = $table->find()
            ->innerJoinWith('Comments')
            ->where([
                'Comments.updated >' => new NativeDateTime('2007-03-18 10:55:00'),
            ]);

        $result = $query->first();
        $this->assertNotEmpty($result);
        $this->assertInstanceOf(FrozenTime::class, $result->updated);

        $query = $table->find()
            ->innerJoinWith('Comments.Articles.Authors')
            ->where([
                'Authors.created >' => new NativeDateTime('2007-03-17 01:16:00'),
            ]);

        $result = $query->first();
        $this->assertNotEmpty($result);
        $this->assertInstanceOf(FrozenTime::class, $result->updated);
    }

    /**
     * Test that leftJoinWith queries map types correctly.
     */
    public function testComplexTypesInJoinedWhereWithLeftJoinWith(): void
    {
        $table = $this->getTableLocator()->get('Users');
        $table->hasOne('Comments');
        $table->Comments->belongsTo('Articles');
        $table->Comments->Articles->belongsTo('Authors', [
            'className' => 'Users',
        ]);

        $query = $table->find()
            ->leftJoinWith('Comments')
            ->where([
                'Comments.updated >' => new NativeDateTime('2007-03-18 10:55:00'),
            ]);

        $result = $query->first();
        $this->assertNotEmpty($result);
        $this->assertInstanceOf(FrozenTime::class, $result->updated);

        $query = $table->find()
            ->leftJoinWith('Comments.Articles.Authors')
            ->where([
                'Authors.created >' => new NativeDateTime('2007-03-17 01:16:00'),
            ]);

        $result = $query->first();
        $this->assertNotEmpty($result);
        $this->assertInstanceOf(FrozenTime::class, $result->updated);
    }

    /**
     * Tests that it is possible to contain to fetch
     * associations off of a junction table.
     */
    public function testBelongsToManyJoinDataAssociation(): void
    {
        $articles = $this->getTableLocator()->get('Articles');

        $tags = $this->getTableLocator()->get('Tags');
        $tags->hasMany('SpecialTags');

        $specialTags = $this->getTableLocator()->get('SpecialTags');
        $specialTags->belongsTo('Authors');
        $specialTags->belongsTo('Articles');
        $specialTags->belongsTo('Tags');

        $articles->belongsToMany('Tags', [
            'through' => $specialTags,
        ]);
        $query = $articles->find()
            ->contain(['Tags', 'Tags.SpecialTags.Authors'])
            ->where(['Articles.id' => 1]);
        $result = $query->first();
        $this->assertNotEmpty($result->tags, 'Missing tags');
        $this->assertNotEmpty($result->tags[0], 'Missing first tag');
        $this->assertNotEmpty($result->tags[0]->_joinData, 'Missing _joinData');
        $this->assertNotEmpty($result->tags[0]->special_tags[0]->author, 'Missing author on _joinData');
    }

    /**
     * Tests that it is possible to use matching with dot notation
     * even when part of the part of the path in the dot notation is
     * shared for two different calls
     */
    public function testDotNotationNotOverride(): void
    {
        $table = $this->getTableLocator()->get('Comments');
        $articles = $table->belongsTo('Articles');
        $specialTags = $articles->hasMany('SpecialTags');
        $specialTags->belongsTo('Authors');
        $specialTags->belongsTo('Tags');

        $results = $table
            ->find()
            ->select(['name' => 'Authors.name', 'tag' => 'Tags.name'])
            ->matching('Articles.SpecialTags.Tags')
            ->matching('Articles.SpecialTags.Authors', function ($q) {
                return $q->where(['Authors.id' => 2]);
            })
            ->distinct()
            ->enableHydration(false)
            ->toArray();

        $this->assertEquals([['name' => 'nate', 'tag' => 'tag1']], $results);
    }

    /**
     * Test expression based ordering with unions.
     */
    public function testComplexOrderWithUnion(): void
    {
        $table = $this->getTableLocator()->get('Comments');
        $query = $table->find();
        $inner = $table->find()
            ->select(['content' => 'comment'])
            ->where(['id >' => 3]);
        $inner2 = $table->find()
            ->select(['content' => 'comment'])
            ->where(['id <' => 3]);

        $order = $query->func()->concat(['content' => 'literal', 'test']);

        $query->select(['inside.content'])
            ->from(['inside' => $inner->unionAll($inner2)])
            ->orderAsc($order);

        $results = $query->toArray();
        $this->assertCount(5, $results);
    }

    /**
     * Test that associations that are loaded with subqueries
     * do not cause errors when the subquery has a limit & order clause.
     */
    public function testEagerLoadOrderAndSubquery(): void
    {
        $table = $this->getTableLocator()->get('Articles');
        $table->hasMany('Comments', [
            'strategy' => 'subquery',
        ]);
        $query = $table->find()
            ->select(['score' => 100])
            ->enableAutoFields()
            ->contain(['Comments'])
            ->limit(5)
            ->order(['score' => 'desc']);
        $result = $query->all();
        $this->assertCount(3, $result);
    }

    /**
     * Tests that having bound placeholders in the order clause does not result
     * in an error when trying to count a query.
     */
    public function testCountWithComplexOrderBy(): void
    {
        $table = $this->getTableLocator()->get('Articles');
        $query = $table->find();
        $query->orderDesc(
            $query->newExpr()->case()->when(['id' => 3])->then(1)->else(0)
        );
        $query->order(['title' => 'desc']);
        // Executing the normal query before getting the count
        $query->all();
        $this->assertSame(3, $query->count());

        $table = $this->getTableLocator()->get('Articles');
        $query = $table->find();
        $query->orderDesc(
            $query->newExpr()->case()->when(['id' => 3])->then(1)->else(0)
        );
        $query->orderDesc($query->newExpr()->add(['id' => 3]));
        // Not executing the query first, just getting the count
        $this->assertSame(3, $query->count());
    }

    /**
     * Tests that the now() function expression can be used in the
     * where clause of a query
     *
     * @see https://github.com/cakephp/cakephp/issues/7943
     */
    public function testFunctionInWhereClause(): void
    {
        $table = $this->getTableLocator()->get('Comments');
        $table->updateAll(['updated' => FrozenTime::now()->addDays(2)], ['id' => 6]);
        $query = $table->find();
        $result = $query->where(['updated >' => $query->func()->now('datetime')])->first();
        $this->assertSame(6, $result->id);
    }

    /**
     * Tests that `notMatching()` can be used on `belongsToMany`
     * associations without passing a query builder callback.
     */
    public function testNotMatchingForBelongsToManyWithoutQueryBuilder(): void
    {
        $Articles = $this->getTableLocator()->get('Articles');
        $Articles->belongsToMany('Tags');

        $result = $Articles->find('list')->notMatching('Tags')->toArray();
        $expected = [
            3 => 'Third Article',
        ];

        $this->assertEquals($expected, $result);
    }

    /**
     * Tests deep formatters get the right object type when applied in a beforeFind
     *
     * @see https://github.com/cakephp/cakephp/issues/9787
     */
    public function testFormatDeepDistantAssociationRecords2(): void
    {
        $table = $this->getTableLocator()->get('authors');
        $table->hasMany('articles');
        $articles = $table->getAssociation('articles')->getTarget();
        $articles->hasMany('articlesTags');
        $tags = $articles->getAssociation('articlesTags')->getTarget()->belongsTo('tags');

        $tags->getTarget()->getEventManager()->on('Model.beforeFind', function ($e, $query) {
            return $query->formatResults(function ($results) {
                return $results->map(function (Entity $tag) {
                    $tag->name .= ' - visited';

                    return $tag;
                });
            });
        });

        $query = $table->find()->contain(['articles.articlesTags.tags']);

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
     * Tests that subqueries can be used with function expressions.
     */
    public function testFunctionExpressionWithSubquery(): void
    {
        $table = $this->getTableLocator()->get('Articles');

        $query = $table
            ->find()
            ->select(function (Query $q) use ($table) {
                return [
                    'value' => $q
                        ->func()
                        ->ABS([
                            $table
                                ->getConnection()
                                ->selectQuery(-1),
                        ])
                        ->setReturnType('integer'),
                ];
            });

        $result = $query->first()->get('value');
        $this->assertSame(1, $result);
    }

    /**
     * Tests that correlated subqueries can be used with function expressions.
     */
    public function testFunctionExpressionWithCorrelatedSubquery(): void
    {
        $table = $this->getTableLocator()->get('Articles');
        $table->belongsTo('Authors');

        $query = $table
            ->find()
            ->select(function (Query $q) use ($table) {
                return [
                    'value' => $q->func()->UPPER([
                        $table
                            ->getAssociation('Authors')
                            ->find()
                            ->select(['Authors.name'])
                            ->where(function (QueryExpression $exp) {
                                return $exp->equalFields('Authors.id', 'Articles.author_id');
                            }),
                    ]),
                ];
            });

        $result = $query->first()->get('value');
        $this->assertSame('MARIANO', $result);
    }

    /**
     * Tests that subqueries can be used with multi argument function expressions.
     */
    public function testMultiArgumentFunctionExpressionWithSubquery(): void
    {
        $table = $this->getTableLocator()->get('Articles');

        $query = $table
            ->find()
            ->select(function (Query $q) use ($table) {
                return [
                    'value' => $q
                        ->func()
                        ->ROUND(
                            [
                                $table
                                    ->getConnection()
                                    ->selectQuery(1.23456),
                                2,
                            ],
                            [null, 'integer']
                        )
                        ->setReturnType('float'),
                ];
            });

        $result = $query->first()->get('value');
        $this->assertSame(1.23, $result);
    }

    /**
     * Tests that correlated subqueries can be used with multi argument function expressions.
     */
    public function testMultiArgumentFunctionExpressionWithCorrelatedSubquery(): void
    {
        $table = $this->getTableLocator()->get('Articles');
        $table->belongsTo('Authors');

        $this->assertSame(
            1,
            $table->getAssociation('Authors')->updateAll(['name' => null], ['id' => 3])
        );

        $query = $table
            ->find()
            ->select(function (Query $q) use ($table) {
                return [
                    'value' => $q->func()->coalesce([
                        $table
                            ->getAssociation('Authors')
                            ->find()
                            ->select(['Authors.name'])
                            ->where(function (QueryExpression $exp) {
                                return $exp->equalFields('Authors.id', 'Articles.author_id');
                            }),
                        '1',
                    ]),
                ];
            });

        $results = $query->all()->extract('value')->toArray();
        $this->assertEquals(['mariano', '1', 'mariano'], $results);
    }

    /**
     * Tests that subqueries can be used with function expressions that are being transpiled.
     */
    public function testTranspiledFunctionExpressionWithSubquery(): void
    {
        $table = $this->getTableLocator()->get('Articles');
        $table->belongsTo('Authors');

        $query = $table
            ->find()
            ->select(function (Query $q) use ($table) {
                return [
                    'value' => $q->func()->concat([
                        $table
                            ->getAssociation('Authors')
                            ->find()
                            ->select(['Authors.name'])
                            ->where(['Authors.id' => 1]),
                        ' appended',
                    ]),
                ];
            });

        $result = $query->first()->get('value');
        $this->assertSame('mariano appended', $result);
    }

    /**
     * Tests that correlated subqueries can be used with function expressions that are being transpiled.
     */
    public function testTranspiledFunctionExpressionWithCorrelatedSubquery(): void
    {
        $table = $this->getTableLocator()->get('Articles');
        $table->belongsTo('Authors');

        $query = $table
            ->find()
            ->select(function (Query $q) use ($table) {
                return [
                    'value' => $q->func()->concat([
                        $table
                            ->getAssociation('Authors')
                            ->find()
                            ->select(['Authors.name'])
                            ->where(function (QueryExpression $exp) {
                                return $exp->equalFields('Authors.id', 'Articles.author_id');
                            }),
                        ' appended',
                    ]),
                ];
            });

        $result = $query->first()->get('value');
        $this->assertSame('mariano appended', $result);
    }
}
