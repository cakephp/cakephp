<?php
/**
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The Open Group Test Suite License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @since         3.0.0
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Test\TestCase\Database\Expression;

use Cake\Database\Expression\CaseExpression;
use Cake\Database\Expression\QueryExpression;
use Cake\Database\ValueBinder;
use Cake\TestSuite\TestCase;

/**
 * Tests CaseExpression class
 */
class CaseExpressionTest extends TestCase
{

    /**
     * Test that the sql output works correctly
     *
     * @return void
     */
    public function testSqlOutput()
    {
        $expr = new QueryExpression();
        $expr->eq('test', 'true');
        $expr2 = new QueryExpression();
        $expr2->eq('test2', 'false');

        $caseExpression = new CaseExpression($expr, 'foobar');
        $expected = 'CASE WHEN test = :c0 THEN :c1 END';
        $this->assertSame($expected, $caseExpression->sql(new ValueBinder()));

        $caseExpression->add($expr2);
        $expected = 'CASE WHEN test = :c0 THEN :c1 WHEN test2 = :c2 THEN :c3 END';
        $this->assertSame($expected, $caseExpression->sql(new ValueBinder()));

        $caseExpression = new CaseExpression([$expr], ['foobar', 'else']);
        $expected = 'CASE WHEN test = :c0 THEN :c1 ELSE :c2 END';
        $this->assertSame($expected, $caseExpression->sql(new ValueBinder()));

        $caseExpression = new CaseExpression([$expr], ['foobar' => 'literal', 'else']);
        $expected = 'CASE WHEN test = :c0 THEN foobar ELSE :c1 END';
        $this->assertSame($expected, $caseExpression->sql(new ValueBinder()));
    }

    /**
     * Tests that the expression is correctly traversed
     *
     * @return void
     */
    public function testTraverse()
    {
        $count = 0;
        $visitor = function () use (&$count) {
            $count++;
        };

        $expr = new QueryExpression();
        $expr->eq('test', 'true');
        $expr2 = new QueryExpression();
        $expr2->eq('test', 'false');
        $caseExpression = new CaseExpression([$expr, $expr2]);
        $caseExpression->traverse($visitor);
        $this->assertSame(4, $count);
    }
}
