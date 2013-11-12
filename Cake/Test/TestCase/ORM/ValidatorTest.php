<?php
/**
 * PHP Version 5.4
 *
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @since         CakePHP(tm) v 3.0.0
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
namespace Cake\Test\TestCase\ORM;

use \Cake\ORM\Validation\ValidationSet;
use \Cake\ORM\Validator;

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
		$this->assertInstanceOf('\Cake\ORM\Validation\ValidationSet', $set);
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
		$field = $validator->field('foo');
		$this->assertInstanceOf('\Cake\ORM\Validation\ValidationSet', $field);
		$this->assertCount(0, $field);
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

		$validator->validatePresence('title', 'created');
		$this->assertEquals('created', $validator->field('title')->isPresenceRequired());

		$validator->validatePresence('title', 'updated');
		$this->assertEquals('updated', $validator->field('title')->isPresenceRequired());
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

}
