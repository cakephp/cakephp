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

use Cake\Database\Expression\DistinctComparisonExpression;
use Cake\Database\Expression\IdentifierExpression;
use Cake\Database\Expression\QueryExpression;
use Cake\Database\ValueBinder;
use Cake\TestSuite\TestCase;

class DistinctComparisonExpressionTest extends TestCase
{
    public function testSqlWithIdentifierValue(): void
    {
        $expr = new DistinctComparisonExpression(
            new IdentifierExpression('field'),
            new IdentifierExpression('other_field'),
            null,
            'IS DISTINCT FROM',
        );

        $this->assertEqualsSql('field IS DISTINCT FROM other_field', $expr->sql(new ValueBinder()));
    }

    public function testSqlWithExpressionValue(): void
    {
        $expr = new DistinctComparisonExpression(
            new IdentifierExpression('field'),
            new QueryExpression(['other_field' => 'value']),
            null,
            'IS DISTINCT FROM',
        );

        $this->assertEqualsSql('field IS DISTINCT FROM (other_field = :c0)', $expr->sql(new ValueBinder()));
    }

    public function testSqlWithNotFlag(): void
    {
        $expr = new DistinctComparisonExpression(
            new IdentifierExpression('field'),
            'value',
            null,
            'IS DISTINCT FROM',
        );
        $expr->setNot(true);

        $this->assertEqualsSql('NOT (field IS DISTINCT FROM :c0)', $expr->sql(new ValueBinder()));
    }
}
