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

use Cake\Database\Expression\QueryExpression;
use Cake\Datasource\ConnectionManager;
use Cake\ORM\Association\BelongsToMany;
use Cake\ORM\Entity;
use Cake\ORM\TableRegistry;
use Cake\TestSuite\TestCase;

/**
 * Tests BelongsToMany class
 */
class BelongsToManyTest extends TestCase
{
    /**
     * Fixtures
     *
     * @var array
     */
    public $fixtures = ['core.articles', 'core.special_tags', 'core.articles_tags', 'core.tags'];

    /**
     * Set up
     *
     * @return void
     */
    public function setUp()
    {
        parent::setUp();
        $this->tag = $this->getMockBuilder('Cake\ORM\Table')
            ->setMethods(['find', 'delete'])
            ->setConstructorArgs([['alias' => 'Tags', 'table' => 'tags']])
            ->getMock();
        $this->tag->schema([
            'id' => ['type' => 'integer'],
            'name' => ['type' => 'string'],
            '_constraints' => [
                'primary' => ['type' => 'primary', 'columns' => ['id']]
            ]
        ]);
        $this->article = $this->getMockBuilder('Cake\ORM\Table')
            ->setMethods(['find', 'delete'])
            ->setConstructorArgs([['alias' => 'Articles', 'table' => 'articles']])
            ->getMock();
        $this->article->schema([
            'id' => ['type' => 'integer'],
            'name' => ['type' => 'string'],
            '_constraints' => [
                'primary' => ['type' => 'primary', 'columns' => ['id']]
            ]
        ]);
    }

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
     * Tests that the association reports it can be joined
     *
     * @return void
     */
    public function testCanBeJoined()
    {
        $assoc = new BelongsToMany('Test');
        $this->assertFalse($assoc->canBeJoined());
    }

    /**
     * Tests sort() method
     *
     * @return void
     */
    public function testSort()
    {
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
    public function testRequiresKeys()
    {
        $assoc = new BelongsToMany('Test');
        $this->assertTrue($assoc->requiresKeys());
        $assoc->strategy(BelongsToMany::STRATEGY_SUBQUERY);
        $this->assertFalse($assoc->requiresKeys());
        $assoc->strategy(BelongsToMany::STRATEGY_SELECT);
        $this->assertTrue($assoc->requiresKeys());
    }

    /**
     * Tests that BelongsToMany can't use the join strategy
     *
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Invalid strategy "join" was provided
     * @return void
     */
    public function testStrategyFailure()
    {
        $assoc = new BelongsToMany('Test');
        $assoc->strategy(BelongsToMany::STRATEGY_JOIN);
    }

    /**
     * Tests the junction method
     *
     * @return void
     */
    public function testJunction()
    {
        $assoc = new BelongsToMany('Test', [
            'sourceTable' => $this->article,
            'targetTable' => $this->tag,
            'strategy' => 'subquery'
        ]);
        $junction = $assoc->junction();
        $this->assertInstanceOf('Cake\ORM\Table', $junction);
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

        $this->assertSame($assoc->strategy(), $this->tag->association('Articles')->strategy());
        $this->assertSame($assoc->strategy(), $this->tag->association('ArticlesTags')->strategy());
        $this->assertSame($assoc->strategy(), $this->article->association('ArticlesTags')->strategy());
    }

    /**
     * Tests the junction passes the source connection name on.
     *
     * @return void
     */
    public function testJunctionConnection()
    {
        $mock = $this->getMockBuilder('Cake\Database\Connection')
                ->setMethods(['driver'])
                ->setConstructorArgs(['name' => 'other_source'])
                ->getMock();
        ConnectionManager::config('other_source', $mock);
        $this->article->connection(ConnectionManager::get('other_source'));

        $assoc = new BelongsToMany('Test', [
            'sourceTable' => $this->article,
            'targetTable' => $this->tag
        ]);
        $junction = $assoc->junction();
        $this->assertSame($mock, $junction->connection());
        ConnectionManager::drop('other_source');
    }

    /**
     * Tests the junction method custom keys
     *
     * @return void
     */
    public function testJunctionCustomKeys()
    {
        $this->article->belongsToMany('Tags', [
            'joinTable' => 'articles_tags',
            'foreignKey' => 'article',
            'targetForeignKey' => 'tag'
        ]);
        $this->tag->belongsToMany('Articles', [
            'joinTable' => 'articles_tags',
            'foreignKey' => 'tag',
            'targetForeignKey' => 'article'
        ]);
        $junction = $this->article->association('Tags')->junction();
        $this->assertEquals('article', $junction->association('Articles')->foreignKey());
        $this->assertEquals('article', $this->article->association('ArticlesTags')->foreignKey());

        $junction = $this->tag->association('Articles')->junction();
        $this->assertEquals('tag', $junction->association('Tags')->foreignKey());
        $this->assertEquals('tag', $this->tag->association('ArticlesTags')->foreignKey());
    }

    /**
     * Tests it is possible to set the table name for the join table
     *
     * @return void
     */
    public function testJunctionWithDefaultTableName()
    {
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
    public function testSaveStrategy()
    {
        $assoc = new BelongsToMany('Test');
        $this->assertEquals(BelongsToMany::SAVE_REPLACE, $assoc->saveStrategy());
        $assoc->saveStrategy(BelongsToMany::SAVE_APPEND);
        $this->assertEquals(BelongsToMany::SAVE_APPEND, $assoc->saveStrategy());
        $assoc->saveStrategy(BelongsToMany::SAVE_REPLACE);
        $this->assertEquals(BelongsToMany::SAVE_REPLACE, $assoc->saveStrategy());
    }

    /**
     * Tests that it is possible to pass the saveAssociated strategy in the constructor
     *
     * @return void
     */
    public function testSaveStrategyInOptions()
    {
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
    public function testSaveStrategyInvalid()
    {
        $assoc = new BelongsToMany('Test', ['saveStrategy' => 'depsert']);
    }

    /**
     * Test cascading deletes.
     *
     * @return void
     */
    public function testCascadeDelete()
    {
        $articleTag = $this->getMockBuilder('Cake\ORM\Table')
            ->setMethods(['deleteAll'])
            ->getMock();
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
     * Test cascading deletes with dependent=false
     *
     * @return void
     */
    public function testCascadeDeleteDependent()
    {
        $articleTag = $this->getMockBuilder('Cake\ORM\Table')
            ->setMethods(['delete', 'deleteAll'])
            ->getMock();
        $config = [
            'sourceTable' => $this->article,
            'targetTable' => $this->tag,
            'dependent' => false,
            'sort' => ['id' => 'ASC'],
        ];
        $association = new BelongsToMany('Tags', $config);
        $association->junction($articleTag);
        $this->article
            ->association($articleTag->alias())
            ->conditions(['click_count' => 3]);

        $articleTag->expects($this->never())
            ->method('deleteAll');
        $articleTag->expects($this->never())
            ->method('delete');

        $entity = new Entity(['id' => 1, 'name' => 'PHP']);
        $association->cascadeDelete($entity);
    }

    /**
     * Test cascading deletes with callbacks.
     *
     * @return void
     */
    public function testCascadeDeleteWithCallbacks()
    {
        $articleTag = TableRegistry::get('ArticlesTags');
        $config = [
            'sourceTable' => $this->article,
            'targetTable' => $this->tag,
            'cascadeCallbacks' => true,
        ];
        $association = new BelongsToMany('Tag', $config);
        $association->junction($articleTag);
        $this->article->association($articleTag->alias());

        $counter = $this->getMockBuilder('StdClass')
            ->setMethods(['__invoke'])
            ->getMock();
        $counter->expects($this->exactly(2))->method('__invoke');
        $articleTag->eventManager()->on('Model.beforeDelete', $counter);

        $this->assertEquals(2, $articleTag->find()->where(['article_id' => 1])->count());
        $entity = new Entity(['id' => 1, 'name' => 'PHP']);
        $association->cascadeDelete($entity);

        $this->assertEquals(0, $articleTag->find()->where(['article_id' => 1])->count());
    }

    /**
     * Test linking entities having a non persisted source entity
     *
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Source entity needs to be persisted before proceeding
     * @return void
     */
    public function testLinkWithNotPersistedSource()
    {
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
    public function testLinkWithNotPersistedTarget()
    {
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
    public function testLinkSuccess()
    {
        $connection = ConnectionManager::get('test');
        $joint = $this->getMockBuilder('\Cake\ORM\Table')
            ->setMethods(['save'])
            ->setConstructorArgs([['alias' => 'ArticlesTags', 'connection' => $connection]])
            ->getMock();

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
            ->will($this->returnCallback(function ($e, $opts) use ($entity) {
                $expected = ['article_id' => 1, 'tag_id' => 2];
                $this->assertEquals($expected, $e->toArray());
                $this->assertEquals(['foo' => 'bar'], $opts);
                $this->assertTrue($e->isNew());

                return $entity;
            }));

        $joint->expects($this->at(1))
            ->method('save')
            ->will($this->returnCallback(function ($e, $opts) use ($entity) {
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
    public function testUnlinkWithNotPersistedSource()
    {
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
    public function testUnlinkWithNotPersistedTarget()
    {
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
    public function testUnlinkSuccess()
    {
        $joint = TableRegistry::get('SpecialTags');
        $articles = TableRegistry::get('Articles');
        $tags = TableRegistry::get('Tags');

        $assoc = $articles->belongsToMany('Tags', [
            'sourceTable' => $articles,
            'targetTable' => $tags,
            'through' => $joint,
            'joinTable' => 'special_tags',
        ]);
        $entity = $articles->get(2, ['contain' => 'Tags']);
        $initial = $entity->tags;
        $this->assertCount(1, $initial);

        $this->assertTrue($assoc->unlink($entity, $entity->tags));
        $this->assertEmpty($entity->get('tags'), 'Property should be empty');

        $new = $articles->get(2, ['contain' => 'Tags']);
        $this->assertCount(0, $new->tags, 'DB should be clean');
        $this->assertSame(3, $tags->find()->count(), 'Tags should still exist');
    }

    /**
     * Tests that unlinking with last parameter set to false
     * will not remove entities from the association property
     *
     * @return void
     */
    public function testUnlinkWithoutPropertyClean()
    {
        $joint = TableRegistry::get('SpecialTags');
        $articles = TableRegistry::get('Articles');
        $tags = TableRegistry::get('Tags');

        $assoc = $articles->belongsToMany('Tags', [
            'sourceTable' => $articles,
            'targetTable' => $tags,
            'through' => $joint,
            'joinTable' => 'special_tags',
            'conditions' => ['SpecialTags.highlighted' => true]
        ]);
        $entity = $articles->get(2, ['contain' => 'Tags']);
        $initial = $entity->tags;
        $this->assertCount(1, $initial);

        $this->assertTrue($assoc->unlink($entity, $initial, ['cleanProperty' => false]));
        $this->assertNotEmpty($entity->get('tags'), 'Property should not be empty');
        $this->assertEquals($initial, $entity->get('tags'), 'Property should be untouched');

        $new = $articles->get(2, ['contain' => 'Tags']);
        $this->assertCount(0, $new->tags, 'DB should be clean');
    }

    /**
     * Tests that replaceLink requires the sourceEntity to have primaryKey values
     * for the source entity
     *
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Could not find primary key value for source entity
     * @return void
     */
    public function testReplaceWithMissingPrimaryKey()
    {
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
     * Test that replaceLinks() can saveAssociated an empty set, removing all rows.
     *
     * @return void
     */
    public function testReplaceLinksUpdateToEmptySet()
    {
        $joint = TableRegistry::get('ArticlesTags');
        $articles = TableRegistry::get('Articles');
        $tags = TableRegistry::get('Tags');

        $assoc = $articles->belongsToMany('Tags', [
            'sourceTable' => $articles,
            'targetTable' => $tags,
            'through' => $joint,
            'joinTable' => 'articles_tags',
        ]);

        $entity = $articles->get(1, ['contain' => 'Tags']);
        $this->assertCount(2, $entity->tags);

        $assoc->replaceLinks($entity, []);
        $this->assertSame([], $entity->tags, 'Property should be empty');
        $this->assertFalse($entity->dirty('tags'), 'Property should be cleaned');

        $new = $articles->get(1, ['contain' => 'Tags']);
        $this->assertSame([], $entity->tags, 'Should not be data in db');
    }

    /**
     * Tests that replaceLinks will delete entities not present in the passed,
     * array, maintain those are already persisted and were passed and also
     * insert the rest.
     *
     * @return void
     */
    public function testReplaceLinkSuccess()
    {
        $joint = TableRegistry::get('ArticlesTags');
        $articles = TableRegistry::get('Articles');
        $tags = TableRegistry::get('Tags');

        $assoc = $articles->belongsToMany('Tags', [
            'sourceTable' => $articles,
            'targetTable' => $tags,
            'through' => $joint,
            'joinTable' => 'articles_tags',
        ]);
        $entity = $articles->get(1, ['contain' => 'Tags']);

        // 1=existing, 2=removed, 3=new link, & new tag
        $tagData = [
            new Entity(['id' => 1], ['markNew' => false]),
            new Entity(['id' => 3]),
            new Entity(['name' => 'net new']),
        ];

        $result = $assoc->replaceLinks($entity, $tagData, ['associated' => false]);
        $this->assertTrue($result);
        $this->assertSame($tagData, $entity->tags, 'Tags should match replaced objects');
        $this->assertFalse($entity->dirty('tags'), 'Should be clean');

        $fresh = $articles->get(1, ['contain' => 'Tags']);
        $this->assertCount(3, $fresh->tags, 'Records should be in db');

        $this->assertNotEmpty($tags->get(2), 'Unlinked tag should still exist');
    }

    /**
     * Tests that replaceLinks() will contain() the target table when
     * there are conditions present on the association.
     *
     * In this case the replacement will fail because the association conditions
     * hide the fixture data.
     *
     * @return void
     */
    public function testReplaceLinkWithConditions()
    {
        $joint = TableRegistry::get('SpecialTags');
        $articles = TableRegistry::get('Articles');
        $tags = TableRegistry::get('Tags');

        $assoc = $articles->belongsToMany('Tags', [
            'sourceTable' => $articles,
            'targetTable' => $tags,
            'through' => $joint,
            'joinTable' => 'special_tags',
            'conditions' => ['SpecialTags.highlighted' => true]
        ]);
        $entity = $articles->get(1, ['contain' => 'Tags']);

        $result = $assoc->replaceLinks($entity, [], ['associated' => false]);
        $this->assertTrue($result);
        $this->assertSame([], $entity->tags, 'Tags should match replaced objects');
        $this->assertFalse($entity->dirty('tags'), 'Should be clean');

        $fresh = $articles->get(1, ['contain' => 'Tags']);
        $this->assertCount(0, $fresh->tags, 'Association should be empty');

        $jointCount = $joint->find()->where(['article_id' => 1])->count();
        $this->assertSame(1, $jointCount, 'Non matching joint record should remain.');
    }

    /**
     * Tests replaceLinks with failing domain rules and new link targets.
     *
     * @return void
     */
    public function testReplaceLinkFailingDomainRules()
    {
        $articles = TableRegistry::get('Articles');
        $tags = TableRegistry::get('Tags');
        $tags->eventManager()->on('Model.buildRules', function ($event, $rules) {
            $rules->add(function () {
                return false;
            }, 'rule', ['errorField' => 'name', 'message' => 'Bad data']);
        });

        $assoc = $articles->belongsToMany('Tags', [
            'sourceTable' => $articles,
            'targetTable' => $tags,
            'through' => TableRegistry::get('ArticlesTags'),
            'joinTable' => 'articles_tags',
        ]);
        $entity = $articles->get(1, ['contain' => 'Tags']);
        $originalCount = count($entity->tags);

        $tags = [
            new Entity(['name' => 'tag99', 'description' => 'Best tag'])
        ];
        $result = $assoc->replaceLinks($entity, $tags);
        $this->assertFalse($result, 'replace should have failed.');
        $this->assertNotEmpty($tags[0]->errors(), 'Bad entity should have errors.');

        $entity = $articles->get(1, ['contain' => 'Tags']);
        $this->assertCount($originalCount, $entity->tags, 'Should not have changed.');
        $this->assertEquals('tag1', $entity->tags[0]->name);
    }

    /**
     * Provider for empty values
     *
     * @return array
     */
    public function emptyProvider()
    {
        return [
            [''],
            [false],
            [null],
            [[]]
        ];
    }

    /**
     * Test that saveAssociated() fails on non-empty, non-iterable value
     *
     * @expectedException InvalidArgumentException
     * @expectedExceptionMessage Could not save tags, it cannot be traversed
     * @return void
     */
    public function testSaveAssociatedNotEmptyNotIterable()
    {
        $articles = TableRegistry::get('Articles');
        $assoc = $articles->belongsToMany('Tags', [
            'saveStrategy' => BelongsToMany::SAVE_APPEND,
            'joinTable' => 'articles_tags',
        ]);
        $entity = new Entity([
            'id' => 1,
            'tags' => 'oh noes',
        ], ['markNew' => true]);

        $assoc->saveAssociated($entity);
    }

    /**
     * Test that saving an empty set on create works.
     *
     * @dataProvider emptyProvider
     * @return void
     */
    public function testSaveAssociatedEmptySetSuccess($value)
    {
        $table = $this->getMockBuilder('Cake\ORM\Table')
            ->setMethods(['table'])
            ->getMock();
        $table->schema([]);
        $assoc = $this->getMockBuilder('\Cake\ORM\Association\BelongsToMany')
            ->setMethods(['_saveTarget', 'replaceLinks'])
            ->setConstructorArgs(['tags', ['sourceTable' => $table]])
            ->getMock();
        $entity = new Entity([
            'id' => 1,
            'tags' => $value,
        ], ['markNew' => true]);

        $assoc->saveStrategy(BelongsToMany::SAVE_REPLACE);
        $assoc->expects($this->never())
            ->method('replaceLinks');
        $assoc->expects($this->never())
            ->method('_saveTarget');
        $this->assertSame($entity, $assoc->saveAssociated($entity));
    }

    /**
     * Test that saving an empty set on update works.
     *
     * @dataProvider emptyProvider
     * @return void
     */
    public function testSaveAssociatedEmptySetUpdateSuccess($value)
    {
        $table = $this->getMockBuilder('Cake\ORM\Table')
            ->setMethods(['table'])
            ->getMock();
        $table->schema([]);
        $assoc = $this->getMockBuilder('\Cake\ORM\Association\BelongsToMany')
            ->setMethods(['_saveTarget', 'replaceLinks'])
            ->setConstructorArgs(['tags', ['sourceTable' => $table]])
            ->getMock();
        $entity = new Entity([
            'id' => 1,
            'tags' => $value,
        ], ['markNew' => false]);

        $assoc->saveStrategy(BelongsToMany::SAVE_REPLACE);
        $assoc->expects($this->once())
            ->method('replaceLinks')
            ->with($entity, [])
            ->will($this->returnValue(true));

        $assoc->expects($this->never())
            ->method('_saveTarget');

        $this->assertSame($entity, $assoc->saveAssociated($entity));
    }

    /**
     * Tests saving with replace strategy returning true
     *
     * @return void
     */
    public function testSaveAssociatedWithReplace()
    {
        $table = $this->getMockBuilder('Cake\ORM\Table')
            ->setMethods(['table'])
            ->getMock();
        $table->schema([]);
        $assoc = $this->getMockBuilder('\Cake\ORM\Association\BelongsToMany')
            ->setMethods(['replaceLinks'])
            ->setConstructorArgs(['tags', ['sourceTable' => $table]])
            ->getMock();
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
        $this->assertSame($entity, $assoc->saveAssociated($entity, $options));
    }

    /**
     * Tests saving with replace strategy returning true
     *
     * @return void
     */
    public function testSaveAssociatedWithReplaceReturnFalse()
    {
        $table = $this->getMockBuilder('Cake\ORM\Table')
            ->setMethods(['table'])
            ->getMock();
        $table->schema([]);
        $assoc = $this->getMockBuilder('\Cake\ORM\Association\BelongsToMany')
            ->setMethods(['replaceLinks'])
            ->setConstructorArgs(['tags', ['sourceTable' => $table]])
            ->getMock();
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
        $this->assertFalse($assoc->saveAssociated($entity, $options));
    }

    /**
     * Test that saveAssociated() ignores non entity values.
     *
     * @return void
     */
    public function testSaveAssociatedOnlyEntitiesAppend()
    {
        $connection = ConnectionManager::get('test');
        $mock = $this->getMockBuilder('Cake\ORM\Table')
            ->setMethods(['saveAssociated', 'schema'])
            ->setConstructorArgs([['table' => 'tags', 'connection' => $connection]])
            ->getMock();
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
            ->method('saveAssociated');

        $association = new BelongsToMany('Tags', $config);
        $association->saveAssociated($entity);
    }

    /**
     * Tests that targetForeignKey() returns the correct configured value
     *
     * @return void
     */
    public function testTargetForeignKey()
    {
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
    public function testJunctionWithCustomForeignKeys()
    {
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
    public function testPropertyOption()
    {
        $config = ['propertyName' => 'thing_placeholder'];
        $association = new BelongsToMany('Thing', $config);
        $this->assertEquals('thing_placeholder', $association->property());
    }

    /**
     * Test that plugin names are omitted from property()
     *
     * @return void
     */
    public function testPropertyNoPlugin()
    {
        $mock = $this->getMockBuilder('Cake\ORM\Table')
            ->disableOriginalConstructor()
            ->getMock();
        $config = [
            'sourceTable' => $this->article,
            'targetTable' => $mock,
        ];
        $association = new BelongsToMany('Contacts.Tags', $config);
        $this->assertEquals('tags', $association->property());
    }

    /**
     * Test that the generated associations are correct.
     *
     * @return void
     */
    public function testGeneratedAssociations()
    {
        $articles = TableRegistry::get('Articles');
        $tags = TableRegistry::get('Tags');
        $conditions = ['SpecialTags.highlighted' => true];
        $assoc = $articles->belongsToMany('Tags', [
            'sourceTable' => $articles,
            'targetTable' => $tags,
            'foreignKey' => 'foreign_key',
            'targetForeignKey' => 'target_foreign_key',
            'through' => 'SpecialTags',
            'conditions' => $conditions,
        ]);
        // Generate associations
        $assoc->junction();

        $tagAssoc = $articles->association('Tags');
        $this->assertNotEmpty($tagAssoc, 'btm should exist');
        $this->assertEquals($conditions, $tagAssoc->conditions());
        $this->assertEquals('target_foreign_key', $tagAssoc->targetForeignKey());
        $this->assertEquals('foreign_key', $tagAssoc->foreignKey());

        $jointAssoc = $articles->association('SpecialTags');
        $this->assertNotEmpty($jointAssoc, 'has many to junction should exist');
        $this->assertInstanceOf('Cake\ORM\Association\HasMany', $jointAssoc);
        $this->assertEquals('foreign_key', $jointAssoc->foreignKey());

        $articleAssoc = $tags->association('Articles');
        $this->assertNotEmpty($articleAssoc, 'reverse btm should exist');
        $this->assertInstanceOf('Cake\ORM\Association\BelongsToMany', $articleAssoc);
        $this->assertEquals($conditions, $articleAssoc->conditions());
        $this->assertEquals('foreign_key', $articleAssoc->targetForeignKey(), 'keys should swap');
        $this->assertEquals('target_foreign_key', $articleAssoc->foreignKey(), 'keys should swap');

        $jointAssoc = $tags->association('SpecialTags');
        $this->assertNotEmpty($jointAssoc, 'has many to junction should exist');
        $this->assertInstanceOf('Cake\ORM\Association\HasMany', $jointAssoc);
        $this->assertEquals('target_foreign_key', $jointAssoc->foreignKey());
    }

    /**
     * Tests that fetching belongsToMany association will not force
     * all fields being returned, but intead will honor the select() clause
     *
     * @see https://github.com/cakephp/cakephp/issues/7916
     * @return void
     */
    public function testEagerLoadingBelongsToManyLimitedFields()
    {
        $table = TableRegistry::get('Articles');
        $table->belongsToMany('Tags');
        $result = $table
            ->find()
            ->contain(['Tags' => function ($q) {
                return $q->select(['id']);
            }])
            ->first();

        $this->assertNotEmpty($result->tags[0]->id);
        $this->assertEmpty($result->tags[0]->name);
    }

    /**
     * Tests that fetching belongsToMany association will retain autoFields(true) if it was used.
     *
     * @see https://github.com/cakephp/cakephp/issues/8052
     * @return void
     */
    public function testEagerLoadingBelongsToManyLimitedFieldsWithAutoFields()
    {
        $table = TableRegistry::get('Articles');
        $table->belongsToMany('Tags');
        $result = $table
            ->find()
            ->contain(['Tags' => function ($q) {
                return $q->select(['two' => $q->newExpr('1 + 1')])->autoFields(true);
            }])
            ->first();

        $this->assertNotEmpty($result->tags[0]->two, 'Should have computed field');
        $this->assertNotEmpty($result->tags[0]->name, 'Should have standard field');
    }

    /**
     * Test that association proxy find() applies joins when conditions are involved.
     *
     * @return void
     */
    public function testAssociationProxyFindWithConditions()
    {
        $table = TableRegistry::get('Articles');
        $table->belongsToMany('Tags', [
            'foreignKey' => 'article_id',
            'associationForeignKey' => 'tag_id',
            'conditions' => ['SpecialTags.highlighted' => true],
            'through' => 'SpecialTags'
        ]);
        $query = $table->Tags->find();
        $result = $query->toArray();
        $this->assertCount(1, $result);
        $this->assertEquals(1, $result[0]->id);
    }

    /**
     * Test that association proxy find() applies complex conditions
     *
     * @return void
     */
    public function testAssociationProxyFindWithComplexConditions()
    {
        $table = TableRegistry::get('Articles');
        $table->belongsToMany('Tags', [
            'foreignKey' => 'article_id',
            'associationForeignKey' => 'tag_id',
            'conditions' => [
                'OR' => [
                    'SpecialTags.highlighted' => true,
                ]
            ],
            'through' => 'SpecialTags'
        ]);
        $query = $table->Tags->find();
        $result = $query->toArray();
        $this->assertCount(1, $result);
        $this->assertEquals(1, $result[0]->id);
    }

    /**
     * Test that matching() works on belongsToMany associations.
     *
     * @return void
     */
    public function testBelongsToManyAssociationWithArrayConditions()
    {
        $table = TableRegistry::get('Articles');
        $table->belongsToMany('Tags', [
            'foreignKey' => 'article_id',
            'associationForeignKey' => 'tag_id',
            'conditions' => ['SpecialTags.highlighted' => true],
            'through' => 'SpecialTags'
        ]);
        $query = $table->find()->matching('Tags', function ($q) {
            return $q->where(['Tags.name' => 'tag1']);
        });
        $results = $query->toArray();
        $this->assertCount(1, $results);
        $this->assertNotEmpty($results[0]->_matchingData);
    }

    /**
     * Test that matching() works on belongsToMany associations.
     *
     * @return void
     */
    public function testBelongsToManyAssociationWithExpressionConditions()
    {
        $table = TableRegistry::get('Articles');
        $table->belongsToMany('Tags', [
            'foreignKey' => 'article_id',
            'associationForeignKey' => 'tag_id',
            'conditions' => [new QueryExpression("name LIKE 'tag%'")],
            'through' => 'SpecialTags'
        ]);
        $query = $table->find()->matching('Tags', function ($q) {
            return $q->where(['Tags.name' => 'tag1']);
        });
        $results = $query->toArray();
        $this->assertCount(1, $results);
        $this->assertNotEmpty($results[0]->_matchingData);
    }

    /**
     * Test that association proxy find() with matching resolves joins correctly
     *
     * @return void
     */
    public function testAssociationProxyFindWithConditionsMatching()
    {
        $table = TableRegistry::get('Articles');
        $table->belongsToMany('Tags', [
            'foreignKey' => 'article_id',
            'associationForeignKey' => 'tag_id',
            'conditions' => ['SpecialTags.highlighted' => true],
            'through' => 'SpecialTags'
        ]);
        $query = $table->Tags->find()->matching('Articles', function ($query) {
            return $query->where(['Articles.id' => 1]);
        });
        // The inner join on special_tags excludes the results.
        $this->assertEquals(0, $query->count());
    }
}
