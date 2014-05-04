<?php
/**
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @since         3.0.0
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Test\TestCase\Validation;

use \Cake\Validation\ValidationSet;
use \Cake\Validation\Validator;

/**
 * Tests Validator class
 *
 */
class ValidatorTest extends \Cake\TestSuite\TestCase {

/**
 * Testing you can dynamically add rules to a field
 *
 * @return void
 */
	public function testAddingRulesToField() {
		$validator = new Validator;
		$validator->add('title', 'not-empty', ['rule' => 'notEmpty']);
		$set = $validator->field('title');
		$this->assertInstanceOf('\Cake\Validation\ValidationSet', $set);
		$this->assertCount(1, $set);

		$validator->add('title', 'another', ['rule' => 'alphanumeric']);
		$this->assertCount(2, $set);

		$validator->add('body', 'another', ['rule' => 'crazy']);
		$this->assertCount(1, $validator->field('body'));
		$this->assertCount(2, $validator);
	}

/**
 * Tests that calling field will create a default validation set for it
 *
 * @return void
 */
	public function testFieldDefault() {
		$validator = new Validator;
		$this->assertFalse($validator->hasField('foo'));

		$field = $validator->field('foo');
		$this->assertInstanceOf('\Cake\Validation\ValidationSet', $field);
		$this->assertCount(0, $field);
		$this->assertTrue($validator->hasField('foo'));
	}

/**
 * Tests that field method can be used as a setter
 *
 * @return void
 */
	public function testFieldSetter() {
		$validator = new Validator;
		$validationSet = new ValidationSet;
		$validator->field('thing', $validationSet);
		$this->assertSame($validationSet, $validator->field('thing'));
	}

/**
 * Tests the remove method
 *
 * @return void
 */
	public function testRemove() {
		$validator = new Validator;
		$validator->add('title', 'not-empty', ['rule' => 'notEmpty']);
		$validator->add('title', 'foo', ['rule' => 'bar']);
		$this->assertCount(2, $validator->field('title'));
		$validator->remove('title');
		$this->assertCount(0, $validator->field('title'));
		$validator->remove('title');

		$validator->add('title', 'not-empty', ['rule' => 'notEmpty']);
		$validator->add('title', 'foo', ['rule' => 'bar']);
		$this->assertCount(2, $validator->field('title'));
		$validator->remove('title', 'foo');
		$this->assertCount(1, $validator->field('title'));
		$this->assertNull($validator->field('title')->rule('foo'));
	}

/**
 * Tests the validatePresence method
 *
 * @return void
 */
	public function testValidatePresence() {
		$validator = new Validator;
		$this->assertSame($validator, $validator->validatePresence('title'));
		$this->assertTrue($validator->field('title')->isPresenceRequired());

		$validator->validatePresence('title', false);
		$this->assertFalse($validator->field('title')->isPresenceRequired());

		$validator->validatePresence('title', 'create');
		$this->assertEquals('create', $validator->field('title')->isPresenceRequired());

		$validator->validatePresence('title', 'update');
		$this->assertEquals('update', $validator->field('title')->isPresenceRequired());
	}

/**
 * Tests the isPresenceRequired method
 *
 * @return void
 */
	public function testIsPresenceRequired() {
		$validator = new Validator;
		$this->assertSame($validator, $validator->validatePresence('title'));
		$this->assertTrue($validator->isPresenceRequired('title', true));
		$this->assertTrue($validator->isPresenceRequired('title', false));

		$validator->validatePresence('title', false);
		$this->assertFalse($validator->isPresenceRequired('title', true));
		$this->assertFalse($validator->isPresenceRequired('title', false));

		$validator->validatePresence('title', 'create');
		$this->assertTrue($validator->isPresenceRequired('title', true));
		$this->assertFalse($validator->isPresenceRequired('title', false));

		$validator->validatePresence('title', 'update');
		$this->assertTrue($validator->isPresenceRequired('title', false));
		$this->assertFalse($validator->isPresenceRequired('title', true));
	}

/**
 * Tests errors generated when a field presence is required
 *
 * @return void
 */
	public function testErrorsWithPresenceRequired() {
		$validator = new Validator;
		$validator->validatePresence('title');
		$errors = $validator->errors(['foo' => 'something']);
		$expected = ['title' => ['This field is required']];
		$this->assertEquals($expected, $errors);

		$this->assertEmpty($validator->errors(['title' => 'bar']));

		$validator->validatePresence('title', false);
		$this->assertEmpty($validator->errors(['foo' => 'bar']));
	}

/**
 * Tests custom error messages generated when a field presence is required
 *
 * @return void
 */
	public function testCustomErrorsWithPresenceRequired() {
		$validator = new Validator;
		$validator->validatePresence('title', true, 'Custom message');
		$errors = $validator->errors(['foo' => 'something']);
		$expected = ['title' => ['Custom message']];
		$this->assertEquals($expected, $errors);
	}

/**
 * Tests the allowEmpty method
 *
 * @return void
 */
	public function testAllowEmpty() {
		$validator = new Validator;
		$this->assertSame($validator, $validator->allowEmpty('title'));
		$this->assertTrue($validator->field('title')->isEmptyAllowed());

		$validator->allowEmpty('title', 'create');
		$this->assertEquals('create', $validator->field('title')->isEmptyAllowed());

		$validator->allowEmpty('title', 'update');
		$this->assertEquals('update', $validator->field('title')->isEmptyAllowed());
	}

/**
 * Test the notEmpty() method.
 *
 * @return void
 */
	public function testNotEmpty() {
		$validator = new Validator;
		$validator->notEmpty('title');
		$this->assertFalse($validator->field('title')->isEmptyAllowed());

		$validator->allowEmpty('title');
		$this->assertTrue($validator->field('title')->isEmptyAllowed());
	}

/**
 * Test the notEmpty() method.
 *
 * @return void
 */
	public function testNotEmptyModes() {
		$validator = new Validator;
		$validator->notEmpty('title', 'Need a title', 'create');
		$this->assertFalse($validator->isEmptyAllowed('title', true));
		$this->assertTrue($validator->isEmptyAllowed('title', false));

		$validator->notEmpty('title', 'Need a title', 'update');
		$this->assertTrue($validator->isEmptyAllowed('title', true));
		$this->assertFalse($validator->isEmptyAllowed('title', false));

		$validator->notEmpty('title', 'Need a title');
		$this->assertFalse($validator->isEmptyAllowed('title', true));
		$this->assertFalse($validator->isEmptyAllowed('title', false));

		$validator->notEmpty('title');
		$this->assertFalse($validator->isEmptyAllowed('title', true));
		$this->assertFalse($validator->isEmptyAllowed('title', false));
	}

/**
 * Test interactions between notEmpty() and isAllowed().
 *
 * @return void
 */
	public function testNotEmptyAndIsAllowed() {
		$validator = new Validator;
		$validator->allowEmpty('title')
			->notEmpty('title', 'Need it', 'update');
		$this->assertTrue($validator->isEmptyAllowed('title', true));
		$this->assertFalse($validator->isEmptyAllowed('title', false));

		$validator->allowEmpty('title')
			->notEmpty('title');
		$this->assertFalse($validator->isEmptyAllowed('title', true));
		$this->assertFalse($validator->isEmptyAllowed('title', false));

		$validator->notEmpty('title')
			->allowEmpty('title', 'create');
		$this->assertTrue($validator->isEmptyAllowed('title', true));
		$this->assertFalse($validator->isEmptyAllowed('title', false));
	}

/**
 * Tests the isEmptyAllowed method
 *
 * @return void
 */
	public function testIsEmptyAllowed() {
		$validator = new Validator;
		$this->assertSame($validator, $validator->allowEmpty('title'));
		$this->assertTrue($validator->isEmptyAllowed('title', true));
		$this->assertTrue($validator->isEmptyAllowed('title', false));

		$validator->notEmpty('title');
		$this->assertFalse($validator->isEmptyAllowed('title', true));
		$this->assertFalse($validator->isEmptyAllowed('title', false));

		$validator->allowEmpty('title', 'create');
		$this->assertTrue($validator->isEmptyAllowed('title', true));
		$this->assertFalse($validator->isEmptyAllowed('title', false));

		$validator->allowEmpty('title', 'update');
		$this->assertTrue($validator->isEmptyAllowed('title', false));
		$this->assertFalse($validator->isEmptyAllowed('title', true));
	}

/**
 * Tests errors generated when a field is not allowed to be empty
 *
 * @return void
 */
	public function testErrorsWithEmptyNotAllowed() {
		$validator = new Validator;
		$validator->notEmpty('title');
		$errors = $validator->errors(['title' => '']);
		$expected = ['title' => ['This field cannot be left empty']];
		$this->assertEquals($expected, $errors);

		$errors = $validator->errors(['title' => []]);
		$expected = ['title' => ['This field cannot be left empty']];
		$this->assertEquals($expected, $errors);

		$errors = $validator->errors(['title' => null]);
		$expected = ['title' => ['This field cannot be left empty']];
		$this->assertEquals($expected, $errors);

		$errors = $validator->errors(['title' => 0]);
		$this->assertEmpty($errors);

		$errors = $validator->errors(['title' => '0']);
		$this->assertEmpty($errors);

		$errors = $validator->errors(['title' => false]);
		$this->assertEmpty($errors);
	}

/**
 * Tests custom error mesages generated when a field is not allowed to be empty
 *
 * @return void
 */
	public function testCustomErrorsWithEmptyNotAllowed() {
		$validator = new Validator;
		$validator->notEmpty('title', 'Custom message');
		$errors = $validator->errors(['title' => '']);
		$expected = ['title' => ['Custom message']];
		$this->assertEquals($expected, $errors);
	}

/**
 * Tests errors generated when a field is allowed to be empty
 *
 * @return void
 */
	public function testErrorsWithEmptyAllowed() {
		$validator = new Validator;
		$validator->allowEmpty('title');
		$errors = $validator->errors(['title' => '']);
		$this->assertEmpty($errors);

		$errors = $validator->errors(['title' => []]);
		$this->assertEmpty($errors);

		$errors = $validator->errors(['title' => null]);
		$this->assertEmpty($errors);

		$errors = $validator->errors(['title' => 0]);
		$this->assertEmpty($errors);

		$errors = $validator->errors(['title' => '0']);
		$this->assertEmpty($errors);

		$errors = $validator->errors(['title' => false]);
		$this->assertEmpty($errors);
	}

/**
 * Test the provider() method
 *
 * @return void
 */
	public function testProvider() {
		$validator = new Validator;
		$object = new \stdClass;
		$this->assertSame($validator, $validator->provider('foo', $object));
		$this->assertSame($object, $validator->provider('foo'));
		$this->assertNull($validator->provider('bar'));

		$another = new \stdClass;
		$this->assertSame($validator, $validator->provider('bar', $another));
		$this->assertSame($another, $validator->provider('bar'));

		$this->assertEquals(new \Cake\Validation\RulesProvider, $validator->provider('default'));
	}

/**
 * Tests errors() method when using validators from the default provider, this proves
 * that it returns a default validation message and the custom one set in the rule
 *
 * @return void
 */
	public function testErrorsFromDefaultProvider() {
		$validator = new Validator;
		$validator
			->add('email', 'alpha', ['rule' => 'alphanumeric'])
			->add('email', 'notEmpty', ['rule' => 'notEmpty'])
			->add('email', 'email', ['rule' => 'email', 'message' => 'Y u no write email?']);
		$errors = $validator->errors(['email' => 'not an email!']);
		$expected = [
			'email' => [
				'alpha' => 'The provided value is invalid',
				'email' => 'Y u no write email?'
			]
		];
		$this->assertEquals($expected, $errors);
	}

/**
 * Tests using validation methods from different providers and returning the error
 * as a string
 *
 * @return void
 */
	public function testErrorsFromCustomProvider() {
		$validator = new Validator;
		$validator
			->add('email', 'alpha', ['rule' => 'alphanumeric'])
			->add('title', 'cool', ['rule' => 'isCool', 'provider' => 'thing']);

		$thing = $this->getMock('\stdClass', ['isCool']);
		$thing->expects($this->once())->method('isCool')
			->will($this->returnCallback(function($data, $context) use ($thing) {
				$this->assertEquals('bar', $data);
				$expected = [
					'default' => new \Cake\Validation\RulesProvider,
					'thing' => $thing
				];
				$expected = [
					'newRecord' => true,
					'providers' => $expected,
					'data' => [
						'email' => '!',
						'title' => 'bar'
					]
				];
				$this->assertEquals($expected, $context);
				return "That ain't cool, yo";
			}));

		$validator->provider('thing', $thing);
		$errors = $validator->errors(['email' => '!', 'title' => 'bar']);
		$expected = [
			'email' => ['alpha' => 'The provided value is invalid'],
			'title' => ['cool' => "That ain't cool, yo"]
		];
		$this->assertEquals($expected, $errors);
	}

/**
 * Tests that it is possible to pass extra arguments to the validation function
 * and it still gets the providers as last argument
 *
 * @return void
 */
	public function testMethodsWithExtraArguments() {
		$validator = new Validator;
		$validator->add('title', 'cool', [
			'rule' => ['isCool', 'and', 'awesome'],
			'provider' => 'thing'
		]);
		$thing = $this->getMock('\stdClass', ['isCool']);
		$thing->expects($this->once())->method('isCool')
			->will($this->returnCallback(function($data, $a, $b, $context) use ($thing) {
				$this->assertEquals('bar', $data);
				$this->assertEquals('and', $a);
				$this->assertEquals('awesome', $b);
				$expected = [
					'default' => new \Cake\Validation\RulesProvider,
					'thing' => $thing
				];
				$expected = [
					'newRecord' => true,
					'providers' => $expected,
					'data' => [
						'email' => '!',
						'title' => 'bar'
					]
				];
				$this->assertEquals($expected, $context);
				return "That ain't cool, yo";
			}));
		$validator->provider('thing', $thing);
		$errors = $validator->errors(['email' => '!', 'title' => 'bar']);
		$expected = [
			'title' => ['cool' => "That ain't cool, yo"]
		];
		$this->assertEquals($expected, $errors);
	}

/**
 * Tests that it is possible to use a closure as a rule
 *
 * @return void
 */
	public function testUsingClosureAsRule() {
		$validator = new Validator;
		$validator->add('name', 'myRule', [
			'rule' => function($data, $provider) {
				$this->assertEquals('foo', $data);
				return 'You fail';
			}
		]);
		$expected = ['name' => ['myRule' => 'You fail']];
		$this->assertEquals($expected, $validator->errors(['name' => 'foo']));
	}

/**
 * Tests that setting last to a rule will stop validating the rest of the rules
 *
 * @return void
 */
	public function testErrorsWithLastRule() {
		$validator = new Validator;
		$validator
			->add('email', 'alpha', ['rule' => 'alphanumeric', 'last' => true])
			->add('email', 'email', ['rule' => 'email', 'message' => 'Y u no write email?']);
		$errors = $validator->errors(['email' => 'not an email!']);
		$expected = [
			'email' => [
				'alpha' => 'The provided value is invalid'
			]
		];

		$this->assertEquals($expected, $errors);
	}

/**
 * Tests it is possible to get validation sets for a field using an array interface
 *
 * @return void
 */
	public function testArrayAccessGet() {
		$validator = new Validator;
		$validator
			->add('email', 'alpha', ['rule' => 'alphanumeric'])
			->add('title', 'cool', ['rule' => 'isCool', 'provider' => 'thing']);
		$this->assertSame($validator['email'], $validator->field('email'));
		$this->assertSame($validator['title'], $validator->field('title'));
	}

/**
 * Tests it is possible to check for validation sets for a field using an array inteface
 *
 * @return void
 */
	public function testArrayAccessExists() {
		$validator = new Validator;
		$validator
			->add('email', 'alpha', ['rule' => 'alphanumeric'])
			->add('title', 'cool', ['rule' => 'isCool', 'provider' => 'thing']);
		$this->assertTrue(isset($validator['email']));
		$this->assertTrue(isset($validator['title']));
		$this->assertFalse(isset($validator['foo']));
	}

/**
 * Tests it is possible to set validation rules for a field using an array inteface
 *
 * @return void
 */
	public function testArrayAccessSet() {
		$validator = new Validator;
		$validator
			->add('email', 'alpha', ['rule' => 'alphanumeric'])
			->add('title', 'cool', ['rule' => 'isCool', 'provider' => 'thing']);
		$validator['name'] = $validator->field('title');
		$this->assertSame($validator->field('title'), $validator->field('name'));
		$validator['name'] = ['alpha' => ['rule' => 'alphanumeric']];
		$this->assertEquals($validator->field('email'), $validator->field('email'));
	}

/**
 * Tests it is possible to unset validation rules
 *
 * @return void
 */
	public function testArrayAccessUset() {
		$validator = new Validator;
		$validator
			->add('email', 'alpha', ['rule' => 'alphanumeric'])
			->add('title', 'cool', ['rule' => 'isCool', 'provider' => 'thing']);
		$this->assertTrue(isset($validator['title']));
		unset($validator['title']);
		$this->assertFalse(isset($validator['title']));
	}

/**
 * Tests the countable interface
 *
 * @return void
 */
	public function testCount() {
		$validator = new Validator;
		$validator
			->add('email', 'alpha', ['rule' => 'alphanumeric'])
			->add('title', 'cool', ['rule' => 'isCool', 'provider' => 'thing']);
		$this->assertCount(2, $validator);
	}

/**
 * Tests adding rules via alternative syntax
 *
 * @return void
 */
	public function testAddMulitple() {
		$validator = new Validator;
		$validator->add('title', [
			'notEmpty' => [
				'rule' => 'notEmpty'
			],
			'length' => [
				'rule' => ['minLength', 10],
				'message' => 'Titles need to be at least 10 characters long'
			]
		]);
		$set = $validator->field('title');
		$this->assertInstanceOf('\Cake\Validation\ValidationSet', $set);
		$this->assertCount(2, $set);
	}

}
