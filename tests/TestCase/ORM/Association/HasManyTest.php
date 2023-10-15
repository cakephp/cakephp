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

use Cake\Database\Driver\Sqlserver;
use Cake\Database\Expression\OrderByExpression;
use Cake\Database\Expression\QueryExpression;
use Cake\Database\Expression\TupleComparison;
use Cake\Database\IdentifierQuoter;
use Cake\Database\StatementInterface;
use Cake\Database\TypeMap;
use Cake\Datasource\ConnectionManager;
use Cake\ORM\Association;
use Cake\ORM\Association\HasMany;
use Cake\ORM\Entity;
use Cake\ORM\ResultSet;
use Cake\TestSuite\TestCase;
use Closure;
use InvalidArgumentException;
use function Cake\I18n\__;

/**
 * Tests HasMany class
 */
class HasManyTest extends TestCase
{
    /**
     * Fixtures
     *
     * @var array<string>
     */
    protected $fixtures = [
        'core.Comments',
        'core.Articles',
        'core.Tags',
        'core.Authors',
        'core.Users',
        'core.ArticlesTags',
    ];

    /**
     * @var \Cake\ORM\Table|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $author;

    /**
     * @var \Cake\ORM\Table|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $article;

    /**
     * @var \Cake\Database\TypeMap
     */
    protected $articlesTypeMap;

    /**
     * @var bool
     */
    protected $autoQuote;

    /**
     * Set up
     */
    public function setUp(): void
    {
        parent::setUp();
        $this->setAppNamespace('TestApp');

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
            ->onlyMethods(['find', 'deleteAll', 'delete'])
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
     */
    public function testSetForeignKey(): void
    {
        $assoc = new HasMany('Articles', [
            'sourceTable' => $this->author,
        ]);
        $this->assertSame('author_id', $assoc->getForeignKey());
        $this->assertSame($assoc, $assoc->setForeignKey('another_key'));
        $this->assertSame('another_key', $assoc->getForeignKey());
    }

    /**
     * Test that foreignKey generation ignores database names in target table.
     */
    public function testForeignKeyIgnoreDatabaseName(): void
    {
        $this->author->setTable('schema.authors');
        $assoc = new HasMany('Articles', [
            'sourceTable' => $this->author,
        ]);
        $this->assertSame('author_id', $assoc->getForeignKey());
    }

    /**
     * Tests that the association reports it can be joined
     */
    public function testCanBeJoined(): void
    {
        $assoc = new HasMany('Test');
        $this->assertFalse($assoc->canBeJoined());
    }

    /**
     * Tests setSort() method
     */
    public function testSetSort(): void
    {
        $assoc = new HasMany('Test');
        $this->assertNull($assoc->getSort());
        $assoc->setSort(['id' => 'ASC']);
        $this->assertEquals(['id' => 'ASC'], $assoc->getSort());
    }

    /**
     * Tests requiresKeys() method
     */
    public function testRequiresKeys(): void
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
     */
    public function testStrategyFailure(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid strategy "join" was provided');
        $assoc = new HasMany('Test');
        $assoc->setStrategy(HasMany::STRATEGY_JOIN);
    }

    /**
     * Test the eager loader method with no extra options
     */
    public function testEagerLoader(): void
    {
        $config = [
            'sourceTable' => $this->author,
            'targetTable' => $this->article,
            'strategy' => 'select',
        ];
        $association = new HasMany('Articles', $config);
        $query = $this->article->selectQuery();
        $this->article->method('find')
            ->with('all')
            ->will($this->returnValue($query));
        $keys = [1, 2, 3, 4];

        $callable = $association->eagerLoader(compact('keys', 'query'));
        $row = ['Authors__id' => 1];

        $result = $callable($row);
        $this->assertArrayHasKey('Articles', $result);
        $this->assertSame($row['Authors__id'], $result['Articles'][0]->author_id);
        $this->assertSame($row['Authors__id'], $result['Articles'][1]->author_id);

        $row = ['Authors__id' => 2];
        $result = $callable($row);
        $this->assertArrayNotHasKey('Articles', $result);

        $row = ['Authors__id' => 3];
        $result = $callable($row);
        $this->assertArrayHasKey('Articles', $result);
        $this->assertSame($row['Authors__id'], $result['Articles'][0]->author_id);

        $row = ['Authors__id' => 4];
        $result = $callable($row);
        $this->assertArrayNotHasKey('Articles', $result);
    }

    /**
     * Test the eager loader method with default query clauses
     */
    public function testEagerLoaderWithDefaults(): void
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

        $query = $this->article->selectQuery();
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
     */
    public function testEagerLoaderWithOverrides(): void
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
        $query = $this->article->selectQuery();
        $query->addDefaultTypes($this->article->Comments->getSource());

        $this->article->method('find')
            ->with('all')
            ->will($this->returnValue($query));

        $association->eagerLoader([
            'conditions' => ['Articles.id !=' => 3],
            'sort' => ['title' => 'DESC'],
            'fields' => ['id', 'title', 'author_id'],
            'contain' => ['Comments' => ['fields' => ['comment', 'article_id']]],
            'keys' => $keys,
            'query' => $query,
        ]);
        $expected = [
            'Articles__id' => 'Articles.id',
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
     */
    public function testEagerLoaderFieldsException(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('You are required to select the "Articles.author_id"');
        $config = [
            'sourceTable' => $this->author,
            'targetTable' => $this->article,
            'strategy' => 'select',
        ];
        $association = new HasMany('Articles', $config);
        $keys = [1, 2, 3, 4];
        $query = $this->article->selectQuery();
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
     */
    public function testEagerLoaderWithQueryBuilder(): void
    {
        $config = [
            'sourceTable' => $this->author,
            'targetTable' => $this->article,
            'strategy' => 'select',
        ];
        $association = new HasMany('Articles', $config);
        $keys = [1, 2, 3, 4];

        /** @var \Cake\ORM\Query $query */
        $query = $this->article->selectQuery();
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
     */
    public function testEagerLoaderMultipleKeys(): void
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
            ->onlyMethods(['all', 'andWhere', 'getRepository'])
            ->setConstructorArgs([ConnectionManager::get('test'), $this->article])
            ->getMock();
        $query->method('getRepository')
            ->will($this->returnValue($this->article));
        $this->article->method('find')
            ->with('all')
            ->will($this->returnValue($query));

        $stmt = $this->getMockBuilder(StatementInterface::class)->getMock();
        $results = new ResultSet($query, $stmt);

        $results->unserialize(serialize([
            ['id' => 1, 'title' => 'article 1', 'author_id' => 2, 'site_id' => 10],
            ['id' => 2, 'title' => 'article 2', 'author_id' => 1, 'site_id' => 20],
        ]));
        $query->method('all')
            ->will($this->returnValue($results));

        $tuple = new TupleComparison(
            ['Articles.author_id', 'Articles.site_id'],
            $keys,
            ['integer'],
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
     * Test that not selecting join keys fails with an error
     */
    public function testEagerloaderNoForeignKeys(): void
    {
        $authors = $this->getTableLocator()->get('Authors');
        $authors->hasMany('Articles');

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Unable to load `Articles` association. Ensure foreign key in `Authors`');
        $query = $authors->find()
            ->select(['Authors.name'])
            ->where(['Authors.id' => 1])
            ->contain('Articles');
        $query->first();
    }

    /**
     * Test cascading deletes.
     */
    public function testCascadeDelete(): void
    {
        $articles = $this->getTableLocator()->get('Articles');
        $config = [
            'dependent' => true,
            'sourceTable' => $this->author,
            'targetTable' => $articles,
            'conditions' => ['Articles.published' => 'Y'],
        ];
        $association = new HasMany('Articles', $config);

        $entity = new Entity(['id' => 1, 'name' => 'PHP']);
        $this->assertTrue($association->cascadeDelete($entity));

        $published = $articles
            ->find('published')
            ->where([
                'published' => 'Y',
                'author_id' => 1,
            ]);
        $this->assertCount(0, $published->all());
    }

    /**
     * Test cascading deletes with a finder
     */
    public function testCascadeDeleteFinder(): void
    {
        $articles = $this->getTableLocator()->get('Articles');
        $config = [
            'dependent' => true,
            'sourceTable' => $this->author,
            'targetTable' => $articles,
            'finder' => 'published',
        ];
        // Exclude one record from the association finder
        $articles->updateAll(
            ['published' => 'N'],
            ['author_id' => 1, 'title' => 'First Article']
        );
        $association = new HasMany('Articles', $config);

        $entity = new Entity(['id' => 1, 'name' => 'PHP']);
        $this->assertTrue($association->cascadeDelete($entity));

        $published = $articles->find('published')->where(['author_id' => 1]);
        $this->assertCount(0, $published->all(), 'Associated records should be removed');

        $all = $articles->find()->where(['author_id' => 1]);
        $this->assertCount(1, $all->all(), 'Record not in association finder should remain');
    }

    /**
     * Test cascading delete with has many.
     */
    public function testCascadeDeleteCallbacks(): void
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

        $query = $articles->find()->where(['author_id' => 1]);
        $this->assertSame(0, $query->count(), 'Cleared related rows');

        $query = $articles->find()->where(['author_id' => 3]);
        $this->assertSame(1, $query->count(), 'other records left behind');
    }

    /**
     * Test cascading delete with a rule preventing deletion
     */
    public function testCascadeDeleteCallbacksRuleFailure(): void
    {
        $articles = $this->getTableLocator()->get('Articles');
        $config = [
            'dependent' => true,
            'sourceTable' => $this->author,
            'targetTable' => $articles,
            'cascadeCallbacks' => true,
        ];
        $association = new HasMany('Articles', $config);
        $articles = $association->getTarget();
        $articles->getEventManager()->on('Model.buildRules', function ($event, $rules): void {
            $rules->addDelete(function () {
                return false;
            });
        });

        $author = new Entity(['id' => 1, 'name' => 'mark']);
        $this->assertFalse($association->cascadeDelete($author));
        $matching = $articles->find()
            ->where(['Articles.author_id' => $author->id])
            ->all();
        $this->assertGreaterThan(0, count($matching));
    }

    /**
     * Test that saveAssociated() ignores non entity values.
     */
    public function testSaveAssociatedOnlyEntities(): void
    {
        $mock = $this->getMockBuilder('Cake\ORM\Table')
            ->addMethods(['saveAssociated'])
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
     */
    public function testPropertyOption(): void
    {
        $config = ['propertyName' => 'thing_placeholder'];
        $association = new HasMany('Thing', $config);
        $this->assertSame('thing_placeholder', $association->getProperty());
    }

    /**
     * Tests propertyName is used during marshalling and validation
     */
    public function testPropertyOptionMarshalAndValidation(): void
    {
        $authors = $this->getTableLocator()->get('Authors');
        $authors->hasMany('Articles', [
            'propertyName' => 'blogs',
        ]);
        $authors->getValidator()
            ->requirePresence('blogs', true, 'blogs must be set');

        $data = [
            'name' => 'corey',
        ];
        $author = $authors->newEntity($data);
        $this->assertEmpty($author->blogs, 'No blogs set');
        $this->assertTrue($author->hasErrors(), 'Should have validation errors');
        $this->assertArrayHasKey('blogs', $author->getErrors());
    }

    /**
     * Test that plugin names are omitted from property()
     */
    public function testPropertyNoPlugin(): void
    {
        $mock = $this->getMockBuilder('Cake\ORM\Table')
            ->disableOriginalConstructor()
            ->getMock();
        $config = [
            'sourceTable' => $this->author,
            'targetTable' => $mock,
        ];
        $association = new HasMany('Contacts.Addresses', $config);
        $this->assertSame('addresses', $association->getProperty());
    }

    /**
     * Test that the ValueBinder is reset when using strategy = Association::STRATEGY_SUBQUERY
     */
    public function testValueBinderUpdateOnSubQueryStrategy(): void
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
     * Tests using subquery strategy when parent query
     * that contains limit without order.
     */
    public function testSubqueryWithLimit()
    {
        $Authors = $this->getTableLocator()->get('Authors');
        $Authors->hasMany('Articles', [
            'strategy' => Association::STRATEGY_SUBQUERY,
        ]);

        $query = $Authors->find();
        $result = $query
            ->contain('Articles')
            ->first();

        if (in_array($result->name, ['mariano', 'larry'])) {
            $this->assertNotEmpty($result->articles);
        } else {
            $this->assertEmpty($result->articles);
        }
    }

    /**
     * Tests using subquery strategy when parent query
     * that contains limit with order.
     */
    public function testSubqueryWithLimitAndOrder()
    {
        $this->skipIf(ConnectionManager::get('test')->getDriver() instanceof Sqlserver, 'Sql Server does not support ORDER BY on field not in GROUP BY');

        $Authors = $this->getTableLocator()->get('Authors');
        $Authors->hasMany('Articles', [
            'strategy' => Association::STRATEGY_SUBQUERY,
        ]);

        $query = $Authors->find();
        $result = $query
            ->contain('Articles')
            ->order(['name' => 'ASC'])
            ->limit(2)
            ->toArray();

        $this->assertCount(0, $result[0]->articles);
        $this->assertCount(1, $result[1]->articles);
    }

    /**
     * Assertion method for order by clause contents.
     *
     * @param array $expected The expected join clause.
     * @param \Cake\ORM\Query $query The query to check.
     */
    protected function assertJoin($expected, $query): void
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
     */
    protected function assertWhereClause($expected, $query): void
    {
        if ($this->autoQuote) {
            $quoter = new IdentifierQuoter($query->getConnection()->getDriver());
            $expected->traverse(Closure::fromCallable([$quoter, 'quoteExpression']));
        }
        $this->assertEquals($expected, $query->clause('where'));
    }

    /**
     * Assertion method for order by clause contents.
     *
     * @param \Cake\Database\QueryExpression $expected The expected where clause.
     * @param \Cake\ORM\Query $query The query to check.
     */
    protected function assertOrderClause($expected, $query): void
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
     */
    protected function assertSelectClause($expected, $query): void
    {
        if ($this->autoQuote) {
            $driver = $query->getConnection()->getDriver();
            foreach ($expected as $key => $value) {
                $expected[$driver->quoteIdentifier($key)] = $driver->quoteIdentifier($value);
                unset($expected[$key]);
            }
        }
        $this->assertEquals($expected, $query->clause('select'));
    }

    /**
     * Tests that unlinking calls the right methods
     */
    public function testUnlinkSuccess(): void
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
     */
    public function testUnlinkWithEmptyArray(): void
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
     */
    public function testLinkUsesSingleTransaction(): void
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
        $listenerAfterSave = function ($e, $entity, $options) use ($articles): void {
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
     */
    public function testSaveAssociatedNotEmptyNotIterable(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Could not save comments, it cannot be traversed');
        $articles = $this->getTableLocator()->get('Articles');
        $association = $articles->hasMany('Comments', [
            'saveStrategy' => HasMany::SAVE_APPEND,
        ]);

        $entity = $articles->newEmptyEntity();
        $entity->set('comments', 'oh noes');

        $association->saveAssociated($entity);
    }

    /**
     * Data provider for empty values.
     *
     * @return array
     */
    public function emptySetDataProvider(): array
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
     */
    public function testSaveAssociatedEmptySetWithAppendStrategyDoesNotAffectAssociatedRecordsOnCreate($value): void
    {
        $articles = $this->getTableLocator()->get('Articles');
        $association = $articles->hasMany('Comments', [
            'saveStrategy' => HasMany::SAVE_APPEND,
        ]);

        $comments = $association->find();
        $this->assertNotEmpty($comments);

        $entity = $articles->newEmptyEntity();
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
     */
    public function testSaveAssociatedEmptySetWithAppendStrategyDoesNotAffectAssociatedRecordsOnUpdate($value): void
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
     */
    public function testSaveAssociatedEmptySetWithReplaceStrategyDoesNotAffectAssociatedRecordsOnCreate($value): void
    {
        $articles = $this->getTableLocator()->get('Articles');
        $association = $articles->hasMany('Comments', [
            'saveStrategy' => HasMany::SAVE_REPLACE,
        ]);

        $comments = $association->find();
        $this->assertNotEmpty($comments);

        $entity = $articles->newEmptyEntity();
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
     */
    public function testSaveAssociatedEmptySetWithReplaceStrategyRemovesAssociatedRecordsOnUpdate($value): void
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
     * Test that the associated entities are not saved when there's any rule
     * that fail on them and the errors are correctly set on the original entity.
     */
    public function testSaveAssociatedWithFailedRuleOnAssociated(): void
    {
        $articles = $this->getTableLocator()->get('Articles');
        $articles->hasMany('Comments');
        $comments = $this->getTableLocator()->get('Comments');
        $comments->belongsTo('Users');
        $rules = $comments->rulesChecker();
        $rules->add($rules->existsIn('user_id', 'Users'));
        $article = $articles->newEntity([
            'title' => 'Bakeries are sky rocketing',
            'body' => 'All because of cake',
            'comments' => [
                [
                    'user_id' => 1,
                    'comment' => 'That is true!',
                ],
                [
                    'user_id' => 999, // This rule will fail because the user doesn't exist
                    'comment' => 'Of course',
                ],
            ],
        ], ['associated' => ['Comments']]);
        $this->assertFalse($article->hasErrors());
        $this->assertFalse($articles->save($article, ['associated' => ['Comments']]));
        $this->assertTrue($article->hasErrors());
        $this->assertFalse($article->comments[0]->hasErrors());
        $this->assertTrue($article->comments[1]->hasErrors());
        $this->assertNotEmpty($article->comments[1]->getErrors());
        $expected = [
            'user_id' => [
                '_existsIn' => __('This value does not exist'),
            ],
        ];
        $this->assertEquals($expected, $article->comments[1]->getErrors());
    }

    /**
     * Tests that providing an invalid strategy throws an exception
     */
    public function testInvalidSaveStrategy(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $articles = $this->getTableLocator()->get('Articles');

        $association = $articles->hasMany('Comments');
        $association->setSaveStrategy('anotherThing');
    }

    /**
     * Tests saveStrategy
     */
    public function testSetSaveStrategy(): void
    {
        $articles = $this->getTableLocator()->get('Articles');

        $association = $articles->hasMany('Comments');
        $this->assertSame($association, $association->setSaveStrategy(HasMany::SAVE_REPLACE));
        $this->assertSame(HasMany::SAVE_REPLACE, $association->getSaveStrategy());
    }

    /**
     * Test that save works with replace saveStrategy and are not deleted once they are not null
     */
    public function testSaveReplaceSaveStrategy(): void
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
        $this->assertSame($sizeArticles, $authors->Articles->find('all')->where(['author_id' => $entity['id']])->count());

        $articleId = $entity->articles[0]->id;
        unset($entity->articles[0]);
        $entity->setDirty('articles', true);

        $authors->save($entity, ['associated' => ['Articles']]);

        $this->assertSame($sizeArticles - 1, $authors->Articles->find('all')->where(['author_id' => $entity['id']])->count());
        $this->assertTrue($authors->Articles->exists(['id' => $articleId]));
    }

    /**
     * Test that save works with replace saveStrategy conditions
     */
    public function testSaveReplaceSaveStrategyClosureConditions(): void
    {
        $authors = $this->getTableLocator()->get('Authors');
        $authors->hasMany('Articles')
            ->setDependent(true)
            ->setSaveStrategy('replace')
            ->setConditions(function () {
                return ['published' => 'Y'];
            });

        $entity = $authors->newEntity([
            'name' => 'mylux',
            'articles' => [
                ['title' => 'Not matching conditions', 'body' => '', 'published' => 'N'],
                ['title' => 'Random Post', 'body' => 'The cake is nice', 'published' => 'Y'],
                ['title' => 'Another Random Post', 'body' => 'The cake is yummy', 'published' => 'Y'],
                ['title' => 'One more random post', 'body' => 'The cake is forever', 'published' => 'Y'],
            ],
        ], ['associated' => ['Articles']]);

        $entity = $authors->save($entity, ['associated' => ['Articles']]);
        $sizeArticles = count($entity->articles);
        // Should be one fewer because of conditions.
        $this->assertSame($sizeArticles - 1, $authors->Articles->find('all')->where(['author_id' => $entity['id']])->count());

        $articleId = $entity->articles[0]->id;
        unset($entity->articles[0], $entity->articles[1]);
        $entity->setDirty('articles', true);

        $authors->save($entity, ['associated' => ['Articles']]);

        $this->assertSame($sizeArticles - 2, $authors->Articles->find('all')->where(['author_id' => $entity['id']])->count());

        // Should still exist because it doesn't match the association conditions.
        $articles = $this->getTableLocator()->get('Articles');
        $this->assertTrue($articles->exists(['id' => $articleId]));
    }

    /**
     * Test that save works with replace saveStrategy, replacing the already persisted entities even if no new entities are passed
     */
    public function testSaveReplaceSaveStrategyNotAdding(): void
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
     */
    public function testSaveAppendSaveStrategy(): void
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

        $this->assertSame($sizeArticles, $authors->Articles->find('all')->where(['author_id' => $entity['id']])->count());

        $articleId = $entity->articles[0]->id;
        unset($entity->articles[0]);
        $entity->setDirty('articles', true);

        $authors->save($entity, ['associated' => ['Articles']]);

        $this->assertSame($sizeArticles, $authors->Articles->find('all')->where(['author_id' => $entity['id']])->count());
        $this->assertTrue($authors->Articles->exists(['id' => $articleId]));
    }

    /**
     * Test that save has append as the default save strategy
     */
    public function testSaveDefaultSaveStrategy(): void
    {
        $authors = $this->getTableLocator()->get('Authors');
        $authors->hasMany('Articles', ['saveStrategy' => HasMany::SAVE_APPEND]);
        $this->assertSame(HasMany::SAVE_APPEND, $authors->getAssociation('articles')->getSaveStrategy());
    }

    /**
     * Test that the associated entities are unlinked and deleted when they are dependent
     */
    public function testSaveReplaceSaveStrategyDependent(): void
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
        $this->assertSame($sizeArticles, $authors->Articles->find('all')->where(['author_id' => $entity['id']])->count());

        $articleId = $entity->articles[0]->id;
        unset($entity->articles[0]);
        $entity->setDirty('articles', true);

        $authors->save($entity, ['associated' => ['Articles']]);

        $this->assertSame($sizeArticles - 1, $authors->Articles->find('all')->where(['author_id' => $entity['id']])->count());
        $this->assertFalse($authors->Articles->exists(['id' => $articleId]));
    }

    /**
     * Test that the associated entities are unlinked and deleted when they are dependent
     * when associated entities array is indexed by string keys
     */
    public function testSaveReplaceSaveStrategyDependentWithStringKeys(): void
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
     */
    public function testSaveReplaceSaveStrategyDependentWithConditions(): void
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

        // Remove an article from the association finder scope
        $articles->updateAll(['published' => 'N'], ['author_id' => 1, 'title' => 'Third Article']);

        $entity = $authors->get(1, ['contain' => ['Articles']]);
        $data = [
            'name' => 'updated',
            'articles' => [
                ['title' => 'New First', 'body' => 'New First', 'published' => 'Y'],
            ],
        ];
        $entity = $authors->patchEntity($entity, $data, ['associated' => ['Articles']]);
        $entity = $authors->save($entity, ['associated' => ['Articles']]);

        // Should only have one article left as we 'replaced' the others.
        $this->assertCount(1, $entity->articles);

        // No additional records in db.
        $this->assertCount(
            1,
            $authors->Articles->find()->where(['author_id' => 1])->toArray()
        );

        $others = $articles->find('all')
            ->where(['Articles.author_id' => 1, 'published' => 'N'])
            ->orderAsc('title')
            ->toArray();
        $this->assertCount(
            1,
            $others,
            'Record not matching association condition should stay'
        );
        $this->assertSame('Third Article', $others[0]->title);
    }

    /**
     * Test that the associated entities are unlinked and deleted when they have a not nullable foreign key
     */
    public function testSaveReplaceSaveStrategyNotNullable(): void
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

        $this->assertSame($sizeComments, $articles->Comments->find('all')->where(['article_id' => $article->id])->count());
        $this->assertTrue($articles->Comments->exists(['id' => $commentId]));

        unset($article->comments[0]);
        $article->setDirty('comments', true);
        $article = $articles->save($article, ['associated' => ['Comments']]);

        $this->assertSame($sizeComments - 1, $articles->Comments->find('all')->where(['article_id' => $article->id])->count());
        $this->assertFalse($articles->Comments->exists(['id' => $commentId]));
    }

    /**
     * Test that the associated entities are unlinked and deleted when they have a not nullable foreign key
     */
    public function testSaveReplaceSaveStrategyAdding(): void
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

        $this->assertSame($sizeComments, $articles->Comments->find('all')->where(['article_id' => $article->id])->count());
        $this->assertTrue($articles->Comments->exists(['id' => $commentId]));

        unset($article->comments[0]);
        $article->comments[] = $articles->Comments->newEntity([
            'user_id' => 1,
            'comment' => 'new comment',
        ]);

        $article->setDirty('comments', true);
        $article = $articles->save($article, ['associated' => ['Comments']]);

        $this->assertSame($sizeComments, $articles->Comments->find('all')->where(['article_id' => $article->id])->count());
        $this->assertFalse($articles->Comments->exists(['id' => $commentId]));
        $this->assertTrue($articles->Comments->exists(['comment' => 'new comment', 'article_id' => $articleId]));
    }

    /**
     * Tests that dependent, non-cascading deletes are using the association
     * conditions for deleting associated records.
     */
    public function testHasManyNonCascadingUnlinkDeleteUsesAssociationConditions(): void
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

        $this->assertSame(3, $Comments->getTarget()->find()->where(['Comments.article_id' => $article->get('id')])->count());

        unset($article->comments[1]);
        $article->setDirty('comments', true);

        $article = $Articles->save($article);
        $this->assertNotEmpty($article);

        // Given the association condition of `'Comments.published' => 'Y'`,
        // it is expected that only one of the three linked comments are
        // actually being deleted, as only one of them matches the
        // association condition.
        $this->assertSame(2, $Comments->getTarget()->find()->where(['Comments.article_id' => $article->get('id')])->count());
    }

    /**
     * Tests that non-dependent, non-cascading deletes are using the association
     * conditions for updating associated records.
     */
    public function testHasManyNonDependentNonCascadingUnlinkUpdateUsesAssociationConditions(): void
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

        $this->assertSame(3, $Articles->getTarget()->find()->where(['Articles.author_id' => $author->get('id')])->count());

        $article2 = $author->articles[1];
        unset($author->articles[1]);
        $author->setDirty('articles', true);

        $author = $Authors->save($author);
        $this->assertNotEmpty($author);

        // Given the association condition of `'Articles.published' => 'Y'`,
        // it is expected that only one of the three linked articles are
        // actually being unlinked (nulled), as only one of them matches the
        // association condition.
        $this->assertSame(2, $Articles->getTarget()->find()->where(['Articles.author_id' => $author->get('id')])->count());
        $this->assertNull($Articles->get($article2->get('id'))->get('author_id'));
        $this->assertEquals($author->get('id'), $Articles->get($article3->get('id'))->get('author_id'));
    }
}
