<?php
/**
 * CakeValidationSetTest file
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

App::uses('CakeValidationSet', 'Model/Validator');

/**
 * CakeValidationSetTest
 *
 * @package       Cake.Test.Case.Model.Validator
 */
class CakeValidationSetTest extends CakeTestCase {

/**
 * override locale to the default (eng).
 *
 * @return void
 */
	public function setUp() {
		parent::setUp();
		Configure::write('Config.language', 'eng');
	}

/**
 * testValidate method
 *
 * @return void
 */
	public function testValidate() {
		$Field = new CakeValidationSet('title', 'notBlank');
		$data = array(
			'title' => '',
			'body' => 'a body'
		);

		$result = $Field->validate($data);
		$expected = array('This field cannot be left blank');
		$this->assertEquals($expected, $result);

		$Field = new CakeValidationSet('body', 'notBlank');

		$result = $Field->validate($data);
		$this->assertEmpty($result);

		$Field = new CakeValidationSet('nothere', array(
			'notBlank' => array(
				'rule' => 'notBlank',
				'required' => true
			)
		));

		$result = $Field->validate($data);
		$expected = array('notBlank');
		$this->assertEquals($expected, $result);

		$Field = new CakeValidationSet('body', array(
			'inList' => array(
				'rule' => array('inList', array('test'))
			)
		));
		$result = $Field->validate($data);
		$expected = array('inList');
		$this->assertEquals($expected, $result);
	}

/**
 * testGetRule method
 *
 * @return void
 */
	public function testGetRule() {
		$rules = array('notBlank' => array('rule' => 'notBlank', 'message' => 'Can not be empty'));
		$Field = new CakeValidationSet('title', $rules);
		$result = $Field->getRule('notBlank');
		$this->assertInstanceOf('CakeValidationRule', $result);
		$this->assertEquals('notBlank', $result->rule);
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
		$rules = array('notBlank' => array('rule' => 'notBlank', 'message' => 'Can not be empty'));
		$Field = new CakeValidationSet('title', $rules);

		$result = $Field->getRules();
		$this->assertEquals(array('notBlank'), array_keys($result));
		$this->assertInstanceOf('CakeValidationRule', $result['notBlank']);
	}

/**
 * testSetRule method
 *
 * @return void
 */
	public function testSetRule() {
		$rules = array('notBlank' => array('rule' => 'notBlank', 'message' => 'Can not be empty'));
		$Field = new CakeValidationSet('title', $rules);
		$Rule = new CakeValidationRule($rules['notBlank']);

		$this->assertEquals($Rule, $Field->getRule('notBlank'));

		$rules = array('validEmail' => array('rule' => 'email', 'message' => 'Invalid email'));
		$Rule = new CakeValidationRule($rules['validEmail']);
		$Field->setRule('validEmail', $Rule);
		$result = $Field->getRules();
		$this->assertEquals(array('notBlank', 'validEmail'), array_keys($result));

		$rules = array('validEmail' => array('rule' => 'email', 'message' => 'Other message'));
		$Rule = new CakeValidationRule($rules['validEmail']);
		$Field->setRule('validEmail', $Rule);
		$result = $Field->getRules();
		$this->assertEquals(array('notBlank', 'validEmail'), array_keys($result));
		$result = $Field->getRule('validEmail');
		$this->assertInstanceOf('CakeValidationRule', $result);
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
		$rule = array('notBlank' => array('rule' => 'notBlank', 'message' => 'Can not be empty'));
		$Field = new CakeValidationSet('title', $rule);
		$RuleEmpty = new CakeValidationRule($rule['notBlank']);

		$rule = array('validEmail' => array('rule' => 'email', 'message' => 'Invalid email'));
		$RuleEmail = new CakeValidationRule($rule['validEmail']);

		$rules = array('validEmail' => $RuleEmail);
		$Field->setRules($rules, false);
		$result = $Field->getRules();
		$this->assertEquals(array('validEmail'), array_keys($result));

		$Field->setRules(array('validEmail' => $rule), false);
		$result = $Field->getRules();
		$this->assertEquals(array('validEmail'), array_keys($result));
		$this->assertTrue(array_pop($result) instanceof CakeValidationRule);

		$rules = array('notBlank' => $RuleEmpty);
		$Field->setRules($rules, true);
		$result = $Field->getRules();
		$this->assertEquals(array('validEmail', 'notBlank'), array_keys($result));

		$rules = array('notBlank' => array('rule' => 'notBlank'));
		$Field->setRules($rules, true);
		$result = $Field->getRules();
		$this->assertEquals(array('validEmail', 'notBlank'), array_keys($result));
		$this->assertTrue(array_pop($result) instanceof CakeValidationRule);
		$this->assertTrue(array_pop($result) instanceof CakeValidationRule);
	}

/**
 * Tests getting a rule from the set using array access
 *
 * @return void
 */
	public function testArrayAccessGet() {
		$Set = new CakeValidationSet('title', array(
			'notBlank' => array('rule' => 'notBlank', 'required' => true),
			'numeric' => array('rule' => 'numeric'),
			'other' => array('rule' => array('other', 1)),
		));

		$rule = $Set['notBlank'];
		$this->assertInstanceOf('CakeValidationRule', $rule);
		$this->assertEquals('notBlank', $rule->rule);

		$rule = $Set['numeric'];
		$this->assertInstanceOf('CakeValidationRule', $rule);
		$this->assertEquals('numeric', $rule->rule);

		$rule = $Set['other'];
		$this->assertInstanceOf('CakeValidationRule', $rule);
		$this->assertEquals(array('other', 1), $rule->rule);
	}

/**
 * Tests checking a rule from the set using array access
 *
 * @return void
 */
	public function testArrayAccessExists() {
		$Set = new CakeValidationSet('title', array(
			'notBlank' => array('rule' => 'notBlank', 'required' => true),
			'numeric' => array('rule' => 'numeric'),
			'other' => array('rule' => array('other', 1)),
		));

		$this->assertTrue(isset($Set['notBlank']));
		$this->assertTrue(isset($Set['numeric']));
		$this->assertTrue(isset($Set['other']));
		$this->assertFalse(isset($Set['fail']));
	}

/**
 * Tests setting a rule in the set using array access
 *
 * @return void
 */
	public function testArrayAccessSet() {
		$Set = new CakeValidationSet('title', array(
			'notBlank' => array('rule' => 'notBlank', 'required' => true),
		));

		$this->assertFalse(isset($Set['other']));
		$Set['other'] = array('rule' => array('other', 1));
		$rule = $Set['other'];
		$this->assertInstanceOf('CakeValidationRule', $rule);
		$this->assertEquals(array('other', 1), $rule->rule);

		$this->assertFalse(isset($Set['numeric']));
		$Set['numeric'] = new CakeValidationRule(array('rule' => 'numeric'));
		$rule = $Set['numeric'];
		$this->assertInstanceOf('CakeValidationRule', $rule);
		$this->assertEquals('numeric', $rule->rule);
	}

/**
 * Tests unseting a rule from the set using array access
 *
 * @return void
 */
	public function testArrayAccessUnset() {
		$Set = new CakeValidationSet('title', array(
			'notBlank' => array('rule' => 'notBlank', 'required' => true),
			'numeric' => array('rule' => 'numeric'),
			'other' => array('rule' => array('other', 1)),
		));

		unset($Set['notBlank']);
		$this->assertFalse(isset($Set['notBlank']));

		unset($Set['numeric']);
		$this->assertFalse(isset($Set['notBlank']));

		unset($Set['other']);
		$this->assertFalse(isset($Set['notBlank']));
	}

/**
 * Tests it is possible to iterate a validation set object
 *
 * @return void
 */
	public function testIterator() {
		$Set = new CakeValidationSet('title', array(
			'notBlank' => array('rule' => 'notBlank', 'required' => true),
			'numeric' => array('rule' => 'numeric'),
			'other' => array('rule' => array('other', 1)),
		));

		$i = 0;
		foreach ($Set as $name => $rule) {
			if ($i === 0) {
				$this->assertEquals('notBlank', $name);
			}
			if ($i === 1) {
				$this->assertEquals('numeric', $name);
			}
			if ($i === 2) {
				$this->assertEquals('other', $name);
			}
			$this->assertInstanceOf('CakeValidationRule', $rule);
			$i++;
		}
		$this->assertEquals(3, $i);
	}

/**
 * Tests countable interface
 *
 * @return void
 */
	public function testCount() {
		$Set = new CakeValidationSet('title', array(
			'notBlank' => array('rule' => 'notBlank', 'required' => true),
			'numeric' => array('rule' => 'numeric'),
			'other' => array('rule' => array('other', 1)),
		));
		$this->assertCount(3, $Set);

		unset($Set['other']);
		$this->assertCount(2, $Set);
	}

/**
 * Test removeRule method
 *
 * @return void
 */
	public function testRemoveRule() {
		$Set = new CakeValidationSet('title', array(
			'notBlank' => array('rule' => 'notBlank', 'required' => true),
			'numeric' => array('rule' => 'numeric'),
			'other' => array('rule' => array('other', 1)),
		));

		$Set->removeRule('notBlank');
		$this->assertFalse(isset($Set['notBlank']));

		$Set->removeRule('numeric');
		$this->assertFalse(isset($Set['numeric']));

		$Set->removeRule('other');
		$this->assertFalse(isset($Set['other']));
	}

}
