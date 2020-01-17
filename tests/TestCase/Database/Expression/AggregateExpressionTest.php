<?php
declare(strict_types=1);

/**
 * CakePHP(tm) : Rapid Development Framework (https://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 *
 * Licensed under The Open Group Test Suite License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 * @link          https://cakephp.org CakePHP(tm) Project
 * @since         4.1.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Test\TestCase\Database\Expression;

use Cake\Database\Expression\AggregateExpression;
use Cake\Database\ValueBinder;

/**
 * Tests FunctionExpression class
 */
class AggregateExpressionTest extends FunctionExpressionTest
{
    /**
     * @var string The expression class to test with
     */
    protected $expressionClass = AggregateExpression::class;

    /**
     * Tests annotating an aggregate with an empty window expression
     *
     * @return void
     */
    public function testEmptyWindow()
    {
        $f = (new AggregateExpression('MyFunction'))->over();
        $this->assertSame('MyFunction() OVER ()', $f->sql(new ValueBinder()));
    }
}
