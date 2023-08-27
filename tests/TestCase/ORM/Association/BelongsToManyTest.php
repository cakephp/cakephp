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
namespace Cake\Test\TestCase\ORM\Association;

use Cake\Database\Connection;
use Cake\Database\Driver;
use Cake\Database\Exception\DatabaseException;
use Cake\Database\Expression\OrderClauseExpression;
use Cake\Database\Expression\QueryExpression;
use Cake\Datasource\ConnectionManager;
use Cake\Datasource\EntityInterface;
use Cake\Event\EventInterface;
use Cake\I18n\DateTime;
use Cake\ORM\Association\BelongsTo;
use Cake\ORM\Association\BelongsToMany;
use Cake\ORM\Association\HasMany;
use Cake\ORM\Entity;
use Cake\ORM\Exception\MissingTableClassException;
use Cake\ORM\Locator\TableLocator;
use Cake\ORM\Query\SelectQuery;
use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\TestSuite\TestCase;
use InvalidArgumentException;
use TestApp\Model\Entity\ArticlesTag;
use function Cake\Collection\collection;

/**
 * Tests BelongsToMany class
 */
class BelongsToManyTest extends TestCase
{
    /**
     * Fixtures
     *
     * @var array<string>
     */
    protected array $fixtures = [
        'core.Articles',
        'core.Tags',
        'core.SpecialTags',
        'core.ArticlesTags',
        'core.ArticlesTagsBindingKeys',
        'core.BinaryUuidItems',
        'core.BinaryUuidTags',
        'core.BinaryUuidItemsBinaryUuidTags',
        'core.CompositeKeyArticles',
        'core.CompositeKeyArticlesTags',
    ];

    /**
     * @var \Cake\ORM\Table|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $tag;

    /**
     * @var \Cake\ORM\Table|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $article;

    /**
     * Set up
     */
    public function setUp(): void
    {
        parent::setUp();
        $this->tag = $this->getMockBuilder(Table::class)
            ->onlyMethods(['find', 'delete'])
            ->setConstructorArgs([['alias' => 'Tags', 'table' => 'tags']])
            ->getMock();
        $this->tag->setSchema([
            'id' => ['type' => 'integer'],
            'name' => ['type' => 'string'],
            '_constraints' => [
                'primary' => ['type' => 'primary', 'columns' => ['id']],
            ],
        ]);
        $this->article = $this->getMockBuilder(Table::class)
            ->onlyMethods(['find', 'delete'])
            ->setConstructorArgs([['alias' => 'Articles', 'table' => 'articles']])
            ->getMock();
        $this->article->setSchema([
            'id' => ['type' => 'integer'],
            'name' => ['type' => 'string'],
            '_constraints' => [
                'primary' => ['type' => 'primary', 'columns' => ['id']],
            ],
        ]);
    }

    /**
     * Tests setForeignKey()
     */
    public function testSetForeignKey(): void
    {
        $assoc = new BelongsToMany('Test', [
            'sourceTable' => $this->article,
            'targetTable' => $this->tag,
        ]);
        $this->assertSame('article_id', $assoc->getForeignKey());
        $this->assertSame($assoc, $assoc->setForeignKey('another_key'));
        $this->assertSame('another_key', $assoc->getForeignKey());
    }

    /**
     * Tests that the association reports it can be joined
     */
    public function testCanBeJoined(): void
    {
        $assoc = new BelongsToMany('Test');
        $this->assertFalse($assoc->canBeJoined());
    }

    /**
     * Tests setSort() method
     */
    public function testSetSort(): void
    {
        $assoc = new BelongsToMany('Test');
        $this->assertNull($assoc->getSort());

        $assoc->setSort('id ASC');
        $this->assertSame('id ASC', $assoc->getSort());

        $assoc->setSort(['id' => 'ASC']);
        $this->assertSame(['id' => 'ASC'], $assoc->getSort());

        $closure = function () {
            return ['id' => 'ASC'];
        };
        $assoc->setSort($closure);
        $this->assertSame($closure, $assoc->getSort());

        $expression = new OrderClauseExpression('id', 'ASC');
        $assoc->setSort($expression);
        $this->assertSame($expression, $assoc->getSort());
    }

    /**
     * Tests that sorting works using the accepted types for `setSort()`.
     */
    public function testSorting(): void
    {
        $articles = $this->getTableLocator()->get('Articles');
        $assoc = $articles->belongsToMany('Tags');

        $field = 'Tags.id';
        $driver = $articles->getConnection()->getDriver();
        if ($driver->isAutoQuotingEnabled()) {
            $field = $driver->quoteIdentifier($field);
        }

        $assoc->setSort("$field DESC");
        $result = $articles->get(1, ...['contain' => 'Tags']);
        $this->assertSame([2, 1], array_column($result['tags'], 'id'));

        $assoc->setSort(['Tags.id' => 'DESC']);
        $result = $articles->get(1, ...['contain' => 'Tags']);
        $this->assertSame([2, 1], array_column($result['tags'], 'id'));

        $assoc->setSort(function () {
            return ['Tags.id' => 'DESC'];
        });
        $result = $articles->get(1, ...['contain' => 'Tags']);
        $this->assertSame([2, 1], array_column($result['tags'], 'id'));

        $assoc->setSort(new OrderClauseExpression('Tags.id', 'DESC'));
        $result = $articles->get(1, ...['contain' => 'Tags']);
        $this->assertSame([2, 1], array_column($result['tags'], 'id'));
    }

    /**
     * Tests requiresKeys() method
     */
    public function testRequiresKeys(): void
    {
        $assoc = new BelongsToMany('Test');
        $this->assertTrue($assoc->requiresKeys());

        $assoc->setStrategy(BelongsToMany::STRATEGY_SUBQUERY);
        $this->assertFalse($assoc->requiresKeys());

        $assoc->setStrategy(BelongsToMany::STRATEGY_SELECT);
        $this->assertTrue($assoc->requiresKeys());
    }

    /**
     * Tests that BelongsToMany can't use the join strategy
     */
    public function testStrategyFailure(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid strategy `join` was provided');
        $assoc = new BelongsToMany('Test');
        $assoc->setStrategy(BelongsToMany::STRATEGY_JOIN);
    }

    /**
     * Tests the junction method
     */
    public function testJunction(): void
    {
        $assoc = new BelongsToMany('Test', [
            'sourceTable' => $this->article,
            'targetTable' => $this->tag,
            'strategy' => 'subquery',
        ]);
        $junction = $assoc->junction();
        $this->assertInstanceOf(Table::class, $junction);
        $this->assertSame('ArticlesTags', $junction->getAlias());
        $this->assertSame('articles_tags', $junction->getTable());
        $this->assertSame($this->article, $junction->getAssociation('Articles')->getTarget());
        $this->assertSame($this->tag, $junction->getAssociation('Tags')->getTarget());

        $this->assertInstanceOf(BelongsTo::class, $junction->getAssociation('Articles'));
        $this->assertInstanceOf(BelongsTo::class, $junction->getAssociation('Tags'));

        $this->assertSame($junction, $this->tag->getAssociation('ArticlesTags')->getTarget());
        $this->assertSame($this->article, $this->tag->getAssociation('Articles')->getTarget());

        $this->assertInstanceOf(BelongsToMany::class, $this->tag->getAssociation('Articles'));
        $this->assertInstanceOf(HasMany::class, $this->tag->getAssociation('ArticlesTags'));

        $this->assertSame($junction, $assoc->junction());
        $junction2 = $this->getTableLocator()->get('Foos');
        $assoc->junction($junction2);
        $this->assertSame($junction2, $assoc->junction());

        $assoc->junction('ArticlesTags');
        $this->assertSame($junction, $assoc->junction());

        $this->assertSame($assoc->getStrategy(), $this->tag->getAssociation('Articles')->getStrategy());
        $this->assertSame($assoc->getStrategy(), $this->tag->getAssociation('ArticlesTags')->getStrategy());
        $this->assertSame($assoc->getStrategy(), $this->article->getAssociation('ArticlesTags')->getStrategy());

        $this->assertSame($this->article->getPrimaryKey(), $junction->getAssociation('Articles')->getBindingKey());
        $this->assertSame($this->tag->getPrimaryKey(), $junction->getAssociation('Tags')->getBindingKey());
    }

    /**
     * Tests the junction passes the source connection name on.
     */
    public function testJunctionConnection(): void
    {
        $driver = $this->getMockBuilder(Driver::class)->getMock();
        $driver->expects($this->once())
            ->method('enabled')
            ->willReturn(true);

        $mock = $this->getMockBuilder(Connection::class)
            ->setConstructorArgs([['name' => 'other_source', 'driver' => $driver]])
            ->getMock();
        ConnectionManager::setConfig('other_source', $mock);
        $this->article->setConnection(ConnectionManager::get('other_source'));

        $assoc = new BelongsToMany('Test', [
            'sourceTable' => $this->article,
            'targetTable' => $this->tag,
        ]);
        $junction = $assoc->junction();
        $this->assertSame($mock, $junction->getConnection());
        ConnectionManager::drop('other_source');
    }

    /**
     * Tests the junction method custom keys
     */
    public function testJunctionCustomKeys(): void
    {
        $this->article->belongsToMany('Tags', [
            'targetTable' => $this->tag,
            'joinTable' => 'articles_tags',
            'foreignKey' => 'article',
            'targetForeignKey' => 'tag',
        ]);
        $this->tag->belongsToMany('Articles', [
            'targetTable' => $this->article,
            'joinTable' => 'articles_tags',
            'foreignKey' => 'tag',
            'targetForeignKey' => 'article',
        ]);
        $junction = $this->article->getAssociation('Tags')->junction();
        $this->assertSame('article', $junction->getAssociation('Articles')->getForeignKey());
        $this->assertSame('article', $this->article->getAssociation('ArticlesTags')->getForeignKey());

        $junction = $this->tag->getAssociation('Articles')->junction();
        $this->assertSame('tag', $junction->getAssociation('Tags')->getForeignKey());
        $this->assertSame('tag', $this->tag->getAssociation('ArticlesTags')->getForeignKey());
    }

    /**
     * Tests it is possible to set the table name for the join table
     */
    public function testJunctionWithDefaultTableName(): void
    {
        $assoc = new BelongsToMany('Test', [
            'sourceTable' => $this->article,
            'targetTable' => $this->tag,
            'joinTable' => 'tags_articles',
        ]);
        $junction = $assoc->junction();
        $this->assertSame('TagsArticles', $junction->getAlias());
        $this->assertSame('tags_articles', $junction->getTable());
    }

    /**
     * Test multiple associations with differerent keys fails
     */
    public function testMultipleAssociationsSameJunction(): void
    {
        $assoc = new BelongsToMany('This', [
            'sourceTable' => $this->article,
            'targetTable' => $this->tag,
            'targetForeignKey' => 'this_id',
        ]);
        $assoc->junction();

        $assoc = new BelongsToMany('That', [
            'sourceTable' => $this->article,
            'targetTable' => $this->tag,
            'targetForeignKey' => 'that_id',
        ]);

        $this->expectException(InvalidArgumentException::class);
        $assoc->junction();
    }

    /**
     * Tests same source and target table failure.
     */
    public function testSameSourceTargetJunction(): void
    {
        $assoc = new BelongsToMany('This', [
            'sourceTable' => $this->article,
            'targetTable' => $this->article,
        ]);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The `This` association on `Articles` cannot target the same table.');
        $assoc->junction();
    }

    /**
     * Tests saveStrategy
     */
    public function testSetSaveStrategy(): void
    {
        $assoc = new BelongsToMany('Test');
        $this->assertSame(BelongsToMany::SAVE_REPLACE, $assoc->getSaveStrategy());

        $assoc->setSaveStrategy(BelongsToMany::SAVE_APPEND);
        $this->assertSame(BelongsToMany::SAVE_APPEND, $assoc->getSaveStrategy());

        $assoc->setSaveStrategy(BelongsToMany::SAVE_REPLACE);
        $this->assertSame(BelongsToMany::SAVE_REPLACE, $assoc->getSaveStrategy());
    }

    /**
     * Tests that it is possible to pass the saveAssociated strategy in the constructor
     */
    public function testSaveStrategyInOptions(): void
    {
        $assoc = new BelongsToMany('Test', ['saveStrategy' => BelongsToMany::SAVE_APPEND]);
        $this->assertSame(BelongsToMany::SAVE_APPEND, $assoc->getSaveStrategy());
    }

    /**
     * Tests that passing an invalid strategy will throw an exception
     */
    public function testSaveStrategyInvalid(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid save strategy `depsert`');
        new BelongsToMany('Test', ['saveStrategy' => 'depsert']);
    }

    /**
     * Ensure that the `finder` option is applied to the target
     * table.
     */
    public function testFinderOption(): void
    {
        $this->setAppNamespace('TestApp');

        $articles = $this->getTableLocator()->get('Articles');
        $tags = $this->getTableLocator()->get('Tags');

        $tags->belongsToMany('Articles', [
            'sourceTable' => $tags,
            'targetTable' => $articles,
            'finder' => 'published',
        ]);
        $articles->updateAll(['published' => 'N'], ['id' => 1]);
        $entity = $tags->get(1, ...['contain' => 'Articles']);
        $this->assertCount(1, $entity->articles, 'only one article should load');
        $this->assertSame('Y', $entity->articles[0]->published);
    }

    /**
     * Test cascading deletes.
     */
    public function testCascadeDelete(): void
    {
        $articleTag = $this->getMockBuilder(Table::class)
            ->onlyMethods(['deleteAll'])
            ->getMock();
        $config = [
            'sourceTable' => $this->article,
            'targetTable' => $this->tag,
            'sort' => ['id' => 'ASC'],
        ];
        $association = new BelongsToMany('Tags', $config);
        $association->junction($articleTag);
        $this->article
            ->getAssociation($articleTag->getAlias())
            ->setConditions(['click_count' => 3]);

        $articleTag->expects($this->once())
            ->method('deleteAll')
            ->with([
                'click_count' => 3,
                'article_id' => 1,
            ]);

        $entity = new Entity(['id' => 1, 'name' => 'PHP']);
        $association->cascadeDelete($entity);
    }

    /**
     * Test cascading deletes with dependent=false
     */
    public function testCascadeDeleteDependent(): void
    {
        $articleTag = $this->getMockBuilder(Table::class)
            ->onlyMethods(['delete', 'deleteAll'])
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
            ->getAssociation($articleTag->getAlias())
            ->setConditions(['click_count' => 3]);

        $articleTag->expects($this->never())
            ->method('deleteAll');
        $articleTag->expects($this->never())
            ->method('delete');

        $entity = new Entity(['id' => 1, 'name' => 'PHP']);
        $association->cascadeDelete($entity);
    }

    /**
     * Test cascading deletes with callbacks.
     */
    public function testCascadeDeleteWithCallbacks(): void
    {
        $articleTag = $this->getTableLocator()->get('ArticlesTags');
        $config = [
            'sourceTable' => $this->article,
            'targetTable' => $this->tag,
            'cascadeCallbacks' => true,
        ];
        $association = new BelongsToMany('Tag', $config);
        $association->junction($articleTag);
        $this->article->getAssociation($articleTag->getAlias());

        $counter = 0;
        $articleTag->getEventManager()->on('Model.beforeDelete', function () use (&$counter): void {
            $counter++;
        });

        $this->assertSame(2, $articleTag->find()->where(['article_id' => 1])->count());
        $entity = new Entity(['id' => 1, 'name' => 'PHP']);
        $association->cascadeDelete($entity);

        $this->assertSame(0, $articleTag->find()->where(['article_id' => 1])->count());
        $this->assertSame(2, $counter);
    }

    /**
     * Test cascading delete with a rule preventing deletion
     */
    public function testCascadeDeleteCallbacksRuleFailure(): void
    {
        $articleTag = $this->getTableLocator()->get('ArticlesTags');
        $config = [
            'sourceTable' => $this->article,
            'targetTable' => $this->tag,
            'cascadeCallbacks' => true,
        ];
        $association = new BelongsToMany('Tag', $config);
        $association->junction($articleTag);
        $this->article->getAssociation($articleTag->getAlias());

        $articleTag->getEventManager()->on('Model.buildRules', function ($event, $rules): void {
            $rules->addDelete(function () {
                return false;
            });
        });
        $entity = new Entity(['id' => 1, 'name' => 'PHP']);
        $this->assertFalse($association->cascadeDelete($entity));

        $matching = $articleTag->find()
            ->where(['ArticlesTags.tag_id' => $entity->id])
            ->all();
        $this->assertGreaterThan(0, count($matching));
    }

    /**
     * Test linking entities having a non persisted source entity
     */
    public function testLinkWithNotPersistedSource(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Source entity needs to be persisted before links can be created or removed');
        $config = [
            'sourceTable' => $this->article,
            'targetTable' => $this->tag,
            'joinTable' => 'tags_articles',
        ];
        $assoc = new BelongsToMany('Test', $config);
        $entity = new Entity(['id' => 1]);
        $tags = [new Entity(['id' => 2]), new Entity(['id' => 3])];
        $assoc->link($entity, $tags);
    }

    /**
     * Test liking entities having a non persisted target entity
     */
    public function testLinkWithNotPersistedTarget(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Cannot link entities that have not been persisted yet');
        $config = [
            'sourceTable' => $this->article,
            'targetTable' => $this->tag,
            'joinTable' => 'tags_articles',
        ];
        $assoc = new BelongsToMany('Test', $config);
        $entity = new Entity(['id' => 1], ['markNew' => false]);
        $tags = [new Entity(['id' => 2]), new Entity(['id' => 3])];
        $assoc->link($entity, $tags);
    }

    /**
     * Tests that linking entities will persist correctly with append strategy
     */
    public function testLinkSuccessSaveAppend(): void
    {
        $articles = $this->getTableLocator()->get('Articles');
        $tags = $this->getTableLocator()->get('Tags');

        $config = [
            'sourceTable' => $articles,
            'targetTable' => $tags,
            'saveStrategy' => BelongsToMany::SAVE_APPEND,
        ];
        $assoc = $articles->belongsToMany('Tags', $config);

        // Load without tags as that is a main use case for append strategies
        $article = $articles->get(1);
        $opts = ['markNew' => false];
        $tags = [
            new Entity(['id' => 2, 'name' => 'add'], $opts),
            new Entity(['id' => 3, 'name' => 'adder'], $opts),
        ];

        $this->assertTrue($assoc->link($article, $tags));
        $this->assertCount(2, $article->tags, 'In-memory tags are incorrect');
        $this->assertSame([2, 3], collection($article->tags)->extract('id')->toList());

        $article = $articles->get(1, ...['contain' => ['Tags']]);
        $this->assertCount(3, $article->tags, 'Persisted tags are wrong');
        $this->assertSame([1, 2, 3], collection($article->tags)->extract('id')->toList());
    }

    /**
     * Tests that linking the same tag to multiple articles works
     */
    public function testLinkSaveAppendSharedTarget(): void
    {
        $articles = $this->getTableLocator()->get('Articles');
        $tags = $this->getTableLocator()->get('Tags');
        $articlesTags = $this->getTableLocator()->get('ArticlesTags');
        $articlesTags->deleteAll('1=1');

        $config = [
            'sourceTable' => $articles,
            'targetTable' => $tags,
            'saveStrategy' => BelongsToMany::SAVE_APPEND,
        ];
        $assoc = $articles->belongsToMany('Tags', $config);

        $articleOne = $articles->get(1);
        $articleTwo = $articles->get(2);

        $tagTwo = $tags->get(2);
        $tagThree = $tags->get(3);

        $this->assertTrue($assoc->link($articleOne, [$tagThree, $tagTwo]));
        $this->assertTrue($assoc->link($articleTwo, [$tagThree]));

        $this->assertCount(2, $articleOne->tags, 'In-memory tags are incorrect');
        $this->assertSame([3, 2], collection($articleOne->tags)->extract('id')->toList());

        $this->assertCount(1, $articleTwo->tags, 'In-memory tags are incorrect');
        $this->assertSame([3], collection($articleTwo->tags)->extract('id')->toList());
        $rows = $articlesTags->find()->all();
        $this->assertCount(3, $rows, '3 link rows should be created.');
    }

    /**
     * Tests that liking entities will validate data and pass on to _saveLinks
     */
    public function testLinkSuccessWithMocks(): void
    {
        $connection = ConnectionManager::get('test');
        $joint = $this->getMockBuilder(Table::class)
            ->onlyMethods(['save', 'getPrimaryKey'])
            ->setConstructorArgs([['alias' => 'ArticlesTags', 'connection' => $connection]])
            ->getMock();

        $config = [
            'sourceTable' => $this->article,
            'targetTable' => $this->tag,
            'through' => $joint,
            'joinTable' => 'tags_articles',
        ];

        $assoc = new BelongsToMany('Test', $config);
        $opts = ['markNew' => false];
        $entity = new Entity(['id' => 1], $opts);
        $tags = [new Entity(['id' => 2], $opts), new Entity(['id' => 3], $opts)];
        $saveOptions = ['foo' => 'bar'];

        $joint->method('getPrimaryKey')
            ->willReturn(['article_id', 'tag_id']);

        $joint->expects($this->exactly(2))
            ->method('save')
            ->willReturnOnConsecutiveCalls($entity, $entity);

        $this->assertTrue($assoc->link($entity, $tags, $saveOptions));
        $this->assertSame($entity->test, $tags);
    }

    /**
     * Tests that linking entities will set the junction table registry alias
     */
    public function testLinkSetSourceToJunctionEntities(): void
    {
        $connection = ConnectionManager::get('test');
        /** @var \Cake\ORM\Table|\PHPUnit\Framework\MockObject\MockObject $joint */
        $joint = $this->getMockBuilder(Table::class)
            ->onlyMethods(['save', 'getPrimaryKey'])
            ->setConstructorArgs([['alias' => 'ArticlesTags', 'connection' => $connection]])
            ->getMock();
        $joint->setRegistryAlias('Plugin.ArticlesTags');

        $config = [
            'sourceTable' => $this->article,
            'targetTable' => $this->tag,
            'through' => $joint,
        ];

        $assoc = new BelongsToMany('Tags', $config);
        $opts = ['markNew' => false];
        $entity = new Entity(['id' => 1], $opts);
        $tags = [new Entity(['id' => 2], $opts)];

        $joint->method('getPrimaryKey')
            ->willReturn(['article_id', 'tag_id']);

        $joint->expects($this->once())
            ->method('save')
            ->willReturnCallback(function (EntityInterface $e) {
                $this->assertSame('Plugin.ArticlesTags', $e->getSource());

                return $e;
            });

        $this->assertTrue($assoc->link($entity, $tags));
        $this->assertSame($entity->tags, $tags);
        $this->assertSame('Plugin.ArticlesTags', $entity->tags[0]->get('_joinData')->getSource());
    }

    /**
     * Test liking entities having a non persisted source entity
     */
    public function testUnlinkWithNotPersistedSource(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Source entity needs to be persisted before links can be created or removed');
        $config = [
            'sourceTable' => $this->article,
            'targetTable' => $this->tag,
            'joinTable' => 'tags_articles',
        ];
        $assoc = new BelongsToMany('Test', $config);
        $entity = new Entity(['id' => 1]);
        $tags = [new Entity(['id' => 2]), new Entity(['id' => 3])];
        $assoc->unlink($entity, $tags);
    }

    /**
     * Test liking entities having a non persisted target entity
     */
    public function testUnlinkWithNotPersistedTarget(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Cannot link entities that have not been persisted');
        $config = [
            'sourceTable' => $this->article,
            'targetTable' => $this->tag,
            'joinTable' => 'tags_articles',
        ];
        $assoc = new BelongsToMany('Test', $config);
        $entity = new Entity(['id' => 1], ['markNew' => false]);
        $tags = [new Entity(['id' => 2]), new Entity(['id' => 3])];
        $assoc->unlink($entity, $tags);
    }

    /**
     * Tests that unlinking calls the right methods
     */
    public function testUnlinkSuccess(): void
    {
        $joint = $this->getTableLocator()->get('SpecialTags');
        $articles = $this->getTableLocator()->get('Articles');
        $tags = $this->getTableLocator()->get('Tags');

        $assoc = $articles->belongsToMany('Tags', [
            'sourceTable' => $articles,
            'targetTable' => $tags,
            'through' => $joint,
            'joinTable' => 'special_tags',
        ]);
        $entity = $articles->get(2, ...['contain' => 'Tags']);
        $initial = $entity->tags;
        $this->assertCount(1, $initial);

        $this->assertTrue($assoc->unlink($entity, $entity->tags));
        $this->assertEmpty($entity->get('tags'), 'Property should be empty');

        $new = $articles->get(2, ...['contain' => 'Tags']);
        $this->assertCount(0, $new->tags, 'DB should be clean');
        $this->assertSame(3, $tags->find()->count(), 'Tags should still exist');
    }

    /**
     * Tests that unlinking with last parameter set to false
     * will not remove entities from the association property
     */
    public function testUnlinkWithoutPropertyClean(): void
    {
        $joint = $this->getTableLocator()->get('SpecialTags');
        $articles = $this->getTableLocator()->get('Articles');
        $tags = $this->getTableLocator()->get('Tags');

        $assoc = $articles->belongsToMany('Tags', [
            'sourceTable' => $articles,
            'targetTable' => $tags,
            'through' => $joint,
            'joinTable' => 'special_tags',
            'conditions' => ['SpecialTags.highlighted' => true],
        ]);
        $entity = $articles->get(2, ...['contain' => 'Tags']);
        $initial = $entity->tags;
        $this->assertCount(1, $initial);

        $this->assertTrue($assoc->unlink($entity, $initial, ['cleanProperty' => false]));
        $this->assertNotEmpty($entity->get('tags'), 'Property should not be empty');
        $this->assertEquals($initial, $entity->get('tags'), 'Property should be untouched');

        $new = $articles->get(2, ...['contain' => 'Tags']);
        $this->assertCount(0, $new->tags, 'DB should be clean');
    }

    /**
     * Tests that replaceLink requires the sourceEntity to have primaryKey values
     * for the source entity
     */
    public function testReplaceWithMissingPrimaryKey(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Could not find primary key value for source entity');
        $config = [
            'sourceTable' => $this->article,
            'targetTable' => $this->tag,
            'joinTable' => 'tags_articles',
        ];
        $assoc = new BelongsToMany('Test', $config);
        $entity = new Entity(['foo' => 1], ['markNew' => false]);
        $tags = [new Entity(['id' => 2]), new Entity(['id' => 3])];
        $assoc->replaceLinks($entity, $tags);
    }

    /**
     * Test that replaceLinks() can saveAssociated an empty set, removing all rows.
     */
    public function testReplaceLinksUpdateToEmptySet(): void
    {
        $joint = $this->getTableLocator()->get('ArticlesTags');
        $articles = $this->getTableLocator()->get('Articles');
        $tags = $this->getTableLocator()->get('Tags');

        $assoc = $articles->belongsToMany('Tags', [
            'sourceTable' => $articles,
            'targetTable' => $tags,
            'through' => $joint,
            'joinTable' => 'articles_tags',
        ]);

        $entity = $articles->get(1, ...['contain' => 'Tags']);
        $this->assertCount(2, $entity->tags);

        $assoc->replaceLinks($entity, []);
        $this->assertSame([], $entity->tags, 'Property should be empty');
        $this->assertFalse($entity->isDirty('tags'), 'Property should be cleaned');

        $new = $articles->get(1, ...['contain' => 'Tags']);
        $this->assertSame([], $new->tags, 'Should not be data in db');
    }

    /**
     * Tests that replaceLinks will delete entities not present in the passed,
     * array, maintain those are already persisted and were passed and also
     * insert the rest.
     */
    public function testReplaceLinkSuccess(): void
    {
        $joint = $this->getTableLocator()->get('ArticlesTags');
        $articles = $this->getTableLocator()->get('Articles');
        $tags = $this->getTableLocator()->get('Tags');

        $assoc = $articles->belongsToMany('Tags', [
            'sourceTable' => $articles,
            'targetTable' => $tags,
            'through' => $joint,
        ]);
        $entity = $articles->get(1, ...['contain' => 'Tags']);

        // 1=existing, 2=removed, 3=new link, & new tag
        $tagData = [
            new Entity(['id' => 1], ['markNew' => false]),
            new Entity(['id' => 3]),
            new Entity(['name' => 'net new']),
        ];

        $result = $assoc->replaceLinks($entity, $tagData, ['associated' => false]);
        $this->assertTrue($result);
        $this->assertSame($tagData, $entity->tags, 'Tags should match replaced objects');
        $this->assertFalse($entity->isDirty('tags'), 'Should be clean');

        $fresh = $articles->get(1, ...['contain' => 'Tags']);
        $this->assertCount(3, $fresh->tags, 'Records should be in db');

        $this->assertNotEmpty($tags->get(2), 'Unlinked tag should still exist');
    }

    /**
     * Tests that replaceLinks() will contain() the target table when
     * there are conditions present on the association.
     *
     * In this case the replacement will fail because the association conditions
     * hide the fixture data.
     */
    public function testReplaceLinkWithConditions(): void
    {
        $joint = $this->getTableLocator()->get('SpecialTags');
        $articles = $this->getTableLocator()->get('Articles');
        $tags = $this->getTableLocator()->get('Tags');

        $assoc = $articles->belongsToMany('Tags', [
            'sourceTable' => $articles,
            'targetTable' => $tags,
            'through' => $joint,
            'joinTable' => 'special_tags',
            'conditions' => ['SpecialTags.highlighted' => true],
        ]);
        $entity = $articles->get(1, ...['contain' => 'Tags']);

        $result = $assoc->replaceLinks($entity, [], ['associated' => false]);
        $this->assertTrue($result);
        $this->assertSame([], $entity->tags, 'Tags should match replaced objects');
        $this->assertFalse($entity->isDirty('tags'), 'Should be clean');

        $fresh = $articles->get(1, ...['contain' => 'Tags']);
        $this->assertCount(0, $fresh->tags, 'Association should be empty');

        $jointCount = $joint->find()->where(['article_id' => 1])->count();
        $this->assertSame(1, $jointCount, 'Non matching joint record should remain.');
    }

    /**
     * Test that replaceLinks() will apply finder conditions
     * defined in the junction table associations if they exist.
     */
    public function testReplaceLinkWithFinderInJunctionAssociations(): void
    {
        $this->setAppNamespace('TestApp');

        $joint = $this->getTableLocator()->get('ArticlesTags');
        $articles = $this->getTableLocator()->get('Articles');
        $tags = $this->getTableLocator()->get('Tags');

        // Update an article to not match the association finder.
        $articles->updateAll(['published' => 'N'], ['id' => 1]);
        $assoc = $tags->belongsToMany('Articles', [
            'sourceTable' => $tags,
            'targetTable' => $articles,
            'through' => $joint,
            'finder' => 'published',
        ]);
        $entity = $tags->get(1, ...['contain' => 'Articles']);
        $this->assertCount(1, $entity->articles);

        $result = $assoc->replaceLinks($entity, [], ['associated' => false]);
        $this->assertTrue($result);
        $this->assertSame([], $entity->articles, 'Articles should match replaced objects');
        $this->assertFalse($entity->isDirty('articles'), 'Should be clean');

        $fresh = $tags->get(1, ...['contain' => 'Articles']);
        $this->assertCount(0, $fresh->articles, 'Association should be empty');

        $other = $joint->find()->where(['tag_id' => 1])->toArray();
        $this->assertCount(1, $other, 'Non matching joint record should remain.');
        $this->assertSame(1, $other[0]->article_id);
    }

    /**
     * Test that replaceLinks() loads junction records with the correct entity class
     */
    public function testReplaceLinksFetchCorrectJunctionEntity(): void
    {
        $joint = $this->getTableLocator()->get('ArticlesTags');
        $articles = $this->getTableLocator()->get('Articles');
        $tags = $this->getTableLocator()->get('Tags');

        $assoc = $articles->belongsToMany('Tags', [
            'sourceTable' => $articles,
            'targetTable' => $tags,
            'through' => $joint,
        ]);
        $joint->setEntityClass(ArticlesTag::class);

        $joint->getEventManager()->on('Model.afterDelete', function ($event, $entity) {
            $this->assertInstanceOf(ArticlesTag::class, $entity);
            $this->assertNotEmpty($entity->tag_id);
            $this->assertNotEmpty($entity->article_id);
        });

        $entity = $articles->get(1, ...['contain' => 'Tags']);
        $this->assertCount(2, $entity->tags);

        $assoc->replaceLinks($entity, []);
    }

    /**
     * Test that replaceLinks() loads junction records with the correct entity class
     */
    public function testReplaceLinksFinderCondition(): void
    {
        $this->setAppNamespace('TestApp');

        $joint = $this->getTableLocator()->get('ArticlesTags');
        $articles = $this->getTableLocator()->get('Articles');
        $tags = $this->getTableLocator()->get('Tags');

        $assoc = $tags->belongsToMany('Articles', [
            'sourceTable' => $tags,
            'targetTable' => $articles,
            'through' => $joint,
            'finder' => ['published' => ['title' => 'First Article']],
        ]);
        $entity = $tags->get(1, ...['contain' => 'Articles']);
        $this->assertCount(1, $entity->articles);

        $assoc->replaceLinks($entity, []);

        $fresh = $tags->get(1, ...['contain' => 'Articles']);
        $this->assertCount(0, $fresh->articles, 'Association should be empty');

        $other = $joint->find()->where(['tag_id' => 1])->toArray();
        $this->assertCount(1, $other, 'Non matching joint record should remain.');
        $this->assertSame(2, $other[0]->article_id);
    }

    /**
     * Test that replace links use loads junction records with a concrete
     * target table so that finders with contain will work.
     */
    public function testReplaceLinksFinderContain(): void
    {
        $this->setAppNamespace('TestApp');

        $joint = $this->getTableLocator()->get('ArticlesTags');
        $articles = $this->getTableLocator()->get('Articles');
        $tags = $this->getTableLocator()->get('Tags');

        $assoc = $tags->belongsToMany('Articles', [
            'sourceTable' => $tags,
            'targetTable' => $articles,
            'through' => $joint,
            'finder' => 'withAuthors',
        ]);
        $tag = $tags->get(1);
        $article = $articles->get(1);

        $assoc->replaceLinks($tag, [$article]);

        $fresh = $tags->get(1, ...['contain' => 'Articles']);
        $this->assertCount(1, $fresh->articles);
    }

    /**
     * Tests replaceLinks with failing domain rules and new link targets.
     */
    public function testReplaceLinkFailingDomainRules(): void
    {
        $articles = $this->getTableLocator()->get('Articles');
        $tags = $this->getTableLocator()->get('Tags');
        $tags->getEventManager()->on('Model.buildRules', function (EventInterface $event, RulesChecker $rules): void {
            $rules->add(function () {
                return false;
            }, 'rule', ['errorField' => 'name', 'message' => 'Bad data']);
        });

        $assoc = $articles->belongsToMany('Tags', [
            'sourceTable' => $articles,
            'targetTable' => $tags,
            'through' => $this->getTableLocator()->get('ArticlesTags'),
        ]);
        $entity = $articles->get(1, ...['contain' => 'Tags']);
        $originalCount = count($entity->tags);

        $tags = [
            new Entity(['name' => 'tag99', 'description' => 'Best tag']),
        ];
        $result = $assoc->replaceLinks($entity, $tags);
        $this->assertFalse($result, 'replace should have failed.');
        $this->assertNotEmpty($tags[0]->getErrors(), 'Bad entity should have errors.');

        $entity = $articles->get(1, ...['contain' => 'Tags']);
        $this->assertCount($originalCount, $entity->tags, 'Should not have changed.');
        $this->assertSame('tag1', $entity->tags[0]->name);
    }

    /**
     * Tests that replaceLinks will delete entities not present in the passed,
     * array, maintain those are already persisted and were passed and also
     * insert the rest.
     */
    public function testReplaceLinkBinaryUuid(): void
    {
        $items = $this->getTableLocator()->get('BinaryUuidItems');
        $tags = $this->getTableLocator()->get('BinaryUuidTags');

        $items->belongsToMany('BinaryUuidTags', [
            'sourceTable' => $items,
            'targetTable' => $tags,
        ]);
        $itemName = 'Item 1';
        $item = $items->find()->where(['BinaryUuidItems.name' => $itemName])->firstOrFail();
        $existingTag = $tags->find()->where(['BinaryUuidTags.name' => 'Defect'])->firstOrFail();

        // 1=existing, 2=new tag
        $item->binary_uuid_tags = [
            $existingTag,
            new Entity(['name' => 'net new']),
        ];
        $item->name = 'Updated';
        $items->saveOrFail($item);

        $refresh = $items->find()->where(['id' => $item->id])->contain('BinaryUuidTags')->firstOrFail();
        $this->assertCount(2, $refresh->binary_uuid_tags, 'Two tags should exist');

        $refresh->binary_uuid_tags = [$refresh->binary_uuid_tags[0]];
        $items->save($refresh);

        $refresh = $items->get($item->id, ...['contain' => 'BinaryUuidTags']);
        $this->assertCount(1, $refresh->binary_uuid_tags, 'One tag should remain');
    }

    public function testReplaceLinksComplexTypeForeignKey()
    {
        $articles = $this->fetchTable('CompositeKeyArticles');
        $tags = $this->fetchTable('Tags');

        $articles->belongsToMany('Tags', [
            'foreignKey' => ['author_id', 'created'],
        ]);

        $article = $articles->newEntity([
            'author_id' => 1,
            'body' => 'First post',
            'created' => new DateTime(),
        ]);
        $articles->saveOrFail($article);
        $tag1 = $tags->find()->where(['Tags.name' => 'tag1'])->firstOrFail();
        $tag2 = $tags->find()->where(['Tags.name' => 'tag2'])->firstOrFail();

        $findArticle = function ($article) use ($articles) {
            return $articles->find()
                ->where(['CompositeKeyArticles.author_id' => $article->author_id])
                ->contain('Tags')
                ->firstOrFail();
        };

        $article = $findArticle($article);
        $this->assertEmpty($article->tags);

        // Create the first link
        $article = $articles->patchEntity($article, ['tags' => ['_ids' => [$tag1->id]]]);
        $result = $articles->save($article, ['associated' => 'Tags']);
        $this->assertNotEmpty($result);
        $this->assertCount(1, $result->tags);
        $this->assertEquals($tag1->id, $result->tags[0]->id);

        // Add second tag. Reload tag objects so created fields have different
        // instances.
        $article = $findArticle($article);
        $article = $articles->patchEntity($article, ['tags' => ['_ids' => [$tag1->id, $tag2->id]]]);
        $result = $articles->save($article, ['associated' => 'Tags']);

        // Check in memory entity.
        $this->assertNotEmpty($result);
        $this->assertCount(2, $result->tags);
        $this->assertEquals('tag1', $result->tags[0]->name);
        $this->assertEquals('tag2', $result->tags[1]->name);

        // Reload to check persisted state.
        $result = $findArticle($article);
        $this->assertNotEmpty($result);
        $this->assertCount(2, $result->tags);
        $this->assertEquals('tag1', $result->tags[0]->name);
        $this->assertEquals('tag2', $result->tags[1]->name);
    }

    public function testReplaceLinksMissingKeyData()
    {
        $articles = $this->fetchTable('Articles');
        $tags = $this->fetchTable('Tags');

        $articles->belongsToMany('Tags');
        $article = $articles->find()->firstOrFail();

        $tag1 = $tags->find()->where(['Tags.name' => 'tag1'])->firstOrFail();
        $tag1->_joinData = new ArticlesTag(['tag_id' => 99]);

        $article->tags = [$tag1];
        $articles->saveOrFail($article, ['associated' => ['Tags']]);

        $this->assertCount(1, $article->tags);
    }

    /**
     * Provider for empty values
     *
     * @return array
     */
    public static function emptyProvider(): array
    {
        return [
            [''],
            [false],
            [null],
            [[]],
        ];
    }

    /**
     * Test that saving an empty set on create works.
     *
     * @dataProvider emptyProvider
     * @param mixed $value
     */
    public function testSaveAssociatedEmptySetSuccess($value): void
    {
        /** @var \Cake\ORM\Table|\PHPUnit\Framework\MockObject\MockBuilder $table */
        $table = $this->getMockBuilder(Table::class)
            ->getMock();
        $table->setSchema([]);
        /** @var \Cake\ORM\Association\BelongsToMany|\PHPUnit\Framework\MockObject\MockObject $assoc */
        $assoc = $this->getMockBuilder(BelongsToMany::class)
            ->onlyMethods(['_saveTarget', 'replaceLinks'])
            ->setConstructorArgs(['tags', ['sourceTable' => $table]])
            ->getMock();
        $entity = new Entity([
            'id' => 1,
            'tags' => $value,
        ], ['markNew' => true]);

        $assoc->setSaveStrategy(BelongsToMany::SAVE_REPLACE);
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
     * @param mixed $value
     */
    public function testSaveAssociatedEmptySetUpdateSuccess($value): void
    {
        /** @var \Cake\ORM\Table|\PHPUnit\Framework\MockObject\MockBuilder $table */
        $table = $this->getMockBuilder(Table::class)
            ->getMock();
        $table->setSchema([]);
        /** @var \Cake\ORM\Association\BelongsToMany|\PHPUnit\Framework\MockObject\MockObject $assoc */
        $assoc = $this->getMockBuilder(BelongsToMany::class)
            ->onlyMethods(['_saveTarget', 'replaceLinks'])
            ->setConstructorArgs(['tags', ['sourceTable' => $table]])
            ->getMock();
        $entity = new Entity([
            'id' => 1,
            'tags' => $value,
        ], ['markNew' => false]);

        $assoc->setSaveStrategy(BelongsToMany::SAVE_REPLACE);
        $assoc->expects($this->once())
            ->method('replaceLinks')
            ->with($entity, [])
            ->willReturn(true);

        $assoc->expects($this->never())
            ->method('_saveTarget');

        $this->assertSame($entity, $assoc->saveAssociated($entity));
    }

    /**
     * Tests saving with replace strategy returning true
     */
    public function testSaveAssociatedWithReplace(): void
    {
        /** @var \Cake\ORM\Table|\PHPUnit\Framework\MockObject\MockObject $table */
        $table = $this->getMockBuilder(Table::class)
            ->getMock();
        $table->setSchema([]);
        $assoc = $this->getMockBuilder(BelongsToMany::class)
            ->onlyMethods(['replaceLinks'])
            ->setConstructorArgs(['tags', ['sourceTable' => $table]])
            ->getMock();
        $entity = new Entity([
            'id' => 1,
            'tags' => [
                new Entity(['name' => 'foo']),
            ],
        ]);

        $options = ['foo' => 'bar'];
        $assoc->setSaveStrategy(BelongsToMany::SAVE_REPLACE);
        $assoc->expects($this->once())->method('replaceLinks')
            ->with($entity, $entity->tags, $options)
            ->willReturn(true);
        $this->assertSame($entity, $assoc->saveAssociated($entity, $options));
    }

    /**
     * Tests saving with replace strategy returning true
     */
    public function testSaveAssociatedWithReplaceReturnFalse(): void
    {
        /** @var \Cake\ORM\Table|\PHPUnit\Framework\MockObject\MockObject $table */
        $table = $this->getMockBuilder(Table::class)
            ->getMock();
        $table->setSchema([]);
        $assoc = $this->getMockBuilder(BelongsToMany::class)
            ->onlyMethods(['replaceLinks'])
            ->setConstructorArgs(['tags', ['sourceTable' => $table]])
            ->getMock();
        $entity = new Entity([
            'id' => 1,
            'tags' => [
                new Entity(['name' => 'foo']),
            ],
        ]);

        $options = ['foo' => 'bar'];
        $assoc->setSaveStrategy(BelongsToMany::SAVE_REPLACE);
        $assoc->expects($this->once())->method('replaceLinks')
            ->with($entity, $entity->tags, $options)
            ->willReturn(false);
        $this->assertFalse($assoc->saveAssociated($entity, $options));
    }

    /**
     * Tests that setTargetForeignKey() returns the correct configured value
     */
    public function testSetTargetForeignKey(): void
    {
        $assoc = new BelongsToMany('Test', [
            'sourceTable' => $this->article,
            'targetTable' => $this->tag,
        ]);
        $this->assertSame('tag_id', $assoc->getTargetForeignKey());
        $assoc->setTargetForeignKey('another_key');
        $this->assertSame('another_key', $assoc->getTargetForeignKey());

        $assoc = new BelongsToMany('Test', [
            'sourceTable' => $this->article,
            'targetTable' => $this->tag,
            'targetForeignKey' => 'foo',
        ]);
        $this->assertSame('foo', $assoc->getTargetForeignKey());
    }

    /**
     * Tests that custom foreignKeys are properly transmitted to involved associations
     * when they are customized
     */
    public function testJunctionWithCustomForeignKeys(): void
    {
        $assoc = new BelongsToMany('Test', [
            'sourceTable' => $this->article,
            'targetTable' => $this->tag,
            'foreignKey' => 'Art',
            'targetForeignKey' => 'Tag',
        ]);
        $junction = $assoc->junction();
        $this->assertSame('Art', $junction->getAssociation('Articles')->getForeignKey());
        $this->assertSame('Tag', $junction->getAssociation('Tags')->getForeignKey());

        $inverseRelation = $this->tag->getAssociation('Articles');
        $this->assertSame('Tag', $inverseRelation->getForeignKey());
        $this->assertSame('Art', $inverseRelation->getTargetForeignKey());
    }

    /**
     * Test that fallback class is used for join table even when fallback
     * class usage is turned off for table locator.
     */
    public function testFallbackClassForJunction(): void
    {
        $assoc = new BelongsToMany('Test', [
            'sourceTable' => $this->article,
            'targetTable' => $this->tag,
        ]);
        $assoc->setTableLocator((new TableLocator())->allowFallbackClass(false));
        $junction = $assoc->junction();
        $this->assertInstanceOf(Table::class, $junction);
    }

    /**
     * Test that fallback class is used for join table even when fallback
     * class usage is turned off for table locator.
     */
    public function testNoFallbackClassForThrough(): void
    {
        $this->expectException(MissingTableClassException::class);
        $this->expectExceptionMessage('Table class for alias `ArticlesTags` could not be found.');

        $assoc = new BelongsToMany('Test', [
            'sourceTable' => $this->article,
            'targetTable' => $this->tag,
            'through' => 'ArticlesTags',
        ]);
        $tableLocator = new TableLocator();
        $tableLocator->allowFallbackClass(false);
        $assoc->setTableLocator($tableLocator);
        $assoc->junction();
    }

    /**
     * Tests that property is being set using the constructor options.
     */
    public function testPropertyOption(): void
    {
        $config = ['propertyName' => 'thing_placeholder'];
        $association = new BelongsToMany('Thing', $config);
        $this->assertSame('thing_placeholder', $association->getProperty());
    }

    /**
     * Test that plugin names are omitted from property()
     */
    public function testPropertyNoPlugin(): void
    {
        $mock = $this->getMockBuilder(Table::class)
            ->disableOriginalConstructor()
            ->getMock();
        $config = [
            'sourceTable' => $this->article,
            'targetTable' => $mock,
        ];
        $association = new BelongsToMany('Contacts.Tags', $config);
        $this->assertSame('tags', $association->getProperty());
    }

    /**
     * Test that the generated associations are correct.
     */
    public function testGeneratedAssociations(): void
    {
        $articles = $this->getTableLocator()->get('Articles');
        $tags = $this->getTableLocator()->get('Tags');
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

        $tagAssoc = $articles->getAssociation('Tags');
        $this->assertNotEmpty($tagAssoc, 'btm should exist');
        $this->assertEquals($conditions, $tagAssoc->getConditions());
        $this->assertSame('target_foreign_key', $tagAssoc->getTargetForeignKey());
        $this->assertSame('foreign_key', $tagAssoc->getForeignKey());

        $jointAssoc = $articles->getAssociation('SpecialTags');
        $this->assertNotEmpty($jointAssoc, 'has many to junction should exist');
        $this->assertInstanceOf(HasMany::class, $jointAssoc);
        $this->assertSame('foreign_key', $jointAssoc->getForeignKey());

        $articleAssoc = $tags->getAssociation('Articles');
        $this->assertNotEmpty($articleAssoc, 'reverse btm should exist');
        $this->assertInstanceOf(BelongsToMany::class, $articleAssoc);
        $this->assertEquals($conditions, $articleAssoc->getConditions());
        $this->assertSame('foreign_key', $articleAssoc->getTargetForeignKey(), 'keys should swap');
        $this->assertSame('target_foreign_key', $articleAssoc->getForeignKey(), 'keys should swap');

        $jointAssoc = $tags->getAssociation('SpecialTags');
        $this->assertNotEmpty($jointAssoc, 'has many to junction should exist');
        $this->assertInstanceOf(HasMany::class, $jointAssoc);
        $this->assertSame('target_foreign_key', $jointAssoc->getForeignKey());
    }

    /**
     * Tests that eager loading requires association keys
     */
    public function testEagerLoadingRequiresPrimaryKey(): void
    {
        $this->expectException(DatabaseException::class);
        $this->expectExceptionMessage('The `tags` table does not define a primary key');
        $table = $this->getTableLocator()->get('Articles');
        $tags = $this->getTableLocator()->get('Tags');
        $tags->getSchema()->dropConstraint('primary');

        $table->belongsToMany('Tags');
        $table->find()->contain('Tags')->first();
    }

    /**
     * Tests that fetching belongsToMany association will not force
     * all fields being returned, but instead will honor the select() clause
     *
     * @see https://github.com/cakephp/cakephp/issues/7916
     */
    public function testEagerLoadingBelongsToManyLimitedFields(): void
    {
        $table = $this->getTableLocator()->get('Articles');
        $table->belongsToMany('Tags');
        $result = $table
            ->find()
            ->contain(['Tags' => function (SelectQuery $q) {
                return $q->select(['id']);
            }])
            ->first();

        $this->assertNotEmpty($result->tags[0]->id);
        $this->assertEmpty($result->tags[0]->name);

        $result = $table
            ->find()
            ->contain([
                'Tags' => [
                    'fields' => [
                        'Tags.name',
                    ],
                ],
            ])
            ->first();
        $this->assertNotEmpty($result->tags[0]->name);
        $this->assertEmpty($result->tags[0]->id);
    }

    /**
     * Tests that fetching belongsToMany association will retain autoFields(true) if it was used.
     *
     * @see https://github.com/cakephp/cakephp/issues/8052
     */
    public function testEagerLoadingBelongsToManyLimitedFieldsWithAutoFields(): void
    {
        $table = $this->getTableLocator()->get('Articles');
        $table->belongsToMany('Tags');
        $result = $table
            ->find()
            ->contain(['Tags' => function (SelectQuery $q) {
                return $q->select(['two' => $q->newExpr('1 + 1')])->enableAutoFields();
            }])
            ->first();

        $this->assertNotEmpty($result->tags[0]->two, 'Should have computed field');
        $this->assertNotEmpty($result->tags[0]->name, 'Should have standard field');
    }

    /**
     * Test that association proxy find() works with no join records
     */
    public function testAssociationProxyFindNoJoinRecords(): void
    {
        $table = $this->getTableLocator()->get('Articles');
        $table->belongsToMany('Tags');
        $table->Tags->junction()->deleteAll('1=1');

        $query = $table->Tags->find();
        $result = $query->toArray();
        $this->assertCount(3, $result);
    }

    /**
     * Test that association proxy find() applies joins when conditions are involved.
     */
    public function testAssociationProxyFindWithConditions(): void
    {
        $table = $this->getTableLocator()->get('Articles');
        $table->belongsToMany('Tags', [
            'conditions' => ['SpecialTags.highlighted' => true],
            'through' => 'SpecialTags',
        ]);
        $query = $table->Tags->find();
        $result = $query->toArray();
        $this->assertCount(1, $result);
        $this->assertSame(1, $result[0]->id);
    }

    /**
     * Test that association proxy find() applies complex conditions
     */
    public function testAssociationProxyFindWithComplexConditions(): void
    {
        $table = $this->getTableLocator()->get('Articles');
        $table->belongsToMany('Tags', [
            'conditions' => [
                'OR' => [
                    'SpecialTags.highlighted' => true,
                ],
            ],
            'through' => 'SpecialTags',
        ]);
        $query = $table->Tags->find();
        $result = $query->toArray();
        $this->assertCount(1, $result);
        $this->assertSame(1, $result[0]->id);
    }

    /**
     * Test that matching() works on belongsToMany associations.
     */
    public function testBelongsToManyAssociationWithArrayConditions(): void
    {
        $table = $this->getTableLocator()->get('Articles');
        $table->belongsToMany('Tags', [
            'conditions' => ['SpecialTags.highlighted' => true],
            'through' => 'SpecialTags',
        ]);
        $query = $table->find()->matching('Tags', function (SelectQuery $q) {
            return $q->where(['Tags.name' => 'tag1']);
        });
        $results = $query->toArray();
        $this->assertCount(1, $results);
        $this->assertNotEmpty($results[0]->_matchingData);
    }

    /**
     * Test that matching() works on belongsToMany associations.
     */
    public function testBelongsToManyAssociationWithExpressionConditions(): void
    {
        $table = $this->getTableLocator()->get('Articles');
        $table->belongsToMany('Tags', [
            'conditions' => [new QueryExpression("name LIKE 'tag%'")],
            'through' => 'SpecialTags',
        ]);
        $query = $table->find()->matching('Tags', function (SelectQuery $q) {
            return $q->where(['Tags.name' => 'tag1']);
        });
        $results = $query->toArray();
        $this->assertCount(1, $results);
        $this->assertNotEmpty($results[0]->_matchingData);
    }

    /**
     * Test that association proxy find() with matching resolves joins correctly
     */
    public function testAssociationProxyFindWithConditionsMatching(): void
    {
        $table = $this->getTableLocator()->get('Articles');
        $table->belongsToMany('Tags', [
            'conditions' => ['SpecialTags.highlighted' => true],
            'through' => 'SpecialTags',
        ]);
        $query = $table->Tags->find()->matching('Articles', function (SelectQuery $query) {
            return $query->where(['Articles.id' => 1]);
        });
        // The inner join on special_tags excludes the results.
        $this->assertSame(0, $query->count());
    }

    /**
     * Test custom binding key for target table association
     */
    public function testCustomTargetBindingKeyContain(): void
    {
        $this->getTableLocator()->get('ArticlesTags')
            ->belongsTo('SpecialTags', [
                'bindingKey' => 'tag_id',
                'foreignKey' => 'tag_id',
            ]);

        $table = $this->getTableLocator()->get('Articles');
        $table->belongsToMany('SpecialTags', [
            'through' => 'ArticlesTags',
            'targetForeignKey' => 'tag_id',
        ]);

        $results = $table->find()
            ->contain('SpecialTags', function ($query) {
                return $query->orderBy(['SpecialTags.tag_id']);
            })
            ->where(['id' => 2])
            ->toArray();

        $this->assertCount(1, $results);
        $this->assertCount(2, $results[0]->special_tags);

        $this->assertSame(2, $results[0]->special_tags[0]->id);
        $this->assertSame(1, $results[0]->special_tags[0]->tag_id);

        $this->assertSame(1, $results[0]->special_tags[1]->id);
        $this->assertSame(3, $results[0]->special_tags[1]->tag_id);
    }

    /**
     * Test custom binding key for target table association
     */
    public function testCustomTargetBindingKeyLink(): void
    {
        $this->getTableLocator()->get('ArticlesTags')
            ->belongsTo('SpecialTags', [
                'bindingKey' => 'tag_id',
                'foreignKey' => 'tag_id',
            ]);

        $table = $this->getTableLocator()->get('Articles');
        $table->belongsToMany('SpecialTags', [
            'through' => 'ArticlesTags',
            'targetForeignKey' => 'tag_id',
        ]);

        $specialTag = $table->SpecialTags->newEntity([
            'article_id' => 2,
            'tag_id' => 2,
        ]);
        $table->SpecialTags->save($specialTag);

        $article = $table->get(2);
        $this->assertTrue($table->SpecialTags->link($article, [$specialTag]));

        $results = $table->find()
            ->contain('SpecialTags')
            ->where(['id' => 2])
            ->toArray();

        $this->assertCount(1, $results);
        $this->assertCount(3, $results[0]->special_tags);
    }

    /**
     * Test custom binding key for target table association
     */
    public function testBindingKeyMatching(): void
    {
        $table = $this->getTableLocator()->get('Tags');
        $table->belongsToMany('Articles', [
            'through' => 'ArticlesTagsBindingKeys',
            'foreignKey' => 'tagname',
            'bindingKey' => 'name',
        ]);
        $query = $table->find()
            ->matching('Articles', function ($q) {
                return $q->where(['Articles.id >' => 0]);
            });
        $results = $query->all();

        // 4 records in the junction table.
        $this->assertCount(4, $results);
    }
}
