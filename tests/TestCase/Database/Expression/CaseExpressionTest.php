<?php
/**
 * Project: hesa-mbit.
 * User: walther
 * Date: 2014/08/08
 * Time: 9:37 AM
 */

namespace Cake\Test\TestCase\Database\Expression;

use Cake\Database\Expression\QueryExpression;
use Cake\Database\ValueBinder;
use Cake\TestSuite\TestCase;
use Cake\Database\Expression\CaseExpression;

/**
 * Tests CaseExpression class
 */
class CaseExpressionTest extends TestCase {

/**
 * Test that the sql output works correctly
 *
 * @return void
 */
	public function testSqlOutput() {
		$expr = new QueryExpression();
		$expr->eq('test', 'true');
		$caseExpression = new CaseExpression($expr);

		$this->assertInstanceOf('Cake\Database\ExpressionInterface', $caseExpression);
		$expected = 'CASE WHEN test = :c0 THEN 1 ELSE 0 END';
		$this->assertSame($expected, $caseExpression->sql(new ValueBinder()));
	}

/**
 * Test that we can pass in things as the isTrue/isFalse part
 *
 * @return void
 */
	public function testSetTrue() {
		$expr = new QueryExpression();
		$expr->eq('test', 'true');
		$caseExpression = new CaseExpression($expr);
		$expr2 = new QueryExpression();

		$caseExpression->isTrue($expr2);
		$this->assertSame($expr2, $caseExpression->isTrue());

		$caseExpression->isTrue('test_string');
		$this->assertSame(['value' => 'test_string', 'type' => null], $caseExpression->isTrue());

		$caseExpression->isTrue(['test_string' => 'literal']);
		$this->assertSame('test_string', $caseExpression->isTrue());
	}

/**
 * Test that things are compiled correctly
 *
 * @return void
 */
	public function testSqlCompiler() {
		$expr = new QueryExpression();
		$expr->eq('test', 'true');
		$caseExpression = new CaseExpression($expr);
		$expr2 = new QueryExpression();
		$expr2->eq('test', 'false');

		$caseExpression->isTrue($expr2);
		$this->assertSame('CASE WHEN test = :c0 THEN test = :c1 ELSE 0 END', $caseExpression->sql(new ValueBinder()));

		$caseExpression->isTrue('test_string');
		$this->assertSame('CASE WHEN test = :c0 THEN :c1 ELSE 0 END', $caseExpression->sql(new ValueBinder()));

		$caseExpression->isTrue(['test_string' => 'literal']);
		$this->assertSame('CASE WHEN test = :c0 THEN test_string ELSE 0 END', $caseExpression->sql(new ValueBinder()));
	}

/**
 * Tests that the expression is correctly traversed
 *
 * @return void
 */
	public function testTraverse() {
		$count = 0;
		$visitor = function() use (&$count) {
			$count++;
		};

		$expr = new QueryExpression();
		$expr->eq('test', 'true');
		$caseExpression = new CaseExpression($expr);
		$caseExpression->traverse($visitor);
		$this->assertSame(2, $count);
	}
}