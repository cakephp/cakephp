<?php
/**
 *
 * PHP Version 5.4
 *
 * CakePHP(tm) Tests <http://book.cakephp.org/2.0/en/development/testing.html>
 * Copyright 2005-2012, Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The Open Group Test Suite License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright 2005-2013, Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://book.cakephp.org/2.0/en/development/testing.html CakePHP(tm) Tests
 * @package       Cake.Test.Case.Model.Datasource.Database
 * @since         CakePHP(tm) v 3.0
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
namespace Cake\Test\TestCase\Database;

use Cake\Database\FunctionsTrait;

/**
 * Tests FunctionsTrait class
 *
 **/
class FunctionsTraitTest extends \Cake\TestSuite\TestCase {

/**
 * Setups a mock for FunctionsTrait
 *
 * @return void
 */
	public function setUp() {
		parent::setUp();
		$this->functions = $this->getObjectForTrait(
			'\Cake\Database\FunctionsTrait'
		);
	}

/**
 * Tests generating a generic function call
 *
 * @return void
 */
	public function testFunc() {
		$function = $this->functions->func('MyFunc', ['b' => 'literal']);
		$this->assertInstanceOf('\Cake\Database\Expression\FunctionExpression', $function);
		$this->assertEquals('MyFunc', $function->name());
		$this->assertEquals('MyFunc(b)', (string)$function);
	}

/**
 * Tests generating a SUM() function
 *
 * @return void
 */
	public function testSum() {
		$function = $this->functions->sum('total');
		$this->assertInstanceOf('\Cake\Database\Expression\FunctionExpression', $function);
		$this->assertEquals('SUM(total)', (string)$function);
	}

/**
 * Tests generating a AVG() function
 *
 * @return void
 */
	public function testAvg() {
		$function = $this->functions->avg('salary');
		$this->assertInstanceOf('\Cake\Database\Expression\FunctionExpression', $function);
		$this->assertEquals('AVG(salary)', (string)$function);
	}

/**
 * Tests generating a MAX() function
 *
 * @return void
 */
	public function testMAX() {
		$function = $this->functions->max('created');
		$this->assertInstanceOf('\Cake\Database\Expression\FunctionExpression', $function);
		$this->assertEquals('MAX(created)', (string)$function);
	}

/**
 * Tests generating a MIN() function
 *
 * @return void
 */
	public function testMin() {
		$function = $this->functions->min('created');
		$this->assertInstanceOf('\Cake\Database\Expression\FunctionExpression', $function);
		$this->assertEquals('MIN(created)', (string)$function);
	}

/**
 * Tests generating a COUNT() function
 *
 * @return void
 */
	public function testCount() {
		$function = $this->functions->count('*');
		$this->assertInstanceOf('\Cake\Database\Expression\FunctionExpression', $function);
		$this->assertEquals('COUNT(*)', (string)$function);
	}

/**
 * Tests generating a CONCAT() function
 *
 * @return void
 */
	public function testConcat() {
		$function = $this->functions->concat(['title' => 'literal', ' is a string']);
		$this->assertInstanceOf('\Cake\Database\Expression\FunctionExpression', $function);
		$param = $function->bindings()[1]['placeholder'];
		$this->assertEquals("CONCAT(title, :$param)", (string)$function);
	}

/**
 * Tests generating a COALESCE() function
 *
 * @return void
 */
	public function testCoalesce() {
		$function = $this->functions->coalesce(['NULL' => 'literal', '1', '2']);
		$this->assertInstanceOf('\Cake\Database\Expression\FunctionExpression', $function);
		$param = $function->bindings()[1]['placeholder'];
		$param2 = $function->bindings()[2]['placeholder'];
		$this->assertEquals("COALESCE(NULL, :$param, :$param2)", (string)$function);
	}

/**
 * Tests generating a NOW(), CURRENT_TIME() and CURRENT_DATE() function
 *
 * @return void
 */
	public function testNow() {
		$function = $this->functions->now();
		$this->assertInstanceOf('\Cake\Database\Expression\FunctionExpression', $function);
		$this->assertEquals("NOW()", (string)$function);

		$function = $this->functions->now('date');
		$this->assertInstanceOf('\Cake\Database\Expression\FunctionExpression', $function);
		$this->assertEquals("CURRENT_DATE()", (string)$function);

		$function = $this->functions->now('time');
		$this->assertInstanceOf('\Cake\Database\Expression\FunctionExpression', $function);
		$this->assertEquals("CURRENT_TIME()", (string)$function);
	}
}
