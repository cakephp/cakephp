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
use Cake\Database\ValueBinder;
use Cake\TestSuite\TestCase;

/**
 * Tests BetweenExpression class
 */
class BetweenExpressionTest extends TestCase
{
    /**
     * Tests that BETWEEN expression is generated correctly
     */
    public function testBetween(): void
    {
        $expr = new BetweenExpression('age', 18, 65);
        $this->assertEqualsSql(
            'age BETWEEN :c0 AND :c1',
            $expr->sql(new ValueBinder()),
        );
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
     * Tests BETWEEN with an identifier expression as field
     */
    public function testBetweenWithIdentifierField(): void
    {
        $expr = new BetweenExpression(
            new IdentifierExpression('Users.age'),
            18,
            65,
        );
        $this->assertEqualsSql(
            'Users.age BETWEEN :c0 AND :c1',
            $expr->sql(new ValueBinder()),
        );
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
}
