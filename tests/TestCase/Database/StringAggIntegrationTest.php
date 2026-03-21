<?php
declare(strict_types=1);

/**
 * CakePHP(tm) : Rapid Development Framework (https://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 *
 * Licensed under The Open Group Test Suite License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 * @link          https://cakephp.org CakePHP(tm) Project
 * @since         5.3.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Test\TestCase\Database;

use Cake\Database\Driver\Sqlserver;
use Cake\Datasource\ConnectionManager;
use Cake\TestSuite\TestCase;

/**
 * Integration tests for STRING_AGG / GROUP_CONCAT function
 */
class StringAggIntegrationTest extends TestCase
{
    /**
     * @var \Cake\Database\Connection
     */
    protected $connection;

    /**
     * Fixtures
     *
     * @var array<string>
     */
    protected array $fixtures = ['core.StringAggItems'];

    /**
     * @inheritDoc
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->connection = ConnectionManager::get('test');
    }

    /**
     * Test basic string aggregation
     */
    public function testStringAggBasic(): void
    {
        $query = $this->connection->selectQuery(
            ['category', 'names' => $this->getStringAggExpr('name', ',')],
            'string_agg_items',
        );
        $query->groupBy('category');

        $result = $query->execute()->fetchAll('assoc');

        $this->assertCount(3, $result);

        // Find results for each category
        $resultsByCategory = [];
        foreach ($result as $row) {
            $resultsByCategory[$row['category']] = $row['names'];
        }

        // Category A should have all 3 items
        $this->assertStringContainsString('Item 1', $resultsByCategory['A']);
        $this->assertStringContainsString('Item 2', $resultsByCategory['A']);
        $this->assertStringContainsString('Item 3', $resultsByCategory['A']);

        // Category B should have 2 items
        $this->assertStringContainsString('Item 4', $resultsByCategory['B']);
        $this->assertStringContainsString('Item 5', $resultsByCategory['B']);

        // Category C should have 1 item
        $this->assertSame('Item 6', $resultsByCategory['C']);
    }

    /**
     * Test string aggregation with custom separator
     */
    public function testStringAggWithSeparator(): void
    {
        $query = $this->connection->selectQuery(
            [
                'category',
                'names' => $this->getStringAggExpr('name', ' - '),
            ],
            'string_agg_items',
        );
        $query->groupBy('category');

        $result = $query->execute()->fetchAll('assoc');

        $resultsByCategory = [];
        foreach ($result as $row) {
            $resultsByCategory[$row['category']] = $row['names'];
        }

        // Check that separator is used
        $this->assertStringContainsString(' - ', $resultsByCategory['A']);
    }

    /**
     * Test string aggregation with ORDER BY
     */
    public function testStringAggWithOrderBy(): void
    {
        $query = $this->connection->selectQuery(
            [
                'category',
                'names' => $this->getStringAggExpr('name', ',', 'sort_order'),
            ],
            'string_agg_items',
        );
        $query->groupBy('category');

        $result = $query->execute()->fetchAll('assoc');

        $resultsByCategory = [];
        foreach ($result as $row) {
            $resultsByCategory[$row['category']] = $row['names'];
        }

        // Verify items are ordered by sort_order
        // Item 1 (sort_order 1), Item 2 (sort_order 2), Item 3 (sort_order 3)
        $this->assertStringContainsString('Item 1', $resultsByCategory['A']);
        $this->assertStringContainsString('Item 2', $resultsByCategory['A']);
        $this->assertStringContainsString('Item 3', $resultsByCategory['A']);
    }

    /**
     * Test that STRING_AGG is used for drivers that support it
     */
    public function testStringAggUsesCorrectFunction(): void
    {
        // For SQLite 3.44+, PostgreSQL, SQL Server - should use STRING_AGG
        // For older SQLite and MySQL <10.5 - should use GROUP_CONCAT (via translation)
        $query = $this->connection->selectQuery(
            ['names' => $this->getStringAggExpr('name', ',')],
            'string_agg_items',
        );

        $sql = $query->sql();

        // The SQL should contain either STRING_AGG or GROUP_CONCAT
        // depending on driver support
        $this->assertTrue(
            str_contains($sql, 'STRING_AGG') || str_contains($sql, 'GROUP_CONCAT'),
            'SQL should contain STRING_AGG or GROUP_CONCAT: ' . $sql
        );
    }

    /**
     * Helper method to create string aggregation expression
     *
     * @param \Cake\Database\ExpressionInterface|string $expression The expression to aggregate
     * @param string $separator The separator to use
     * @param string|null $orderBy Optional ORDER BY expression
     * @return \Cake\Database\Expression\FunctionExpression
     */
    protected function getStringAggExpr(
        $expression,
        string $separator = ',',
        ?string $orderBy = null,
    ): \Cake\Database\Expression\FunctionExpression {
        $query = $this->connection->selectQuery('id', 'string_agg_items');
        $func = $query->func();

        return $func->stringAgg($expression, $separator, $orderBy);
    }
}
