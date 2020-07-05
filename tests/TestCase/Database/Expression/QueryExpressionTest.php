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
     *
     * @return void
     */
    public function testConjunction()
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
     *
     * @return void
     */
    public function testAndOrCalls()
    {
        $expr = new QueryExpression();
        $expected = 'Cake\Database\Expression\QueryExpression';
        $this->assertInstanceOf($expected, $expr->and([]));
        $this->assertInstanceOf($expected, $expr->or([]));
    }

    /**
     * Test SQL generation with one element
     *
     * @return void
     */
    public function testSqlGenerationOneClause()
    {
        $expr = new QueryExpression();
        $binder = new ValueBinder();
        $expr->add(['Users.username' => 'sally'], ['Users.username' => 'string']);

        $result = $expr->sql($binder);
        $this->assertSame('Users.username = :c0', $result);
    }

    /**
     * Test SQL generation with many elements
     *
     * @return void
     */
    public function testSqlGenerationMultipleClauses()
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
     *
     * @return void
     */
    public function testSqlWhenEmpty()
    {
        $expr = new QueryExpression();
        $binder = new ValueBinder();
        $result = $expr->sql($binder);
        $this->assertSame('', $result);
    }

    /**
     * Test deep cloning of expression trees.
     *
     * @return void
     */
    public function testDeepCloning()
    {
        $expr = new QueryExpression();
        $expr = $expr->add(new QueryExpression('1 + 1'))
            ->isNull('deleted')
            ->like('title', 'things%');

        $dupe = clone $expr;
        $this->assertEquals($dupe, $expr);
        $this->assertNotSame($dupe, $expr);
        $originalParts = [];
        $expr->iterateParts(function ($part) use (&$originalParts) {
            $originalParts[] = $part;
        });
        $dupe->iterateParts(function ($part, $i) use ($originalParts) {
            $this->assertNotSame($originalParts[$i], $part);
        });
    }

    /**
     * Tests the hasNestedExpression() function
     *
     * @return void
     */
    public function testHasNestedExpression()
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
    public function methodsProvider()
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
     * @return void
     */
    public function testTypeMapUsage($method)
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
     * @return void
     */
    public function testEmptyOr()
    {
        $expr = new QueryExpression();
        $expr = $expr->or([]);
        $expr = $expr->or([]);
        $this->assertCount(0, $expr);

        $expr = new QueryExpression(['OR' => []]);
        $this->assertCount(0, $expr);
    }
}
