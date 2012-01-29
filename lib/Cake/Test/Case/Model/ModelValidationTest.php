<?php
/**
 * ModelValidationTest file
 *
 * PHP 5
 *
 * CakePHP(tm) Tests <http://book.cakephp.org/view/1196/Testing>
 * Copyright 2005-2011, Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice
 *
 * @copyright     Copyright 2005-2011, Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://book.cakephp.org/view/1196/Testing CakePHP(tm) Tests
 * @package       Cake.Test.Case.Model
 * @since         CakePHP(tm) v 1.2.0.4206
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
require_once dirname(__FILE__) . DS . 'ModelTestBase.php';

/**
 * ModelValidationTest
 *
 * @package       Cake.Test.Case.Model
 */
class ModelValidationTest extends BaseModelTest {

/**
 * Tests validation parameter order in custom validation methods
 *
 * @return void
 */
	public function testValidationParams() {
		$TestModel = new ValidationTest1();
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
				'last' => true,
				'allowEmpty' => false,
				'required' => true
			),
			'or' => true,
			'ignore_on_same' => 'id'
		);
		$this->assertEquals($TestModel->validatorParams, $expected);

		$TestModel->validate['title'] = array(
			'rule' => 'customValidatorWithMessage',
			'required' => true
		);
		$expected = array(
			'title' => array('This field will *never* validate! Muhahaha!')
		);

		$this->assertEquals($TestModel->invalidFields(), $expected);

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
				'last' => true,
				'allowEmpty' => false,
				'required' => true
			),
			'six' => 6
		);
		$this->assertEquals($TestModel->validatorParams, $expected);

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
				'last' => true,
				'allowEmpty' => false,
				'required' => true
			)
		);
		$this->assertEquals($TestModel->validatorParams, $expected);
	}

/**
 * Tests validation parameter fieldList in invalidFields
 *
 * @return void
 */
	public function testInvalidFieldsWithFieldListParams() {
		$TestModel = new ValidationTest1();
		$TestModel->validate = $validate = array(
			'title' => array(
				'rule' => 'alphaNumeric',
				'required' => true
			),
			'name' => array(
				'rule' => 'alphaNumeric',
				'required' => true
		));
		$TestModel->set(array('title' => '$$', 'name' => '##'));
		$TestModel->invalidFields(array('fieldList' => array('title')));
		$expected = array(
			'title' => array('This field cannot be left blank')
		);
		$this->assertEquals($TestModel->validationErrors, $expected);
		$TestModel->validationErrors = array();

		$TestModel->invalidFields(array('fieldList' => array('name')));
		$expected = array(
			'name' => array('This field cannot be left blank')
		);
		$this->assertEquals($TestModel->validationErrors, $expected);
		$TestModel->validationErrors = array();

		$TestModel->invalidFields(array('fieldList' => array('name', 'title')));
		$expected = array(
			'name' => array('This field cannot be left blank'),
			'title' => array('This field cannot be left blank')
		);
		$this->assertEquals($TestModel->validationErrors, $expected);
		$TestModel->validationErrors = array();

		$TestModel->whitelist = array('name');
		$TestModel->invalidFields();
		$expected = array('name' => array('This field cannot be left blank'));
		$this->assertEquals($TestModel->validationErrors, $expected);

		$this->assertEquals($TestModel->validate, $validate);
	}

/**
 * Test that invalidFields() integrates well with save().  And that fieldList can be an empty type.
 *
 * @return void
 */
	public function testInvalidFieldsWhitelist() {
		$TestModel = new ValidationTest1();
		$TestModel->validate = array(
			'title' => array(
				'rule' => 'alphaNumeric',
				'required' => true
			),
			'name' => array(
				'rule' => 'alphaNumeric',
				'required' => true
		));

		$TestModel->whitelist = array('name');
		$TestModel->save(array('name' => '#$$#', 'title' => '$$$$'));

		$expected = array('name' => array('This field cannot be left blank'));
		$this->assertEquals($TestModel->validationErrors, $expected);
	}

/**
 * testValidates method
 *
 * @return void
 */
	public function testValidates() {
		$TestModel = new TestValidate();

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
		$this->assertEquals($result, $data);
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
		$this->assertEquals($result, $data);
		$result = $TestModel->validates();
		$this->assertTrue($result);

		$data = array('TestValidate' => array(
			'user_id' => '1',
			'title' => 0,
			'body' => 'body'
		));
		$result = $TestModel->create($data);
		$this->assertEquals($result, $data);
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
		$this->assertEquals($result, $data);
		$result = $TestModel->validates();
		$this->assertTrue($result);

		$data = array('TestValidate' => array(
			'user_id' => '1',
			'title' => 0,
			'body' => 'body',
			'modified' => '2007-05-01'
		));
		$result = $TestModel->create($data);
		$this->assertEquals($result, $data);
		$result = $TestModel->validates();
		$this->assertTrue($result);

		$data = array('TestValidate' => array(
			'user_id' => '1',
			'title' => 0,
			'body' => 'body',
			'modified' => 'invalid-date-here'
		));
		$result = $TestModel->create($data);
		$this->assertEquals($result, $data);
		$result = $TestModel->validates();
		$this->assertFalse($result);

		$data = array('TestValidate' => array(
			'user_id' => '1',
			'title' => 0,
			'body' => 'body',
			'modified' => 0
		));
		$result = $TestModel->create($data);
		$this->assertEquals($result, $data);
		$result = $TestModel->validates();
		$this->assertFalse($result);

		$data = array('TestValidate' => array(
			'user_id' => '1',
			'title' => 0,
			'body' => 'body',
			'modified' => '0'
		));
		$result = $TestModel->create($data);
		$this->assertEquals($result, $data);
		$result = $TestModel->validates();
		$this->assertFalse($result);

		$TestModel->validate['modified'] = array('allowEmpty' => false, 'rule' => 'date');

		$data = array('TestValidate' => array('modified' => null));
		$result = $TestModel->create($data);
		$this->assertEquals($result, $data);
		$result = $TestModel->validates();
		$this->assertFalse($result);

		$data = array('TestValidate' => array('modified' => false));
		$result = $TestModel->create($data);
		$this->assertEquals($result, $data);
		$result = $TestModel->validates();
		$this->assertFalse($result);

		$data = array('TestValidate' => array('modified' => ''));
		$result = $TestModel->create($data);
		$this->assertEquals($result, $data);
		$result = $TestModel->validates();
		$this->assertFalse($result);

		$data = array('TestValidate' => array(
			'modified' => '2007-05-01'
		));
		$result = $TestModel->create($data);
		$this->assertEquals($result, $data);
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
		$this->assertEquals($result, $data);
		$result = $TestModel->validates();
		$this->assertFalse($result);

		$data = array('TestValidate' => array(
			'user_id' => '1',
			'title' => 0,
			'body' => 'body',
			'slug' => 'slug-right-here'
		));
		$result = $TestModel->create($data);
		$this->assertEquals($result, $data);
		$result = $TestModel->validates();
		$this->assertTrue($result);

		$data = array('TestValidate' => array(
			'user_id' => '1',
			'title' => 0,
			'body' => 'body',
			'slug' => 'abcdefghijklmnopqrstuvwxyzabcdefghijklmnopqrstuvwxyz'
		));
		$result = $TestModel->create($data);
		$this->assertEquals($result, $data);
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
		$this->assertEquals($result, $data);
		$result = $TestModel->validates();
		$this->assertFalse($result);

		$data = array('TestValidate' => array(
			'title' => 'title',
			'number' => 0
		));
		$result = $TestModel->create($data);
		$this->assertEquals($result, $data);
		$result = $TestModel->validates();
		$this->assertFalse($result);

		$data = array('TestValidate' => array(
			'title' => 'title',
			'number' => '3'
		));
		$result = $TestModel->create($data);
		$this->assertEquals($result, $data);
		$result = $TestModel->validates();
		$this->assertTrue($result);

		$data = array('TestValidate' => array(
			'title' => 'title',
			'number' => 3
		));
		$result = $TestModel->create($data);
		$this->assertEquals($result, $data);
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
		$this->assertEquals($result, $data);
		$result = $TestModel->validates();
		$this->assertFalse($result);

		$data = array('TestValidate' => array(
			'title' => 'title',
			'number' => 3
		));
		$result = $TestModel->create($data);
		$this->assertEquals($result, $data);
		$result = $TestModel->validates();
		$this->assertFalse($result);

		$TestModel->validate = array(
			'title' => array(
				'allowEmpty' => false,
				'rule' => 'validateTitle'
		));

		$data = array('TestValidate' => array('title' => ''));
		$result = $TestModel->create($data);
		$this->assertEquals($result, $data);
		$result = $TestModel->validates();
		$this->assertFalse($result);

		$data = array('TestValidate' => array('title' => 'new title'));
		$result = $TestModel->create($data);
		$this->assertEquals($result, $data);
		$result = $TestModel->validates();
		$this->assertFalse($result);

		$data = array('TestValidate' => array('title' => 'title-new'));
		$result = $TestModel->create($data);
		$this->assertEquals($result, $data);
		$result = $TestModel->validates();
		$this->assertTrue($result);

		$TestModel->validate = array('title' => array(
			'allowEmpty' => true,
			'rule' => 'validateTitle'
		));
		$data = array('TestValidate' => array('title' => ''));
		$result = $TestModel->create($data);
		$this->assertEquals($result, $data);
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
		$this->assertEquals($result, $data);
		$result = $TestModel->validates();
		$this->assertTrue($result);

		$TestModel->validate = array(
			'title' => array(
				'rule' => array('userDefined', 'Article', 'titleDuplicate')
		));
		$data = array('TestValidate' => array('title' => 'My Article Title'));
		$result = $TestModel->create($data);
		$this->assertEquals($result, $data);
		$result = $TestModel->validates();
		$this->assertFalse($result);

		$data = array('TestValidate' => array(
			'title' => 'My Article With a Different Title'
		));
		$result = $TestModel->create($data);
		$this->assertEquals($result, $data);
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
			'title' => array('tooShort')
		);
		$this->assertEquals($expected, $result);

		$TestModel->validate = array(
			'title' => array(
				'tooShort' => array(
					'rule' => array('minLength', 50),
					'last' => false
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
			'title' => array('tooShort', 'onlyLetters')
		);
		$this->assertEquals($expected, $result);
	}

/**
 * test that validates() checks all the 'with' associations as well for validation
 * as this can cause partial/wrong data insertion.
 *
 * @return void
 */
	public function testValidatesWithAssociations() {
		$this->loadFixtures('Something', 'SomethingElse', 'JoinThing');
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

		$Something = new Something();
		$JoinThing = $Something->JoinThing;

		$JoinThing->validate = array('doomed' => array('rule' => 'notEmpty'));

		$expectedError = array('doomed' => array('This field cannot be left blank'));

		$Something->create();
		$result = $Something->save($data);
		$this->assertFalse($result, 'Save occured even when with models failed. %s');
		$this->assertEquals($JoinThing->validationErrors, $expectedError);
		$count = $Something->find('count', array('conditions' => array('Something.id' => $data['Something']['id'])));
		$this->assertSame($count, 0);

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
		$this->assertEquals($joinRecords, 0, 'Records were saved on the join table. %s');
	}

/**
 * test that saveAll and with models with validation interact well
 *
 * @return void
 */
	public function testValidatesWithModelsAndSaveAll() {
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
		$Something = new Something();
		$JoinThing = $Something->JoinThing;

		$JoinThing->validate = array('doomed' => array('rule' => 'notEmpty'));
		$expectedError = array('doomed' => array('This field cannot be left blank'));

		$Something->create();
		$result = $Something->saveAll($data, array('validate' => 'only'));
		$this->assertFalse($result);
		$this->assertEquals($JoinThing->validationErrors, $expectedError);

		$Something->create();
		$result = $Something->saveAll($data, array('validate' => 'first'));
		$this->assertFalse($result);
		$this->assertEquals($JoinThing->validationErrors, $expectedError);

		$count = $Something->find('count', array('conditions' => array('Something.id' => $data['Something']['id'])));
		$this->assertSame($count, 0);

		$joinRecords = $JoinThing->find('count', array(
			'conditions' => array('JoinThing.something_id' => $data['Something']['id'])
		));
		$this->assertEquals($joinRecords, 0, 'Records were saved on the join table. %s');
	}

/**
 * test that saveAll and with models at initial insert (no id has set yet)
 * with validation interact well
 *
 * @return void
 */
	public function testValidatesWithModelsAndSaveAllWithoutId() {
		$this->loadFixtures('Post', 'Author');

		$data = array(
			'Author' => array(
				'name' => 'Foo Bar',
			),
			'Post' => array(
				array('title' => 'Hello'),
				array('title' => 'World'),
			)
		);
		$Author = new Author();
		$Post = $Author->Post;

		$Post->validate = array('author_id' => array('rule' => 'numeric'));

		$Author->create();
		$result = $Author->saveAll($data, array('validate' => 'only'));
		$this->assertTrue($result);

		$Author->create();
		$result = $Author->saveAll($data, array('validate' => 'first'));
		$this->assertTrue($result);
		$this->assertFalse(is_null($Author->id));

		$id = $Author->id;
		$count = $Author->find('count', array('conditions' => array('Author.id' => $id)));
		$this->assertSame($count, 1);

		$count = $Post->find('count', array(
			'conditions' => array('Post.author_id' => $id)
		));
		$this->assertEquals($count, count($data['Post']));
	}

/**
 * Test that missing validation methods trigger errors in development mode.
 * Helps to make developement easier.
 *
 * @expectedException PHPUnit_Framework_Error
 * @return void
 */
	public function testMissingValidationErrorTriggering() {
		Configure::write('debug', 2);

		$TestModel = new ValidationTest1();
		$TestModel->create(array('title' => 'foo'));
		$TestModel->validate = array(
			'title' => array(
				'rule' => array('thisOneBringsThePain'),
				'required' => true
			)
		);
		$TestModel->invalidFields(array('fieldList' => array('title')));
	}

/**
 * Test that missing validation methods does not trigger errors in production mode.
 *
 * @return void
 */
	public function testMissingValidationErrorNoTriggering() {
		Configure::write('debug', 0);
		$TestModel = new ValidationTest1();
		$TestModel->create(array('title' => 'foo'));
		$TestModel->validate = array(
			'title' => array(
				'rule' => array('thisOneBringsThePain'),
				'required' => true
			)
		);
		$TestModel->invalidFields(array('fieldList' => array('title')));
		$this->assertEquals($TestModel->validationErrors, array());
	}

/**
 * Test placeholder replacement when validation message is an array
 *
 * @return void
 */
	public function testValidationMessageAsArray() {
		$TestModel = new ValidationTest1();
		$TestModel->validate = array(
			'title' => array(
				'minLength' => array(
					'rule' => array('minLength', 6),
					'required' => true,
					'message' => 'Minimum length allowed is %d chars',
					'last' => false
				),
				'between' => array(
					'rule' => array('between', 5, 15),
					'message' => array('You may enter up to %s chars (minimum is %s chars)', 14, 6)
				)
			)
		);

		$TestModel->create();
		$TestModel->invalidFields();
		$expected = array(
			'title' => array(
				'Minimum length allowed is 6 chars',
			)
		);
		$this->assertEquals($TestModel->validationErrors, $expected);

		$TestModel->create(array('title' => 'foo'));
		$TestModel->invalidFields();
		$expected = array(
			'title' => array(
				'Minimum length allowed is 6 chars',
				'You may enter up to 14 chars (minimum is 6 chars)'
			)
		);
		$this->assertEquals($TestModel->validationErrors, $expected);
	}

/**
 * Test for 'on' => [create|update] in validation rules.
 *
 * @return void
 */
	function testStateValidation() {
		$this->loadFixtures('Article');
		$Article = new Article();

		$data = array(
			'Article' => array(
				'title' => '',
				'body' => 'Extra Fields Body',
				'published' => '1'
			)
		);

		$Article->validate = array(
			'title' => array(
				'notempty' => array(
					'rule' => 'notEmpty',
					'on' => 'create'
				)
			)
		);

		$Article->create($data);
		$this->assertFalse($Article->validates());

		$Article->save(null, array('validate' => false));
		$data['Article']['id'] = $Article->id;
		$Article->set($data);
		$this->assertTrue($Article->validates());

		unset($data['Article']['id']);
		$Article->validate = array(
			'title' => array(
				'notempty' => array(
					'rule' => 'notEmpty',
					'on' => 'update'
				)
			)
		);

		$Article->create($data);
		$this->assertTrue($Article->validates());

		$Article->save(null, array('validate' => false));
		$data['Article']['id'] = $Article->id;
		$Article->set($data);
		$this->assertFalse($Article->validates());
	}

}
