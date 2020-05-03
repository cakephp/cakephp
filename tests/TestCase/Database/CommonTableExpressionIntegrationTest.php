<?php
declare(strict_types=1);

namespace Cake\Test\TestCase\Database;

use Cake\Database\Driver\Mysql;
use Cake\Database\Driver\Sqlserver;
use Cake\Database\Expression\CommonTableExpression;
use Cake\Database\Expression\QueryExpression;
use Cake\Database\Query;
use Cake\Database\ValueBinder;
use Cake\Datasource\ConnectionManager;
use Cake\TestSuite\TestCase;
use InvalidArgumentException;

class CommonTableExpressionIntegrationTest extends TestCase
{
    /**
     * @inheritDoc
     */
    protected $fixtures = [
        'core.Articles',
    ];

    /**
     * @inheritDoc
     */
    public $autoFixtures = false;

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

        $this->skipIf(
            !$this->connection->getDriver()->supportsCTEs(),
            'The current driver does not support common table expressions.'
        );

        $this->autoQuote = $this->connection->getDriver()->isAutoQuotingEnabled();
    }

    public function tearDown(): void
    {
        parent::tearDown();
        $this->connection->getDriver()->enableAutoQuoting($this->autoQuote);
        unset($this->connection);
    }

    public function assertQuotedQuery($pattern, $query, $optional = false): void
    {
        if ($optional) {
            $optional = '?';
        }
        $pattern = str_replace('<', '[`"\[]' . $optional, $pattern);
        $pattern = str_replace('>', '[`"\]]' . $optional, $pattern);
        $this->assertRegExp('#' . $pattern . '#', $query);
    }

    public function testCommonTableExpression()
    {
        $expression = $this->connection->newQuery()->cte('cte', $this->connection->newQuery()->select(1));

        $this->assertInstanceOf(CommonTableExpression::class, $expression);
        $this->assertEquals('SELECT 1', $expression->getQuery());
    }

    public function testCommonTableExpressionWithQueryAsClosure()
    {
        $expression = $this->connection
            ->newQuery()
            ->cte('cte', function (QueryExpression $exp, Query $query) {
                return $query->getConnection()->newQuery()->select(1);
            });

        $this->assertInstanceOf(CommonTableExpression::class, $expression);
        $this->assertEquals('SELECT 1', $expression->getQuery());
    }

    public function testWithCteAsExpression(): void
    {
        $cteQuery = $this->connection
            ->newQuery()
            ->select(['col' => 1]);

        $query = $this->connection
            ->newQuery()
            ->with(new CommonTableExpression('cte', $cteQuery))
            ->select('col')
            ->from('cte');

        $this->assertQuotedQuery(
            'WITH cte AS \(SELECT 1 AS <col>\) SELECT <col> FROM <cte>',
            $query->sql(),
            !$this->autoQuote
        );

        $expected = [
            [
                'col' => '1',
            ],
        ];

        $stmt = $query->execute();
        $result = $stmt->fetchAll('assoc');
        $stmt->closeCursor();
        $this->assertEquals($expected, $result);
    }

    public function testWithCteAsCallable(): void
    {
        $cteQuery = $this->connection
            ->newQuery()
            ->select(['col' => 1]);

        $query = $this->connection
            ->newQuery()
            ->with(function (QueryExpression $exp, Query $query) use ($cteQuery) {
                return $query
                    ->cte('cte', $cteQuery);
            })
            ->select('col')
            ->from('cte');

        $this->assertQuotedQuery(
            'WITH cte AS \(SELECT 1 AS <col>\) SELECT <col> FROM <cte>',
            $query->sql(),
            !$this->autoQuote
        );

        $expected = [
            [
                'col' => '1',
            ],
        ];

        $stmt = $query->execute();
        $result = $stmt->fetchAll('assoc');
        $stmt->closeCursor();
        $this->assertEquals($expected, $result);
    }

    public function testWithCteInvalidType(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'The common table expression must be an instance of `Cake\Database\Expression\CommonTableExpression`, ' .
            '`integer` given.'
        );

        $this->connection->newQuery()->with(123);
    }

    public function testWithCteNamesMustBeUnique(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'A common table expression with the name `cte` already exists.'
        );

        $this->connection
            ->newQuery()
            ->with(function (QueryExpression $exp, Query $query) {
                return $query
                    ->cte('cte', $query->newExpr('SELECT 1'));
            })
            ->with(function (QueryExpression $exp, Query $query) {
                return $query
                    ->cte('cte', $query->newExpr('SELECT 1'));
            });
    }

    public function testWithRecursiveCte(): void
    {
        $query1 = $this->connection
            ->newQuery()
            ->select(1);
        $query2 = $this->connection
            ->newQuery()
            ->select(function (Query $query) {
                return $query->newExpr('col + 1');
            })
            ->from('cte')
            ->where(['col !=' => 3], ['col' => 'integer']);
        $cteQuery = $query1->unionAll($query2);

        $query = $this->connection
            ->newQuery()
            ->with(function (QueryExpression $exp, Query $query) use ($cteQuery) {
                return $query
                    ->cte('cte', $cteQuery)
                    ->setFields(['col'])
                    ->setRecursive(true);
            })
            ->select('col')
            ->from('cte');

        $this->assertQuotedQuery(
            'WITH( RECURSIVE)? cte\(<col>\) AS ' .
                '\(\(?SELECT 1\)?\nUNION ALL \(?SELECT \(col \+ 1\) FROM <cte> WHERE <col> != \:c0\)?\) ' .
                    'SELECT <col> FROM <cte>',
            $query->sql(),
            !$this->autoQuote
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

        $stmt = $query->execute();
        $result = $stmt->fetchAll('assoc');
        $stmt->closeCursor();
        $this->assertEquals($expected, $result);
    }

    public function testWithNoFields(): void
    {
        $cteQuery = $this->connection
            ->newQuery()
            ->select(['col1' => 1, 'col2' => 2]);

        $query = $this->connection
            ->newQuery()
            ->with(function (QueryExpression $exp, Query $query) use ($cteQuery) {
                return $query
                    ->cte('cte', $cteQuery);
            })
            ->select('*')
            ->from('cte');

        $this->assertQuotedQuery(
            'WITH cte AS \(SELECT 1 AS <col1>, 2 AS <col2>\) SELECT \* FROM <cte>',
            $query->sql(),
            !$this->autoQuote
        );

        $expected = [
            [
                'col1' => '1',
                'col2' => '2',
            ],
        ];

        $stmt = $query->execute();
        $result = $stmt->fetchAll('assoc');
        $stmt->closeCursor();
        $this->assertEquals($expected, $result);
    }

    public function testWithFieldsAsStrings(): void
    {
        $cteQuery = $this->connection
            ->newQuery()
            ->select([1, 2]);

        $query = $this->connection
            ->newQuery()
            ->with(function (QueryExpression $exp, Query $query) use ($cteQuery) {
                return $query
                    ->cte('cte', $cteQuery)
                    ->setFields(['col1', 'col2']);
            })
            ->select(['col1', 'col2'])
            ->from('cte');

        $this->assertQuotedQuery(
            'WITH cte\(<col1>, <col2>\) AS \(SELECT 1, 2\) SELECT <col1>, <col2> FROM <cte>',
            $query->sql(),
            !$this->autoQuote
        );

        $expected = [
            [
                'col1' => '1',
                'col2' => '2',
            ],
        ];

        $stmt = $query->execute();
        $result = $stmt->fetchAll('assoc');
        $stmt->closeCursor();
        $this->assertEquals($expected, $result);
    }

    public function testWithFieldAsExpressions(): void
    {
        $cteQuery = $this->connection
            ->newQuery()
            ->select([1, 2]);

        $query = $this->connection
            ->newQuery()
            ->with(function (QueryExpression $exp, Query $query) use ($cteQuery) {
                return $query
                    ->cte('cte', $cteQuery)
                    ->setFields([
                        $cteQuery->identifier('col1'),
                        $cteQuery->identifier('col2'),
                    ]);
            })
            ->select(['col1', 'col2'])
            ->from('cte');

        $this->assertQuotedQuery(
            'WITH cte\(<col1>, <col2>\) AS \(SELECT 1, 2\) SELECT <col1>, <col2> FROM <cte>',
            $query->sql(),
            !$this->autoQuote
        );

        $expected = [
            [
                'col1' => '1',
                'col2' => '2',
            ],
        ];

        $stmt = $query->execute();
        $result = $stmt->fetchAll('assoc');
        $stmt->closeCursor();
        $this->assertEquals($expected, $result);
    }

    public function testWithReset(): void
    {
        $query = $this->connection->newQuery();

        $this->assertEmpty($query->clause('with'));

        $query
            ->with(function (QueryExpression $exp, Query $query) {
                return $query
                    ->cte('cte1', $query->getConnection()->newQuery()->select(['col' => 1]));
            })
            ->select('col')
            ->from('cte');

        $this->assertCount(1, $query->clause('with'));
        $this->assertQuotedQuery(
            'WITH cte1 AS \(SELECT 1 AS <col>\) SELECT <col> FROM <cte>',
            $query->sql(),
            !$this->autoQuote
        );

        $query->with(null, true);

        $this->assertEmpty($query->clause('with'));
        $this->assertQuotedQuery(
            'SELECT <col> FROM <cte>',
            $query->sql(),
            !$this->autoQuote
        );
    }

    public function testWithResetRequiresOverwriting(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Resetting the WITH clause only works when overwriting is enabled.');

        $this->connection->newQuery()->with(null);
    }

    public function testWithOverwrite(): void
    {
        $query = $this->connection->newQuery();

        $this->assertEmpty($query->clause('with'));

        $query
            ->with(function (QueryExpression $exp, Query $query) {
                return $query
                    ->cte('cte1', $query->getConnection()->newQuery()->select(['col' => 1]));
            })
            ->select('col')
            ->from('cte');

        $this->assertQuotedQuery(
            'WITH cte1 AS \(SELECT 1 AS <col>\) SELECT <col> FROM <cte>',
            $query->sql(),
            !$this->autoQuote
        );

        $query->with(function (QueryExpression $exp, Query $query) {
            return $query
                ->cte('cte2', $query->getConnection()->newQuery()->select(['col' => 2]));
        });

        $this->assertQuotedQuery(
            'WITH cte1 AS \(SELECT 1 AS <col>\), cte2 AS \(SELECT 2 AS <col>\) SELECT <col> FROM <cte>',
            $query->sql(),
            !$this->autoQuote
        );

        $query->with(
            function (QueryExpression $exp, Query $query) {
                return $query
                    ->cte('cte1', $query->getConnection()->newQuery()->select(['col' => 3]));
            },
            true
        );

        $this->assertQuotedQuery(
            'WITH cte1 AS \(SELECT 3 AS <col>\) SELECT <col> FROM <cte>',
            $query->sql(),
            !$this->autoQuote
        );
    }

    public function testWithInSubquery(): void
    {
        $this->skipIf(
            ($this->connection->getDriver() instanceof Sqlserver),
            '`WITH` in subquery syntax is not supported in SQL Server.'
        );

        $cteQuery = $this->connection
            ->newQuery()
            ->select(['col' => 1]);

        $subquery = $this->connection
            ->newQuery()
            ->with(new CommonTableExpression('cte', $cteQuery))
            ->select('col')
            ->from('cte');

        $query = $this->connection
            ->newQuery()
            ->select('col')
            ->from(['alias' => $subquery]);

        $this->assertQuotedQuery(
            'SELECT <col> FROM \(WITH cte AS \(SELECT 1 AS <col>\) SELECT <col> FROM <cte>\) <alias>',
            $query->sql(),
            !$this->autoQuote
        );

        $expected = [
            [
                'col' => '1',
            ],
        ];

        $stmt = $query->execute();
        $result = $stmt->fetchAll('assoc');
        $stmt->closeCursor();
        $this->assertEquals($expected, $result);
    }

    public function testWithInInsertQuery()
    {
        $this->skipIf(
            ($this->connection->getDriver() instanceof Mysql),
            '`WITH ... INSERT INTO` syntax is not supported in MySQL.'
        );

        $this->loadFixtures('Articles');

        $query = $this->connection
            ->newQuery()
            ->with(function (QueryExpression $exp, Query $query) {
                return $query
                    ->cte('cte', $query->newExpr("SELECT 'Fourth Article', 'Fourth Article Body'"))
                    ->setFields(['title', 'body']);
            })
            ->insert(['title', 'body'])
            ->into('articles')
            ->values(
                $this->connection
                    ->newQuery()
                    ->select('*')
                    ->from('cte')
            );

        $this->assertQuotedQuery(
            'WITH cte\(<title>, <body>\) AS \(SELECT \'Fourth Article\', \'Fourth Article Body\'\) ' .
                'INSERT INTO <articles> \(<title>, <body>\) (OUTPUT INSERTED\.\* )?SELECT \* FROM <cte>',
            $query->sql(),
            !$this->autoQuote
        );

        $stmt = $this->connection
            ->newQuery()
            ->select('*')
            ->from('articles')
            ->where(['id' => 4])
            ->execute();
        $result = $stmt->fetch('assoc');
        $stmt->closeCursor();
        $this->assertFalse($result);

        $query->execute()->closeCursor();

        $expected = [
            'id' => '4',
            'author_id' => null,
            'title' => 'Fourth Article',
            'body' => 'Fourth Article Body',
            'published' => 'N',
        ];

        $stmt = $this->connection
            ->newQuery()
            ->select('*')
            ->from('articles')
            ->where(['id' => 4])
            ->execute();
        $result = $stmt->fetch('assoc');
        $stmt->closeCursor();
        $this->assertEquals($expected, $result);
    }

    public function testWithInInsertQueryInsertIntoWith()
    {
        $this->skipIf(
            ($this->connection->getDriver() instanceof Sqlserver),
            '`INSERT INTO ... WITH` syntax is not supported in SQL Server.'
        );

        $this->loadFixtures('Articles');

        $query = $this->connection
            ->newQuery()
            ->insert(['title', 'body'])
            ->into('articles')
            ->values(
                $this->connection
                    ->newQuery()
                    ->with(function (QueryExpression $exp, Query $query) {
                        return $query
                            ->cte('cte', $query->newExpr("SELECT 'Fourth Article', 'Fourth Article Body'"))
                            ->setFields(['title', 'body']);
                    })
                    ->select('*')
                    ->from('cte')
            );

        $this->assertQuotedQuery(
            'INSERT INTO <articles> \(<title>, <body>\) ' .
                'WITH cte\(<title>, <body>\) AS \(SELECT \'Fourth Article\', \'Fourth Article Body\'\) SELECT \* FROM <cte>',
            $query->sql(),
            !$this->autoQuote
        );

        $query->execute()->closeCursor();

        $expected = [
            'id' => '4',
            'author_id' => null,
            'title' => 'Fourth Article',
            'body' => 'Fourth Article Body',
            'published' => 'N',
        ];

        $stmt = $this->connection
            ->newQuery()
            ->select('*')
            ->from('articles')
            ->where(['id' => 4])
            ->execute();
        $result = $stmt->fetch('assoc');
        $stmt->closeCursor();
        $this->assertEquals($expected, $result);
    }

    public function testWithInUpdateQuery()
    {
        $this->skipIf(
            ($this->connection->getDriver() instanceof Mysql) &&
            (strpos($this->connection->getDriver()->getVersion(), 'MariaDB') !== false),
            '`WITH ... UPDATE` syntax is not supported in MariaDB.'
        );

        $this->loadFixtures('Articles');

        $query = $this->connection
            ->newQuery()
            ->with(function (QueryExpression $exp, Query $query) {
                return $query->cte(
                    'cte',
                    $query
                        ->getConnection()
                        ->newQuery()
                        ->select('articles.id')
                        ->from('articles')
                        ->where(['articles.id !=' => 1])
                );
            })
            ->update('articles')
            ->set('published', 'N')
            ->where(function (QueryExpression $exp, Query $query) {
                return $exp->in(
                    'articles.id',
                    $query
                        ->getConnection()
                        ->newQuery()
                        ->select('cte.id')
                        ->from('cte')
                );
            });

        $this->assertQuotedQuery(
            'WITH cte AS \(SELECT <articles>.<id> FROM <articles> WHERE <articles>.<id> != \:c0\) ' .
                'UPDATE <articles> SET <published> = \:c1 WHERE <id> IN \(SELECT <cte>\.<id> FROM <cte>\)',
            $query->sql(),
            !$this->autoQuote
        );

        $expected = [
            'count' => '3',
        ];

        $stmt = $this->connection
            ->newQuery()
            ->select(['count' => 'COUNT(*)'])
            ->from('articles')
            ->where(['published' => 'Y'])
            ->execute();
        $result = $stmt->fetch('assoc');
        $stmt->closeCursor();
        $this->assertEquals($expected, $result);

        $query->execute()->closeCursor();

        $expected = [
            'count' => '1',
        ];

        $stmt = $this->connection
            ->newQuery()
            ->select(['count' => 'COUNT(*)'])
            ->from('articles')
            ->where(['published' => 'Y'])
            ->execute();
        $result = $stmt->fetch('assoc');
        $stmt->closeCursor();
        $this->assertEquals($expected, $result);
    }

    public function testWithInUpdateQueryUpdateCte()
    {
        $this->skipIf(
            !($this->connection->getDriver() instanceof Sqlserver),
            '`WITH cte ... UPDATE cte` syntax is only supported in SQL Server.'
        );

        $this->loadFixtures('Articles');

        $query = $this->connection
            ->newQuery()
            ->with(function (QueryExpression $exp, Query $query) {
                return $query->cte(
                    'cte',
                    $query
                        ->getConnection()
                        ->newQuery()
                        ->select('articles.published')
                        ->from('articles')
                        ->where(['articles.id !=' => 1])
                );
            })
            ->update('cte')
            ->set('cte.published', 'N');

        $this->assertQuotedQuery(
            'WITH cte AS \(SELECT <articles>.<published> FROM <articles> WHERE <articles>.<id> != \:c0\) ' .
                'UPDATE <cte> SET <cte>\.<published> = \:c1',
            $query->sql(),
            !$this->autoQuote
        );

        $expected = [
            'count' => '3',
        ];

        $stmt = $this->connection
            ->newQuery()
            ->select(['count' => 'COUNT(*)'])
            ->from('articles')
            ->where(['published' => 'Y'])
            ->execute();
        $result = $stmt->fetch('assoc');
        $stmt->closeCursor();
        $this->assertEquals($expected, $result);

        $query->execute()->closeCursor();

        $expected = [
            'count' => '1',
        ];

        $stmt = $this->connection
            ->newQuery()
            ->select(['count' => 'COUNT(*)'])
            ->from('articles')
            ->where(['published' => 'Y'])
            ->execute();
        $result = $stmt->fetch('assoc');
        $stmt->closeCursor();
        $this->assertEquals($expected, $result);
    }

    public function testWithInDeleteQuery()
    {
        $this->skipIf(
            ($this->connection->getDriver() instanceof Mysql) &&
            (strpos($this->connection->getDriver()->getVersion(), 'MariaDB') !== false),
            '`WITH ... DELETE` syntax is not supported in MariaDB.'
        );

        $this->loadFixtures('Articles');

        $query = $this->connection
            ->newQuery()
            ->with(function (QueryExpression $exp, Query $query) {
                return $query->cte(
                    'cte',
                    $query
                        ->getConnection()
                        ->newQuery()
                        ->select('articles.id')
                        ->from('articles')
                        ->where(['articles.id !=' => 1])
                );
            })
            ->delete()
            ->from(['a' => 'articles'])
            ->where(function (QueryExpression $exp, Query $query) {
                return $exp->in(
                    'a.id',
                    $query
                        ->getConnection()
                        ->newQuery()
                        ->select('cte.id')
                        ->from('cte')
                );
            });

        $this->assertQuotedQuery(
            'WITH cte AS \(SELECT <articles>\.<id> FROM <articles> WHERE <articles>\.<id> != \:c0\) ' .
                'DELETE FROM <articles> WHERE <id> IN \(SELECT <cte>\.<id> FROM <cte>\)',
            $query->sql(),
            !$this->autoQuote
        );

        $expected = [
            'count' => '3',
        ];

        $stmt = $this->connection
            ->newQuery()
            ->select(['count' => 'COUNT(*)'])
            ->from('articles')
            ->execute();
        $result = $stmt->fetch('assoc');
        $stmt->closeCursor();
        $this->assertEquals($expected, $result);

        $query->execute()->closeCursor();

        $expected = [
            [
                'id' => '1',
                'author_id' => '1',
                'title' => 'First Article',
                'body' => 'First Article Body',
                'published' => 'Y',
            ],
        ];

        $stmt = $this->connection->newQuery()->select('*')->from('articles')->execute();
        $result = $stmt->fetchAll('assoc');
        $stmt->closeCursor();
        $this->assertEquals($expected, $result);
    }

    public function testWithInDeleteQueryDeleteFromCte()
    {
        $this->skipIf(
            !($this->connection->getDriver() instanceof Sqlserver),
            '`WITH cte ... DELETE FROM cte` syntax is only supported in SQL Server.'
        );

        $this->loadFixtures('Articles');

        $query = $this->connection
            ->newQuery()
            ->with(function (QueryExpression $exp, Query $query) {
                return $query->cte(
                    'cte',
                    $query
                        ->getConnection()
                        ->newQuery()
                        ->select('id')
                        ->from('articles')
                        ->where(['id !=' => 1])
                );
            })
            ->delete()
            ->from('cte');

        $this->assertQuotedQuery(
            'WITH cte AS \(SELECT <id> FROM <articles> WHERE <id> != \:c0\) DELETE FROM <cte>',
            $query->sql(),
            !$this->autoQuote
        );

        $expected = [
            'count' => '3',
        ];

        $stmt = $this->connection
            ->newQuery()
            ->select(['count' => 'COUNT(*)'])
            ->from('articles')
            ->execute();
        $result = $stmt->fetch('assoc');
        $stmt->closeCursor();
        $this->assertEquals($expected, $result);

        $query->execute()->closeCursor();

        $expected = [
            [
                'id' => '1',
                'author_id' => '1',
                'title' => 'First Article',
                'body' => 'First Article Body',
                'published' => 'Y',
            ],
        ];

        $stmt = $this->connection
            ->newQuery()
            ->select('*')
            ->from('articles')
            ->execute();
        $result = $stmt->fetchAll('assoc');
        $stmt->closeCursor();
        $this->assertEquals($expected, $result);
    }

    public function testWithTransformExpressions()
    {
        $cteQuery = $this->connection
            ->newQuery()
            ->select(['col' => 1]);

        $query = $this->connection
            ->newQuery()
            ->with(function (QueryExpression $exp, Query $query) use ($cteQuery) {
                return $query
                    ->cte('cte', $cteQuery)
                    ->setModifiers(['MATERIALIZED'])
                    ->setRecursive(true);
            })
            ->select('col')
            ->from('cte');

        if ($this->connection->getDriver() instanceof Sqlserver) {
            $pattern = 'WITH cte AS MATERIALIZED \(SELECT 1 AS <col>\) SELECT <col> FROM <cte>';
        } else {
            $pattern = 'WITH RECURSIVE cte AS MATERIALIZED \(SELECT 1 AS <col>\) SELECT <col> FROM <cte>';
        }

        $this->assertQuotedQuery(
            $pattern,
            $query->sql(),
            !$this->autoQuote
        );
    }

    public function testTraverse(): void
    {
        $cte1 = new CommonTableExpression('cte1', $this->connection->newQuery()->select(1));
        $cte2 = new CommonTableExpression('cte2', $this->connection->newQuery()->select(1));
        $query = $this->connection
            ->newQuery()
            ->with($cte1)
            ->with($cte2)
            ->select('col')
            ->from('cte');

        $parts = [];
        $query->traverse(function ($part, $name) use (&$parts) {
            $parts[$name] = $part;
        });

        $this->assertArrayHasKey('with', $parts);
        $this->assertSame(['cte1' => $cte1, 'cte2' => $cte2], $parts['with']);
    }

    public function testClone(): void
    {
        $cte1Query = $this->connection->newQuery()->select(1);
        $cte1 = new CommonTableExpression('cte1', $cte1Query);

        $cte2Query = $this->connection->newQuery()->select(1);
        $cte2 = new CommonTableExpression('cte2', $cte2Query);

        $query = $this->connection
            ->newQuery()
            ->with($cte1)
            ->with($cte2)
            ->select('col')
            ->from('cte');

        $clone = clone $query;

        $with = $clone->clause('with');

        $this->assertCount(2, $with);

        $this->assertInstanceOf(CommonTableExpression::class, $with['cte1']);
        $this->assertNotSame($cte1, $with['cte1']);
        $this->assertEquals($cte1->getName(), $with['cte1']->getName());
        $this->assertNotSame($cte1Query, $with['cte1']->getQuery());
        $this->assertEquals('SELECT 1', $with['cte1']->getQuery()->sql(new ValueBinder()));

        $this->assertInstanceOf(CommonTableExpression::class, $with['cte2']);
        $this->assertNotSame($cte2, $with['cte2']);
        $this->assertEquals($cte2->getName(), $with['cte2']->getName());
        $this->assertNotSame($cte2Query, $with['cte2']->getQuery());
        $this->assertEquals('SELECT 1', $with['cte2']->getQuery()->sql(new ValueBinder()));
    }
}
