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
 * @since         4.1.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Test\TestCase\Database\QueryTests;

use Cake\Database\Driver\Mysql;
use Cake\Database\Driver\Sqlite;
use Cake\Database\Driver\Sqlserver;
use Cake\Database\DriverInterface;
use Cake\Database\Expression\CommonTableExpression;
use Cake\Database\Expression\QueryExpression;
use Cake\Database\Query;
use Cake\Database\ValueBinder;
use Cake\Datasource\ConnectionManager;
use Cake\TestSuite\TestCase;

class CommonTableExpressionQueryTest extends TestCase
{
    /**
     * @inheritDoc
     */
    protected $fixtures = [
        'core.Articles',
    ];

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

        $this->skipIf(
            !$this->connection->getDriver()->supports(DriverInterface::FEATURE_CTE),
            'The current driver does not support common table expressions.'
        );
    }

    public function tearDown(): void
    {
        parent::tearDown();
        unset($this->connection);
    }

    /**
     * Tests with() sql generation.
     */
    public function testWithCte(): void
    {
        $query = $this->connection->selectQuery()
            ->with(new CommonTableExpression('cte', function () {
                return $this->connection->selectQuery(['col' => 1]);
            }))
            ->select('col')
            ->from('cte');

        $this->assertRegExpSql(
            'WITH <cte> AS \(SELECT 1 AS <col>\) SELECT <col> FROM <cte>',
            $query->sql(new ValueBinder()),
            !$this->autoQuote
        );

        $expected = [
            [
                'col' => '1',
            ],
        ];

        $result = $query->execute();
        $this->assertEquals($expected, $result->fetchAll('assoc'));
        $result->closeCursor();
    }

    /**
     * Tests calling with() with overwrite clears other CTEs.
     */
    public function testWithCteOverwrite(): void
    {
        $query = $this->connection->selectQuery()
            ->with(new CommonTableExpression('cte', function () {
                return $this->connection->selectQuery(['col' => '1']);
            }))
            ->select('col')
            ->from('cte');

        $this->assertEqualsSql(
            'WITH cte AS (SELECT 1 AS col) SELECT col FROM cte',
            $query->sql(new ValueBinder())
        );

        $query
            ->with(new CommonTableExpression('cte2', $this->connection->selectQuery()), true)
            ->from('cte2', true);
        $this->assertEqualsSql(
            'WITH cte2 AS () SELECT col FROM cte2',
            $query->sql(new ValueBinder())
        );
    }

    /**
     * Tests recursive CTE.
     */
    public function testWithRecursiveCte(): void
    {
        $query = $this->connection->selectQuery()
            ->with(function (CommonTableExpression $cte, Query $query) {
                $anchorQuery = $query->select(1);

                $recursiveQuery = $query->getConnection()
                    ->selectQuery()
                    ->select(function (Query $query) {
                        return $query->newExpr('col + 1');
                    })
                    ->from('cte')
                    ->where(['col !=' => 3], ['col' => 'integer']);

                $cteQuery = $anchorQuery->unionAll($recursiveQuery);

                return $cte
                    ->name('cte')
                    ->field(['col'])
                    ->query($cteQuery)
                    ->recursive();
            })
            ->select('col')
            ->from('cte');

        if ($this->connection->getDriver() instanceof Sqlserver) {
            $expectedSql =
                'WITH cte(col) AS ' .
                    "(SELECT 1\nUNION ALL SELECT (col + 1) FROM cte WHERE col != :c0) " .
                        'SELECT col FROM cte';
        } elseif ($this->connection->getDriver() instanceof Sqlite) {
            $expectedSql =
                'WITH RECURSIVE cte(col) AS ' .
                    "(SELECT 1\nUNION ALL SELECT (col + 1) FROM cte WHERE col != :c0) " .
                        'SELECT col FROM cte';
        } else {
            $expectedSql =
                'WITH RECURSIVE cte(col) AS ' .
                    "((SELECT 1)\nUNION ALL (SELECT (col + 1) FROM cte WHERE col != :c0)) " .
                        'SELECT col FROM cte';
        }
        $this->assertEqualsSql(
            $expectedSql,
            $query->sql(new ValueBinder())
        );

        $expected = [
            [
                'col' => '1',
            ],
            [
                'col' => '2',
            ],
            [
                'col' => '3',
            ],
        ];

        $result = $query->execute();
        $this->assertEquals($expected, $result->fetchAll('assoc'));
        $result->closeCursor();
    }

    /**
     * Test inserting from CTE.
     */
    public function testWithInsertQuery(): void
    {
        $this->skipIf(
            ($this->connection->getDriver() instanceof Mysql),
            '`WITH ... INSERT INTO` syntax is not supported in MySQL.'
        );

        // test initial state
        $result = $this->connection
        ->selectQuery('*')
            ->from('articles')
            ->where(['id' => 4])
            ->execute();
        $this->assertFalse($result->fetch('assoc'));
        $result->closeCursor();

        $query = $this->connection
            ->insertQuery()
            ->with(function (CommonTableExpression $cte, Query $query) {
                return $cte
                    ->name('cte')
                    ->field(['title', 'body'])
                    ->query($query->newExpr("SELECT 'Fourth Article', 'Fourth Article Body'"));
            })
            ->insert(['title', 'body'])
            ->into('articles')
            ->values(
                $this->connection
                    ->selectQuery('*')
                    ->from('cte')
            );

        $this->assertRegExpSql(
            "WITH <cte>\(<title>, <body>\) AS \(SELECT 'Fourth Article', 'Fourth Article Body'\) " .
                'INSERT INTO <articles> \(<title>, <body>\)',
            $query->sql(new ValueBinder()),
            !$this->autoQuote
        );

        // run insert
        $query->execute()->closeCursor();

        $expected = [
            'id' => '4',
            'author_id' => null,
            'title' => 'Fourth Article',
            'body' => 'Fourth Article Body',
            'published' => 'N',
        ];

        // test updated state
        $result = $this->connection
            ->selectQuery('*')
            ->from('articles')
            ->where(['id' => 4])
            ->execute();
        $this->assertEquals($expected, $result->fetch('assoc'));
        $result->closeCursor();
    }

    /**
     * Tests inserting from CTE as values list.
     */
    public function testWithInInsertWithValuesQuery(): void
    {
        $this->skipIf(
            ($this->connection->getDriver() instanceof Sqlserver),
            '`INSERT INTO ... WITH` syntax is not supported in SQL Server.'
        );

        $query = $this->connection->insertQuery()
            ->insert(['title', 'body'])
            ->into('articles')
            ->values(
                $this->connection->selectQuery()
                    ->with(function (CommonTableExpression $cte, Query $query) {
                        return $cte
                            ->name('cte')
                            ->field(['title', 'body'])
                            ->query($query->newExpr("SELECT 'Fourth Article', 'Fourth Article Body'"));
                    })
                    ->select('*')
                    ->from('cte')
            );

        $this->assertRegExpSql(
            'INSERT INTO <articles> \(<title>, <body>\) ' .
                "WITH <cte>\(<title>, <body>\) AS \(SELECT 'Fourth Article', 'Fourth Article Body'\) SELECT \* FROM <cte>",
            $query->sql(new ValueBinder()),
            !$this->autoQuote
        );

        // run insert
        $query->execute()->closeCursor();

        $expected = [
            'id' => '4',
            'author_id' => null,
            'title' => 'Fourth Article',
            'body' => 'Fourth Article Body',
            'published' => 'N',
        ];

        // test updated state
        $result = $this->connection
            ->selectQuery('*')
            ->from('articles')
            ->where(['id' => 4])
            ->execute();
        $this->assertEquals($expected, $result->fetch('assoc'));
        $result->closeCursor();
    }

    /**
     * Tests updating from CTE.
     */
    public function testWithInUpdateQuery(): void
    {
        $this->skipIf(
            $this->connection->getDriver() instanceof Mysql && $this->connection->getDriver()->isMariadb(),
            'MariaDB does not support CTEs in UPDATE query.'
        );

        // test initial state
        $result = $this->connection
            ->selectQuery(['count' => 'COUNT(*)'])
            ->from('articles')
            ->where(['published' => 'Y'])
            ->execute();
        $this->assertEquals(['count' => '3'], $result->fetch('assoc'));
        $result->closeCursor();

        $query = $this->connection->updateQuery()
            ->with(function (CommonTableExpression $cte, Query $query) {
                $cteQuery = $query
                    ->select('articles.id')
                    ->from('articles')
                    ->where(['articles.id !=' => 1]);

                return $cte
                    ->name('cte')
                    ->query($cteQuery);
            })
            ->update('articles')
            ->set('published', 'N')
            ->where(function (QueryExpression $exp, Query $query) {
                return $exp->in(
                    'articles.id',
                    $query
                        ->getConnection()
                        ->selectQuery('cte.id')
                        ->from('cte')
                );
            });

        $this->assertEqualsSql(
            'WITH cte AS (SELECT articles.id FROM articles WHERE articles.id != :c0) ' .
                'UPDATE articles SET published = :c1 WHERE id IN (SELECT cte.id FROM cte)',
            $query->sql(new ValueBinder())
        );

        // run update
        $query->execute()->closeCursor();

        // test updated state
        $result = $this->connection
            ->selectQuery(['count' => 'COUNT(*)'])
            ->from('articles')
            ->where(['published' => 'Y'])
            ->execute();
        $this->assertEquals(['count' => '1'], $result->fetch('assoc'));
        $result->closeCursor();
    }

    /**
     * Tests deleting from CTE.
     */
    public function testWithInDeleteQuery(): void
    {
        $this->skipIf(
            $this->connection->getDriver() instanceof Mysql && $this->connection->getDriver()->isMariadb(),
            'MariaDB does not support CTEs in DELETE query.'
        );

        // test initial state
        $result = $this->connection
            ->selectQuery(['count' => 'COUNT(*)'])
            ->from('articles')
            ->execute();
        $this->assertEquals(['count' => '3'], $result->fetch('assoc'));
        $result->closeCursor();

        $query = $this->connection->deleteQuery()
            ->with(function (CommonTableExpression $cte, Query $query) {
                $query->select('articles.id')
                    ->from('articles')
                    ->where(['articles.id !=' => 1]);

                return $cte
                    ->name('cte')
                    ->query($query);
            })
            ->from(['a' => 'articles'])
            ->where(function (QueryExpression $exp, Query $query) {
                return $exp->in(
                    'a.id',
                    $query
                        ->getConnection()
                        ->selectQuery('cte.id')
                        ->from('cte')
                );
            });

        $this->assertEqualsSql(
            'WITH cte AS (SELECT articles.id FROM articles WHERE articles.id != :c0) ' .
                'DELETE FROM articles WHERE id IN (SELECT cte.id FROM cte)',
            $query->sql(new ValueBinder())
        );

        // run delete
        $query->execute()->closeCursor();

        $expected = [
            'id' => '1',
            'author_id' => '1',
            'title' => 'First Article',
            'body' => 'First Article Body',
            'published' => 'Y',
        ];

        // test updated state
        $result = $this->connection
            ->selectQuery('*')
            ->from('articles')
            ->execute();
        $this->assertEquals($expected, $result->fetch('assoc'));
        $result->closeCursor();
    }
}
