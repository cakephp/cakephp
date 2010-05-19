<?php
/* SVN FILE: $Id: model.test.php 8225 2009-07-08 03:25:30Z mark_story $ */

/**
 * ModelValidationTest file
 *
 * PHP versions 4 and 5
 *
 * CakePHP(tm) Tests <http://book.cakephp.org/view/1196/Testing>
 * Copyright 2005-2010, Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 *  Licensed under The Open Group Test Suite License
 *  Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright 2005-2010, Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://book.cakephp.org/view/1196/Testing CakePHP(tm) Tests
 * @package       cake
 * @subpackage    cake.tests.cases.libs.model
 * @since         CakePHP(tm) v 1.2.0.4206
 * @license       http://www.opensource.org/licenses/opengroup.php The Open Group Test Suite License
 */
require_once dirname(__FILE__) . DS . 'model.test.php';

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

		$TestModel->validate['title'] = array(
			'rule' => array('customValidatorWithSixParams', 'one', 'two', null, 'four'),
			'required' => true
		);
		$TestModel->create(array('title' => 'foo'));
		$TestModel->invalidFields();
		$expected = array(
			'data' => array(
				'title' => 'foo'
			),
			'one' => 'one',
			'two' => 'two',
			'three' => null,
			'four' => 'four',
			'five' => array(
				'rule' => array(1 => 'one', 2 => 'two', 3 => null, 4 => 'four'),
				'on' => null,
				'last' => false,
				'allowEmpty' => false,
				'required' => true
			),
			'six' => 6
		);
		$this->assertEqual($TestModel->validatorParams, $expected);

		$TestModel->validate['title'] = array(
			'rule' => array('customValidatorWithSixParams', 'one', array('two'), null, 'four', array('five' => 5)),
			'required' => true
		);
		$TestModel->create(array('title' => 'foo'));
		$TestModel->invalidFields();
		$expected = array(
			'data' => array(
				'title' => 'foo'
			),
			'one' => 'one',
			'two' => array('two'),
			'three' => null,
			'four' => 'four',
			'five' => array('five' => 5),
			'six' => array(
				'rule' => array(1 => 'one', 2 => array('two'), 3 => null, 4 => 'four', 5 => array('five' => 5)),
				'on' => null,
				'last' => false,
				'allowEmpty' => false,
				'required' => true
			)
		);
		$this->assertEqual($TestModel->validatorParams, $expected);
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

/**
 * testValidates method
 *
 * @access public
 * @return void
 */
	function testValidates() {
		$TestModel =& new TestValidate();

		$TestModel->validate = array(
			'user_id' => 'numeric',
			'title' => array('allowEmpty' => false, 'rule' => 'notEmpty'),
			'body' => 'notEmpty'
		);

		$data = array('TestValidate' => array(
			'user_id' => '1',
			'title' => '',
			'body' => 'body'
		));
		$result = $TestModel->create($data);
		$this->assertTrue($result);
		$result = $TestModel->validates();
		$this->assertFalse($result);

		$data = array('TestValidate' => array(
			'user_id' => '1',
			'title' => 'title',
			'body' => 'body'
		));
		$result = $TestModel->create($data) && $TestModel->validates();
		$this->assertTrue($result);

		$data = array('TestValidate' => array(
			'user_id' => '1',
			'title' => '0',
			'body' => 'body'
		));
		$result = $TestModel->create($data);
		$this->assertTrue($result);
		$result = $TestModel->validates();
		$this->assertTrue($result);

		$data = array('TestValidate' => array(
			'user_id' => '1',
			'title' => 0,
			'body' => 'body'
		));
		$result = $TestModel->create($data);
		$this->assertTrue($result);
		$result = $TestModel->validates();
		$this->assertTrue($result);

		$TestModel->validate['modified'] = array('allowEmpty' => true, 'rule' => 'date');

		$data = array('TestValidate' => array(
			'user_id' => '1',
			'title' => 0,
			'body' => 'body',
			'modified' => ''
		));
		$result = $TestModel->create($data);
		$this->assertTrue($result);
		$result = $TestModel->validates();
		$this->assertTrue($result);

		$data = array('TestValidate' => array(
			'user_id' => '1',
			'title' => 0,
			'body' => 'body',
			'modified' => '2007-05-01'
		));
		$result = $TestModel->create($data);
		$this->assertTrue($result);
		$result = $TestModel->validates();
		$this->assertTrue($result);

		$data = array('TestValidate' => array(
			'user_id' => '1',
			'title' => 0,
			'body' => 'body',
			'modified' => 'invalid-date-here'
		));
		$result = $TestModel->create($data);
		$this->assertTrue($result);
		$result = $TestModel->validates();
		$this->assertFalse($result);

		$data = array('TestValidate' => array(
			'user_id' => '1',
			'title' => 0,
			'body' => 'body',
			'modified' => 0
		));
		$result = $TestModel->create($data);
		$this->assertTrue($result);
		$result = $TestModel->validates();
		$this->assertFalse($result);

		$data = array('TestValidate' => array(
			'user_id' => '1',
			'title' => 0,
			'body' => 'body',
			'modified' => '0'
		));
		$result = $TestModel->create($data);
		$this->assertTrue($result);
		$result = $TestModel->validates();
		$this->assertFalse($result);

		$TestModel->validate['modified'] = array('allowEmpty' => false, 'rule' => 'date');

		$data = array('TestValidate' => array('modified' => null));
		$result = $TestModel->create($data);
		$this->assertTrue($result);
		$result = $TestModel->validates();
		$this->assertFalse($result);

		$data = array('TestValidate' => array('modified' => false));
		$result = $TestModel->create($data);
		$this->assertTrue($result);
		$result = $TestModel->validates();
		$this->assertFalse($result);

		$data = array('TestValidate' => array('modified' => ''));
		$result = $TestModel->create($data);
		$this->assertTrue($result);
		$result = $TestModel->validates();
		$this->assertFalse($result);

		$data = array('TestValidate' => array(
			'modified' => '2007-05-01'
		));
		$result = $TestModel->create($data);
		$this->assertTrue($result);
		$result = $TestModel->validates();
		$this->assertTrue($result);

		$TestModel->validate['slug'] = array('allowEmpty' => false, 'rule' => array('maxLength', 45));

		$data = array('TestValidate' => array(
			'user_id' => '1',
			'title' => 0,
			'body' => 'body',
			'slug' => ''
		));
		$result = $TestModel->create($data);
		$this->assertTrue($result);
		$result = $TestModel->validates();
		$this->assertFalse($result);

		$data = array('TestValidate' => array(
			'user_id' => '1',
			'title' => 0,
			'body' => 'body',
			'slug' => 'slug-right-here'
		));
		$result = $TestModel->create($data);
		$this->assertTrue($result);
		$result = $TestModel->validates();
		$this->assertTrue($result);

		$data = array('TestValidate' => array(
			'user_id' => '1',
			'title' => 0,
			'body' => 'body',
			'slug' => 'abcdefghijklmnopqrstuvwxyzabcdefghijklmnopqrstuvwxyz'
		));
		$result = $TestModel->create($data);
		$this->assertTrue($result);
		$result = $TestModel->validates();
		$this->assertFalse($result);

		$TestModel->validate = array(
			'number' => array(
				'rule' => 'validateNumber',
				'min' => 3,
				'max' => 5
			),
			'title' => array(
				'allowEmpty' => false,
				'rule' => 'notEmpty'
		));

		$data = array('TestValidate' => array(
			'title' => 'title',
			'number' => '0'
		));
		$result = $TestModel->create($data);
		$this->assertTrue($result);
		$result = $TestModel->validates();
		$this->assertFalse($result);

		$data = array('TestValidate' => array(
			'title' => 'title',
			'number' => 0
		));
		$result = $TestModel->create($data);
		$this->assertTrue($result);
		$result = $TestModel->validates();
		$this->assertFalse($result);

		$data = array('TestValidate' => array(
			'title' => 'title',
			'number' => '3'
		));
		$result = $TestModel->create($data);
		$this->assertTrue($result);
		$result = $TestModel->validates();
		$this->assertTrue($result);

		$data = array('TestValidate' => array(
			'title' => 'title',
			'number' => 3
		));
		$result = $TestModel->create($data);
		$this->assertTrue($result);
		$result = $TestModel->validates();
		$this->assertTrue($result);

		$TestModel->validate = array(
			'number' => array(
				'rule' => 'validateNumber',
				'min' => 5,
				'max' => 10
			),
			'title' => array(
				'allowEmpty' => false,
				'rule' => 'notEmpty'
		));

		$data = array('TestValidate' => array(
			'title' => 'title',
			'number' => '3'
		));
		$result = $TestModel->create($data);
		$this->assertTrue($result);
		$result = $TestModel->validates();
		$this->assertFalse($result);

		$data = array('TestValidate' => array(
			'title' => 'title',
			'number' => 3
		));
		$result = $TestModel->create($data);
		$this->assertTrue($result);
		$result = $TestModel->validates();
		$this->assertFalse($result);

		$TestModel->validate = array(
			'title' => array(
				'allowEmpty' => false,
				'rule' => 'validateTitle'
		));

		$data = array('TestValidate' => array('title' => ''));
		$result = $TestModel->create($data);
		$this->assertTrue($result);
		$result = $TestModel->validates();
		$this->assertFalse($result);

		$data = array('TestValidate' => array('title' => 'new title'));
		$result = $TestModel->create($data);
		$this->assertTrue($result);
		$result = $TestModel->validates();
		$this->assertFalse($result);

		$data = array('TestValidate' => array('title' => 'title-new'));
		$result = $TestModel->create($data);
		$this->assertTrue($result);
		$result = $TestModel->validates();
		$this->assertTrue($result);

		$TestModel->validate = array('title' => array(
			'allowEmpty' => true,
			'rule' => 'validateTitle'
		));
		$data = array('TestValidate' => array('title' => ''));
		$result = $TestModel->create($data);
		$this->assertTrue($result);
		$result = $TestModel->validates();
		$this->assertTrue($result);

		$TestModel->validate = array(
			'title' => array(
				'length' => array(
					'allowEmpty' => true,
					'rule' => array('maxLength', 10)
		)));
		$data = array('TestValidate' => array('title' => ''));
		$result = $TestModel->create($data);
		$this->assertTrue($result);
		$result = $TestModel->validates();
		$this->assertTrue($result);

		$TestModel->validate = array(
			'title' => array(
				'rule' => array('userDefined', 'Article', 'titleDuplicate')
		));
		$data = array('TestValidate' => array('title' => 'My Article Title'));
		$result = $TestModel->create($data);
		$this->assertTrue($result);
		$result = $TestModel->validates();
		$this->assertFalse($result);

		$data = array('TestValidate' => array(
			'title' => 'My Article With a Different Title'
		));
		$result = $TestModel->create($data);
		$this->assertTrue($result);
		$result = $TestModel->validates();
		$this->assertTrue($result);

		$TestModel->validate = array(
			'title' => array(
				'tooShort' => array('rule' => array('minLength', 50)),
				'onlyLetters' => array('rule' => '/^[a-z]+$/i')
			),
		);
		$data = array('TestValidate' => array(
			'title' => 'I am a short string'
		));
		$TestModel->create($data);
		$result = $TestModel->validates();
		$this->assertFalse($result);
		$result = $TestModel->validationErrors;
		$expected = array(
			'title' => 'onlyLetters'
		);
		$this->assertEqual($result, $expected);

		$TestModel->validate = array(
			'title' => array(
				'tooShort' => array(
					'rule' => array('minLength', 50),
					'last' => true
				),
				'onlyLetters' => array('rule' => '/^[a-z]+$/i')
			),
		);
		$data = array('TestValidate' => array(
			'title' => 'I am a short string'
		));
		$TestModel->create($data);
		$result = $TestModel->validates();
		$this->assertFalse($result);
		$result = $TestModel->validationErrors;
		$expected = array(
			'title' => 'tooShort'
		);
		$this->assertEqual($result, $expected);
	}

/**
 * test that validates() checks all the 'with' associations as well for validation
 * as this can cause partial/wrong data insertion.
 *
 * @return void
 */
	function testValidatesWithAssociations() {
		$data = array(
			'Something' => array(
				'id' => 5,
				'title' => 'Extra Fields',
				'body' => 'Extra Fields Body',
				'published' => '1'
			),
			'SomethingElse' => array(
				array('something_else_id' => 1, 'doomed' => '')
			)
		);

		$Something =& new Something();
		$JoinThing =& $Something->JoinThing;

		$JoinThing->validate = array('doomed' => array('rule' => 'notEmpty'));

		$expectedError = array('doomed' => 'This field cannot be left blank');

		$Something->create();
		$result = $Something->save($data);
		$this->assertFalse($result, 'Save occured even when with models failed. %s');
		$this->assertEqual($JoinThing->validationErrors, $expectedError);
		$count = $Something->find('count', array('conditions' => array('Something.id' => $data['Something']['id'])));
		$this->assertIdentical($count, 0);

		$data = array(
			'Something' => array(
				'id' => 5,
				'title' => 'Extra Fields',
				'body' => 'Extra Fields Body',
				'published' => '1'
			),
			'SomethingElse' => array(
				array('something_else_id' => 1, 'doomed' => 1),
				array('something_else_id' => 1, 'doomed' => '')
			)
		);
		$Something->create();
		$result = $Something->save($data);
		$this->assertFalse($result, 'Save occured even when with models failed. %s');

		$joinRecords = $JoinThing->find('count', array(
			'conditions' => array('JoinThing.something_id' => $data['Something']['id'])
		));
		$this->assertEqual($joinRecords, 0, 'Records were saved on the join table. %s');
	}

/**
 * test that saveAll and with models with validation interact well
 *
 * @return void
 */
	function testValidatesWithModelsAndSaveAll() {
		$data = array(
			'Something' => array(
				'id' => 5,
				'title' => 'Extra Fields',
				'body' => 'Extra Fields Body',
				'published' => '1'
			),
			'SomethingElse' => array(
				array('something_else_id' => 1, 'doomed' => '')
			)
		);
		$Something =& new Something();
		$JoinThing =& $Something->JoinThing;

		$JoinThing->validate = array('doomed' => array('rule' => 'notEmpty'));
		$expectedError = array('doomed' => 'This field cannot be left blank');

		$Something->create();
		$result = $Something->saveAll($data, array('validate' => 'only'));
		$this->assertFalse($result);
		$this->assertEqual($JoinThing->validationErrors, $expectedError);

		$Something->create();
		$result = $Something->saveAll($data, array('validate' => 'first'));
		$this->assertFalse($result);
		$this->assertEqual($JoinThing->validationErrors, $expectedError);

		$count = $Something->find('count', array('conditions' => array('Something.id' => $data['Something']['id'])));
		$this->assertIdentical($count, 0);

		$joinRecords = $JoinThing->find('count', array(
			'conditions' => array('JoinThing.something_id' => $data['Something']['id'])
		));
		$this->assertEqual($joinRecords, 0, 'Records were saved on the join table. %s');
	}

/**
 * Test that missing validation methods trigger errors in development mode.
 * Helps to make developement easier.
 *
 * @return void
 */
	function testMissingValidationErrorTriggering() {
		$restore = Configure::read('debug');
		Configure::write('debug', 2);

		$TestModel =& new ValidationTest1();
		$TestModel->create(array('title' => 'foo'));
		$TestModel->validate = array(
			'title' => array(
				'rule' => array('thisOneBringsThePain'),
				'required' => true
			)
		);
		$this->expectError(new PatternExpectation('/thisOneBringsThePain for title/i'));
		$TestModel->invalidFields(array('fieldList' => array('title')));

		Configure::write('debug', 0);
		$this->assertNoErrors();
		$TestModel->invalidFields(array('fieldList' => array('title')));
		Configure::write('debug', $restore);
	}

}
