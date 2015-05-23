<?php
/**
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @since         3.1.0
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Test\TestCase\Database\Expression;

use Cake\Database\Expression\CaseExpression;
use Cake\Database\Expression\QueryExpression;
use Cake\Database\ValueBinder;
use Cake\TestSuite\TestCase;

/**
 * Tests QueryExpression class
 */
class QueryExpressionTest extends TestCase
{

    /**
     * Test and() and or() calls work transparently
     *
     * @return void
     */
    public function testAndOrCalls()
    {
        $expr = new QueryExpression();
        $expected = '\Cake\Database\Expression\QueryExpression';
        $this->assertInstanceOf($expected, $expr->and([]));
        $this->assertInstanceOf($expected, $expr->or([]));
    }
}
