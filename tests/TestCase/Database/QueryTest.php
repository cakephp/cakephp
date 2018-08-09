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
namespace Cake\Test\TestCase\Database;

use Cake\Core\Configure;
use Cake\Database\Expression\IdentifierExpression;
use Cake\Database\Query;
use Cake\Database\TypeMap;
use Cake\Datasource\ConnectionManager;
use Cake\TestSuite\TestCase;

/**
 * Tests Query class
 */
class QueryTest extends TestCase
{

    public $fixtures = ['core.articles', 'core.authors', 'core.comments'];

    public $autoFixtures = false;

    const ARTICLE_COUNT = 3;
    const AUTHOR_COUNT = 4;
    const COMMENT_COUNT = 6;

    public function setUp()
    {
        parent::setUp();
        $this->connection = ConnectionManager::get('test');
        $this->autoQuote = $this->connection->driver()->autoQuoting();
    }

    public function tearDown()
    {
        parent::tearDown();
        $this->connection->driver()->autoQuoting($this->autoQuote);
        unset($this->connection);
    }

    /**
     * Queries need a default type to prevent fatal errors
     * when an uninitialized query has its sql() method called.
     *
     * @return void
     */
    public function testDefaultType()
    {
        $query = new Query($this->connection);
        $this->assertEquals('', $query->sql());
        $this->assertEquals('select', $query->type());
    }

    /**
     * Tests that it is possible to obtain expression results from a query
     *
     * @return void
     */
    public function testSelectFieldsOnly()
    {
        $this->connection->driver()->autoQuoting(false);
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
     *
     * @return void
     */
    public function testSelectClosure()
    {
        $this->connection->driver()->autoQuoting(false);
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
     *
     * @return void
     */
    public function testSelectFieldsFromTable()
    {
        $this->loadFixtures('Authors', 'Articles');
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
        $this->assertEquals(['nate'], $result->fetch());
        $this->assertEquals(['mariano'], $result->fetch());
        $this->assertCount(4, $result);
        $result->closeCursor();
    }

    /**
     * Tests it is possible to select aliased fields
     *
     * @return void
     */
    public function testSelectAliasedFieldsFromTable()
    {
        $this->loadFixtures('Comments');
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
     *
     * @return void
     */
    public function testSelectAliasedTables()
    {
        $this->loadFixtures('Authors', 'Articles');
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
     *
     * @return void
     */
    public function testSelectWithJoins()
    {
        $this->loadFixtures('Authors', 'Articles');
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
            ['table' => 'authors', 'type' => 'INNER', 'conditions' => $query->newExpr()->equalFields('author_id', 'authors.id')]
        ], [], true)->execute();
        $this->assertCount(3, $result);
        $this->assertEquals(['title' => 'First Article', 'name' => 'mariano'], $result->fetch('assoc'));
        $this->assertEquals(['title' => 'Second Article', 'name' => 'larry'], $result->fetch('assoc'));
        $result->closeCursor();
    }

    /**
     * Tests it is possible to add joins to a select query using array or expression as conditions
     *
     * @return void
     */
    public function testSelectWithJoinsConditions()
    {
        $this->loadFixtures('Authors', 'Articles', 'Comments');
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
        $time = new \DateTime('2007-03-18 10:50:00');
        $types = ['created' => 'datetime'];
        $result = $query
            ->select(['title', 'comment' => 'c.comment'])
            ->from('articles')
            ->join(['table' => 'comments', 'alias' => 'c', 'conditions' => ['created <=' => $time]], $types)
            ->execute();
        $this->assertEquals(['title' => 'First Article', 'comment' => 'First Comment for First Article'], $result->fetch('assoc'));
        $result->closeCursor();
    }

    /**
     * Tests that joins can be aliased using array keys
     *
     * @return void
     */
    public function testSelectAliasedJoins()
    {
        $this->loadFixtures('Authors', 'Articles', 'Comments');
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
        $time = new \DateTime('2007-03-18 10:45:23');
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
     *
     * @return void
     */
    public function testSelectLeftJoin()
    {
        $this->loadFixtures('Articles', 'Comments');
        $query = new Query($this->connection);
        $time = new \DateTime('2007-03-18 10:45:23');
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
            ->execute();
        $this->assertEquals(
            ['title' => 'First Article', 'name' => 'Second Comment for First Article'],
            $result->fetch('assoc')
        );
        $result->closeCursor();
    }

    /**
     * Tests the innerJoin method
     *
     * @return void
     */
    public function testSelectInnerJoin()
    {
        $this->loadFixtures('Articles', 'Comments');
        $query = new Query($this->connection);
        $time = new \DateTime('2007-03-18 10:45:23');
        $types = ['created' => 'datetime'];
        $result = $query
            ->select(['title', 'name' => 'c.comment'])
            ->from('articles')
            ->innerJoin(['c' => 'comments'], ['created <' => $time], $types)
            ->execute();
        $this->assertCount(0, $result->fetchAll());
        $result->closeCursor();
    }

    /**
     * Tests the rightJoin method
     *
     * @return void
     */
    public function testSelectRightJoin()
    {
        $this->loadFixtures('Articles', 'Comments');
        $this->skipIf(
            $this->connection->driver() instanceof \Cake\Database\Driver\Sqlite,
            'SQLite does not support RIGHT joins'
        );
        $query = new Query($this->connection);
        $time = new \DateTime('2007-03-18 10:45:23');
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
     *
     * @return void
     */
    public function testSelectJoinWithCallback()
    {
        $this->loadFixtures('Articles', 'Comments');
        $query = new Query($this->connection);
        $types = ['created' => 'datetime'];
        $result = $query
            ->select(['title', 'name' => 'c.comment'])
            ->from('articles')
            ->innerJoin(['c' => 'comments'], function ($exp, $q) use ($query, $types) {
                $this->assertSame($q, $query);
                $exp->add(['created <' => new \DateTime('2007-03-18 10:45:23')], $types);

                return $exp;
            })
            ->execute();
        $this->assertCount(0, $result->fetchAll());
        $result->closeCursor();
    }

    /**
     * Tests that it is possible to pass a callable as conditions for a join
     *
     * @return void
     */
    public function testSelectJoinWithCallback2()
    {
        $this->loadFixtures('Authors', 'Comments');
        $query = new Query($this->connection);
        $types = ['created' => 'datetime'];
        $result = $query
            ->select(['name', 'commentary' => 'comments.comment'])
            ->from('authors')
            ->innerJoin('comments', function ($exp, $q) use ($query, $types) {
                $this->assertSame($q, $query);
                $exp->add(['created >' => new \DateTime('2007-03-18 10:45:23')], $types);

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
     *
     * @return void
     */
    public function testSelectSimpleWhere()
    {
        $this->loadFixtures('Articles');
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
     *
     * @return void
     */
    public function testSelectWhereOperatorMoreThan()
    {
        $this->loadFixtures('Comments');
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
     *
     * @return void
     */
    public function testSelectWhereOperatorLessThan()
    {
        $this->loadFixtures('Articles');
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
     *
     * @return void
     */
    public function testSelectWhereOperatorLessThanEqual()
    {
        $this->loadFixtures('Articles');
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
     *
     * @return void
     */
    public function testSelectWhereOperatorMoreThanEqual()
    {
        $this->loadFixtures('Articles');
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
     *
     * @return void
     */
    public function testSelectWhereOperatorNotEqual()
    {
        $this->loadFixtures('Articles');
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
     *
     * @return void
     */
    public function testSelectWhereOperatorLike()
    {
        $this->loadFixtures('Articles');
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
     *
     * @return void
     */
    public function testSelectWhereOperatorLikeExpansion()
    {
        $this->loadFixtures('Articles');
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
     *
     * @return void
     */
    public function testSelectWhereOperatorNotLike()
    {
        $this->loadFixtures('Articles');
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
     *
     * @return void
     */
    public function testSelectWhereUnary()
    {
        $this->loadFixtures('Articles');
        $query = new Query($this->connection);
        $result = $query
            ->select(['id'])
            ->from('articles')
            ->where([
                'title is not' => null,
                'user_id is' => null
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
     *
     * @return void
     */
    public function testSelectWhereTypes()
    {
        $this->loadFixtures('Comments');
        $query = new Query($this->connection);
        $result = $query
            ->select(['id'])
            ->from('comments')
            ->where(['created' => new \DateTime('2007-03-18 10:45:23')], ['created' => 'datetime'])
            ->execute();
        $this->assertCount(1, $result);
        $this->assertEquals(['id' => 1], $result->fetch('assoc'));
        $result->closeCursor();

        $query = new Query($this->connection);
        $result = $query
            ->select(['id'])
            ->from('comments')
            ->where(['created >' => new \DateTime('2007-03-18 10:46:00')], ['created' => 'datetime'])
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
                    'created >' => new \DateTime('2007-03-18 10:40:00'),
                    'created <' => new \DateTime('2007-03-18 10:46:00')
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
                    'id' => '3something-crazy',
                    'created <' => new \DateTime('2013-01-01 12:00')
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
                    'id' => '1something-crazy',
                    'created <' => new \DateTime('2013-01-01 12:00')
                ],
                ['created' => 'datetime', 'id' => 'integer']
            )
            ->execute();
        $this->assertCount(1, $result);
        $this->assertEquals(['id' => 1], $result->fetch('assoc'));
        $result->closeCursor();
    }

    /**
     * Tests that passing an array type to any where condition will replace
     * the passed array accordingly as a proper IN condition
     *
     * @return void
     */
    public function testSelectWhereArrayType()
    {
        $this->loadFixtures('Comments');
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
     *
     * @expectedException \Cake\Database\Exception
     * @expectedExceptionMessage Impossible to generate condition with empty list of values for field
     * @return void
     */
    public function testSelectWhereArrayTypeEmpty()
    {
        $this->loadFixtures('Comments');
        $query = new Query($this->connection);
        $result = $query
            ->select(['id'])
            ->from('comments')
            ->where(['id' => []], ['id' => 'integer[]'])
            ->execute();
    }

    /**
     * Tests exception message for impossible condition when using an expression
     * @expectedException \Cake\Database\Exception
     * @expectedExceptionMessage with empty list of values for field (SELECT 1)
     * @return void
     */
    public function testSelectWhereArrayTypeEmptyWithExpression()
    {
        $this->loadFixtures('Comments');
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
     * Tests that Query::orWhere() can be used to concatenate conditions with OR
     *
     * @return void
     */
    public function testSelectOrWhere()
    {
        $this->loadFixtures('Comments');
        $query = new Query($this->connection);
        $result = $query
            ->select(['id'])
            ->from('comments')
            ->where(['created' => new \DateTime('2007-03-18 10:45:23')], ['created' => 'datetime'])
            ->orWhere(['created' => new \DateTime('2007-03-18 10:47:23')], ['created' => 'datetime'])
            ->execute();
        $this->assertCount(2, $result);
        $this->assertEquals(['id' => 1], $result->fetch('assoc'));
        $this->assertEquals(['id' => 2], $result->fetch('assoc'));
        $result->closeCursor();
    }

    /**
     * Tests that Query::andWhere() can be used to concatenate conditions with AND
     *
     * @return void
     */
    public function testSelectAndWhere()
    {
        $this->loadFixtures('Comments');
        $query = new Query($this->connection);
        $result = $query
            ->select(['id'])
            ->from('comments')
            ->where(['created' => new \DateTime('2007-03-18 10:45:23')], ['created' => 'datetime'])
            ->andWhere(['id' => 1])
            ->execute();
        $this->assertCount(1, $result);
        $this->assertEquals(['id' => 1], $result->fetch('assoc'));
        $result->closeCursor();

        $query = new Query($this->connection);
        $result = $query
            ->select(['id'])
            ->from('comments')
            ->where(['created' => new \DateTime('2007-03-18 10:50:55')], ['created' => 'datetime'])
            ->andWhere(['id' => 2])
            ->execute();
        $this->assertCount(0, $result);
        $result->closeCursor();
    }

    /**
     * Tests that combining Query::andWhere() and Query::orWhere() produces
     * correct conditions nesting
     *
     * @return void
     */
    public function testSelectExpressionNesting()
    {
        $this->loadFixtures('Comments');
        $query = new Query($this->connection);
        $result = $query
            ->select(['id'])
            ->from('comments')
            ->where(['created' => new \DateTime('2007-03-18 10:45:23')], ['created' => 'datetime'])
            ->orWhere(['id' => 2])
            ->andWhere(['created >=' => new \DateTime('2007-03-18 10:40:00')], ['created' => 'datetime'])
            ->execute();
        $this->assertCount(2, $result);
        $this->assertEquals(['id' => 1], $result->fetch('assoc'));
        $this->assertEquals(['id' => 2], $result->fetch('assoc'));
        $result->closeCursor();

        $query = new Query($this->connection);
        $result = $query
            ->select(['id'])
            ->from('comments')
            ->where(['created' => new \DateTime('2007-03-18 10:45:23')], ['created' => 'datetime'])
            ->orWhere(['id' => 2])
            ->andWhere(['created >=' => new \DateTime('2007-03-18 10:40:00')], ['created' => 'datetime'])
            ->orWhere(['created' => new \DateTime('2007-03-18 10:49:23')], ['created' => 'datetime'])
            ->execute();
        $this->assertCount(3, $result);
        $this->assertEquals(['id' => 1], $result->fetch('assoc'));
        $this->assertEquals(['id' => 2], $result->fetch('assoc'));
        $this->assertEquals(['id' => 3], $result->fetch('assoc'));
        $result->closeCursor();
    }

    /**
     * Tests that Query::orWhere() can be used without calling where() before
     *
     * @return void
     */
    public function testSelectOrWhereNoPreviousCondition()
    {
        $this->loadFixtures('Comments');
        $query = new Query($this->connection);
        $result = $query
            ->select(['id'])
            ->from('comments')
            ->orWhere(['created' => new \DateTime('2007-03-18 10:45:23')], ['created' => 'datetime'])
            ->orWhere(['created' => new \DateTime('2007-03-18 10:47:23')], ['created' => 'datetime'])
            ->execute();
        $this->assertCount(2, $result);
        $this->assertEquals(['id' => 1], $result->fetch('assoc'));
        $this->assertEquals(['id' => 2], $result->fetch('assoc'));
        $result->closeCursor();
    }

    /**
     * Tests that Query::andWhere() can be used without calling where() before
     *
     * @return void
     */
    public function testSelectAndWhereNoPreviousCondition()
    {
        $this->loadFixtures('Comments');
        $query = new Query($this->connection);
        $result = $query
            ->select(['id'])
            ->from('comments')
            ->andWhere(['created' => new \DateTime('2007-03-18 10:45:23')], ['created' => 'datetime'])
            ->andWhere(['id' => 1])
            ->execute();
        $this->assertCount(1, $result);
        $this->assertEquals(['id' => 1], $result->fetch('assoc'));
        $result->closeCursor();
    }

    /**
     * Tests that it is possible to pass a closure to where() to build a set of
     * conditions and return the expression to be used
     *
     * @return void
     */
    public function testSelectWhereUsingClosure()
    {
        $this->loadFixtures('Comments');
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
                    ->eq('created', new \DateTime('2007-03-18 10:45:23'), 'datetime');
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
                    ->eq('created', new \DateTime('2021-12-30 15:00'), 'datetime');
            })
            ->execute();
        $this->assertCount(0, $result);
        $result->closeCursor();
    }

    /**
     * Tests generating tuples in the values side containing closure expressions
     *
     * @return void
     */
    public function testTupleWithClosureExpression()
    {
        $this->loadFixtures('Comments');
        $query = new Query($this->connection);
        $query->select(['id'])
            ->from('comments')
            ->where([
                'OR' => [
                    'id' => 1,
                    function ($exp) {
                        return $exp->eq('id', 2);
                    }
                ]
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
     *
     * @return void
     */
    public function testSelectAndWhereUsingClosure()
    {
        $this->loadFixtures('Comments');
        $query = new Query($this->connection);
        $result = $query
            ->select(['id'])
            ->from('comments')
            ->where(['id' => '1'])
            ->andWhere(function ($exp) {
                return $exp->eq('created', new \DateTime('2007-03-18 10:45:23'), 'datetime');
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
                return $exp->eq('created', new \DateTime('2022-12-21 12:00'), 'datetime');
            })
            ->execute();
        $this->assertCount(0, $result);
        $result->closeCursor();
    }

    /**
     * Tests that it is possible to pass a closure to orWhere() to build a set of
     * conditions and return the expression to be used
     *
     * @return void
     */
    public function testSelectOrWhereUsingClosure()
    {
        $this->loadFixtures('Comments');
        $query = new Query($this->connection);
        $result = $query
            ->select(['id'])
            ->from('comments')
            ->where(['id' => '1'])
            ->orWhere(function ($exp) {
                return $exp->eq('created', new \DateTime('2007-03-18 10:47:23'), 'datetime');
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
            ->where(['id' => '1'])
            ->orWhere(function ($exp) {
                return $exp
                    ->eq('created', new \DateTime('2012-12-22 12:00'), 'datetime')
                    ->eq('id', 3);
            })
            ->execute();
        $this->assertCount(1, $result);
        $this->assertEquals(['id' => 1], $result->fetch('assoc'));
        $result->closeCursor();
    }

    /**
     * Tests that expression objects can be used as the field in a comparison
     * and the values will be bound correctly to the query
     *
     * @return void
     */
    public function testSelectWhereUsingExpressionInField()
    {
        $this->loadFixtures('Comments');
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
     *
     * @return void
     */
    public function testSelectWhereOperatorMethods()
    {
        $this->loadFixtures('Articles', 'Comments', 'Authors');
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
                    [new \DateTime('2007-03-18 10:45:23'), new \DateTime('2007-03-18 10:47:23')],
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
                    [new \DateTime('2007-03-18 10:45:23'), new \DateTime('2007-03-18 10:47:23')],
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
     *
     * @return void
     */
    public function testInValueCast()
    {
        $this->loadFixtures('Comments');
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
     *
     * @return void
     */
    public function testInValueCast2()
    {
        $this->loadFixtures('Comments');
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
     *
     * @return void
     */
    public function testInClausePlaceholderGeneration()
    {
        $this->loadFixtures('Comments');
        $query = new Query($this->connection);
        $query->select(['id'])
            ->from('comments')
            ->where(['id IN' => [1, 2]])
            ->sql();
        $bindings = $query->valueBinder()->bindings();
        $this->assertArrayHasKey(':c0', $bindings);
        $this->assertEquals('c0', $bindings[':c0']['placeholder']);
        $this->assertArrayHasKey(':c1', $bindings);
        $this->assertEquals('c1', $bindings[':c1']['placeholder']);
    }

    /**
     * Tests where() with callable types.
     *
     * @return void
     */
    public function testWhereCallables()
    {
        $query = new Query($this->connection);
        $query->select(['id'])
            ->from('articles')
            ->where([
                'id' => '\Cake\Error\Debugger::dump',
                'title' => ['\Cake\Error\Debugger', 'dump'],
                'author_id' => function ($exp) {
                    return 1;
                }
            ]);
        $this->assertQuotedQuery(
            'SELECT <id> FROM <articles> WHERE \(<id> = :c0 AND <title> = :c1 AND <author_id> = :c2\)',
            $query->sql(),
            !$this->autoQuote
        );
    }

    /**
     * Tests that empty values don't set where clauses.
     *
     * @return void
     */
    public function testWhereEmptyValues()
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
     *
     * @return void
     */
    public function testWhereWithBetween()
    {
        $this->loadFixtures('Comments');
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
     *
     * @return void
     */
    public function testWhereWithBetweenComplex()
    {
        $this->loadFixtures('Comments');
        $query = new Query($this->connection);
        $result = $query
            ->select(['id'])
            ->from('comments')
            ->where(function ($exp) {
                $from = new \DateTime('2007-03-18 10:51:00');
                $to = new \DateTime('2007-03-18 10:54:00');

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
     *
     * @return void
     */
    public function testWhereWithBetweenWithExpressionField()
    {
        $this->loadFixtures('Comments');
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
     *
     * @return void
     */
    public function testWhereWithBetweenWithExpressionParts()
    {
        $this->loadFixtures('Comments');
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
     *
     * @return void
     */
    public function testSelectExpressionComposition()
    {
        $this->loadFixtures('Comments');
        $query = new Query($this->connection);
        $result = $query
            ->select(['id'])
            ->from('comments')
            ->where(function ($exp) {
                $and = $exp->and_(['id' => 2, 'id >' => 1]);

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
                $and = $exp->and_(['id' => 2, 'id <' => 2]);

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
                $and = $exp->and_(function ($and) {
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
                $or = $exp->or_(['id' => 1]);
                $and = $exp->and_(['id >' => 2, 'id <' => 4]);

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
                $or = $exp->or_(function ($or) {
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
     *
     * @return void
     */
    public function testSelectWhereNot()
    {
        $this->loadFixtures('Articles', 'Comments');
        $query = new Query($this->connection);
        $result = $query
            ->select(['id'])
            ->from('comments')
            ->where(function ($exp) {
                return $exp->not(
                    $exp->and_(['id' => 2, 'created' => new \DateTime('2007-03-18 10:47:23')], ['created' => 'datetime'])
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
                    $exp->and_(['id' => 2, 'created' => new \DateTime('2012-12-21 12:00')], ['created' => 'datetime'])
                );
            })
            ->execute();
        $this->assertCount(6, $result);
        $result->closeCursor();
    }

    /**
     * Tests that conditions can be nested with an unary operator using the array notation
     * and the not() method
     *
     * @return void
     */
    public function testSelectWhereNot2()
    {
        $this->loadFixtures('Articles');
        $query = new Query($this->connection);
        $result = $query
            ->select(['id'])
            ->from('articles')
            ->where([
                'not' => ['or' => ['id' => 1, 'id >' => 2], 'id' => 3]
            ])
            ->execute();
        $this->assertCount(2, $result);
        $this->assertEquals(['id' => 1], $result->fetch('assoc'));
        $this->assertEquals(['id' => 2], $result->fetch('assoc'));
        $result->closeCursor();
    }

    /**
     * Tests order() method both with simple fields and expressions
     *
     * @return void
     */
    public function testSelectOrderBy()
    {
        $this->loadFixtures('Comments');
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
     *
     * @return void
     */
    public function testSelectOrderByString()
    {
        $this->loadFixtures('Articles');
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
     * Test orderAsc() and its input types.
     *
     * @return void
     */
    public function testSelectOrderAsc()
    {
        $this->loadFixtures('Articles');
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
    }

    /**
     * Test orderDesc() and its input types.
     *
     * @return void
     */
    public function testSelectOrderDesc()
    {
        $this->loadFixtures('Articles');
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
    }

    /**
     * Tests that group by fields can be passed similar to select fields
     * and that it sends the correct query to the database
     *
     * @return void
     */
    public function testSelectGroup()
    {
        $this->loadFixtures('Authors', 'Articles');
        $query = new Query($this->connection);
        $result = $query
            ->select(['total' => 'count(author_id)', 'author_id'])
            ->from('articles')
            ->join(['table' => 'authors', 'alias' => 'a', 'conditions' => 'author_id = a.id'])
            ->group('author_id')
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
     *
     * @return void
     */
    public function testSelectDistinct()
    {
        $this->loadFixtures('Authors', 'Articles');
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
     * Tests that it is possible to select distinct rows, even filtering by one column
     * this is testing that there is a specific implementation for DISTINCT ON
     *
     * @return void
     */
    public function testSelectDistinctON()
    {
        $this->loadFixtures('Authors', 'Articles');
        $query = new Query($this->connection);
        $result = $query
            ->select(['id', 'author_id'])
            ->distinct(['author_id'])
            ->from(['a' => 'articles'])
            ->order(['author_id' => 'ASC'])
            ->execute();
        $this->assertCount(2, $result);
        $results = $result->fetchAll('assoc');
        $this->assertEquals(['id', 'author_id'], array_keys($results[0]));
        $this->assertEquals(
            [3, 1],
            collection($results)->sortBy('author_id')->extract('author_id')->toList()
        );

        $query = new Query($this->connection);
        $result = $query
            ->select(['id', 'author_id'])
            ->distinct('author_id')
            ->from(['a' => 'articles'])
            ->order(['author_id' => 'ASC'])
            ->execute();
        $this->assertCount(2, $result);
        $results = $result->fetchAll('assoc');
        $this->assertEquals(['id', 'author_id'], array_keys($results[0]));
        $this->assertEquals(
            [3, 1],
            collection($results)->sortBy('author_id')->extract('author_id')->toList()
        );
    }

    /**
     * Test use of modifiers in the query
     *
     * Testing the generated SQL since the modifiers are usually different per driver
     *
     * @return void
     */
    public function testSelectModifiers()
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
    }

    /**
     * Tests that having() behaves pretty much the same as the where() method
     *
     * @return void
     */
    public function testSelectHaving()
    {
        $this->loadFixtures('Authors', 'Articles');
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
     * Tests that Query::orHaving() can be used to concatenate conditions with OR
     * in the having clause
     *
     * @return void
     */
    public function testSelectOrHaving()
    {
        $this->loadFixtures('Authors', 'Articles');
        $query = new Query($this->connection);
        $result = $query
            ->select(['total' => 'count(author_id)', 'author_id'])
            ->from('articles')
            ->join(['table' => 'authors', 'alias' => 'a', 'conditions' => $query->newExpr()->equalFields('author_id', 'a.id')])
            ->group('author_id')
            ->having(['count(author_id) >' => 2], ['count(author_id)' => 'integer'])
            ->orHaving(['count(author_id) <' => 2], ['count(author_id)' => 'integer'])
            ->execute();
        $expected = [['total' => 1, 'author_id' => 3]];
        $this->assertEquals($expected, $result->fetchAll('assoc'));

        $query = new Query($this->connection);
        $result = $query
            ->select(['total' => 'count(author_id)', 'author_id'])
            ->from('articles')
            ->join(['table' => 'authors', 'alias' => 'a', 'conditions' => $query->newExpr()->equalFields('author_id', 'a.id')])
            ->group('author_id')
            ->having(['count(author_id) >' => 2], ['count(author_id)' => 'integer'])
            ->orHaving(['count(author_id) <=' => 2], ['count(author_id)' => 'integer'])
            ->execute();
        $expected = [['total' => 2, 'author_id' => 1], ['total' => 1, 'author_id' => 3]];
        $this->assertEquals($expected, $result->fetchAll('assoc'));

        $query = new Query($this->connection);
        $result = $query
            ->select(['total' => 'count(author_id)', 'author_id'])
            ->from('articles')
            ->join(['table' => 'authors', 'alias' => 'a', 'conditions' => $query->newExpr()->equalFields('author_id', 'a.id')])
            ->group('author_id')
            ->having(['count(author_id) >' => 2], ['count(author_id)' => 'integer'])
            ->orHaving(function ($e) {
                return $e->add('count(author_id) = 1 + 1');
            })
            ->execute();
        $expected = [['total' => 2, 'author_id' => 1]];
        $this->assertEquals($expected, $result->fetchAll('assoc'));
    }

    /**
     * Tests that Query::andHaving() can be used to concatenate conditions with AND
     * in the having clause
     *
     * @return void
     */
    public function testSelectAndHaving()
    {
        $this->loadFixtures('Authors', 'Articles');
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
     * Tests selecting rows using a limit clause
     *
     * @return void
     */
    public function testSelectLimit()
    {
        $this->loadFixtures('Articles');
        $query = new Query($this->connection);
        $result = $query->select('id')->from('articles')->limit(1)->execute();
        $this->assertCount(1, $result);

        $query = new Query($this->connection);
        $result = $query->select('id')->from('articles')->limit(2)->execute();
        $this->assertCount(2, $result);
    }

    /**
     * Tests selecting rows combining a limit and offset clause
     *
     * @return void
     */
    public function testSelectOffset()
    {
        $this->loadFixtures('Articles', 'Comments');
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
            ->execute();
        $dirty = $this->readAttribute($query, '_dirty');
        $this->assertFalse($dirty);
        $query->offset(2);
        $dirty = $this->readAttribute($query, '_dirty');
        $this->assertTrue($dirty);
    }

    /**
     * Test selecting rows using the page() method.
     *
     * @return void
     */
    public function testSelectPage()
    {
        $this->loadFixtures('Comments');
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
     * Tests that Query objects can be included inside the select clause
     * and be used as a normal field, including binding any passed parameter
     *
     * @return void
     */
    public function testSubqueryInSelect()
    {
        $this->loadFixtures('Authors', 'Articles', 'Comments');
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
     *
     * @return void
     */
    public function testSuqueryInFrom()
    {
        $this->loadFixtures('Comments');
        $query = new Query($this->connection);
        $subquery = (new Query($this->connection))
            ->select(['id', 'comment'])
            ->from('comments')
            ->where(['created >' => new \DateTime('2007-03-18 10:45:23')], ['created' => 'datetime']);
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
     *
     * @return void
     */
    public function testSubqueryInWhere()
    {
        $this->loadFixtures('Authors', 'Comments');
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
            ->where(['created >' => new \DateTime('2007-03-18 10:45:23')], ['created' => 'datetime']);
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
     *
     * @return void
     */
    public function testSubqueryExistsWhere()
    {
        $this->loadFixtures('Articles', 'Authors');
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
     *
     * @return void
     */
    public function testSubqueryInJoin()
    {
        $this->loadFixtures('Authors', 'Articles');
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
     *
     * @return void
     */
    public function testUnion()
    {
        $this->loadFixtures('Authors', 'Articles', 'Comments');
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
     *
     * @return void
     */
    public function testUnionOrderBy()
    {
        $this->loadFixtures('Articles', 'Comments');
        $this->skipIf(
            ($this->connection->driver() instanceof \Cake\Database\Driver\Sqlite ||
            $this->connection->driver() instanceof \Cake\Database\Driver\Sqlserver),
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
     *
     * @return void
     */
    public function testUnionAll()
    {
        $this->loadFixtures('Authors', 'Articles', 'Comments');
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
     *
     * @return void
     */
    public function testDecorateResults()
    {
        $this->loadFixtures('Articles');
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
            $this->assertEquals('bar', $row['foo']);
            $this->assertArrayNotHasKey('modified_id', $row);
        }

        $results = $query->decorateResults(null, true)->execute();
        while ($row = $result->fetch('assoc')) {
            $this->assertArrayNotHasKey('foo', $row);
            $this->assertArrayNotHasKey('modified_id', $row);
        }
        $results->closeCursor();
    }

    /**
     * Test a basic delete using from()
     *
     * @return void
     */
    public function testDeleteWithFrom()
    {
        $this->loadFixtures('Authors');
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
     *
     * @return void
     */
    public function testDeleteWithAliasedFrom()
    {
        $this->loadFixtures('Authors');
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
     *
     * @return void
     */
    public function testDeleteNoFrom()
    {
        $this->loadFixtures('Authors');
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
     *
     *
     * @expectedException \RuntimeException
     * @expectedExceptionMessage Aliases are being removed from conditions for UPDATE/DELETE queries, this can break references to joined tables.
     * @return void
     */
    public function testDeleteRemovingAliasesCanBreakJoins()
    {
        $query = new Query($this->connection);

        $query
            ->delete('authors')
            ->from(['a ' => 'authors'])
            ->innerJoin('articles')
            ->where(['a.id' => 1]);

        $query->sql();
    }

    /**
     * Test setting select() & delete() modes.
     *
     * @return void
     */
    public function testSelectAndDeleteOnSameQuery()
    {
        $this->loadFixtures('Authors');
        $query = new Query($this->connection);
        $result = $query->select()
            ->delete('authors')
            ->where('1 = 1');
        $result = $query->sql();

        $this->assertQuotedQuery('DELETE FROM <authors>', $result, !$this->autoQuote);
        $this->assertContains(' WHERE 1 = 1', $result);
    }

    /**
     * Test a simple update.
     *
     * @return void
     */
    public function testUpdateSimple()
    {
        $this->loadFixtures('Authors');
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
     *
     * @expectedException \InvalidArgumentException
     *
     * @return void
     */
    public function testUpdateArgTypeChecking()
    {
        $query = new Query($this->connection);
        $query->update(['Articles']);
    }

    /**
     * Test update with multiple fields.
     *
     * @return void
     */
    public function testUpdateMultipleFields()
    {
        $this->loadFixtures('Articles');
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
     *
     * @return void
     */
    public function testUpdateMultipleFieldsArray()
    {
        $this->loadFixtures('Articles');
        $query = new Query($this->connection);
        $query->update('articles')
            ->set([
                'title' => 'mark',
                'body' => 'some text'
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
     *
     * @return void
     */
    public function testUpdateWithExpression()
    {
        $this->loadFixtures('Comments');
        $query = new Query($this->connection);

        $expr = $query->newExpr()->equalFields('article_id', 'user_id');

        $query->update('comments')
            ->set($expr)
            ->where(['id' => 1]);
        $result = $query->sql();

        $this->assertQuotedQuery(
            'UPDATE <comments> SET <article_id> = \(<user_id>\) WHERE <id> = :',
            $result,
            !$this->autoQuote
        );

        $result = $query->execute();
        $this->assertCount(1, $result);
        $result->closeCursor();
    }

    /**
     * Test update with array fields and types.
     *
     * @return void
     */
    public function testUpdateArrayFields()
    {
        $this->loadFixtures('Comments');
        $query = new Query($this->connection);
        $date = new \DateTime;
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
     *
     * @return void
     */
    public function testUpdateSetCallable()
    {
        $this->loadFixtures('Comments');
        $query = new Query($this->connection);
        $date = new \DateTime;
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
     *
     * @return void
     */
    public function testUpdateStripAliasesFromConditions()
    {
        $query = new Query($this->connection);

        $query
            ->update('authors')
            ->set(['name' => 'name'])
            ->where([
                'OR' => [
                    'a.id' => 1,
                    'AND' => [
                        'b.name NOT IN' => ['foo', 'bar'],
                        'OR' => [
                            $query->newExpr()->eq(new IdentifierExpression('c.name'), 'zap'),
                            'd.name' => 'baz',
                            (new Query($this->connection))->select(['e.name'])->where(['e.name' => 'oof'])
                        ]
                    ]
                ],
            ]);

        $this->assertQuotedQuery(
            'UPDATE <authors> SET <name> = :c0 WHERE \(' .
                '<id> = :c1 OR \(' .
                    '<name> not in \(:c2,:c3\) AND \(' .
                        '\(<c>\.<name>\) = :c4 OR <name> = :c5 OR \(SELECT <e>\.<name> WHERE <e>\.<name> = :c6\)' .
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
     *
     * @expectedException \RuntimeException
     * @expectedExceptionMessage Aliases are being removed from conditions for UPDATE/DELETE queries, this can break references to joined tables.
     * @return void
     */
    public function testUpdateRemovingAliasesCanBreakJoins()
    {
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
     *
     * @expectedException \Cake\Database\Exception
     * @return void
     */
    public function testInsertValuesBeforeInsertFailure()
    {
        $query = new Query($this->connection);
        $query->select('*')->values([
            'id' => 1,
            'title' => 'mark',
            'body' => 'test insert'
        ]);
    }

    /**
     * Inserting nothing should not generate an error.
     *
     * @expectedException \RuntimeException
     * @expectedExceptionMessage At least 1 column is required to perform an insert.
     * @return void
     */
    public function testInsertNothing()
    {
        $query = new Query($this->connection);
        $query->insert([]);
    }

    /**
     * Test insert overwrites values
     *
     * @return void
     */
    public function testInsertOverwritesValues()
    {
        $this->loadFixtures('Articles');
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
     *
     * @return void
     */
    public function testInsertSimple()
    {
        $this->loadFixtures('Articles');
        $query = new Query($this->connection);
        $query->insert(['title', 'body'])
            ->into('articles')
            ->values([
                'title' => 'mark',
                'body' => 'test insert'
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
        if (!$this->connection->driver() instanceof \Cake\Database\Driver\Sqlserver) {
            $this->assertCount(1, $result, '1 row should be inserted');
        }

        $expected = [
            [
                'id' => 4,
                'author_id' => null,
                'title' => 'mark',
                'body' => 'test insert',
                'published' => 'N',
            ]
        ];
        $this->assertTable('articles', 1, $expected, ['id >=' => 4]);
    }

    /**
     * Test insert queries quote integer column names
     *
     * @return void
     */
    public function testInsertQuoteColumns()
    {
        $this->loadFixtures('Articles');
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
     *
     * @return void
     */
    public function testInsertSparseRow()
    {
        $this->loadFixtures('Articles');
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
        if (!$this->connection->driver() instanceof \Cake\Database\Driver\Sqlserver) {
            $this->assertCount(1, $result, '1 row should be inserted');
        }

        $expected = [
            [
                'id' => 4,
                'author_id' => null,
                'title' => 'mark',
                'body' => null,
                'published' => 'N',
            ]
        ];
        $this->assertTable('articles', 1, $expected, ['id >=' => 4]);
    }

    /**
     * Test inserting multiple rows with sparse data.
     *
     * @return void
     */
    public function testInsertMultipleRowsSparse()
    {
        $this->loadFixtures('Articles');
        $query = new Query($this->connection);
        $query->insert(['title', 'body'])
            ->into('articles')
            ->values([
                'body' => 'test insert'
            ])
            ->values([
                'title' => 'jose',
            ]);

        $result = $query->execute();
        $result->closeCursor();

        //PDO_SQLSRV returns -1 for successful inserts when using INSERT ... OUTPUT
        if (!$this->connection->driver() instanceof \Cake\Database\Driver\Sqlserver) {
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
     *
     * @return void
     */
    public function testInsertFromSelect()
    {
        $this->loadFixtures('Authors', 'Articles');
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
        if (!$this->connection->driver() instanceof \Cake\Database\Driver\Sqlserver) {
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
     *
     * @expectedException \Cake\Database\Exception
     */
    public function testInsertFailureMixingTypesArrayFirst()
    {
        $this->loadFixtures('Articles');
        $query = new Query($this->connection);
        $query->insert(['name'])
            ->into('articles')
            ->values(['name' => 'mark'])
            ->values(new Query($this->connection));
    }

    /**
     * Test that an exception is raised when mixing query + array types.
     *
     * @expectedException \Cake\Database\Exception
     */
    public function testInsertFailureMixingTypesQueryFirst()
    {
        $this->loadFixtures('Articles');
        $query = new Query($this->connection);
        $query->insert(['name'])
            ->into('articles')
            ->values(new Query($this->connection))
            ->values(['name' => 'mark']);
    }

    /**
     * Test that insert can use expression objects as values.
     *
     * @return void
     */
    public function testInsertExpressionValues()
    {
        $this->loadFixtures('Articles', 'Authors');
        $query = new Query($this->connection);
        $query->insert(['title', 'author_id'])
            ->into('articles')
            ->values(['title' => $query->newExpr("SELECT 'jose'"), 'author_id' => 99]);

        $result = $query->execute();
        $result->closeCursor();

        //PDO_SQLSRV returns -1 for successful inserts when using INSERT ... OUTPUT
        if (!$this->connection->driver() instanceof \Cake\Database\Driver\Sqlserver) {
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
        if (!$this->connection->driver() instanceof \Cake\Database\Driver\Sqlserver) {
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
     * Tests that functions are correctly transformed and their parameters are bound
     *
     * @group FunctionExpression
     * @return void
     */
    public function testSQLFunctions()
    {
        $this->loadFixtures('Comments');
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
                'c' => $query->func()->concat(['comment' => 'literal', ' is appended'])
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
        $this->assertEquals(3, abs($result[0]['d']));

        $query = new Query($this->connection);
        $result = $query
            ->select(['d' => $query->func()->now('date')])
            ->execute();
        $this->assertEquals([['d' => date('Y-m-d')]], $result->fetchAll('assoc'));

        $query = new Query($this->connection);
        $result = $query
            ->select(['d' => $query->func()->now('time')])
            ->execute();

        $this->assertWithinRange(
            date('U'),
            (new \DateTime($result->fetchAll('assoc')[0]['d']))->format('U'),
            1
        );

        $query = new Query($this->connection);
        $result = $query
            ->select(['d' => $query->func()->now()])
            ->execute();
        $this->assertWithinRange(
            date('U'),
            (new \DateTime($result->fetchAll('assoc')[0]['d']))->format('U'),
            1
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
                'substractYears' => $query->func()->dateAdd('created', -2, 'year')
            ])
            ->from('comments')
            ->where(['created' => '2007-03-18 10:45:23'])
            ->execute()
            ->fetchAll('assoc');
        $result[0]['m'] = ltrim($result[0]['m'], '0');
        $result[0]['me'] = ltrim($result[0]['me'], '0');
        $result[0]['addDays'] = substr($result[0]['addDays'], 0, 10);
        $result[0]['substractYears'] = substr($result[0]['substractYears'], 0, 10);
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
            'substractYears' => '2005-03-18'
        ];
        $this->assertEquals($expected, $result[0]);
    }

    /**
     * Tests that default types are passed to functions accepting a $types param
     *
     * @return void
     */
    public function testDefaultTypes()
    {
        $this->loadFixtures('Comments');
        $query = new Query($this->connection);
        $this->assertEquals([], $query->defaultTypes());
        $types = ['id' => 'integer', 'created' => 'datetime'];
        $this->assertSame($query, $query->defaultTypes($types));
        $this->assertSame($types, $query->defaultTypes());

        $results = $query->select(['id', 'comment'])
            ->from('comments')
            ->where(['created >=' => new \DateTime('2007-03-18 10:55:00')])
            ->execute();
        $expected = [['id' => '6', 'comment' => 'Second Comment for Second Article']];
        $this->assertEquals($expected, $results->fetchAll('assoc'));

        // Now test default can be overridden
        $types = ['created' => 'date'];
        $results = $query
            ->where(['created >=' => new \DateTime('2007-03-18 10:50:00')], $types, true)
            ->execute();
        $this->assertCount(6, $results, 'All 6 rows should match.');
    }

    /**
     * Tests parameter binding
     *
     * @return void
     */
    public function testBind()
    {
        $this->loadFixtures('Comments');
        $query = new Query($this->connection);
        $results = $query->select(['id', 'comment'])
            ->from('comments')
            ->where(['created BETWEEN :foo AND :bar'])
            ->bind(':foo', new \DateTime('2007-03-18 10:50:00'), 'datetime')
            ->bind(':bar', new \DateTime('2007-03-18 10:52:00'), 'datetime')
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
     *
     * @return void
     */
    public function testAppendSelect()
    {
        $this->loadFixtures('Articles');
        $query = new Query($this->connection);
        $sql = $query
            ->select(['id', 'title'])
            ->from('articles')
            ->where(['id' => 1])
            ->epilog('FOR UPDATE')
            ->sql();
        $this->assertContains('SELECT', $sql);
        $this->assertContains('FROM', $sql);
        $this->assertContains('WHERE', $sql);
        $this->assertEquals(' FOR UPDATE', substr($sql, -11));
    }

    /**
     * Test that epilog() will actually append a string to an insert query
     *
     * @return void
     */
    public function testAppendInsert()
    {
        $query = new Query($this->connection);
        $sql = $query
            ->insert(['id', 'title'])
            ->into('articles')
            ->values([1, 'a title'])
            ->epilog('RETURNING id')
            ->sql();
        $this->assertContains('INSERT', $sql);
        $this->assertContains('INTO', $sql);
        $this->assertContains('VALUES', $sql);
        $this->assertEquals(' RETURNING id', substr($sql, -13));
    }

    /**
     * Test that epilog() will actually append a string to an update query
     *
     * @return void
     */
    public function testAppendUpdate()
    {
        $query = new Query($this->connection);
        $sql = $query
            ->update('articles')
            ->set(['title' => 'foo'])
            ->where(['id' => 1])
            ->epilog('RETURNING id')
            ->sql();
        $this->assertContains('UPDATE', $sql);
        $this->assertContains('SET', $sql);
        $this->assertContains('WHERE', $sql);
        $this->assertEquals(' RETURNING id', substr($sql, -13));
    }

    /**
     * Test that epilog() will actually append a string to a delete query
     *
     * @return void
     */
    public function testAppendDelete()
    {
        $query = new Query($this->connection);
        $sql = $query
            ->delete('articles')
            ->where(['id' => 1])
            ->epilog('RETURNING id')
            ->sql();
        $this->assertContains('DELETE FROM', $sql);
        $this->assertContains('WHERE', $sql);
        $this->assertEquals(' RETURNING id', substr($sql, -13));
    }

    /**
     * Tests automatic identifier quoting in the select clause
     *
     * @return void
     */
    public function testQuotingSelectFieldsAndAlias()
    {
        $this->connection->driver()->autoQuoting(true);
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
     *
     * @return void
     */
    public function testQuotingFromAndAlias()
    {
        $this->connection->driver()->autoQuoting(true);
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
     *
     * @return void
     */
    public function testQuotingDistinctOn()
    {
        $this->connection->driver()->autoQuoting(true);
        $query = new Query($this->connection);
        $sql = $query->select('*')->distinct(['something'])->sql();
        $this->assertQuotedQuery('<something>', $sql);
    }

    /**
     * Tests automatic identifier quoting in the join clause
     *
     * @return void
     */
    public function testQuotingJoinsAndAlias()
    {
        $this->connection->driver()->autoQuoting(true);
        $query = new Query($this->connection);
        $sql = $query->select('*')->join(['something'])->sql();
        $this->assertQuotedQuery('JOIN <something>', $sql);

        $query = new Query($this->connection);
        $sql = $query->select('*')->join(['foo' => 'something'])->sql();
        $this->assertQuotedQuery('JOIN <something> <foo>', $sql);

        $query = new Query($this->connection);
        $sql = $query->select('*')->join(['foo' => $query->newExpr('bar')])->sql();
        $this->assertQuotedQuery('JOIN \(bar\) <foo>', $sql);
    }

    /**
     * Tests automatic identifier quoting in the group by clause
     *
     * @return void
     */
    public function testQuotingGroupBy()
    {
        $this->connection->driver()->autoQuoting(true);
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
     *
     * @return void
     */
    public function testQuotingExpressions()
    {
        $this->connection->driver()->autoQuoting(true);
        $query = new Query($this->connection);
        $sql = $query->select('*')
            ->where(['something' => 'value'])
            ->sql();
        $this->assertQuotedQuery('WHERE <something> = :c0', $sql);

        $query = new Query($this->connection);
        $sql = $query->select('*')
            ->where([
                'something' => 'value',
                'OR' => ['foo' => 'bar', 'baz' => 'cake']
            ])
            ->sql();
        $this->assertQuotedQuery('<something> = :c0 AND', $sql);
        $this->assertQuotedQuery('\(<foo> = :c1 OR <baz> = :c2\)', $sql);
    }

    /**
     * Tests that insert query parts get quoted automatically
     *
     * @return void
     */
    public function testQuotingInsert()
    {
        $this->connection->driver()->autoQuoting(true);
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
     *
     * @return void
     */
    public function testToString()
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
     *
     * @return void
     */
    public function testDebugInfo()
    {
        $query = (new Query($this->connection))->select('*')
            ->from('articles')
            ->defaultTypes(['id' => 'integer'])
            ->where(['id' => '1']);

        $expected = [
            '(help)' => 'This is a Query object, to get the results execute or iterate it.',
            'sql' => $query->sql(),
            'params' => [
                ':c0' => ['value' => '1', 'type' => 'integer', 'placeholder' => 'c0']
            ],
            'defaultTypes' => ['id' => 'integer'],
            'decorators' => 0,
            'executed' => false
        ];
        $result = $query->__debugInfo();
        $this->assertEquals($expected, $result);

        $query->execute();
        $expected = [
            '(help)' => 'This is a Query object, to get the results execute or iterate it.',
            'sql' => $query->sql(),
            'params' => [
                ':c0' => ['value' => '1', 'type' => 'integer', 'placeholder' => 'c0']
            ],
            'defaultTypes' => ['id' => 'integer'],
            'decorators' => 0,
            'executed' => true
        ];
        $result = $query->__debugInfo();
        $this->assertEquals($expected, $result);
    }

    /**
     * Tests __debugInfo on incomplete query
     *
     * @return void
     */
    public function testDebugInfoIncompleteQuery()
    {
        $query = (new Query($this->connection))
            ->insert(['title']);
        $result = $query->__debugInfo();
        $this->assertContains('incomplete', $result['sql']);
        $this->assertSame([], $result['params']);
    }

    /**
     * Tests that it is possible to pass ExpressionInterface to isNull and isNotNull
     *
     * @return void
     */
    public function testIsNullWithExpressions()
    {
        $this->loadFixtures('Authors');
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
     *
     * @return void
     */
    public function testIsNullAutoQuoting()
    {
        $this->connection->driver()->autoQuoting(true);
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
     *
     * @return void
     */
    public function testDirectIsNull()
    {
        $this->loadFixtures('Authors');
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
     * Tests that using the IS NOT operator will automatically translate to the best
     * possible operator depending on the passed value
     *
     * @return void
     */
    public function testDirectIsNotNull()
    {
        $this->loadFixtures('Authors');
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
     * Tests that case statements work correctly for various use-cases.
     *
     * @return void
     */
    public function testSqlCaseStatement()
    {
        $this->loadFixtures('Comments');
        $query = new Query($this->connection);
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

        //Postgres requires the case statement to be cast to a integer
        if ($this->connection->driver() instanceof \Cake\Database\Driver\Postgres) {
            $publishedCase = $query->func()->cast([$publishedCase, 'integer' => 'literal'])->type(' AS ');
            $notPublishedCase = $query->func()->cast([$notPublishedCase, 'integer' => 'literal'])->type(' AS ');
        }

        $results = $query
            ->select([
                'published' => $query->func()->sum($publishedCase),
                'not_published' => $query->func()->sum($notPublishedCase)
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
                'published' => 'L'
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
                ->add(['published' => 'N'])
        ];
        $values = [
            'Published',
            'Not published',
            'None'
        ];
        $results = $query
            ->select([
                'id',
                'comment',
                'status' => $query->newExpr()->addCase($conditions, $values)
            ])
            ->from(['comments'])
            ->execute()
            ->fetchAll('assoc');

        $this->assertEquals('Published', $results[2]['status']);
        $this->assertEquals('Not published', $results[3]['status']);
        $this->assertEquals('None', $results[6]['status']);
    }

    /**
     * Shows that bufferResults(false) will prevent client-side results buffering
     *
     * @return void
     */
    public function testUnbufferedQuery()
    {
        $this->loadFixtures('Articles');
        $query = new Query($this->connection);
        $result = $query->select(['body', 'author_id'])
            ->from('articles')
            ->bufferResults(false)
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
    }

    /**
     * Test that cloning goes deep.
     *
     * @return void
     */
    public function testDeepClone()
    {
        $this->loadFixtures('Articles');
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
            $query->selectTypeMap(),
            $dupe->selectTypeMap()
        );
    }

    /**
     * Tests the selectTypeMap method
     *
     * @return void
     */
    public function testSelectTypeMap()
    {
        $query = new Query($this->connection);
        $typeMap = $query->selectTypeMap();
        $this->assertInstanceOf(TypeMap::class, $typeMap);
        $another = clone $typeMap;
        $query->selectTypeMap($another);
        $this->assertSame($another, $query->selectTypeMap());
    }

    /**
     * Tests the automatic type conversion for the fields in the result
     *
     * @return void
     */
    public function testSelectTypeConversion()
    {
        $this->loadFixtures('Comments');
        $query = new Query($this->connection);
        $query
            ->select(['id', 'comment', 'the_date' => 'created'])
            ->from('comments')
            ->limit(1)
            ->selectTypeMap()->types(['id' => 'integer', 'the_date' => 'datetime']);
        $result = $query->execute()->fetchAll('assoc');
        $this->assertInternalType('integer', $result[0]['id']);
        $this->assertInstanceOf('DateTime', $result[0]['the_date']);
    }


    /**
     * Tests that the json type can save and get data symetrically
     *
     * @return void
     */
    public function testSymetricJsonType()
    {
        $query = new Query($this->connection);
        $insert = $query
            ->insert(['comment', 'article_id', 'user_id'], ['comment' => 'json'])
            ->into('comments')
            ->values([
                'comment' => ['a' => 'b', 'c' => true],
                'article_id' => 1,
                'user_id' => 1
            ])
            ->execute();

        $id = $insert->lastInsertId('comments', 'id');
        $insert->closeCursor();

        $query = new Query($this->connection);
        $query
            ->select(['comment'])
            ->from('comments')
            ->where(['id' => $id])
            ->selectTypeMap()->types(['comment' => 'json']);

        $result = $query->execute();
        $comment = $result->fetchAll('assoc')[0]['comment'];
        $result->closeCursor();
        $this->assertSame(['a' => 'b', 'c' => true], $comment);
    }

    /**
     * Test removeJoin().
     *
     * @return void
     */
    public function testRemoveJoin()
    {
        $this->loadFixtures('Articles');
        $query = new Query($this->connection);
        $query->select(['id', 'title'])
            ->from('articles')
            ->join(['authors' => [
                'type' => 'INNER',
                'conditions' => ['articles.author_id = authors.id']
            ]]);
        $this->assertArrayHasKey('authors', $query->join());

        $this->assertSame($query, $query->removeJoin('authors'));
        $this->assertArrayNotHasKey('authors', $query->join());
    }

    /**
     * Tests that types in the type map are used in the
     * specific comparison functions when using a callable
     *
     * @return void
     */
    public function testBetweenExpressionAndTypeMap()
    {
        $this->loadFixtures('Comments');
        $query = new Query($this->connection);
        $query->select('id')
            ->from('comments')
            ->defaultTypes(['created' => 'datetime'])
            ->where(function ($expr) {
                $from = new \DateTime('2007-03-18 10:45:00');
                $to = new \DateTime('2007-03-18 10:48:00');

                return $expr->between('created', $from, $to);
            });
        $this->assertCount(2, $query->execute()->fetchAll());
    }

    /**
     * Test use of modifiers in a INSERT query
     *
     * Testing the generated SQL since the modifiers are usually different per driver
     *
     * @return void
     */
    public function testInsertModifiers()
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
     *
     * @return void
     */
    public function testUpdateModifiers()
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
     *
     * @return void
     */
    public function testDeleteModifiers()
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
     * Tests that fetch returns an anonymous object when the string 'obj'
     * is passed as an argument
     *
     * @return void
     */
    public function testSelectWithObjFetchType()
    {
        $this->loadFixtures('Comments');
        $query = new Query($this->connection);
        $result = $query
            ->select(['id'])
            ->from('comments')
            ->where(['id' => '1'])
            ->execute();
        $obj = (object)['id' => 1];
        $this->assertEquals($obj, $result->fetch('obj'));

        $query = new Query($this->connection);
        $result = $query
            ->select(['id'])
            ->from('comments')
            ->where(['id' => '1'])
            ->execute();
        $rows = $result->fetchAll('obj');
        $this->assertEquals($obj, $rows[0]);
    }

    /**
     * Assertion for comparing a table's contents with what is in it.
     *
     * @param string $table
     * @param int $count
     * @param array $rows
     * @param array $conditions
     * @return void
     */
    public function assertTable($table, $count, $rows, $conditions = [])
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
     * @return void
     */
    public function assertQuotedQuery($pattern, $query, $optional = false)
    {
        if ($optional) {
            $optional = '?';
        }
        $pattern = str_replace('<', '[`"\[]' . $optional, $pattern);
        $pattern = str_replace('>', '[`"\]]' . $optional, $pattern);
        $this->assertRegExp('#' . $pattern . '#', $query);
    }
}
