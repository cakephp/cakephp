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
 * @since         3.3.0
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Test\TestCase\Database;

use Cake\TestSuite\TestCase;
use Cake\Database\Type\StringType;
use Cake\Database\Type\ExpressionTypeInterface;
use Cake\Database\Expression\FunctionExpression;
use Cake\Database\ValueBinder;
use Cake\Database\Type;
use Cake\Database\Expression\Comparison;

class TestType extends StringType implements ExpressionTypeInterface
{

    public function toExpression($value)
    {
        return new FunctionExpression('CONCAT', [$value, ' - foo']);
    }
}

/**
 * Tests for Expression objects casting values to other expressions
 * using the type classes
 *
 */
class FunctionsBuilderTest extends TestCase
{

    /**
     * Setups a mock for FunctionsBuilder
     *
     * @return void
     */
    public function setUp()
    {
        parent::setUp();
        Type::map('test', new TestType);
    }

    public function testComparisonSimple()
    {
        $comparison = new Comparison('field', 'the thing', 'test', '=');
        $binder = new ValueBinder;
        $sql = $comparison->sql($binder);
        $this->assertEquals('field = (CONCAT(:c0, :c1))', $sql);
        $this->assertEquals('the thing', $binder->bindings()[':c0']['value']);
    }

    public function testComparisonMultiple()
    {
        $comparison = new Comparison('field', ['2', '3'], 'test[]', 'IN');
        $binder = new ValueBinder;
        $sql = $comparison->sql($binder);
        $this->assertEquals('field IN (CONCAT(:c0, :c1),CONCAT(:c2, :c3))', $sql);
        $this->assertEquals('2', $binder->bindings()[':c0']['value']);
        $this->assertEquals('3', $binder->bindings()[':c2']['value']);
    }

}
