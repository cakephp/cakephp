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

use Cake\Database\Expression\OrderByExpression;
use Cake\Database\Expression\QueryExpression;
use Cake\Database\Expression\TupleComparison;
use Cake\Database\IdentifierQuoter;
use Cake\Database\TypeMap;
use Cake\Datasource\ConnectionManager;
use Cake\ORM\Association;
use Cake\ORM\Association\HasMany;
use Cake\ORM\Entity;
use Cake\ORM\TableRegistry;
use Cake\TestSuite\TestCase;

/**
 * Tests HasMany class
 *
 */
class HasManyTest extends TestCase
{
    /**
     * Fixtures
     *
     * @var array
     */
    public $fixtures = ['core.comments', 'core.articles', 'core.authors'];

    /**
     * Set up
     *
     * @return void
     */
    public function setUp()
    {
        parent::setUp();
        $this->author = TableRegistry::get('Authors', [
            'schema' => [
                'id' => ['type' => 'integer'],
                'name' => ['type' => 'string'],
                '_constraints' => [
                    'primary' => ['type' => 'primary', 'columns' => ['id']]
                ]
            ]
        ]);
        $connection = ConnectionManager::get('test');
        $this->article = $this->getMockBuilder('Cake\ORM\Table')
            ->setMethods(['find', 'deleteAll', 'delete'])
            ->setConstructorArgs([['alias' => 'Articles', 'table' => 'articles', 'connection' => $connection]])
            ->getMock();
        $this->article->schema([
            'id' => ['type' => 'integer'],
            'title' => ['type' => 'string'],
            'author_id' => ['type' => 'integer'],
            '_constraints' => [
                'primary' => ['type' => 'primary', 'columns' => ['id']]
            ]
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
        $this->autoQuote = $connection->driver()->autoQuoting();
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
     * Test that foreignKey generation ignores database names in target table.
     *
     * @return void
     */
    public function testForeignKey()
    {
        $this->author->table('schema.authors');
        $assoc = new HasMany('Articles', [
            'sourceTable' => $this->author
        ]);
        $this->assertEquals('author_id', $assoc->foreignKey());
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
     * @return void
     */
    public function testSort()
    {
        $assoc = new HasMany('Test');
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
        $assoc = new HasMany('Test');
        $this->assertTrue($assoc->requiresKeys());

        $assoc->strategy(HasMany::STRATEGY_SUBQUERY);
        $this->assertFalse($assoc->requiresKeys());

        $assoc->strategy(HasMany::STRATEGY_SELECT);
        $this->assertTrue($assoc->requiresKeys());
    }

    /**
     * Tests that HasMany can't use the join strategy
     *
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Invalid strategy "join" was provided
     * @return void
     */
    public function testStrategyFailure()
    {
        $assoc = new HasMany('Test');
        $assoc->strategy(HasMany::STRATEGY_JOIN);
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
            'strategy' => 'select'
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
            'strategy' => 'select'
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
            'strategy' => 'select'
        ];
        $this->article->hasMany('Comments');

        $association = new HasMany('Articles', $config);
        $keys = [1, 2, 3, 4];
        $query = $this->article->query();
        $query->addDefaultTypes($this->article->Comments->source());

        $this->article->method('find')
            ->with('all')
            ->will($this->returnValue($query));

        $association->eagerLoader([
            'conditions' => ['Articles.id !=' => 3],
            'sort' => ['title' => 'DESC'],
            'fields' => ['title', 'author_id'],
            'contain' => ['Comments' => ['fields' => ['comment']]],
            'keys' => $keys,
            'query' => $query
        ]);
        $expected = [
            'Articles__title' => 'Articles.title',
            'Articles__author_id' => 'Articles.author_id'
        ];
        $this->assertSelectClause($expected, $query);

        $expected = new QueryExpression(
            [
                'Articles.published' => 'Y',
                'Articles.id !=' => 3,
                'Articles.author_id IN' => $keys
            ],
            $query->typeMap()
        );
        $this->assertWhereClause($expected, $query);

        $expected = new OrderByExpression(['title' => 'DESC']);
        $this->assertOrderClause($expected, $query);
        $this->assertArrayHasKey('Comments', $query->contain());
    }

    /**
     * Test that failing to add the foreignKey to the list of fields will throw an
     * exception
     *
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage You are required to select the "Articles.author_id"
     * @return void
     */
    public function testEagerLoaderFieldsException()
    {
        $config = [
            'sourceTable' => $this->author,
            'targetTable' => $this->article,
            'strategy' => 'select'
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
            'query' => $query
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
            'strategy' => 'select'
        ];
        $association = new HasMany('Articles', $config);
        $keys = [1, 2, 3, 4];
        $query = $this->article->query();
        $this->article->method('find')
            ->with('all')
            ->will($this->returnValue($query));

        $queryBuilder = function ($query) {
            return $query->select(['author_id'])->join('comments')->where(['comments.id' => 1]);
        };
        $association->eagerLoader(compact('keys', 'query', 'queryBuilder'));

        $expected = [
            'Articles__author_id' => 'Articles.author_id'
        ];
        $this->assertSelectClause($expected, $query);

        $expected = [
            [
                'type' => 'INNER',
                'alias' => null,
                'table' => 'comments',
                'conditions' => new QueryExpression([], $query->typeMap()),
            ]
        ];
        $this->assertJoin($expected, $query);

        $expected = new QueryExpression(
            [
                'Articles.author_id IN' => $keys,
                'comments.id' => 1,
            ],
            $query->typeMap()
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
            'foreignKey' => ['author_id', 'site_id']
        ];

        $this->author->primaryKey(['id', 'site_id']);
        $association = new HasMany('Articles', $config);
        $keys = [[1, 10], [2, 20], [3, 30], [4, 40]];
        $query = $this->getMockBuilder('Cake\ORM\Query')
            ->setMethods(['all', 'andWhere'])
            ->setConstructorArgs([null, null])
            ->getMock();
        $this->article->method('find')
            ->with('all')
            ->will($this->returnValue($query));

        $results = [
            ['id' => 1, 'title' => 'article 1', 'author_id' => 2, 'site_id' => 10],
            ['id' => 2, 'title' => 'article 2', 'author_id' => 1, 'site_id' => 20]
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
            ['id' => 1, 'title' => 'article 1', 'author_id' => 2, 'site_id' => 10]
        ];
        $this->assertEquals($row, $result);

        $row = ['Authors__id' => 1, 'username' => 'author 2', 'Authors__site_id' => 20];
        $result = $callable($row);
        $row['Articles'] = [
            ['id' => 2, 'title' => 'article 2', 'author_id' => 1, 'site_id' => 20]
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
                'author_id' => 1
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
        $articles = TableRegistry::get('Articles');
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
            ]
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
            'sourceTable' => $this->author,
            'targetTable' => $mock,
        ];
        $association = new HasMany('Contacts.Addresses', $config);
        $this->assertEquals('addresses', $association->property());
    }

    /**
     * Test that the ValueBinder is reset when using strategy = Association::STRATEGY_SUBQUERY
     *
     * @return void
     */
    public function testValueBinderUpdateOnSubQueryStrategy()
    {
        $Authors = TableRegistry::get('Authors');
        $Authors->hasMany('Articles', [
            'strategy' => Association::STRATEGY_SUBQUERY
        ]);

        $query = $Authors->find();
        $authorsAndArticles = $query
            ->select([
                'id',
                'slug' => $query->func()->concat([
                    '---',
                    'name' => 'identifier'
                ])
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
            $driver = $query->connection()->driver();
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
            $quoter = new IdentifierQuoter($query->connection()->driver());
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
            $quoter = new IdentifierQuoter($query->connection()->driver());
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
            $connection = $query->connection();
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
        $articles = TableRegistry::get('Articles');
        $assoc = $this->author->hasMany('Articles', [
            'sourceTable' => $this->author,
            'targetTable' => $articles
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
        $articles = TableRegistry::get('Articles');
        $assoc = $this->author->hasMany('Articles', [
            'sourceTable' => $this->author,
            'targetTable' => $articles
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
}
