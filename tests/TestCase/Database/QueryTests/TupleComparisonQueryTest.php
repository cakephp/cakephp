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
 * @since         4.3.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */

namespace Cake\Test\TestCase\Database\QueryTests;

use Cake\Database\Driver\Mysql;
use Cake\Database\Driver\Postgres;
use Cake\Database\Driver\Sqlite;
use Cake\Database\Driver\Sqlserver;
use Cake\Database\Expression\TupleComparison;
use Cake\Database\Query;
use Cake\Database\StatementInterface;
use Cake\Database\TypeMap;
use Cake\Datasource\ConnectionManager;
use Cake\TestSuite\TestCase;
use PDOException;
use RuntimeException;

/**
 * Tuple comparison query tests.
 *
 * These tests are specifically relevant in the context of Sqlite and
 * Sqlserver, for which the tuple comparison will be transformed when
 * composite fields are used.
 *
 * @see \Cake\Database\Driver\TupleComparisonTranslatorTrait::_transformTupleComparison()
 */
class TupleComparisonQueryTest extends TestCase
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
     * @var \Cake\Database\Query
     */
    protected $query;

    public function setUp(): void
    {
        parent::setUp();

        $this->connection = ConnectionManager::get('test');
        $this->query = new Query($this->connection);
    }

    public function tearDown(): void
    {
        parent::tearDown();

        unset($this->query);
        unset($this->connection);
    }

    public function testTransformWithInvalidOperator(): void
    {
        $driver = $this->connection->getDriver();
        if (
            $driver instanceof Sqlite ||
            $driver instanceof Sqlserver
        ) {
            $this->expectException(RuntimeException::class);
            $this->expectExceptionMessage(
                'Tuple comparison transform only supports the `IN` and `=` operators, `NOT IN` given.'
            );
        } else {
            $this->markTestSkipped('Tuple comparisons are only being transformed for Sqlite and Sqlserver.');
        }

        $this->query
            ->select(['articles.id', 'articles.author_id'])
            ->from('articles')
            ->where([
                new TupleComparison(
                    ['articles.id', 'articles.author_id'],
                    (new Query($this->connection))
                        ->select(['ArticlesAlias.id', 'ArticlesAlias.author_id'])
                        ->from(['ArticlesAlias' => 'articles'])
                        ->where(['ArticlesAlias.author_id' => 1]),
                    [],
                    'NOT IN'
                ),
            ])
            ->orderAsc('articles.id')
            ->execute();
    }

    public function testInWithMultiResultSubquery(): void
    {
        $typeMap = new TypeMap([
            'id' => 'integer',
            'author_id' => 'integer',
        ]);

        $query = $this->query
            ->select(['articles.id', 'articles.author_id'])
            ->from('articles')
            ->where([
                new TupleComparison(
                    ['articles.id', 'articles.author_id'],
                    (new Query($this->connection))
                        ->select(['ArticlesAlias.id', 'ArticlesAlias.author_id'])
                        ->from(['ArticlesAlias' => 'articles'])
                        ->where(['ArticlesAlias.author_id' => 1]),
                    [],
                    'IN'
                ),
            ])
            ->orderAsc('articles.id')
            ->setSelectTypeMap($typeMap);

        $expected = [
            [
                'id' => 1,
                'author_id' => 1,
            ],
            [
                'id' => 3,
                'author_id' => 1,
            ],
        ];
        $this->assertSame($expected, $query->execute()->fetchAll(StatementInterface::FETCH_TYPE_ASSOC));
    }

    public function testInWithSingleResultSubquery(): void
    {
        $typeMap = new TypeMap([
            'id' => 'integer',
            'author_id' => 'integer',
        ]);

        $query = $this->query
            ->select(['articles.id', 'articles.author_id'])
            ->from('articles')
            ->where([
                new TupleComparison(
                    ['articles.id', 'articles.author_id'],
                    (new Query($this->connection))
                        ->select(['ArticlesAlias.id', 'ArticlesAlias.author_id'])
                        ->from(['ArticlesAlias' => 'articles'])
                        ->where(['ArticlesAlias.id' => 1]),
                    [],
                    'IN'
                ),
            ])
            ->setSelectTypeMap($typeMap);

        $expected = [
            [
                'id' => 1,
                'author_id' => 1,
            ],
        ];
        $this->assertSame($expected, $query->execute()->fetchAll(StatementInterface::FETCH_TYPE_ASSOC));
    }

    public function testInWithMultiArrayValues(): void
    {
        $typeMap = new TypeMap([
            'id' => 'integer',
            'author_id' => 'integer',
        ]);

        $query = $this->query
            ->select(['articles.id', 'articles.author_id'])
            ->from('articles')
            ->where([
                new TupleComparison(
                    ['articles.id', 'articles.author_id'],
                    [[1, 1], [3, 1]],
                    ['integer', 'integer'],
                    'IN'
                ),
            ])
            ->orderAsc('articles.id')
            ->setSelectTypeMap($typeMap);

        $expected = [
            [
                'id' => 1,
                'author_id' => 1,
            ],
            [
                'id' => 3,
                'author_id' => 1,
            ],
        ];
        $this->assertSame($expected, $query->execute()->fetchAll(StatementInterface::FETCH_TYPE_ASSOC));
    }

    public function testEqualWithMultiResultSubquery(): void
    {
        $driver = $this->connection->getDriver();
        if (
            $driver instanceof Mysql ||
            $driver instanceof Postgres
        ) {
            $this->expectException(PDOException::class);
            $this->expectExceptionMessageMatches('/cardinality violation/i');
        } else {
            // Due to the way tuple comparisons are being translated, the DBMS will
            // not run into a cardinality violation scenario.
            $this->markTestSkipped(
                'Sqlite and Sqlserver currently do not fail with subqueries returning incompatible results.'
            );
        }

        $this->query
            ->select(['articles.id', 'articles.author_id'])
            ->from('articles')
            ->where([
                new TupleComparison(
                    ['articles.id', 'articles.author_id'],
                    (new Query($this->connection))
                        ->select(['ArticlesAlias.id', 'ArticlesAlias.author_id'])
                        ->from(['ArticlesAlias' => 'articles'])
                        ->where(['ArticlesAlias.author_id' => 1]),
                    [],
                    '='
                ),
            ])
            ->orderAsc('articles.id')
            ->execute();
    }

    public function testEqualWithSingleResultSubquery(): void
    {
        $typeMap = new TypeMap([
            'id' => 'integer',
            'author_id' => 'integer',
        ]);

        $query = $this->query
            ->select(['articles.id', 'articles.author_id'])
            ->from('articles')
            ->where([
                new TupleComparison(
                    ['articles.id', 'articles.author_id'],
                    (new Query($this->connection))
                        ->select(['ArticlesAlias.id', 'ArticlesAlias.author_id'])
                        ->from(['ArticlesAlias' => 'articles'])
                        ->where(['ArticlesAlias.id' => 1]),
                    [],
                    '='
                ),
            ])
            ->setSelectTypeMap($typeMap);

        $expected = [
            [
                'id' => 1,
                'author_id' => 1,
            ],
        ];
        $this->assertSame($expected, $query->execute()->fetchAll(StatementInterface::FETCH_TYPE_ASSOC));
    }

    public function testEqualWithSingleArrayValue(): void
    {
        $typeMap = new TypeMap([
            'id' => 'integer',
            'author_id' => 'integer',
        ]);

        $query = $this->query
            ->select(['articles.id', 'articles.author_id'])
            ->from('articles')
            ->where([
                new TupleComparison(
                    ['articles.id', 'articles.author_id'],
                    [1, 1],
                    ['integer', 'integer'],
                    '='
                ),
            ])
            ->orderAsc('articles.id')
            ->setSelectTypeMap($typeMap);

        $expected = [
            [
                'id' => 1,
                'author_id' => 1,
            ],
        ];
        $this->assertSame($expected, $query->execute()->fetchAll(StatementInterface::FETCH_TYPE_ASSOC));
    }
}
