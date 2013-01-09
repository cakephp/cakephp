<?php
/**
 * CakeValidationRuleTest file
 *
 * PHP 5
 *
 * CakePHP(tm) Tests <http://book.cakephp.org/view/1196/Testing>
 * Copyright 2005-2012, Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice
 *
 * @copyright     Copyright 2005-2012, Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://book.cakephp.org/view/1196/Testing CakePHP(tm) Tests
 * @package       Cake.Test.Case.Model.Validator
 * @since         CakePHP(tm) v 2.2.0
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */

App::uses('CakeValidationRule', 'Model/Validator');

/**
 * CakeValidationRuleTest
 *
 * @package       Cake.Test.Case.Model.Validator
 */
class CakeValidationRuleTest extends CakeTestCase {

/**
 * Auxiliary method to test custom validators
 *
 * @return boolean
 **/
	public function myTestRule() {
		return false;
	}

/**
 * Auxiliary method to test custom validators
 *
 * @return boolean
 **/
	public function myTestRule2() {
		return true;
	}

/**
 * Auxiliary method to test custom validators
 *
 * @return string
 **/
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

		$Rule = new CakeValidationRule($def);
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

		$Rule = new CakeValidationRule($def);
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

		$Rule = new CakeValidationRule($def);
		$Rule->process('fieldName', $data, $methods);
	}

/**
 * Test isRequired method
 *
 * @return void
 */
	public function testIsRequired() {
		$def = array('rule' => 'notEmpty', 'required' => true);
		$Rule = new CakeValidationRule($def);
		$this->assertTrue($Rule->isRequired());

		$def = array('rule' => 'notEmpty', 'required' => false);
		$Rule = new CakeValidationRule($def);
		$this->assertFalse($Rule->isRequired());

		$def = array('rule' => 'notEmpty', 'required' => 'create');
		$Rule = new CakeValidationRule($def);
		$this->assertTrue($Rule->isRequired());

		$def = array('rule' => 'notEmpty', 'required' => 'update');
		$Rule = new CakeValidationRule($def);
		$this->assertFalse($Rule->isRequired());

		$Rule->isUpdate(true);
		$this->assertTrue($Rule->isRequired());
	}

/**
 * Test isEmptyAllowed method
 *
 * @return void
 */
	public function testIsEmptyAllowed() {
		$def = array('rule' => 'aRule', 'allowEmpty' => true);
		$Rule = new CakeValidationRule($def);
		$this->assertTrue($Rule->isEmptyAllowed());

		$def = array('rule' => 'aRule', 'allowEmpty' => false);
		$Rule = new CakeValidationRule($def);
		$this->assertFalse($Rule->isEmptyAllowed());

		$def = array('rule' => 'notEmpty', 'allowEmpty' => false, 'on' => 'update');
		$Rule = new CakeValidationRule($def);
		$this->assertTrue($Rule->isEmptyAllowed());

		$Rule->isUpdate(true);
		$this->assertFalse($Rule->isEmptyAllowed());

		$def = array('rule' => 'notEmpty', 'allowEmpty' => false, 'on' => 'create');
		$Rule = new CakeValidationRule($def);
		$this->assertFalse($Rule->isEmptyAllowed());

		$Rule->isUpdate(true);
		$this->assertTrue($Rule->isEmptyAllowed());
	}

/**
 * Test checkRequired method
 *
 * @return void
 */
	public function testCheckRequiredWhenRequiredAndAllowEmpty() {
		$Rule = $this->getMock('CakeValidationRule', array('isRequired'));
		$Rule->expects($this->any())
			->method('isRequired')
			->will($this->returnValue(true));
		$Rule->allowEmpty = true;

		$fieldname = 'field';
		$data = array(
			$fieldname => null
		);

		$this->assertFalse($Rule->checkRequired($fieldname, $data), "A null but present field should not fail requirement check if allowEmpty is true");

		$Rule->allowEmpty = false;

		$this->assertTrue($Rule->checkRequired($fieldname, $data), "A null but present field should fail requirement check if allowEmpty is false");
	}

}
