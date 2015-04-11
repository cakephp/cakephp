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

use Cake\Database\Expression\IdentifierExpression;
use Cake\Database\Expression\QueryExpression;
use Cake\Database\Expression\TupleComparison;
use Cake\Database\TypeMap;
use Cake\Datasource\ConnectionManager;
use Cake\ORM\Association\BelongsToMany;
use Cake\ORM\Entity;
use Cake\ORM\Query;
use Cake\ORM\Table;
use Cake\ORM\TableRegistry;
use Cake\TestSuite\TestCase;

/**
 * Tests BelongsToMany class
 *
 */
class BelongsToManyTest extends TestCase
{

    /**
     * Set up
     *
     * @return void
     */
    public function setUp()
    {
        parent::setUp();
        $this->tag = $this->getMock(
            'Cake\ORM\Table',
            ['find', 'delete'],
            [['alias' => 'Tags', 'table' => 'tags']]
        );
        $this->tag->schema([
            'id' => ['type' => 'integer'],
            'name' => ['type' => 'string'],
            '_constraints' => [
                'primary' => ['type' => 'primary', 'columns' => ['id']]
            ]
        ]);
        $this->article = $this->getMock(
            'Cake\ORM\Table',
            ['find', 'delete'],
            [['alias' => 'Articles', 'table' => 'articles']]
        );
        $this->article->schema([
            'id' => ['type' => 'integer'],
            'name' => ['type' => 'string'],
            '_constraints' => [
                'primary' => ['type' => 'primary', 'columns' => ['id']]
            ]
        ]);
        TableRegistry::set('Articles', $this->article);
        TableRegistry::get('ArticlesTags', [
            'table' => 'articles_tags',
            'schema' => [
                'article_id' => ['type' => 'integer'],
                'tag_id' => ['type' => 'integer'],
                '_constraints' => [
                    'primary' => ['type' => 'primary', 'columns' => ['article_id', 'tag_id']]
                ]
            ]
        ]);
        $this->tagsTypeMap = new TypeMap([
            'Tags.id' => 'integer',
            'id' => 'integer',
            'Tags.name' => 'string',
            'name' => 'string',
        ]);
        $this->articlesTagsTypeMap = new TypeMap([
            'ArticlesTags.article_id' => 'integer',
            'article_id' => 'integer',
            'ArticlesTags.tag_id' => 'integer',
            'tag_id' => 'integer',
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
            'targetTable' => $this->tag
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
        $articleTag = $this->getMock('Cake\ORM\Table', ['deleteAll'], []);
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
        $articleTag = $this->getMock('Cake\ORM\Table', ['delete', 'deleteAll'], []);
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
        $articleTag = $this->getMock('Cake\ORM\Table', ['find', 'delete'], []);
        $config = [
            'sourceTable' => $this->article,
            'targetTable' => $this->tag,
            'cascadeCallbacks' => true,
        ];
        $association = new BelongsToMany('Tag', $config);
        $association->junction($articleTag);
        $this->article
            ->association($articleTag->alias())
            ->conditions(['click_count' => 3]);

        $articleTagOne = new Entity(['article_id' => 1, 'tag_id' => 2]);
        $articleTagTwo = new Entity(['article_id' => 1, 'tag_id' => 4]);
        $iterator = new \ArrayIterator([
            $articleTagOne,
            $articleTagTwo
        ]);

        $query = $this->getMock('\Cake\ORM\Query', [], [], '', false);
        $query->expects($this->at(0))
            ->method('where')
            ->with(['click_count' => 3])
            ->will($this->returnSelf());
        $query->expects($this->at(1))
            ->method('where')
            ->with(['article_id' => 1])
            ->will($this->returnSelf());

        $query->expects($this->any())
            ->method('getIterator')
            ->will($this->returnValue($iterator));

        $articleTag->expects($this->once())
            ->method('find')
            ->will($this->returnValue($query));

        $articleTag->expects($this->at(1))
            ->method('delete')
            ->with($articleTagOne, []);
        $articleTag->expects($this->at(2))
            ->method('delete')
            ->with($articleTagTwo, []);

        $articleTag->expects($this->never())
            ->method('deleteAll');

        $entity = new Entity(['id' => 1, 'name' => 'PHP']);
        $association->cascadeDelete($entity);
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
        $joint = $this->getMock(
            '\Cake\ORM\Table',
            ['save'],
            [['alias' => 'ArticlesTags', 'connection' => $connection]]
        );
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
        $connection = ConnectionManager::get('test');
        $joint = $this->getMock(
            '\Cake\ORM\Table',
            ['delete', 'find'],
            [['alias' => 'ArticlesTags', 'connection' => $connection]]
        );
        $config = [
            'sourceTable' => $this->article,
            'targetTable' => $this->tag,
            'through' => $joint,
            'joinTable' => 'tags_articles'
        ];
        $assoc = $this->article->belongsToMany('Test', $config);
        $assoc->junction();
        $this->article->association('ArticlesTags')
            ->conditions(['foo' => 1]);

        $query1 = $this->getMock('\Cake\ORM\Query', [], [$connection, $joint]);
        $query2 = $this->getMock('\Cake\ORM\Query', [], [$connection, $joint]);

        $joint->expects($this->at(0))->method('find')
            ->with('all')
            ->will($this->returnValue($query1));

        $joint->expects($this->at(1))->method('find')
            ->with('all')
            ->will($this->returnValue($query2));

        $query1->expects($this->at(0))
            ->method('where')
            ->with(['foo' => 1])
            ->will($this->returnSelf());
        $query1->expects($this->at(1))
            ->method('where')
            ->with(['article_id' => 1])
            ->will($this->returnSelf());
        $query1->expects($this->at(2))
            ->method('andWhere')
            ->with(['tag_id' => 2])
            ->will($this->returnSelf());
        $query1->expects($this->once())
            ->method('union')
            ->with($query2)
            ->will($this->returnSelf());

        $query2->expects($this->at(0))
            ->method('where')
            ->with(['foo' => 1])
            ->will($this->returnSelf());
        $query2->expects($this->at(1))
            ->method('where')
            ->with(['article_id' => 1])
            ->will($this->returnSelf());
        $query2->expects($this->at(2))
            ->method('andWhere')
            ->with(['tag_id' => 3])
            ->will($this->returnSelf());

        $jointEntities = [
            new Entity(['article_id' => 1, 'tag_id' => 2]),
            new Entity(['article_id' => 1, 'tag_id' => 3])
        ];

        $query1->expects($this->once())
            ->method('toArray')
            ->will($this->returnValue($jointEntities));

        $opts = ['markNew' => false];
        $tags = [new Entity(['id' => 2], $opts), new Entity(['id' => 3], $opts)];
        $entity = new Entity(['id' => 1, 'test' => $tags], $opts);

        $joint->expects($this->at(2))
            ->method('delete')
            ->with($jointEntities[0]);

        $joint->expects($this->at(3))
            ->method('delete')
            ->with($jointEntities[1]);

        $assoc->unlink($entity, $tags);
        $this->assertEmpty($entity->get('test'));
    }

    /**
     * Tests that unlinking with last parameter set to false
     * will not remove entities from the association property
     *
     * @return void
     */
    public function testUnlinkWithoutPropertyClean()
    {
        $connection = ConnectionManager::get('test');
        $joint = $this->getMock(
            '\Cake\ORM\Table',
            ['delete', 'find'],
            [['alias' => 'ArticlesTags', 'connection' => $connection]]
        );
        $config = [
            'sourceTable' => $this->article,
            'targetTable' => $this->tag,
            'through' => $joint,
            'joinTable' => 'tags_articles'
        ];
        $assoc = new BelongsToMany('Test', $config);
        $assoc
            ->junction()
            ->association('tags')
            ->conditions(['foo' => 1]);

        $joint->expects($this->never())->method('find');
        $opts = ['markNew' => false];
        $jointEntities = [
            new Entity(['article_id' => 1, 'tag_id' => 2]),
            new Entity(['article_id' => 1, 'tag_id' => 3])
        ];
        $tags = [
            new Entity(['id' => 2, '_joinData' => $jointEntities[0]], $opts),
            new Entity(['id' => 3, '_joinData' => $jointEntities[1]], $opts)
        ];
        $entity = new Entity(['id' => 1, 'test' => $tags], $opts);

        $joint->expects($this->at(0))
            ->method('delete')
            ->with($jointEntities[0]);

        $joint->expects($this->at(1))
            ->method('delete')
            ->with($jointEntities[1]);

        $assoc->unlink($entity, $tags, false);
        $this->assertEquals($tags, $entity->get('test'));
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
        $connection = ConnectionManager::get('test');
        $joint = $this->getMock(
            '\Cake\ORM\Table',
            ['delete', 'find'],
            [['alias' => 'ArticlesTags', 'connection' => $connection]]
        );
        $config = [
            'sourceTable' => $this->article,
            'targetTable' => $this->tag,
            'through' => $joint,
            'joinTable' => 'tags_articles'
        ];
        $assoc = $this->getMock(
            '\Cake\ORM\Association\BelongsToMany',
            ['_collectJointEntities', '_saveTarget'],
            ['tags', $config]
        );
        $assoc->junction();

        $this->article
            ->association('ArticlesTags')
            ->conditions(['foo' => 1]);

        $query1 = $this->getMock(
            '\Cake\ORM\Query',
            ['where', 'andWhere', 'addDefaultTypes'],
            [$connection, $joint]
        );

        $joint->expects($this->at(0))->method('find')
            ->with('all')
            ->will($this->returnValue($query1));

        $query1->expects($this->at(0))
            ->method('where')
            ->with(['foo' => 1])
            ->will($this->returnSelf());
        $query1->expects($this->at(1))
            ->method('where')
            ->with(['article_id' => 1])
            ->will($this->returnSelf());

        $existing = [
            new Entity(['article_id' => 1, 'tag_id' => 2]),
            new Entity(['article_id' => 1, 'tag_id' => 4]),
        ];
        $query1->setResult(new \ArrayIterator($existing));

        $tags = [];
        $entity = new Entity(['id' => 1, 'test' => $tags]);

        $assoc->expects($this->once())->method('_collectJointEntities')
            ->with($entity, $tags)
            ->will($this->returnValue([]));

        $joint->expects($this->at(1))
            ->method('delete')
            ->with($existing[0]);
        $joint->expects($this->at(2))
            ->method('delete')
            ->with($existing[1]);

        $assoc->expects($this->never())
            ->method('_saveTarget');

        $assoc->replaceLinks($entity, $tags);
        $this->assertSame([], $entity->tags);
        $this->assertFalse($entity->dirty('tags'));
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
        $connection = ConnectionManager::get('test');
        $joint = $this->getMock(
            '\Cake\ORM\Table',
            ['delete', 'find'],
            [['alias' => 'ArticlesTags', 'connection' => $connection]]
        );
        $config = [
            'sourceTable' => $this->article,
            'targetTable' => $this->tag,
            'through' => $joint,
            'joinTable' => 'tags_articles'
        ];
        $assoc = $this->getMock(
            '\Cake\ORM\Association\BelongsToMany',
            ['_collectJointEntities', '_saveTarget'],
            ['tags', $config]
        );
        $assoc->junction();

        $this->article
            ->association('ArticlesTags')
            ->conditions(['foo' => 1]);

        $query1 = $this->getMock(
            '\Cake\ORM\Query',
            ['where', 'andWhere', 'addDefaultTypes'],
            [$connection, $joint]
        );

        $joint->expects($this->at(0))->method('find')
            ->with('all')
            ->will($this->returnValue($query1));

        $query1->expects($this->at(0))
            ->method('where')
            ->with(['foo' => 1])
            ->will($this->returnSelf());
        $query1->expects($this->at(1))
            ->method('where')
            ->with(['article_id' => 1])
            ->will($this->returnSelf());

        $existing = [
            new Entity(['article_id' => 1, 'tag_id' => 2]),
            new Entity(['article_id' => 1, 'tag_id' => 4]),
            new Entity(['article_id' => 1, 'tag_id' => 5]),
            new Entity(['article_id' => 1, 'tag_id' => 6])
        ];
        $query1->setResult(new \ArrayIterator($existing));

        $opts = ['markNew' => false];
        $tags = [
            new Entity(['id' => 2], $opts),
            new Entity(['id' => 3], $opts),
            new Entity(['id' => 6])
        ];
        $entity = new Entity(['id' => 1, 'test' => $tags], $opts);

        $jointEntities = [
            new Entity(['article_id' => 1, 'tag_id' => 2])
        ];
        $assoc->expects($this->once())->method('_collectJointEntities')
            ->with($entity, $tags)
            ->will($this->returnValue($jointEntities));

        $joint->expects($this->at(1))
            ->method('delete')
            ->with($existing[1]);
        $joint->expects($this->at(2))
            ->method('delete')
            ->with($existing[2]);

        $options = ['foo' => 'bar'];
        $assoc->expects($this->once())
            ->method('_saveTarget')
            ->with($entity, [1 => $tags[1], 2 => $tags[2]], $options + ['associated' => false])
            ->will($this->returnCallback(function ($entity, $inserts) use ($tags) {
                $this->assertSame([1 => $tags[1], 2 => $tags[2]], $inserts);
                $entity->tags = $inserts;
                return true;
            }));

        $assoc->replaceLinks($entity, $tags, $options + ['associated' => false]);
        $this->assertSame($tags, $entity->tags);
        $this->assertFalse($entity->dirty('tags'));
    }

    /**
     * Test that saving an empty set on create works.
     *
     * @return void
     */
    public function testSaveAssociatedEmptySetSuccess()
    {
        $assoc = $this->getMock(
            '\Cake\ORM\Association\BelongsToMany',
            ['_saveTarget', 'replaceLinks'],
            ['tags']
        );
        $entity = new Entity([
            'id' => 1,
            'tags' => []
        ], ['markNew' => true]);

        $assoc->saveStrategy(BelongsToMany::SAVE_REPLACE);
        $assoc->expects($this->never())
            ->method('replaceLinks');
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
        $assoc = $this->getMock(
            '\Cake\ORM\Association\BelongsToMany',
            ['replaceLinks'],
            ['tags']
        );
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
        $assoc = $this->getMock(
            '\Cake\ORM\Association\BelongsToMany',
            ['replaceLinks'],
            ['tags']
        );
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
        $mock = $this->getMock(
            'Cake\ORM\Table',
            ['saveAssociated', 'schema'],
            [['table' => 'tags', 'connection' => $connection]]
        );
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
        $mock = $this->getMock('Cake\ORM\Table', [], [], '', false);
        $config = [
            'sourceTable' => $this->article,
            'targetTable' => $mock,
        ];
        $association = new BelongsToMany('Contacts.Tags', $config);
        $this->assertEquals('tags', $association->property());
    }
}
