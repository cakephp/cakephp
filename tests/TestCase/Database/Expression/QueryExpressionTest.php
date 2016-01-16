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
 * @since         3.0.6
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
        $this->assertEquals('Users.username = :c0', $result);
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
                'Users.active' => 'boolean'
            ]
        );

        $result = $expr->sql($binder);
        $this->assertEquals('(Users.username = :c0 AND Users.active = :c1)', $result);
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
        $this->assertEquals('', $result);
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
}
