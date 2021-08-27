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
 * @since         3.7.5
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Test\TestCase\Database\Expression;

use Cake\Database\Expression\ComparisonExpression;
use Cake\Database\Expression\IdentifierExpression;
use Cake\Database\Expression\QueryExpression;
use Cake\Database\ValueBinder;
use Cake\TestSuite\TestCase;

/**
 * Tests Comparison class
 */
class ComparisonExpressionTest extends TestCase
{
    /**
     * Test sql generation using IdentifierExpression
     */
    public function testIdentifiers(): void
    {
        $expr = new ComparisonExpression('field', new IdentifierExpression('other_field'));
        $this->assertEqualsSql('field = other_field', $expr->sql(new ValueBinder()));

        $expr = new ComparisonExpression(new IdentifierExpression('field'), new IdentifierExpression('other_field'));
        $this->assertEqualsSql('field = other_field', $expr->sql(new ValueBinder()));

        $expr = new ComparisonExpression(new IdentifierExpression('field'), new QueryExpression(['other_field']));
        $this->assertEqualsSql('field = (other_field)', $expr->sql(new ValueBinder()));

        $expr = new ComparisonExpression(new IdentifierExpression('field'), 'value');
        $this->assertEqualsSql('field = :c0', $expr->sql(new ValueBinder()));

        $expr = new ComparisonExpression(new QueryExpression(['field']), new IdentifierExpression('other_field'));
        $this->assertEqualsSql('field = other_field', $expr->sql(new ValueBinder()));
    }

    /**
     * Tests that cloning Comparion instance clones it's value and field expressions.
     */
    public function testClone(): void
    {
        $exp = new ComparisonExpression(new QueryExpression('field1'), 1, 'integer', '<');
        $exp2 = clone $exp;

        $this->assertNotSame($exp->getField(), $exp2->getField());
    }
}
