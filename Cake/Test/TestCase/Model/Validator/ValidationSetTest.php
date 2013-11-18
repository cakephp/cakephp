<?php
/**
 * ValidationSetTest file
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
namespace Cake\Test\TestCase\Model\Validator;

use Cake\Core\Configure;
use Cake\Model\Validator\ValidationRule;
use Cake\Model\Validator\ValidationSet;
use Cake\TestSuite\TestCase;

/**
 * ValidationSetTest
 *
 */
class ValidationSetTest extends TestCase {

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
		$Field = new ValidationSet('title', 'notEmpty');
		$data = array(
			'title' => '',
			'body' => 'a body'
		);

		$result = $Field->validate($data);
		$expected = array('The provided value is invalid');
		$this->assertEquals($expected, $result);

		$Field = new ValidationSet('body', 'notEmpty');

		$result = $Field->validate($data);
		$this->assertEmpty($result);

		$Field = new ValidationSet('body', array(
			'inList' => array(
				'rule' => array('inList', array('test'))
			)
		));
		$result = $Field->validate($data);
		$expected = array('inList');
		$this->assertEquals($expected, $result);
	}

/**
 * testValidateWithvalidatePresent method
 *
 * @return void
 */
	public function testValidateWithvalidatePresent() {
		$data = array(
			'title' => '',
			'body' => 'a body'
		);
		$expectedPresent = array('This field must exist in data');

		$Field = new ValidationSet('notthere', array(
			'_validatePresent' => true,
			'notEmpty' => array(
				'rule' => 'notEmpty'
			)
		));

		$result = $Field->validate($data);
		$this->assertEquals($expectedPresent, $result);

		$Field = new ValidationSet('notthere', array(
			'_validatePresent' => 'create',
			'notEmpty' => array(
				'rule' => 'notEmpty'
			)
		));

		$result = $Field->validate($data);
		$this->assertEquals($expectedPresent, $result);

		$result = $Field->validate($data, true);
		$this->assertSame(array(), $result);

		$Field = new ValidationSet('notthere', array(
			'_validatePresent' => 'update',
			'notEmpty' => array(
				'rule' => 'notEmpty'
			)
		));

		$result = $Field->validate($data);
		$this->assertSame(array(), $result);

		$result = $Field->validate($data, true);
		$this->assertEquals($expectedPresent, $result);
	}

/**
 * testValidateWithAllowEmpty method
 *
 * @return void
 */
	public function testValidateWithAllowEmpty() {
		$data = array(
			'title' => '',
			'body' => 'a body'
		);

		$Field = new ValidationSet('title', array(
			'_allowEmpty' => true,
			'notEmpty' => array(
				'rule' => 'notEmpty'
			)
		));

		$result = $Field->validate($data);
		$this->assertSame(array(), $result);

		$Field = new ValidationSet('title', array(
			'_allowEmpty' => 'create',
			'notEmpty' => array(
				'rule' => 'notEmpty'
			)
		));

		$result = $Field->validate($data);
		$this->assertSame(array(), $result);

		$result = $Field->validate($data, true);
		$this->assertEquals(array('notEmpty'), $result);

		$Field = new ValidationSet('title', array(
			'_allowEmpty' => 'update',
			'notEmpty' => array(
				'rule' => 'notEmpty'
			)
		));

		$result = $Field->validate($data);
		$this->assertEquals(array('notEmpty'), $result);

		$result = $Field->validate($data, true);
		$this->assertSame(array(), $result);

		$Field = new ValidationSet('title', array(
			'_allowEmpty' => 'update',
			'notEmpty' => array(
				'rule' => 'notEmpty',
				'on' => 'create'
			),
			'between' => array(
				'rule' => array('between', 1, 50),
				'on' => 'update'
			)
		));
		$result = $Field->validate($data);
		$this->assertEquals(array('notEmpty'), $result);

		$result = $Field->validate($data, true);
		$this->assertSame(array(), $result);
	}

/**
 * testGetRule method
 *
 * @return void
 */
	public function testGetRule() {
		$rules = array('notEmpty' => array('rule' => 'notEmpty', 'message' => 'Can not be empty'));
		$Field = new ValidationSet('title', $rules);
		$result = $Field->getRule('notEmpty');
		$this->assertInstanceOf('Cake\Model\Validator\ValidationRule', $result);
		$this->assertEquals('notEmpty', $result->rule);
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
		$Field = new ValidationSet('title', $rules);

		$result = $Field->getRules();
		$this->assertEquals(array('notEmpty'), array_keys($result));
		$this->assertInstanceOf('Cake\Model\Validator\ValidationRule', $result['notEmpty']);
	}

/**
 * testSetRule method
 *
 * @return void
 */
	public function testSetRule() {
		$rules = array('notEmpty' => array('rule' => 'notEmpty', 'message' => 'Can not be empty'));
		$Field = new ValidationSet('title', $rules);
		$Rule = new ValidationRule($rules['notEmpty']);

		$this->assertEquals($Rule, $Field->getRule('notEmpty'));

		$rules = array('validEmail' => array('rule' => 'email', 'message' => 'Invalid email'));
		$Rule = new ValidationRule($rules['validEmail']);
		$Field->setRule('validEmail', $Rule);
		$result = $Field->getRules();
		$this->assertEquals(array('notEmpty', 'validEmail'), array_keys($result));

		$rules = array('validEmail' => array('rule' => 'email', 'message' => 'Other message'));
		$Rule = new ValidationRule($rules['validEmail']);
		$Field->setRule('validEmail', $Rule);
		$result = $Field->getRules();
		$this->assertEquals(array('notEmpty', 'validEmail'), array_keys($result));
		$result = $Field->getRule('validEmail');
		$this->assertInstanceOf('Cake\Model\Validator\ValidationRule', $result);
		$this->assertEquals('email', $result->rule);
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
		$Field = new ValidationSet('title', $rule);
		$RuleEmpty = new ValidationRule($rule['notEmpty']);

		$rule = array('validEmail' => array('rule' => 'email', 'message' => 'Invalid email'));
		$RuleEmail = new ValidationRule($rule['validEmail']);

		$rules = array('validEmail' => $RuleEmail);
		$Field->setRules($rules, false);
		$result = $Field->getRules();
		$this->assertEquals(array('validEmail'), array_keys($result));

		$Field->setRules(array('validEmail' => $rule), false);
		$result = $Field->getRules();
		$this->assertEquals(array('validEmail'), array_keys($result));
		$this->assertTrue(array_pop($result) instanceof ValidationRule);

		$rules = array('notEmpty' => $RuleEmpty);
		$Field->setRules($rules, true);
		$result = $Field->getRules();
		$this->assertEquals(array('validEmail', 'notEmpty'), array_keys($result));

		$rules = array('notEmpty' => array('rule' => 'notEmpty'));
		$Field->setRules($rules, true);
		$result = $Field->getRules();
		$this->assertEquals(array('validEmail', 'notEmpty'), array_keys($result));
		$this->assertTrue(array_pop($result) instanceof ValidationRule);
		$this->assertTrue(array_pop($result) instanceof ValidationRule);
	}

/**
 * Tests getting a rule from the set using array access
 *
 * @return void
 */
	public function testArrayAccessGet() {
		$Set = new ValidationSet('title', array(
			'_validatePresent' => true,
			'notEmpty' => array('rule' => 'notEmpty'),
			'numeric' => array('rule' => 'numeric'),
			'other' => array('rule' => array('other', 1)),
		));

		$rule = $Set['notEmpty'];
		$this->assertInstanceOf('Cake\Model\Validator\ValidationRule', $rule);
		$this->assertEquals('notEmpty', $rule->rule);

		$rule = $Set['numeric'];
		$this->assertInstanceOf('Cake\Model\Validator\ValidationRule', $rule);
		$this->assertEquals('numeric', $rule->rule);

		$rule = $Set['other'];
		$this->assertInstanceOf('Cake\Model\Validator\ValidationRule', $rule);
		$this->assertEquals(array('other', 1), $rule->rule);
	}

/**
 * Tests checking a rule from the set using array access
 *
 * @return void
 */
	public function testArrayAccessExists() {
		$Set = new ValidationSet('title', array(
			'_validatePresent' => true,
			'notEmpty' => array('rule' => 'notEmpty'),
			'numeric' => array('rule' => 'numeric'),
			'other' => array('rule' => array('other', 1)),
		));

		$this->assertTrue(isset($Set['notEmpty']));
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
		$Set = new ValidationSet('title', array(
			'_validatePresent' => true,
			'notEmpty' => array('rule' => 'notEmpty'),
		));

		$this->assertFalse(isset($Set['other']));
		$Set['other'] = array('rule' => array('other', 1));
		$rule = $Set['other'];
		$this->assertInstanceOf('Cake\Model\Validator\ValidationRule', $rule);
		$this->assertEquals(array('other', 1), $rule->rule);

		$this->assertFalse(isset($Set['numeric']));
		$Set['numeric'] = new ValidationRule(array('rule' => 'numeric'));
		$rule = $Set['numeric'];
		$this->assertInstanceOf('Cake\Model\Validator\ValidationRule', $rule);
		$this->assertEquals('numeric', $rule->rule);
	}

/**
 * Tests unseting a rule from the set using array access
 *
 * @return void
 */
	public function testArrayAccessUnset() {
		$Set = new ValidationSet('title', array(
			'_validatePresent' => true,
			'notEmpty' => array('rule' => 'notEmpty'),
			'numeric' => array('rule' => 'numeric'),
			'other' => array('rule' => array('other', 1)),
		));

		unset($Set['notEmpty']);
		$this->assertFalse(isset($Set['notEmpty']));

		unset($Set['numeric']);
		$this->assertFalse(isset($Set['notEmpty']));

		unset($Set['other']);
		$this->assertFalse(isset($Set['notEmpty']));
	}

/**
 * Tests it is possible to iterate a validation set object
 *
 * @return void
 */
	public function testIterator() {
		$Set = new ValidationSet('title', array(
			'_validatePresent' => true,
			'notEmpty' => array('rule' => 'notEmpty'),
			'numeric' => array('rule' => 'numeric'),
			'other' => array('rule' => array('other', 1)),
		));

		$i = 0;
		foreach ($Set as $name => $rule) {
			if ($i === 0) {
				$this->assertEquals('notEmpty', $name);
			}
			if ($i === 1) {
				$this->assertEquals('numeric', $name);
			}
			if ($i === 2) {
				$this->assertEquals('other', $name);
			}
			$this->assertInstanceOf('Cake\Model\Validator\ValidationRule', $rule);
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
		$Set = new ValidationSet('title', array(
			'_validatePresent' => true,
			'notEmpty' => array('rule' => 'notEmpty'),
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
		$Set = new ValidationSet('title', array(
			'_validatePresent' => true,
			'notEmpty' => array('rule' => 'notEmpty'),
			'numeric' => array('rule' => 'numeric'),
			'other' => array('rule' => array('other', 1)),
		));

		$Set->removeRule('notEmpty');
		$this->assertFalse(isset($Set['notEmpty']));

		$Set->removeRule('numeric');
		$this->assertFalse(isset($Set['numeric']));

		$Set->removeRule('other');
		$this->assertFalse(isset($Set['other']));
	}

}
