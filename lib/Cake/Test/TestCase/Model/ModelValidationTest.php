<?php
/**
 * ModelValidationTest file
 *
 * PHP 5
 *
 * CakePHP(tm) Tests <http://book.cakephp.org/2.0/en/development/testing.html>
 * Copyright 2005-2012, Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice
 *
 * @copyright     Copyright 2005-2012, Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://book.cakephp.org/2.0/en/development/testing.html CakePHP(tm) Tests
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
				'required' => true,
				'message' => null
			),
			'or' => true,
			'ignoreOnSame' => 'id'
		);
		$this->assertEquals($expected, $TestModel->validatorParams);

		$TestModel->validate['title'] = array(
			'rule' => 'customValidatorWithMessage',
			'required' => true
		);
		$expected = array(
			'title' => array('This field will *never* validate! Muhahaha!')
		);

		$this->assertEquals($expected, $TestModel->invalidFields());

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
				'required' => true,
				'message' => null
			),
			'six' => 6
		);
		$this->assertEquals($expected, $TestModel->validatorParams);

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
				'required' => true,
				'message' => null
			)
		);
		$this->assertEquals($expected, $TestModel->validatorParams);
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
		$this->assertEquals($expected, $TestModel->validationErrors);
		$TestModel->validationErrors = array();

		$TestModel->invalidFields(array('fieldList' => array('name')));
		$expected = array(
			'name' => array('This field cannot be left blank')
		);
		$this->assertEquals($expected, $TestModel->validationErrors);
		$TestModel->validationErrors = array();

		$TestModel->invalidFields(array('fieldList' => array('name', 'title')));
		$expected = array(
			'name' => array('This field cannot be left blank'),
			'title' => array('This field cannot be left blank')
		);
		$this->assertEquals($expected, $TestModel->validationErrors);
		$TestModel->validationErrors = array();

		$TestModel->whitelist = array('name');
		$TestModel->invalidFields();
		$expected = array('name' => array('This field cannot be left blank'));
		$this->assertEquals($expected, $TestModel->validationErrors);

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
		$this->assertEquals($expected, $TestModel->validationErrors);
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
		$this->assertEquals($data, $result);
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
		$this->assertEquals($data, $result);
		$result = $TestModel->validates();
		$this->assertTrue($result);

		$data = array('TestValidate' => array(
			'user_id' => '1',
			'title' => 0,
			'body' => 'body'
		));
		$result = $TestModel->create($data);
		$this->assertEquals($data, $result);
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
		$this->assertEquals($data, $result);
		$result = $TestModel->validates();
		$this->assertTrue($result);

		$data = array('TestValidate' => array(
			'user_id' => '1',
			'title' => 0,
			'body' => 'body',
			'modified' => '2007-05-01'
		));
		$result = $TestModel->create($data);
		$this->assertEquals($data, $result);
		$result = $TestModel->validates();
		$this->assertTrue($result);

		$data = array('TestValidate' => array(
			'user_id' => '1',
			'title' => 0,
			'body' => 'body',
			'modified' => 'invalid-date-here'
		));
		$result = $TestModel->create($data);
		$this->assertEquals($data, $result);
		$result = $TestModel->validates();
		$this->assertFalse($result);

		$data = array('TestValidate' => array(
			'user_id' => '1',
			'title' => 0,
			'body' => 'body',
			'modified' => 0
		));
		$result = $TestModel->create($data);
		$this->assertEquals($data, $result);
		$result = $TestModel->validates();
		$this->assertFalse($result);

		$data = array('TestValidate' => array(
			'user_id' => '1',
			'title' => 0,
			'body' => 'body',
			'modified' => '0'
		));
		$result = $TestModel->create($data);
		$this->assertEquals($data, $result);
		$result = $TestModel->validates();
		$this->assertFalse($result);

		$TestModel->validate['modified'] = array('allowEmpty' => false, 'rule' => 'date');

		$data = array('TestValidate' => array('modified' => null));
		$result = $TestModel->create($data);
		$this->assertEquals($data, $result);
		$result = $TestModel->validates();
		$this->assertFalse($result);

		$data = array('TestValidate' => array('modified' => false));
		$result = $TestModel->create($data);
		$this->assertEquals($data, $result);
		$result = $TestModel->validates();
		$this->assertFalse($result);

		$data = array('TestValidate' => array('modified' => ''));
		$result = $TestModel->create($data);
		$this->assertEquals($data, $result);
		$result = $TestModel->validates();
		$this->assertFalse($result);

		$data = array('TestValidate' => array(
			'modified' => '2007-05-01'
		));
		$result = $TestModel->create($data);
		$this->assertEquals($data, $result);
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
		$this->assertEquals($data, $result);
		$result = $TestModel->validates();
		$this->assertFalse($result);

		$data = array('TestValidate' => array(
			'user_id' => '1',
			'title' => 0,
			'body' => 'body',
			'slug' => 'slug-right-here'
		));
		$result = $TestModel->create($data);
		$this->assertEquals($data, $result);
		$result = $TestModel->validates();
		$this->assertTrue($result);

		$data = array('TestValidate' => array(
			'user_id' => '1',
			'title' => 0,
			'body' => 'body',
			'slug' => 'abcdefghijklmnopqrstuvwxyzabcdefghijklmnopqrstuvwxyz'
		));
		$result = $TestModel->create($data);
		$this->assertEquals($data, $result);
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
		$this->assertEquals($data, $result);
		$result = $TestModel->validates();
		$this->assertFalse($result);

		$data = array('TestValidate' => array(
			'title' => 'title',
			'number' => 0
		));
		$result = $TestModel->create($data);
		$this->assertEquals($data, $result);
		$result = $TestModel->validates();
		$this->assertFalse($result);

		$data = array('TestValidate' => array(
			'title' => 'title',
			'number' => '3'
		));
		$result = $TestModel->create($data);
		$this->assertEquals($data, $result);
		$result = $TestModel->validates();
		$this->assertTrue($result);

		$data = array('TestValidate' => array(
			'title' => 'title',
			'number' => 3
		));
		$result = $TestModel->create($data);
		$this->assertEquals($data, $result);
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
		$this->assertEquals($data, $result);
		$result = $TestModel->validates();
		$this->assertFalse($result);

		$data = array('TestValidate' => array(
			'title' => 'title',
			'number' => 3
		));
		$result = $TestModel->create($data);
		$this->assertEquals($data, $result);
		$result = $TestModel->validates();
		$this->assertFalse($result);

		$TestModel->validate = array(
			'title' => array(
				'allowEmpty' => false,
				'rule' => 'validateTitle'
		));

		$data = array('TestValidate' => array('title' => ''));
		$result = $TestModel->create($data);
		$this->assertEquals($data, $result);
		$result = $TestModel->validates();
		$this->assertFalse($result);

		$data = array('TestValidate' => array('title' => 'new title'));
		$result = $TestModel->create($data);
		$this->assertEquals($data, $result);
		$result = $TestModel->validates();
		$this->assertFalse($result);

		$data = array('TestValidate' => array('title' => 'title-new'));
		$result = $TestModel->create($data);
		$this->assertEquals($data, $result);
		$result = $TestModel->validates();
		$this->assertTrue($result);

		$TestModel->validate = array('title' => array(
			'allowEmpty' => true,
			'rule' => 'validateTitle'
		));
		$data = array('TestValidate' => array('title' => ''));
		$result = $TestModel->create($data);
		$this->assertEquals($data, $result);
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
		$this->assertEquals($data, $result);
		$result = $TestModel->validates();
		$this->assertTrue($result);

		$TestModel->validate = array(
			'title' => array(
				'rule' => array('userDefined', 'Article', 'titleDuplicate')
		));
		$data = array('TestValidate' => array('title' => 'My Article Title'));
		$result = $TestModel->create($data);
		$this->assertEquals($data, $result);
		$result = $TestModel->validates();
		$this->assertFalse($result);

		$data = array('TestValidate' => array(
			'title' => 'My Article With a Different Title'
		));
		$result = $TestModel->create($data);
		$this->assertEquals($data, $result);
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
		$result = $TestModel->validationErrors;
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
		$this->assertFalse($result, 'Save occurred even when with models failed. %s');
		$this->assertEquals($expectedError, $JoinThing->validationErrors);
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
		$this->assertFalse($result, 'Save occurred even when with models failed. %s');

		$joinRecords = $JoinThing->find('count', array(
			'conditions' => array('JoinThing.something_id' => $data['Something']['id'])
		));
		$this->assertEquals(0, $joinRecords, 'Records were saved on the join table. %s');
	}

/**
 * test that saveAll and with models with validation interact well
 *
 * @return void
 */
	public function testValidatesWithModelsAndSaveAll() {
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
		$result = $Something->saveAll($data, array('validate' => 'only'));
		$this->assertFalse($result);
		$result = $Something->validateAssociated($data);
		$this->assertFalse($result);
		$this->assertEquals($expectedError, $JoinThing->validationErrors);
		$result = $Something->validator()->validateAssociated($data);
		$this->assertFalse($result);

		$Something->create();
		$result = $Something->saveAll($data, array('validate' => 'first'));
		$this->assertFalse($result);
		$this->assertEquals($expectedError, $JoinThing->validationErrors);

		$count = $Something->find('count', array('conditions' => array('Something.id' => $data['Something']['id'])));
		$this->assertSame($count, 0);

		$joinRecords = $JoinThing->find('count', array(
			'conditions' => array('JoinThing.something_id' => $data['Something']['id'])
		));
		$this->assertEquals(0, $joinRecords, 'Records were saved on the join table. %s');
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
		$result = $Author->validateAssociated($data);
		$this->assertTrue($result);
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
 * Helps to make development easier.
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
		$this->assertEquals(array(), $TestModel->validationErrors);
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
		$expected = array(
			'title' => array(
				'Minimum length allowed is 6 chars',
			)
		);
		$TestModel->invalidFields();
		$this->assertEquals($expected, $TestModel->validationErrors);

		$TestModel->create(array('title' => 'foo'));
		$expected = array(
			'title' => array(
				'Minimum length allowed is 6 chars',
				'You may enter up to 14 chars (minimum is 6 chars)'
			)
		);
		$TestModel->invalidFields();
		$this->assertEquals($expected, $TestModel->validationErrors);
	}

/**
 * Test validation message translation
 *
 * @return void
 */
	public function testValidationMessageTranslation() {
		$lang = Configure::read('Config.language');
		Configure::write('Config.language', 'en');
		App::build(array(
			'Locale' => array(CAKE . 'Test' . DS . 'test_app' . DS . 'Locale' . DS),
		), App::RESET);

		$TestModel = new ValidationTest1();
		$TestModel->validationDomain = 'validation_messages';
		$TestModel->validate = array(
			'title' => array(
				array(
					'rule' => array('customValidationMethod', 'arg1'),
					'required' => true,
					'message' => 'Validation failed: %s'
				)
			)
		);

		$TestModel->create();
		$expected = array(
			'title' => array(
				'Translated validation failed: Translated arg1',
			)
		);
		$TestModel->invalidFields();
		$this->assertEquals($expected, $TestModel->validationErrors);

		$TestModel->validationDomain = 'default';
		Configure::write('Config.language', $lang);
		App::build();
	}

/**
 * Test for 'on' => [create|update] in validation rules.
 *
 * @return void
 */
	public function testStateValidation() {
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

/**
 * Test for 'required' => [create|update] in validation rules.
 *
 * @return void
 */
	public function testStateRequiredValidation() {
		$this->loadFixtures('Article');
		$Article = new Article();

		// no title field present
		$data = array(
			'Article' => array(
				'body' => 'Extra Fields Body',
				'published' => '1'
			)
		);

		$Article->validate = array(
			'title' => array(
				'notempty' => array(
					'rule' => 'notEmpty',
					'required' => 'create'
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
					'required' => 'update'
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

/**
 * Test that 'required' and 'on' are not conflicting
 *
 * @return void
 */
	public function testOnRequiredConflictValidation() {
		$this->loadFixtures('Article');
		$Article = new Article();

		// no title field present
		$data = array(
			'Article' => array(
				'body' => 'Extra Fields Body',
				'published' => '1'
			)
		);

		$Article->validate = array(
			'title' => array(
				'notempty' => array(
					'rule' => 'notEmpty',
					'required' => 'create',
					'on' => 'create'
				)
			)
		);

		$Article->create($data);
		$this->assertFalse($Article->validates());

		$Article->validate = array(
			'title' => array(
				'notempty' => array(
					'rule' => 'notEmpty',
					'required' => 'update',
					'on' => 'create'
				)
			)
		);

		$Article->create($data);
		$this->assertTrue($Article->validates());

		$Article->validate = array(
			'title' => array(
				'notempty' => array(
					'rule' => 'notEmpty',
					'required' => 'create',
					'on' => 'update'
				)
			)
		);

		$Article->create($data);
		$this->assertTrue($Article->validates());

		$Article->validate = array(
			'title' => array(
				'notempty' => array(
					'rule' => 'notEmpty',
					'required' => 'update',
					'on' => 'update'
				)
			)
		);

		$Article->create($data);
		$this->assertTrue($Article->validates());

		$Article->validate = array(
			'title' => array(
				'notempty' => array(
					'rule' => 'notEmpty',
					'required' => 'create',
					'on' => 'create'
				)
			)
		);

		$Article->save(null, array('validate' => false));
		$data['Article']['id'] = $Article->id;
		$Article->set($data);
		$this->assertTrue($Article->validates());

		$Article->validate = array(
			'title' => array(
				'notempty' => array(
					'rule' => 'notEmpty',
					'required' => 'update',
					'on' => 'create'
				)
			)
		);

		$Article->set($data);
		$this->assertTrue($Article->validates());

		$Article->validate = array(
			'title' => array(
				'notempty' => array(
					'rule' => 'notEmpty',
					'required' => 'create',
					'on' => 'update'
				)
			)
		);

		$Article->set($data);
		$this->assertTrue($Article->validates());

		$Article->validate = array(
			'title' => array(
				'notempty' => array(
					'rule' => 'notEmpty',
					'required' => 'update',
					'on' => 'update'
				)
			)
		);

		$Article->set($data);
		$this->assertFalse($Article->validates());
	}

/**
 * testSaveAllDeepValidateOnly
 * tests the validate methods with deeper recursive data
 *
 * @return void
 */
	public function testSaveAllDeepValidateOnly() {
		$this->loadFixtures('Article', 'Comment', 'User', 'Attachment');
		$TestModel = new Article();
		$TestModel->hasMany['Comment']['order'] = array('Comment.created' => 'ASC');
		$TestModel->hasAndBelongsToMany = array();
		$TestModel->Comment->Attachment->validate['attachment'] = 'notEmpty';
		$TestModel->Comment->validate['comment'] = 'notEmpty';

		$data = array(
			'Article' => array('id' => 2),
			'Comment' => array(
				array('comment' => 'First new comment', 'published' => 'Y', 'User' => array('user' => 'newuser', 'password' => 'newuserpass')),
				array('comment' => 'Second new comment', 'published' => 'Y', 'user_id' => 2)
			)
		);
		$result = $TestModel->saveAll($data, array('validate' => 'only', 'deep' => true));
		$this->assertTrue($result);
		$result = $TestModel->validateAssociated($data, array('deep' => true));
		$this->assertTrue($result);

		$data = array(
			'Article' => array('id' => 2),
			'Comment' => array(
				array('comment' => 'First new comment', 'published' => 'Y', 'User' => array('user' => '', 'password' => 'newuserpass')),
				array('comment' => 'Second new comment', 'published' => 'Y', 'user_id' => 2)
			)
		);
		$result = $TestModel->saveAll($data, array('validate' => 'only', 'deep' => true));
		$this->assertFalse($result);
		$result = $TestModel->validateAssociated($data, array('deep' => true));
		$this->assertFalse($result);

		$data = array(
			'Article' => array('id' => 2),
			'Comment' => array(
				array('comment' => 'First new comment', 'published' => 'Y', 'User' => array('user' => 'newuser', 'password' => 'newuserpass')),
				array('comment' => 'Second new comment', 'published' => 'Y', 'user_id' => 2)
			)
		);
		$expected = array(
			'Article' => true,
			'Comment' => array(
				true,
				true
			)
		);
		$result = $TestModel->saveAll($data, array('validate' => 'only', 'atomic' => false, 'deep' => true));
		$this->assertSame($expected, $result);
		$result = $TestModel->validateAssociated($data, array('atomic' => false, 'deep' => true));
		$this->assertSame($expected, $result);

		$data = array(
			'Article' => array('id' => 2),
			'Comment' => array(
				array('comment' => 'First new comment', 'published' => 'Y', 'User' => array('user' => '', 'password' => 'newuserpass')),
				array('comment' => 'Second new comment', 'published' => 'Y', 'user_id' => 2)
			)
		);
		$expected = array(
			'Article' => true,
			'Comment' => array(
				false,
				true
			)
		);
		$result = $TestModel->saveAll($data, array('validate' => 'only', 'atomic' => false, 'deep' => true));
		$this->assertSame($expected, $result);
		$result = $TestModel->validateAssociated($data, array('atomic' => false, 'deep' => true));
		$this->assertSame($expected, $result);

		$data = array(
			'Article' => array('id' => 2),
			'Comment' => array(
				array('comment' => 'Third new comment', 'published' => 'Y', 'user_id' => 5),
				array('comment' => 'Fourth new comment', 'published' => 'Y', 'user_id' => 2, 'Attachment' => array('attachment' => 'deepsaved'))
			)
		);
		$result = $TestModel->saveAll($data, array('validate' => 'only', 'deep' => true));
		$this->assertTrue($result);
		$result = $TestModel->validateAssociated($data, array('deep' => true));
		$this->assertTrue($result);

		$data = array(
			'Article' => array('id' => 2),
			'Comment' => array(
				array('comment' => 'Third new comment', 'published' => 'Y', 'user_id' => 5),
				array('comment' => 'Fourth new comment', 'published' => 'Y', 'user_id' => 2, 'Attachment' => array('attachment' => ''))
			)
		);
		$result = $TestModel->saveAll($data, array('validate' => 'only', 'deep' => true));
		$this->assertFalse($result);
		$result = $TestModel->validateAssociated($data, array('deep' => true));
		$this->assertFalse($result);

		$data = array(
			'Article' => array('id' => 2),
			'Comment' => array(
				array('comment' => 'Third new comment', 'published' => 'Y', 'user_id' => 5),
				array('comment' => 'Fourth new comment', 'published' => 'Y', 'user_id' => 2, 'Attachment' => array('attachment' => 'deepsave'))
			)
		);
		$expected = array(
			'Article' => true,
			'Comment' => array(
				true,
				true
			)
		);
		$result = $TestModel->saveAll($data, array('validate' => 'only', 'atomic' => false, 'deep' => true));
		$this->assertSame($expected, $result);
		$result = $TestModel->validateAssociated($data, array('atomic' => false, 'deep' => true));
		$this->assertSame($expected, $result);

		$data = array(
			'Article' => array('id' => 2),
			'Comment' => array(
				array('comment' => 'Third new comment', 'published' => 'Y', 'user_id' => 5),
				array('comment' => 'Fourth new comment', 'published' => 'Y', 'user_id' => 2, 'Attachment' => array('attachment' => ''))
			)
		);
		$expected = array(
			'Article' => true,
			'Comment' => array(
				true,
				false
			)
		);
		$result = $TestModel->saveAll($data, array('validate' => 'only', 'atomic' => false, 'deep' => true));
		$this->assertSame($expected, $result);
		$result = $TestModel->validateAssociated($data, array('atomic' => false, 'deep' => true));
		$this->assertSame($expected, $result);

		$expected = array(
			'Comment' => array(
				1 => array(
					'Attachment' => array(
						'attachment' => array('This field cannot be left blank')
					)
				)
			)
		);
		$result = $TestModel->validationErrors;
		$this->assertSame($expected, $result);

		$data = array(
			'Attachment' => array(
				'attachment' => 'deepsave insert',
			),
			'Comment' => array(
				'comment' => 'First comment deepsave insert',
				'published' => 'Y',
				'user_id' => 5,
				'Article' => array(
					'title' => 'First Article deepsave insert',
					'body' => 'First Article Body deepsave insert',
					'User' => array(
						'user' => 'deepsave',
						'password' => 'magic'
					),
				),
			)
		);

		$result = $TestModel->Comment->Attachment->saveAll($data, array('validate' => 'only', 'deep' => true));
		$this->assertTrue($result);
		$result = $TestModel->Comment->Attachment->validateAssociated($data, array('deep' => true));
		$this->assertTrue($result);

		$expected = array(
			'Attachment' => true,
			'Comment' => true
		);
		$result = $TestModel->Comment->Attachment->saveAll($data, array('validate' => 'only', 'atomic' => false, 'deep' => true));
		$this->assertSame($expected, $result);
		$result = $TestModel->Comment->Attachment->validateAssociated($data, array('atomic' => false, 'deep' => true));
		$this->assertSame($expected, $result);

		$data = array(
			'Attachment' => array(
				'attachment' => 'deepsave insert',
			),
			'Comment' => array(
				'comment' => 'First comment deepsave insert',
				'published' => 'Y',
				'user_id' => 5,
				'Article' => array(
					'title' => 'First Article deepsave insert',
					'body' => 'First Article Body deepsave insert',
					'User' => array(
						'user' => '',
						'password' => 'magic'
					),
				),
			)
		);

		$result = $TestModel->Comment->Attachment->saveAll($data, array('validate' => 'only', 'deep' => true));
		$this->assertFalse($result);
		$result = $TestModel->Comment->Attachment->validateAssociated($data, array('deep' => true));
		$this->assertFalse($result);

		$result = $TestModel->Comment->Attachment->validationErrors;
		$expected = array(
			'Comment' => array(
				'Article' => array(
					'User' => array(
						'user' => array('This field cannot be left blank')
					)
				)
			)
		);
		$this->assertSame($expected, $result);

		$expected = array(
			'Attachment' => true,
			'Comment' => false
		);
		$result = $TestModel->Comment->Attachment->saveAll($data, array('validate' => 'only', 'atomic' => false, 'deep' => true));
		$this->assertEquals($expected, $result);
		$result = $TestModel->Comment->Attachment->validateAssociated($data, array('atomic' => false, 'deep' => true));
		$this->assertEquals($expected, $result);

		$data['Comment']['Article']['body'] = '';
		$result = $TestModel->Comment->Attachment->saveAll($data, array('validate' => 'only', 'deep' => true));
		$this->assertFalse($result);
		$result = $TestModel->Comment->Attachment->validateAssociated($data, array('deep' => true));
		$this->assertFalse($result);

		$result = $TestModel->Comment->Attachment->validationErrors;
		$expected = array(
			'Comment' => array(
				'Article' => array(
					'body' => array('This field cannot be left blank'),
					'User' => array(
						'user' => array('This field cannot be left blank')
					)
				)
			)
		);
		$this->assertSame($expected, $result);

		$expected = array(
			'Attachment' => true,
			'Comment' => false
		);
		$result = $TestModel->Comment->Attachment->saveAll($data, array('validate' => 'only', 'atomic' => false, 'deep' => true));
		$this->assertEquals($expected, $result);
		$result = $TestModel->Comment->Attachment->validateAssociated($data, array('atomic' => false, 'deep' => true));
		$this->assertEquals($expected, $result);

		$data['Comment']['comment'] = '';
		$result = $TestModel->Comment->Attachment->saveAll($data, array('validate' => 'only', 'deep' => true));
		$this->assertFalse($result);
		$result = $TestModel->Comment->Attachment->validateAssociated($data, array('deep' => true));
		$this->assertFalse($result);

		$result = $TestModel->Comment->Attachment->validationErrors;
		$expected = array(
			'Comment' => array(
				'comment' => array('This field cannot be left blank'),
				'Article' => array(
					'body' => array('This field cannot be left blank'),
					'User' => array(
						'user' => array('This field cannot be left blank')
					)
				)
			)
		);
		$this->assertSame($expected, $result);

		$expected = array(
			'Attachment' => true,
			'Comment' => false
		);
		$result = $TestModel->Comment->Attachment->saveAll($data, array('validate' => 'only', 'atomic' => false, 'deep' => true));
		$this->assertEquals($expected, $result);
		$result = $TestModel->Comment->Attachment->validateAssociated($data, array('atomic' => false, 'deep' => true));
		$this->assertEquals($expected, $result);

		$data['Attachment']['attachment'] = '';
		$result = $TestModel->Comment->Attachment->saveAll($data, array('validate' => 'only', 'deep' => true));
		$this->assertFalse($result);
		$result = $TestModel->Comment->Attachment->validateAssociated($data, array('deep' => true));
		$this->assertFalse($result);

		$result = $TestModel->Comment->Attachment->validationErrors;
		$expected = array(
			'attachment' => array('This field cannot be left blank'),
			'Comment' => array(
				'comment' => array('This field cannot be left blank'),
				'Article' => array(
					'body' => array('This field cannot be left blank'),
					'User' => array(
						'user' => array('This field cannot be left blank')
					)
				)
			)
		);
		$this->assertSame($expected, $result);

		$result = $TestModel->Comment->validationErrors;
		$expected = array(
			'comment' => array('This field cannot be left blank'),
			'Article' => array(
					'body' => array('This field cannot be left blank'),
					'User' => array(
						'user' => array('This field cannot be left blank')
					)
				)
		);
		$this->assertSame($expected, $result);

		$expected = array(
			'Attachment' => false,
			'Comment' => false
		);
		$result = $TestModel->Comment->Attachment->saveAll($data, array('validate' => 'only', 'atomic' => false, 'deep' => true));
		$this->assertEquals($expected, $result);
		$result = $TestModel->Comment->Attachment->validateAssociated($data, array('atomic' => false, 'deep' => true));
		$this->assertEquals($expected, $result);
	}

/**
 * testSaveAllNotDeepValidateOnly
 * tests the validate methods to not validate deeper recursive data
 *
 * @return void
 */
	public function testSaveAllNotDeepValidateOnly() {
		$this->loadFixtures('Article', 'Comment', 'User', 'Attachment');
		$TestModel = new Article();
		$TestModel->hasMany['Comment']['order'] = array('Comment.created' => 'ASC');
		$TestModel->hasAndBelongsToMany = array();
		$TestModel->Comment->Attachment->validate['attachment'] = 'notEmpty';
		$TestModel->Comment->validate['comment'] = 'notEmpty';

		$data = array(
			'Article' => array('id' => 2, 'body' => ''),
			'Comment' => array(
				array('comment' => 'First new comment', 'published' => 'Y', 'User' => array('user' => '', 'password' => 'newuserpass')),
				array('comment' => 'Second new comment', 'published' => 'Y', 'user_id' => 2)
			)
		);
		$result = $TestModel->saveAll($data, array('validate' => 'only', 'deep' => false));
		$this->assertFalse($result);
		$result = $TestModel->validateAssociated($data, array('deep' => false));
		$this->assertFalse($result);

		$expected = array('body' => array('This field cannot be left blank'));
		$result = $TestModel->validationErrors;
		$this->assertSame($expected, $result);

		$data = array(
			'Article' => array('id' => 2, 'body' => 'Ignore invalid user data'),
			'Comment' => array(
				array('comment' => 'First new comment', 'published' => 'Y', 'User' => array('user' => '', 'password' => 'newuserpass')),
				array('comment' => 'Second new comment', 'published' => 'Y', 'user_id' => 2)
			)
		);
		$result = $TestModel->saveAll($data, array('validate' => 'only', 'deep' => false));
		$this->assertTrue($result);
		$result = $TestModel->validateAssociated($data, array('deep' => false));
		$this->assertTrue($result);

		$data = array(
			'Article' => array('id' => 2, 'body' => 'Ignore invalid user data'),
			'Comment' => array(
				array('comment' => 'First new comment', 'published' => 'Y', 'User' => array('user' => '', 'password' => 'newuserpass')),
				array('comment' => 'Second new comment', 'published' => 'Y', 'user_id' => 2)
			)
		);
		$expected = array(
			'Article' => true,
			'Comment' => array(
				true,
				true
			)
		);
		$result = $TestModel->saveAll($data, array('validate' => 'only', 'atomic' => false, 'deep' => false));
		$this->assertSame($expected, $result);
		$result = $TestModel->validateAssociated($data, array('atomic' => false, 'deep' => false));
		$this->assertSame($expected, $result);

		$data = array(
			'Article' => array('id' => 2, 'body' => 'Ignore invalid attachment data'),
			'Comment' => array(
				array('comment' => 'Third new comment', 'published' => 'Y', 'user_id' => 5),
				array('comment' => 'Fourth new comment', 'published' => 'Y', 'user_id' => 2, 'Attachment' => array('attachment' => ''))
			)
		);
		$result = $TestModel->saveAll($data, array('validate' => 'only', 'deep' => false));
		$this->assertTrue($result);
		$result = $TestModel->validateAssociated($data, array('deep' => false));
		$this->assertTrue($result);

		$data = array(
			'Article' => array('id' => 2, 'body' => 'Ignore invalid attachment data'),
			'Comment' => array(
				array('comment' => 'Third new comment', 'published' => 'Y', 'user_id' => 5),
				array('comment' => 'Fourth new comment', 'published' => 'Y', 'user_id' => 2, 'Attachment' => array('attachment' => ''))
			)
		);
		$expected = array(
			'Article' => true,
			'Comment' => array(
				true,
				true
			)
		);
		$result = $TestModel->saveAll($data, array('validate' => 'only', 'atomic' => false, 'deep' => false));
		$this->assertSame($expected, $result);
		$result = $TestModel->validateAssociated($data, array('atomic' => false, 'deep' => false));
		$this->assertSame($expected, $result);

		$expected = array();
		$result = $TestModel->validationErrors;
		$this->assertSame($expected, $result);

		$data = array(
			'Attachment' => array(
				'attachment' => 'deepsave insert',
			),
			'Comment' => array(
				'comment' => 'First comment deepsave insert',
				'published' => 'Y',
				'user_id' => 5,
				'Article' => array(
					'title' => 'First Article deepsave insert ignored',
					'body' => 'First Article Body deepsave insert',
					'User' => array(
						'user' => '',
						'password' => 'magic'
					),
				),
			)
		);

		$result = $TestModel->Comment->Attachment->saveAll($data, array('validate' => 'only', 'deep' => false));
		$this->assertTrue($result);
		$result = $TestModel->Comment->Attachment->validateAssociated($data, array('deep' => false));
		$this->assertTrue($result);

		$result = $TestModel->Comment->Attachment->validationErrors;
		$expected = array();
		$this->assertSame($expected, $result);

		$expected = array(
			'Attachment' => true,
			'Comment' => true
		);
		$result = $TestModel->Comment->Attachment->saveAll($data, array('validate' => 'only', 'atomic' => false, 'deep' => false));
		$this->assertEquals($expected, $result);
		$result = $TestModel->Comment->Attachment->validateAssociated($data, array('atomic' => false, 'deep' => false));
		$this->assertEquals($expected, $result);

		$data['Comment']['Article']['body'] = '';
		$result = $TestModel->Comment->Attachment->saveAll($data, array('validate' => 'only', 'deep' => false));
		$this->assertTrue($result);
		$result = $TestModel->Comment->Attachment->validateAssociated($data, array('deep' => false));
		$this->assertTrue($result);

		$result = $TestModel->Comment->Attachment->validationErrors;
		$expected = array();
		$this->assertSame($expected, $result);

		$expected = array(
			'Attachment' => true,
			'Comment' => true
		);
		$result = $TestModel->Comment->Attachment->saveAll($data, array('validate' => 'only', 'atomic' => false, 'deep' => false));
		$this->assertEquals($expected, $result);
		$result = $TestModel->Comment->Attachment->validateAssociated($data, array('atomic' => false, 'deep' => false));
		$this->assertEquals($expected, $result);
	}

/**
 * testValidateAssociated method
 *
 * @return void
 */
	public function testValidateAssociated() {
		$this->loadFixtures('Comment', 'Attachment');
		$TestModel = new Comment();
		$TestModel->Attachment->validate = array('attachment' => 'notEmpty');

		$data = array(
			'Comment' => array(
				'comment' => 'This is the comment'
			),
			'Attachment' => array(
				'attachment' => ''
			)
		);
		$result = $TestModel->saveAll($data, array('validate' => 'only'));
		$this->assertFalse($result);
		$result = $TestModel->validateAssociated($data);
		$this->assertFalse($result);

		$TestModel->validate = array('comment' => 'notEmpty');
		$record = array(
			'Comment' => array(
				'user_id' => 1,
				'article_id' => 1,
				'comment' => '',
			),
			'Attachment' => array(
				'attachment' => ''
			)
		);
		$result = $TestModel->saveAll($record, array('validate' => 'only'));
		$this->assertFalse($result);
		$result = $TestModel->validateAssociated($record);
		$this->assertFalse($result);

		$fieldList = array(
			'Comment' => array('id', 'article_id', 'user_id'),
			'Attachment' => array('comment_id')
		);
		$result = $TestModel->saveAll($record, array(
			'fieldList' => $fieldList, 'validate' => 'only'
		));
		$this->assertTrue($result);
		$this->assertEmpty($TestModel->validationErrors);
		$result = $TestModel->validateAssociated($record, array('fieldList' => $fieldList));
		$this->assertTrue($result);
		$this->assertEmpty($TestModel->validationErrors);

		$TestModel = new Article();
		$TestModel->belongsTo = $TestModel->hasAndBelongsToMany = array();
		$TestModel->Comment->validate = array('comment' => 'notEmpty');
		$data = array(
			'Article' => array('id' => 2),
			'Comment' => array(
				array(
					'id' => 1,
					'comment' => '',
					'published' => 'Y',
					'user_id' => 1,
				),
				array(
					'id' => 2,
					'comment' =>
					'comment',
					'published' => 'Y',
					'user_id' => 1
				),
				array(
					'id' => 3,
					'comment' => '',
					'published' => 'Y',
					'user_id' => 1
		)));
		$result = $TestModel->saveAll($data, array('validate' => 'only'));
		$this->assertFalse($result);
		$result = $TestModel->validateAssociated($data);
		$this->assertFalse($result);

		$expected = array(
			'Article' => true,
			'Comment' => array(false, true, false)
		);
		$result = $TestModel->saveAll($data, array('atomic' => false, 'validate' => 'only'));
		$this->assertSame($expected, $result);
		$result = $TestModel->validateAssociated($data, array('atomic' => false));
		$this->assertSame($expected, $result);

		$expected = array('Comment' => array(
			0 => array('comment' => array('This field cannot be left blank')),
			2 => array('comment' => array('This field cannot be left blank'))
		));
		$this->assertEquals($expected['Comment'], $TestModel->Comment->validationErrors);

		$model = new Comment();
		$model->deleteAll(true);
		$model->validate = array('comment' => 'notEmpty');
		$model->Attachment->validate = array('attachment' => 'notEmpty');
		$model->Attachment->bindModel(array('belongsTo' => array('Comment')));
		$expected = array(
			'comment' => array('This field cannot be left blank'),
			'Attachment' => array(
				'attachment' => array('This field cannot be left blank')
			)
		);

		$data = array(
			'Comment' => array('comment' => '', 'article_id' => 1, 'user_id' => 1),
			'Attachment' => array('attachment' => '')
		);
		$result = $model->saveAll($data, array('validate' => 'only'));
		$this->assertFalse($result);
		$result = $model->validateAssociated($data);
		$this->assertFalse($result);
		$this->assertEquals($expected, $model->validationErrors);
		$this->assertEquals($expected['Attachment'], $model->Attachment->validationErrors);
	}

/**
 * testValidateMany method
 *
 * @return void
 */
	public function testValidateMany() {
		$TestModel = new Article();
		$TestModel->validate = array('title' => 'notEmpty');
		$data = array(
			0 => array('title' => ''),
			1 => array('title' => 'title 1'),
			2 => array('title' => 'title 2'),
		);
		$expected = array(
			0 => array('title' => array('This field cannot be left blank')),
		);

		$result = $TestModel->saveAll($data, array('validate' => 'only'));
		$this->assertFalse($result);
		$this->assertEquals($expected, $TestModel->validationErrors);
		$result = $TestModel->validateMany($data);
		$this->assertFalse($result);
		$this->assertEquals($expected, $TestModel->validationErrors);

		$data = array(
			0 => array('title' => 'title 0'),
			1 => array('title' => ''),
			2 => array('title' => 'title 2'),
		);
		$expected = array(
			1 => array('title' => array('This field cannot be left blank')),
		);
		$result = $TestModel->saveAll($data, array('validate' => 'only'));
		$this->assertFalse($result);
		$this->assertEquals($expected, $TestModel->validationErrors);
		$result = $TestModel->validateMany($data);
		$this->assertFalse($result);
		$this->assertEquals($expected, $TestModel->validationErrors);
	}

/**
 * testGetMethods method
 *
 * @return void
 */
	public function testGetMethods() {
		$this->loadFixtures('Article', 'Comment');
		$TestModel = new Article();
		$Validator = $TestModel->validator();

		$result = $Validator->getMethods();

		$expected = array_map('strtolower', get_class_methods('Article'));
		$this->assertEquals($expected, array_keys($result));
	}

/**
 * testSetValidationDomain method
 *
 * @return void
 */
	public function testSetValidationDomain() {
		$this->loadFixtures('Article', 'Comment');
		$TestModel = new Article();
		$Validator = $TestModel->validator();

		$result = $Validator->setValidationDomain('default');
		$this->assertEquals('default', $TestModel->validationDomain);

		$result = $Validator->setValidationDomain('other');
		$this->assertEquals('other', $TestModel->validationDomain);
	}

/**
 * testGetModel method
 *
 * @return void
 */
	public function testGetModel() {
		$TestModel = new Article();
		$Validator = $TestModel->validator();

		$result = $Validator->getModel();
		$this->assertInstanceOf('Article', $result);
	}

/**
 * Tests it is possible to get validation sets for a field using an array inteface
 *
 * @return void
 */
	public function testArrayAccessGet() {
		$TestModel = new Article();
		$Validator = $TestModel->validator();

		$titleValidator = $Validator['title'];
		$this->assertEquals('title', $titleValidator->field);
		$this->assertCount(1, $titleValidator->getRules());
		$rule = current($titleValidator->getRules());
		$this->assertEquals('notEmpty', $rule->rule);

		$titleValidator = $Validator['body'];
		$this->assertEquals('body', $titleValidator->field);
		$this->assertCount(1, $titleValidator->getRules());
		$rule = current($titleValidator->getRules());
		$this->assertEquals('notEmpty', $rule->rule);

		$titleValidator = $Validator['user_id'];
		$this->assertEquals('user_id', $titleValidator->field);
		$this->assertCount(1, $titleValidator->getRules());
		$rule = current($titleValidator->getRules());
		$this->assertEquals('numeric', $rule->rule);
	}

/**
 * Tests it is possible to check for validation sets for a field using an array inteface
 *
 * @return void
 */
	public function testArrayAccessExists() {
		$TestModel = new Article();
		$Validator = $TestModel->validator();

		$this->assertTrue(isset($Validator['title']));
		$this->assertTrue(isset($Validator['body']));
		$this->assertTrue(isset($Validator['user_id']));
		$this->assertFalse(isset($Validator['other']));
	}

/**
 * Tests it is possible to set validation rules for a field using an array inteface
 *
 * @return void
 */
	public function testArrayAccessSet() {
		$TestModel = new Article();
		$Validator = $TestModel->validator();

		$set = array(
			'numeric' => array('rule' => 'numeric', 'allowEmpty' => false),
			'range' => array('rule' => array('between', 1, 5), 'allowEmpty' => false),
		);
		$Validator['other'] = $set;
		$rules = $Validator['other'];
		$this->assertEquals('other', $rules->field);

		$validators = $rules->getRules();
		$this->assertCount(2, $validators);
		$this->assertEquals('numeric', $validators['numeric']->rule);
		$this->assertEquals(array('between', 1, 5), $validators['range']->rule);

		$Validator['new'] = new CakeValidationSet('new', $set, array());
		$rules = $Validator['new'];
		$this->assertEquals('new', $rules->field);

		$validators = $rules->getRules();
		$this->assertCount(2, $validators);
		$this->assertEquals('numeric', $validators['numeric']->rule);
		$this->assertEquals(array('between', 1, 5), $validators['range']->rule);
	}

/**
 * Tests it is possible to unset validation rules
 *
 * @return void
 */
	public function testArrayAccessUset() {
		$TestModel = new Article();
		$Validator = $TestModel->validator();

		$this->assertTrue(isset($Validator['title']));
		unset($Validator['title']);
		$this->assertFalse(isset($Validator['title']));
	}

/**
 * Tests it is possible to iterate a validation object
 *
 * @return void
 */
	public function testIterator() {
		$TestModel = new Article();
		$Validator = $TestModel->validator();

		$i = 0;
		foreach ($Validator as $field => $rules) {
			if ($i === 0) {
				$this->assertEquals('user_id', $field);
			}
			if ($i === 1) {
				$this->assertEquals('title', $field);
			}
			if ($i === 2) {
				$this->assertEquals('body', $field);
			}
			$this->assertInstanceOf('CakeValidationSet', $rules);
			$i++;
		}
		$this->assertEquals(3, $i);
	}

/**
 * Tests countable interface in ModelValidator
 *
 * @return void
 */
	public function testCount() {
		$TestModel = new Article();
		$Validator = $TestModel->validator();
		$this->assertCount(3, $Validator);

		$set = array(
			'numeric' => array('rule' => 'numeric', 'allowEmpty' => false),
			'range' => array('rule' => array('between', 1, 5), 'allowEmpty' => false),
		);
		$Validator['other'] = $set;
		$this->assertCount(4, $Validator);

		unset($Validator['title']);
		$this->assertCount(3, $Validator);
		unset($Validator['body']);
		$this->assertCount(2, $Validator);
	}

/**
 * Tests it is possible to add validation rules
 *
 * @return void
 */
	public function testAddRule() {
		$TestModel = new Article();
		$Validator = $TestModel->validator();

		$Validator->add('other', 'numeric', array('rule' => 'numeric', 'allowEmpty' => false));
		$Validator->add('other', 'range', array('rule' => array('between', 1, 5), 'allowEmpty' => false));
		$rules = $Validator['other'];
		$this->assertEquals('other', $rules->field);

		$validators = $rules->getRules();
		$this->assertCount(2, $validators);
		$this->assertEquals('numeric', $validators['numeric']->rule);
		$this->assertEquals(array('between', 1, 5), $validators['range']->rule);
	}

/**
 * Tests it is possible to remove validation rules
 *
 * @return void
 */
	public function testRemoveRule() {
		$TestModel = new Article();
		$Validator = $TestModel->validator();

		$this->assertTrue(isset($Validator['title']));
		$Validator->remove('title');
		$this->assertFalse(isset($Validator['title']));

		$Validator->add('other', 'numeric', array('rule' => 'numeric', 'allowEmpty' => false));
		$Validator->add('other', 'range', array('rule' => array('between', 1, 5), 'allowEmpty' => false));
		$this->assertTrue(isset($Validator['other']));

		$Validator->remove('other', 'numeric');
		$this->assertTrue(isset($Validator['other']));
		$this->assertFalse(isset($Validator['other']['numeric']));
		$this->assertTrue(isset($Validator['other']['range']));
	}

/**
 * Tests validation callbacks are triggered
 *
 * @return void
 */
	public function testValidateCallbacks() {
		$TestModel = $this->getMock('Article', array('beforeValidate', 'afterValidate'));
		$TestModel->expects($this->once())->method('beforeValidate');
		$TestModel->expects($this->once())->method('afterValidate');

		$TestModel->set(array('title' => '', 'body' => 'body'));
		$TestModel->validates();
	}

/**
 * Tests that altering data in a beforeValidate callback will lead to saving those
 * values in database
 *
 * @return void
 */
	public function testValidateFirstWithBeforeValidate() {
		$this->loadFixtures('Article', 'User');
		$model = new CustomArticle();
		$model->validate = array(
			'title' => array(
				'notempty' => array(
					'rule' => 'notEmpty',
					'required' => true,
					'allowEmpty' => false
				)
			)
		);
		$data = array(
			'CustomArticle' => array(
				'body' => 'foo0'
			)
		);
		$result = $model->saveAll($data, array('validate' => 'first'));
		$this->assertTrue($result);

		$this->assertFalse($model->findMethods['unPublished'], 'beforeValidate was run twice');

		$model->findMethods['unPublished'] = true;
		$data = array(
			'CustomArticle' => array(
				'body' => 'foo1'
			)
		);
		$result = $model->saveAll($data, array('validate' => 'first', 'deep' => true));
		$this->assertTrue($result);
		$title = $model->field('title', array('body' => 'foo1'));
		$this->assertEquals('foo', $title);
		$this->assertFalse($model->findMethods['unPublished'], 'beforeValidate was run twice');

		$data = array(
			array('body' => 'foo2'),
			array('body' => 'foo3'),
			array('body' => 'foo4')
		);

		$result = $model->saveAll($data, array('validate' => 'first', 'deep' => true));
		$this->assertTrue($result);

		$this->assertEquals('foo', $model->field('title', array('body' => 'foo2')));
		$this->assertEquals('foo', $model->field('title', array('body' => 'foo3')));
		$this->assertEquals('foo', $model->field('title', array('body' => 'foo4')));
	}

/**
 * Tests that altering data in a beforeValidate callback will lead to saving those
 * values in database
 *
 * @return void
 */
	public function testValidateFirstAssociatedWithBeforeValidate() {
		$this->loadFixtures('Article', 'User');
		$model = new CustomArticle();
		$model->validate = array(
			'title' => array(
				'notempty' => array(
					'rule' => 'notEmpty',
					'required' => true
				)
			)
		);
		$articles = array(
			array('body' => 'foo1'),
			array('body' => 'foo2'),
			array('body' => 'foo3')
		);
		$user = new User();
		$user->bindModel(array('hasMany' => array('CustomArticle')));
		$data = array(
			'User' => array('user' => 'foo', 'password' => 'bar'),
			'CustomArticle' => $articles
		);
		$result = $user->saveAll($data, array('validate' => 'first'));
		$this->assertTrue($result);

		$this->assertEquals('foo', $model->field('title', array('body' => 'foo1')));
		$this->assertEquals('foo', $model->field('title', array('body' => 'foo2')));
		$this->assertEquals('foo', $model->field('title', array('body' => 'foo3')));
	}

/**
 * testValidateFirstWithDefaults method
 *
 * return @void
 */
	public function testFirstWithDefaults() {
		$this->loadFixtures('Article', 'Tag', 'Comment', 'User', 'ArticlesTag');
		$TestModel = new Article();

		$result = $TestModel->find('first', array(
			'conditions' => array('Article.id' => 1)
		));
		$expected = array(
			'Article' => array(
				'id' => 1,
				'user_id' => 1,
				'title' => 'First Article',
				'body' => 'First Article Body',
				'published' => 'Y',
				'created' => '2007-03-18 10:39:23'
			),
		);
		unset($result['Article']['updated']);
		$this->assertEquals($expected['Article'], $result['Article']);

		$data = array(
			'Article' => array(
				'id' => 1,
				'title' => 'First Article (modified)'
			),
			'Comment' => array(
				array('comment' => 'Article comment', 'user_id' => 1)
			)
		);
		$result = $TestModel->saveAll($data, array('validate' => 'first'));
		$this->assertTrue($result);

		$result = $TestModel->find('first', array(
			'conditions' => array('Article.id' => 1)
		));
		$expected['Article']['title'] = 'First Article (modified)';
		unset($result['Article']['updated']);
		$this->assertEquals($expected['Article'], $result['Article']);
	}

	public function testAddMultipleRules() {
		$TestModel = new Article();
		$Validator = $TestModel->validator();

		$set = array(
			'numeric' => array('rule' => 'numeric', 'allowEmpty' => false),
			'range' => array('rule' => array('between', 1, 5), 'allowEmpty' => false),
		);

		$Validator->add('other', $set);
		$rules = $Validator['other'];
		$this->assertEquals('other', $rules->field);

		$validators = $rules->getRules();
		$this->assertCount(2, $validators);
		$this->assertEquals('numeric', $validators['numeric']->rule);
		$this->assertEquals(array('between', 1, 5), $validators['range']->rule);

		$set = new CakeValidationSet('other', array(
			'a' => array('rule' => 'numeric', 'allowEmpty' => false),
			'b' => array('rule' => array('between', 1, 5), 'allowEmpty' => false),
		));

		$Validator->add('other', $set);
		$this->assertSame($set, $Validator->getField('other'));
	}

/**
 * Test that rules are parsed correctly when calling getField()
 *
 * @return void
 */
	public function testValidator() {
		$TestModel = new Article();
		$Validator = $TestModel->validator();

		$result = $Validator->getField();
		$expected = array('user_id', 'title', 'body');
		$this->assertEquals($expected, array_keys($result));
		$this->assertTrue($result['user_id'] instanceof CakeValidationSet);

		$result = $TestModel->validator()->getField('title');
		$this->assertTrue($result instanceof CakeValidationSet);
	}

/**
 * Tests that altering data in a beforeValidate callback will lead to saving those
 * values in database, this time with belongsTo associations
 *
 * @return void
 */
	public function testValidateFirstAssociatedWithBeforeValidate2() {
		$this->loadFixtures('Article', 'User');
		$model = new CustomArticle();
		$model->validate = array(
			'title' => array(
				'notempty' => array(
					'rule' => 'notEmpty',
					'required' => true
				)
			)
		);

		$data = array(
			'User' => array('user' => 'foo', 'password' => 'bar'),
			'CustomArticle' => array(
				'body' => 'a test'
			)
		);
		$result = $model->saveAll($data, array('validate' => 'first'));
		$this->assertTrue($result);

		$this->assertEquals('foo', $model->field('title', array('body' => 'a test')));
	}

/**
 * Testing you can dynamically add rules to a field, added this to dispel doubts
 * after a presentation made to show off this new feature
 *
 * @return void
 **/
	public function testDynamicValidationRuleBuilding() {
		$model = new Article;
		$validator = $model->validator();
		$validator->add('body', 'isSpecial', array('rule' => 'special'));
		$rules = $validator['body']->getRules();
		$this->assertCount(2, $rules);
		$this->assertEquals('special', $rules['isSpecial']->rule);
		$validator['body']->setRule('isAwesome', array('rule' => 'awesome'));
		$rules = $validator['body']->getRules();
		$this->assertCount(3, $rules);
		$this->assertEquals('awesome', $rules['isAwesome']->rule);
	}

/**
 * Test to ensure custom validation methods work with CakeValidationSet
 *
 * @return void
 */
	public function testCustomMethodsWithCakeValidationSet() {
		$TestModel = new TestValidate();
		$Validator = $TestModel->validator();

		$Validator->add('title', 'validateTitle', array(
			'rule' => 'validateTitle',
			'message' => 'That aint right',
		));
		$data = array('title' => 'notatitle');
		$result = $Validator->getField('title')->validate($data);
		$expected = array(0 => 'That aint right');
		$this->assertEquals($expected, $result);

		$data = array('title' => 'title-is-good');
		$result = $Validator->getField('title')->validate($data);
		$expected = array();
		$this->assertEquals($expected, $result);
	}

	public function testCustomMethodWithEmptyValue() {
		$this->loadFixtures('Article');

		$model = $this->getMock('Article', array('isLegit'));
		$model->validate = array(
			'title' => array(
				'custom' => array(
					'rule' => array('isLegit'),
					'message' => 'is no good'
				)
			)
		);
		$model->expects($this->once())
			->method('isLegit')
			->will($this->returnValue(false));

		$model->set(array('title' => ''));
		$this->assertFalse($model->validates());
	}

}
