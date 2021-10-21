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

use Cake\Database\Driver\Sqlite;
use Cake\Database\Driver\Sqlserver;
use Cake\Database\Expression\TupleComparison;
use Cake\TestSuite\TestCase;
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

    public function testTransformWithInvalidOperator(): void
    {
        $articles = $this->getTableLocator()->get('Articles');

        $driver = $articles->getConnection()->getDriver();
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

        $articles
            ->find()
            ->select(['Articles.id', 'Articles.author_id'])
            ->where([
                new TupleComparison(
                    ['Articles.id', 'Articles.author_id'],
                    $articles
                        ->subquery()
                        ->select(['ArticlesAlias.id', 'ArticlesAlias.author_id'])
                        ->from(['ArticlesAlias' => $articles->getTable()])
                        ->where(['ArticlesAlias.author_id' => 1]),
                    [],
                    'NOT IN'
                ),
            ])
            ->orderAsc('Articles.id')
            ->disableHydration()
            ->toArray();
    }
}
