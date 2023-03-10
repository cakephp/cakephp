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
namespace Cake\Test\TestCase\Database;

use Cake\Database\Connection;
use Cake\Database\Driver\Mysql;
use Cake\Database\Driver\Postgres;
use Cake\Database\Driver\Sqlite;
use Cake\Database\Driver\Sqlserver;
use Cake\Database\Exception\DatabaseException;
use Cake\Database\Expression\CommonTableExpression;
use Cake\Database\Expression\IdentifierExpression;
use Cake\Database\Expression\QueryExpression;
use Cake\Database\Expression\StringExpression;
use Cake\Database\Expression\TupleComparison;
use Cake\Database\Expression\WindowExpression;
use Cake\Database\ExpressionInterface;
use Cake\Database\Query;
use Cake\Database\Statement\StatementDecorator;
use Cake\Database\StatementInterface;
use Cake\Database\TypeFactory;
use Cake\Database\TypeMap;
use Cake\Database\ValueBinder;
use Cake\Datasource\ConnectionManager;
use Cake\TestSuite\TestCase;
use DateTime;
use DateTimeImmutable;
use InvalidArgumentException;
use ReflectionProperty;
use RuntimeException;
use stdClass;
use TestApp\Database\Type\BarType;
use function Cake\Collection\collection;

/**
 * Tests Query class
 */
class QueryTest extends TestCase
{
    protected $fixtures = [
        'core.Articles',
        'core.Authors',
        'core.Comments',
        'core.Profiles',
        'core.MenuLinkTrees',
    ];

    /**
     * @var int
     */
    public const ARTICLE_COUNT = 3;
    /**
     * @var int
     */
    public const AUTHOR_COUNT = 4;
    /**
     * @var int
     */
    public const COMMENT_COUNT = 6;

    /**
     * @var \Cake\Database\Connection
     */
    protected $connection;

    /**
     * @var bool
     */
    protected $autoQuote;

    public function setUp(): void
    {
        parent::setUp();
        $this->connection = ConnectionManager::get('test');
        $this->autoQuote = $this->connection->getDriver()->isAutoQuotingEnabled();
    }

    public function tearDown(): void
    {
        parent::tearDown();
        $this->connection->getDriver()->enableAutoQuoting($this->autoQuote);
        unset($this->connection);
    }

    public function testConnectionRoles(): void
    {
        // Defaults to write role
        $this->assertSame(Connection::ROLE_WRITE, (new Query($this->connection))->getConnectionRole());

        $selectQuery = $this->connection->selectQuery();
        $this->assertSame(Connection::ROLE_WRITE, $selectQuery->getConnectionRole());

        // Can set read role for select queries
        $this->assertSame(Connection::ROLE_READ, $selectQuery->setConnectionRole(Connection::ROLE_READ)->getConnectionRole());

        // Can set read role for select queries
        $this->assertSame(Connection::ROLE_READ, $selectQuery->useReadRole()->getConnectionRole());

        // Can set write role for select queries
        $this->assertSame(Connection::ROLE_WRITE, $selectQuery->useWriteRole()->getConnectionRole());
    }

    /**
     * Queries need a default type to prevent fatal errors
     * when an uninitialized query has its sql() method called.
     */
    public function testDefaultType(): void
    {
        $query = new Query($this->connection);
        $this->assertSame('', $query->sql());
        $this->assertSame('select', $query->type());
    }

    /**
     * Tests that it is possible to obtain expression results from a query
     */
    public function testSelectFieldsOnly(): void
    {
        $this->connection->getDriver()->enableAutoQuoting(false);
        $query = new Query($this->connection);
        $result = $query->select('1 + 1')->execute();
        $this->assertInstanceOf('Cake\Database\StatementInterface', $result);
        $this->assertEquals([2], $result->fetch());
        $result->closeCursor();

        //This new field should be appended
        $result = $query->select(['1 + 3'])->execute();
        $this->assertInstanceOf('Cake\Database\StatementInterface', $result);
        $this->assertEquals([2, 4], $result->fetch());
        $result->closeCursor();

        //This should now overwrite all previous fields
        $result = $query->select(['1 + 2', '1 + 5'], true)->execute();
        $this->assertEquals([3, 6], $result->fetch());
        $result->closeCursor();
    }

    /**
     * Tests that it is possible to pass a closure as fields in select()
     */
    public function testSelectClosure(): void
    {
        $this->connection->getDriver()->enableAutoQuoting(false);
        $query = new Query($this->connection);
        $result = $query->select(function ($q) use ($query) {
            $this->assertSame($query, $q);

            return ['1 + 2', '1 + 5'];
        })->execute();
        $this->assertEquals([3, 6], $result->fetch());
        $result->closeCursor();
    }

    /**
     * Tests it is possible to select fields from tables with no conditions
     */
    public function testSelectFieldsFromTable(): void
    {
        $query = new Query($this->connection);
        $result = $query->select(['body', 'author_id'])->from('articles')->execute();
        $this->assertEquals(['body' => 'First Article Body', 'author_id' => 1], $result->fetch('assoc'));
        $this->assertEquals(['body' => 'Second Article Body', 'author_id' => 3], $result->fetch('assoc'));
        $result->closeCursor();

        //Append more tables to next execution
        $result = $query->select('name')->from(['authors'])->order(['name' => 'desc', 'articles.id' => 'asc'])->execute();
        $this->assertEquals(['body' => 'First Article Body', 'author_id' => 1, 'name' => 'nate'], $result->fetch('assoc'));
        $this->assertEquals(['body' => 'Second Article Body', 'author_id' => 3, 'name' => 'nate'], $result->fetch('assoc'));
        $this->assertEquals(['body' => 'Third Article Body', 'author_id' => 1, 'name' => 'nate'], $result->fetch('assoc'));
        $result->closeCursor();

        // Overwrite tables and only fetch from authors
        $result = $query->select('name', true)->from('authors', true)->order(['name' => 'desc'], true)->execute();
        $this->assertSame(['nate'], $result->fetch());
        $this->assertSame(['mariano'], $result->fetch());
        $this->assertCount(4, $result);
        $result->closeCursor();
    }

    /**
     * Tests it is possible to select aliased fields
     */
    public function testSelectAliasedFieldsFromTable(): void
    {
        $query = new Query($this->connection);
        $result = $query->select(['text' => 'comment', 'article_id'])->from('comments')->execute();
        $this->assertEquals(['text' => 'First Comment for First Article', 'article_id' => 1], $result->fetch('assoc'));
        $this->assertEquals(['text' => 'Second Comment for First Article', 'article_id' => 1], $result->fetch('assoc'));
        $result->closeCursor();

        $query = new Query($this->connection);
        $result = $query->select(['text' => 'comment', 'article' => 'article_id'])->from('comments')->execute();
        $this->assertEquals(['text' => 'First Comment for First Article', 'article' => 1], $result->fetch('assoc'));
        $this->assertEquals(['text' => 'Second Comment for First Article', 'article' => 1], $result->fetch('assoc'));
        $result->closeCursor();

        $query = new Query($this->connection);
        $query->select(['text' => 'comment'])->select(['article_id', 'foo' => 'comment']);
        $result = $query->from('comments')->execute();
        $this->assertEquals(
            ['foo' => 'First Comment for First Article', 'text' => 'First Comment for First Article', 'article_id' => 1],
            $result->fetch('assoc')
        );
        $result->closeCursor();

        $query = new Query($this->connection);
        $exp = $query->newExpr('1 + 1');
        $comp = $query->newExpr(['article_id +' => 2]);
        $result = $query->select(['text' => 'comment', 'two' => $exp, 'three' => $comp])
            ->from('comments')->execute();
        $this->assertEquals(['text' => 'First Comment for First Article', 'two' => 2, 'three' => 3], $result->fetch('assoc'));
        $result->closeCursor();
    }

    /**
     * Tests that tables can also be aliased and referenced in the select clause using such alias
     */
    public function testSelectAliasedTables(): void
    {
        $query = new Query($this->connection);
        $result = $query->select(['text' => 'a.body', 'a.author_id'])
            ->from(['a' => 'articles'])->execute();

        $this->assertEquals(['text' => 'First Article Body', 'author_id' => 1], $result->fetch('assoc'));
        $this->assertEquals(['text' => 'Second Article Body', 'author_id' => 3], $result->fetch('assoc'));
        $result->closeCursor();

        $result = $query->select(['name' => 'b.name'])->from(['b' => 'authors'])
            ->order(['text' => 'desc', 'name' => 'desc'])
            ->execute();
        $this->assertEquals(
            ['text' => 'Third Article Body', 'author_id' => 1, 'name' => 'nate'],
            $result->fetch('assoc')
        );
        $this->assertEquals(
            ['text' => 'Third Article Body', 'author_id' => 1, 'name' => 'mariano'],
            $result->fetch('assoc')
        );
        $result->closeCursor();
    }

    /**
     * Tests it is possible to add joins to a select query
     */
    public function testSelectWithJoins(): void
    {
        $query = new Query($this->connection);
        $result = $query
            ->select(['title', 'name'])
            ->from('articles')
            ->join(['table' => 'authors', 'alias' => 'a', 'conditions' => $query->newExpr()->equalFields('author_id', 'a.id')])
            ->order(['title' => 'asc'])
            ->execute();

        $this->assertCount(3, $result);
        $this->assertEquals(['title' => 'First Article', 'name' => 'mariano'], $result->fetch('assoc'));
        $this->assertEquals(['title' => 'Second Article', 'name' => 'larry'], $result->fetch('assoc'));
        $result->closeCursor();

        $result = $query->join('authors', [], true)->execute();
        $this->assertCount(12, $result, 'Cross join results in 12 records');
        $result->closeCursor();

        $result = $query->join([
            ['table' => 'authors', 'type' => 'INNER', 'conditions' => $query->newExpr()->equalFields('author_id', 'authors.id')],
        ], [], true)->execute();
        $this->assertCount(3, $result);
        $this->assertEquals(['title' => 'First Article', 'name' => 'mariano'], $result->fetch('assoc'));
        $this->assertEquals(['title' => 'Second Article', 'name' => 'larry'], $result->fetch('assoc'));
        $result->closeCursor();
    }

    /**
     * Tests it is possible to add joins to a select query using array or expression as conditions
     */
    public function testSelectWithJoinsConditions(): void
    {
        $query = new Query($this->connection);
        $result = $query
            ->select(['title', 'name'])
            ->from('articles')
            ->join(['table' => 'authors', 'alias' => 'a', 'conditions' => [$query->newExpr()->equalFields('author_id ', 'a.id')]])
            ->order(['title' => 'asc'])
            ->execute();
        $this->assertEquals(['title' => 'First Article', 'name' => 'mariano'], $result->fetch('assoc'));
        $this->assertEquals(['title' => 'Second Article', 'name' => 'larry'], $result->fetch('assoc'));
        $result->closeCursor();

        $query = new Query($this->connection);
        $conditions = $query->newExpr()->equalFields('author_id', 'a.id');
        $result = $query
            ->select(['title', 'name'])
            ->from('articles')
            ->join(['table' => 'authors', 'alias' => 'a', 'conditions' => $conditions])
            ->order(['title' => 'asc'])
            ->execute();
        $this->assertEquals(['title' => 'First Article', 'name' => 'mariano'], $result->fetch('assoc'));
        $this->assertEquals(['title' => 'Second Article', 'name' => 'larry'], $result->fetch('assoc'));
        $result->closeCursor();

        $query = new Query($this->connection);
        $time = new DateTime('2007-03-18 10:45:23');
        $types = ['created' => 'datetime'];
        $result = $query
            ->select(['title', 'comment' => 'c.comment'])
            ->from('articles')
            ->join(['table' => 'comments', 'alias' => 'c', 'conditions' => ['created' => $time]], $types)
            ->execute();
        $this->assertEquals(['title' => 'First Article', 'comment' => 'First Comment for First Article'], $result->fetch('assoc'));
        $result->closeCursor();
    }

    /**
     * Tests that joins can be aliased using array keys
     */
    public function testSelectAliasedJoins(): void
    {
        $query = new Query($this->connection);
        $result = $query
            ->select(['title', 'name'])
            ->from('articles')
            ->join(['a' => 'authors'])
            ->order(['name' => 'desc', 'articles.id' => 'asc'])
            ->execute();
        $this->assertEquals(['title' => 'First Article', 'name' => 'nate'], $result->fetch('assoc'));
        $this->assertEquals(['title' => 'Second Article', 'name' => 'nate'], $result->fetch('assoc'));
        $result->closeCursor();

        $query = new Query($this->connection);
        $conditions = $query->newExpr('author_id = a.id');
        $result = $query
            ->select(['title', 'name'])
            ->from('articles')
            ->join(['a' => ['table' => 'authors', 'conditions' => $conditions]])
            ->order(['title' => 'asc'])
            ->execute();
        $this->assertEquals(['title' => 'First Article', 'name' => 'mariano'], $result->fetch('assoc'));
        $this->assertEquals(['title' => 'Second Article', 'name' => 'larry'], $result->fetch('assoc'));
        $result->closeCursor();

        $query = new Query($this->connection);
        $time = new DateTime('2007-03-18 10:45:23');
        $types = ['created' => 'datetime'];
        $result = $query
            ->select(['title', 'name' => 'c.comment'])
            ->from('articles')
            ->join(['c' => ['table' => 'comments', 'conditions' => ['created' => $time]]], $types)
            ->execute();
        $this->assertEquals(['title' => 'First Article', 'name' => 'First Comment for First Article'], $result->fetch('assoc'));
        $result->closeCursor();
    }

    /**
     * Tests the leftJoin method
     */
    public function testSelectLeftJoin(): void
    {
        $query = new Query($this->connection);
        $time = new DateTime('2007-03-18 10:45:23');
        $types = ['created' => 'datetime'];
        $result = $query
            ->select(['title', 'name' => 'c.comment'])
            ->from('articles')
            ->leftJoin(['c' => 'comments'], ['created <' => $time], $types)
            ->execute();
        $this->assertEquals(['title' => 'First Article', 'name' => null], $result->fetch('assoc'));
        $result->closeCursor();

        $query = new Query($this->connection);
        $result = $query
            ->select(['title', 'name' => 'c.comment'])
            ->from('articles')
            ->leftJoin(['c' => 'comments'], ['created >' => $time], $types)
            ->order(['created' => 'asc'])
            ->execute();
        $this->assertEquals(
            ['title' => 'First Article', 'name' => 'Second Comment for First Article'],
            $result->fetch('assoc')
        );
        $result->closeCursor();
    }

    /**
     * Tests the innerJoin method
     */
    public function testSelectInnerJoin(): void
    {
        $query = new Query($this->connection);
        $time = new DateTime('2007-03-18 10:45:23');
        $types = ['created' => 'datetime'];
        $statement = $query
            ->select(['title', 'name' => 'c.comment'])
            ->from('articles')
            ->innerJoin(['c' => 'comments'], ['created <' => $time], $types)
            ->execute();
        $this->assertCount(0, $statement->fetchAll());
        $statement->closeCursor();
    }

    /**
     * Tests the rightJoin method
     */
    public function testSelectRightJoin(): void
    {
        $this->skipIf(
            $this->connection->getDriver() instanceof Sqlite,
            'SQLite does not support RIGHT joins'
        );
        $query = new Query($this->connection);
        $time = new DateTime('2007-03-18 10:45:23');
        $types = ['created' => 'datetime'];
        $result = $query
            ->select(['title', 'name' => 'c.comment'])
            ->from('articles')
            ->rightJoin(['c' => 'comments'], ['created <' => $time], $types)
            ->execute();
        $this->assertCount(6, $result);
        $this->assertEquals(
            ['title' => null, 'name' => 'First Comment for First Article'],
            $result->fetch('assoc')
        );
        $result->closeCursor();
    }

    /**
     * Tests that it is possible to pass a callable as conditions for a join
     */
    public function testSelectJoinWithCallback(): void
    {
        $query = new Query($this->connection);
        $types = ['created' => 'datetime'];
        $result = $query
            ->select(['title', 'name' => 'c.comment'])
            ->from('articles')
            ->innerJoin(['c' => 'comments'], function ($exp, $q) use ($query, $types) {
                $this->assertSame($q, $query);
                $exp->add(['created <' => new DateTime('2007-03-18 10:45:23')], $types);

                return $exp;
            })
            ->execute();
        $this->assertCount(0, $result->fetchAll());
        $result->closeCursor();
    }

    /**
     * Tests that it is possible to pass a callable as conditions for a join
     */
    public function testSelectJoinWithCallback2(): void
    {
        $query = new Query($this->connection);
        $types = ['created' => 'datetime'];
        $result = $query
            ->select(['name', 'commentary' => 'comments.comment'])
            ->from('authors')
            ->innerJoin('comments', function ($exp, $q) use ($query, $types) {
                $this->assertSame($q, $query);
                $exp->add(['created' => new DateTime('2007-03-18 10:47:23')], $types);

                return $exp;
            })
            ->execute();
        $this->assertEquals(
            ['name' => 'mariano', 'commentary' => 'Second Comment for First Article'],
            $result->fetch('assoc')
        );
        $result->closeCursor();
    }

    /**
     * Tests it is possible to filter a query by using simple AND joined conditions
     */
    public function testSelectSimpleWhere(): void
    {
        $query = new Query($this->connection);
        $result = $query
            ->select(['title'])
            ->from('articles')
            ->where(['id' => 1, 'title' => 'First Article'])
            ->execute();
        $this->assertCount(1, $result);
        $result->closeCursor();

        $query = new Query($this->connection);
        $result = $query
            ->select(['title'])
            ->from('articles')
            ->where(['id' => 100], ['id' => 'integer'])
            ->execute();
        $this->assertCount(0, $result);
        $result->closeCursor();
    }

    /**
     * Tests using where conditions with operators and scalar values works
     */
    public function testSelectWhereOperatorMoreThan(): void
    {
        $query = new Query($this->connection);
        $result = $query
            ->select(['comment'])
            ->from('comments')
            ->where(['id >' => 4])
            ->execute();
        $this->assertCount(2, $result);
        $this->assertEquals(['comment' => 'First Comment for Second Article'], $result->fetch('assoc'));
        $result->closeCursor();
    }

    /**
     * Tests using where conditions with operators and scalar values works
     */
    public function testSelectWhereOperatorLessThan(): void
    {
        $query = new Query($this->connection);
        $result = $query
            ->select(['title'])
            ->from('articles')
            ->where(['id <' => 2])
            ->execute();
        $this->assertCount(1, $result);
        $this->assertEquals(['title' => 'First Article'], $result->fetch('assoc'));
        $result->closeCursor();
    }

    /**
     * Tests using where conditions with operators and scalar values works
     */
    public function testSelectWhereOperatorLessThanEqual(): void
    {
        $query = new Query($this->connection);
        $result = $query
            ->select(['title'])
            ->from('articles')
            ->where(['id <=' => 2])
            ->execute();
        $this->assertCount(2, $result);
        $result->closeCursor();
    }

    /**
     * Tests using where conditions with operators and scalar values works
     */
    public function testSelectWhereOperatorMoreThanEqual(): void
    {
        $query = new Query($this->connection);
        $result = $query
            ->select(['title'])
            ->from('articles')
            ->where(['id >=' => 1])
            ->execute();
        $this->assertCount(3, $result);
        $result->closeCursor();
    }

    /**
     * Tests using where conditions with operators and scalar values works
     */
    public function testSelectWhereOperatorNotEqual(): void
    {
        $query = new Query($this->connection);
        $result = $query
            ->select(['title'])
            ->from('articles')
            ->where(['id !=' => 2])
            ->execute();
        $this->assertCount(2, $result);
        $this->assertEquals(['title' => 'First Article'], $result->fetch('assoc'));
        $result->closeCursor();
    }

    /**
     * Tests using where conditions with operators and scalar values works
     */
    public function testSelectWhereOperatorLike(): void
    {
        $query = new Query($this->connection);
        $result = $query
            ->select(['title'])
            ->from('articles')
            ->where(['title LIKE' => 'First Article'])
            ->execute();
        $this->assertCount(1, $result);
        $this->assertEquals(['title' => 'First Article'], $result->fetch('assoc'));
        $result->closeCursor();
    }

    /**
     * Tests using where conditions with operators and scalar values works
     */
    public function testSelectWhereOperatorLikeExpansion(): void
    {
        $query = new Query($this->connection);
        $result = $query
            ->select(['title'])
            ->from('articles')
            ->where(['title like' => '%Article%'])
            ->execute();
        $this->assertCount(3, $result);
        $result->closeCursor();
    }

    /**
     * Tests using where conditions with operators and scalar values works
     */
    public function testSelectWhereOperatorNotLike(): void
    {
        $query = new Query($this->connection);
        $result = $query
            ->select(['title'])
            ->from('articles')
            ->where(['title not like' => '%Article%'])
            ->execute();
        $this->assertCount(0, $result);
        $result->closeCursor();
    }

    /**
     * Test that unary expressions in selects are built correctly.
     */
    public function testSelectWhereUnary(): void
    {
        $query = new Query($this->connection);
        $result = $query
            ->select(['id'])
            ->from('articles')
            ->where([
                'title is not' => null,
                'user_id is' => null,
            ])
            ->sql();
        $this->assertQuotedQuery(
            'SELECT <id> FROM <articles> WHERE \(\(<title>\) IS NOT NULL AND \(<user_id>\) IS NULL\)',
            $result,
            !$this->autoQuote
        );
    }

    /**
     * Tests selecting with conditions and specifying types for those
     */
    public function testSelectWhereTypes(): void
    {
        $query = new Query($this->connection);
        $result = $query
            ->select(['id'])
            ->from('comments')
            ->where(['created' => new DateTime('2007-03-18 10:45:23')], ['created' => 'datetime'])
            ->execute();
        $this->assertCount(1, $result);
        $this->assertEquals(['id' => 1], $result->fetch('assoc'));
        $result->closeCursor();

        $query = new Query($this->connection);
        $result = $query
            ->select(['id'])
            ->from('comments')
            ->where(['created >' => new DateTime('2007-03-18 10:46:00')], ['created' => 'datetime'])
            ->execute();
        $this->assertCount(5, $result);
        $this->assertEquals(['id' => 2], $result->fetch('assoc'));
        $this->assertEquals(['id' => 3], $result->fetch('assoc'));
        $result->closeCursor();

        $query = new Query($this->connection);
        $result = $query
            ->select(['id'])
            ->from('comments')
            ->where(
                [
                    'created >' => new DateTime('2007-03-18 10:40:00'),
                    'created <' => new DateTime('2007-03-18 10:46:00'),
                ],
                ['created' => 'datetime']
            )
            ->execute();
        $this->assertCount(1, $result);
        $this->assertEquals(['id' => 1], $result->fetch('assoc'));
        $result->closeCursor();

        $query = new Query($this->connection);
        $result = $query
            ->select(['id'])
            ->from('comments')
            ->where(
                [
                    'id' => '3',
                    'created <' => new DateTime('2013-01-01 12:00'),
                ],
                ['created' => 'datetime', 'id' => 'integer']
            )
            ->execute();
        $this->assertCount(1, $result);
        $this->assertEquals(['id' => 3], $result->fetch('assoc'));
        $result->closeCursor();

        $query = new Query($this->connection);
        $result = $query
            ->select(['id'])
            ->from('comments')
            ->where(
                [
                    'id' => '1',
                    'created <' => new DateTime('2013-01-01 12:00'),
                ],
                ['created' => 'datetime', 'id' => 'integer']
            )
            ->execute();
        $this->assertCount(1, $result);
        $this->assertEquals(['id' => 1], $result->fetch('assoc'));
        $result->closeCursor();
    }

    /**
     * Tests Query::whereNull()
     */
    public function testSelectWhereNull(): void
    {
        $query = new Query($this->connection);
        $result = $query
            ->select(['id', 'parent_id'])
            ->from('menu_link_trees')
            ->whereNull(['parent_id'])
            ->execute();
        $this->assertCount(5, $result);
        $result->closeCursor();

        $query = new Query($this->connection);
        $result = $query
            ->select(['id'])
            ->from('menu_link_trees')
            ->whereNull($this->connection->selectQuery('parent_id'))
            ->execute();
        $this->assertCount(5, $result);
        $result->closeCursor();

        $query = new Query($this->connection);
        $result = $query
            ->select(['id'])
            ->from('menu_link_trees')
            ->whereNull('parent_id')
            ->execute();
        $this->assertCount(5, $result);
        $result->closeCursor();
    }

    /**
     * Tests Query::whereNotNull()
     */
    public function testSelectWhereNotNull(): void
    {
        $query = new Query($this->connection);
        $result = $query
            ->select(['id', 'parent_id'])
            ->from('menu_link_trees')
            ->whereNotNull(['parent_id'])
            ->execute();
        $this->assertCount(13, $result);
        $result->closeCursor();

        $query = new Query($this->connection);
        $result = $query
            ->select(['id'])
            ->from('menu_link_trees')
            ->whereNotNull($this->connection->selectQuery('parent_id'))
            ->execute();
        $this->assertCount(13, $result);
        $result->closeCursor();

        $query = new Query($this->connection);
        $result = $query
            ->select(['id'])
            ->from('menu_link_trees')
            ->whereNotNull('parent_id')
            ->execute();
        $this->assertCount(13, $result);
        $result->closeCursor();
    }

    /**
     * Tests that passing an array type to any where condition will replace
     * the passed array accordingly as a proper IN condition
     */
    public function testSelectWhereArrayType(): void
    {
        $query = new Query($this->connection);
        $result = $query
            ->select(['id'])
            ->from('comments')
            ->where(['id' => ['1', '3']], ['id' => 'integer[]'])
            ->execute();
        $this->assertCount(2, $result);
        $this->assertEquals(['id' => 1], $result->fetch('assoc'));
        $this->assertEquals(['id' => 3], $result->fetch('assoc'));
        $result->closeCursor();
    }

    /**
     * Tests that passing an empty array type to any where condition will not
     * result in a SQL error, but an internal exception
     */
    public function testSelectWhereArrayTypeEmpty(): void
    {
        $this->expectException(DatabaseException::class);
        $this->expectExceptionMessage('Impossible to generate condition with empty list of values for field');
        $query = new Query($this->connection);
        $result = $query
            ->select(['id'])
            ->from('comments')
            ->where(['id' => []], ['id' => 'integer[]'])
            ->execute();
    }

    /**
     * Tests exception message for impossible condition when using an expression
     */
    public function testSelectWhereArrayTypeEmptyWithExpression(): void
    {
        $this->expectException(DatabaseException::class);
        $this->expectExceptionMessage('with empty list of values for field (SELECT 1)');
        $query = new Query($this->connection);
        $result = $query
            ->select(['id'])
            ->from('comments')
            ->where(function ($exp, $q) {
                return $exp->in($q->newExpr('SELECT 1'), []);
            })
            ->execute();
    }

    /**
     * Tests that Query::andWhere() can be used to concatenate conditions with AND
     */
    public function testSelectAndWhere(): void
    {
        $query = new Query($this->connection);
        $result = $query
            ->select(['id'])
            ->from('comments')
            ->where(['created' => new DateTime('2007-03-18 10:45:23')], ['created' => 'datetime'])
            ->andWhere(['id' => 1])
            ->execute();
        $this->assertCount(1, $result);
        $this->assertEquals(['id' => 1], $result->fetch('assoc'));
        $result->closeCursor();

        $query = new Query($this->connection);
        $result = $query
            ->select(['id'])
            ->from('comments')
            ->where(['created' => new DateTime('2007-03-18 10:50:55')], ['created' => 'datetime'])
            ->andWhere(['id' => 2])
            ->execute();
        $this->assertCount(0, $result);
        $result->closeCursor();
    }

    /**
     * Tests that Query::andWhere() can be used without calling where() before
     */
    public function testSelectAndWhereNoPreviousCondition(): void
    {
        $query = new Query($this->connection);
        $result = $query
            ->select(['id'])
            ->from('comments')
            ->andWhere(['created' => new DateTime('2007-03-18 10:45:23')], ['created' => 'datetime'])
            ->andWhere(['id' => 1])
            ->execute();
        $this->assertCount(1, $result);
        $this->assertEquals(['id' => 1], $result->fetch('assoc'));
        $result->closeCursor();
    }

    /**
     * Tests that it is possible to pass a closure to where() to build a set of
     * conditions and return the expression to be used
     */
    public function testSelectWhereUsingClosure(): void
    {
        $query = new Query($this->connection);
        $result = $query
            ->select(['id'])
            ->from('comments')
            ->where(function ($exp) {
                return $exp->eq('id', 1);
            })
            ->execute();
        $this->assertCount(1, $result);
        $this->assertEquals(['id' => 1], $result->fetch('assoc'));
        $result->closeCursor();

        $query = new Query($this->connection);
        $result = $query
            ->select(['id'])
            ->from('comments')
            ->where(function ($exp) {
                return $exp
                    ->eq('id', 1)
                    ->eq('created', new DateTime('2007-03-18 10:45:23'), 'datetime');
            })
            ->execute();
        $this->assertCount(1, $result);
        $this->assertEquals(['id' => 1], $result->fetch('assoc'));
        $result->closeCursor();

        $query = new Query($this->connection);
        $result = $query
            ->select(['id'])
            ->from('comments')
            ->where(function ($exp) {
                return $exp
                    ->eq('id', 1)
                    ->eq('created', new DateTime('2021-12-30 15:00'), 'datetime');
            })
            ->execute();
        $this->assertCount(0, $result);
        $result->closeCursor();
    }

    /**
     * Tests generating tuples in the values side containing closure expressions
     */
    public function testTupleWithClosureExpression(): void
    {
        $query = new Query($this->connection);
        $query->select(['id'])
            ->from('comments')
            ->where([
                'OR' => [
                    'id' => 1,
                    function ($exp) {
                        return $exp->eq('id', 2);
                    },
                ],
            ]);

        $result = $query->sql();
        $this->assertQuotedQuery(
            'SELECT <id> FROM <comments> WHERE \(<id> = :c0 OR <id> = :c1\)',
            $result,
            !$this->autoQuote
        );
    }

    /**
     * Tests that it is possible to pass a closure to andWhere() to build a set of
     * conditions and return the expression to be used
     */
    public function testSelectAndWhereUsingClosure(): void
    {
        $query = new Query($this->connection);
        $result = $query
            ->select(['id'])
            ->from('comments')
            ->where(['id' => '1'])
            ->andWhere(function ($exp) {
                return $exp->eq('created', new DateTime('2007-03-18 10:45:23'), 'datetime');
            })
            ->execute();
        $this->assertCount(1, $result);
        $this->assertEquals(['id' => 1], $result->fetch('assoc'));
        $result->closeCursor();

        $query = new Query($this->connection);
        $result = $query
            ->select(['id'])
            ->from('comments')
            ->where(['id' => '1'])
            ->andWhere(function ($exp) {
                return $exp->eq('created', new DateTime('2022-12-21 12:00'), 'datetime');
            })
            ->execute();
        $this->assertCount(0, $result);
        $result->closeCursor();
    }

    /**
     * Tests that expression objects can be used as the field in a comparison
     * and the values will be bound correctly to the query
     */
    public function testSelectWhereUsingExpressionInField(): void
    {
        $query = new Query($this->connection);
        $result = $query
            ->select(['id'])
            ->from('comments')
            ->where(function ($exp) {
                $field = clone $exp;
                $field->add('SELECT min(id) FROM comments');

                return $exp
                    ->eq($field, 100, 'integer');
            })
            ->execute();
        $this->assertCount(0, $result);
        $result->closeCursor();
    }

    /**
     * Tests using where conditions with operator methods
     */
    public function testSelectWhereOperatorMethods(): void
    {
        $query = new Query($this->connection);
        $result = $query
            ->select(['title'])
            ->from('articles')
            ->where(function ($exp) {
                return $exp->gt('id', 1);
            })
            ->execute();
        $this->assertCount(2, $result);
        $this->assertEquals(['title' => 'Second Article'], $result->fetch('assoc'));
        $result->closeCursor();

        $query = new Query($this->connection);
        $result = $query->select(['title'])
            ->from('articles')
            ->where(function ($exp) {
                return $exp->lt('id', 2);
            })
            ->execute();
        $this->assertCount(1, $result);
        $this->assertEquals(['title' => 'First Article'], $result->fetch('assoc'));
        $result->closeCursor();

        $query = new Query($this->connection);
        $result = $query->select(['title'])
            ->from('articles')
            ->where(function ($exp) {
                return $exp->lte('id', 2);
            })
            ->execute();
        $this->assertCount(2, $result);
        $result->closeCursor();

        $query = new Query($this->connection);
        $result = $query
            ->select(['title'])
            ->from('articles')
            ->where(function ($exp) {
                return $exp->gte('id', 1);
            })
            ->execute();
        $this->assertCount(3, $result);
        $result->closeCursor();

        $query = new Query($this->connection);
        $result = $query
            ->select(['title'])
            ->from('articles')
            ->where(function ($exp) {
                return $exp->lte('id', 1);
            })
            ->execute();
        $this->assertCount(1, $result);
        $result->closeCursor();

        $query = new Query($this->connection);
        $result = $query
            ->select(['title'])
            ->from('articles')
            ->where(function ($exp) {
                return $exp->notEq('id', 2);
            })
            ->execute();
        $this->assertCount(2, $result);
        $this->assertEquals(['title' => 'First Article'], $result->fetch('assoc'));
        $result->closeCursor();

        $query = new Query($this->connection);
        $result = $query
            ->select(['title'])
            ->from('articles')
            ->where(function ($exp) {
                return $exp->like('title', 'First Article');
            })
            ->execute();
        $this->assertCount(1, $result);
        $this->assertEquals(['title' => 'First Article'], $result->fetch('assoc'));
        $result->closeCursor();

        $query = new Query($this->connection);
        $result = $query
            ->select(['title'])
            ->from('articles')
            ->where(function ($exp) {
                return $exp->like('title', '%Article%');
            })
            ->execute();
        $this->assertCount(3, $result);
        $result->closeCursor();

        $query = new Query($this->connection);
        $result = $query
            ->select(['title'])
            ->from('articles')
            ->where(function ($exp) {
                return $exp->notLike('title', '%Article%');
            })
            ->execute();
        $this->assertCount(0, $result);
        $result->closeCursor();

        $query = new Query($this->connection);
        $result = $query
            ->select(['id'])
            ->from('comments')
            ->where(function ($exp) {
                return $exp->isNull('published');
            })
            ->execute();
        $this->assertCount(0, $result);
        $result->closeCursor();

        $query = new Query($this->connection);
        $result = $query
            ->select(['id'])
            ->from('comments')
            ->where(function ($exp) {
                return $exp->isNotNull('published');
            })
            ->execute();
        $this->assertCount(6, $result);
        $result->closeCursor();

        $query = new Query($this->connection);
        $result = $query
            ->select(['id'])
            ->from('comments')
            ->where(function ($exp) {
                return $exp->in('published', ['Y', 'N']);
            })
            ->execute();
        $this->assertCount(6, $result);
        $result->closeCursor();

        $query = new Query($this->connection);
        $result = $query
            ->select(['id'])
            ->from('comments')
            ->where(function ($exp) {
                return $exp->in(
                    'created',
                    [new DateTime('2007-03-18 10:45:23'), new DateTime('2007-03-18 10:47:23')],
                    'datetime'
                );
            })
            ->execute();
        $this->assertCount(2, $result);
        $this->assertEquals(['id' => 1], $result->fetch('assoc'));
        $this->assertEquals(['id' => 2], $result->fetch('assoc'));
        $result->closeCursor();

        $query = new Query($this->connection);
        $result = $query
            ->select(['id'])
            ->from('comments')
            ->where(function ($exp) {
                return $exp->notIn(
                    'created',
                    [new DateTime('2007-03-18 10:45:23'), new DateTime('2007-03-18 10:47:23')],
                    'datetime'
                );
            })
            ->execute();
        $this->assertCount(4, $result);
        $this->assertEquals(['id' => 3], $result->fetch('assoc'));
        $result->closeCursor();
    }

    /**
     * Tests that calling "in" and "notIn" will cast the passed values to an array
     */
    public function testInValueCast(): void
    {
        $query = new Query($this->connection);
        $result = $query
            ->select(['id'])
            ->from('comments')
            ->where(function ($exp) {
                return $exp->in('created', '2007-03-18 10:45:23', 'datetime');
            })
            ->execute();
        $this->assertCount(1, $result);
        $this->assertEquals(['id' => 1], $result->fetch('assoc'));
        $result->closeCursor();

        $query = new Query($this->connection);
        $result = $query
            ->select(['id'])
            ->from('comments')
            ->where(function ($exp) {
                return $exp->notIn('created', '2007-03-18 10:45:23', 'datetime');
            })
            ->execute();
        $this->assertCount(5, $result);
        $this->assertEquals(['id' => 2], $result->fetch('assoc'));
        $this->assertEquals(['id' => 3], $result->fetch('assoc'));
        $this->assertEquals(['id' => 4], $result->fetch('assoc'));
        $this->assertEquals(['id' => 5], $result->fetch('assoc'));
        $result->closeCursor();

        $query = new Query($this->connection);
        $result = $query
            ->select(['id'])
            ->from('comments')
            ->where(function ($exp, $q) {
                return $exp->in(
                    'created',
                    $q->newExpr("'2007-03-18 10:45:23'"),
                    'datetime'
                );
            })
            ->execute();
        $this->assertCount(1, $result);
        $this->assertEquals(['id' => 1], $result->fetch('assoc'));
        $result->closeCursor();

        $query = new Query($this->connection);
        $result = $query
            ->select(['id'])
            ->from('comments')
            ->where(function ($exp, $q) {
                return $exp->notIn(
                    'created',
                    $q->newExpr("'2007-03-18 10:45:23'"),
                    'datetime'
                );
            })
            ->execute();
        $this->assertCount(5, $result);
        $result->closeCursor();
    }

    /**
     * Tests that calling "in" and "notIn" will cast the passed values to an array
     */
    public function testInValueCast2(): void
    {
        $query = new Query($this->connection);
        $result = $query
            ->select(['id'])
            ->from('comments')
            ->where(['created IN' => '2007-03-18 10:45:23'])
            ->execute();
        $this->assertCount(1, $result);
        $this->assertEquals(['id' => 1], $result->fetch('assoc'));
        $result->closeCursor();

        $query = new Query($this->connection);
        $result = $query
            ->select(['id'])
            ->from('comments')
            ->where(['created NOT IN' => '2007-03-18 10:45:23'])
            ->execute();
        $this->assertCount(5, $result);
        $result->closeCursor();
    }

    /**
     * Tests that IN clauses generate correct placeholders
     */
    public function testInClausePlaceholderGeneration(): void
    {
        $query = new Query($this->connection);
        $query->select(['id'])
            ->from('comments')
            ->where(['id IN' => [1, 2]])
            ->sql();
        $bindings = $query->getValueBinder()->bindings();
        $this->assertArrayHasKey(':c0', $bindings);
        $this->assertSame('c0', $bindings[':c0']['placeholder']);
        $this->assertArrayHasKey(':c1', $bindings);
        $this->assertSame('c1', $bindings[':c1']['placeholder']);
    }

    /**
     * Tests where() with callable types.
     */
    public function testWhereCallables(): void
    {
        $query = new Query($this->connection);
        $query->select(['id'])
            ->from('articles')
            ->where([
                'id' => 'Cake\Error\Debugger::dump',
                'title' => ['Cake\Error\Debugger', 'dump'],
                'author_id' => function ($exp) {
                    return 1;
                },
            ]);
        $this->assertQuotedQuery(
            'SELECT <id> FROM <articles> WHERE \(<id> = :c0 AND <title> = :c1 AND <author_id> = :c2\)',
            $query->sql(),
            !$this->autoQuote
        );
    }

    /**
     * Tests that empty values don't set where clauses.
     */
    public function testWhereEmptyValues(): void
    {
        $query = new Query($this->connection);
        $query->from('comments')
            ->where('');

        $this->assertCount(0, $query->clause('where'));

        $query->where([]);
        $this->assertCount(0, $query->clause('where'));
    }

    /**
     * Tests that it is possible to use a between expression
     * in a where condition
     */
    public function testWhereWithBetween(): void
    {
        $query = new Query($this->connection);
        $result = $query
            ->select(['id'])
            ->from('comments')
            ->where(function ($exp) {
                return $exp->between('id', 5, 6, 'integer');
            })
            ->execute();

        $this->assertCount(2, $result);
        $first = $result->fetch('assoc');
        $this->assertEquals(5, $first['id']);

        $second = $result->fetch('assoc');
        $this->assertEquals(6, $second['id']);
        $result->closeCursor();
    }

    /**
     * Tests that it is possible to use a between expression
     * in a where condition with a complex data type
     */
    public function testWhereWithBetweenComplex(): void
    {
        $query = new Query($this->connection);
        $result = $query
            ->select(['id'])
            ->from('comments')
            ->where(function ($exp) {
                $from = new DateTime('2007-03-18 10:51:00');
                $to = new DateTime('2007-03-18 10:54:00');

                return $exp->between('created', $from, $to, 'datetime');
            })
            ->execute();

        $this->assertCount(2, $result);
        $first = $result->fetch('assoc');
        $this->assertEquals(4, $first['id']);

        $second = $result->fetch('assoc');
        $this->assertEquals(5, $second['id']);
        $result->closeCursor();
    }

    /**
     * Tests that it is possible to use an expression object
     * as the field for a between expression
     */
    public function testWhereWithBetweenWithExpressionField(): void
    {
        $query = new Query($this->connection);
        $result = $query
            ->select(['id'])
            ->from('comments')
            ->where(function ($exp, $q) {
                $field = $q->func()->coalesce([new IdentifierExpression('id'), 1 => 'literal']);

                return $exp->between($field, 5, 6, 'integer');
            })
            ->execute();

        $this->assertCount(2, $result);
        $first = $result->fetch('assoc');
        $this->assertEquals(5, $first['id']);

        $second = $result->fetch('assoc');
        $this->assertEquals(6, $second['id']);
        $result->closeCursor();
    }

    /**
     * Tests that it is possible to use an expression object
     * as any of the parts of the between expression
     */
    public function testWhereWithBetweenWithExpressionParts(): void
    {
        $query = new Query($this->connection);
        $result = $query
            ->select(['id'])
            ->from('comments')
            ->where(function ($exp, $q) {
                $from = $q->newExpr("'2007-03-18 10:51:00'");
                $to = $q->newExpr("'2007-03-18 10:54:00'");

                return $exp->between('created', $from, $to);
            })
            ->execute();

        $this->assertCount(2, $result);
        $first = $result->fetch('assoc');
        $this->assertEquals(4, $first['id']);

        $second = $result->fetch('assoc');
        $this->assertEquals(5, $second['id']);
        $result->closeCursor();
    }

    /**
     * Tests nesting query expressions both using arrays and closures
     */
    public function testSelectExpressionComposition(): void
    {
        $query = new Query($this->connection);
        $result = $query
            ->select(['id'])
            ->from('comments')
            ->where(function ($exp) {
                $and = $exp->and(['id' => 2, 'id >' => 1]);

                return $exp->add($and);
            })
            ->execute();
        $this->assertCount(1, $result);
        $this->assertEquals(['id' => 2], $result->fetch('assoc'));
        $result->closeCursor();

        $query = new Query($this->connection);
        $result = $query
            ->select(['id'])
            ->from('comments')
            ->where(function ($exp) {
                $and = $exp->and(['id' => 2, 'id <' => 2]);

                return $exp->add($and);
            })
            ->execute();
        $this->assertCount(0, $result);
        $result->closeCursor();

        $query = new Query($this->connection);
        $result = $query
            ->select(['id'])
            ->from('comments')
            ->where(function ($exp) {
                $and = $exp->and(function ($and) {
                    return $and->eq('id', 1)->gt('id', 0);
                });

                return $exp->add($and);
            })
            ->execute();
        $this->assertCount(1, $result);
        $this->assertEquals(['id' => 1], $result->fetch('assoc'));
        $result->closeCursor();

        $query = new Query($this->connection);
        $result = $query
            ->select(['id'])
            ->from('comments')
            ->where(function ($exp) {
                $or = $exp->or(['id' => 1]);
                $and = $exp->and(['id >' => 2, 'id <' => 4]);

                return $or->add($and);
            })
            ->execute();
        $this->assertCount(2, $result);
        $this->assertEquals(['id' => 1], $result->fetch('assoc'));
        $this->assertEquals(['id' => 3], $result->fetch('assoc'));
        $result->closeCursor();

        $query = new Query($this->connection);
        $result = $query
            ->select(['id'])
            ->from('comments')
            ->where(function ($exp) {
                $or = $exp->or(function ($or) {
                    return $or->eq('id', 1)->eq('id', 2);
                });

                return $or;
            })
            ->execute();
        $this->assertCount(2, $result);
        $this->assertEquals(['id' => 1], $result->fetch('assoc'));
        $this->assertEquals(['id' => 2], $result->fetch('assoc'));
        $result->closeCursor();
    }

    /**
     * Tests that conditions can be nested with an unary operator using the array notation
     * and the not() method
     */
    public function testSelectWhereNot(): void
    {
        $query = new Query($this->connection);
        $result = $query
            ->select(['id'])
            ->from('comments')
            ->where(function ($exp) {
                return $exp->not(
                    $exp->and(['id' => 2, 'created' => new DateTime('2007-03-18 10:47:23')], ['created' => 'datetime'])
                );
            })
            ->execute();
        $this->assertCount(5, $result);
        $this->assertEquals(['id' => 1], $result->fetch('assoc'));
        $this->assertEquals(['id' => 3], $result->fetch('assoc'));
        $result->closeCursor();

        $query = new Query($this->connection);
        $result = $query
            ->select(['id'])
            ->from('comments')
            ->where(function ($exp) {
                return $exp->not(
                    $exp->and(['id' => 2, 'created' => new DateTime('2012-12-21 12:00')], ['created' => 'datetime'])
                );
            })
            ->execute();
        $this->assertCount(6, $result);
        $result->closeCursor();
    }

    /**
     * Tests that conditions can be nested with an unary operator using the array notation
     * and the not() method
     */
    public function testSelectWhereNot2(): void
    {
        $query = new Query($this->connection);
        $result = $query
            ->select(['id'])
            ->from('articles')
            ->where([
                'not' => ['or' => ['id' => 1, 'id >' => 2], 'id' => 3],
            ])
            ->execute();
        $this->assertCount(2, $result);
        $this->assertEquals(['id' => 1], $result->fetch('assoc'));
        $this->assertEquals(['id' => 2], $result->fetch('assoc'));
        $result->closeCursor();
    }

    /**
     * Tests whereInArray() and its input types.
     */
    public function testWhereInArray(): void
    {
        $query = new Query($this->connection);
        $query->select(['id'])
            ->from('articles')
            ->whereInList('id', [2, 3])
            ->order(['id']);

        $sql = $query->sql();
        $this->assertQuotedQuery(
            'SELECT <id> FROM <articles> WHERE <id> in \\(:c0,:c1\\)',
            $sql,
            !$this->autoQuote
        );

        $result = $query->execute()->fetchAll('assoc');
        $this->assertEquals(['id' => '2'], $result[0]);
    }

    /**
     * Tests whereInArray() and empty array input.
     */
    public function testWhereInArrayEmpty(): void
    {
        $query = new Query($this->connection);
        $query->select(['id'])
            ->from('articles')
            ->whereInList('id', [], ['allowEmpty' => true]);

        $this->assertQuotedQuery(
            'SELECT <id> FROM <articles> WHERE 1=0',
            $query->sql(),
            !$this->autoQuote
        );

        $statement = $query->execute();
        $this->assertFalse($statement->fetch('assoc'));
        $statement->closeCursor();
    }

    /**
     * Tests whereNotInList() and its input types.
     */
    public function testWhereNotInList(): void
    {
        $query = new Query($this->connection);
        $query->select(['id'])
            ->from('articles')
            ->whereNotInList('id', [1, 3]);

        $this->assertQuotedQuery(
            'SELECT <id> FROM <articles> WHERE <id> not in \\(:c0,:c1\\)',
            $query->sql(),
            !$this->autoQuote
        );

        $result = $query->execute()->fetchAll('assoc');
        $this->assertEquals(['id' => '2'], $result[0]);
    }

    /**
     * Tests whereNotInList() and empty array input.
     */
    public function testWhereNotInListEmpty(): void
    {
        $query = new Query($this->connection);
        $query->select(['id'])
            ->from('articles')
            ->whereNotInList('id', [], ['allowEmpty' => true])
            ->order(['id']);

        $this->assertQuotedQuery(
            'SELECT <id> FROM <articles> WHERE \(<id>\) IS NOT NULL',
            $query->sql(),
            !$this->autoQuote
        );

        $result = $query->execute()->fetchAll('assoc');
        $this->assertEquals(['id' => '1'], $result[0]);
    }

    /**
     * Tests whereNotInListOrNull() and its input types.
     */
    public function testWhereNotInListOrNull(): void
    {
        $query = new Query($this->connection);
        $query->select(['id'])
            ->from('articles')
            ->whereNotInListOrNull('id', [1, 3]);

        $this->assertQuotedQuery(
            'SELECT <id> FROM <articles> WHERE \\(<id> not in \\(:c0,:c1\\) OR \\(<id>\\) IS NULL\\)',
            $query->sql(),
            !$this->autoQuote
        );

        $result = $query->execute()->fetchAll('assoc');
        $this->assertEquals(['id' => '2'], $result[0]);
    }

    /**
     * Tests whereNotInListOrNull() and empty array input.
     */
    public function testWhereNotInListOrNullEmpty(): void
    {
        $query = new Query($this->connection);
        $query->select(['id'])
            ->from('articles')
            ->whereNotInListOrNull('id', [], ['allowEmpty' => true])
            ->order(['id']);

        $this->assertQuotedQuery(
            'SELECT <id> FROM <articles> WHERE \(<id>\) IS NOT NULL',
            $query->sql(),
            !$this->autoQuote
        );

        $result = $query->execute()->fetchAll('assoc');
        $this->assertEquals(['id' => '1'], $result[0]);
    }

    /**
     * Tests order() method both with simple fields and expressions
     */
    public function testSelectOrderBy(): void
    {
        $query = new Query($this->connection);
        $result = $query
            ->select(['id'])
            ->from('comments')
            ->order(['id' => 'desc'])
            ->execute();
        $this->assertEquals(['id' => 6], $result->fetch('assoc'));
        $this->assertEquals(['id' => 5], $result->fetch('assoc'));
        $this->assertEquals(['id' => 4], $result->fetch('assoc'));
        $result->closeCursor();

        $result = $query->order(['id' => 'asc'])->execute();
        $this->assertEquals(['id' => 1], $result->fetch('assoc'));
        $this->assertEquals(['id' => 2], $result->fetch('assoc'));
        $this->assertEquals(['id' => 3], $result->fetch('assoc'));
        $result->closeCursor();

        $result = $query->order(['comment' => 'asc'])->execute();
        $this->assertEquals(['id' => 1], $result->fetch('assoc'));
        $this->assertEquals(['id' => 2], $result->fetch('assoc'));
        $this->assertEquals(['id' => 3], $result->fetch('assoc'));
        $result->closeCursor();

        $result = $query->order(['comment' => 'asc'], true)->execute();
        $this->assertEquals(['id' => 1], $result->fetch('assoc'));
        $this->assertEquals(['id' => 5], $result->fetch('assoc'));
        $this->assertEquals(['id' => 4], $result->fetch('assoc'));
        $result->closeCursor();

        $result = $query->order(['user_id' => 'asc', 'created' => 'desc'], true)
            ->execute();
        $this->assertEquals(['id' => 5], $result->fetch('assoc'));
        $this->assertEquals(['id' => 4], $result->fetch('assoc'));
        $this->assertEquals(['id' => 3], $result->fetch('assoc'));
        $result->closeCursor();

        $expression = $query->newExpr(['(id + :offset) % 2']);
        $result = $query
            ->order([$expression, 'id' => 'desc'], true)
            ->bind(':offset', 1, null)
            ->execute();
        $this->assertEquals(['id' => 5], $result->fetch('assoc'));
        $this->assertEquals(['id' => 3], $result->fetch('assoc'));
        $this->assertEquals(['id' => 1], $result->fetch('assoc'));
        $result->closeCursor();

        $result = $query
            ->order($expression, true)
            ->order(['id' => 'asc'])
            ->bind(':offset', 1, null)
            ->execute();
        $this->assertEquals(['id' => 1], $result->fetch('assoc'));
        $this->assertEquals(['id' => 3], $result->fetch('assoc'));
        $this->assertEquals(['id' => 5], $result->fetch('assoc'));
        $result->closeCursor();
    }

    /**
     * Test that order() being a string works.
     */
    public function testSelectOrderByString(): void
    {
        $query = new Query($this->connection);
        $query->select(['id'])
            ->from('articles')
            ->order('id asc');
        $result = $query->execute();
        $this->assertEquals(['id' => 1], $result->fetch('assoc'));
        $this->assertEquals(['id' => 2], $result->fetch('assoc'));
        $this->assertEquals(['id' => 3], $result->fetch('assoc'));
        $result->closeCursor();
    }

    /**
     * Test exception for order() with an associative array which contains extra values.
     */
    public function testSelectOrderByAssociativeArrayContainingExtraExpressions(): void
    {
        $this->expectException('RuntimeException');
        $this->expectExceptionMessage(
            'Passing extra expressions by associative array (`\'id\' => \'desc -- Comment\'`) ' .
            'is not allowed to avoid potential SQL injection. ' .
            'Use QueryExpression or numeric array instead.'
        );

        $query = new Query($this->connection);
        $query->select(['id'])
            ->from('articles')
            ->order([
                'id' => 'desc -- Comment',
            ]);
    }

    /**
     * Tests that order() works with closures.
     */
    public function testSelectOrderByClosure(): void
    {
        $query = new Query($this->connection);
        $query
            ->select('*')
            ->from('articles')
            ->order(function ($exp, $q) use ($query) {
                $this->assertInstanceOf(QueryExpression::class, $exp);
                $this->assertSame($query, $q);

                return ['id' => 'ASC'];
            });

        $this->assertQuotedQuery(
            'SELECT \* FROM <articles> ORDER BY <id> ASC',
            $query->sql(),
            !$this->autoQuote
        );

        $query = new Query($this->connection);
        $query
            ->select('*')
            ->from('articles')
            ->order(function ($exp) {
                return [$exp->add(['id % 2 = 0']), 'title' => 'ASC'];
            });

        $this->assertQuotedQuery(
            'SELECT \* FROM <articles> ORDER BY id % 2 = 0, <title> ASC',
            $query->sql(),
            !$this->autoQuote
        );

        $query = new Query($this->connection);
        $query
            ->select('*')
            ->from('articles')
            ->order(function ($exp) {
                return $exp->add('a + b');
            });

        $this->assertQuotedQuery(
            'SELECT \* FROM <articles> ORDER BY a \+ b',
            $query->sql(),
            !$this->autoQuote
        );

        $query = new Query($this->connection);
        $query
            ->select('*')
            ->from('articles')
            ->order(function ($exp, $q) {
                return $q->func()->sum('a');
            });

        $this->assertQuotedQuery(
            'SELECT \* FROM <articles> ORDER BY SUM\(a\)',
            $query->sql(),
            !$this->autoQuote
        );
    }

    /**
     * Test orderAsc() and its input types.
     */
    public function testSelectOrderAsc(): void
    {
        $query = new Query($this->connection);
        $query->select(['id'])
            ->from('articles')
            ->orderAsc('id');

        $sql = $query->sql();
        $result = $query->execute()->fetchAll('assoc');
        $expected = [
            ['id' => 1],
            ['id' => 2],
            ['id' => 3],
        ];
        $this->assertEquals($expected, $result);
        $this->assertQuotedQuery(
            'SELECT <id> FROM <articles> ORDER BY <id> ASC',
            $sql,
            !$this->autoQuote
        );

        $query = new Query($this->connection);
        $query->select(['id'])
            ->from('articles')
            ->orderAsc($query->func()->concat(['id' => 'identifier', '3']));

        $result = $query->execute()->fetchAll('assoc');
        $expected = [
            ['id' => 1],
            ['id' => 2],
            ['id' => 3],
        ];
        $this->assertEquals($expected, $result);

        $query = new Query($this->connection);
        $query->select(['id'])
            ->from('articles')
            ->orderAsc(function (QueryExpression $exp, Query $query) {
                return $exp
                    ->case()
                    ->when(['author_id' => 1])
                    ->then(1)
                    ->else($query->identifier('id'));
            })
            ->orderAsc('id');
        $sql = $query->sql();
        $result = $query->execute()->fetchAll('assoc');
        $expected = [
            ['id' => 1],
            ['id' => 3],
            ['id' => 2],
        ];
        $this->assertEquals($expected, $result);
        $this->assertQuotedQuery(
            'SELECT <id> FROM <articles> ORDER BY CASE WHEN <author_id> = :c0 THEN :c1 ELSE <id> END ASC, <id> ASC',
            $sql,
            !$this->autoQuote
        );
    }

    /**
     * Test orderDesc() and its input types.
     */
    public function testSelectOrderDesc(): void
    {
        $query = new Query($this->connection);
        $query->select(['id'])
            ->from('articles')
            ->orderDesc('id');
        $sql = $query->sql();
        $result = $query->execute()->fetchAll('assoc');
        $expected = [
            ['id' => 3],
            ['id' => 2],
            ['id' => 1],
        ];
        $this->assertEquals($expected, $result);
        $this->assertQuotedQuery(
            'SELECT <id> FROM <articles> ORDER BY <id> DESC',
            $sql,
            !$this->autoQuote
        );

        $query = new Query($this->connection);
        $query->select(['id'])
            ->from('articles')
            ->orderDesc($query->func()->concat(['id' => 'identifier', '3']));

        $result = $query->execute()->fetchAll('assoc');
        $expected = [
            ['id' => 3],
            ['id' => 2],
            ['id' => 1],
        ];
        $this->assertEquals($expected, $result);

        $query = new Query($this->connection);
        $query->select(['id'])
            ->from('articles')
            ->orderDesc(function (QueryExpression $exp, Query $query) {
                return $exp
                    ->case()
                    ->when(['author_id' => 1])
                    ->then(1)
                    ->else($query->identifier('id'));
            })
            ->orderDesc('id');
        $sql = $query->sql();
        $result = $query->execute()->fetchAll('assoc');
        $expected = [
            ['id' => 2],
            ['id' => 3],
            ['id' => 1],
        ];
        $this->assertEquals($expected, $result);
        $this->assertQuotedQuery(
            'SELECT <id> FROM <articles> ORDER BY CASE WHEN <author_id> = :c0 THEN :c1 ELSE <id> END DESC, <id> DESC',
            $sql,
            !$this->autoQuote
        );
    }

    /**
     * Tests that group by fields can be passed similar to select fields
     * and that it sends the correct query to the database
     */
    public function testSelectGroup(): void
    {
        $query = new Query($this->connection);
        $result = $query
            ->select(['total' => 'count(author_id)', 'author_id'])
            ->from('articles')
            ->join(['table' => 'authors', 'alias' => 'a', 'conditions' => 'author_id = a.id'])
            ->group('author_id')
            ->order(['total' => 'desc'])
            ->execute();
        $expected = [['total' => 2, 'author_id' => 1], ['total' => '1', 'author_id' => 3]];
        $this->assertEquals($expected, $result->fetchAll('assoc'));

        $result = $query->select(['total' => 'count(title)', 'name'], true)
            ->group(['name'], true)
            ->order(['total' => 'asc'])
            ->execute();
        $expected = [['total' => 1, 'name' => 'larry'], ['total' => 2, 'name' => 'mariano']];
        $this->assertEquals($expected, $result->fetchAll('assoc'));

        $result = $query->select(['articles.id'])
            ->group(['articles.id'])
            ->execute();
        $this->assertCount(3, $result);
    }

    /**
     * Tests that it is possible to select distinct rows
     */
    public function testSelectDistinct(): void
    {
        $query = new Query($this->connection);
        $result = $query
            ->select(['author_id'])
            ->from(['a' => 'articles'])
            ->execute();
        $this->assertCount(3, $result);

        $result = $query->distinct()->execute();
        $this->assertCount(2, $result);

        $result = $query->select(['id'])->distinct(false)->execute();
        $this->assertCount(3, $result);
    }

    /**
     * Tests distinct on a specific column reduces rows based on that column.
     */
    public function testSelectDistinctON(): void
    {
        $query = new Query($this->connection);
        $result = $query
            ->select(['author_id'])
            ->distinct(['author_id'])
            ->from(['a' => 'articles'])
            ->order(['author_id' => 'ASC'])
            ->execute();
        $this->assertCount(2, $result);
        $results = $result->fetchAll('assoc');
        $this->assertEquals(
            [3, 1],
            collection($results)->sortBy('author_id')->extract('author_id')->toList()
        );

        $query = new Query($this->connection);
        $result = $query
            ->select(['author_id'])
            ->distinct('author_id')
            ->from(['a' => 'articles'])
            ->order(['author_id' => 'ASC'])
            ->execute();
        $this->assertCount(2, $result);
        $results = $result->fetchAll('assoc');
        $this->assertEquals(
            [3, 1],
            collection($results)->sortBy('author_id')->extract('author_id')->toList()
        );
    }

    /**
     * Test use of modifiers in the query
     *
     * Testing the generated SQL since the modifiers are usually different per driver
     */
    public function testSelectModifiers(): void
    {
        $query = new Query($this->connection);
        $result = $query
            ->select(['city', 'state', 'country'])
            ->from(['addresses'])
            ->modifier('DISTINCTROW');
        $this->assertQuotedQuery(
            'SELECT DISTINCTROW <city>, <state>, <country> FROM <addresses>',
            $result->sql(),
            !$this->autoQuote
        );

        $query = new Query($this->connection);
        $result = $query
            ->select(['city', 'state', 'country'])
            ->from(['addresses'])
            ->modifier(['DISTINCTROW', 'SQL_NO_CACHE']);
        $this->assertQuotedQuery(
            'SELECT DISTINCTROW SQL_NO_CACHE <city>, <state>, <country> FROM <addresses>',
            $result->sql(),
            !$this->autoQuote
        );

        $query = new Query($this->connection);
        $result = $query
            ->select(['city', 'state', 'country'])
            ->from(['addresses'])
            ->modifier('DISTINCTROW')
            ->modifier('SQL_NO_CACHE');
        $this->assertQuotedQuery(
            'SELECT DISTINCTROW SQL_NO_CACHE <city>, <state>, <country> FROM <addresses>',
            $result->sql(),
            true
        );

        $query = new Query($this->connection);
        $result = $query
            ->select(['city', 'state', 'country'])
            ->from(['addresses'])
            ->modifier(['TOP 10']);
        $this->assertQuotedQuery(
            'SELECT TOP 10 <city>, <state>, <country> FROM <addresses>',
            $result->sql(),
            !$this->autoQuote
        );

        $query = new Query($this->connection);
        $result = $query
            ->select(['city', 'state', 'country'])
            ->from(['addresses'])
            ->modifier($query->newExpr('EXPRESSION'));
        $this->assertQuotedQuery(
            'SELECT EXPRESSION <city>, <state>, <country> FROM <addresses>',
            $result->sql(),
            !$this->autoQuote
        );
    }

    /**
     * Tests that having() behaves pretty much the same as the where() method
     */
    public function testSelectHaving(): void
    {
        $query = new Query($this->connection);
        $result = $query
            ->select(['total' => 'count(author_id)', 'author_id'])
            ->from('articles')
            ->join(['table' => 'authors', 'alias' => 'a', 'conditions' => $query->newExpr()->equalFields('author_id', 'a.id')])
            ->group('author_id')
            ->having(['count(author_id) <' => 2], ['count(author_id)' => 'integer'])
            ->execute();
        $expected = [['total' => 1, 'author_id' => 3]];
        $this->assertEquals($expected, $result->fetchAll('assoc'));

        $result = $query->having(['count(author_id)' => 2], ['count(author_id)' => 'integer'], true)
            ->execute();
        $expected = [['total' => 2, 'author_id' => 1]];
        $this->assertEquals($expected, $result->fetchAll('assoc'));

        $result = $query->having(function ($e) {
            return $e->add('count(author_id) = 1 + 1');
        }, [], true)
            ->execute();
        $expected = [['total' => 2, 'author_id' => 1]];
        $this->assertEquals($expected, $result->fetchAll('assoc'));
    }

    /**
     * Tests that Query::andHaving() can be used to concatenate conditions with AND
     * in the having clause
     */
    public function testSelectAndHaving(): void
    {
        $query = new Query($this->connection);
        $result = $query
            ->select(['total' => 'count(author_id)', 'author_id'])
            ->from('articles')
            ->join(['table' => 'authors', 'alias' => 'a', 'conditions' => $query->newExpr()->equalFields('author_id', 'a.id')])
            ->group('author_id')
            ->having(['count(author_id) >' => 2], ['count(author_id)' => 'integer'])
            ->andHaving(['count(author_id) <' => 2], ['count(author_id)' => 'integer'])
            ->execute();
        $this->assertCount(0, $result);

        $query = new Query($this->connection);
        $result = $query
            ->select(['total' => 'count(author_id)', 'author_id'])
            ->from('articles')
            ->join(['table' => 'authors', 'alias' => 'a', 'conditions' => $query->newExpr()->equalFields('author_id', 'a.id')])
            ->group('author_id')
            ->having(['count(author_id)' => 2], ['count(author_id)' => 'integer'])
            ->andHaving(['count(author_id) >' => 1], ['count(author_id)' => 'integer'])
            ->execute();
        $expected = [['total' => 2, 'author_id' => 1]];
        $this->assertEquals($expected, $result->fetchAll('assoc'));

        $query = new Query($this->connection);
        $result = $query
            ->select(['total' => 'count(author_id)', 'author_id'])
            ->from('articles')
            ->join(['table' => 'authors', 'alias' => 'a', 'conditions' => $query->newExpr()->equalFields('author_id', 'a.id')])
            ->group('author_id')
            ->andHaving(function ($e) {
                return $e->add('count(author_id) = 2 - 1');
            })
            ->execute();
        $expected = [['total' => 1, 'author_id' => 3]];
        $this->assertEquals($expected, $result->fetchAll('assoc'));
    }

    /**
     * Test having casing with string expressions
     */
    public function testHavingAliasCasingStringExpression(): void
    {
        $this->skipIf($this->autoQuote, 'Does not work when autoquoting is enabled.');
        $query = new Query($this->connection);
        $query
            ->select(['id'])
            ->from(['Authors' => 'authors'])
            ->where([
                'FUNC( Authors.id) =' => 1,
                'FUNC( Authors.id) IS NOT' => null,
            ])
            ->having(['COUNT(DISTINCT Authors.id) =' => 1]);

        $this->assertSame(
            'SELECT id FROM authors Authors WHERE ' .
            '(FUNC( Authors.id) = :c0 AND (FUNC( Authors.id)) IS NOT NULL) ' .
            'HAVING COUNT(DISTINCT Authors.id) = :c1',
            trim($query->sql())
        );
    }

    /**
     * Tests selecting rows using a limit clause
     */
    public function testSelectLimit(): void
    {
        $query = new Query($this->connection);
        $result = $query->select('id')->from('articles')->limit(1)->execute();
        $this->assertCount(1, $result);

        $query = new Query($this->connection);
        $result = $query->select('id')->from('articles')->limit(2)->execute();
        $this->assertCount(2, $result);
    }

    /**
     * Tests selecting rows with string offset/limit
     */
    public function testSelectLimitInvalid(): void
    {
        $query = new Query($this->connection);
        $this->expectException(InvalidArgumentException::class);
        $query->select('id')->from('comments')
            ->limit('1 --more')
            ->order(['id' => 'ASC'])
            ->execute();
    }

    /**
     * Tests selecting rows with string offset/limit
     */
    public function testSelectOffsetInvalid(): void
    {
        $query = new Query($this->connection);
        $this->expectException(InvalidArgumentException::class);
        $query->select('id')->from('comments')
            ->offset('1 --more')
            ->order(['id' => 'ASC'])
            ->execute();
    }

    /**
     * Tests selecting rows combining a limit and offset clause
     */
    public function testSelectOffset(): void
    {
        $query = new Query($this->connection);
        $result = $query->select('id')->from('comments')
            ->limit(1)
            ->offset(0)
            ->order(['id' => 'ASC'])
            ->execute();
        $this->assertCount(1, $result);
        $this->assertEquals(['id' => 1], $result->fetch('assoc'));

        $query = new Query($this->connection);
        $result = $query->select('id')->from('comments')
            ->limit(1)
            ->offset(1)
            ->execute();
        $this->assertCount(1, $result);
        $this->assertEquals(['id' => 2], $result->fetch('assoc'));

        $query = new Query($this->connection);
        $result = $query->select('id')->from('comments')
            ->limit(1)
            ->offset(2)
            ->execute();
        $this->assertCount(1, $result);
        $this->assertEquals(['id' => 3], $result->fetch('assoc'));

        $query = new Query($this->connection);
        $result = $query->select('id')->from('articles')
            ->order(['id' => 'DESC'])
            ->limit(1)
            ->offset(0)
            ->execute();
        $this->assertCount(1, $result);
        $this->assertEquals(['id' => 3], $result->fetch('assoc'));

        $result = $query->limit(2)->offset(1)->execute();
        $this->assertCount(2, $result);
        $this->assertEquals(['id' => 2], $result->fetch('assoc'));
        $this->assertEquals(['id' => 1], $result->fetch('assoc'));

        $query = new Query($this->connection);
        $query->select('id')->from('comments')
            ->limit(1)
            ->offset(1)
            ->execute()
            ->closeCursor();

        $reflect = new ReflectionProperty($query, '_dirty');
        $reflect->setAccessible(true);
        $this->assertFalse($reflect->getValue($query));

        $query->offset(2);
        $this->assertTrue($reflect->getValue($query));
    }

    /**
     * Test Pages number.
     */
    public function testPageShouldStartAtOne(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Pages must start at 1.');

        $query = new Query($this->connection);
        $result = $query->from('comments')->page(0);
    }

    /**
     * Test selecting rows using the page() method.
     */
    public function testSelectPage(): void
    {
        $query = new Query($this->connection);
        $result = $query->select('id')->from('comments')
            ->limit(1)
            ->page(1)
            ->execute();

        $this->assertCount(1, $result);
        $this->assertEquals(['id' => 1], $result->fetch('assoc'));

        $query = new Query($this->connection);
        $result = $query->select('id')->from('comments')
            ->limit(1)
            ->page(2)
            ->order(['id' => 'asc'])
            ->execute();
        $this->assertCount(1, $result);
        $this->assertEquals(['id' => 2], $result->fetch('assoc'));

        $query = new Query($this->connection);
        $query->select('id')->from('comments')->page(3, 10);
        $this->assertEquals(10, $query->clause('limit'));
        $this->assertEquals(20, $query->clause('offset'));

        $query = new Query($this->connection);
        $query->select('id')->from('comments')->page(1);
        $this->assertEquals(25, $query->clause('limit'));
        $this->assertEquals(0, $query->clause('offset'));

        $query->select('id')->from('comments')->page(2);
        $this->assertEquals(25, $query->clause('limit'));
        $this->assertEquals(25, $query->clause('offset'));
    }

    /**
     * Test selecting rows using the page() method and ordering the results
     * by a calculated column.
     */
    public function testSelectPageWithOrder(): void
    {
        $query = new Query($this->connection);
        $result = $query
            ->select([
                'id',
                'ids_added' => $query->newExpr()->add('(user_id + article_id)'),
            ])
            ->from('comments')
            ->order(['ids_added' => 'asc'])
            ->limit(2)
            ->page(3)
            ->execute();
        $this->assertCount(2, $result);
        $this->assertEquals(
            [
                ['id' => '6', 'ids_added' => '4'],
                ['id' => '2', 'ids_added' => '5'],
            ],
            $result->fetchAll('assoc')
        );
    }

    /**
     * Tests that Query objects can be included inside the select clause
     * and be used as a normal field, including binding any passed parameter
     */
    public function testSubqueryInSelect(): void
    {
        $query = new Query($this->connection);
        $subquery = (new Query($this->connection))
            ->select('name')
            ->from(['b' => 'authors'])
            ->where([$query->newExpr()->equalFields('b.id', 'a.id')]);
        $result = $query
            ->select(['id', 'name' => $subquery])
            ->from(['a' => 'comments'])->execute();

        $expected = [
            ['id' => 1, 'name' => 'mariano'],
            ['id' => 2, 'name' => 'nate'],
            ['id' => 3, 'name' => 'larry'],
            ['id' => 4, 'name' => 'garrett'],
            ['id' => 5, 'name' => null],
            ['id' => 6, 'name' => null],
        ];
        $this->assertEquals($expected, $result->fetchAll('assoc'));

        $query = new Query($this->connection);
        $subquery = (new Query($this->connection))
            ->select('name')
            ->from(['b' => 'authors'])
            ->where(['name' => 'mariano'], ['name' => 'string']);
        $result = $query
            ->select(['id', 'name' => $subquery])
            ->from(['a' => 'articles'])->execute();

        $expected = [
            ['id' => 1, 'name' => 'mariano'],
            ['id' => 2, 'name' => 'mariano'],
            ['id' => 3, 'name' => 'mariano'],
        ];
        $this->assertEquals($expected, $result->fetchAll('assoc'));
    }

    /**
     * Tests that Query objects can be included inside the from clause
     * and be used as a normal table, including binding any passed parameter
     */
    public function testSuqueryInFrom(): void
    {
        $query = new Query($this->connection);
        $subquery = (new Query($this->connection))
            ->select(['id', 'comment'])
            ->from('comments')
            ->where(['created >' => new DateTime('2007-03-18 10:45:23')], ['created' => 'datetime']);
        $result = $query
            ->select(['say' => 'comment'])
            ->from(['b' => $subquery])
            ->where(['id !=' => 3])
            ->execute();

        $expected = [
            ['say' => 'Second Comment for First Article'],
            ['say' => 'Fourth Comment for First Article'],
            ['say' => 'First Comment for Second Article'],
            ['say' => 'Second Comment for Second Article'],
        ];
        $this->assertEquals($expected, $result->fetchAll('assoc'));
    }

    /**
     * Tests that Query objects can be included inside the where clause
     * and be used as a normal condition, including binding any passed parameter
     */
    public function testSubqueryInWhere(): void
    {
        $query = new Query($this->connection);
        $subquery = (new Query($this->connection))
            ->select(['id'])
            ->from('authors')
            ->where(['id' => 1]);
        $result = $query
            ->select(['name'])
            ->from(['authors'])
            ->where(['id !=' => $subquery])
            ->execute();

        $expected = [
            ['name' => 'nate'],
            ['name' => 'larry'],
            ['name' => 'garrett'],
        ];
        $this->assertEquals($expected, $result->fetchAll('assoc'));
        $result->closeCursor();

        $query = new Query($this->connection);
        $subquery = (new Query($this->connection))
            ->select(['id'])
            ->from('comments')
            ->where(['created >' => new DateTime('2007-03-18 10:45:23')], ['created' => 'datetime']);
        $result = $query
            ->select(['name'])
            ->from(['authors'])
            ->where(['id not in' => $subquery])
            ->execute();

        $expected = [
            ['name' => 'mariano'],
        ];
        $this->assertEquals($expected, $result->fetchAll('assoc'));
        $result->closeCursor();
    }

    /**
     * Tests that Query objects can be included inside the where clause
     * and be used as a EXISTS and NOT EXISTS conditions
     */
    public function testSubqueryExistsWhere(): void
    {
        $query = new Query($this->connection);
        $subQuery = (new Query($this->connection))
            ->select(['id'])
            ->from('articles')
            ->where(function ($exp) {
                return $exp->equalFields('authors.id', 'articles.author_id');
            });
        $result = $query
            ->select(['id'])
            ->from('authors')
            ->where(function ($exp) use ($subQuery) {
                return $exp->exists($subQuery);
            })
            ->execute();
        $this->assertCount(2, $result);
        $this->assertEquals(['id' => 1], $result->fetch('assoc'));
        $this->assertEquals(['id' => 3], $result->fetch('assoc'));

        $query = new Query($this->connection);
        $subQuery = (new Query($this->connection))
            ->select(['id'])
            ->from('articles')
            ->where(function ($exp) {
                return $exp->equalFields('authors.id', 'articles.author_id');
            });
        $result = $query
            ->select(['id'])
            ->from('authors')
            ->where(function ($exp) use ($subQuery) {
                return $exp->notExists($subQuery);
            })
            ->execute();
        $this->assertCount(2, $result);
        $this->assertEquals(['id' => 2], $result->fetch('assoc'));
        $this->assertEquals(['id' => 4], $result->fetch('assoc'));
    }

    /**
     * Tests that it is possible to use a subquery in a join clause
     */
    public function testSubqueryInJoin(): void
    {
        $subquery = (new Query($this->connection))->select('*')->from('authors');

        $query = new Query($this->connection);
        $result = $query
            ->select(['title', 'name'])
            ->from('articles')
            ->join(['b' => $subquery])
            ->execute();
        $this->assertCount(self::ARTICLE_COUNT * self::AUTHOR_COUNT, $result, 'Cross join causes multiplication');
        $result->closeCursor();

        $subquery->where(['id' => 1]);
        $result = $query->execute();
        $this->assertCount(3, $result);
        $result->closeCursor();

        $query->join(['b' => ['table' => $subquery, 'conditions' => [$query->newExpr()->equalFields('b.id', 'articles.id')]]], [], true);
        $result = $query->execute();
        $this->assertCount(1, $result);
        $result->closeCursor();
    }

    /**
     * Tests that it is possible to one or multiple UNION statements in a query
     */
    public function testUnion(): void
    {
        $union = (new Query($this->connection))->select(['id', 'title'])->from(['a' => 'articles']);
        $query = new Query($this->connection);
        $result = $query->select(['id', 'comment'])
            ->from(['c' => 'comments'])
            ->union($union)
            ->execute();
        $this->assertCount(self::COMMENT_COUNT + self::ARTICLE_COUNT, $result);
        $rows = $result->fetchAll();
        $result->closeCursor();

        $union->select(['foo' => 'id', 'bar' => 'title']);
        $union = (new Query($this->connection))
            ->select(['id', 'name', 'other' => 'id', 'nameish' => 'name'])
            ->from(['b' => 'authors'])
            ->where(['id ' => 1])
            ->order(['id' => 'desc']);

        $query->select(['foo' => 'id', 'bar' => 'comment'])->union($union);
        $result = $query->execute();
        $this->assertCount(self::COMMENT_COUNT + self::AUTHOR_COUNT, $result);
        $this->assertNotEquals($rows, $result->fetchAll());
        $result->closeCursor();

        $union = (new Query($this->connection))
            ->select(['id', 'title'])
            ->from(['c' => 'articles']);
        $query->select(['id', 'comment'], true)->union($union, true);
        $result = $query->execute();
        $this->assertCount(self::COMMENT_COUNT + self::ARTICLE_COUNT, $result);
        $this->assertEquals($rows, $result->fetchAll());
        $result->closeCursor();
    }

    /**
     * Tests that it is possible to run unions with order statements
     */
    public function testUnionOrderBy(): void
    {
        $this->skipIf(
            ($this->connection->getDriver() instanceof Sqlite ||
            $this->connection->getDriver() instanceof Sqlserver),
            'Driver does not support ORDER BY in UNIONed queries.'
        );
        $union = (new Query($this->connection))
            ->select(['id', 'title'])
            ->from(['a' => 'articles'])
            ->order(['a.id' => 'asc']);

        $query = new Query($this->connection);
        $result = $query->select(['id', 'comment'])
            ->from(['c' => 'comments'])
            ->order(['c.id' => 'asc'])
            ->union($union)
            ->execute();
        $this->assertCount(self::COMMENT_COUNT + self::ARTICLE_COUNT, $result);

        $rows = $result->fetchAll();
        $this->assertCount(self::COMMENT_COUNT + self::ARTICLE_COUNT, $result);
    }

    /**
     * Tests that UNION ALL can be built by setting the second param of union() to true
     */
    public function testUnionAll(): void
    {
        $union = (new Query($this->connection))->select(['id', 'title'])->from(['a' => 'articles']);
        $query = new Query($this->connection);
        $result = $query->select(['id', 'comment'])
            ->from(['c' => 'comments'])
            ->union($union)
            ->execute();
        $this->assertCount(self::ARTICLE_COUNT + self::COMMENT_COUNT, $result);
        $rows = $result->fetchAll();
        $result->closeCursor();

        $union->select(['foo' => 'id', 'bar' => 'title']);
        $union = (new Query($this->connection))
            ->select(['id', 'name', 'other' => 'id', 'nameish' => 'name'])
            ->from(['b' => 'authors'])
            ->where(['id ' => 1])
            ->order(['id' => 'desc']);

        $query->select(['foo' => 'id', 'bar' => 'comment'])->unionAll($union);
        $result = $query->execute();
        $this->assertCount(1 + self::COMMENT_COUNT + self::ARTICLE_COUNT, $result);
        $this->assertNotEquals($rows, $result->fetchAll());
        $result->closeCursor();
    }

    /**
     * Tests stacking decorators for results and resetting the list of decorators
     */
    public function testDecorateResults(): void
    {
        $query = new Query($this->connection);
        $result = $query
            ->select(['id', 'title'])
            ->from('articles')
            ->order(['id' => 'ASC'])
            ->decorateResults(function ($row) {
                $row['modified_id'] = $row['id'] + 1;

                return $row;
            })
            ->execute();

        while ($row = $result->fetch('assoc')) {
            $this->assertEquals($row['id'] + 1, $row['modified_id']);
        }

        $result = $query->decorateResults(function ($row) {
            $row['modified_id']--;

            return $row;
        })->execute();

        while ($row = $result->fetch('assoc')) {
            $this->assertEquals($row['id'], $row['modified_id']);
        }
        $result->closeCursor();

        $result = $query
            ->decorateResults(function ($row) {
                $row['foo'] = 'bar';

                return $row;
            }, true)
            ->execute();

        while ($row = $result->fetch('assoc')) {
            $this->assertSame('bar', $row['foo']);
            $this->assertArrayNotHasKey('modified_id', $row);
        }

        $results = $query->decorateResults(null, true)->execute();
        while ($row = $results->fetch('assoc')) {
            $this->assertArrayNotHasKey('foo', $row);
            $this->assertArrayNotHasKey('modified_id', $row);
        }
        $results->closeCursor();
    }

    /**
     * Test a basic delete using from()
     */
    public function testDeleteWithFrom(): void
    {
        $query = new Query($this->connection);

        $query->delete()
            ->from('authors')
            ->where('1 = 1');

        $result = $query->sql();
        $this->assertQuotedQuery('DELETE FROM <authors>', $result, !$this->autoQuote);

        $result = $query->execute();
        $this->assertInstanceOf('Cake\Database\StatementInterface', $result);
        $this->assertCount(self::AUTHOR_COUNT, $result);
        $result->closeCursor();
    }

    /**
     * Test delete with from and alias.
     */
    public function testDeleteWithAliasedFrom(): void
    {
        $query = new Query($this->connection);

        $query->delete()
            ->from(['a ' => 'authors'])
            ->where(['a.id !=' => 99]);

        $result = $query->sql();
        $this->assertQuotedQuery('DELETE FROM <authors> WHERE <id> != :c0', $result, !$this->autoQuote);

        $result = $query->execute();
        $this->assertInstanceOf('Cake\Database\StatementInterface', $result);
        $this->assertCount(self::AUTHOR_COUNT, $result);
        $result->closeCursor();
    }

    /**
     * Test a basic delete with no from() call.
     */
    public function testDeleteNoFrom(): void
    {
        $query = new Query($this->connection);

        $query->delete('authors')
            ->where('1 = 1');

        $result = $query->sql();
        $this->assertQuotedQuery('DELETE FROM <authors>', $result, !$this->autoQuote);

        $result = $query->execute();
        $this->assertInstanceOf('Cake\Database\StatementInterface', $result);
        $this->assertCount(self::AUTHOR_COUNT, $result);
        $result->closeCursor();
    }

    /**
     * Tests that delete queries that contain joins do trigger a notice,
     * warning about possible incompatibilities with aliases being removed
     * from the conditions.
     */
    public function testDeleteRemovingAliasesCanBreakJoins(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Aliases are being removed from conditions for UPDATE/DELETE queries, this can break references to joined tables.');
        $query = new Query($this->connection);

        $query
            ->delete('authors')
            ->from(['a ' => 'authors'])
            ->innerJoin('articles')
            ->where(['a.id' => 1]);

        $query->sql();
    }

    /**
     * Tests that aliases are stripped from delete query conditions
     * where possible.
     */
    public function testDeleteStripAliasesFromConditions(): void
    {
        $query = new Query($this->connection);

        $query
            ->delete()
            ->from(['a' => 'authors'])
            ->where([
                'OR' => [
                    'a.id' => 1,
                    'a.name IS' => null,
                    'a.email IS NOT' => null,
                    'AND' => [
                        'b.name NOT IN' => ['foo', 'bar'],
                        'OR' => [
                            $query->newExpr()->eq(new IdentifierExpression('c.name'), 'zap'),
                            'd.name' => 'baz',
                            (new Query($this->connection))->select(['e.name'])->where(['e.name' => 'oof']),
                        ],
                    ],
                ],
            ]);

        $this->assertQuotedQuery(
            'DELETE FROM <authors> WHERE \(' .
                '<id> = :c0 OR \(<name>\) IS NULL OR \(<email>\) IS NOT NULL OR \(' .
                    '<name> not in \(:c1,:c2\) AND \(' .
                        '<name> = :c3 OR <name> = :c4 OR \(SELECT <e>\.<name> WHERE <e>\.<name> = :c5\)' .
                    '\)' .
                '\)' .
            '\)',
            $query->sql(),
            !$this->autoQuote
        );
    }

    /**
     * Test setting select() & delete() modes.
     */
    public function testSelectAndDeleteOnSameQuery(): void
    {
        $query = new Query($this->connection);
        $result = $query->select()
            ->delete('authors')
            ->where('1 = 1');
        $result = $query->sql();

        $this->assertQuotedQuery('DELETE FROM <authors>', $result, !$this->autoQuote);
        $this->assertStringContainsString(' WHERE 1 = 1', $result);
    }

    /**
     * Test a simple update.
     */
    public function testUpdateSimple(): void
    {
        $query = new Query($this->connection);
        $query->update('authors')
            ->set('name', 'mark')
            ->where(['id' => 1]);
        $result = $query->sql();
        $this->assertQuotedQuery('UPDATE <authors> SET <name> = :', $result, !$this->autoQuote);

        $result = $query->execute();
        $this->assertCount(1, $result);
        $result->closeCursor();
    }

    /**
     * Test update with type checking
     * by passing an array as table arg
     */
    public function testUpdateArgTypeChecking(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $query = new Query($this->connection);
        $query->update(['Articles']);
    }

    /**
     * Test update with multiple fields.
     */
    public function testUpdateMultipleFields(): void
    {
        $query = new Query($this->connection);
        $query->update('articles')
            ->set('title', 'mark', 'string')
            ->set('body', 'some text', 'string')
            ->where(['id' => 1]);
        $result = $query->sql();

        $this->assertQuotedQuery(
            'UPDATE <articles> SET <title> = :c0 , <body> = :c1',
            $result,
            !$this->autoQuote
        );

        $this->assertQuotedQuery(' WHERE <id> = :c2$', $result, !$this->autoQuote);
        $result = $query->execute();
        $this->assertCount(1, $result);
        $result->closeCursor();
    }

    /**
     * Test updating multiple fields with an array.
     */
    public function testUpdateMultipleFieldsArray(): void
    {
        $query = new Query($this->connection);
        $query->update('articles')
            ->set([
                'title' => 'mark',
                'body' => 'some text',
            ], ['title' => 'string', 'body' => 'string'])
            ->where(['id' => 1]);
        $result = $query->sql();

        $this->assertQuotedQuery(
            'UPDATE <articles> SET <title> = :c0 , <body> = :c1',
            $result,
            !$this->autoQuote
        );
        $this->assertQuotedQuery('WHERE <id> = :', $result, !$this->autoQuote);

        $result = $query->execute();
        $this->assertCount(1, $result);
        $result->closeCursor();
    }

    /**
     * Test updates with an expression.
     */
    public function testUpdateWithExpression(): void
    {
        $query = new Query($this->connection);

        $expr = $query->newExpr()->equalFields('article_id', 'user_id');

        $query->update('comments')
            ->set($expr)
            ->where(['id' => 1]);
        $result = $query->sql();

        $this->assertQuotedQuery(
            'UPDATE <comments> SET <article_id> = <user_id> WHERE <id> = :',
            $result,
            !$this->autoQuote
        );

        $result = $query->execute();
        $this->assertCount(1, $result);
        $result->closeCursor();
    }

    /**
     * Tests update with subquery that references itself
     */
    public function testUpdateSubquery(): void
    {
        $this->skipIf($this->connection->getDriver() instanceof Mysql);

        $subquery = new Query($this->connection);
        $subquery
            ->select('created')
            ->from(['c' => 'comments'])
            ->where(['c.id' => new IdentifierExpression('comments.id')]);

        $query = new Query($this->connection);
        $query->update('comments')
            ->set('updated', $subquery);

        $this->assertEqualsSql(
            'UPDATE comments SET updated = (SELECT created FROM comments c WHERE c.id = comments.id)',
            $query->sql(new ValueBinder())
        );

        $result = $query->execute();
        $this->assertCount(6, $result);
        $result->closeCursor();

        $result = (new Query($this->connection))->select(['created', 'updated'])->from('comments')->execute();
        foreach ($result->fetchAll('assoc') as $row) {
            $this->assertSame($row['created'], $row['updated']);
        }
        $result->closeCursor();
    }

    /**
     * Test update with array fields and types.
     */
    public function testUpdateArrayFields(): void
    {
        $query = new Query($this->connection);
        $date = new DateTime();
        $query->update('comments')
            ->set(['comment' => 'mark', 'created' => $date], ['created' => 'date'])
            ->where(['id' => 1]);
        $result = $query->sql();

        $this->assertQuotedQuery(
            'UPDATE <comments> SET <comment> = :c0 , <created> = :c1',
            $result,
            !$this->autoQuote
        );

        $this->assertQuotedQuery(' WHERE <id> = :c2$', $result, !$this->autoQuote);
        $result = $query->execute();
        $this->assertCount(1, $result);

        $query = new Query($this->connection);
        $result = $query->select('created')->from('comments')->where(['id' => 1])->execute();
        $result = $result->fetchAll('assoc')[0]['created'];
        $this->assertStringStartsWith($date->format('Y-m-d'), $result);
    }

    /**
     * Test update with callable in set
     */
    public function testUpdateSetCallable(): void
    {
        $query = new Query($this->connection);
        $date = new DateTime();
        $query->update('comments')
            ->set(function ($exp) use ($date) {
                return $exp
                    ->eq('comment', 'mark')
                    ->eq('created', $date, 'date');
            })
            ->where(['id' => 1]);
        $result = $query->sql();

        $this->assertQuotedQuery(
            'UPDATE <comments> SET <comment> = :c0 , <created> = :c1',
            $result,
            !$this->autoQuote
        );

        $this->assertQuotedQuery(' WHERE <id> = :c2$', $result, !$this->autoQuote);
        $result = $query->execute();
        $this->assertCount(1, $result);
    }

    /**
     * Tests that aliases are stripped from update query conditions
     * where possible.
     */
    public function testUpdateStripAliasesFromConditions(): void
    {
        $query = new Query($this->connection);

        $query
            ->update('authors')
            ->set(['name' => 'name'])
            ->where([
                'OR' => [
                    'a.id' => 1,
                    'a.name IS' => null,
                    'a.email IS NOT' => null,
                    'AND' => [
                        'b.name NOT IN' => ['foo', 'bar'],
                        'OR' => [
                            $query->newExpr()->eq(new IdentifierExpression('c.name'), 'zap'),
                            'd.name' => 'baz',
                            (new Query($this->connection))->select(['e.name'])->where(['e.name' => 'oof']),
                        ],
                    ],
                ],
            ]);

        $this->assertQuotedQuery(
            'UPDATE <authors> SET <name> = :c0 WHERE \(' .
                '<id> = :c1 OR \(<name>\) IS NULL OR \(<email>\) IS NOT NULL OR \(' .
                    '<name> not in \(:c2,:c3\) AND \(' .
                        '<name> = :c4 OR <name> = :c5 OR \(SELECT <e>\.<name> WHERE <e>\.<name> = :c6\)' .
                    '\)' .
                '\)' .
            '\)',
            $query->sql(),
            !$this->autoQuote
        );
    }

    /**
     * Tests that update queries that contain joins do trigger a notice,
     * warning about possible incompatibilities with aliases being removed
     * from the conditions.
     */
    public function testUpdateRemovingAliasesCanBreakJoins(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Aliases are being removed from conditions for UPDATE/DELETE queries, this can break references to joined tables.');
        $query = new Query($this->connection);

        $query
            ->update('authors')
            ->set(['name' => 'name'])
            ->innerJoin('articles')
            ->where(['a.id' => 1]);

        $query->sql();
    }

    /**
     * You cannot call values() before insert() it causes all sorts of pain.
     */
    public function testInsertValuesBeforeInsertFailure(): void
    {
        $this->expectException(DatabaseException::class);
        $query = new Query($this->connection);
        $query->select('*')->values([
            'id' => 1,
            'title' => 'mark',
            'body' => 'test insert',
        ]);
    }

    /**
     * Inserting nothing should not generate an error.
     */
    public function testInsertNothing(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('At least 1 column is required to perform an insert.');
        $query = new Query($this->connection);
        $query->insert([]);
    }

    /**
     * Test insert() with no into()
     */
    public function testInsertNoInto(): void
    {
        $this->expectException(DatabaseException::class);
        $this->expectExceptionMessage('Could not compile insert query. No table was specified');
        $query = new Query($this->connection);
        $query->insert(['title', 'body'])->sql();
    }

    /**
     * Test insert overwrites values
     */
    public function testInsertOverwritesValues(): void
    {
        $query = new Query($this->connection);
        $query->insert(['title', 'body'])
            ->insert(['title'])
            ->into('articles')
            ->values([
                'title' => 'mark',
            ]);

        $result = $query->sql();
        $this->assertQuotedQuery(
            'INSERT INTO <articles> \(<title>\) (OUTPUT INSERTED\.\* )?' .
            'VALUES \(:c0\)',
            $result,
            !$this->autoQuote
        );
    }

    /**
     * Test inserting a single row.
     */
    public function testInsertSimple(): void
    {
        $query = new Query($this->connection);
        $query->insert(['title', 'body'])
            ->into('articles')
            ->values([
                'title' => 'mark',
                'body' => 'test insert',
            ]);
        $result = $query->sql();
        $this->assertQuotedQuery(
            'INSERT INTO <articles> \(<title>, <body>\) (OUTPUT INSERTED\.\* )?' .
            'VALUES \(:c0, :c1\)',
            $result,
            !$this->autoQuote
        );

        $result = $query->execute();
        $result->closeCursor();

        //PDO_SQLSRV returns -1 for successful inserts when using INSERT ... OUTPUT
        if (!$this->connection->getDriver() instanceof Sqlserver) {
            $this->assertCount(1, $result, '1 row should be inserted');
        }

        $expected = [
            [
                'id' => 4,
                'author_id' => null,
                'title' => 'mark',
                'body' => 'test insert',
                'published' => 'N',
            ],
        ];
        $this->assertTable('articles', 1, $expected, ['id >=' => 4]);
    }

    /**
     * Test insert queries quote integer column names
     */
    public function testInsertQuoteColumns(): void
    {
        $query = new Query($this->connection);
        $query->insert([123])
            ->into('articles')
            ->values([
                '123' => 'mark',
            ]);
        $result = $query->sql();
        $this->assertQuotedQuery(
            'INSERT INTO <articles> \(<123>\) (OUTPUT INSERTED\.\* )?' .
            'VALUES \(:c0\)',
            $result,
            !$this->autoQuote
        );
    }

    /**
     * Test an insert when not all the listed fields are provided.
     * Columns should be matched up where possible.
     */
    public function testInsertSparseRow(): void
    {
        $query = new Query($this->connection);
        $query->insert(['title', 'body'])
            ->into('articles')
            ->values([
                'title' => 'mark',
            ]);
        $result = $query->sql();
        $this->assertQuotedQuery(
            'INSERT INTO <articles> \(<title>, <body>\) (OUTPUT INSERTED\.\* )?' .
            'VALUES \(:c0, :c1\)',
            $result,
            !$this->autoQuote
        );

        $result = $query->execute();
        $result->closeCursor();

        //PDO_SQLSRV returns -1 for successful inserts when using INSERT ... OUTPUT
        if (!$this->connection->getDriver() instanceof Sqlserver) {
            $this->assertCount(1, $result, '1 row should be inserted');
        }

        $expected = [
            [
                'id' => 4,
                'author_id' => null,
                'title' => 'mark',
                'body' => null,
                'published' => 'N',
            ],
        ];
        $this->assertTable('articles', 1, $expected, ['id >=' => 4]);
    }

    /**
     * Test inserting multiple rows with sparse data.
     */
    public function testInsertMultipleRowsSparse(): void
    {
        $query = new Query($this->connection);
        $query->insert(['title', 'body'])
            ->into('articles')
            ->values([
                'body' => 'test insert',
            ])
            ->values([
                'title' => 'jose',
            ]);

        $result = $query->execute();
        $result->closeCursor();

        //PDO_SQLSRV returns -1 for successful inserts when using INSERT ... OUTPUT
        if (!$this->connection->getDriver() instanceof Sqlserver) {
            $this->assertCount(2, $result, '2 rows should be inserted');
        }

        $expected = [
            [
                'id' => 4,
                'author_id' => null,
                'title' => null,
                'body' => 'test insert',
                'published' => 'N',
            ],
            [
                'id' => 5,
                'author_id' => null,
                'title' => 'jose',
                'body' => null,
                'published' => 'N',
            ],
        ];
        $this->assertTable('articles', 2, $expected, ['id >=' => 4]);
    }

    /**
     * Test that INSERT INTO ... SELECT works.
     */
    public function testInsertFromSelect(): void
    {
        $select = (new Query($this->connection))->select(['name', "'some text'", 99])
            ->from('authors')
            ->where(['id' => 1]);

        $query = new Query($this->connection);
        $query->insert(
            ['title', 'body', 'author_id'],
            ['title' => 'string', 'body' => 'string', 'author_id' => 'integer']
        )
        ->into('articles')
        ->values($select);

        $result = $query->sql();
        $this->assertQuotedQuery(
            'INSERT INTO <articles> \(<title>, <body>, <author_id>\) (OUTPUT INSERTED\.\* )?SELECT',
            $result,
            !$this->autoQuote
        );
        $this->assertQuotedQuery(
            'SELECT <name>, \'some text\', 99 FROM <authors>',
            $result,
            !$this->autoQuote
        );
        $result = $query->execute();
        $result->closeCursor();

        //PDO_SQLSRV returns -1 for successful inserts when using INSERT ... OUTPUT
        if (!$this->connection->getDriver() instanceof Sqlserver) {
            $this->assertCount(1, $result);
        }

        $result = (new Query($this->connection))->select('*')
            ->from('articles')
            ->where(['author_id' => 99])
            ->execute();
        $this->assertCount(1, $result);
        $expected = [
            'id' => 4,
            'title' => 'mariano',
            'body' => 'some text',
            'author_id' => 99,
            'published' => 'N',
        ];
        $this->assertEquals($expected, $result->fetch('assoc'));
    }

    /**
     * Test that an exception is raised when mixing query + array types.
     */
    public function testInsertFailureMixingTypesArrayFirst(): void
    {
        $this->expectException(DatabaseException::class);
        $query = new Query($this->connection);
        $query->insert(['name'])
            ->into('articles')
            ->values(['name' => 'mark'])
            ->values(new Query($this->connection));
    }

    /**
     * Test that an exception is raised when mixing query + array types.
     */
    public function testInsertFailureMixingTypesQueryFirst(): void
    {
        $this->expectException(DatabaseException::class);
        $query = new Query($this->connection);
        $query->insert(['name'])
            ->into('articles')
            ->values(new Query($this->connection))
            ->values(['name' => 'mark']);
    }

    /**
     * Test that insert can use expression objects as values.
     */
    public function testInsertExpressionValues(): void
    {
        $query = new Query($this->connection);
        $query->insert(['title', 'author_id'])
            ->into('articles')
            ->values(['title' => $query->newExpr("SELECT 'jose'"), 'author_id' => 99]);

        $result = $query->execute();
        $result->closeCursor();

        //PDO_SQLSRV returns -1 for successful inserts when using INSERT ... OUTPUT
        if (!$this->connection->getDriver() instanceof Sqlserver) {
            $this->assertCount(1, $result);
        }

        $result = (new Query($this->connection))->select('*')
            ->from('articles')
            ->where(['author_id' => 99])
            ->execute();
        $this->assertCount(1, $result);
        $expected = [
            'id' => 4,
            'title' => 'jose',
            'body' => null,
            'author_id' => '99',
            'published' => 'N',
        ];
        $this->assertEquals($expected, $result->fetch('assoc'));

        $subquery = new Query($this->connection);
        $subquery->select(['name'])
            ->from('authors')
            ->where(['id' => 1]);

        $query = new Query($this->connection);
        $query->insert(['title', 'author_id'])
            ->into('articles')
            ->values(['title' => $subquery, 'author_id' => 100]);
        $result = $query->execute();
        $result->closeCursor();
        //PDO_SQLSRV returns -1 for successful inserts when using INSERT ... OUTPUT
        if (!$this->connection->getDriver() instanceof Sqlserver) {
            $this->assertCount(1, $result);
        }

        $result = (new Query($this->connection))->select('*')
            ->from('articles')
            ->where(['author_id' => 100])
            ->execute();
        $this->assertCount(1, $result);
        $expected = [
            'id' => 5,
            'title' => 'mariano',
            'body' => null,
            'author_id' => '100',
            'published' => 'N',
        ];
        $this->assertEquals($expected, $result->fetch('assoc'));
    }

    /**
     * Tests that the identifier method creates an expression object.
     */
    public function testIdentifierExpression(): void
    {
        $query = new Query($this->connection);
        /** @var \Cake\Database\Expression\IdentifierExpression $identifier */
        $identifier = $query->identifier('foo');

        $this->assertInstanceOf(IdentifierExpression::class, $identifier);
        $this->assertSame('foo', $identifier->getIdentifier());
    }

    /**
     * Tests the interface contract of identifier
     */
    public function testIdentifierInterface(): void
    {
        $query = new Query($this->connection);
        $identifier = $query->identifier('description');

        $this->assertInstanceOf(ExpressionInterface::class, $identifier);
        $this->assertSame('description', $identifier->getIdentifier());

        $identifier->setIdentifier('title');
        $this->assertSame('title', $identifier->getIdentifier());
    }

    /**
     * Tests that functions are correctly transformed and their parameters are bound
     *
     * @group FunctionExpression
     */
    public function testSQLFunctions(): void
    {
        $query = new Query($this->connection);
        $result = $query->select(
            function ($q) {
                return ['total' => $q->func()->count('*')];
            }
        )
            ->from('comments')
            ->execute();
        $expected = [['total' => 6]];
        $this->assertEquals($expected, $result->fetchAll('assoc'));

        $query = new Query($this->connection);
        $result = $query->select([
                'c' => $query->func()->concat(['comment' => 'literal', ' is appended']),
            ])
            ->from('comments')
            ->order(['c' => 'ASC'])
            ->limit(1)
            ->execute();
        $expected = [
            ['c' => 'First Comment for First Article is appended'],
        ];
        $this->assertEquals($expected, $result->fetchAll('assoc'));

        $query = new Query($this->connection);
        $result = $query
            ->select(['d' => $query->func()->dateDiff(['2012-01-05', '2012-01-02'])])
            ->execute()
            ->fetchAll('assoc');
        $this->assertEquals(3, abs((int)$result[0]['d']));

        $query = new Query($this->connection);
        $result = $query
            ->select(['d' => $query->func()->now('date')])
            ->execute();

        $result = $result->fetchAll('assoc');
        $this->assertEquals([['d' => date('Y-m-d')]], $result);

        $query = new Query($this->connection);
        $result = $query
            ->select(['d' => $query->func()->now('time')])
            ->execute();

        $this->assertWithinRange(
            date('U'),
            (new DateTime($result->fetchAll('assoc')[0]['d']))->format('U'),
            10
        );

        $query = new Query($this->connection);
        $result = $query
            ->select(['d' => $query->func()->now()])
            ->execute();
        $this->assertWithinRange(
            date('U'),
            (new DateTime($result->fetchAll('assoc')[0]['d']))->format('U'),
            10
        );

        $query = new Query($this->connection);
        $result = $query
            ->select([
                'd' => $query->func()->datePart('day', 'created'),
                'm' => $query->func()->datePart('month', 'created'),
                'y' => $query->func()->datePart('year', 'created'),
                'de' => $query->func()->extract('day', 'created'),
                'me' => $query->func()->extract('month', 'created'),
                'ye' => $query->func()->extract('year', 'created'),
                'wd' => $query->func()->weekday('created'),
                'dow' => $query->func()->dayOfWeek('created'),
                'addDays' => $query->func()->dateAdd('created', 2, 'day'),
                'substractYears' => $query->func()->dateAdd('created', -2, 'year'),
            ])
            ->from('comments')
            ->where(['created' => '2007-03-18 10:45:23'])
            ->execute()
            ->fetchAll('assoc');

        $result[0]['addDays'] = substr($result[0]['addDays'], 0, 10);
        $result[0]['substractYears'] = substr($result[0]['substractYears'], 0, 10);

        if (PHP_VERSION_ID < 80100) {
            $result[0]['m'] = ltrim($result[0]['m'], '0');
            $result[0]['me'] = ltrim($result[0]['me'], '0');
            $expected = [
                'd' => '18',
                'm' => '3',
                'y' => '2007',
                'de' => '18',
                'me' => '3',
                'ye' => '2007',
                'wd' => '1', // Sunday
                'dow' => '1',
                'addDays' => '2007-03-20',
                'substractYears' => '2005-03-18',
            ];
        } else {
            $expected = [
                'd' => 18,
                'm' => 3,
                'y' => 2007,
                'de' => 18,
                'me' => 3,
                'ye' => 2007,
                'wd' => 1, // Sunday
                'dow' => 1,
                'addDays' => '2007-03-20',
                'substractYears' => '2005-03-18',
            ];
        }

        $this->assertSame($expected, $result[0]);
    }

    /**
     * Tests that the values in tuple comparison expression are being bound correctly,
     * specifically for dialects that translate tuple comparisons.
     *
     * @see \Cake\Database\Driver\TupleComparisonTranslatorTrait::_transformTupleComparison()
     * @see \Cake\Database\Driver\Sqlite::_expressionTranslators()
     * @see \Cake\Database\Driver\Sqlserver::_expressionTranslators()
     */
    public function testTupleComparisonValuesAreBeingBoundCorrectly(): void
    {
        $this->skipIf(
            $this->connection->getDriver() instanceof Sqlserver,
            'This test fails sporadically in SQLServer'
        );

        $query = (new Query($this->connection))
            ->select('*')
            ->from('profiles')
            ->where(
                new TupleComparison(
                    ['id', 'user_id'],
                    [[1, 1]],
                    ['integer', 'integer'],
                    'IN'
                )
            );

        $result = $query->execute()->fetch(StatementInterface::FETCH_TYPE_ASSOC);

        $bindings = array_values($query->getValueBinder()->bindings());
        $this->assertCount(2, $bindings);
        $this->assertSame(1, $bindings[0]['value']);
        $this->assertSame('integer', $bindings[0]['type']);
        $this->assertSame(1, $bindings[1]['value']);
        $this->assertSame('integer', $bindings[1]['type']);

        $this->assertSame(1, (int)$result['id']);
        $this->assertSame(1, (int)$result['user_id']);
    }

    /**
     * Tests that the values in tuple comparison expressions are being bound as expected
     * when types are omitted, specifically for dialects that translate tuple comparisons.
     *
     * @see \Cake\Database\Driver\TupleComparisonTranslatorTrait::_transformTupleComparison()
     * @see \Cake\Database\Driver\Sqlite::_expressionTranslators()
     * @see \Cake\Database\Driver\Sqlserver::_expressionTranslators()
     */
    public function testTupleComparisonTypesCanBeOmitted(): void
    {
        $this->skipIf(
            $this->connection->getDriver() instanceof Sqlserver,
            'This test fails sporadically in SQLServer'
        );

        $query = (new Query($this->connection))
            ->select('*')
            ->from('profiles')
            ->where(
                new TupleComparison(
                    ['id', 'user_id'],
                    [[1, 1]],
                    [],
                    'IN'
                )
            );
        $result = $query->execute()->fetch(StatementInterface::FETCH_TYPE_ASSOC);

        $bindings = array_values($query->getValueBinder()->bindings());
        $this->assertCount(2, $bindings);
        $this->assertSame(1, $bindings[0]['value']);
        $this->assertNull($bindings[0]['type']);
        $this->assertSame(1, $bindings[1]['value']);
        $this->assertNull($bindings[1]['type']);

        $this->assertSame(1, (int)$result['id']);
        $this->assertSame(1, (int)$result['user_id']);
    }

    /**
     * Tests that default types are passed to functions accepting a $types param
     */
    public function testDefaultTypes(): void
    {
        $query = new Query($this->connection);
        $this->assertEquals([], $query->getDefaultTypes());
        $types = ['id' => 'integer', 'created' => 'datetime'];
        $this->assertSame($query, $query->setDefaultTypes($types));
        $this->assertSame($types, $query->getDefaultTypes());

        $results = $query->select(['id', 'comment'])
            ->from('comments')
            ->where(['created >=' => new DateTime('2007-03-18 10:55:00')])
            ->execute();
        $expected = [['id' => '6', 'comment' => 'Second Comment for Second Article']];
        $this->assertEquals($expected, $results->fetchAll('assoc'));

        // Now test default can be overridden
        $types = ['created' => 'date'];
        $results = $query
            ->where(['created >=' => new DateTime('2007-03-18 10:50:00')], $types, true)
            ->execute();
        $this->assertCount(6, $results, 'All 6 rows should match.');
    }

    /**
     * Tests parameter binding
     */
    public function testBind(): void
    {
        $query = new Query($this->connection);
        $results = $query->select(['id', 'comment'])
            ->from('comments')
            ->where(['created BETWEEN :foo AND :bar'])
            ->bind(':foo', new DateTime('2007-03-18 10:50:00'), 'datetime')
            ->bind(':bar', new DateTime('2007-03-18 10:52:00'), 'datetime')
            ->execute();
        $expected = [['id' => '4', 'comment' => 'Fourth Comment for First Article']];
        $this->assertEquals($expected, $results->fetchAll('assoc'));

        $query = new Query($this->connection);
        $results = $query->select(['id', 'comment'])
            ->from('comments')
            ->where(['created BETWEEN :foo AND :bar'])
            ->bind(':foo', '2007-03-18 10:50:00')
            ->bind(':bar', '2007-03-18 10:52:00')
            ->execute();
        $this->assertEquals($expected, $results->fetchAll('assoc'));
    }

    /**
     * Test that epilog() will actually append a string to a select query
     */
    public function testAppendSelect(): void
    {
        $query = new Query($this->connection);
        $sql = $query
            ->select(['id', 'title'])
            ->from('articles')
            ->where(['id' => 1])
            ->epilog('FOR UPDATE')
            ->sql();
        $this->assertStringContainsString('SELECT', $sql);
        $this->assertStringContainsString('FROM', $sql);
        $this->assertStringContainsString('WHERE', $sql);
        $this->assertSame(' FOR UPDATE', substr($sql, -11));
    }

    /**
     * Test that epilog() will actually append a string to an insert query
     */
    public function testAppendInsert(): void
    {
        $query = new Query($this->connection);
        $sql = $query
            ->insert(['id', 'title'])
            ->into('articles')
            ->values([1, 'a title'])
            ->epilog('RETURNING id')
            ->sql();
        $this->assertStringContainsString('INSERT', $sql);
        $this->assertStringContainsString('INTO', $sql);
        $this->assertStringContainsString('VALUES', $sql);
        $this->assertSame(' RETURNING id', substr($sql, -13));
    }

    /**
     * Test that epilog() will actually append a string to an update query
     */
    public function testAppendUpdate(): void
    {
        $query = new Query($this->connection);
        $sql = $query
            ->update('articles')
            ->set(['title' => 'foo'])
            ->where(['id' => 1])
            ->epilog('RETURNING id')
            ->sql();
        $this->assertStringContainsString('UPDATE', $sql);
        $this->assertStringContainsString('SET', $sql);
        $this->assertStringContainsString('WHERE', $sql);
        $this->assertSame(' RETURNING id', substr($sql, -13));
    }

    /**
     * Test that epilog() will actually append a string to a delete query
     */
    public function testAppendDelete(): void
    {
        $query = new Query($this->connection);
        $sql = $query
            ->delete('articles')
            ->where(['id' => 1])
            ->epilog('RETURNING id')
            ->sql();
        $this->assertStringContainsString('DELETE FROM', $sql);
        $this->assertStringContainsString('WHERE', $sql);
        $this->assertSame(' RETURNING id', substr($sql, -13));
    }

    /**
     * Tests automatic identifier quoting in the select clause
     */
    public function testQuotingSelectFieldsAndAlias(): void
    {
        $this->connection->getDriver()->enableAutoQuoting(true);
        $query = new Query($this->connection);
        $sql = $query->select(['something'])->sql();
        $this->assertQuotedQuery('SELECT <something>$', $sql);

        $query = new Query($this->connection);
        $sql = $query->select(['foo' => 'something'])->sql();
        $this->assertQuotedQuery('SELECT <something> AS <foo>$', $sql);

        $query = new Query($this->connection);
        $sql = $query->select(['foo' => 1])->sql();
        $this->assertQuotedQuery('SELECT 1 AS <foo>$', $sql);

        $query = new Query($this->connection);
        $sql = $query->select(['foo' => '1 + 1'])->sql();
        $this->assertQuotedQuery('SELECT <1 \+ 1> AS <foo>$', $sql);

        $query = new Query($this->connection);
        $sql = $query->select(['foo' => $query->newExpr('1 + 1')])->sql();
        $this->assertQuotedQuery('SELECT \(1 \+ 1\) AS <foo>$', $sql);

        $query = new Query($this->connection);
        $sql = $query->select(['foo' => new IdentifierExpression('bar')])->sql();
        $this->assertQuotedQuery('<bar>', $sql);
    }

    /**
     * Tests automatic identifier quoting in the from clause
     */
    public function testQuotingFromAndAlias(): void
    {
        $this->connection->getDriver()->enableAutoQuoting(true);
        $query = new Query($this->connection);
        $sql = $query->select('*')->from(['something'])->sql();
        $this->assertQuotedQuery('FROM <something>', $sql);

        $query = new Query($this->connection);
        $sql = $query->select('*')->from(['foo' => 'something'])->sql();
        $this->assertQuotedQuery('FROM <something> <foo>$', $sql);

        $query = new Query($this->connection);
        $sql = $query->select('*')->from(['foo' => $query->newExpr('bar')])->sql();
        $this->assertQuotedQuery('FROM \(bar\) <foo>$', $sql);
    }

    /**
     * Tests automatic identifier quoting for DISTINCT ON
     */
    public function testQuotingDistinctOn(): void
    {
        $this->connection->getDriver()->enableAutoQuoting(true);
        $query = new Query($this->connection);
        $sql = $query->select('*')->distinct(['something'])->sql();
        $this->assertQuotedQuery('<something>', $sql);
    }

    /**
     * Tests automatic identifier quoting in the join clause
     */
    public function testQuotingJoinsAndAlias(): void
    {
        $this->connection->getDriver()->enableAutoQuoting(true);
        $query = new Query($this->connection);
        $sql = $query->select('*')->join(['something'])->sql();
        $this->assertQuotedQuery('JOIN <something>', $sql);

        $query = new Query($this->connection);
        $sql = $query->select('*')->join(['foo' => 'something'])->sql();
        $this->assertQuotedQuery('JOIN <something> <foo>', $sql);

        $query = new Query($this->connection);
        $sql = $query->select('*')->join(['foo' => $query->newExpr('bar')])->sql();
        $this->assertQuotedQuery('JOIN \(bar\) <foo>', $sql);

        $query = new Query($this->connection);
        $sql = $query->select('*')->join([
            'alias' => 'orders',
            'table' => 'Order',
            'conditions' => ['1 = 1'],
        ])->sql();
        $this->assertQuotedQuery('JOIN <Order> <orders> ON 1 = 1', $sql);
    }

    /**
     * Tests automatic identifier quoting in the group by clause
     */
    public function testQuotingGroupBy(): void
    {
        $this->connection->getDriver()->enableAutoQuoting(true);
        $query = new Query($this->connection);
        $sql = $query->select('*')->group(['something'])->sql();
        $this->assertQuotedQuery('GROUP BY <something>', $sql);

        $query = new Query($this->connection);
        $sql = $query->select('*')->group([$query->newExpr('bar')])->sql();
        $this->assertQuotedQuery('GROUP BY \(bar\)', $sql);

        $query = new Query($this->connection);
        $sql = $query->select('*')->group([new IdentifierExpression('bar')])->sql();
        $this->assertQuotedQuery('GROUP BY \(<bar>\)', $sql);
    }

    /**
     * Tests automatic identifier quoting strings inside conditions expressions
     */
    public function testQuotingExpressions(): void
    {
        $this->connection->getDriver()->enableAutoQuoting(true);
        $query = new Query($this->connection);
        $sql = $query->select('*')
            ->where(['something' => 'value'])
            ->sql();
        $this->assertQuotedQuery('WHERE <something> = :c0', $sql);

        $query = new Query($this->connection);
        $sql = $query->select('*')
            ->where([
                'something' => 'value',
                'OR' => ['foo' => 'bar', 'baz' => 'cake'],
            ])
            ->sql();
        $this->assertQuotedQuery('<something> = :c0 AND', $sql);
        $this->assertQuotedQuery('\(<foo> = :c1 OR <baz> = :c2\)', $sql);
    }

    /**
     * Tests that insert query parts get quoted automatically
     */
    public function testQuotingInsert(): void
    {
        $this->connection->getDriver()->enableAutoQuoting(true);
        $query = new Query($this->connection);
        $sql = $query->insert(['bar', 'baz'])
            ->into('foo')
            ->where(['something' => 'value'])
            ->sql();
        $this->assertQuotedQuery('INSERT INTO <foo> \(<bar>, <baz>\)', $sql);

        $query = new Query($this->connection);
        $sql = $query->insert([$query->newExpr('bar')])
            ->into('foo')
            ->where(['something' => 'value'])
            ->sql();
        $this->assertQuotedQuery('INSERT INTO <foo> \(\(bar\)\)', $sql);
    }

    /**
     * Tests converting a query to a string
     */
    public function testToString(): void
    {
        $query = new Query($this->connection);
        $query
            ->select(['title'])
            ->from('articles');
        $result = (string)$query;
        $this->assertQuotedQuery('SELECT <title> FROM <articles>', $result, !$this->autoQuote);
    }

    /**
     * Tests __debugInfo
     */
    public function testDebugInfo(): void
    {
        $query = (new Query($this->connection))->select('*')
            ->from('articles')
            ->setDefaultTypes(['id' => 'integer'])
            ->where(['id' => '1']);

        $expected = [
            '(help)' => 'This is a Query object, to get the results execute or iterate it.',
            'sql' => $query->sql(),
            'params' => [
                ':c0' => ['value' => '1', 'type' => 'integer', 'placeholder' => 'c0'],
            ],
            'defaultTypes' => ['id' => 'integer'],
            'decorators' => 0,
            'executed' => false,
        ];
        $result = $query->__debugInfo();
        $this->assertEquals($expected, $result);

        $query->execute();
        $expected = [
            '(help)' => 'This is a Query object, to get the results execute or iterate it.',
            'sql' => $query->sql(),
            'params' => [
                ':c0' => ['value' => '1', 'type' => 'integer', 'placeholder' => 'c0'],
            ],
            'defaultTypes' => ['id' => 'integer'],
            'decorators' => 0,
            'executed' => true,
        ];
        $result = $query->__debugInfo();
        $this->assertEquals($expected, $result);
    }

    /**
     * Tests __debugInfo on incomplete query
     */
    public function testDebugInfoIncompleteQuery(): void
    {
        $query = (new Query($this->connection))
            ->insert(['title']);
        $result = $query->__debugInfo();
        $this->assertStringContainsString('incomplete', $result['sql']);
        $this->assertSame([], $result['params']);
    }

    /**
     * Tests that it is possible to pass ExpressionInterface to isNull and isNotNull
     */
    public function testIsNullWithExpressions(): void
    {
        $query = new Query($this->connection);
        $subquery = (new Query($this->connection))
            ->select(['id'])
            ->from('authors')
            ->where(['id' => 1]);

        $result = $query
            ->select(['name'])
            ->from(['authors'])
            ->where(function ($exp) use ($subquery) {
                return $exp->isNotNull($subquery);
            })
            ->execute();
        $this->assertNotEmpty($result->fetchAll('assoc'));

        $result = (new Query($this->connection))
            ->select(['name'])
            ->from(['authors'])
            ->where(function ($exp) use ($subquery) {
                return $exp->isNull($subquery);
            })
            ->execute();
        $this->assertEmpty($result->fetchAll('assoc'));
    }

    /**
     * Tests that strings passed to isNull and isNotNull will be treated as identifiers
     * when using autoQuoting
     */
    public function testIsNullAutoQuoting(): void
    {
        $this->connection->getDriver()->enableAutoQuoting(true);
        $query = new Query($this->connection);
        $query->select('*')->from('things')->where(function ($exp) {
            return $exp->isNull('field');
        });
        $this->assertQuotedQuery('WHERE \(<field>\) IS NULL', $query->sql());

        $query = new Query($this->connection);
        $query->select('*')->from('things')->where(function ($exp) {
            return $exp->isNotNull('field');
        });
        $this->assertQuotedQuery('WHERE \(<field>\) IS NOT NULL', $query->sql());
    }

    /**
     * Tests that using the IS operator will automatically translate to the best
     * possible operator depending on the passed value
     */
    public function testDirectIsNull(): void
    {
        $sql = (new Query($this->connection))
            ->select(['name'])
            ->from(['authors'])
            ->where(['name IS' => null])
            ->sql();
        $this->assertQuotedQuery('WHERE \(<name>\) IS NULL', $sql, !$this->autoQuote);

        $results = (new Query($this->connection))
            ->select(['name'])
            ->from(['authors'])
            ->where(['name IS' => 'larry'])
            ->execute();
        $this->assertCount(1, $results);
        $this->assertEquals(['name' => 'larry'], $results->fetch('assoc'));
    }

    /**
     * Tests that using the wrong NULL operator will throw meaningful exception instead of
     * cloaking as always-empty result set.
     */
    public function testIsNullInvalid(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Expression `name` is missing operator (IS, IS NOT) with `null` value.');

        (new Query($this->connection))
            ->select(['name'])
            ->from(['authors'])
            ->where(['name' => null])
            ->sql();
    }

    /**
     * Tests that using the wrong NULL operator will throw meaningful exception instead of
     * cloaking as always-empty result set.
     */
    public function testIsNotNullInvalid(): void
    {
        $this->expectException(InvalidArgumentException::class);

        (new Query($this->connection))
            ->select(['name'])
            ->from(['authors'])
            ->where(['name !=' => null])
            ->sql();
    }

    /**
     * Tests that using the IS NOT operator will automatically translate to the best
     * possible operator depending on the passed value
     */
    public function testDirectIsNotNull(): void
    {
        $sql = (new Query($this->connection))
            ->select(['name'])
            ->from(['authors'])
            ->where(['name IS NOT' => null])
            ->sql();
        $this->assertQuotedQuery('WHERE \(<name>\) IS NOT NULL', $sql, !$this->autoQuote);

        $results = (new Query($this->connection))
            ->select(['name'])
            ->from(['authors'])
            ->where(['name IS NOT' => 'larry'])
            ->execute();
        $this->assertCount(3, $results);
        $this->assertNotEquals(['name' => 'larry'], $results->fetch('assoc'));
    }

    /**
     * Performs the simple update query and verifies the row count.
     */
    public function testRowCountAndClose(): void
    {
        $statementMock = $this->getMockBuilder(StatementInterface::class)
            ->onlyMethods(['rowCount', 'closeCursor'])
            ->getMockForAbstractClass();

        $statementMock->expects($this->once())
            ->method('rowCount')
            ->willReturn(500);

        $statementMock->expects($this->once())
            ->method('closeCursor');

        /** @var \Cake\ORM\Query|\PHPUnit\Framework\MockObject\MockObject $queryMock */
        $queryMock = $this->getMockBuilder(Query::class)
            ->onlyMethods(['execute'])
            ->setConstructorArgs([$this->connection])
            ->getMock();

        $queryMock->expects($this->once())
            ->method('execute')
            ->willReturn($statementMock);

        $rowCount = $queryMock->update('authors')
            ->set('name', 'mark')
            ->where(['id' => 1])
            ->rowCountAndClose();

        $this->assertEquals(500, $rowCount);
    }

    /**
     * Tests that case statements work correctly for various use-cases.
     *
     * @deprecated
     */
    public function testSqlCaseStatement(): void
    {
        $query = new Query($this->connection);
        $publishedCase = null;
        $notPublishedCase = null;
        $this->deprecated(function () use ($query, &$publishedCase, &$notPublishedCase) {
            $publishedCase = $query
                ->newExpr()
                ->addCase(
                    $query
                        ->newExpr()
                        ->add(['published' => 'Y']),
                    1,
                    'integer'
                );
            $notPublishedCase = $query
                ->newExpr()
                ->addCase(
                    $query
                        ->newExpr()
                        ->add(['published' => 'N']),
                    1,
                    'integer'
                );
        });

        // Postgres requires the case statement to be cast to a integer
        if ($this->connection->getDriver() instanceof Postgres) {
            $publishedCase = $query->func()->cast($publishedCase, 'integer');
            $notPublishedCase = $query->func()->cast($notPublishedCase, 'integer');
        }

        $results = $query
            ->select([
                'published' => $query->func()->sum($publishedCase),
                'not_published' => $query->func()->sum($notPublishedCase),
            ])
            ->from(['comments'])
            ->execute()
            ->fetchAll('assoc');

        $this->assertEquals(5, $results[0]['published']);
        $this->assertEquals(1, $results[0]['not_published']);

        $query = new Query($this->connection);
        $query
            ->insert(['article_id', 'user_id', 'comment', 'published'])
            ->into('comments')
            ->values([
                'article_id' => 2,
                'user_id' => 1,
                'comment' => 'In limbo',
                'published' => 'L',
            ])
            ->execute()
            ->closeCursor();

        $query = new Query($this->connection);
        $conditions = [
            $query
                ->newExpr()
                ->add(['published' => 'Y']),
            $query
                ->newExpr()
                ->add(['published' => 'N']),
        ];
        $values = [
            'Published',
            'Not published',
            'None',
        ];
        $this->deprecated(function () use ($query, $conditions, $values) {
            $query
                ->select([
                    'id',
                    'comment',
                    'status' => $query->newExpr()->addCase($conditions, $values),
                ])
                ->from(['comments']);
        });
        $results = $query
            ->execute()
            ->fetchAll('assoc');

        $this->assertSame('Published', $results[2]['status']);
        $this->assertSame('Not published', $results[3]['status']);
        $this->assertSame('None', $results[6]['status']);

        $query = new Query($this->connection);
        $this->deprecated(function () use ($query) {
            $query->select(['id'])
                ->from('articles')
                ->orderAsc(function (QueryExpression $exp, Query $query) {
                    return $exp->addCase(
                        [$query->newExpr()->add(['author_id' => 1])],
                        [1, $query->identifier('id')],
                        ['integer', null]
                    );
                })
                ->orderAsc('id');
        });
        $result = $query->execute()->fetchAll('assoc');
        $expected = [
            ['id' => 1],
            ['id' => 3],
            ['id' => 2],
        ];
        $this->assertEquals($expected, $result);
    }

    /**
     * Shows that bufferResults(false) will prevent client-side results buffering
     */
    public function testUnbufferedQuery(): void
    {
        $this->deprecated(function () {
            $query = new Query($this->connection);
            $result = $query->select(['body', 'author_id'])
                ->from('articles')
                ->enableBufferedResults(false)
                ->execute();

            if (!method_exists($result, 'bufferResults')) {
                $result->closeCursor();
                $this->markTestSkipped('This driver does not support unbuffered queries');
            }

            $this->assertCount(0, $result, 'Unbuffered queries only have a count when results are fetched');

            $list = $result->fetchAll('assoc');
            $this->assertCount(3, $list);
            $result->closeCursor();

            $query = new Query($this->connection);
            $result = $query->select(['body', 'author_id'])
                ->from('articles')
                ->execute();

            $this->assertCount(3, $result, 'Buffered queries can be counted any time.');
            $list = $result->fetchAll('assoc');
            $this->assertCount(3, $list);
            $result->closeCursor();
        });
    }

    public function testCloneUpdateExpression(): void
    {
        $query = new Query($this->connection);
        $query->update($query->newExpr('update'));

        $clause = $query->clause('update');
        $clauseClone = (clone $query)->clause('update');

        $this->assertIsArray($clause);

        foreach ($clause as $key => $value) {
            $this->assertEquals($value, $clauseClone[$key]);
            $this->assertNotSame($value, $clauseClone[$key]);
        }
    }

    public function testCloneSetExpression(): void
    {
        $query = new Query($this->connection);
        $query
            ->update('table')
            ->set(['column' => $query->newExpr('value')]);

        $clause = $query->clause('set');
        $clauseClone = (clone $query)->clause('set');

        $this->assertInstanceOf(ExpressionInterface::class, $clause);

        $this->assertEquals($clause, $clauseClone);
        $this->assertNotSame($clause, $clauseClone);
    }

    public function testCloneValuesExpression(): void
    {
        $query = new Query($this->connection);
        $query
            ->insert(['column'])
            ->into('table')
            ->values(['column' => $query->newExpr('value')]);

        $clause = $query->clause('values');
        $clauseClone = (clone $query)->clause('values');

        $this->assertInstanceOf(ExpressionInterface::class, $clause);

        $this->assertEquals($clause, $clauseClone);
        $this->assertNotSame($clause, $clauseClone);
    }

    public function testCloneWithExpression(): void
    {
        $query = new Query($this->connection);
        $query
            ->with(
                new CommonTableExpression(
                    'cte',
                    new Query($this->connection)
                )
            )
            ->with(function (CommonTableExpression $cte, Query $query) {
                return $cte
                    ->name('cte2')
                    ->query($query);
            });

        $clause = $query->clause('with');
        $clauseClone = (clone $query)->clause('with');

        $this->assertIsArray($clause);

        foreach ($clause as $key => $value) {
            $this->assertEquals($value, $clauseClone[$key]);
            $this->assertNotSame($value, $clauseClone[$key]);
        }
    }

    public function testCloneSelectExpression(): void
    {
        $query = new Query($this->connection);
        $query
            ->select($query->newExpr('select'))
            ->select(['alias' => $query->newExpr('select')]);

        $clause = $query->clause('select');
        $clauseClone = (clone $query)->clause('select');

        $this->assertIsArray($clause);

        foreach ($clause as $key => $value) {
            $this->assertEquals($value, $clauseClone[$key]);
            $this->assertNotSame($value, $clauseClone[$key]);
        }
    }

    public function testCloneDistinctExpression(): void
    {
        $query = new Query($this->connection);
        $query->distinct($query->newExpr('distinct'));

        $clause = $query->clause('distinct');
        $clauseClone = (clone $query)->clause('distinct');

        $this->assertInstanceOf(ExpressionInterface::class, $clause);

        $this->assertEquals($clause, $clauseClone);
        $this->assertNotSame($clause, $clauseClone);
    }

    public function testCloneModifierExpression(): void
    {
        $query = new Query($this->connection);
        $query->modifier($query->newExpr('modifier'));

        $clause = $query->clause('modifier');
        $clauseClone = (clone $query)->clause('modifier');

        $this->assertIsArray($clause);

        foreach ($clause as $key => $value) {
            $this->assertEquals($value, $clauseClone[$key]);
            $this->assertNotSame($value, $clauseClone[$key]);
        }
    }

    public function testCloneFromExpression(): void
    {
        $query = new Query($this->connection);
        $query->from(['alias' => new Query($this->connection)]);

        $clause = $query->clause('from');
        $clauseClone = (clone $query)->clause('from');

        $this->assertIsArray($clause);

        foreach ($clause as $key => $value) {
            $this->assertEquals($value, $clauseClone[$key]);
            $this->assertNotSame($value, $clauseClone[$key]);
        }
    }

    public function testCloneJoinExpression(): void
    {
        $query = new Query($this->connection);
        $query
            ->innerJoin(
                ['alias_inner' => new Query($this->connection)],
                ['alias_inner.fk = parent.pk']
            )
            ->leftJoin(
                ['alias_left' => new Query($this->connection)],
                ['alias_left.fk = parent.pk']
            )
            ->rightJoin(
                ['alias_right' => new Query($this->connection)],
                ['alias_right.fk = parent.pk']
            );

        $clause = $query->clause('join');
        $clauseClone = (clone $query)->clause('join');

        $this->assertIsArray($clause);

        foreach ($clause as $key => $value) {
            $this->assertEquals($value['table'], $clauseClone[$key]['table']);
            $this->assertNotSame($value['table'], $clauseClone[$key]['table']);

            $this->assertEquals($value['conditions'], $clauseClone[$key]['conditions']);
            $this->assertNotSame($value['conditions'], $clauseClone[$key]['conditions']);
        }
    }

    public function testCloneWhereExpression(): void
    {
        $query = new Query($this->connection);
        $query
            ->where($query->newExpr('where'))
            ->where(['field' => $query->newExpr('where')]);

        $clause = $query->clause('where');
        $clauseClone = (clone $query)->clause('where');

        $this->assertInstanceOf(ExpressionInterface::class, $clause);

        $this->assertEquals($clause, $clauseClone);
        $this->assertNotSame($clause, $clauseClone);
    }

    public function testCloneGroupExpression(): void
    {
        $query = new Query($this->connection);
        $query->group($query->newExpr('group'));

        $clause = $query->clause('group');
        $clauseClone = (clone $query)->clause('group');

        $this->assertIsArray($clause);

        foreach ($clause as $key => $value) {
            $this->assertEquals($value, $clauseClone[$key]);
            $this->assertNotSame($value, $clauseClone[$key]);
        }
    }

    public function testCloneHavingExpression(): void
    {
        $query = new Query($this->connection);
        $query->having($query->newExpr('having'));

        $clause = $query->clause('having');
        $clauseClone = (clone $query)->clause('having');

        $this->assertInstanceOf(ExpressionInterface::class, $clause);

        $this->assertEquals($clause, $clauseClone);
        $this->assertNotSame($clause, $clauseClone);
    }

    public function testCloneWindowExpression(): void
    {
        $query = new Query($this->connection);
        $query
            ->window('window1', new WindowExpression())
            ->window('window2', function (WindowExpression $window) {
                return $window;
            });

        $clause = $query->clause('window');
        $clauseClone = (clone $query)->clause('window');

        $this->assertIsArray($clause);

        foreach ($clause as $key => $value) {
            $this->assertEquals($value['name'], $clauseClone[$key]['name']);
            $this->assertNotSame($value['name'], $clauseClone[$key]['name']);

            $this->assertEquals($value['window'], $clauseClone[$key]['window']);
            $this->assertNotSame($value['window'], $clauseClone[$key]['window']);
        }
    }

    public function testCloneOrderExpression(): void
    {
        $query = new Query($this->connection);
        $query
            ->order($query->newExpr('order'))
            ->orderAsc($query->newExpr('order_asc'))
            ->orderDesc($query->newExpr('order_desc'));

        $clause = $query->clause('order');
        $clauseClone = (clone $query)->clause('order');

        $this->assertInstanceOf(ExpressionInterface::class, $clause);

        $this->assertEquals($clause, $clauseClone);
        $this->assertNotSame($clause, $clauseClone);
    }

    public function testCloneLimitExpression(): void
    {
        $query = new Query($this->connection);
        $query->limit($query->newExpr('1'));

        $clause = $query->clause('limit');
        $clauseClone = (clone $query)->clause('limit');

        $this->assertInstanceOf(ExpressionInterface::class, $clause);

        $this->assertEquals($clause, $clauseClone);
        $this->assertNotSame($clause, $clauseClone);
    }

    public function testCloneOffsetExpression(): void
    {
        $query = new Query($this->connection);
        $query->offset($query->newExpr('1'));

        $clause = $query->clause('offset');
        $clauseClone = (clone $query)->clause('offset');

        $this->assertInstanceOf(ExpressionInterface::class, $clause);

        $this->assertEquals($clause, $clauseClone);
        $this->assertNotSame($clause, $clauseClone);
    }

    public function testCloneUnionExpression(): void
    {
        $query = new Query($this->connection);
        $query->where(['id' => 1]);

        $query2 = new Query($this->connection);
        $query2->where(['id' => 2]);

        $query->union($query2);

        $clause = $query->clause('union');
        $clauseClone = (clone $query)->clause('union');

        $this->assertIsArray($clause);

        foreach ($clause as $key => $value) {
            $this->assertEquals($value['query'], $clauseClone[$key]['query']);
            $this->assertNotSame($value['query'], $clauseClone[$key]['query']);
        }
    }

    public function testCloneEpilogExpression(): void
    {
        $query = new Query($this->connection);
        $query->epilog($query->newExpr('epilog'));

        $clause = $query->clause('epilog');
        $clauseClone = (clone $query)->clause('epilog');

        $this->assertInstanceOf(ExpressionInterface::class, $clause);

        $this->assertEquals($clause, $clauseClone);
        $this->assertNotSame($clause, $clauseClone);
    }

    /**
     * Test that cloning goes deep.
     */
    public function testDeepClone(): void
    {
        $query = new Query($this->connection);
        $query->select(['id', 'title' => $query->func()->concat(['title' => 'literal', 'test'])])
            ->from('articles')
            ->where(['Articles.id' => 1])
            ->offset(10)
            ->limit(1)
            ->order(['Articles.id' => 'DESC']);
        $dupe = clone $query;

        $this->assertEquals($query->clause('where'), $dupe->clause('where'));
        $this->assertNotSame($query->clause('where'), $dupe->clause('where'));
        $dupe->where(['Articles.title' => 'thinger']);
        $this->assertNotEquals($query->clause('where'), $dupe->clause('where'));

        $this->assertNotSame(
            $query->clause('select')['title'],
            $dupe->clause('select')['title']
        );
        $this->assertEquals($query->clause('order'), $dupe->clause('order'));
        $this->assertNotSame($query->clause('order'), $dupe->clause('order'));

        $query->order(['Articles.title' => 'ASC']);
        $this->assertNotEquals($query->clause('order'), $dupe->clause('order'));

        $this->assertNotSame(
            $query->getSelectTypeMap(),
            $dupe->getSelectTypeMap()
        );
    }

    /**
     * Tests the selectTypeMap method
     */
    public function testSelectTypeMap(): void
    {
        $query = new Query($this->connection);
        $typeMap = $query->getSelectTypeMap();
        $this->assertInstanceOf(TypeMap::class, $typeMap);
        $another = clone $typeMap;
        $query->setSelectTypeMap($another);
        $this->assertSame($another, $query->getSelectTypeMap());
    }

    /**
     * Tests the automatic type conversion for the fields in the result
     */
    public function testSelectTypeConversion(): void
    {
        TypeFactory::set('custom_datetime', new BarType('custom_datetime'));

        $query = new Query($this->connection);
        $query
            ->select(['id', 'comment', 'the_date' => 'created', 'updated'])
            ->from('comments')
            ->limit(1)
            ->getSelectTypeMap()
                ->setTypes([
                    'id' => 'integer',
                    'the_date' => 'datetime',
                    'updated' => 'custom_datetime',
                ]);

        $result = $query->execute()->fetchAll('assoc');
        $this->assertIsInt($result[0]['id']);
        $this->assertInstanceOf(DateTimeImmutable::class, $result[0]['the_date']);
        $this->assertInstanceOf(DateTimeImmutable::class, $result[0]['updated']);
    }

    /**
     * Tests that the JSON type can save and get data symmetrically
     */
    public function testSymmetricJsonType(): void
    {
        $query = new Query($this->connection);
        $insert = $query
            ->insert(['comment', 'article_id', 'user_id'], ['comment' => 'json'])
            ->into('comments')
            ->values([
                'comment' => ['a' => 'b', 'c' => true],
                'article_id' => 1,
                'user_id' => 1,
            ])
            ->execute();

        $id = $insert->lastInsertId('comments', 'id');
        $insert->closeCursor();

        $query = new Query($this->connection);
        $query
            ->select(['comment'])
            ->from('comments')
            ->where(['id' => $id])
            ->getSelectTypeMap()->setTypes(['comment' => 'json']);

        $result = $query->execute();
        $comment = $result->fetchAll('assoc')[0]['comment'];
        $result->closeCursor();
        $this->assertSame(['a' => 'b', 'c' => true], $comment);
    }

    /**
     * Test removeJoin().
     */
    public function testRemoveJoin(): void
    {
        $query = new Query($this->connection);
        $query->select(['id', 'title'])
            ->from('articles')
            ->join(['authors' => [
                'type' => 'INNER',
                'conditions' => ['articles.author_id = authors.id'],
            ]]);
        $this->assertArrayHasKey('authors', $query->clause('join'));

        $this->assertSame($query, $query->removeJoin('authors'));
        $this->assertArrayNotHasKey('authors', $query->clause('join'));
    }

    /**
     * Tests that types in the type map are used in the
     * specific comparison functions when using a callable
     */
    public function testBetweenExpressionAndTypeMap(): void
    {
        $query = new Query($this->connection);
        $query->select('id')
            ->from('comments')
            ->setDefaultTypes(['created' => 'datetime'])
            ->where(function ($expr) {
                $from = new DateTime('2007-03-18 10:45:00');
                $to = new DateTime('2007-03-18 10:48:00');

                return $expr->between('created', $from, $to);
            });
        $this->assertCount(2, $query->execute()->fetchAll());
    }

    /**
     * Test use of modifiers in a INSERT query
     *
     * Testing the generated SQL since the modifiers are usually different per driver
     */
    public function testInsertModifiers(): void
    {
        $query = new Query($this->connection);
        $result = $query
            ->insert(['title'])
            ->into('articles')
            ->values(['title' => 'foo'])
            ->modifier('IGNORE');
        $this->assertQuotedQuery(
            'INSERT IGNORE INTO <articles> \(<title>\) (OUTPUT INSERTED\.\* )?',
            $result->sql(),
            !$this->autoQuote
        );

        $query = new Query($this->connection);
        $result = $query
            ->insert(['title'])
            ->into('articles')
            ->values(['title' => 'foo'])
            ->modifier(['IGNORE', 'LOW_PRIORITY']);
        $this->assertQuotedQuery(
            'INSERT IGNORE LOW_PRIORITY INTO <articles> \(<title>\) (OUTPUT INSERTED\.\* )?',
            $result->sql(),
            !$this->autoQuote
        );
    }

    /**
     * Test use of modifiers in a UPDATE query
     *
     * Testing the generated SQL since the modifiers are usually different per driver
     */
    public function testUpdateModifiers(): void
    {
        $query = new Query($this->connection);
        $result = $query
            ->update('authors')
            ->set('name', 'mark')
            ->modifier('TOP 10 PERCENT');
        $this->assertQuotedQuery(
            'UPDATE TOP 10 PERCENT <authors> SET <name> = :c0',
            $result->sql(),
            !$this->autoQuote
        );

        $query = new Query($this->connection);
        $result = $query
            ->update('authors')
            ->set('name', 'mark')
            ->modifier(['TOP 10 PERCENT', 'FOO']);
        $this->assertQuotedQuery(
            'UPDATE TOP 10 PERCENT FOO <authors> SET <name> = :c0',
            $result->sql(),
            !$this->autoQuote
        );

        $query = new Query($this->connection);
        $result = $query
            ->update('authors')
            ->set('name', 'mark')
            ->modifier([$query->newExpr('TOP 10 PERCENT')]);
        $this->assertQuotedQuery(
            'UPDATE TOP 10 PERCENT <authors> SET <name> = :c0',
            $result->sql(),
            !$this->autoQuote
        );
    }

    /**
     * Test use of modifiers in a DELETE query
     *
     * Testing the generated SQL since the modifiers are usually different per driver
     */
    public function testDeleteModifiers(): void
    {
        $query = new Query($this->connection);
        $result = $query->delete()
            ->from('authors')
            ->where('1 = 1')
            ->modifier('IGNORE');
        $this->assertQuotedQuery(
            'DELETE IGNORE FROM <authors> WHERE 1 = 1',
            $result->sql(),
            !$this->autoQuote
        );

        $query = new Query($this->connection);
        $result = $query->delete()
            ->from('authors')
            ->where('1 = 1')
            ->modifier(['IGNORE', 'QUICK']);
        $this->assertQuotedQuery(
            'DELETE IGNORE QUICK FROM <authors> WHERE 1 = 1',
            $result->sql(),
            !$this->autoQuote
        );
    }

    /**
     * Test getValueBinder()
     */
    public function testGetValueBinder(): void
    {
        $query = new Query($this->connection);

        $this->assertInstanceOf('Cake\Database\ValueBinder', $query->getValueBinder());
    }

    /**
     * Test automatic results casting
     */
    public function testCastResults(): void
    {
        $query = new Query($this->connection);
        $fields = [
            'user_id' => 'integer',
            'is_active' => 'boolean',
        ];
        $typeMap = new TypeMap($fields + ['a' => 'integer']);
        $results = $query
            ->select(array_keys($fields))
            ->select(['a' => 'is_active'])
            ->from('profiles')
            ->setSelectTypeMap($typeMap)
            ->where(['user_id' => 1])
            ->execute()
            ->fetchAll('assoc');
        $this->assertSame([['user_id' => 1, 'is_active' => false, 'a' => 0]], $results);
    }

    /**
     * Test disabling type casting
     */
    public function testCastResultsDisable(): void
    {
        $query = new Query($this->connection);
        $typeMap = new TypeMap(['a' => 'datetime']);
        $results = $query
            ->select(['a' => 'id'])
            ->from('profiles')
            ->setSelectTypeMap($typeMap)
            ->limit(1)
            ->disableResultsCasting()
            ->execute()
            ->fetchAll('assoc');
        $this->assertEquals([['a' => '1']], $results);
    }

    /**
     * Test obtaining the current results casting mode.
     */
    public function testObtainingResultsCastingMode(): void
    {
        $query = new Query($this->connection);

        $this->assertTrue($query->isResultsCastingEnabled());

        $query->disableResultsCasting();
        $this->assertFalse($query->isResultsCastingEnabled());
    }

    /**
     * Test that type conversion is only applied once.
     */
    public function testAllNoDuplicateTypeCasting(): void
    {
        $this->skipIf($this->autoQuote, 'Produces bad SQL in postgres with autoQuoting');
        $query = new Query($this->connection);
        $query
            ->select('1.5 AS a')
            ->setSelectTypeMap(new TypeMap(['a' => 'integer']));

        // Convert to an array and make the query dirty again.
        $result = $query->execute()->fetchAll('assoc');
        $this->assertEquals([['a' => 1]], $result);

        $query->setSelectTypeMap(new TypeMap(['a' => 'float']));
        // Get results a second time.
        $result = $query->execute()->fetchAll('assoc');

        // Had the type casting being remembered from the first time,
        // The value would be a truncated float (1.0)
        $this->assertEquals([['a' => 1.5]], $result);
    }

    /**
     * Test that reading an undefined clause does not emit an error.
     */
    public function testClauseUndefined(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The \'nope\' clause is not defined. Valid clauses are: delete, update');
        $query = new Query($this->connection);
        $this->assertEmpty($query->clause('where'));
        $query->clause('nope');
    }

    /**
     * Assertion for comparing a table's contents with what is in it.
     *
     * @param string $table
     * @param int $count
     * @param array $rows
     * @param array $conditions
     */
    public function assertTable($table, $count, $rows, $conditions = []): void
    {
        $result = (new Query($this->connection))->select('*')
            ->from($table)
            ->where($conditions)
            ->execute();
        $this->assertCount($count, $result, 'Row count is incorrect');
        $this->assertEquals($rows, $result->fetchAll('assoc'));
        $result->closeCursor();
    }

    /**
     * Assertion for comparing a regex pattern against a query having its identifiers
     * quoted. It accepts queries quoted with the characters `<` and `>`. If the third
     * parameter is set to true, it will alter the pattern to both accept quoted and
     * unquoted queries
     *
     * @param string $pattern
     * @param string $query the result to compare against
     * @param bool $optional
     */
    public function assertQuotedQuery($pattern, $query, $optional = false): void
    {
        if ($optional) {
            $optional = '?';
        }
        $pattern = str_replace('<', '[`"\[]' . $optional, $pattern);
        $pattern = str_replace('>', '[`"\]]' . $optional, $pattern);
        $this->assertMatchesRegularExpression('#' . $pattern . '#', $query);
    }

    /**
     * Test that calling fetchAssoc return an associated array.
     *
     * @throws \Exception
     */
    public function testFetchAssoc(): void
    {
        $query = new Query($this->connection);
        $fields = [
            'id' => 'integer',
            'user_id' => 'integer',
            'is_active' => 'boolean',
        ];
        $typeMap = new TypeMap($fields);
        $statement = $query
            ->select([
                'id',
                'user_id',
                'is_active',
            ])
            ->from('profiles')
            ->setSelectTypeMap($typeMap)
            ->limit(1)
            ->execute();

        $this->assertSame(['id' => 1, 'user_id' => 1, 'is_active' => false], $statement->fetchAssoc());
        $statement->closeCursor();
    }

    /**
     * Test that calling fetchAssoc return an empty associated array.
     *
     * @throws \Exception
     */
    public function testFetchAssocWithEmptyResult(): void
    {
        $query = new Query($this->connection);

        $results = $query
            ->select(['id'])
            ->from('profiles')
            ->where(['id' => -1])
            ->execute()
            ->fetchAssoc();
        $this->assertSame([], $results);
    }

    /**
     * Test that calling fetch with with FETCH_TYPE_OBJ return stdClass object.
     *
     * @throws \Exception
     */
    public function testFetchObjects(): void
    {
        $query = new Query($this->connection);
        $stmt = $query->select([
                'id',
                'user_id',
                'is_active',
            ])
            ->from('profiles')
            ->limit(1)
            ->execute();
        $results = $stmt->fetch(StatementDecorator::FETCH_TYPE_OBJ);
        $stmt->closeCursor();

        $this->assertInstanceOf(stdClass::class, $results);
    }

    /**
     * Test that fetchColumn() will return the correct value at $position.
     *
     * @throws \Exception
     */
    public function testFetchColumn(): void
    {
        $query = new Query($this->connection);
        $fields = [
            'integer',
            'integer',
            'boolean',
        ];
        $typeMap = new TypeMap($fields);
        $query
            ->select([
                'id',
                'user_id',
                'is_active',
            ])
            ->from('profiles')
            ->setSelectTypeMap($typeMap)
            ->where(['id' => 2])
            ->limit(1);
        $statement = $query->execute();
        $results = $statement->fetchColumn(0);
        $this->assertSame(2, $results);
        $statement->closeCursor();

        $statement = $query->execute();
        $results = $statement->fetchColumn(1);
        $this->assertSame(2, $results);
        $statement->closeCursor();

        $statement = $query->execute();
        $results = $statement->fetchColumn(2);
        $this->assertSame(false, $results);
        $statement->closeCursor();
    }

    /**
     * Test that fetchColumn() will return false if $position is not set.
     *
     * @throws \Exception
     */
    public function testFetchColumnReturnsFalse(): void
    {
        $query = new Query($this->connection);
        $fields = [
            'integer',
            'integer',
            'boolean',
        ];
        $typeMap = new TypeMap($fields);
        $query
            ->select([
                'id',
                'user_id',
                'is_active',
            ])
            ->from('profiles')
            ->setSelectTypeMap($typeMap)
            ->where(['id' => 2])
            ->limit(1);
        $statement = $query->execute();
        $results = $statement->fetchColumn(3);
        $this->assertFalse($results);
        $statement->closeCursor();
    }

    /**
     * Tests that query expressions can be used for ordering.
     */
    public function testOrderBySubquery(): void
    {
        $this->autoQuote = true;
        $this->connection->getDriver()->enableAutoQuoting($this->autoQuote);

        $connection = $this->connection;

        $query = new Query($connection);

        $stmt = $connection->update('articles', ['published' => 'N'], ['id' => 3]);
        $stmt->closeCursor();

        $subquery = new Query($connection);
        $subquery
            ->select(
                $subquery->newExpr()->case()->when(['a.published' => 'N'])->then(1)->else(0)
            )
            ->from(['a' => 'articles'])
            ->where([
                'a.id = articles.id',
            ]);

        $query
            ->select(['id'])
            ->from('articles')
            ->orderDesc($subquery)
            ->orderAsc('id')
            ->setSelectTypeMap(new TypeMap([
                'id' => 'integer',
            ]));

        $this->assertQuotedQuery(
            'SELECT <id> FROM <articles> ORDER BY \(' .
                'SELECT \(CASE WHEN <a>\.<published> = \:c0 THEN \:c1 ELSE \:c2 END\) ' .
                'FROM <articles> <a> ' .
                'WHERE a\.id = articles\.id' .
            '\) DESC, <id> ASC',
            $query->sql(),
            !$this->autoQuote
        );

        $this->assertEquals(
            [
                [
                    'id' => 3,
                ],
                [
                    'id' => 1,
                ],
                [
                    'id' => 2,
                ],
            ],
            $query->execute()->fetchAll('assoc')
        );
    }

    /**
     * Test that reusing expressions will duplicate bindings and run successfully.
     *
     * This replicates what the SQL Server driver would do for <= SQL Server 2008
     * when ordering on fields that are expressions.
     *
     * @see \Cake\Database\Driver\Sqlserver::_pagingSubquery()
     */
    public function testReusingExpressions(): void
    {
        $connection = $this->connection;

        $query = new Query($connection);

        $stmt = $connection->update('articles', ['published' => 'N'], ['id' => 3]);
        $stmt->closeCursor();

        $subqueryA = new Query($connection);
        $subqueryA
            ->select('count(*)')
            ->from(['a' => 'articles'])
            ->where([
                'a.id = articles.id',
                'a.published' => 'Y',
            ]);

        $subqueryB = new Query($connection);
        $subqueryB
            ->select('count(*)')
            ->from(['b' => 'articles'])
            ->where([
                'b.id = articles.id',
                'b.published' => 'N',
            ]);

        $query
            ->select([
                'id',
                'computedA' => $subqueryA,
                'computedB' => $subqueryB,
            ])
            ->from('articles')
            ->orderDesc($subqueryB)
            ->orderAsc('id')
            ->setSelectTypeMap(new TypeMap([
                'id' => 'integer',
                'computedA' => 'integer',
                'computedB' => 'integer',
            ]));

        $this->assertQuotedQuery(
            'SELECT <id>, ' .
                '\(SELECT count\(\*\) FROM <articles> <a> WHERE \(a\.id = articles\.id AND <a>\.<published> = :c0\)\) AS <computedA>, ' .
                '\(SELECT count\(\*\) FROM <articles> <b> WHERE \(b\.id = articles\.id AND <b>\.<published> = :c1\)\) AS <computedB> ' .
            'FROM <articles> ' .
            'ORDER BY \(' .
                'SELECT count\(\*\) FROM <articles> <b> WHERE \(b\.id = articles\.id AND <b>\.<published> = :c2\)' .
            '\) DESC, <id> ASC',
            $query->sql(),
            !$this->autoQuote
        );

        $this->assertSame(
            [
                [
                    'id' => 3,
                    'computedA' => 0,
                    'computedB' => 1,
                ],
                [
                    'id' => 1,
                    'computedA' => 1,
                    'computedB' => 0,
                ],
                [
                    'id' => 2,
                    'computedA' => 1,
                    'computedB' => 0,
                ],
            ],
            $query->execute()->fetchAll('assoc')
        );

        $this->assertSame(
            [
                ':c0' => [
                    'value' => 'Y',
                    'type' => null,
                    'placeholder' => 'c0',
                ],
                ':c1' => [
                    'value' => 'N',
                    'type' => null,
                    'placeholder' => 'c1',
                ],
                ':c2' => [
                    'value' => 'N',
                    'type' => null,
                    'placeholder' => 'c2',
                ],
            ],
            $query->getValueBinder()->bindings()
        );
    }

    /**
     * Tests creating StringExpression.
     */
    public function testStringExpression(): void
    {
        $driver = $this->connection->getDriver();
        $collation = null;
        if ($driver instanceof Mysql) {
            if (version_compare($this->connection->getDriver()->version(), '5.7.0', '<')) {
                $collation = 'utf8_general_ci';
            } else {
                $collation = 'utf8mb4_general_ci';
            }
        } elseif ($driver instanceof Postgres) {
            $collation = 'en_US.utf8';
        } elseif ($driver instanceof Sqlite) {
            $collation = 'BINARY';
        } elseif ($driver instanceof Sqlserver) {
            $collation = 'Latin1_general_CI_AI';
        }

        $query = new Query($this->connection);
        if ($driver instanceof Postgres) {
            // Older postgres versions throw an error on the parameter type without a cast
            $query->select(['test_string' => $query->func()->cast(new StringExpression('testString', $collation), 'text')]);
            $expected = "SELECT \(CAST\(:c0 COLLATE \"{$collation}\" AS text\)\) AS <test_string>";
        } else {
            $query->select(['test_string' => new StringExpression('testString', $collation)]);
            $expected = "SELECT \(:c0 COLLATE {$collation}\) AS <test_string>";
        }
        $this->assertRegExpSql($expected, $query->sql(new ValueBinder()), !$this->autoQuote);

        $statement = $query->execute();
        $this->assertSame('testString', $statement->fetchColumn(0));
        $statement->closeCursor();
    }

    /**
     * Tests setting identifier collation.
     */
    public function testIdentifierCollation(): void
    {
        $driver = $this->connection->getDriver();
        $collation = null;
        if ($driver instanceof Mysql) {
            if (version_compare($this->connection->getDriver()->version(), '5.7.0', '<')) {
                $collation = 'utf8_general_ci';
            } else {
                $collation = 'utf8mb4_general_ci';
            }
        } elseif ($driver instanceof Postgres) {
            $collation = 'en_US.utf8';
        } elseif ($driver instanceof Sqlite) {
            $collation = 'BINARY';
        } elseif ($driver instanceof Sqlserver) {
            $collation = 'Latin1_general_CI_AI';
        }

        $query = (new Query($this->connection))
            ->select(['test_string' => new IdentifierExpression('title', $collation)])
            ->from('articles')
            ->where(['id' => 1]);

        if ($driver instanceof Postgres) {
            // Older postgres versions throw an error on the parameter type without a cast
            $expected = "SELECT \(<title> COLLATE \"{$collation}\"\) AS <test_string>";
        } else {
            $expected = "SELECT \(<title> COLLATE {$collation}\) AS <test_string>";
        }
        $this->assertRegExpSql($expected, $query->sql(new ValueBinder()), !$this->autoQuote);

        $statement = $query->execute();
        $this->assertSame('First Article', $statement->fetchColumn(0));
        $statement->closeCursor();
    }

    /**
     * Simple expressions from the point of view of the query expression
     * object are expressions where the field contains one space at most.
     */
    public function testOperatorsInSimpleConditionsAreCaseInsensitive(): void
    {
        $query = (new Query($this->connection))
            ->select('id')
            ->from('articles')
            ->where(['id in' => [1, 2, 3]]);

        $this->assertQuotedQuery(
            'SELECT <id> FROM <articles> WHERE <id> in \(:c0,:c1,:c2\)',
            $query->sql(),
            !$this->autoQuote
        );

        $query = (new Query($this->connection))
            ->select('id')
            ->from('articles')
            ->where(['id IN' => [1, 2, 3]]);

        $this->assertQuotedQuery(
            'SELECT <id> FROM <articles> WHERE <id> in \(:c0,:c1,:c2\)',
            $query->sql(),
            !$this->autoQuote
        );
    }

    /**
     * Complex expressions from the point of view of the query expression
     * object are expressions where the field contains multiple spaces.
     */
    public function testOperatorsInComplexConditionsAreCaseInsensitive(): void
    {
        $this->skipIf($this->autoQuote, 'Does not work when autoquoting is enabled.');

        $query = (new Query($this->connection))
            ->select('id')
            ->from('profiles')
            ->where(['CONCAT(first_name, " ", last_name) in' => ['foo bar', 'baz 42']]);

        $this->assertSame(
            'SELECT id FROM profiles WHERE CONCAT\(first_name, " ", last_name\) in \(:c0,:c1\)',
            $query->sql()
        );

        $query = (new Query($this->connection))
            ->select('id')
            ->from('profiles')
            ->where(['CONCAT(first_name, " ", last_name) IN' => ['foo bar', 'baz 42']]);

        $this->assertSame(
            'SELECT id FROM profiles WHERE CONCAT\(first_name, " ", last_name\) in \(:c0,:c1\)',
            $query->sql()
        );

        $query = (new Query($this->connection))
            ->select('id')
            ->from('profiles')
            ->where(['CONCAT(first_name, " ", last_name) not in' => ['foo bar', 'baz 42']]);

        $this->assertSame(
            'SELECT id FROM profiles WHERE CONCAT\(first_name, " ", last_name\) not in \(:c0,:c1\)',
            $query->sql()
        );

        $query = (new Query($this->connection))
            ->select('id')
            ->from('profiles')
            ->where(['CONCAT(first_name, " ", last_name) NOT IN' => ['foo bar', 'baz 42']]);

        $this->assertSame(
            'SELECT id FROM profiles WHERE CONCAT\(first_name, " ", last_name\) not in \(:c0,:c1\)',
            $query->sql()
        );
    }
}
