<?php
/* SVN FILE: $Id: model.test.php 8225 2009-07-08 03:25:30Z mark_story $ */
/**
 * ModelValidationTest file
 *
 * Long description for file
 *
 * PHP versions 4 and 5
 *
 * CakePHP(tm) Tests <https://trac.cakephp.org/wiki/Developement/TestSuite>
 * Copyright 2005-2008, Cake Software Foundation, Inc. (http://www.cakefoundation.org)
 *
 *  Licensed under The Open Group Test Suite License
 *  Redistributions of files must retain the above copyright notice.
 *
 * @filesource
 * @copyright     Copyright 2005-2008, Cake Software Foundation, Inc. (http://www.cakefoundation.org)
 * @link          https://trac.cakephp.org/wiki/Developement/TestSuite CakePHP(tm) Tests
 * @package       cake
 * @subpackage    cake.tests.cases.libs.model
 * @since         CakePHP(tm) v 1.2.0.4206
 * @version       $Revision: 8225 $
 * @modifiedby    $LastChangedBy: mark_story $
 * @lastmodified  $Date: 2009-07-07 23:25:30 -0400 (Tue, 07 Jul 2009) $
 * @license       http://www.opensource.org/licenses/opengroup.php The Open Group Test Suite License
 */
require_once dirname(__FILE__) . DS . 'model.test.php';
require_once dirname(__FILE__) . DS . 'model_validation.test.php';
/**
 * ModelValidationTest
 *
 * @package       cake
 * @subpackage    cake.tests.cases.libs.model.operations
 */
class ModelValidationTest extends BaseModelTest {
/**
 * Tests validation parameter order in custom validation methods
 *
 * @access public
 * @return void
 */
	function testValidationParams() {
		$TestModel =& new ValidationTest1();
		$TestModel->validate['title'] = array(
			'rule' => 'customValidatorWithParams',
			'required' => true
		);
		$TestModel->create(array('title' => 'foo'));
		$TestModel->invalidFields();

		$expected = array(
			'data' => array(
				'title' => 'foo'
			),
			'validator' => array(
				'rule' => 'customValidatorWithParams',
				'on' => null,
				'last' => false,
				'allowEmpty' => false,
				'required' => true
			),
			'or' => true,
			'ignore_on_same' => 'id'
		);
		$this->assertEqual($TestModel->validatorParams, $expected);

		$TestModel->validate['title'] = array(
			'rule' => 'customValidatorWithMessage',
			'required' => true
		);
		$expected = array(
			'title' => 'This field will *never* validate! Muhahaha!'
		);

		$this->assertEqual($TestModel->invalidFields(), $expected);
	}
/**
 * Tests validation parameter fieldList in invalidFields
 *
 * @access public
 * @return void
 */
	function testInvalidFieldsWithFieldListParams() {
		$TestModel =& new ValidationTest1();
		$TestModel->validate = $validate = array(
			'title' => array(
				'rule' => 'customValidator',
				'required' => true
			),
			'name' => array(
				'rule' => 'allowEmpty',
				'required' => true
		));
		$TestModel->invalidFields(array('fieldList' => array('title')));
		$expected = array(
			'title' => 'This field cannot be left blank'
		);
		$this->assertEqual($TestModel->validationErrors, $expected);
		$TestModel->validationErrors = array();

		$TestModel->invalidFields(array('fieldList' => array('name')));
		$expected = array(
			'name' => 'This field cannot be left blank'
		);
		$this->assertEqual($TestModel->validationErrors, $expected);
		$TestModel->validationErrors = array();

		$TestModel->invalidFields(array('fieldList' => array('name', 'title')));
		$expected = array(
			'name' => 'This field cannot be left blank',
			'title' => 'This field cannot be left blank'
		);
		$this->assertEqual($TestModel->validationErrors, $expected);
		$TestModel->validationErrors = array();

		$TestModel->whitelist = array('name');
		$TestModel->invalidFields();
		$expected = array('name' => 'This field cannot be left blank');
		$this->assertEqual($TestModel->validationErrors, $expected);
		$TestModel->validationErrors = array();

		$this->assertEqual($TestModel->validate, $validate);
	}

}
?>