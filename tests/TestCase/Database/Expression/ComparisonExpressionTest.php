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
use Cake\Database\Expression\QueryExpression;
use Cake\TestSuite\TestCase;

/**
 * Tests Comparison class
 */
class ComparisonExpressionTest extends TestCase
{
    /**
     * Tests that cloning Comparion instance clones it's value and field expressions.
     *
     * @return void
     */
    public function testClone()
    {
        $exp = new ComparisonExpression(new QueryExpression('field1'), 1, 'integer', '<');
        $exp2 = clone $exp;

        $this->assertNotSame($exp->getField(), $exp2->getField());
    }
}
