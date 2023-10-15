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
 * @since         3.0.6
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Test\TestCase\Database\Expression;

use Cake\Database\Expression\QueryExpression;
use Cake\Database\ValueBinder;
use Cake\TestSuite\TestCase;

/**
 * Tests QueryExpression class
 */
class QueryExpressionTest extends TestCase
{
    /**
     * Test setConjunction()/getConjunction() works.
     */
    public function testConjunction(): void
    {
        $expr = new QueryExpression(['1', '2']);
        $binder = new ValueBinder();

        $this->assertSame($expr, $expr->setConjunction('+'));
        $this->assertSame('+', $expr->getConjunction());

        $result = $expr->sql($binder);
        $this->assertSame('(1 + 2)', $result);
    }

    /**
     * Test and() and or() calls work transparently
     */
    public function testAndOrCalls(): void
    {
        $expr = new QueryExpression();
        $expected = 'Cake\Database\Expression\QueryExpression';
        $this->assertInstanceOf($expected, $expr->and([]));
        $this->assertInstanceOf($expected, $expr->or([]));
    }

    /**
     * Test SQL generation with one element
     */
    public function testSqlGenerationOneClause(): void
    {
        $expr = new QueryExpression();
        $binder = new ValueBinder();
        $expr->add(['Users.username' => 'sally'], ['Users.username' => 'string']);

        $result = $expr->sql($binder);
        $this->assertSame('Users.username = :c0', $result);
    }

    /**
     * Test SQL generation with many elements
     */
    public function testSqlGenerationMultipleClauses(): void
    {
        $expr = new QueryExpression();
        $binder = new ValueBinder();
        $expr->add(
            [
                'Users.username' => 'sally',
                'Users.active' => 1,
            ],
            [
                'Users.username' => 'string',
                'Users.active' => 'boolean',
            ]
        );

        $result = $expr->sql($binder);
        $this->assertSame('(Users.username = :c0 AND Users.active = :c1)', $result);
    }

    /**
     * Test that empty expressions don't emit invalid SQL.
     */
    public function testSqlWhenEmpty(): void
    {
        $expr = new QueryExpression();
        $binder = new ValueBinder();
        $result = $expr->sql($binder);
        $this->assertSame('', $result);
    }

    /**
     * Test deep cloning of expression trees.
     */
    public function testDeepCloning(): void
    {
        $expr = new QueryExpression();
        $expr = $expr->add(new QueryExpression('1 + 1'))
            ->isNull('deleted')
            ->like('title', 'things%');

        $dupe = clone $expr;
        $this->assertEquals($dupe, $expr);
        $this->assertNotSame($dupe, $expr);
        $originalParts = [];
        $expr->iterateParts(function ($part) use (&$originalParts): void {
            $originalParts[] = $part;
        });
        $dupe->iterateParts(function ($part, $i) use ($originalParts): void {
            $this->assertNotSame($originalParts[$i], $part);
        });
    }

    /**
     * Tests the hasNestedExpression() function
     */
    public function testHasNestedExpression(): void
    {
        $expr = new QueryExpression();
        $this->assertFalse($expr->hasNestedExpression());

        $expr->add(['a' => 'b']);
        $this->assertTrue($expr->hasNestedExpression());

        $expr = new QueryExpression();
        $expr->add('a = b');
        $this->assertFalse($expr->hasNestedExpression());

        $expr->add(new QueryExpression('1 = 1'));
        $this->assertTrue($expr->hasNestedExpression());
    }

    /**
     * Returns the list of specific comparison methods
     *
     * @return void
     */
    public function methodsProvider(): array
    {
        return [
            ['eq'], ['notEq'], ['gt'], ['lt'], ['gte'], ['lte'], ['like'],
            ['notLike'], ['in'], ['notIn'],
        ];
    }

    /**
     * Tests that the query expression uses the type map when the
     * specific comparison functions are used.
     *
     * @dataProvider methodsProvider
     */
    public function testTypeMapUsage(string $method): void
    {
        $expr = new QueryExpression([], ['created' => 'date']);
        $expr->{$method}('created', 'foo');

        $binder = new ValueBinder();
        $expr->sql($binder);
        $bindings = $binder->bindings();
        $type = current($bindings)['type'];

        $this->assertSame('date', $type);
    }

    /**
     * Tests that creating query expressions with either the
     * array notation or using the combinators will produce a
     * zero-count expression object.
     *
     * @see https://github.com/cakephp/cakephp/issues/12081
     */
    public function testEmptyOr(): void
    {
        $expr = new QueryExpression();
        $expr = $expr->or([]);
        $expr = $expr->or([]);
        $this->assertCount(0, $expr);

        $expr = new QueryExpression(['OR' => []]);
        $this->assertCount(0, $expr);
    }

    /**
     * Tests that both conditions are generated for notInOrNull().
     */
    public function testNotInOrNull(): void
    {
        $expr = new QueryExpression();
        $expr->notInOrNull('test', ['one', 'two']);
        $this->assertEqualsSql(
            '(test NOT IN (:c0,:c1) OR (test) IS NULL)',
            $expr->sql(new ValueBinder())
        );
    }

    /**
     * Test deprecated adding of case statement.
     */
    public function testDeprecatedAddCaseStatement(): void
    {
        $this->deprecated(function () {
            (new QueryExpression())->addCase([]);
            $this->assertTrue(true);
        });
    }

    public function testCaseWithoutValue(): void
    {
        $expression = (new QueryExpression())
            ->case()
            ->when(1)
            ->then(2);

        $this->assertEqualsSql(
            'CASE WHEN :c0 THEN :c1 ELSE NULL END',
            $expression->sql(new ValueBinder())
        );
    }

    public function testCaseWithNullValue(): void
    {
        $expression = (new QueryExpression())
            ->case(null)
            ->when(1)
            ->then('Yes');

        $this->assertEqualsSql(
            'CASE NULL WHEN :c0 THEN :c1 ELSE NULL END',
            $expression->sql(new ValueBinder())
        );
    }

    public function testCaseWithValueAndType(): void
    {
        $expression = (new QueryExpression())
            ->case('1', 'integer')
            ->when(1)
            ->then('Yes');

        $valueBinder = new ValueBinder();

        $this->assertEqualsSql(
            'CASE :c0 WHEN :c1 THEN :c2 ELSE NULL END',
            $expression->sql($valueBinder)
        );

        $this->assertSame(
            [
                'value' => '1',
                'type' => 'integer',
                'placeholder' => 'c0',
            ],
            $valueBinder->bindings()[':c0']
        );
    }
}
