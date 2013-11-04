<?php
/**
 * PHP Version 5.4
 *
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @since         CakePHP(tm) v 3.0.0
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
namespace Cake\Test\TestCase\Database\Expression;

use Cake\Database\Expression\FieldExpression;
use Cake\Database\ValueBinder;
use Cake\TestSuite\TestCase;

/**
 * Tests FieldExpression class
 *
 **/
class FieldExpressionTest extends TestCase {

/**
 * Tests getting and setting the field
 *
 * @return 
 */
	public function testGetAndSet() {
		$expression = new FieldExpression('foo');
		$this->assertEquals('foo', $expression->getField());
		$expression->setField('bar');
		$this->assertEquals('bar', $expression->getField());
	}

/**
 * Tests converting to sql
 *
 * @return void
 */
	public function testSQL() {
		$expression = new FieldExpression('foo');
		$this->assertEquals('foo', $expression->sql(new ValueBinder));
	}

}
