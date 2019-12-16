<?php
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

use Cake\Database\Expression\OrderByExpression;
use Cake\Database\Expression\QueryExpression;
use Cake\Database\Expression\TupleComparison;
use Cake\Database\IdentifierQuoter;
use Cake\Database\TypeMap;
use Cake\Datasource\ConnectionManager;
use Cake\ORM\Association;
use Cake\ORM\Association\HasMany;
use Cake\ORM\Entity;
use Cake\TestSuite\TestCase;

/**
 * Tests HasMany class
 */
class HasManyTest extends TestCase
{
    /**
     * Fixtures
     *
     * @var array
     */
    public $fixtures = [
        'core.Comments',
        'core.Articles',
        'core.Authors',
    ];

    /**
     * Set up
     *
     * @return void
     */
    public function setUp()
    {
        parent::setUp();
        $this->author = $this->getTableLocator()->get('Authors', [
            'schema' => [
                'id' => ['type' => 'integer'],
                'name' => ['type' => 'string'],
                '_constraints' => [
                    'primary' => ['type' => 'primary', 'columns' => ['id']],
                ],
            ],
        ]);
        $connection = ConnectionManager::get('test');
        $this->article = $this->getMockBuilder('Cake\ORM\Table')
            ->setMethods(['find', 'deleteAll', 'delete'])
            ->setConstructorArgs([['alias' => 'Articles', 'table' => 'articles', 'connection' => $connection]])
            ->getMock();
        $this->article->setSchema([
            'id' => ['type' => 'integer'],
            'title' => ['type' => 'string'],
            'author_id' => ['type' => 'integer'],
            '_constraints' => [
                'primary' => ['type' => 'primary', 'columns' => ['id']],
            ],
        ]);

        $this->articlesTypeMap = new TypeMap([
            'Articles.id' => 'integer',
            'id' => 'integer',
            'Articles.title' => 'string',
            'title' => 'string',
            'Articles.author_id' => 'integer',
            'author_id' => 'integer',
            'Articles__id' => 'integer',
            'Articles__title' => 'string',
            'Articles__author_id' => 'integer',
        ]);
        $this->autoQuote = $connection->getDriver()->isAutoQuotingEnabled();
    }

    /**
     * Tests that foreignKey() returns the correct configured value
     *
     * @return void
     */
    public function testSetForeignKey()
    {
        $assoc = new HasMany('Articles', [
            'sourceTable' => $this->author,
        ]);
        $this->assertEquals('author_id', $assoc->getForeignKey());
        $this->assertSame($assoc, $assoc->setForeignKey('another_key'));
        $this->assertEquals('another_key', $assoc->getForeignKey());
    }

    /**
     * Tests that foreignKey() returns the correct configured value
     *
     * @group deprecated
     * @return void
     */
    public function testForeignKey()
    {
        $this->deprecated(function () {
            $assoc = new HasMany('Articles', [
                'sourceTable' => $this->author,
            ]);
            $this->assertEquals('author_id', $assoc->foreignKey());
            $this->assertEquals('another_key', $assoc->foreignKey('another_key'));
            $this->assertEquals('another_key', $assoc->foreignKey());
        });
    }

    /**
     * Test that foreignKey generation ignores database names in target table.
     *
     * @return void
     */
    public function testForeignKeyIgnoreDatabaseName()
    {
        $this->author->setTable('schema.authors');
        $assoc = new HasMany('Articles', [
            'sourceTable' => $this->author,
        ]);
        $this->assertEquals('author_id', $assoc->getForeignKey());
    }

    /**
     * Tests that the association reports it can be joined
     *
     * @return void
     */
    public function testCanBeJoined()
    {
        $assoc = new HasMany('Test');
        $this->assertFalse($assoc->canBeJoined());
    }

    /**
     * Tests sort() method
     *
     * @group deprecated
     * @return void
     */
    public function testSort()
    {
        $this->deprecated(function () {
            $assoc = new HasMany('Test');
            $this->assertNull($assoc->sort());
            $assoc->sort(['id' => 'ASC']);
            $this->assertEquals(['id' => 'ASC'], $assoc->sort());
        });
    }

    /**
     * Tests setSort() method
     *
     * @return void
     */
    public function testSetSort()
    {
        $assoc = new HasMany('Test');
        $this->assertNull($assoc->getSort());
        $assoc->setSort(['id' => 'ASC']);
        $this->assertEquals(['id' => 'ASC'], $assoc->getSort());
    }

    /**
     * Tests requiresKeys() method
     *
     * @return void
     */
    public function testRequiresKeys()
    {
        $assoc = new HasMany('Test');
        $this->assertTrue($assoc->requiresKeys());

        $assoc->setStrategy(HasMany::STRATEGY_SUBQUERY);
        $this->assertFalse($assoc->requiresKeys());

        $assoc->setStrategy(HasMany::STRATEGY_SELECT);
        $this->assertTrue($assoc->requiresKeys());
    }

    /**
     * Tests that HasMany can't use the join strategy
     *
     * @return void
     */
    public function testStrategyFailure()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid strategy "join" was provided');
        $assoc = new HasMany('Test');
        $assoc->setStrategy(HasMany::STRATEGY_JOIN);
    }

    /**
     * Test the eager loader method with no extra options
     *
     * @return void
     */
    public function testEagerLoader()
    {
        $config = [
            'sourceTable' => $this->author,
            'targetTable' => $this->article,
            'strategy' => 'select',
        ];
        $association = new HasMany('Articles', $config);
        $query = $this->article->query();
        $this->article->method('find')
            ->with('all')
            ->will($this->returnValue($query));
        $keys = [1, 2, 3, 4];

        $callable = $association->eagerLoader(compact('keys', 'query'));
        $row = ['Authors__id' => 1];

        $result = $callable($row);
        $this->assertArrayHasKey('Articles', $result);
        $this->assertEquals($row['Authors__id'], $result['Articles'][0]->author_id);
        $this->assertEquals($row['Authors__id'], $result['Articles'][1]->author_id);

        $row = ['Authors__id' => 2];
        $result = $callable($row);
        $this->assertArrayNotHasKey('Articles', $result);

        $row = ['Authors__id' => 3];
        $result = $callable($row);
        $this->assertArrayHasKey('Articles', $result);
        $this->assertEquals($row['Authors__id'], $result['Articles'][0]->author_id);

        $row = ['Authors__id' => 4];
        $result = $callable($row);
        $this->assertArrayNotHasKey('Articles', $result);
    }

    /**
     * Test the eager loader method with default query clauses
     *
     * @return void
     */
    public function testEagerLoaderWithDefaults()
    {
        $config = [
            'sourceTable' => $this->author,
            'targetTable' => $this->article,
            'conditions' => ['Articles.published' => 'Y'],
            'sort' => ['id' => 'ASC'],
            'strategy' => 'select',
        ];
        $association = new HasMany('Articles', $config);
        $keys = [1, 2, 3, 4];

        $query = $this->article->query();
        $this->article->method('find')
            ->with('all')
            ->will($this->returnValue($query));

        $association->eagerLoader(compact('keys', 'query'));

        $expected = new QueryExpression(
            ['Articles.published' => 'Y', 'Articles.author_id IN' => $keys],
            $this->articlesTypeMap
        );
        $this->assertWhereClause($expected, $query);

        $expected = new OrderByExpression(['id' => 'ASC']);
        $this->assertOrderClause($expected, $query);
    }

    /**
     * Test the eager loader method with overridden query clauses
     *
     * @return void
     */
    public function testEagerLoaderWithOverrides()
    {
        $config = [
            'sourceTable' => $this->author,
            'targetTable' => $this->article,
            'conditions' => ['Articles.published' => 'Y'],
            'sort' => ['id' => 'ASC'],
            'strategy' => 'select',
        ];
        $this->article->hasMany('Comments');

        $association = new HasMany('Articles', $config);
        $keys = [1, 2, 3, 4];

        /** @var \Cake\ORM\Query $query */
        $query = $this->article->query();
        $query->addDefaultTypes($this->article->Comments->getSource());

        $this->article->method('find')
            ->with('all')
            ->will($this->returnValue($query));

        $association->eagerLoader([
            'conditions' => ['Articles.id !=' => 3],
            'sort' => ['title' => 'DESC'],
            'fields' => ['title', 'author_id'],
            'contain' => ['Comments' => ['fields' => ['comment', 'article_id']]],
            'keys' => $keys,
            'query' => $query,
        ]);
        $expected = [
            'Articles__title' => 'Articles.title',
            'Articles__author_id' => 'Articles.author_id',
        ];
        $this->assertSelectClause($expected, $query);

        $expected = new QueryExpression(
            [
                'Articles.published' => 'Y',
                'Articles.id !=' => 3,
                'Articles.author_id IN' => $keys,
            ],
            $query->getTypeMap()
        );
        $this->assertWhereClause($expected, $query);

        $expected = new OrderByExpression(['title' => 'DESC']);
        $this->assertOrderClause($expected, $query);
        $this->assertArrayHasKey('Comments', $query->getContain());
    }

    /**
     * Test that failing to add the foreignKey to the list of fields will throw an
     * exception
     *
     * @return void
     */
    public function testEagerLoaderFieldsException()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('You are required to select the "Articles.author_id"');
        $config = [
            'sourceTable' => $this->author,
            'targetTable' => $this->article,
            'strategy' => 'select',
        ];
        $association = new HasMany('Articles', $config);
        $keys = [1, 2, 3, 4];
        $query = $this->article->query();
        $this->article->method('find')
            ->with('all')
            ->will($this->returnValue($query));

        $association->eagerLoader([
            'fields' => ['id', 'title'],
            'keys' => $keys,
            'query' => $query,
        ]);
    }

    /**
     * Tests that eager loader accepts a queryBuilder option
     *
     * @return void
     */
    public function testEagerLoaderWithQueryBuilder()
    {
        $config = [
            'sourceTable' => $this->author,
            'targetTable' => $this->article,
            'strategy' => 'select',
        ];
        $association = new HasMany('Articles', $config);
        $keys = [1, 2, 3, 4];

        /** @var \Cake\ORM\Query $query */
        $query = $this->article->query();
        $this->article->method('find')
            ->with('all')
            ->will($this->returnValue($query));

        $queryBuilder = function ($query) {
            return $query->select(['author_id'])->join('comments')->where(['comments.id' => 1]);
        };
        $association->eagerLoader(compact('keys', 'query', 'queryBuilder'));

        $expected = [
            'Articles__author_id' => 'Articles.author_id',
        ];
        $this->assertSelectClause($expected, $query);

        $expected = [
            [
                'type' => 'INNER',
                'alias' => null,
                'table' => 'comments',
                'conditions' => new QueryExpression([], $query->getTypeMap()),
            ],
        ];
        $this->assertJoin($expected, $query);

        $expected = new QueryExpression(
            [
                'Articles.author_id IN' => $keys,
                'comments.id' => 1,
            ],
            $query->getTypeMap()
        );
        $this->assertWhereClause($expected, $query);
    }

    /**
     * Test the eager loader method with no extra options
     *
     * @return void
     */
    public function testEagerLoaderMultipleKeys()
    {
        $config = [
            'sourceTable' => $this->author,
            'targetTable' => $this->article,
            'strategy' => 'select',
            'foreignKey' => ['author_id', 'site_id'],
        ];

        $this->author->setPrimaryKey(['id', 'site_id']);
        $association = new HasMany('Articles', $config);
        $keys = [[1, 10], [2, 20], [3, 30], [4, 40]];
        $query = $this->getMockBuilder('Cake\ORM\Query')
            ->setMethods(['all', 'andWhere'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->article->method('find')
            ->with('all')
            ->will($this->returnValue($query));

        $results = [
            ['id' => 1, 'title' => 'article 1', 'author_id' => 2, 'site_id' => 10],
            ['id' => 2, 'title' => 'article 2', 'author_id' => 1, 'site_id' => 20],
        ];
        $query->method('all')
            ->will($this->returnValue($results));

        $tuple = new TupleComparison(
            ['Articles.author_id', 'Articles.site_id'],
            $keys,
            [],
            'IN'
        );
        $query->expects($this->once())->method('andWhere')
            ->with($tuple)
            ->will($this->returnSelf());

        $callable = $association->eagerLoader(compact('keys', 'query'));
        $row = ['Authors__id' => 2, 'Authors__site_id' => 10, 'username' => 'author 1'];
        $result = $callable($row);
        $row['Articles'] = [
            ['id' => 1, 'title' => 'article 1', 'author_id' => 2, 'site_id' => 10],
        ];
        $this->assertEquals($row, $result);

        $row = ['Authors__id' => 1, 'username' => 'author 2', 'Authors__site_id' => 20];
        $result = $callable($row);
        $row['Articles'] = [
            ['id' => 2, 'title' => 'article 2', 'author_id' => 1, 'site_id' => 20],
        ];
        $this->assertEquals($row, $result);
    }

    /**
     * Test cascading deletes.
     *
     * @return void
     */
    public function testCascadeDelete()
    {
        $config = [
            'dependent' => true,
            'sourceTable' => $this->author,
            'targetTable' => $this->article,
            'conditions' => ['Articles.is_active' => true],
        ];
        $association = new HasMany('Articles', $config);

        $this->article->expects($this->once())
            ->method('deleteAll')
            ->with([
                'Articles.is_active' => true,
                'Articles.author_id' => 1,
            ]);

        $entity = new Entity(['id' => 1, 'name' => 'PHP']);
        $association->cascadeDelete($entity);
    }

    /**
     * Test cascading delete with has many.
     *
     * @return void
     */
    public function testCascadeDeleteCallbacks()
    {
        $articles = $this->getTableLocator()->get('Articles');
        $config = [
            'dependent' => true,
            'sourceTable' => $this->author,
            'targetTable' => $articles,
            'conditions' => ['Articles.published' => 'Y'],
            'cascadeCallbacks' => true,
        ];
        $association = new HasMany('Articles', $config);

        $author = new Entity(['id' => 1, 'name' => 'mark']);
        $this->assertTrue($association->cascadeDelete($author));

        $query = $articles->query()->where(['author_id' => 1]);
        $this->assertEquals(0, $query->count(), 'Cleared related rows');

        $query = $articles->query()->where(['author_id' => 3]);
        $this->assertEquals(1, $query->count(), 'other records left behind');
    }

    /**
     * Test that saveAssociated() ignores non entity values.
     *
     * @return void
     */
    public function testSaveAssociatedOnlyEntities()
    {
        $mock = $this->getMockBuilder('Cake\ORM\Table')
            ->setMethods(['saveAssociated'])
            ->disableOriginalConstructor()
            ->getMock();
        $config = [
            'sourceTable' => $this->author,
            'targetTable' => $mock,
        ];

        $entity = new Entity([
            'username' => 'Mark',
            'email' => 'mark@example.com',
            'articles' => [
                ['title' => 'First Post'],
                new Entity(['title' => 'Second Post']),
            ],
        ]);

        $mock->expects($this->never())
            ->method('saveAssociated');

        $association = new HasMany('Articles', $config);
        $association->saveAssociated($entity);
    }

    /**
     * Tests that property is being set using the constructor options.
     *
     * @return void
     */
    public function testPropertyOption()
    {
        $config = ['propertyName' => 'thing_placeholder'];
        $association = new hasMany('Thing', $config);
        $this->assertEquals('thing_placeholder', $association->getProperty());
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
            'sourceTable' => $this->author,
            'targetTable' => $mock,
        ];
        $association = new HasMany('Contacts.Addresses', $config);
        $this->assertEquals('addresses', $association->getProperty());
    }

    /**
     * Test that the ValueBinder is reset when using strategy = Association::STRATEGY_SUBQUERY
     *
     * @return void
     */
    public function testValueBinderUpdateOnSubQueryStrategy()
    {
        $Authors = $this->getTableLocator()->get('Authors');
        $Authors->hasMany('Articles', [
            'strategy' => Association::STRATEGY_SUBQUERY,
        ]);

        $query = $Authors->find();
        $authorsAndArticles = $query
            ->select([
                'id',
                'slug' => $query->func()->concat([
                    '---',
                    'name' => 'identifier',
                ]),
            ])
            ->contain('Articles')
            ->where(['name' => 'mariano'])
            ->first();

        $this->assertCount(2, $authorsAndArticles->get('articles'));
    }

    /**
     * Assertion method for order by clause contents.
     *
     * @param array $expected The expected join clause.
     * @param \Cake\ORM\Query $query The query to check.
     * @return void
     */
    protected function assertJoin($expected, $query)
    {
        if ($this->autoQuote) {
            $driver = $query->getConnection()->getDriver();
            $quoter = new IdentifierQuoter($driver);
            foreach ($expected as &$join) {
                $join['table'] = $driver->quoteIdentifier($join['table']);
                if ($join['conditions']) {
                    $quoter->quoteExpression($join['conditions']);
                }
            }
        }
        $this->assertEquals($expected, array_values($query->clause('join')));
    }

    /**
     * Assertion method for where clause contents.
     *
     * @param \Cake\Database\QueryExpression $expected The expected where clause.
     * @param \Cake\ORM\Query $query The query to check.
     * @return void
     */
    protected function assertWhereClause($expected, $query)
    {
        if ($this->autoQuote) {
            $quoter = new IdentifierQuoter($query->getConnection()->getDriver());
            $expected->traverse([$quoter, 'quoteExpression']);
        }
        $this->assertEquals($expected, $query->clause('where'));
    }

    /**
     * Assertion method for order by clause contents.
     *
     * @param \Cake\Database\QueryExpression $expected The expected where clause.
     * @param \Cake\ORM\Query $query The query to check.
     * @return void
     */
    protected function assertOrderClause($expected, $query)
    {
        if ($this->autoQuote) {
            $quoter = new IdentifierQuoter($query->getConnection()->getDriver());
            $quoter->quoteExpression($expected);
        }
        $this->assertEquals($expected, $query->clause('order'));
    }

    /**
     * Assertion method for select clause contents.
     *
     * @param array $expected Array of expected fields.
     * @param \Cake\ORM\Query $query The query to check.
     * @return void
     */
    protected function assertSelectClause($expected, $query)
    {
        if ($this->autoQuote) {
            $connection = $query->getConnection();
            foreach ($expected as $key => $value) {
                $expected[$connection->quoteIdentifier($key)] = $connection->quoteIdentifier($value);
                unset($expected[$key]);
            }
        }
        $this->assertEquals($expected, $query->clause('select'));
    }

    /**
     * Tests that unlinking calls the right methods
     *
     * @return void
     */
    public function testUnlinkSuccess()
    {
        $articles = $this->getTableLocator()->get('Articles');
        $assoc = $this->author->hasMany('Articles', [
            'sourceTable' => $this->author,
            'targetTable' => $articles,
        ]);

        $entity = $this->author->get(1, ['contain' => 'Articles']);
        $initial = $entity->articles;
        $this->assertCount(2, $initial);

        $assoc->unlink($entity, $entity->articles);
        $this->assertEmpty($entity->get('articles'), 'Property should be empty');

        $new = $this->author->get(2, ['contain' => 'Articles']);
        $this->assertCount(0, $new->articles, 'DB should be clean');
        $this->assertSame(4, $this->author->find()->count(), 'Authors should still exist');
        $this->assertSame(3, $articles->find()->count(), 'Articles should still exist');
    }

    /**
     * Tests that unlink with an empty array does nothing
     *
     * @return void
     */
    public function testUnlinkWithEmptyArray()
    {
        $articles = $this->getTableLocator()->get('Articles');
        $assoc = $this->author->hasMany('Articles', [
            'sourceTable' => $this->author,
            'targetTable' => $articles,
        ]);

        $entity = $this->author->get(1, ['contain' => 'Articles']);
        $initial = $entity->articles;
        $this->assertCount(2, $initial);

        $assoc->unlink($entity, []);

        $new = $this->author->get(1, ['contain' => 'Articles']);
        $this->assertCount(2, $new->articles, 'Articles should remain linked');
        $this->assertSame(4, $this->author->find()->count(), 'Authors should still exist');
        $this->assertSame(3, $articles->find()->count(), 'Articles should still exist');
    }

    /**
     * Tests that link only uses a single database transaction
     *
     * @return void
     */
    public function testLinkUsesSingleTransaction()
    {
        $articles = $this->getTableLocator()->get('Articles');
        $assoc = $this->author->hasMany('Articles', [
            'sourceTable' => $this->author,
            'targetTable' => $articles,
        ]);

        // Ensure author in fixture has zero associated articles
        $entity = $this->author->get(2, ['contain' => 'Articles']);
        $initial = $entity->articles;
        $this->assertCount(0, $initial);

        // Ensure that after each model is saved, we are still within a transaction.
        $listenerAfterSave = function ($e, $entity, $options) use ($articles) {
            $this->assertTrue(
                $articles->getConnection()->inTransaction(),
                'Multiple transactions used to save associated models.'
            );
        };
        $articles->getEventManager()->on('Model.afterSave', $listenerAfterSave);

        $options = ['atomic' => false];
        $assoc->link($entity, $articles->find('all')->toArray(), $options);

        // Ensure that link was successful.
        $new = $this->author->get(2, ['contain' => 'Articles']);
        $this->assertCount(3, $new->articles);
    }

    /**
     * Test that saveAssociated() fails on non-empty, non-iterable value
     *
     * @return void
     */
    public function testSaveAssociatedNotEmptyNotIterable()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Could not save comments, it cannot be traversed');
        $articles = $this->getTableLocator()->get('Articles');
        $association = $articles->hasMany('Comments', [
            'saveStrategy' => HasMany::SAVE_APPEND,
        ]);

        $entity = $articles->newEntity();
        $entity->set('comments', 'oh noes');

        $association->saveAssociated($entity);
    }

    /**
     * Data provider for empty values.
     *
     * @return array
     */
    public function emptySetDataProvider()
    {
        return [
            [''],
            [false],
            [null],
            [[]],
        ];
    }

    /**
     * Test that saving empty sets with the `append` strategy does not
     * affect the associated records for not yet persisted parent entities.
     *
     * @dataProvider emptySetDataProvider
     * @param mixed $value Empty value.
     * @return void
     */
    public function testSaveAssociatedEmptySetWithAppendStrategyDoesNotAffectAssociatedRecordsOnCreate($value)
    {
        $articles = $this->getTableLocator()->get('Articles');
        $association = $articles->hasMany('Comments', [
            'saveStrategy' => HasMany::SAVE_APPEND,
        ]);

        $comments = $association->find();
        $this->assertNotEmpty($comments);

        $entity = $articles->newEntity();
        $entity->set('comments', $value);

        $this->assertSame($entity, $association->saveAssociated($entity));
        $this->assertEquals($value, $entity->get('comments'));
        $this->assertEquals($comments, $association->find());
    }

    /**
     * Test that saving empty sets with the `append` strategy does not
     * affect the associated records for already persisted parent entities.
     *
     * @dataProvider emptySetDataProvider
     * @param mixed $value Empty value.
     * @return void
     */
    public function testSaveAssociatedEmptySetWithAppendStrategyDoesNotAffectAssociatedRecordsOnUpdate($value)
    {
        $articles = $this->getTableLocator()->get('Articles');
        $association = $articles->hasMany('Comments', [
            'saveStrategy' => HasMany::SAVE_APPEND,
        ]);

        $entity = $articles->get(1, [
            'contain' => ['Comments'],
        ]);
        $comments = $entity->get('comments');
        $this->assertNotEmpty($comments);

        $entity->set('comments', $value);
        $this->assertSame($entity, $association->saveAssociated($entity));
        $this->assertEquals($value, $entity->get('comments'));

        $entity = $articles->get(1, [
            'contain' => ['Comments'],
        ]);
        $this->assertEquals($comments, $entity->get('comments'));
    }

    /**
     * Test that saving empty sets with the `replace` strategy does not
     * affect the associated records for not yet persisted parent entities.
     *
     * @dataProvider emptySetDataProvider
     * @param mixed $value Empty value.
     * @return void
     */
    public function testSaveAssociatedEmptySetWithReplaceStrategyDoesNotAffectAssociatedRecordsOnCreate($value)
    {
        $articles = $this->getTableLocator()->get('Articles');
        $association = $articles->hasMany('Comments', [
            'saveStrategy' => HasMany::SAVE_REPLACE,
        ]);

        $comments = $association->find();
        $this->assertNotEmpty($comments);

        $entity = $articles->newEntity();
        $entity->set('comments', $value);

        $this->assertSame($entity, $association->saveAssociated($entity));
        $this->assertEquals($value, $entity->get('comments'));
        $this->assertEquals($comments, $association->find());
    }

    /**
     * Test that saving empty sets with the `replace` strategy does remove
     * the associated records for already persisted parent entities.
     *
     * @dataProvider emptySetDataProvider
     * @param mixed $value Empty value.
     * @return void
     */
    public function testSaveAssociatedEmptySetWithReplaceStrategyRemovesAssociatedRecordsOnUpdate($value)
    {
        $articles = $this->getTableLocator()->get('Articles');
        $association = $articles->hasMany('Comments', [
            'saveStrategy' => HasMany::SAVE_REPLACE,
        ]);

        $entity = $articles->get(1, [
            'contain' => ['Comments'],
        ]);
        $comments = $entity->get('comments');
        $this->assertNotEmpty($comments);

        $entity->set('comments', $value);
        $this->assertSame($entity, $association->saveAssociated($entity));
        $this->assertEquals([], $entity->get('comments'));

        $entity = $articles->get(1, [
            'contain' => ['Comments'],
        ]);
        $this->assertEmpty($entity->get('comments'));
    }

    /**
     * Tests that providing an invalid strategy throws an exception
     *
     * @return void
     */
    public function testInvalidSaveStrategy()
    {
        $this->expectException(\InvalidArgumentException::class);
        $articles = $this->getTableLocator()->get('Articles');

        $association = $articles->hasMany('Comments');
        $association->setSaveStrategy('anotherThing');
    }

    /**
     * Tests saveStrategy
     *
     * @return void
     */
    public function testSetSaveStrategy()
    {
        $articles = $this->getTableLocator()->get('Articles');

        $association = $articles->hasMany('Comments');
        $this->assertSame($association, $association->setSaveStrategy(HasMany::SAVE_REPLACE));
        $this->assertSame(HasMany::SAVE_REPLACE, $association->getSaveStrategy());
    }

    /**
     * Tests saveStrategy
     *
     * @group deprecated
     * @return void
     */
    public function testSaveStrategy()
    {
        $this->deprecated(function () {
            $articles = $this->getTableLocator()->get('Articles');

            $association = $articles->hasMany('Comments');
            $this->assertSame(HasMany::SAVE_REPLACE, $association->saveStrategy(HasMany::SAVE_REPLACE));
            $this->assertSame(HasMany::SAVE_REPLACE, $association->saveStrategy());
        });
    }

    /**
     * Test that save works with replace saveStrategy and are not deleted once they are not null
     *
     * @return void
     */
    public function testSaveReplaceSaveStrategy()
    {
        $authors = $this->getTableLocator()->get('Authors');
        $authors->hasMany('Articles', ['saveStrategy' => HasMany::SAVE_REPLACE]);

        $entity = $authors->newEntity([
            'name' => 'mylux',
            'articles' => [
                ['title' => 'One Random Post', 'body' => 'The cake is not a lie'],
                ['title' => 'Another Random Post', 'body' => 'The cake is nice'],
                ['title' => 'One more random post', 'body' => 'The cake is forever'],
            ],
        ], ['associated' => ['Articles']]);

        $entity = $authors->save($entity, ['associated' => ['Articles']]);
        $sizeArticles = count($entity->articles);
        $this->assertEquals($sizeArticles, $authors->Articles->find('all')->where(['author_id' => $entity['id']])->count());

        $articleId = $entity->articles[0]->id;
        unset($entity->articles[0]);
        $entity->setDirty('articles', true);

        $authors->save($entity, ['associated' => ['Articles']]);

        $this->assertEquals($sizeArticles - 1, $authors->Articles->find('all')->where(['author_id' => $entity['id']])->count());
        $this->assertTrue($authors->Articles->exists(['id' => $articleId]));
    }

    /**
     * Test that save works with replace saveStrategy, replacing the already persisted entities even if no new entities are passed
     *
     * @return void
     */
    public function testSaveReplaceSaveStrategyNotAdding()
    {
        $authors = $this->getTableLocator()->get('Authors');
        $authors->hasMany('Articles', ['saveStrategy' => 'replace']);

        $entity = $authors->newEntity([
            'name' => 'mylux',
            'articles' => [
                ['title' => 'One Random Post', 'body' => 'The cake is not a lie'],
                ['title' => 'Another Random Post', 'body' => 'The cake is nice'],
                ['title' => 'One more random post', 'body' => 'The cake is forever'],
            ],
        ], ['associated' => ['Articles']]);

        $entity = $authors->save($entity, ['associated' => ['Articles']]);
        $sizeArticles = count($entity->articles);
        $this->assertCount($sizeArticles, $authors->Articles->find('all')->where(['author_id' => $entity['id']]));

        $entity->set('articles', []);

        $entity = $authors->save($entity, ['associated' => ['Articles']]);

        $this->assertCount(0, $authors->Articles->find('all')->where(['author_id' => $entity['id']]));
    }

    /**
     * Test that save works with append saveStrategy not deleting or setting null anything
     *
     * @return void
     */
    public function testSaveAppendSaveStrategy()
    {
        $authors = $this->getTableLocator()->get('Authors');
        $authors->hasMany('Articles', ['saveStrategy' => 'append']);

        $entity = $authors->newEntity([
            'name' => 'mylux',
            'articles' => [
                ['title' => 'One Random Post', 'body' => 'The cake is not a lie'],
                ['title' => 'Another Random Post', 'body' => 'The cake is nice'],
                ['title' => 'One more random post', 'body' => 'The cake is forever'],
            ],
        ], ['associated' => ['Articles']]);

        $entity = $authors->save($entity, ['associated' => ['Articles']]);
        $sizeArticles = count($entity->articles);

        $this->assertEquals($sizeArticles, $authors->Articles->find('all')->where(['author_id' => $entity['id']])->count());

        $articleId = $entity->articles[0]->id;
        unset($entity->articles[0]);
        $entity->setDirty('articles', true);

        $authors->save($entity, ['associated' => ['Articles']]);

        $this->assertEquals($sizeArticles, $authors->Articles->find('all')->where(['author_id' => $entity['id']])->count());
        $this->assertTrue($authors->Articles->exists(['id' => $articleId]));
    }

    /**
     * Test that save has append as the default save strategy
     *
     * @return void
     */
    public function testSaveDefaultSaveStrategy()
    {
        $authors = $this->getTableLocator()->get('Authors');
        $authors->hasMany('Articles', ['saveStrategy' => HasMany::SAVE_APPEND]);
        $this->assertEquals(HasMany::SAVE_APPEND, $authors->getAssociation('articles')->getSaveStrategy());
    }

    /**
     * Test that the associated entities are unlinked and deleted when they are dependent
     *
     * @return void
     */
    public function testSaveReplaceSaveStrategyDependent()
    {
        $authors = $this->getTableLocator()->get('Authors');
        $authors->hasMany('Articles', ['saveStrategy' => HasMany::SAVE_REPLACE, 'dependent' => true]);

        $entity = $authors->newEntity([
            'name' => 'mylux',
            'articles' => [
                ['title' => 'One Random Post', 'body' => 'The cake is not a lie'],
                ['title' => 'Another Random Post', 'body' => 'The cake is nice'],
                ['title' => 'One more random post', 'body' => 'The cake is forever'],
            ],
        ], ['associated' => ['Articles']]);

        $entity = $authors->save($entity, ['associated' => ['Articles']]);
        $sizeArticles = count($entity->articles);
        $this->assertEquals($sizeArticles, $authors->Articles->find('all')->where(['author_id' => $entity['id']])->count());

        $articleId = $entity->articles[0]->id;
        unset($entity->articles[0]);
        $entity->setDirty('articles', true);

        $authors->save($entity, ['associated' => ['Articles']]);

        $this->assertEquals($sizeArticles - 1, $authors->Articles->find('all')->where(['author_id' => $entity['id']])->count());
        $this->assertFalse($authors->Articles->exists(['id' => $articleId]));
    }

    /**
     * Test that the associated entities are unlinked and deleted when they are dependent
     * when associated entities array is indexed by string keys
     *
     * @return void
     */
    public function testSaveReplaceSaveStrategyDependentWithStringKeys()
    {
        $authors = $this->getTableLocator()->get('Authors');
        $authors->hasMany('Articles', ['saveStrategy' => HasMany::SAVE_REPLACE, 'dependent' => true]);

        $entity = $authors->newEntity([
            'name' => 'mylux',
            'articles' => [
                ['title' => 'One Random Post', 'body' => 'The cake is not a lie'],
                ['title' => 'Another Random Post', 'body' => 'The cake is nice'],
                ['title' => 'One more random post', 'body' => 'The cake is forever'],
            ],
        ], ['associated' => ['Articles']]);

        $entity = $authors->saveOrFail($entity, ['associated' => ['Articles']]);
        $sizeArticles = count($entity->articles);
        $this->assertSame($sizeArticles, $authors->Articles->find('all')->where(['author_id' => $entity['id']])->count());

        $articleId = $entity->articles[0]->id;
        $entity->articles = [
            'one' => $entity->articles[1],
            'two' => $entity->articles[2],
        ];

        $authors->saveOrFail($entity, ['associated' => ['Articles']]);

        $this->assertSame($sizeArticles - 1, $authors->Articles->find('all')->where(['author_id' => $entity['id']])->count());
        $this->assertFalse($authors->Articles->exists(['id' => $articleId]));
    }

    /**
     * Test that the associated entities are unlinked and deleted when they are dependent
     *
     * In the future this should change and apply the finder.
     *
     * @return void
     */
    public function testSaveReplaceSaveStrategyDependentWithConditions()
    {
        $this->getTableLocator()->clear();
        $this->setAppNamespace('TestApp');

        $authors = $this->getTableLocator()->get('Authors');
        $authors->hasMany('Articles', [
            'finder' => 'published',
            'saveStrategy' => HasMany::SAVE_REPLACE,
            'dependent' => true,
        ]);
        $articles = $authors->Articles->getTarget();
        $articles->updateAll(['published' => 'N'], ['author_id' => 1, 'title' => 'Third Article']);

        $entity = $authors->get(1, ['contain' => ['Articles']]);
        $data = [
            'name' => 'updated',
            'articles' => [
                ['title' => 'First Article', 'body' => 'New First', 'published' => 'N'],
            ],
        ];
        $entity = $authors->patchEntity($entity, $data, ['associated' => ['Articles']]);
        $entity = $authors->save($entity, ['associated' => ['Articles']]);

        // Should only have one article left as we 'replaced' the others.
        $this->assertCount(1, $entity->articles);
        $this->assertCount(1, $authors->Articles->find()->toArray());

        $others = $articles->find('all')
            ->where(['Articles.author_id' => 1])
            ->orderAsc('title')
            ->toArray();
        $this->assertCount(
            1,
            $others,
            'Record not matching condition should stay. But does not'
        );
        $this->assertSame('First Article', $others[0]->title);
    }

    /**
     * Test that the associated entities are unlinked and deleted when they have a not nullable foreign key
     *
     * @return void
     */
    public function testSaveReplaceSaveStrategyNotNullable()
    {
        $articles = $this->getTableLocator()->get('Articles');
        $articles->hasMany('Comments', ['saveStrategy' => HasMany::SAVE_REPLACE]);

        $article = $articles->newEntity([
            'title' => 'Bakeries are sky rocketing',
            'body' => 'All because of cake',
            'comments' => [
                [
                    'user_id' => 1,
                    'comment' => 'That is true!',
                ],
                [
                    'user_id' => 2,
                    'comment' => 'Of course',
                ],
            ],
        ], ['associated' => ['Comments']]);

        $article = $articles->save($article, ['associated' => ['Comments']]);
        $commentId = $article->comments[0]->id;
        $sizeComments = count($article->comments);

        $this->assertEquals($sizeComments, $articles->Comments->find('all')->where(['article_id' => $article->id])->count());
        $this->assertTrue($articles->Comments->exists(['id' => $commentId]));

        unset($article->comments[0]);
        $article->setDirty('comments', true);
        $article = $articles->save($article, ['associated' => ['Comments']]);

        $this->assertEquals($sizeComments - 1, $articles->Comments->find('all')->where(['article_id' => $article->id])->count());
        $this->assertFalse($articles->Comments->exists(['id' => $commentId]));
    }

    /**
     * Test that the associated entities are unlinked and deleted when they have a not nullable foreign key
     *
     * @return void
     */
    public function testSaveReplaceSaveStrategyAdding()
    {
        $articles = $this->getTableLocator()->get('Articles');
        $articles->hasMany('Comments', ['saveStrategy' => HasMany::SAVE_REPLACE]);

        $article = $articles->newEntity([
            'title' => 'Bakeries are sky rocketing',
            'body' => 'All because of cake',
            'comments' => [
                [
                    'user_id' => 1,
                    'comment' => 'That is true!',
                ],
                [
                    'user_id' => 2,
                    'comment' => 'Of course',
                ],
            ],
        ], ['associated' => ['Comments']]);

        $article = $articles->save($article, ['associated' => ['Comments']]);
        $commentId = $article->comments[0]->id;
        $sizeComments = count($article->comments);
        $articleId = $article->id;

        $this->assertEquals($sizeComments, $articles->Comments->find('all')->where(['article_id' => $article->id])->count());
        $this->assertTrue($articles->Comments->exists(['id' => $commentId]));

        unset($article->comments[0]);
        $article->comments[] = $articles->Comments->newEntity([
            'user_id' => 1,
            'comment' => 'new comment',
        ]);

        $article->setDirty('comments', true);
        $article = $articles->save($article, ['associated' => ['Comments']]);

        $this->assertEquals($sizeComments, $articles->Comments->find('all')->where(['article_id' => $article->id])->count());
        $this->assertFalse($articles->Comments->exists(['id' => $commentId]));
        $this->assertTrue($articles->Comments->exists(['comment' => 'new comment', 'article_id' => $articleId]));
    }

    /**
     * Tests that dependent, non-cascading deletes are using the association
     * conditions for deleting associated records.
     *
     * @return void
     */
    public function testHasManyNonCascadingUnlinkDeleteUsesAssociationConditions()
    {
        $Articles = $this->getTableLocator()->get('Articles');
        $Comments = $Articles->hasMany('Comments', [
            'dependent' => true,
            'cascadeCallbacks' => false,
            'saveStrategy' => HasMany::SAVE_REPLACE,
            'conditions' => [
                'Comments.published' => 'Y',
            ],
        ]);

        $article = $Articles->newEntity([
            'title' => 'Title',
            'body' => 'Body',
            'comments' => [
                [
                    'user_id' => 1,
                    'comment' => 'First comment',
                    'published' => 'Y',
                ],
                [
                    'user_id' => 1,
                    'comment' => 'Second comment',
                    'published' => 'Y',
                ],
            ],
        ]);
        $article = $Articles->save($article);
        $this->assertNotEmpty($article);

        $comment3 = $Comments->getTarget()->newEntity([
            'article_id' => $article->get('id'),
            'user_id' => 1,
            'comment' => 'Third comment',
            'published' => 'N',
        ]);
        $comment3 = $Comments->getTarget()->save($comment3);
        $this->assertNotEmpty($comment3);

        $this->assertEquals(3, $Comments->getTarget()->find()->where(['Comments.article_id' => $article->get('id')])->count());

        unset($article->comments[1]);
        $article->setDirty('comments', true);

        $article = $Articles->save($article);
        $this->assertNotEmpty($article);

        // Given the association condition of `'Comments.published' => 'Y'`,
        // it is expected that only one of the three linked comments are
        // actually being deleted, as only one of them matches the
        // association condition.
        $this->assertEquals(2, $Comments->getTarget()->find()->where(['Comments.article_id' => $article->get('id')])->count());
    }

    /**
     * Tests that non-dependent, non-cascading deletes are using the association
     * conditions for updating associated records.
     *
     * @return void
     */
    public function testHasManyNonDependentNonCascadingUnlinkUpdateUsesAssociationConditions()
    {
        $Authors = $this->getTableLocator()->get('Authors');
        $Authors->associations()->removeAll();
        $Articles = $Authors->hasMany('Articles', [
            'dependent' => false,
            'cascadeCallbacks' => false,
            'saveStrategy' => HasMany::SAVE_REPLACE,
            'conditions' => [
                'Articles.published' => 'Y',
            ],
        ]);

        $author = $Authors->newEntity([
            'name' => 'Name',
            'articles' => [
                [
                    'title' => 'First article',
                    'body' => 'First article',
                    'published' => 'Y',
                ],
                [
                    'title' => 'Second article',
                    'body' => 'Second article',
                    'published' => 'Y',
                ],
            ],
        ]);
        $author = $Authors->save($author);
        $this->assertNotEmpty($author);

        $article3 = $Articles->getTarget()->newEntity([
            'author_id' => $author->get('id'),
            'title' => 'Third article',
            'body' => 'Third article',
            'published' => 'N',
        ]);
        $article3 = $Articles->getTarget()->save($article3);
        $this->assertNotEmpty($article3);

        $this->assertEquals(3, $Articles->getTarget()->find()->where(['Articles.author_id' => $author->get('id')])->count());

        $article2 = $author->articles[1];
        unset($author->articles[1]);
        $author->setDirty('articles', true);

        $author = $Authors->save($author);
        $this->assertNotEmpty($author);

        // Given the association condition of `'Articles.published' => 'Y'`,
        // it is expected that only one of the three linked articles are
        // actually being unlinked (nulled), as only one of them matches the
        // association condition.
        $this->assertEquals(2, $Articles->getTarget()->find()->where(['Articles.author_id' => $author->get('id')])->count());
        $this->assertNull($Articles->get($article2->get('id'))->get('author_id'));
        $this->assertEquals($author->get('id'), $Articles->get($article3->get('id'))->get('author_id'));
    }
}
