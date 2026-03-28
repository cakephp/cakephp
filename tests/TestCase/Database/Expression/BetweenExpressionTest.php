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
 * @since         5.4.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Test\TestCase\Database\Expression;

use Cake\Database\Expression\BetweenExpression;
use Cake\Database\Expression\IdentifierExpression;
use Cake\Database\Expression\QueryExpression;
use Cake\Database\ValueBinder;
use Cake\TestSuite\TestCase;

/**
 * Tests BetweenExpression class
 */
class BetweenExpressionTest extends TestCase
{
    /**
     * Tests basic BETWEEN SQL generation
     */
    public function testSimpleBetween(): void
    {
        $expr = new BetweenExpression('field', 1, 10, 'integer');
        $binder = new ValueBinder();
        $this->assertSame('field BETWEEN :c0 AND :c1', $expr->sql($binder));
        $this->assertSame(1, $binder->bindings()[':c0']['value']);
        $this->assertSame(10, $binder->bindings()[':c1']['value']);
        $this->assertSame('integer', $binder->bindings()[':c0']['type']);
        $this->assertSame('integer', $binder->bindings()[':c1']['type']);
    }

    /**
     * Tests that return type is set to boolean
     */
    public function testReturnType(): void
    {
        $expr = new BetweenExpression('field', 1, 10, 'integer');
        $this->assertSame('boolean', $expr->getReturnType());
    }

    /**
     * Tests that NOT BETWEEN expression is generated correctly
     */
    public function testNotBetween(): void
    {
        $expr = new BetweenExpression('age', 18, 65, null, true);
        $this->assertEqualsSql(
            'age NOT BETWEEN :c0 AND :c1',
            $expr->sql(new ValueBinder()),
        );
    }

    /**
     * Tests BETWEEN with identifier expression
     */
    public function testBetweenWithIdentifier(): void
    {
        $field = new IdentifierExpression('Users.age');
        $expr = new BetweenExpression($field, 18, 65, 'integer');
        $binder = new ValueBinder();
        $this->assertSame('Users.age BETWEEN :c0 AND :c1', $expr->sql($binder));
    }

    /**
     * Tests NOT BETWEEN with an identifier expression as field
     */
    public function testNotBetweenWithIdentifierField(): void
    {
        $expr = new BetweenExpression(
            new IdentifierExpression('Users.age'),
            18,
            65,
            null,
            true,
        );
        $this->assertEqualsSql(
            'Users.age NOT BETWEEN :c0 AND :c1',
            $expr->sql(new ValueBinder()),
        );
    }

    /**
     * Tests BETWEEN with expression values
     */
    public function testBetweenWithExpressionValues(): void
    {
        $from = new QueryExpression('MIN(age)');
        $to = new QueryExpression('MAX(age)');
        $expr = new BetweenExpression('value', $from, $to);
        $binder = new ValueBinder();
        $this->assertSame('value BETWEEN MIN(age) AND MAX(age)', $expr->sql($binder));
    }

    /**
     * Tests that values are properly bound with type
     */
    public function testBetweenWithType(): void
    {
        $expr = new BetweenExpression('score', 10, 100, 'integer');
        $binder = new ValueBinder();
        $expr->sql($binder);

        $bindings = $binder->bindings();
        $this->assertSame('integer', $bindings[':c0']['type']);
        $this->assertSame('integer', $bindings[':c1']['type']);
    }

    /**
     * Tests traverse functionality
     */
    public function testTraverse(): void
    {
        $field = new IdentifierExpression('field');
        $from = new QueryExpression('1');
        $to = new QueryExpression('10');
        $expr = new BetweenExpression($field, $from, $to);

        $expressions = [];
        $expr->traverse(function ($exp) use (&$expressions): void {
            $expressions[] = $exp;
        });

        $this->assertCount(3, $expressions);
        $this->assertSame($field, $expressions[0]);
        $this->assertSame($from, $expressions[1]);
        $this->assertSame($to, $expressions[2]);
    }

    /**
     * Tests that cloning creates deep copies of expression parts
     */
    public function testClone(): void
    {
        $field = new IdentifierExpression('field');
        $from = new QueryExpression('start');
        $to = new QueryExpression('end');
        $expr = new BetweenExpression($field, $from, $to);

        $cloned = clone $expr;

        $this->assertNotSame($expr->getField(), $cloned->getField());
        $this->assertEquals($expr->getField(), $cloned->getField());
    }
}
