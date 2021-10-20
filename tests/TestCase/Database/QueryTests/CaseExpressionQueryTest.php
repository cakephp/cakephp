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
use Cake\ORM\Query;
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

    public function testSimpleCase(): void
    {
        $query = $this->getTableLocator()->get('Products')
            ->find()
            ->select(function (Query $query) {
                return [
                    'name',
                    'category_name' => $query->newExpr()
                        ->case($query->identifier('Products.category'))
                        ->when(1)
                        ->then('Touring')
                        ->when(2)
                        ->then('Urban')
                        ->else('Other'),
                ];
            })
            ->orderAsc('category')
            ->orderAsc('name')
            ->disableHydration();

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
        $this->assertSame($expected, $query->toArray());
    }

    public function testSearchedCase(): void
    {
        $query = $this->getTableLocator()->get('Products')
            ->find()
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
            ->orderAsc('price')
            ->orderAsc('name')
            ->disableHydration();

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
        $this->assertSame($expected, $query->toArray());
    }

    public function testOrderByCase(): void
    {
        $query = $this->getTableLocator()->get('Comments')
            ->find()
            ->select(['article_id', 'user_id'])
            ->orderAsc('Comments.article_id')
            ->orderDesc(function (QueryExpression $exp, Query $query) {
                return $query->newExpr()
                    ->case($query->identifier('Comments.article_id'))
                    ->when(1)
                    ->then($query->identifier('Comments.user_id'));
            })
            ->orderAsc(function (QueryExpression $exp, Query $query) {
                return $query->newExpr()
                    ->case($query->identifier('Comments.article_id'))
                    ->when(2)
                    ->then($query->identifier('Comments.user_id'));
            })
            ->disableHydration();

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
        $this->assertSame($expected, $query->toArray());
    }

    public function testHavingByCase(): void
    {
        $articlesTable = $this->getTableLocator()->get('Articles');
        $articlesTable->hasMany('Comments');

        $query = $articlesTable
            ->find()
            ->select(['Articles.title'])
            ->leftJoinWith('Comments')
            ->group(['Articles.id', 'Articles.title'])
            ->having(function (QueryExpression $exp, Query $query) {
                $expression = $query->newExpr()
                    ->case()
                    ->when(['Comments.published' => 'Y'])
                    ->then(1);

                if ($query->getConnection()->getDriver() instanceof Postgres) {
                    $expression = $query->func()->cast($expression, 'integer');
                }

                return $exp->gt(
                    $query->func()->sum($expression),
                    2,
                    'integer'
                );
            })
            ->disableHydration();

        $expected = [
            [
                'title' => 'First Article',
            ],
        ];
        $this->assertSame($expected, $query->toArray());
    }

    public function testUpdateFromCase(): void
    {
        $commentsTable = $this->getTableLocator()->get('Comments');

        $this->assertSame(5, $commentsTable->find()->where(['Comments.published' => 'Y'])->count());
        $this->assertSame(1, $commentsTable->find()->where(['Comments.published' => 'N'])->count());

        $commentsTable->updateAll(
            [
                'published' =>
                    $commentsTable->query()->newExpr()
                        ->case()
                        ->when(['published' => 'Y'])
                        ->then('N')
                        ->else('Y'),
            ],
            '1 = 1'
        );

        $this->assertSame(1, $commentsTable->find()->where(['Comments.published' => 'Y'])->count());
        $this->assertSame(5, $commentsTable->find()->where(['Comments.published' => 'N'])->count());
    }

    public function testInferredReturnType(): void
    {
        $query = $this->getTableLocator()->get('Products')
            ->find()
            ->select(function (Query $query) {
                $expression = $query->newExpr()
                    ->case()
                    ->when(['Products.price <' => 20])
                    ->then(true)
                    ->else(false);

                if ($query->getConnection()->getDriver() instanceof Postgres) {
                    $expression = $query->func()->cast($expression, 'boolean');
                }

                return [
                    'Products.name',
                    'Products.price',
                    'is_cheap' => $expression,
                ];
            })
            ->disableHydration();

        $expected = [
            [
                'name' => 'First product',
                'price' => 10,
                'is_cheap' => true,
            ],
            [
                'name' => 'Second product',
                'price' => 20,
                'is_cheap' => false,
            ],
            [
                'name' => 'Third product',
                'price' => 30,
                'is_cheap' => false,
            ],
        ];
        $this->assertSame($expected, $query->toArray());
    }

    public function testOverwrittenReturnType(): void
    {
        $query = $this->getTableLocator()->get('Products')
            ->find()
            ->select(function (Query $query) {
                return [
                    'name',
                    'price',
                    'is_cheap' => $query->newExpr()
                        ->case()
                        ->when(['price <' => 20])
                        ->then(1)
                        ->else(0)
                        ->setReturnType('boolean'),
                ];
            })
            ->orderAsc('price')
            ->orderAsc('name')
            ->disableHydration();

        $expected = [
            [
                'name' => 'First product',
                'price' => 10,
                'is_cheap' => true,
            ],
            [
                'name' => 'Second product',
                'price' => 20,
                'is_cheap' => false,
            ],
            [
                'name' => 'Third product',
                'price' => 30,
                'is_cheap' => false,
            ],
        ];
        $this->assertSame($expected, $query->toArray());
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

        $query = $this->getTableLocator()->get('Products')
            ->find()
            ->select(function (Query $query) {
                return [
                    'val' => $query->newExpr()
                        ->case($query->newExpr(':value'))
                        ->when($query->newExpr(':when'))
                        ->then($query->newExpr(':then'))
                        ->else($query->newExpr(':else'))
                        ->setReturnType('integer'),
                ];
            })
            ->bind(':value', $value, 'integer')
            ->bind(':when', $when, 'integer')
            ->bind(':then', $then, 'integer')
            ->bind(':else', $else, 'integer')
            ->disableHydration();

        $expected = [
            'val' => $result,
        ];
        $this->assertSame($expected, $query->first());
    }
}
