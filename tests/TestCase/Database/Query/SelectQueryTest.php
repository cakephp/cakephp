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
 * @since         5.0.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Test\TestCase\Database\Query;

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
use Cake\Database\Query\SelectQuery;
use Cake\Database\StatementInterface;
use Cake\Database\TypeFactory;
use Cake\Database\TypeMap;
use Cake\Database\ValueBinder;
use Cake\Datasource\ConnectionManager;
use Cake\I18n\DateTime;
use Cake\Test\TestCase\Database\QueryAssertsTrait;
use Cake\TestSuite\TestCase;
use InvalidArgumentException;
use ReflectionProperty;
use stdClass;
use TestApp\Database\Type\BarType;
use function Cake\Collection\collection;

/**
 * Tests SelectQuery class
 */
class SelectQueryTest extends TestCase
{
    use QueryAssertsTrait;

    protected array $fixtures = [
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

    /**
     * Tests that it is possible to obtain expression results from a query
     */
    public function testSelectFieldsOnly(): void
    {
        $this->connection->getDriver()->enableAutoQuoting(false);
        $query = new SelectQuery($this->connection);
        $result = $query->select('1 + 1')->execute();
        $this->assertInstanceOf(StatementInterface::class, $result);
        $this->assertEquals([2], $result->fetch());
        $result->closeCursor();

        //This new field should be appended
        $result = $query->select(['1 + 3'])->execute();
        $this->assertInstanceOf(StatementInterface::class, $result);
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
        $query = new SelectQuery($this->connection);
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
        $query = new SelectQuery($this->connection);
        $result = $query->select(['body', 'author_id'])->from('articles')->execute();
        $this->assertEquals(['body' => 'First Article Body', 'author_id' => 1], $result->fetch('assoc'));
        $this->assertEquals(['body' => 'Second Article Body', 'author_id' => 3], $result->fetch('assoc'));
        $result->closeCursor();

        //Append more tables to next execution
        $result = $query->select('name')->from(['authors'])->orderBy(['name' => 'desc', 'articles.id' => 'asc'])->execute();
        $this->assertEquals(['body' => 'First Article Body', 'author_id' => 1, 'name' => 'nate'], $result->fetch('assoc'));
        $this->assertEquals(['body' => 'Second Article Body', 'author_id' => 3, 'name' => 'nate'], $result->fetch('assoc'));
        $this->assertEquals(['body' => 'Third Article Body', 'author_id' => 1, 'name' => 'nate'], $result->fetch('assoc'));
        $result->closeCursor();

        // Overwrite tables and only fetch from authors
        $result = $query->select('name', true)->from('authors', true)->orderBy(['name' => 'desc'], true)->execute();
        $this->assertSame(['nate'], $result->fetch());
        $this->assertSame(['mariano'], $result->fetch());
        $result->closeCursor();
    }

    /**
     * Tests it is possible to select aliased fields
     */
    public function testSelectAliasedFieldsFromTable(): void
    {
        $query = new SelectQuery($this->connection);
        $result = $query->select(['text' => 'comment', 'article_id'])->from('comments')->execute();
        $this->assertEquals(['text' => 'First Comment for First Article', 'article_id' => 1], $result->fetch('assoc'));
        $this->assertEquals(['text' => 'Second Comment for First Article', 'article_id' => 1], $result->fetch('assoc'));
        $result->closeCursor();

        $query = new SelectQuery($this->connection);
        $result = $query->select(['text' => 'comment', 'article' => 'article_id'])->from('comments')->execute();
        $this->assertEquals(['text' => 'First Comment for First Article', 'article' => 1], $result->fetch('assoc'));
        $this->assertEquals(['text' => 'Second Comment for First Article', 'article' => 1], $result->fetch('assoc'));
        $result->closeCursor();

        $query = new SelectQuery($this->connection);
        $query->select(['text' => 'comment'])->select(['article_id', 'foo' => 'comment']);
        $result = $query->from('comments')->execute();
        $this->assertEquals(
            ['foo' => 'First Comment for First Article', 'text' => 'First Comment for First Article', 'article_id' => 1],
            $result->fetch('assoc')
        );
        $result->closeCursor();

        $query = new SelectQuery($this->connection);
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
        $query = new SelectQuery($this->connection);
        $result = $query->select(['text' => 'a.body', 'a.author_id'])
            ->from(['a' => 'articles'])->execute();

        $this->assertEquals(['text' => 'First Article Body', 'author_id' => 1], $result->fetch('assoc'));
        $this->assertEquals(['text' => 'Second Article Body', 'author_id' => 3], $result->fetch('assoc'));
        $result->closeCursor();

        $result = $query->select(['name' => 'b.name'])->from(['b' => 'authors'])
            ->orderBy(['text' => 'desc', 'name' => 'desc'])
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
        $query = new SelectQuery($this->connection);
        $result = $query
            ->select(['title', 'name'])
            ->from('articles')
            ->join(['table' => 'authors', 'alias' => 'a', 'conditions' => $query->newExpr()->equalFields('author_id', 'a.id')])
            ->orderBy(['title' => 'asc'])
            ->execute();

        $rows = $result->fetchAll('assoc');
        $this->assertCount(3, $rows);
        $this->assertEquals(['title' => 'First Article', 'name' => 'mariano'], $rows[0]);
        $this->assertEquals(['title' => 'Second Article', 'name' => 'larry'], $rows[1]);
        $result->closeCursor();

        $result = $query->join('authors', [], true)->execute();
        $this->assertCount(12, $result->fetchAll(), 'Cross join results in 12 records');
        $result->closeCursor();

        $result = $query->join([
            ['table' => 'authors', 'type' => 'INNER', 'conditions' => $query->newExpr()->equalFields('author_id', 'authors.id')],
        ], [], true)->execute();
        $rows = $result->fetchAll('assoc');
        $this->assertCount(3, $rows);
        $this->assertEquals(['title' => 'First Article', 'name' => 'mariano'], $rows[0]);
        $this->assertEquals(['title' => 'Second Article', 'name' => 'larry'], $rows[1]);
        $result->closeCursor();
    }

    /**
     * Tests it is possible to add joins to a select query using array or expression as conditions
     */
    public function testSelectWithJoinsConditions(): void
    {
        $query = new SelectQuery($this->connection);
        $result = $query
            ->select(['title', 'name'])
            ->from('articles')
            ->join(['table' => 'authors', 'alias' => 'a', 'conditions' => [$query->newExpr()->equalFields('author_id ', 'a.id')]])
            ->orderBy(['title' => 'asc'])
            ->execute();
        $this->assertEquals(['title' => 'First Article', 'name' => 'mariano'], $result->fetch('assoc'));
        $this->assertEquals(['title' => 'Second Article', 'name' => 'larry'], $result->fetch('assoc'));
        $result->closeCursor();

        $query = new SelectQuery($this->connection);
        $conditions = $query->newExpr()->equalFields('author_id', 'a.id');
        $result = $query
            ->select(['title', 'name'])
            ->from('articles')
            ->join(['table' => 'authors', 'alias' => 'a', 'conditions' => $conditions])
            ->orderBy(['title' => 'asc'])
            ->execute();
        $this->assertEquals(['title' => 'First Article', 'name' => 'mariano'], $result->fetch('assoc'));
        $this->assertEquals(['title' => 'Second Article', 'name' => 'larry'], $result->fetch('assoc'));
        $result->closeCursor();

        $query = new SelectQuery($this->connection);
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
        $query = new SelectQuery($this->connection);
        $result = $query
            ->select(['title', 'name'])
            ->from('articles')
            ->join(['a' => 'authors'])
            ->orderBy(['name' => 'desc', 'articles.id' => 'asc'])
            ->execute();
        $this->assertEquals(['title' => 'First Article', 'name' => 'nate'], $result->fetch('assoc'));
        $this->assertEquals(['title' => 'Second Article', 'name' => 'nate'], $result->fetch('assoc'));
        $result->closeCursor();

        $query = new SelectQuery($this->connection);
        $conditions = $query->newExpr('author_id = a.id');
        $result = $query
            ->select(['title', 'name'])
            ->from('articles')
            ->join(['a' => ['table' => 'authors', 'conditions' => $conditions]])
            ->orderBy(['title' => 'asc'])
            ->execute();
        $this->assertEquals(['title' => 'First Article', 'name' => 'mariano'], $result->fetch('assoc'));
        $this->assertEquals(['title' => 'Second Article', 'name' => 'larry'], $result->fetch('assoc'));
        $result->closeCursor();

        $query = new SelectQuery($this->connection);
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
        $query = new SelectQuery($this->connection);
        $time = new DateTime('2007-03-18 10:45:23');
        $types = ['created' => 'datetime'];
        $result = $query
            ->select(['title', 'name' => 'c.comment'])
            ->from('articles')
            ->leftJoin(['c' => 'comments'], ['created <' => $time], $types)
            ->execute();
        $this->assertEquals(['title' => 'First Article', 'name' => null], $result->fetch('assoc'));
        $result->closeCursor();

        $query = new SelectQuery($this->connection);
        $result = $query
            ->select(['title', 'name' => 'c.comment'])
            ->from('articles')
            ->leftJoin(['c' => 'comments'], ['created >' => $time], $types)
            ->orderBy(['created' => 'asc'])
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
        $query = new SelectQuery($this->connection);
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
        $query = new SelectQuery($this->connection);
        $time = new DateTime('2007-03-18 10:45:23');
        $types = ['created' => 'datetime'];
        $result = $query
            ->select(['title', 'name' => 'c.comment'])
            ->from('articles')
            ->rightJoin(['c' => 'comments'], ['created <' => $time], $types)
            ->execute();
        $rows = $result->fetchAll('assoc');
        $this->assertCount(6, $rows);
        $this->assertEquals(
            ['title' => null, 'name' => 'First Comment for First Article'],
            $rows[0]
        );
        $result->closeCursor();
    }

    /**
     * Tests that it is possible to pass a callable as conditions for a join
     */
    public function testSelectJoinWithCallback(): void
    {
        $query = new SelectQuery($this->connection);
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
        $query = new SelectQuery($this->connection);
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
        $query = new SelectQuery($this->connection);
        $result = $query
            ->select(['title'])
            ->from('articles')
            ->where(['id' => 1, 'title' => 'First Article'])
            ->execute();
        $this->assertCount(1, $result->fetchAll());
        $result->closeCursor();

        $query = new SelectQuery($this->connection);
        $result = $query
            ->select(['title'])
            ->from('articles')
            ->where(['id' => 100], ['id' => 'integer'])
            ->execute();
        $this->assertCount(0, $result->fetchAll());
        $result->closeCursor();
    }

    /**
     * Tests using where conditions with operators and scalar values works
     */
    public function testSelectWhereOperatorMoreThan(): void
    {
        $query = new SelectQuery($this->connection);
        $result = $query
            ->select(['comment'])
            ->from('comments')
            ->where(['id >' => 4])
            ->execute();
        $rows = $result->fetchAll('assoc');
        $this->assertCount(2, $rows);
        $this->assertEquals(['comment' => 'First Comment for Second Article'], $rows[0]);
        $result->closeCursor();
    }

    /**
     * Tests using where conditions with operators and scalar values works
     */
    public function testSelectWhereOperatorLessThan(): void
    {
        $query = new SelectQuery($this->connection);
        $result = $query
            ->select(['title'])
            ->from('articles')
            ->where(['id <' => 2])
            ->execute();
        $rows = $result->fetchAll('assoc');
        $this->assertCount(1, $rows);
        $this->assertEquals(['title' => 'First Article'], $rows[0]);
        $result->closeCursor();
    }

    /**
     * Tests using where conditions with operators and scalar values works
     */
    public function testSelectWhereOperatorLessThanEqual(): void
    {
        $query = new SelectQuery($this->connection);
        $result = $query
            ->select(['title'])
            ->from('articles')
            ->where(['id <=' => 2])
            ->execute();
        $this->assertCount(2, $result->fetchAll());
        $result->closeCursor();
    }

    /**
     * Tests using where conditions with operators and scalar values works
     */
    public function testSelectWhereOperatorMoreThanEqual(): void
    {
        $query = new SelectQuery($this->connection);
        $result = $query
            ->select(['title'])
            ->from('articles')
            ->where(['id >=' => 1])
            ->execute();
        $this->assertCount(3, $result->fetchAll());
        $result->closeCursor();
    }

    /**
     * Tests using where conditions with operators and scalar values works
     */
    public function testSelectWhereOperatorNotEqual(): void
    {
        $query = new SelectQuery($this->connection);
        $result = $query
            ->select(['title'])
            ->from('articles')
            ->where(['id !=' => 2])
            ->execute();
        $rows = $result->fetchAll('assoc');
        $this->assertCount(2, $rows);
        $this->assertEquals(['title' => 'First Article'], $rows[0]);
        $result->closeCursor();
    }

    /**
     * Tests using where conditions with operators and scalar values works
     */
    public function testSelectWhereOperatorLike(): void
    {
        $query = new SelectQuery($this->connection);
        $result = $query
            ->select(['title'])
            ->from('articles')
            ->where(['title LIKE' => 'First Article'])
            ->execute();
        $rows = $result->fetchAll('assoc');
        $this->assertCount(1, $rows);
        $this->assertEquals(['title' => 'First Article'], $rows[0]);
        $result->closeCursor();
    }

    /**
     * Tests using where conditions with operators and scalar values works
     */
    public function testSelectWhereOperatorLikeExpansion(): void
    {
        $query = new SelectQuery($this->connection);
        $result = $query
            ->select(['title'])
            ->from('articles')
            ->where(['title like' => '%Article%'])
            ->execute();
        $this->assertCount(3, $result->fetchAll());
        $result->closeCursor();
    }

    /**
     * Tests using where conditions with operators and scalar values works
     */
    public function testSelectWhereOperatorNotLike(): void
    {
        $query = new SelectQuery($this->connection);
        $result = $query
            ->select(['title'])
            ->from('articles')
            ->where(['title not like' => '%Article%'])
            ->execute();
        $this->assertCount(0, $result->fetchAll());
        $result->closeCursor();
    }

    /**
     * Test that unary expressions in selects are built correctly.
     */
    public function testSelectWhereUnary(): void
    {
        $query = new SelectQuery($this->connection);
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
        $query = new SelectQuery($this->connection);
        $result = $query
            ->select(['id'])
            ->from('comments')
            ->where(['created' => new DateTime('2007-03-18 10:45:23')], ['created' => 'datetime'])
            ->execute();
        $rows = $result->fetchAll('assoc');
        $this->assertCount(1, $rows);
        $this->assertEquals(['id' => 1], $rows[0]);
        $result->closeCursor();

        $query = new SelectQuery($this->connection);
        $result = $query
            ->select(['id'])
            ->from('comments')
            ->where(['created >' => new DateTime('2007-03-18 10:46:00')], ['created' => 'datetime'])
            ->execute();
        $rows = $result->fetchAll('assoc');
        $this->assertCount(5, $rows);
        $this->assertEquals(['id' => 2], $rows[0]);
        $this->assertEquals(['id' => 3], $rows[1]);
        $result->closeCursor();

        $query = new SelectQuery($this->connection);
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
        $rows = $result->fetchAll('assoc');
        $this->assertCount(1, $rows);
        $this->assertEquals(['id' => 1], $rows[0]);
        $result->closeCursor();

        $query = new SelectQuery($this->connection);
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
        $rows = $result->fetchAll('assoc');
        $this->assertCount(1, $rows);
        $this->assertEquals(['id' => 3], $rows[0]);
        $result->closeCursor();

        $query = new SelectQuery($this->connection);
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
        $rows = $result->fetchAll('assoc');
        $this->assertCount(1, $rows);
        $this->assertEquals(['id' => 1], $rows[0]);
        $result->closeCursor();
    }

    /**
     * Tests Query::whereNull()
     */
    public function testSelectWhereNull(): void
    {
        $query = new SelectQuery($this->connection);
        $result = $query
            ->select(['id', 'parent_id'])
            ->from('menu_link_trees')
            ->whereNull(['parent_id'])
            ->execute();
        $this->assertCount(5, $result->fetchAll());
        $result->closeCursor();

        $query = new SelectQuery($this->connection);
        $result = $query
            ->select(['id'])
            ->from('menu_link_trees')
            ->whereNull((new SelectQuery($this->connection))->select('parent_id'))
            ->execute();
        $this->assertCount(5, $result->fetchAll());
        $result->closeCursor();

        $query = new SelectQuery($this->connection);
        $result = $query
            ->select(['id'])
            ->from('menu_link_trees')
            ->whereNull('parent_id')
            ->execute();
        $this->assertCount(5, $result->fetchAll());
        $result->closeCursor();
    }

    /**
     * Tests Query::whereNotNull()
     */
    public function testSelectWhereNotNull(): void
    {
        $query = new SelectQuery($this->connection);
        $result = $query
            ->select(['id', 'parent_id'])
            ->from('menu_link_trees')
            ->whereNotNull(['parent_id'])
            ->execute();
        $this->assertCount(13, $result->fetchAll());
        $result->closeCursor();

        $query = new SelectQuery($this->connection);
        $result = $query
            ->select(['id'])
            ->from('menu_link_trees')
            ->whereNotNull((new SelectQuery($this->connection))->select('parent_id'))
            ->execute();
        $this->assertCount(13, $result->fetchAll());
        $result->closeCursor();

        $query = new SelectQuery($this->connection);
        $result = $query
            ->select(['id'])
            ->from('menu_link_trees')
            ->whereNotNull('parent_id')
            ->execute();
        $this->assertCount(13, $result->fetchAll());
        $result->closeCursor();
    }

    /**
     * Tests that passing an array type to any where condition will replace
     * the passed array accordingly as a proper IN condition
     */
    public function testSelectWhereArrayType(): void
    {
        $query = new SelectQuery($this->connection);
        $result = $query
            ->select(['id'])
            ->from('comments')
            ->where(['id' => ['1', '3']], ['id' => 'integer[]'])
            ->execute();
        $rows = $result->fetchAll('assoc');
        $this->assertCount(2, $rows);
        $this->assertEquals(['id' => 1], $rows[0]);
        $this->assertEquals(['id' => 3], $rows[1]);
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
        $query = new SelectQuery($this->connection);
        $query
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
        $query = new SelectQuery($this->connection);
        $query
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
        $query = new SelectQuery($this->connection);
        $result = $query
            ->select(['id'])
            ->from('comments')
            ->where(['created' => new DateTime('2007-03-18 10:45:23')], ['created' => 'datetime'])
            ->andWhere(['id' => 1])
            ->execute();
        $rows = $result->fetchAll('assoc');
        $this->assertCount(1, $rows);
        $this->assertEquals(['id' => 1], $rows[0]);
        $result->closeCursor();

        $query = new SelectQuery($this->connection);
        $result = $query
            ->select(['id'])
            ->from('comments')
            ->where(['created' => new DateTime('2007-03-18 10:50:55')], ['created' => 'datetime'])
            ->andWhere(['id' => 2])
            ->execute();
        $this->assertCount(0, $result->fetchAll());
        $result->closeCursor();
    }

    /**
     * Tests that Query::andWhere() can be used without calling where() before
     */
    public function testSelectAndWhereNoPreviousCondition(): void
    {
        $query = new SelectQuery($this->connection);
        $result = $query
            ->select(['id'])
            ->from('comments')
            ->andWhere(['created' => new DateTime('2007-03-18 10:45:23')], ['created' => 'datetime'])
            ->andWhere(['id' => 1])
            ->execute();
        $rows = $result->fetchAll('assoc');
        $this->assertCount(1, $rows);
        $this->assertEquals(['id' => 1], $rows[0]);
        $result->closeCursor();
    }

    /**
     * Tests that it is possible to pass a closure to where() to build a set of
     * conditions and return the expression to be used
     */
    public function testSelectWhereUsingClosure(): void
    {
        $query = new SelectQuery($this->connection);
        $result = $query
            ->select(['id'])
            ->from('comments')
            ->where(function (ExpressionInterface $exp) {
                return $exp->eq('id', 1);
            })
            ->execute();
        $rows = $result->fetchAll('assoc');
        $this->assertCount(1, $rows);
        $this->assertEquals(['id' => 1], $rows[0]);
        $result->closeCursor();

        $query = new SelectQuery($this->connection);
        $result = $query
            ->select(['id'])
            ->from('comments')
            ->where(function (ExpressionInterface $exp) {
                return $exp
                    ->eq('id', 1)
                    ->eq('created', new DateTime('2007-03-18 10:45:23'), 'datetime');
            })
            ->execute();
        $rows = $result->fetchAll('assoc');
        $this->assertCount(1, $rows);
        $this->assertEquals(['id' => 1], $rows[0]);
        $result->closeCursor();

        $query = new SelectQuery($this->connection);
        $result = $query
            ->select(['id'])
            ->from('comments')
            ->where(function (ExpressionInterface $exp) {
                return $exp
                    ->eq('id', 1)
                    ->eq('created', new DateTime('2021-12-30 15:00'), 'datetime');
            })
            ->execute();
        $this->assertCount(0, $result->fetchAll());
        $result->closeCursor();
    }

    /**
     * Tests generating tuples in the values side containing closure expressions
     */
    public function testTupleWithClosureExpression(): void
    {
        $query = new SelectQuery($this->connection);
        $query->select(['id'])
            ->from('comments')
            ->where([
                'OR' => [
                    'id' => 1,
                    function (ExpressionInterface $exp) {
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
        $query = new SelectQuery($this->connection);
        $result = $query
            ->select(['id'])
            ->from('comments')
            ->where(['id' => '1'])
            ->andWhere(function (ExpressionInterface $exp) {
                return $exp->eq('created', new DateTime('2007-03-18 10:45:23'), 'datetime');
            })
            ->execute();
        $rows = $result->fetchAll('assoc');
        $this->assertCount(1, $rows);
        $this->assertEquals(['id' => 1], $rows[0]);
        $result->closeCursor();

        $query = new SelectQuery($this->connection);
        $result = $query
            ->select(['id'])
            ->from('comments')
            ->where(['id' => '1'])
            ->andWhere(function (ExpressionInterface $exp) {
                return $exp->eq('created', new DateTime('2022-12-21 12:00'), 'datetime');
            })
            ->execute();
        $this->assertCount(0, $result->fetchAll());
        $result->closeCursor();
    }

    /**
     * Tests that expression objects can be used as the field in a comparison
     * and the values will be bound correctly to the query
     */
    public function testSelectWhereUsingExpressionInField(): void
    {
        $query = new SelectQuery($this->connection);
        $result = $query
            ->select(['id'])
            ->from('comments')
            ->where(function (ExpressionInterface $exp) {
                $field = clone $exp;
                $field->add('SELECT min(id) FROM comments');

                return $exp
                    ->eq($field, 100, 'integer');
            })
            ->execute();
        $this->assertCount(0, $result->fetchAll());
        $result->closeCursor();
    }

    /**
     * Tests using where conditions with operator methods
     */
    public function testSelectWhereOperatorMethods(): void
    {
        $query = new SelectQuery($this->connection);
        $result = $query
            ->select(['title'])
            ->from('articles')
            ->where(function (ExpressionInterface $exp) {
                return $exp->gt('id', 1);
            })
            ->execute();
        $rows = $result->fetchAll('assoc');
        $this->assertCount(2, $rows);
        $this->assertEquals(['title' => 'Second Article'], $rows[0]);
        $result->closeCursor();

        $query = new SelectQuery($this->connection);
        $result = $query->select(['title'])
            ->from('articles')
            ->where(function (ExpressionInterface $exp) {
                return $exp->lt('id', 2);
            })
            ->execute();
        $rows = $result->fetchAll('assoc');
        $this->assertCount(1, $rows);
        $this->assertEquals(['title' => 'First Article'], $rows[0]);
        $result->closeCursor();

        $query = new SelectQuery($this->connection);
        $result = $query->select(['title'])
            ->from('articles')
            ->where(function (ExpressionInterface $exp) {
                return $exp->lte('id', 2);
            })
            ->execute();
        $this->assertCount(2, $result->fetchAll());
        $result->closeCursor();

        $query = new SelectQuery($this->connection);
        $result = $query
            ->select(['title'])
            ->from('articles')
            ->where(function (ExpressionInterface $exp) {
                return $exp->gte('id', 1);
            })
            ->execute();
        $this->assertCount(3, $result->fetchAll());
        $result->closeCursor();

        $query = new SelectQuery($this->connection);
        $result = $query
            ->select(['title'])
            ->from('articles')
            ->where(function (ExpressionInterface $exp) {
                return $exp->lte('id', 1);
            })
            ->execute();
        $this->assertCount(1, $result->fetchAll());
        $result->closeCursor();

        $query = new SelectQuery($this->connection);
        $result = $query
            ->select(['title'])
            ->from('articles')
            ->where(function (ExpressionInterface $exp) {
                return $exp->notEq('id', 2);
            })
            ->execute();
        $rows = $result->fetchAll('assoc');
        $this->assertCount(2, $rows);
        $this->assertEquals(['title' => 'First Article'], $rows[0]);
        $result->closeCursor();

        $query = new SelectQuery($this->connection);
        $result = $query
            ->select(['title'])
            ->from('articles')
            ->where(function (ExpressionInterface $exp) {
                return $exp->like('title', 'First Article');
            })
            ->execute();
        $rows = $result->fetchAll('assoc');
        $this->assertCount(1, $rows);
        $this->assertEquals(['title' => 'First Article'], $rows[0]);
        $result->closeCursor();

        $query = new SelectQuery($this->connection);
        $result = $query
            ->select(['title'])
            ->from('articles')
            ->where(function (ExpressionInterface $exp) {
                return $exp->like('title', '%Article%');
            })
            ->execute();
        $this->assertCount(3, $result->fetchAll());
        $result->closeCursor();

        $query = new SelectQuery($this->connection);
        $result = $query
            ->select(['title'])
            ->from('articles')
            ->where(function (ExpressionInterface $exp) {
                return $exp->notLike('title', '%Article%');
            })
            ->execute();
        $this->assertCount(0, $result->fetchAll());
        $result->closeCursor();

        $query = new SelectQuery($this->connection);
        $result = $query
            ->select(['id'])
            ->from('comments')
            ->where(function (ExpressionInterface $exp) {
                return $exp->isNull('published');
            })
            ->execute();
        $this->assertCount(0, $result->fetchAll());
        $result->closeCursor();

        $query = new SelectQuery($this->connection);
        $result = $query
            ->select(['id'])
            ->from('comments')
            ->where(function (ExpressionInterface $exp) {
                return $exp->isNotNull('published');
            })
            ->execute();
        $this->assertCount(6, $result->fetchAll());
        $result->closeCursor();

        $query = new SelectQuery($this->connection);
        $result = $query
            ->select(['id'])
            ->from('comments')
            ->where(function (ExpressionInterface $exp) {
                return $exp->in('published', ['Y', 'N']);
            })
            ->execute();
        $this->assertCount(6, $result->fetchAll());
        $result->closeCursor();

        $query = new SelectQuery($this->connection);
        $result = $query
            ->select(['id'])
            ->from('comments')
            ->where(function (ExpressionInterface $exp) {
                return $exp->in(
                    'created',
                    [new DateTime('2007-03-18 10:45:23'), new DateTime('2007-03-18 10:47:23')],
                    'datetime'
                );
            })
            ->execute();
        $rows = $result->fetchAll('assoc');
        $this->assertCount(2, $rows);
        $this->assertEquals(['id' => 1], $rows[0]);
        $this->assertEquals(['id' => 2], $rows[1]);
        $result->closeCursor();

        $query = new SelectQuery($this->connection);
        $result = $query
            ->select(['id'])
            ->from('comments')
            ->where(function (ExpressionInterface $exp) {
                return $exp->notIn(
                    'created',
                    [new DateTime('2007-03-18 10:45:23'), new DateTime('2007-03-18 10:47:23')],
                    'datetime'
                );
            })
            ->execute();
        $rows = $result->fetchAll('assoc');
        $this->assertCount(4, $rows);
        $this->assertEquals(['id' => 3], $rows[0]);
        $result->closeCursor();
    }

    /**
     * Tests that calling "in" and "notIn" will cast the passed values to an array
     */
    public function testInValueCast(): void
    {
        $query = new SelectQuery($this->connection);
        $result = $query
            ->select(['id'])
            ->from('comments')
            ->where(function (ExpressionInterface $exp) {
                return $exp->in('created', '2007-03-18 10:45:23', 'datetime');
            })
            ->execute();
        $rows = $result->fetchAll('assoc');
        $this->assertCount(1, $rows);
        $this->assertEquals(['id' => 1], $rows[0]);
        $result->closeCursor();

        $query = new SelectQuery($this->connection);
        $result = $query
            ->select(['id'])
            ->from('comments')
            ->where(function (ExpressionInterface $exp) {
                return $exp->notIn('created', '2007-03-18 10:45:23', 'datetime');
            })
            ->execute();
        $rows = $result->fetchAll('assoc');
        $this->assertCount(5, $rows);
        $this->assertEquals(['id' => 2], $rows[0]);
        $this->assertEquals(['id' => 3], $rows[1]);
        $this->assertEquals(['id' => 4], $rows[2]);
        $this->assertEquals(['id' => 5], $rows[3]);
        $result->closeCursor();

        $query = new SelectQuery($this->connection);
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
        $rows = $result->fetchAll('assoc');
        $this->assertCount(1, $rows);
        $this->assertEquals(['id' => 1], $rows[0]);
        $result->closeCursor();

        $query = new SelectQuery($this->connection);
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
        $this->assertCount(5, $result->fetchAll());
        $result->closeCursor();
    }

    /**
     * Tests that calling "in" and "notIn" will cast the passed values to an array
     */
    public function testInValueCast2(): void
    {
        $query = new SelectQuery($this->connection);
        $result = $query
            ->select(['id'])
            ->from('comments')
            ->where(['created IN' => '2007-03-18 10:45:23'])
            ->execute();

        $rows = $result->fetchAll('assoc');
        $this->assertCount(1, $rows);
        $this->assertEquals(['id' => 1], $rows[0]);
        $result->closeCursor();

        $query = new SelectQuery($this->connection);
        $result = $query
            ->select(['id'])
            ->from('comments')
            ->where(['created NOT IN' => '2007-03-18 10:45:23'])
            ->execute();
        $this->assertCount(5, $result->fetchAll());
        $result->closeCursor();
    }

    /**
     * Tests that IN clauses generate correct placeholders
     */
    public function testInClausePlaceholderGeneration(): void
    {
        $query = new SelectQuery($this->connection);
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
        $query = new SelectQuery($this->connection);
        $query->select(['id'])
            ->from('articles')
            ->where([
                'id' => 'Cake\Error\Debugger::dump',
                'title' => ['Cake\Error\Debugger', 'dump'],
                'author_id' => function (ExpressionInterface $exp) {
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
        $query = new SelectQuery($this->connection);
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
        $query = new SelectQuery($this->connection);
        $result = $query
            ->select(['id'])
            ->from('comments')
            ->where(function (ExpressionInterface $exp) {
                return $exp->between('id', 5, 6, 'integer');
            })
            ->execute();

        $rows = $result->fetchAll('assoc');
        $this->assertCount(2, $rows);
        $this->assertEquals(5, $rows[0]['id']);

        $this->assertEquals(6, $rows[1]['id']);
        $result->closeCursor();
    }

    /**
     * Tests that it is possible to use a between expression
     * in a where condition with a complex data type
     */
    public function testWhereWithBetweenComplex(): void
    {
        $query = new SelectQuery($this->connection);
        $result = $query
            ->select(['id'])
            ->from('comments')
            ->where(function (ExpressionInterface $exp) {
                $from = new DateTime('2007-03-18 10:51:00');
                $to = new DateTime('2007-03-18 10:54:00');

                return $exp->between('created', $from, $to, 'datetime');
            })
            ->execute();

        $rows = $result->fetchAll('assoc');
        $this->assertCount(2, $rows);
        $this->assertEquals(4, $rows[0]['id']);

        $this->assertEquals(5, $rows[1]['id']);
        $result->closeCursor();
    }

    /**
     * Tests that it is possible to use an expression object
     * as the field for a between expression
     */
    public function testWhereWithBetweenWithExpressionField(): void
    {
        $query = new SelectQuery($this->connection);
        $result = $query
            ->select(['id'])
            ->from('comments')
            ->where(function ($exp, $q) {
                $field = $q->func()->coalesce([new IdentifierExpression('id'), 1 => 'literal']);

                return $exp->between($field, 5, 6, 'integer');
            })
            ->execute();

        $rows = $result->fetchAll('assoc');
        $this->assertCount(2, $rows);
        $this->assertEquals(5, $rows[0]['id']);

        $this->assertEquals(6, $rows[1]['id']);
        $result->closeCursor();
    }

    /**
     * Tests that it is possible to use an expression object
     * as any of the parts of the between expression
     */
    public function testWhereWithBetweenWithExpressionParts(): void
    {
        $query = new SelectQuery($this->connection);
        $result = $query
            ->select(['id'])
            ->from('comments')
            ->where(function (ExpressionInterface $exp, Query $q) {
                $from = $q->newExpr("'2007-03-18 10:51:00'");
                $to = $q->newExpr("'2007-03-18 10:54:00'");

                return $exp->between('created', $from, $to);
            })
            ->execute();

        $rows = $result->fetchAll('assoc');
        $this->assertCount(2, $rows);
        $this->assertEquals(4, $rows[0]['id']);

        $this->assertEquals(5, $rows[1]['id']);
        $result->closeCursor();
    }

    /**
     * Tests nesting query expressions both using arrays and closures
     */
    public function testSelectExpressionComposition(): void
    {
        $query = new SelectQuery($this->connection);
        $result = $query
            ->select(['id'])
            ->from('comments')
            ->where(function (ExpressionInterface $exp) {
                $and = $exp->and(['id' => 2, 'id >' => 1]);

                return $exp->add($and);
            })
            ->execute();
        $rows = $result->fetchAll('assoc');
        $this->assertCount(1, $rows);
        $this->assertEquals(['id' => 2], $rows[0]);
        $result->closeCursor();

        $query = new SelectQuery($this->connection);
        $result = $query
            ->select(['id'])
            ->from('comments')
            ->where(function (ExpressionInterface $exp) {
                $and = $exp->and(['id' => 2, 'id <' => 2]);

                return $exp->add($and);
            })
            ->execute();
        $this->assertCount(0, $result->fetchAll());
        $result->closeCursor();

        $query = new SelectQuery($this->connection);
        $result = $query
            ->select(['id'])
            ->from('comments')
            ->where(function (ExpressionInterface $exp) {
                $and = $exp->and(function ($and) {
                    return $and->eq('id', 1)->gt('id', 0);
                });

                return $exp->add($and);
            })
            ->execute();
        $rows = $result->fetchAll('assoc');
        $this->assertCount(1, $rows);
        $this->assertEquals(['id' => 1], $rows[0]);
        $result->closeCursor();

        $query = new SelectQuery($this->connection);
        $result = $query
            ->select(['id'])
            ->from('comments')
            ->where(function (ExpressionInterface $exp) {
                $or = $exp->or(['id' => 1]);
                $and = $exp->and(['id >' => 2, 'id <' => 4]);

                return $or->add($and);
            })
            ->execute();
        $rows = $result->fetchAll('assoc');
        $this->assertCount(2, $rows);
        $this->assertEquals(['id' => 1], $rows[0]);
        $this->assertEquals(['id' => 3], $rows[1]);
        $result->closeCursor();

        $query = new SelectQuery($this->connection);
        $result = $query
            ->select(['id'])
            ->from('comments')
            ->where(function (ExpressionInterface $exp) {
                $or = $exp->or(function ($or) {
                    return $or->eq('id', 1)->eq('id', 2);
                });

                return $or;
            })
            ->execute();
        $rows = $result->fetchAll('assoc');
        $this->assertCount(2, $rows);
        $this->assertEquals(['id' => 1], $rows[0]);
        $this->assertEquals(['id' => 2], $rows[1]);
        $result->closeCursor();
    }

    /**
     * Tests that conditions can be nested with an unary operator using the array notation
     * and the not() method
     */
    public function testSelectWhereNot(): void
    {
        $query = new SelectQuery($this->connection);
        $result = $query
            ->select(['id'])
            ->from('comments')
            ->where(function (ExpressionInterface $exp) {
                return $exp->not(
                    $exp->and(['id' => 2, 'created' => new DateTime('2007-03-18 10:47:23')], ['created' => 'datetime'])
                );
            })
            ->execute();
        $rows = $result->fetchAll('assoc');
        $this->assertCount(5, $rows);
        $this->assertEquals(['id' => 1], $rows[0]);
        $this->assertEquals(['id' => 3], $rows[1]);
        $result->closeCursor();

        $query = new SelectQuery($this->connection);
        $result = $query
            ->select(['id'])
            ->from('comments')
            ->where(function (ExpressionInterface $exp) {
                return $exp->not(
                    $exp->and(['id' => 2, 'created' => new DateTime('2012-12-21 12:00')], ['created' => 'datetime'])
                );
            })
            ->execute();
        $this->assertCount(6, $result->fetchAll());
        $result->closeCursor();
    }

    /**
     * Tests that conditions can be nested with an unary operator using the array notation
     * and the not() method
     */
    public function testSelectWhereNot2(): void
    {
        $query = new SelectQuery($this->connection);
        $result = $query
            ->select(['id'])
            ->from('articles')
            ->where([
                'not' => ['or' => ['id' => 1, 'id >' => 2], 'id' => 3],
            ])
            ->execute();

        $rows = $result->fetchAll('assoc');
        $this->assertCount(2, $rows);
        $this->assertEquals(['id' => 1], $rows[0]);
        $this->assertEquals(['id' => 2], $rows[1]);
        $result->closeCursor();
    }

    /**
     * Tests whereInArray() and its input types.
     */
    public function testWhereInArray(): void
    {
        $query = new SelectQuery($this->connection);
        $query->select(['id'])
            ->from('articles')
            ->whereInList('id', [2, 3])
            ->orderBy(['id']);

        $sql = $query->sql();
        $this->assertQuotedQuery(
            'SELECT <id> FROM <articles> WHERE <id> IN \\(:c0,:c1\\)',
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
        $query = new SelectQuery($this->connection);
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
        $query = new SelectQuery($this->connection);
        $query->select(['id'])
            ->from('articles')
            ->whereNotInList('id', [1, 3]);

        $this->assertQuotedQuery(
            'SELECT <id> FROM <articles> WHERE <id> NOT IN \\(:c0,:c1\\)',
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
        $query = new SelectQuery($this->connection);
        $query->select(['id'])
            ->from('articles')
            ->whereNotInList('id', [], ['allowEmpty' => true])
            ->orderBy(['id']);

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
        $query = new SelectQuery($this->connection);
        $query->select(['id'])
            ->from('articles')
            ->whereNotInListOrNull('id', [1, 3]);

        $this->assertQuotedQuery(
            'SELECT <id> FROM <articles> WHERE \\(<id> NOT IN \\(:c0,:c1\\) OR \\(<id>\\) IS NULL\\)',
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
        $query = new SelectQuery($this->connection);
        $query->select(['id'])
            ->from('articles')
            ->whereNotInListOrNull('id', [], ['allowEmpty' => true])
            ->orderBy(['id']);

        $this->assertQuotedQuery(
            'SELECT <id> FROM <articles> WHERE \(<id>\) IS NOT NULL',
            $query->sql(),
            !$this->autoQuote
        );

        $result = $query->execute()->fetchAll('assoc');
        $this->assertEquals(['id' => '1'], $result[0]);
    }

    /**
     * Tests orderBy() method both with simple fields and expressions
     */
    public function testSelectOrderBy(): void
    {
        $query = new SelectQuery($this->connection);
        $result = $query
            ->select(['id'])
            ->from('comments')
            ->orderBy(['id' => 'desc'])
            ->execute();
        $this->assertEquals(['id' => 6], $result->fetch('assoc'));
        $this->assertEquals(['id' => 5], $result->fetch('assoc'));
        $this->assertEquals(['id' => 4], $result->fetch('assoc'));
        $result->closeCursor();

        $result = $query->orderBy(['id' => 'asc'])->execute();
        $this->assertEquals(['id' => 1], $result->fetch('assoc'));
        $this->assertEquals(['id' => 2], $result->fetch('assoc'));
        $this->assertEquals(['id' => 3], $result->fetch('assoc'));
        $result->closeCursor();

        $result = $query->orderBy(['comment' => 'asc'])->execute();
        $this->assertEquals(['id' => 1], $result->fetch('assoc'));
        $this->assertEquals(['id' => 2], $result->fetch('assoc'));
        $this->assertEquals(['id' => 3], $result->fetch('assoc'));
        $result->closeCursor();

        $result = $query->orderBy(['comment' => 'asc'], true)->execute();
        $this->assertEquals(['id' => 1], $result->fetch('assoc'));
        $this->assertEquals(['id' => 5], $result->fetch('assoc'));
        $this->assertEquals(['id' => 4], $result->fetch('assoc'));
        $result->closeCursor();

        $result = $query->orderBy(['user_id' => 'asc', 'created' => 'desc'], true)
            ->execute();
        $this->assertEquals(['id' => 5], $result->fetch('assoc'));
        $this->assertEquals(['id' => 4], $result->fetch('assoc'));
        $this->assertEquals(['id' => 3], $result->fetch('assoc'));
        $result->closeCursor();

        $expression = $query->newExpr(['(id + :offset) % 2']);
        $result = $query
            ->orderBy([$expression, 'id' => 'desc'], true)
            ->bind(':offset', 1, null)
            ->execute();
        $this->assertEquals(['id' => 5], $result->fetch('assoc'));
        $this->assertEquals(['id' => 3], $result->fetch('assoc'));
        $this->assertEquals(['id' => 1], $result->fetch('assoc'));
        $result->closeCursor();

        $result = $query
            ->orderBy($expression, true)
            ->orderBy(['id' => 'asc'])
            ->bind(':offset', 1, null)
            ->execute();
        $this->assertEquals(['id' => 1], $result->fetch('assoc'));
        $this->assertEquals(['id' => 3], $result->fetch('assoc'));
        $this->assertEquals(['id' => 5], $result->fetch('assoc'));
        $result->closeCursor();
    }

    public function testSelectOrderDeprecated(): void
    {
        $query = new SelectQuery($this->connection);
        $result = $query
            ->select(['id'])
            ->from('comments')
            ->order(['id' => 'desc'])
            ->execute();
        $this->assertEquals([6, 5, 4, 3, 2, 1], array_column($result->fetchAll('assoc'), 'id'));

        $query = new SelectQuery($this->connection);
        $result = $query
            ->select(['id'])
            ->from('comments')
            ->orderDesc('id')
            ->execute();
        $this->assertEquals([6, 5, 4, 3, 2, 1], array_column($result->fetchAll('assoc'), 'id'));

        $query = new SelectQuery($this->connection);
        $result = $query
            ->select(['user_id'])
            ->from('comments')
            ->orderAsc('user_id')
            ->execute();
        $this->assertEquals([1, 1, 1, 2, 2, 4], array_column($result->fetchAll('assoc'), 'user_id'));
    }

    /**
     * Test that orderBy() being a string works.
     */
    public function testSelectOrderByString(): void
    {
        $query = new SelectQuery($this->connection);
        $query->select(['id'])
            ->from('articles')
            ->orderBy('id asc');
        $result = $query->execute();
        $this->assertEquals(['id' => 1], $result->fetch('assoc'));
        $this->assertEquals(['id' => 2], $result->fetch('assoc'));
        $this->assertEquals(['id' => 3], $result->fetch('assoc'));
        $result->closeCursor();
    }

    /**
     * Test exception for orderBy() with an associative array which contains extra values.
     */
    public function testSelectOrderByAssociativeArrayContainingExtraExpressions(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'Passing extra expressions by associative array (`\'id\' => \'desc -- Comment\'`) ' .
            'is not allowed to avoid potential SQL injection. ' .
            'Use QueryExpression or numeric array instead.'
        );

        $query = new SelectQuery($this->connection);
        $query->select(['id'])
            ->from('articles')
            ->orderBy([
                'id' => 'desc -- Comment',
            ]);
    }

    /**
     * Tests that orderBy() works with closures.
     */
    public function testSelectOrderByClosure(): void
    {
        $query = new SelectQuery($this->connection);
        $query
            ->select('*')
            ->from('articles')
            ->orderBy(function ($exp, $q) use ($query) {
                $this->assertInstanceOf(QueryExpression::class, $exp);
                $this->assertSame($query, $q);

                return ['id' => 'ASC'];
            });

        $this->assertQuotedQuery(
            'SELECT \* FROM <articles> ORDER BY <id> ASC',
            $query->sql(),
            !$this->autoQuote
        );

        $query = new SelectQuery($this->connection);
        $query
            ->select('*')
            ->from('articles')
            ->orderBy(function (ExpressionInterface $exp) {
                return [$exp->add(['id % 2 = 0']), 'title' => 'ASC'];
            });

        $this->assertQuotedQuery(
            'SELECT \* FROM <articles> ORDER BY id % 2 = 0, <title> ASC',
            $query->sql(),
            !$this->autoQuote
        );

        $query = new SelectQuery($this->connection);
        $query
            ->select('*')
            ->from('articles')
            ->orderBy(function (ExpressionInterface $exp) {
                return $exp->add('a + b');
            });

        $this->assertQuotedQuery(
            'SELECT \* FROM <articles> ORDER BY a \+ b',
            $query->sql(),
            !$this->autoQuote
        );

        $query = new SelectQuery($this->connection);
        $query
            ->select('*')
            ->from('articles')
            ->orderBy(function ($exp, $q) {
                return $q->func()->sum('a');
            });

        $this->assertQuotedQuery(
            'SELECT \* FROM <articles> ORDER BY SUM\(a\)',
            $query->sql(),
            !$this->autoQuote
        );
    }

    /**
     * Test orderByAsc() and its input types.
     */
    public function testSelectOrderByAsc(): void
    {
        $query = new SelectQuery($this->connection);
        $query->select(['id'])
            ->from('articles')
            ->orderByAsc('id');

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

        $query = new SelectQuery($this->connection);
        $query->select(['id'])
            ->from('articles')
            ->orderByAsc($query->func()->concat(['id' => 'identifier', '3']));

        $result = $query->execute()->fetchAll('assoc');
        $expected = [
            ['id' => 1],
            ['id' => 2],
            ['id' => 3],
        ];
        $this->assertEquals($expected, $result);

        $query = new SelectQuery($this->connection);
        $query->select(['id'])
            ->from('articles')
            ->orderByAsc(function (QueryExpression $exp, Query $query) {
                return $exp
                    ->case()
                    ->when(['author_id' => 1])
                    ->then(1)
                    ->else($query->identifier('id'));
            })
            ->orderByAsc('id');
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
     * Test orderByDesc() and its input types.
     */
    public function testSelectOrderByDesc(): void
    {
        $query = new SelectQuery($this->connection);
        $query->select(['id'])
            ->from('articles')
            ->orderByDesc('id');
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

        $query = new SelectQuery($this->connection);
        $query->select(['id'])
            ->from('articles')
            ->orderByDesc($query->func()->concat(['id' => 'identifier', '3']));

        $result = $query->execute()->fetchAll('assoc');
        $expected = [
            ['id' => 3],
            ['id' => 2],
            ['id' => 1],
        ];
        $this->assertEquals($expected, $result);

        $query = new SelectQuery($this->connection);
        $query->select(['id'])
            ->from('articles')
            ->orderByDesc(function (QueryExpression $exp, Query $query) {
                return $exp
                    ->case()
                    ->when(['author_id' => 1])
                    ->then(1)
                    ->else($query->identifier('id'));
            })
            ->orderByDesc('id');
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
    public function testSelectGroupBy(): void
    {
        $query = new SelectQuery($this->connection);
        $result = $query
            ->select(['total' => 'count(author_id)', 'author_id'])
            ->from('articles')
            ->join(['table' => 'authors', 'alias' => 'a', 'conditions' => 'author_id = a.id'])
            ->groupBy('author_id')
            ->orderBy(['total' => 'desc'])
            ->execute();
        $expected = [['total' => 2, 'author_id' => 1], ['total' => '1', 'author_id' => 3]];
        $this->assertEquals($expected, $result->fetchAll('assoc'));

        $result = $query->select(['total' => 'count(title)', 'name'], true)
            ->groupBy(['name'], true)
            ->orderBy(['total' => 'asc'])
            ->execute();
        $expected = [['total' => 1, 'name' => 'larry'], ['total' => 2, 'name' => 'mariano']];
        $this->assertEquals($expected, $result->fetchAll('assoc'));

        $result = $query->select(['articles.id'])
            ->groupBy(['articles.id'])
            ->execute();
        $this->assertCount(3, $result->fetchAll());
    }

    public function testSelectGroupDeprecated(): void
    {
        $query = new SelectQuery($this->connection);
        $result = $query
            ->select(['total' => 'count(author_id)', 'author_id'])
            ->from('articles')
            ->join(['table' => 'authors', 'alias' => 'a', 'conditions' => 'author_id = a.id'])
            ->group('author_id')
            ->orderBy(['total' => 'desc'])
            ->execute();
        $expected = [['total' => 2, 'author_id' => 1], ['total' => '1', 'author_id' => 3]];
        $this->assertEquals($expected, $result->fetchAll('assoc'));
    }

    /**
     * Tests that it is possible to select distinct rows
     */
    public function testSelectDistinct(): void
    {
        $query = new SelectQuery($this->connection);
        $result = $query
            ->select(['author_id'])
            ->from(['a' => 'articles'])
            ->execute();
        $this->assertCount(3, $result->fetchAll());

        $result = $query->distinct()->execute();
        $this->assertCount(2, $result->fetchAll());

        $result = $query->select(['id'])->distinct(false)->execute();
        $this->assertCount(3, $result->fetchAll());
    }

    /**
     * Tests distinct on a specific column reduces rows based on that column.
     */
    public function testSelectDistinctON(): void
    {
        $query = new SelectQuery($this->connection);
        $result = $query
            ->select(['author_id'])
            ->distinct(['author_id'])
            ->from(['a' => 'articles'])
            ->orderBy(['author_id' => 'ASC'])
            ->execute();
        $results = $result->fetchAll('assoc');
        $this->assertCount(2, $results);
        $this->assertEquals(
            [3, 1],
            collection($results)->sortBy('author_id')->extract('author_id')->toList()
        );

        $query = new SelectQuery($this->connection);
        $result = $query
            ->select(['author_id'])
            ->distinct('author_id')
            ->from(['a' => 'articles'])
            ->orderBy(['author_id' => 'ASC'])
            ->execute();
        $results = $result->fetchAll('assoc');
        $this->assertCount(2, $results);
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
        $query = new SelectQuery($this->connection);
        $result = $query
            ->select(['city', 'state', 'country'])
            ->from(['addresses'])
            ->modifier('DISTINCTROW');
        $this->assertQuotedQuery(
            'SELECT DISTINCTROW <city>, <state>, <country> FROM <addresses>',
            $result->sql(),
            !$this->autoQuote
        );

        $query = new SelectQuery($this->connection);
        $result = $query
            ->select(['city', 'state', 'country'])
            ->from(['addresses'])
            ->modifier(['DISTINCTROW', 'SQL_NO_CACHE']);
        $this->assertQuotedQuery(
            'SELECT DISTINCTROW SQL_NO_CACHE <city>, <state>, <country> FROM <addresses>',
            $result->sql(),
            !$this->autoQuote
        );

        $query = new SelectQuery($this->connection);
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

        $query = new SelectQuery($this->connection);
        $result = $query
            ->select(['city', 'state', 'country'])
            ->from(['addresses'])
            ->modifier(['TOP 10']);
        $this->assertQuotedQuery(
            'SELECT TOP 10 <city>, <state>, <country> FROM <addresses>',
            $result->sql(),
            !$this->autoQuote
        );

        $query = new SelectQuery($this->connection);
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
        $query = new SelectQuery($this->connection);
        $result = $query
            ->select(['total' => 'count(author_id)', 'author_id'])
            ->from('articles')
            ->join(['table' => 'authors', 'alias' => 'a', 'conditions' => $query->newExpr()->equalFields('author_id', 'a.id')])
            ->groupBy('author_id')
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
        $query = new SelectQuery($this->connection);
        $result = $query
            ->select(['total' => 'count(author_id)', 'author_id'])
            ->from('articles')
            ->join(['table' => 'authors', 'alias' => 'a', 'conditions' => $query->newExpr()->equalFields('author_id', 'a.id')])
            ->groupBy('author_id')
            ->having(['count(author_id) >' => 2], ['count(author_id)' => 'integer'])
            ->andHaving(['count(author_id) <' => 2], ['count(author_id)' => 'integer'])
            ->execute();
        $this->assertCount(0, $result->fetchAll());

        $query = new SelectQuery($this->connection);
        $result = $query
            ->select(['total' => 'count(author_id)', 'author_id'])
            ->from('articles')
            ->join(['table' => 'authors', 'alias' => 'a', 'conditions' => $query->newExpr()->equalFields('author_id', 'a.id')])
            ->groupBy('author_id')
            ->having(['count(author_id)' => 2], ['count(author_id)' => 'integer'])
            ->andHaving(['count(author_id) >' => 1], ['count(author_id)' => 'integer'])
            ->execute();
        $expected = [['total' => 2, 'author_id' => 1]];
        $this->assertEquals($expected, $result->fetchAll('assoc'));

        $query = new SelectQuery($this->connection);
        $result = $query
            ->select(['total' => 'count(author_id)', 'author_id'])
            ->from('articles')
            ->join(['table' => 'authors', 'alias' => 'a', 'conditions' => $query->newExpr()->equalFields('author_id', 'a.id')])
            ->groupBy('author_id')
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
        $query = new SelectQuery($this->connection);
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
        $query = new SelectQuery($this->connection);
        $result = $query->select('id')->from('articles')->limit(1)->execute();
        $this->assertCount(1, $result->fetchAll());

        $query = new SelectQuery($this->connection);
        $result = $query->select('id')->from('articles')->limit(2)->execute();
        $this->assertCount(2, $result->fetchAll());
    }

    /**
     * Tests selecting rows combining a limit and offset clause
     */
    public function testSelectOffset(): void
    {
        $query = new SelectQuery($this->connection);
        $result = $query->select('id')->from('comments')
            ->limit(1)
            ->offset(0)
            ->orderBy(['id' => 'ASC'])
            ->execute();
        $rows = $result->fetchAll('assoc');
        $this->assertCount(1, $rows);
        $this->assertEquals(['id' => 1], $rows[0]);

        $query = new SelectQuery($this->connection);
        $result = $query->select('id')->from('comments')
            ->limit(1)
            ->offset(1)
            ->execute();
        $rows = $result->fetchAll('assoc');
        $this->assertCount(1, $rows);
        $this->assertEquals(['id' => 2], $rows[0]);

        $query = new SelectQuery($this->connection);
        $result = $query->select('id')->from('comments')
            ->limit(1)
            ->offset(2)
            ->execute();
        $rows = $result->fetchAll('assoc');
        $this->assertCount(1, $rows);
            $this->assertEquals(['id' => 3], $rows[0]);

        $query = new SelectQuery($this->connection);
        $result = $query->select('id')->from('articles')
            ->orderBy(['id' => 'DESC'])
            ->limit(1)
            ->offset(0)
            ->execute();
        $rows = $result->fetchAll('assoc');
        $this->assertCount(1, $rows);
        $this->assertEquals(['id' => 3], $rows[0]);

        $result = $query->limit(2)->offset(1)->execute();
        $rows = $result->fetchAll('assoc');
        $this->assertCount(2, $rows);
        $this->assertEquals(['id' => 2], $rows[0]);
        $this->assertEquals(['id' => 1], $rows[1]);

        $query = new SelectQuery($this->connection);
        $query->select('id')->from('comments')
            ->limit(1)
            ->offset(1)
            ->execute()
            ->closeCursor();

        $reflect = new ReflectionProperty($query, '_dirty');
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

        $query = new SelectQuery($this->connection);
        $query->from('comments')->page(0);
    }

    /**
     * Test selecting rows using the page() method.
     */
    public function testSelectPage(): void
    {
        $query = new SelectQuery($this->connection);
        $result = $query->select('id')->from('comments')
            ->limit(1)
            ->page(1)
            ->execute();

        $rows = $result->fetchAll('assoc');
        $this->assertCount(1, $rows);
        $this->assertEquals(['id' => 1], $rows[0]);

        $query = new SelectQuery($this->connection);
        $result = $query->select('id')->from('comments')
            ->limit(1)
            ->page(2)
            ->orderBy(['id' => 'asc'])
            ->execute();
        $rows = $result->fetchAll('assoc');
        $this->assertCount(1, $rows);
        $this->assertEquals(['id' => 2], $rows[0]);

        $query = new SelectQuery($this->connection);
        $query->select('id')->from('comments')->page(3, 10);
        $this->assertEquals(10, $query->clause('limit'));
        $this->assertEquals(20, $query->clause('offset'));

        $query = new SelectQuery($this->connection);
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
        $query = new SelectQuery($this->connection);
        $result = $query
            ->select([
                'id',
                'ids_added' => $query->newExpr()->add('(user_id + article_id)'),
            ])
            ->from('comments')
            ->orderBy(['ids_added' => 'asc'])
            ->limit(2)
            ->page(3)
            ->execute();
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
        $query = new SelectQuery($this->connection);
        $subquery = (new SelectQuery($this->connection))
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

        $query = new SelectQuery($this->connection);
        $subquery = (new SelectQuery($this->connection))
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
        $query = new SelectQuery($this->connection);
        $subquery = (new SelectQuery($this->connection))
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
        $query = new SelectQuery($this->connection);
        $subquery = (new SelectQuery($this->connection))
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

        $query = new SelectQuery($this->connection);
        $subquery = (new SelectQuery($this->connection))
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
        $query = new SelectQuery($this->connection);
        $subQuery = (new SelectQuery($this->connection))
            ->select(['id'])
            ->from('articles')
            ->where(function (ExpressionInterface $exp) {
                return $exp->equalFields('authors.id', 'articles.author_id');
            });
        $result = $query
            ->select(['id'])
            ->from('authors')
            ->where(function ($exp) use ($subQuery) {
                return $exp->exists($subQuery);
            })
            ->execute();
        $rows = $result->fetchAll('assoc');
        $this->assertCount(2, $rows);
        $this->assertEquals(['id' => 1], $rows[0]);
        $this->assertEquals(['id' => 3], $rows[1]);

        $query = new SelectQuery($this->connection);
        $subQuery = (new SelectQuery($this->connection))
            ->select(['id'])
            ->from('articles')
            ->where(function (ExpressionInterface $exp) {
                return $exp->equalFields('authors.id', 'articles.author_id');
            });
        $result = $query
            ->select(['id'])
            ->from('authors')
            ->where(function ($exp) use ($subQuery) {
                return $exp->notExists($subQuery);
            })
            ->execute();
        $rows = $result->fetchAll('assoc');
        $this->assertCount(2, $rows);
        $this->assertEquals(['id' => 2], $rows[0]);
        $this->assertEquals(['id' => 4], $rows[1]);
    }

    /**
     * Tests that it is possible to use a subquery in a join clause
     */
    public function testSubqueryInJoin(): void
    {
        $subquery = (new SelectQuery($this->connection))->select('*')->from('authors');

        $query = new SelectQuery($this->connection);
        $result = $query
            ->select(['title', 'name'])
            ->from('articles')
            ->join(['b' => $subquery])
            ->execute();
        $this->assertCount(self::ARTICLE_COUNT * self::AUTHOR_COUNT, $result->fetchAll(), 'Cross join causes multiplication');
        $result->closeCursor();

        $subquery->where(['id' => 1]);
        $result = $query->execute();
        $this->assertCount(3, $result->fetchAll());
        $result->closeCursor();

        $query->join(['b' => ['table' => $subquery, 'conditions' => [$query->newExpr()->equalFields('b.id', 'articles.id')]]], [], true);
        $result = $query->execute();
        $this->assertCount(1, $result->fetchAll());
        $result->closeCursor();
    }

    /**
     * Tests that it is possible to one or multiple UNION statements in a query
     */
    public function testUnion(): void
    {
        $union = (new SelectQuery($this->connection))->select(['id', 'title'])->from(['a' => 'articles']);
        $query = new SelectQuery($this->connection);
        $result = $query->select(['id', 'comment'])
            ->from(['c' => 'comments'])
            ->union($union)
            ->execute();
        $rows = $result->fetchAll();
        $this->assertCount(self::COMMENT_COUNT + self::ARTICLE_COUNT, $rows);
        $result->closeCursor();

        $union->select(['foo' => 'id', 'bar' => 'title']);
        $union = (new SelectQuery($this->connection))
            ->select(['id', 'name', 'other' => 'id', 'nameish' => 'name'])
            ->from(['b' => 'authors'])
            ->where(['id ' => 1])
            ->orderBy(['id' => 'desc']);

        $query->select(['foo' => 'id', 'bar' => 'comment'])->union($union);
        $result = $query->execute();
        $rows2 = $result->fetchAll();
        $this->assertCount(self::COMMENT_COUNT + self::AUTHOR_COUNT, $rows2);
        $this->assertNotEquals($rows, $rows2);
        $result->closeCursor();

        $union = (new SelectQuery($this->connection))
            ->select(['id', 'title'])
            ->from(['c' => 'articles']);
        $query->select(['id', 'comment'], true)->union($union, true);
        $result = $query->execute();
        $rows3 = $result->fetchAll();
        $this->assertCount(self::COMMENT_COUNT + self::ARTICLE_COUNT, $rows3);
        $this->assertEquals($rows, $rows3);
        $result->closeCursor();
    }

    /**
     * Tests that it is possible to run unions with order by statements
     */
    public function testUnionOrderBy(): void
    {
        $this->skipIf(
            ($this->connection->getDriver() instanceof Sqlite ||
            $this->connection->getDriver() instanceof Sqlserver),
            'Driver does not support ORDER BY in UNIONed queries.'
        );
        $union = (new SelectQuery($this->connection))
            ->select(['id', 'title'])
            ->from(['a' => 'articles'])
            ->orderBy(['a.id' => 'asc']);

        $query = new SelectQuery($this->connection);
        $result = $query->select(['id', 'comment'])
            ->from(['c' => 'comments'])
            ->orderBy(['c.id' => 'asc'])
            ->union($union)
            ->execute();
        $this->assertCount(self::COMMENT_COUNT + self::ARTICLE_COUNT, $result->fetchAll());
    }

    /**
     * Tests that UNION ALL can be built by setting the second param of union() to true
     */
    public function testUnionAll(): void
    {
        $union = (new SelectQuery($this->connection))->select(['id', 'title'])->from(['a' => 'articles']);
        $query = new SelectQuery($this->connection);
        $result = $query->select(['id', 'comment'])
            ->from(['c' => 'comments'])
            ->union($union)
            ->execute();
        $rows = $result->fetchAll('assoc');
        $this->assertCount(self::ARTICLE_COUNT + self::COMMENT_COUNT, $rows);
        $result->closeCursor();

        $union->select(['foo' => 'id', 'bar' => 'title']);
        $union = (new SelectQuery($this->connection))
            ->select(['id', 'name', 'other' => 'id', 'nameish' => 'name'])
            ->from(['b' => 'authors'])
            ->where(['id ' => 1])
            ->orderBy(['id' => 'desc']);

        $query->select(['foo' => 'id', 'bar' => 'comment'])->unionAll($union);
        $result = $query->execute();
        $rows2 = $result->fetchAll();
        $this->assertCount(1 + self::COMMENT_COUNT + self::ARTICLE_COUNT, $rows2);
        $this->assertNotEquals($rows, $rows2);
        $result->closeCursor();
    }

    /**
     * Tests stacking decorators for results and resetting the list of decorators
     */
    public function testDecorateResults(): void
    {
        $query = new SelectQuery($this->connection);
        $result = $query
            ->select(['id', 'title'])
            ->from('articles')
            ->orderBy(['id' => 'ASC'])
            ->decorateResults(function ($row) {
                $row['modified_id'] = $row['id'] + 1;

                return $row;
            })
            ->all();

        foreach ($result as $row) {
            $this->assertEquals($row['id'] + 1, $row['modified_id']);
        }

        $result = $query
            ->decorateResults(function ($row) {
                $row['modified_id']--;

                return $row;
            })
            ->all();

        foreach ($result as $row) {
            $this->assertEquals($row['id'], $row['modified_id']);
        }

        $result = $query
            ->decorateResults(function ($row) {
                $row['foo'] = 'bar';

                return $row;
            }, true)
            ->all();

        foreach ($result as $row) {
            $this->assertSame('bar', $row['foo']);
            $this->assertArrayNotHasKey('modified_id', $row);
        }

        $result = $query->decorateResults(null, true)->all();
        foreach ($result as $row) {
            $this->assertArrayNotHasKey('foo', $row);
            $this->assertArrayNotHasKey('modified_id', $row);
        }
    }

    public function testAll(): void
    {
        $query = new SelectQuery($this->connection);

        $query
            ->select(['id', 'title'])
            ->from('articles')
            ->decorateResults(function ($row) {
                $row['generated'] = 'test';

                return $row;
            })
            ->all();

        $count = 0;
        foreach ($query->all() as $row) {
            ++$count;
            $this->assertArrayHasKey('generated', $row);
            $this->assertSame('test', $row['generated']);
        }
        $this->assertSame(3, $count);

        $this->connection->execute('DELETE FROM articles WHERE author_id = 3')->closeCursor();

        // Verify results are cached when query not marked dirty
        $count = 0;
        foreach ($query->all() as $row) {
            ++$count;
        }
        $this->assertSame(3, $count);

        // Mark query as dirty
        $query->select(['id'], true);

        // Verify query is run again
        $count = 0;
        // phpcs:ignore SlevomatCodingStandard.Variables.UnusedVariable.UnusedVariable
        foreach ($query->all() as $row) {
            ++$count;
        }
        $this->assertSame(2, $count);
    }

    public function testGetIterator(): void
    {
        $query = new SelectQuery($this->connection);

        $query
            ->select(['id', 'title'])
            ->from('articles')
            ->decorateResults(function ($row) {
                $row['generated'] = 'test';

                return $row;
            });

        $count = 0;
        foreach ($query as $row) {
            ++$count;
            $this->assertArrayHasKey('generated', $row);
            $this->assertSame('test', $row['generated']);
        }
        $this->assertSame(3, $count);

        $this->connection->execute('DELETE FROM articles WHERE author_id = 3')->closeCursor();

        // Mark query as dirty
        $query->select(['id'], true);

        // Verify all() is called again
        $count = 0;
        foreach ($query as $row) {
            ++$count;
            $this->assertArrayHasKey('generated', $row);
            $this->assertSame('test', $row['generated']);
        }
        $this->assertSame(2, $count);
    }

    /**
     * Tests that functions are correctly transformed and their parameters are bound
     *
     * @group FunctionExpression
     */
    public function testSQLFunctions(): void
    {
        $query = new SelectQuery($this->connection);
        $result = $query->select(
            function ($q) {
                return ['total' => $q->func()->count('*')];
            }
        )
            ->from('comments')
            ->execute();
        $expected = [['total' => 6]];
        $this->assertEquals($expected, $result->fetchAll('assoc'));

        $query = new SelectQuery($this->connection);
        $result = $query->select([
                'c' => $query->func()->concat(['comment' => 'literal', ' is appended']),
            ])
            ->from('comments')
            ->orderBy(['c' => 'ASC'])
            ->limit(1)
            ->execute();
        $expected = [
            ['c' => 'First Comment for First Article is appended'],
        ];
        $this->assertEquals($expected, $result->fetchAll('assoc'));

        $query = new SelectQuery($this->connection);
        $result = $query
            ->select(['d' => $query->func()->dateDiff(['2012-01-05', '2012-01-02'])])
            ->execute()
            ->fetchAll('assoc');
        $this->assertEquals(3, abs((int)$result[0]['d']));

        $query = new SelectQuery($this->connection);
        $result = $query
            ->select(['d' => $query->func()->now('date')])
            ->execute();

        $result = $result->fetchAll('assoc');
        $this->assertEquals([['d' => date('Y-m-d')]], $result);

        $query = new SelectQuery($this->connection);
        $result = $query
            ->select(['d' => $query->func()->now('time')])
            ->execute();

        $this->assertWithinRange(
            date('U'),
            (new DateTime($result->fetchAll('assoc')[0]['d']))->format('U'),
            10
        );

        $query = new SelectQuery($this->connection);
        $result = $query
            ->select(['d' => $query->func()->now()])
            ->execute();
        $this->assertWithinRange(
            date('U'),
            (new DateTime($result->fetchAll('assoc')[0]['d']))->format('U'),
            10
        );

        $query = new SelectQuery($this->connection);
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

        $driver = $this->connection->getDriver();
        if ($driver instanceof Sqlite) {
            $expected = [
                'd' => '18',
                'm' => '03',
                'y' => '2007',
                'de' => '18',
                'me' => '03',
                'ye' => '2007',
            ] + $expected;
        } elseif ($driver instanceof Postgres || $driver instanceof Sqlserver) {
            $expected = array_map(function ($value) {
                return (string)$value;
            }, $expected);
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

        $query = (new SelectQuery($this->connection))
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

        $result = $query->all()[0];

        $bindings = array_values($query->getValueBinder()->bindings());
        $this->assertCount(2, $bindings);
        $this->assertSame(1, $bindings[0]['value']);
        $this->assertSame('integer', $bindings[0]['type']);
        $this->assertSame(1, $bindings[1]['value']);
        $this->assertSame('integer', $bindings[1]['type']);

        $this->assertSame(1, $result['id']);
        $this->assertSame(1, $result['user_id']);
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

        $query = (new SelectQuery($this->connection))
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

        $result = $query->all()[0];

        $bindings = array_values($query->getValueBinder()->bindings());
        $this->assertCount(2, $bindings);
        $this->assertSame(1, $bindings[0]['value']);
        $this->assertNull($bindings[0]['type']);
        $this->assertSame(1, $bindings[1]['value']);
        $this->assertNull($bindings[1]['type']);

        $this->assertSame(1, $result['id']);
        $this->assertSame(1, $result['user_id']);
    }

    /**
     * Tests that default types are passed to functions accepting a $types param
     */
    public function testDefaultTypes(): void
    {
        $query = new SelectQuery($this->connection);
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
        $this->assertCount(6, $results->fetchAll(), 'All 6 rows should match.');
    }

    /**
     * Tests parameter binding
     */
    public function testBind(): void
    {
        $query = new SelectQuery($this->connection);
        $results = $query->select(['id', 'comment'])
            ->from('comments')
            ->where(['created BETWEEN :foo AND :bar'])
            ->bind(':foo', new DateTime('2007-03-18 10:50:00'), 'datetime')
            ->bind(':bar', new DateTime('2007-03-18 10:52:00'), 'datetime')
            ->execute();
        $expected = [['id' => '4', 'comment' => 'Fourth Comment for First Article']];
        $this->assertEquals($expected, $results->fetchAll('assoc'));

        $query = new SelectQuery($this->connection);
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
        $query = new SelectQuery($this->connection);
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
     * Tests automatic identifier quoting in the select clause
     */
    public function testQuotingSelectFieldsAndAlias(): void
    {
        $this->connection->getDriver()->enableAutoQuoting(true);
        $query = new SelectQuery($this->connection);
        $sql = $query->select(['something'])->sql();
        $this->assertQuotedQuery('SELECT <something>$', $sql);

        $query = new SelectQuery($this->connection);
        $sql = $query->select(['foo' => 'something'])->sql();
        $this->assertQuotedQuery('SELECT <something> AS <foo>$', $sql);

        $query = new SelectQuery($this->connection);
        $sql = $query->select(['foo' => 1])->sql();
        $this->assertQuotedQuery('SELECT 1 AS <foo>$', $sql);

        $query = new SelectQuery($this->connection);
        $sql = $query->select(['foo' => '1 + 1'])->sql();
        $this->assertQuotedQuery('SELECT <1 \+ 1> AS <foo>$', $sql);

        $query = new SelectQuery($this->connection);
        $sql = $query->select(['foo' => $query->newExpr('1 + 1')])->sql();
        $this->assertQuotedQuery('SELECT \(1 \+ 1\) AS <foo>$', $sql);

        $query = new SelectQuery($this->connection);
        $sql = $query->select(['foo' => new IdentifierExpression('bar')])->sql();
        $this->assertQuotedQuery('<bar>', $sql);
    }

    /**
     * Tests automatic identifier quoting in the from clause
     */
    public function testQuotingFromAndAlias(): void
    {
        $this->connection->getDriver()->enableAutoQuoting(true);
        $query = new SelectQuery($this->connection);
        $sql = $query->select('*')->from(['something'])->sql();
        $this->assertQuotedQuery('FROM <something>', $sql);

        $query = new SelectQuery($this->connection);
        $sql = $query->select('*')->from(['foo' => 'something'])->sql();
        $this->assertQuotedQuery('FROM <something> <foo>$', $sql);

        $query = new SelectQuery($this->connection);
        $sql = $query->select('*')->from(['foo' => $query->newExpr('bar')])->sql();
        $this->assertQuotedQuery('FROM \(bar\) <foo>$', $sql);
    }

    /**
     * Tests automatic identifier quoting for DISTINCT ON
     */
    public function testQuotingDistinctOn(): void
    {
        $this->connection->getDriver()->enableAutoQuoting(true);
        $query = new SelectQuery($this->connection);
        $sql = $query->select('*')->distinct(['something'])->sql();
        $this->assertQuotedQuery('<something>', $sql);
    }

    /**
     * Tests automatic identifier quoting in the join clause
     */
    public function testQuotingJoinsAndAlias(): void
    {
        $this->connection->getDriver()->enableAutoQuoting(true);
        $query = new SelectQuery($this->connection);
        $sql = $query->select('*')->join(['something'])->sql();
        $this->assertQuotedQuery('JOIN <something>', $sql);

        $query = new SelectQuery($this->connection);
        $sql = $query->select('*')->join(['foo' => 'something'])->sql();
        $this->assertQuotedQuery('JOIN <something> <foo>', $sql);

        $query = new SelectQuery($this->connection);
        $sql = $query->select('*')->join(['foo' => $query->newExpr('bar')])->sql();
        $this->assertQuotedQuery('JOIN \(bar\) <foo>', $sql);

        $query = new SelectQuery($this->connection);
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
        $query = new SelectQuery($this->connection);
        $sql = $query->select('*')->groupBy(['something'])->sql();
        $this->assertQuotedQuery('GROUP BY <something>', $sql);

        $query = new SelectQuery($this->connection);
        $sql = $query->select('*')->groupBy([$query->newExpr('bar')])->sql();
        $this->assertQuotedQuery('GROUP BY \(bar\)', $sql);

        $query = new SelectQuery($this->connection);
        $sql = $query->select('*')->groupBy([new IdentifierExpression('bar')])->sql();
        $this->assertQuotedQuery('GROUP BY \(<bar>\)', $sql);
    }

    /**
     * Tests automatic identifier quoting strings inside conditions expressions
     */
    public function testQuotingExpressions(): void
    {
        $this->connection->getDriver()->enableAutoQuoting(true);
        $query = new SelectQuery($this->connection);
        $sql = $query->select('*')
            ->where(['something' => 'value'])
            ->sql();
        $this->assertQuotedQuery('WHERE <something> = :c0', $sql);

        $query = new SelectQuery($this->connection);
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
     * Tests converting a query to a string
     */
    public function testToString(): void
    {
        $query = new SelectQuery($this->connection);
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
        $query = (new SelectQuery($this->connection))->select('*')
            ->from('articles')
            ->setDefaultTypes(['id' => 'integer'])
            ->where(['id' => '1']);

        $expected = [
            '(help)' => 'This is a Query object, to get the results execute or iterate it.',
            'sql' => $query->sql(),
            'params' => [
                ':c0' => ['value' => '1', 'type' => 'integer', 'placeholder' => 'c0'],
            ],
            'role' => Connection::ROLE_WRITE,
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
            'role' => Connection::ROLE_WRITE,
            'defaultTypes' => ['id' => 'integer'],
            'decorators' => 0,
            'executed' => true,
        ];
        $result = $query->__debugInfo();
        $this->assertEquals($expected, $result);
    }

    /**
     * Tests that it is possible to pass ExpressionInterface to isNull and isNotNull
     */
    public function testIsNullWithExpressions(): void
    {
        $query = new SelectQuery($this->connection);
        $subquery = (new SelectQuery($this->connection))
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

        $result = (new SelectQuery($this->connection))
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
        $query = new SelectQuery($this->connection);
        $query->select('*')->from('things')->where(function (ExpressionInterface $exp) {
            return $exp->isNull('field');
        });
        $this->assertQuotedQuery('WHERE \(<field>\) IS NULL', $query->sql());

        $query = new SelectQuery($this->connection);
        $query->select('*')->from('things')->where(function (ExpressionInterface $exp) {
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
        $sql = (new SelectQuery($this->connection))
            ->select(['name'])
            ->from(['authors'])
            ->where(['name IS' => null])
            ->sql();
        $this->assertQuotedQuery('WHERE \(<name>\) IS NULL', $sql, !$this->autoQuote);

        $result = (new SelectQuery($this->connection))
            ->select(['name'])
            ->from(['authors'])
            ->where(['name IS' => 'larry'])
            ->execute();
        $rows = $result->fetchAll('assoc');
        $this->assertCount(1, $rows);
        $this->assertEquals(['name' => 'larry'], $rows[0]);
    }

    /**
     * Tests that using the wrong NULL operator will throw meaningful exception instead of
     * cloaking as always-empty result set.
     */
    public function testIsNullInvalid(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Expression `name` is missing operator (IS, IS NOT) with `null` value.');

        (new SelectQuery($this->connection))
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

        (new SelectQuery($this->connection))
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
        $sql = (new SelectQuery($this->connection))
            ->select(['name'])
            ->from(['authors'])
            ->where(['name IS NOT' => null])
            ->sql();
        $this->assertQuotedQuery('WHERE \(<name>\) IS NOT NULL', $sql, !$this->autoQuote);

        $results = (new SelectQuery($this->connection))
            ->select(['name'])
            ->from(['authors'])
            ->where(['name IS NOT' => 'larry'])
            ->execute()
            ->fetchAll('assoc');

        $this->assertCount(3, $results);
        $this->assertNotEquals(['name' => 'larry'], $results[0]);
    }

    public function testCloneWithExpression(): void
    {
        $query = new SelectQuery($this->connection);
        $query
            ->with(
                new CommonTableExpression(
                    'cte',
                    new SelectQuery($this->connection)
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
        $query = new SelectQuery($this->connection);
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
        $query = new SelectQuery($this->connection);
        $query->distinct($query->newExpr('distinct'));

        $clause = $query->clause('distinct');
        $clauseClone = (clone $query)->clause('distinct');

        $this->assertInstanceOf(ExpressionInterface::class, $clause);

        $this->assertEquals($clause, $clauseClone);
        $this->assertNotSame($clause, $clauseClone);
    }

    public function testCloneModifierExpression(): void
    {
        $query = new SelectQuery($this->connection);
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
        $query = new SelectQuery($this->connection);
        $query->from(['alias' => new SelectQuery($this->connection)]);

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
        $query = new SelectQuery($this->connection);
        $query
            ->innerJoin(
                ['alias_inner' => new SelectQuery($this->connection)],
                ['alias_inner.fk = parent.pk']
            )
            ->leftJoin(
                ['alias_left' => new SelectQuery($this->connection)],
                ['alias_left.fk = parent.pk']
            )
            ->rightJoin(
                ['alias_right' => new SelectQuery($this->connection)],
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
        $query = new SelectQuery($this->connection);
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
        $query = new SelectQuery($this->connection);
        $query->groupBy($query->newExpr('group'));

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
        $query = new SelectQuery($this->connection);
        $query->having($query->newExpr('having'));

        $clause = $query->clause('having');
        $clauseClone = (clone $query)->clause('having');

        $this->assertInstanceOf(ExpressionInterface::class, $clause);

        $this->assertEquals($clause, $clauseClone);
        $this->assertNotSame($clause, $clauseClone);
    }

    public function testCloneWindowExpression(): void
    {
        $query = new SelectQuery($this->connection);
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
        $query = new SelectQuery($this->connection);
        $query
            ->orderBy($query->newExpr('order'))
            ->orderByAsc($query->newExpr('order_asc'))
            ->orderByDesc($query->newExpr('order_desc'));

        $clause = $query->clause('order');
        $clauseClone = (clone $query)->clause('order');

        $this->assertInstanceOf(ExpressionInterface::class, $clause);

        $this->assertEquals($clause, $clauseClone);
        $this->assertNotSame($clause, $clauseClone);
    }

    public function testCloneLimitExpression(): void
    {
        $query = new SelectQuery($this->connection);
        $query->limit($query->newExpr('1'));

        $clause = $query->clause('limit');
        $clauseClone = (clone $query)->clause('limit');

        $this->assertInstanceOf(ExpressionInterface::class, $clause);

        $this->assertEquals($clause, $clauseClone);
        $this->assertNotSame($clause, $clauseClone);
    }

    public function testCloneOffsetExpression(): void
    {
        $query = new SelectQuery($this->connection);
        $query->offset($query->newExpr('1'));

        $clause = $query->clause('offset');
        $clauseClone = (clone $query)->clause('offset');

        $this->assertInstanceOf(ExpressionInterface::class, $clause);

        $this->assertEquals($clause, $clauseClone);
        $this->assertNotSame($clause, $clauseClone);
    }

    public function testCloneUnionExpression(): void
    {
        $query = new SelectQuery($this->connection);
        $query->where(['id' => 1]);

        $query2 = new SelectQuery($this->connection);
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
        $query = new SelectQuery($this->connection);
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
        $query = new SelectQuery($this->connection);
        $query->select(['id', 'title' => $query->func()->concat(['title' => 'literal', 'test'])])
            ->from('articles')
            ->where(['Articles.id' => 1])
            ->offset(10)
            ->limit(1)
            ->orderBy(['Articles.id' => 'DESC']);
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

        $query->orderBy(['Articles.title' => 'ASC']);
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
        $query = new SelectQuery($this->connection);
        $typeMap = $query->getSelectTypeMap();
        $this->assertInstanceOf(TypeMap::class, $typeMap);
        $another = clone $typeMap;
        $query->setSelectTypeMap($another);
        $this->assertSame($another, $query->getSelectTypeMap());

        $query->setSelectTypeMap(['myid' => 'integer']);
        $this->assertSame('integer', $query->getSelectTypeMap()->type('myid'));
    }

    /**
     * Tests the automatic type conversion for the fields in the result
     */
    public function testSelectTypeConversion(): void
    {
        TypeFactory::set('custom_datetime', new BarType('custom_datetime'));

        $query = new SelectQuery($this->connection);
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
        $this->assertInstanceOf(DateTime::class, $result[0]['the_date']);
        $this->assertInstanceOf(DateTime::class, $result[0]['updated']);
    }

    /**
     * Test removeJoin().
     */
    public function testRemoveJoin(): void
    {
        $query = new SelectQuery($this->connection);
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
        $query = new SelectQuery($this->connection);
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
     * Test automatic results casting
     */
    public function testCastResults(): void
    {
        $query = new SelectQuery($this->connection);
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
        $query = new SelectQuery($this->connection);
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
        $query = new SelectQuery($this->connection);

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
        $query = new SelectQuery($this->connection);
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
     * Test that calling fetchAssoc return an associated array.
     *
     * @throws \Exception
     */
    public function testFetchAssoc(): void
    {
        $query = new SelectQuery($this->connection);
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
        $query = new SelectQuery($this->connection);

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
        $query = new SelectQuery($this->connection);
        $stmt = $query->select([
                'id',
                'user_id',
                'is_active',
            ])
            ->from('profiles')
            ->limit(1)
            ->execute();
        $results = $stmt->fetch('obj');
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
        $query = new SelectQuery($this->connection);
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
        $query = new SelectQuery($this->connection);
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

        $query = new SelectQuery($connection);

        $stmt = $connection->update('articles', ['published' => 'N'], ['id' => 3]);
        $stmt->closeCursor();

        $subquery = new SelectQuery($connection);
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
            ->orderByDesc($subquery)
            ->orderByAsc('id')
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

        $query = new SelectQuery($connection);

        $stmt = $connection->update('articles', ['published' => 'N'], ['id' => 3]);
        $stmt->closeCursor();

        $subqueryA = new SelectQuery($connection);
        $subqueryA
            ->select('count(*)')
            ->from(['a' => 'articles'])
            ->where([
                'a.id = articles.id',
                'a.published' => 'Y',
            ]);

        $subqueryB = new SelectQuery($connection);
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
            ->orderByDesc($subqueryB)
            ->orderByAsc('id')
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

        $query = new SelectQuery($this->connection);
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

        $query = (new SelectQuery($this->connection))
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
        $query = (new SelectQuery($this->connection))
            ->select('id')
            ->from('articles')
            ->where(['id in' => [1, 2, 3]]);

        $this->assertQuotedQuery(
            'SELECT <id> FROM <articles> WHERE <id> IN \(:c0,:c1,:c2\)',
            $query->sql(),
            !$this->autoQuote
        );

        $query = (new SelectQuery($this->connection))
            ->select('id')
            ->from('articles')
            ->where(['id IN' => [1, 2, 3]]);

        $this->assertQuotedQuery(
            'SELECT <id> FROM <articles> WHERE <id> IN \(:c0,:c1,:c2\)',
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

        $query = (new SelectQuery($this->connection))
            ->select('id')
            ->from('profiles')
            ->where(['CONCAT(first_name, " ", last_name) in' => ['foo bar', 'baz 42']]);

        $this->assertSame(
            'SELECT id FROM profiles WHERE CONCAT\(first_name, " ", last_name\) in \(:c0,:c1\)',
            $query->sql()
        );

        $query = (new SelectQuery($this->connection))
            ->select('id')
            ->from('profiles')
            ->where(['CONCAT(first_name, " ", last_name) IN' => ['foo bar', 'baz 42']]);

        $this->assertSame(
            'SELECT id FROM profiles WHERE CONCAT\(first_name, " ", last_name\) in \(:c0,:c1\)',
            $query->sql()
        );

        $query = (new SelectQuery($this->connection))
            ->select('id')
            ->from('profiles')
            ->where(['CONCAT(first_name, " ", last_name) not in' => ['foo bar', 'baz 42']]);

        $this->assertSame(
            'SELECT id FROM profiles WHERE CONCAT\(first_name, " ", last_name\) not in \(:c0,:c1\)',
            $query->sql()
        );

        $query = (new SelectQuery($this->connection))
            ->select('id')
            ->from('profiles')
            ->where(['CONCAT(first_name, " ", last_name) NOT IN' => ['foo bar', 'baz 42']]);

        $this->assertSame(
            'SELECT id FROM profiles WHERE CONCAT\(first_name, " ", last_name\) not in \(:c0,:c1\)',
            $query->sql()
        );
    }
}
