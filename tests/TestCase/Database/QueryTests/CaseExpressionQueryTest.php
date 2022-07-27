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

use Cake\Database\Driver\Postgres;
use Cake\Database\Expression\QueryExpression;
use Cake\Database\Query;
use Cake\Database\StatementInterface;
use Cake\Database\TypeMap;
use Cake\Datasource\ConnectionManager;
use Cake\Test\Fixture\ArticlesFixture;
use Cake\Test\Fixture\CommentsFixture;
use Cake\Test\Fixture\ProductsFixture;
use Cake\TestSuite\TestCase;

class CaseExpressionQueryTest extends TestCase
{
    protected $fixtures = [
        ArticlesFixture::class,
        CommentsFixture::class,
        ProductsFixture::class,
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

    public function testSimpleCase(): void
    {
        $query = $this->query
            ->select(function (Query $query) {
                return [
                    'name',
                    'category_name' => $query->newExpr()
                        ->case($query->identifier('products.category'))
                        ->when(1)
                        ->then('Touring')
                        ->when(2)
                        ->then('Urban')
                        ->else('Other'),
                ];
            })
            ->from('products')
            ->orderAsc('category')
            ->orderAsc('name');

        $expected = [
            [
                'name' => 'First product',
                'category_name' => 'Touring',
            ],
            [
                'name' => 'Second product',
                'category_name' => 'Urban',
            ],
            [
                'name' => 'Third product',
                'category_name' => 'Other',
            ],
        ];
        $this->assertSame($expected, $query->execute()->fetchAll(StatementInterface::FETCH_TYPE_ASSOC));
    }

    public function testSearchedCase(): void
    {
        $typeMap = new TypeMap([
            'price' => 'integer',
        ]);

        $query = $this->query
            ->select(function (Query $query) {
                return [
                    'name',
                    'price',
                    'price_range' => $query->newExpr()
                        ->case()
                        ->when(['price <' => 20])
                        ->then('Under $20')
                        ->when(['price >=' => 20, 'price <' => 30])
                        ->then('Under $30')
                        ->else('$30 and above'),
                ];
            })
            ->from('products')
            ->orderAsc('price')
            ->orderAsc('name')
            ->setSelectTypeMap($typeMap);

        $expected = [
            [
                'name' => 'First product',
                'price' => 10,
                'price_range' => 'Under $20',
            ],
            [
                'name' => 'Second product',
                'price' => 20,
                'price_range' => 'Under $30',
            ],
            [
                'name' => 'Third product',
                'price' => 30,
                'price_range' => '$30 and above',
            ],
        ];
        $this->assertSame($expected, $query->execute()->fetchAll(StatementInterface::FETCH_TYPE_ASSOC));
    }

    public function testOrderByCase(): void
    {
        $typeMap = new TypeMap([
            'article_id' => 'integer',
            'user_id' => 'integer',
        ]);

        $query = $this->query
            ->select(['article_id', 'user_id'])
            ->from('comments')
            ->orderAsc('comments.article_id')
            ->orderDesc(function (QueryExpression $exp, Query $query) {
                return $query->newExpr()
                    ->case($query->identifier('comments.article_id'))
                    ->when(1)
                    ->then($query->identifier('comments.user_id'));
            })
            ->orderAsc(function (QueryExpression $exp, Query $query) {
                return $query->newExpr()
                    ->case($query->identifier('comments.article_id'))
                    ->when(2)
                    ->then($query->identifier('comments.user_id'));
            })
            ->setSelectTypeMap($typeMap);

        $expected = [
            [
                'article_id' => 1,
                'user_id' => 4,
            ],
            [
                'article_id' => 1,
                'user_id' => 2,
            ],
            [
                'article_id' => 1,
                'user_id' => 1,
            ],
            [
                'article_id' => 1,
                'user_id' => 1,
            ],
            [
                'article_id' => 2,
                'user_id' => 1,
            ],
            [
                'article_id' => 2,
                'user_id' => 2,
            ],
        ];
        $this->assertSame($expected, $query->execute()->fetchAll(StatementInterface::FETCH_TYPE_ASSOC));
    }

    public function testHavingByCase(): void
    {
        $query = $this->query
            ->select(['articles.title'])
            ->from('articles')
            ->leftJoin('comments', ['comments.article_id = articles.id'])
            ->group(['articles.id', 'articles.title'])
            ->having(function (QueryExpression $exp, Query $query) {
                $expression = $query->newExpr()
                    ->case()
                    ->when(['comments.published' => 'Y'])
                    ->then(1);

                if ($query->getConnection()->getDriver() instanceof Postgres) {
                    $expression = $query->func()->cast($expression, 'integer');
                }

                return $exp->gt(
                    $query->func()->sum($expression),
                    2,
                    'integer'
                );
            });

        $expected = [
            [
                'title' => 'First Article',
            ],
        ];
        $this->assertSame($expected, $query->execute()->fetchAll(StatementInterface::FETCH_TYPE_ASSOC));
    }

    public function testUpdateFromCase(): void
    {
        $query = $this->query
            ->select(['count' => $this->query->func()->count('*')])
            ->from('comments')
            ->where(['comments.published' => 'Y']);

        $this->assertSame(5, (int)$query->execute()->fetch()[0]);

        $query->where(['comments.published' => 'N'], [], true);

        $this->assertSame(1, (int)$query->execute()->fetch()[0]);

        $query = (new Query($this->connection))
            ->update('comments')
            ->set([
                'published' =>
                    $this->query->newExpr()
                        ->case()
                        ->when(['published' => 'Y'])
                        ->then('N')
                        ->else('Y'),
            ])
            ->where(['1 = 1'])
            ->execute();

        $query = (new Query($this->connection))
            ->select(['count' => $this->query->func()->count('*')])
            ->from('comments')
            ->where(['comments.published' => 'Y']);

        $this->assertSame(1, (int)$query->execute()->fetch()[0]);

        $query->where(['comments.published' => 'N'], [], true);
        $this->assertSame(5, (int)$query->execute()->fetch()[0]);
    }

    public function bindingValueDataProvider(): array
    {
        return [
            ['1', 3],
            ['2', 4],
        ];
    }

    /**
     * @dataProvider bindingValueDataProvider
     * @param string $when The `WHEN` value.
     * @param int $result The result value.
     */
    public function testBindValues(string $when, int $result): void
    {
        $value = '1';
        $then = '3';
        $else = '4';

        $typeMap = new TypeMap([
            'val' => 'integer',
        ]);

        $query = $this->query
            ->select(function (Query $query) {
                return [
                    'val' => $query->newExpr()
                        ->case($query->newExpr(':value'))
                        ->when($query->newExpr(':when'))
                        ->then($query->newExpr(':then'))
                        ->else($query->newExpr(':else')),
                ];
            })
            ->from('products')
            ->bind(':value', $value, 'integer')
            ->bind(':when', $when, 'integer')
            ->bind(':then', $then, 'integer')
            ->bind(':else', $else, 'integer')
            ->setSelectTypeMap($typeMap);

        $expected = [
            'val' => $result,
        ];
        $this->assertSame($expected, $query->execute()->fetch(StatementInterface::FETCH_TYPE_ASSOC));
    }
}
