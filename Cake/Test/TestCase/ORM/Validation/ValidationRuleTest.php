<?php
/**
 * ValidationRuleTest file
 *
 * PHP 5
 *
 * CakePHP(tm) Tests <http://book.cakephp.org/view/1196/Testing>
 * Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://book.cakephp.org/view/1196/Testing CakePHP(tm) Tests
 * @since         CakePHP(tm) v 2.2.0
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Test\TestCase\ORM\Validaton;

use Cake\ORM\Validation\ValidationRule;
use Cake\TestSuite\TestCase;

/**
 * ValidationRuleTest
 *
 */
class ValidationRuleTest extends TestCase {

/**
 * Auxiliary method to test custom validators
 *
 * @return boolean
 */
	public function myTestRule() {
		return false;
	}

/**
 * Auxiliary method to test custom validators
 *
 * @return boolean
 */
	public function myTestRule2() {
		return true;
	}

/**
 * Auxiliary method to test custom validators
 *
 * @return string
 */
	public function myTestRule3() {
		return 'string';
	}

/**
 * tests that passing custom validation methods work
 *
 * @return void
 */
	public function testCustomMethods() {
		$data = 'some data';
		$scopes = ['default' => $this];

		$Rule = new ValidationRule(['rule' => 'myTestRule']);
		$this->assertFalse($Rule->process($data, $scopes, true));

		$Rule = new ValidationRule(['rule' => 'myTestRule2']);
		$this->assertTrue($Rule->process($data, $scopes, true));

		$Rule = new ValidationRule(['rule' => 'myTestRule3']);
		$this->assertEquals('string', $Rule->process($data, $scopes, true));

		$Rule = new ValidationRule(['rule' => 'myTestRule', 'message' => 'foo']);
		$this->assertEquals('foo', $Rule->process($data, $scopes, true));
	}

/**
 * Make sure errors are triggered when validation is missing.
 *
 * @expectedException \InvalidArgumentException
 * @expectedExceptionMessage Invalid validation callable
 * @return void
 */
	public function testCustomMethodMissingError() {
		$def = ['rule' => ['totallyMissing']];
		$data = 'some data';
		$scopes = ['default' => $this];

		$Rule = new ValidationRule($def);
		$Rule->process($data, $scopes, true);
	}

}
