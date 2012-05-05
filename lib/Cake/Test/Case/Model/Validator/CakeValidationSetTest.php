<?php
/**
 * CakeValidationSetTest file
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

App::uses('CakeValidationSet', 'Model/Validator');

/**
 * CakeValidationSetTest
 *
 * @package       Cake.Test.Case.Model.Validator
 */
class CakeValidationSetTest extends CakeTestModel {

/**
 * setUp method
 *
 * @return void
 */
	public function setUp() {
		parent::setUp();
	}

/**
 * testValidate method
 *
 * @return void
 */
	public function testValidate() {
		$Field = new CakeValidationSet('title', 'notEmpty');
		$data = array(
			'title' => '',
			'body' => 'a body'
		);

		$result = $Field->validate($data);
		$expected = array('This field cannot be left blank');
		$this->assertEquals($expected, $result);

		$Field = new CakeValidationSet('body', 'notEmpty');

		$result = $Field->validate($data);
		$this->assertEmpty($result);

		$Field = new CakeValidationSet('nothere', array('notEmpty' => array('rule' => 'notEmpty', 'required' => true)));

		$result = $Field->validate($data);
		$expected = array('notEmpty');
		$this->assertEquals($expected, $result);
	}

/**
 * testGetRule method
 *
 * @return void
 */
	public function testGetRule() {
		$rules = array('notEmpty' => array('rule' => 'notEmpty', 'message' => 'Can not be empty'));
		$Field = new CakeValidationSet('title', $rules);
		$data = array(
			'title' => '',
			'body' => 'a body'
		);

		$result = $Field->getRule('notEmpty');
		$this->assertInstanceOf('CakeRule', $result);
		$this->assertEquals('notEmpty', $result->rule);
		$this->assertEquals(null, $result->required);
		$this->assertEquals(false, $result->allowEmpty);
		$this->assertEquals(null, $result->on);
		$this->assertEquals(true, $result->last);
		$this->assertEquals('Can not be empty', $result->message);
	}

/**
 * testGetRules method
 *
 * @return void
 */
	public function testGetRules() {
		$rules = array('notEmpty' => array('rule' => 'notEmpty', 'message' => 'Can not be empty'));
		$Field = new CakeValidationSet('title', $rules);

		$result = $Field->getRules();
		$this->assertEquals(array('notEmpty'), array_keys($result));
		$this->assertInstanceOf('CakeRule', $result['notEmpty']);
	}

/**
 * testSetRule method
 *
 * @return void
 */
	public function testSetRule() {
		$rules = array('notEmpty' => array('rule' => 'notEmpty', 'message' => 'Can not be empty'));
		$Field = new CakeValidationSet('title', $rules);
		$Rule = new CakeRule($rules['notEmpty']);

		$this->assertEquals($Rule, $Field->getRule('notEmpty'));

		$rules = array('validEmail' => array('rule' => 'email', 'message' => 'Invalid email'));
		$Rule = new CakeRule($rules['validEmail']);
		$Field->setRule('validEmail', $Rule);
		$result = $Field->getRules();
		$this->assertEquals(array('notEmpty', 'validEmail'), array_keys($result));

		$rules = array('validEmail' => array('rule' => 'email', 'message' => 'Other message'));
		$Rule = new CakeRule($rules['validEmail']);
		$Field->setRule('validEmail', $Rule);
		$result = $Field->getRules();
		$this->assertEquals(array('notEmpty', 'validEmail'), array_keys($result));
		$result = $Field->getRule('validEmail');
		$this->assertInstanceOf('CakeRule', $result);
		$this->assertEquals('email', $result->rule);
		$this->assertEquals(null, $result->required);
		$this->assertEquals(false, $result->allowEmpty);
		$this->assertEquals(null, $result->on);
		$this->assertEquals(true, $result->last);
		$this->assertEquals('Other message', $result->message);
	}

/**
 * testSetRules method
 *
 * @return void
 */
	public function testSetRules() {
		$rule = array('notEmpty' => array('rule' => 'notEmpty', 'message' => 'Can not be empty'));
		$Field = new CakeValidationSet('title', $rule);
		$RuleEmpty = new CakeRule($rule['notEmpty']);

		$rule = array('validEmail' => array('rule' => 'email', 'message' => 'Invalid email'));
		$RuleEmail = new CakeRule($rule['validEmail']);

		$rules = array('validEmail' => $RuleEmail);
		$Field->setRules($rules, false);
		$result = $Field->getRules();
		$this->assertEquals(array('validEmail'), array_keys($result));

		$rules = array('notEmpty' => $RuleEmpty);
		$Field->setRules($rules, true);
		$result = $Field->getRules();
		$this->assertEquals(array('validEmail', 'notEmpty'), array_keys($result));
	}

}
