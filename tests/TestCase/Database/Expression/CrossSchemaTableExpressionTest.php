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
 * @since         3.3.0
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Test\TestCase\Database\Expression;

use Cake\Database\Expression\CrossSchemaTableExpression;
use Cake\Database\Expression\IdentifierExpression;
use Cake\Database\ValueBinder;
use Cake\TestSuite\TestCase;

/**
 * Tests CrossSchemaTableExpression class
 */
class CrossSchemaTableExpressionTest extends TestCase
{

    /**
     * Test sql method with ExpressionInterfaces passed and without
     */
    public function testSql()
    {
        $expression = new CrossSchemaTableExpression(
            new IdentifierExpression('schema'),
            new IdentifierExpression('table')
        );

        $this->assertEquals('schema.table', $expression->sql(new ValueBinder()));

        $expression = new CrossSchemaTableExpression('schema', 'table');

        $this->assertEquals('schema.table', $expression->sql(new ValueBinder()));
    }

    /**
     * Test traverse method with ExpressionInterfaces passed and without
     */
    public function testTraverse()
    {
        $expressions = [];

        $collector = function ($e) use (&$expressions) {
            $expressions[] = $e;
        };

        $expression = new CrossSchemaTableExpression(
            new IdentifierExpression('schema'),
            new IdentifierExpression('table')
        );
        $expression->traverse($collector);
        $this->assertEquals([
            new IdentifierExpression('schema'),
            new IdentifierExpression('table')
        ], $expressions);

        $expressions = [];
        $expression = new CrossSchemaTableExpression('schema', 'table');
        $expression->traverse($collector);
        $this->assertEquals([], $expressions);
    }
}
