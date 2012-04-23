<?php
/**
 * CakeFieldTest file
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

require_once dirname(dirname(__FILE__)) . DS . 'ModelTestBase.php';

/**
 * CakeFieldTest
 *
 * @package       Cake.Test.Case.Model.Validator
 */
class CakeFieldTest extends BaseModelTest {

/**
 * setUp method
 *
 * @return void
 */
	public function setUp() {
		parent::setUp();
		$this->Article = new Article();
		$this->Article->set(array('title' => '', 'body' => 'no title'));
		$this->Validator = new ModelValidator($this->Article);
		$this->Validator->getData();
	}

/**
 * testConstruct method
 *
 * @return void
 */
	public function testConstruct() {
		$Field = new CakeField($this->Validator, 'title', 'notEmpty');

		$this->assertEquals(array('title' => '', 'body' => 'no title'), $Field->data);
		$this->assertEquals('title', $Field->field);
		$this->assertEquals(array('notEmpty'), $Field->ruleSet);
	}

/**
 * testValidate method
 *
 * @return void
 */
	public function testValidate() {
		$Field = new CakeField($this->Validator, 'title', 'notEmpty');

		$result = $Field->validate();
		$this->assertFalse($result);

		$Field = new CakeField($this->Validator, 'body', 'notEmpty');

		$result = $Field->validate();
		$this->assertTrue($result);

		$Field = new CakeField($this->Validator, 'nothere', array('notEmpty' => array('rule' => 'notEmpty', 'required' => true)));

		$result = $Field->validate();
		$this->assertFalse($result);
	}

/**
 * testGetRule method
 *
 * @return void
 */
	public function testGetRule() {
		$rules = array('notEmpty' => array('rule' => 'notEmpty', 'message' => 'Can not be empty'));
		$Field = new CakeField($this->Validator, 'title', $rules);

		$result = $Field->getRule('notEmpty');
		$this->assertInstanceOf('CakeRule', $result);
		$this->assertEquals('notEmpty', $result->rule);
		$this->assertEquals(null, $result->required);
		$this->assertEquals(false, $result->allowEmpty);
		$this->assertEquals(null, $result->on);
		$this->assertEquals(true, $result->last);
		$this->assertEquals('Can not be empty', $result->message);
		$this->assertEquals(array('title' => '', 'body' => 'no title'), $result->data);
	}

/**
 * testGetRules method
 *
 * @return void
 */
	public function testGetRules() {
		$rules = array('notEmpty' => array('rule' => 'notEmpty', 'message' => 'Can not be empty'));
		$Field = new CakeField($this->Validator, 'title', $rules);

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
		$Field = new CakeField($this->Validator, 'title', $rules);
		$Rule = new CakeRule($Field, $rules['notEmpty'], 'notEmpty');

		$this->assertEquals($Rule, $Field->getRule('notEmpty'));

		$rules = array('validEmail' => array('rule' => 'email', 'message' => 'Invalid email'));
		$Rule = new CakeRule($Field, $rules['validEmail'], 'validEmail');
		$Field->setRule('validEmail', $Rule);
		$result = $Field->getRules();
		$this->assertEquals(array('notEmpty', 'validEmail'), array_keys($result));

		$rules = array('validEmail' => array('rule' => 'email', 'message' => 'Other message'));
		$Rule = new CakeRule($Field, $rules['validEmail'], 'validEmail');
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
		$this->assertEquals(array('title' => '', 'body' => 'no title'), $result->data);
	}

/**
 * testSetRules method
 *
 * @return void
 */
	public function testSetRules() {
		$rule = array('notEmpty' => array('rule' => 'notEmpty', 'message' => 'Can not be empty'));
		$Field = new CakeField($this->Validator, 'title', $rule);
		$RuleEmpty = new CakeRule($Field, $rule['notEmpty'], 'notEmpty');

		$rule = array('validEmail' => array('rule' => 'email', 'message' => 'Invalid email'));
		$RuleEmail = new CakeRule($Field, $rule['validEmail'], 'validEmail');

		$rules = array('validEmail' => $RuleEmail);
		$Field->setRules($rules, false);
		$result = $Field->getRules();
		$this->assertEquals(array('validEmail'), array_keys($result));

		$rules = array('notEmpty' => $RuleEmpty);
		$Field->setRules($rules, true);
		$result = $Field->getRules();
		$this->assertEquals(array('validEmail', 'notEmpty'), array_keys($result));
	}

/**
 * testGetValidator method
 *
 * @return void
 */
	public function testGetValidator() {
		$rule = array('notEmpty' => array('rule' => 'notEmpty', 'message' => 'Can not be empty'));
		$Field = new CakeField($this->Validator, 'title', $rule);
		$result = $Field->getValidator();
		$this->assertInstanceOf('ModelValidator', $result);
	}

}
