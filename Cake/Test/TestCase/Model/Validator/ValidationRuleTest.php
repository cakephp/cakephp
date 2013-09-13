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
 * @package       Cake.Test.Case.Model.Validator
 * @since         CakePHP(tm) v 2.2.0
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Test\TestCase\Model\Validator;

use Cake\Model\Validator\ValidationRule;
use Cake\TestSuite\TestCase;

/**
 * ValidationRuleTest
 *
 * @package       Cake.Test.TestCase.Model.Validator
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
 * Test isValid method
 *
 * @return void
 */
	public function testIsValid() {
		$def = array('rule' => 'notEmpty', 'message' => 'Can not be empty');
		$data = array(
			'fieldName' => ''
		);
		$methods = array();

		$Rule = new ValidationRule($def);
		$Rule->process('fieldName', $data, $methods);
		$this->assertFalse($Rule->isValid());

		$data = array('fieldName' => 'not empty');
		$Rule->process('fieldName', $data, $methods);
		$this->assertTrue($Rule->isValid());
	}

/**
 * tests that passing custom validation methods work
 *
 * @return void
 */
	public function testCustomMethods() {
		$def = array('rule' => 'myTestRule');
		$data = array(
			'fieldName' => 'some data'
		);
		$methods = array('mytestrule' => array($this, 'myTestRule'));

		$Rule = new ValidationRule($def);
		$Rule->process('fieldName', $data, $methods);
		$this->assertFalse($Rule->isValid());

		$methods = array('mytestrule' => array($this, 'myTestRule2'));
		$Rule->process('fieldName', $data, $methods);
		$this->assertTrue($Rule->isValid());

		$methods = array('mytestrule' => array($this, 'myTestRule3'));
		$Rule->process('fieldName', $data, $methods);
		$this->assertFalse($Rule->isValid());
	}

/**
 * Make sure errors are triggered when validation is missing.
 *
 * @expectedException PHPUnit_Framework_Error_Warning
 * @expectedExceptionMessage Could not find validation handler totallyMissing for fieldName
 * @return void
 */
	public function testCustomMethodMissingError() {
		$def = array('rule' => array('totallyMissing'));
		$data = array(
			'fieldName' => 'some data'
		);
		$methods = array('mytestrule' => array($this, 'myTestRule'));

		$Rule = new ValidationRule($def);
		$Rule->process('fieldName', $data, $methods);
	}

}
