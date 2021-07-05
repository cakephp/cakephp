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
namespace Cake\Test\TestCase\Database\Expression;

use Cake\Database\Expression\CommonTableExpression;
use Cake\Database\Expression\IdentifierExpression;
use Cake\Database\ValueBinder;
use Cake\Datasource\ConnectionManager;
use Cake\TestSuite\TestCase;

class CommonTableExpressionTest extends TestCase
{
    /**
     * @var \Cake\Database\Connection
     */
    protected $connection;

    public function setUp(): void
    {
        parent::setUp();
        $this->connection = ConnectionManager::get('test');
    }

    public function tearDown(): void
    {
        parent::tearDown();
        unset($this->connection);
    }

    /**
     * Tests constructing CommonTableExpressions.
     */
    public function testCteConstructor(): void
    {
        $cte = new CommonTableExpression('test', $this->connection->newQuery());
        $this->assertEqualsSql('test AS ()', $cte->sql(new ValueBinder()));

        $cte = (new CommonTableExpression())
            ->name('test')
            ->query($this->connection->newQuery());
        $this->assertEqualsSql('test AS ()', $cte->sql(new ValueBinder()));
    }

    /**
     * Tests setting fields.
     */
    public function testFields(): void
    {
        $cte = (new CommonTableExpression('test', $this->connection->newQuery()))
            ->field('col1')
            ->field([new IdentifierExpression('col2')]);
        $this->assertEqualsSql('test(col1, col2) AS ()', $cte->sql(new ValueBinder()));
    }

    /**
     * Tests setting CTE materialized
     */
    public function testMaterialized(): void
    {
        $cte = (new CommonTableExpression('test', $this->connection->newQuery()))
            ->materialized();
        $this->assertEqualsSql('test AS MATERIALIZED ()', $cte->sql(new ValueBinder()));

        $cte->notMaterialized();
        $this->assertEqualsSql('test AS NOT MATERIALIZED ()', $cte->sql(new ValueBinder()));
    }

    /**
     * Tests setting CTE as recursive.
     */
    public function testRecursive(): void
    {
        $cte = (new CommonTableExpression('test', $this->connection->newQuery()))
            ->recursive();
        $this->assertTrue($cte->isRecursive());
    }

    /**
     * Tests setting query using closures.
     */
    public function testQueryClosures(): void
    {
        $cte = new CommonTableExpression('test', function () {
            return $this->connection->newQuery();
        });
        $this->assertEqualsSql('test AS ()', $cte->sql(new ValueBinder()));

        $cte->query(function () {
            return $this->connection->newQuery()->select('1');
        });
        $this->assertEqualsSql('test AS (SELECT 1)', $cte->sql(new ValueBinder()));
    }

    /**
     * Tests traversing CommonTableExpression.
     */
    public function testTraverse(): void
    {
        $query = $this->connection->newQuery()->select('1');
        $cte = (new CommonTableExpression('test', $query))->field('field');

        $expressions = [];
        $cte->traverse(function ($expression) use (&$expressions): void {
            $expressions[] = $expression;
        });

        $this->assertEquals(new IdentifierExpression('test'), $expressions[0]);
        $this->assertEquals(new IdentifierExpression('field'), $expressions[1]);
        $this->assertEquals($query, $expressions[2]);
    }

    /**
     * Tests cloning CommonTableExpression
     */
    public function testClone(): void
    {
        $cte = new CommonTableExpression('test', function () {
            return $this->connection->newQuery()->select('1');
        });
        $cte2 = (clone $cte)->name('test2');
        $this->assertNotSame($cte->sql(new ValueBinder()), $cte2->sql(new ValueBinder()));

        $cte2 = (clone $cte)->field('col1');
        $this->assertNotSame($cte->sql(new ValueBinder()), $cte2->sql(new ValueBinder()));
    }
}
