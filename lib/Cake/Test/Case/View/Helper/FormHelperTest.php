<?php
/**
 * FormHelperTest file
 *
 * PHP 5
 *
 * CakePHP(tm) Tests <http://book.cakephp.org/view/1196/Testing>
 * Copyright 2005-2012, Cake Software Foundation, Inc.
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice
 *
 * @copyright     Copyright 2005-2012, Cake Software Foundation, Inc.
 * @link          http://book.cakephp.org/view/1196/Testing CakePHP(tm) Tests
 * @package       Cake.Test.Case.View.Helper
 * @since         CakePHP(tm) v 1.2.0.4206
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
App::uses('ClassRegistry', 'Utility');
App::uses('Controller', 'Controller');
App::uses('View', 'View');
App::uses('Model', 'Model');
App::uses('Security', 'Utility');
App::uses('CakeRequest', 'Network');
App::uses('HtmlHelper', 'View/Helper');
App::uses('FormHelper', 'View/Helper');
App::uses('Router', 'Routing');

/**
 * ContactTestController class
 *
 * @package	   cake
 * @package       Cake.Test.Case.View.Helper
 */
class ContactTestController extends Controller {

/**
 * name property
 *
 * @var string 'ContactTest'
 */
	public $name = 'ContactTest';

/**
 * uses property
 *
 * @var mixed null
 */
	public $uses = null;
}

/**
 * Contact class
 *
 * @package	   cake
 * @package       Cake.Test.Case.View.Helper
 */
class Contact extends CakeTestModel {

/**
 * primaryKey property
 *
 * @var string 'id'
 */
	public $primaryKey = 'id';

/**
 * useTable property
 *
 * @var bool false
 */
	public $useTable = false;

/**
 * name property
 *
 * @var string 'Contact'
 */
	public $name = 'Contact';

/**
 * Default schema
 *
 * @var array
 */
	protected $_schema = array(
		'id' => array('type' => 'integer', 'null' => '', 'default' => '', 'length' => '8'),
		'name' => array('type' => 'string', 'null' => '', 'default' => '', 'length' => '255'),
		'email' => array('type' => 'string', 'null' => '', 'default' => '', 'length' => '255'),
		'phone' => array('type' => 'string', 'null' => '', 'default' => '', 'length' => '255'),
		'password' => array('type' => 'string', 'null' => '', 'default' => '', 'length' => '255'),
		'published' => array('type' => 'date', 'null' => true, 'default' => null, 'length' => null),
		'created' => array('type' => 'date', 'null' => '1', 'default' => '', 'length' => ''),
		'updated' => array('type' => 'datetime', 'null' => '1', 'default' => '', 'length' => null),
		'age' => array('type' => 'integer', 'null' => '', 'default' => '', 'length' => null)
	);

/**
 * validate property
 *
 * @var array
 */
	public $validate = array(
		'non_existing' => array(),
		'idontexist' => array(),
		'imrequired' => array('rule' => array('between', 5, 30), 'allowEmpty' => false),
		'string_required' => 'notEmpty',
		'imalsorequired' => array('rule' => 'alphaNumeric', 'allowEmpty' => false),
		'imrequiredtoo' => array('rule' => 'notEmpty'),
		'required_one' => array('required' => array('rule' => array('notEmpty'))),
		'imnotrequired' => array('required' => false, 'rule' => 'alphaNumeric', 'allowEmpty' => true),
		'imalsonotrequired' => array(
			'alpha' => array('rule' => 'alphaNumeric','allowEmpty' => true),
			'between' => array('rule' => array('between', 5, 30)),
		),
		'imnotrequiredeither' => array('required' => true, 'rule' => array('between', 5, 30), 'allowEmpty' => true),
	);

/**
 * schema method
 *
 * @return void
 */
	public function setSchema($schema) {
		$this->_schema = $schema;
	}

/**
 * hasAndBelongsToMany property
 *
 * @var array
 */
	public $hasAndBelongsToMany = array('ContactTag' => array('with' => 'ContactTagsContact'));

/**
 * hasAndBelongsToMany property
 *
 * @var array
 */
	public $belongsTo = array('User' => array('className' => 'UserForm'));
}

/**
 * ContactTagsContact class
 *
 * @package	   cake
 * @package       Cake.Test.Case.View.Helper
 */
class ContactTagsContact extends CakeTestModel {

/**
 * useTable property
 *
 * @var bool false
 */
	public $useTable = false;

/**
 * name property
 *
 * @var string 'Contact'
 */
	public $name = 'ContactTagsContact';

/**
 * Default schema
 *
 * @var array
 */
	protected $_schema = array(
		'contact_id' => array('type' => 'integer', 'null' => '', 'default' => '', 'length' => '8'),
		'contact_tag_id' => array(
			'type' => 'integer', 'null' => '', 'default' => '', 'length' => '8'
		)
	);

/**
 * schema method
 *
 * @return void
 */
	public function setSchema($schema) {
		$this->_schema = $schema;
	}

}

/**
 * ContactNonStandardPk class
 *
 * @package	   cake
 * @package       Cake.Test.Case.View.Helper
 */
class ContactNonStandardPk extends Contact {

/**
 * primaryKey property
 *
 * @var string 'pk'
 */
	public $primaryKey = 'pk';

/**
 * name property
 *
 * @var string 'ContactNonStandardPk'
 */
	public $name = 'ContactNonStandardPk';

/**
 * schema method
 *
 * @return void
 */
	public function schema($field = false) {
		$this->_schema = parent::schema();
		$this->_schema['pk'] = $this->_schema['id'];
		unset($this->_schema['id']);
		return $this->_schema;
	}

}

/**
 * ContactTag class
 *
 * @package	   cake
 * @package       Cake.Test.Case.View.Helper
 */
class ContactTag extends Model {

/**
 * useTable property
 *
 * @var bool false
 */
	public $useTable = false;

/**
 * schema definition
 *
 * @var array
 */
	protected $_schema = array(
		'id' => array('type' => 'integer', 'null' => false, 'default' => '', 'length' => '8'),
		'name' => array('type' => 'string', 'null' => false, 'default' => '', 'length' => '255'),
		'created' => array('type' => 'date', 'null' => true, 'default' => '', 'length' => ''),
		'modified' => array('type' => 'datetime', 'null' => true, 'default' => '', 'length' => null)
	);
}

/**
 * UserForm class
 *
 * @package	   cake
 * @package       Cake.Test.Case.View.Helper
 */
class UserForm extends CakeTestModel {

/**
 * useTable property
 *
 * @var bool false
 */
	public $useTable = false;

/**
 * primaryKey property
 *
 * @var string 'id'
 */
	public $primaryKey = 'id';

/**
 * name property
 *
 * @var string 'UserForm'
 */
	public $name = 'UserForm';

/**
 * hasMany property
 *
 * @var array
 */
	public $hasMany = array(
		'OpenidUrl' => array('className' => 'OpenidUrl', 'foreignKey' => 'user_form_id'
	));

/**
 * schema definition
 *
 * @var array
 */
	protected $_schema = array(
		'id' => array('type' => 'integer', 'null' => '', 'default' => '', 'length' => '8'),
		'published' => array('type' => 'date', 'null' => true, 'default' => null, 'length' => null),
		'other' => array('type' => 'text', 'null' => true, 'default' => null, 'length' => null),
		'stuff' => array('type' => 'string', 'null' => true, 'default' => null, 'length' => 10),
		'something' => array('type' => 'string', 'null' => true, 'default' => null, 'length' => 255),
		'active' => array('type' => 'boolean', 'null' => false, 'default' => false),
		'created' => array('type' => 'date', 'null' => '1', 'default' => '', 'length' => ''),
		'updated' => array('type' => 'datetime', 'null' => '1', 'default' => '', 'length' => null)
	);
}

/**
 * OpenidUrl class
 *
 * @package	   cake
 * @package       Cake.Test.Case.View.Helper
 */
class OpenidUrl extends CakeTestModel {

/**
 * useTable property
 *
 * @var bool false
 */
	public $useTable = false;

/**
 * primaryKey property
 *
 * @var string 'id'
 */
	public $primaryKey = 'id';

/**
 * name property
 *
 * @var string 'OpenidUrl'
 */
	public $name = 'OpenidUrl';

/**
 * belongsTo property
 *
 * @var array
 */
	public $belongsTo = array('UserForm' => array(
		'className' => 'UserForm', 'foreignKey' => 'user_form_id'
	));

/**
 * validate property
 *
 * @var array
 */
	public $validate = array('openid_not_registered' => array());

/**
 * schema method
 *
 * @var array
 */
	protected $_schema = array(
		'id' => array('type' => 'integer', 'null' => '', 'default' => '', 'length' => '8'),
		'user_form_id' => array(
			'type' => 'user_form_id', 'null' => '', 'default' => '', 'length' => '8'
		),
		'url' => array('type' => 'string', 'null' => '', 'default' => '', 'length' => '255'),
	);

/**
 * beforeValidate method
 *
 * @return void
 */
	public function beforeValidate($options = array()) {
		$this->invalidate('openid_not_registered');
		return true;
	}

}

/**
 * ValidateUser class
 *
 * @package	   cake
 * @package       Cake.Test.Case.View.Helper
 */
class ValidateUser extends CakeTestModel {

/**
 * primaryKey property
 *
 * @var string 'id'
 */
	public $primaryKey = 'id';

/**
 * useTable property
 *
 * @var bool false
 */
	public $useTable = false;

/**
 * name property
 *
 * @var string 'ValidateUser'
 */
	public $name = 'ValidateUser';

/**
 * hasOne property
 *
 * @var array
 */
	public $hasOne = array('ValidateProfile' => array(
		'className' => 'ValidateProfile', 'foreignKey' => 'user_id'
	));

/**
 * schema method
 *
 * @var array
 */
	protected $_schema = array(
		'id' => array('type' => 'integer', 'null' => '', 'default' => '', 'length' => '8'),
		'name' => array('type' => 'string', 'null' => '', 'default' => '', 'length' => '255'),
		'email' => array('type' => 'string', 'null' => '', 'default' => '', 'length' => '255'),
		'balance' => array('type' => 'float', 'null' => false, 'length' => '5,2'),
		'created' => array('type' => 'date', 'null' => '1', 'default' => '', 'length' => ''),
		'updated' => array('type' => 'datetime', 'null' => '1', 'default' => '', 'length' => null)
	);

/**
 * beforeValidate method
 *
 * @return void
 */
	public function beforeValidate($options = array()) {
		$this->invalidate('email');
		return false;
	}

}

/**
 * ValidateProfile class
 *
 * @package	   cake
 * @package       Cake.Test.Case.View.Helper
 */
class ValidateProfile extends CakeTestModel {

/**
 * primaryKey property
 *
 * @var string 'id'
 */
	public $primaryKey = 'id';

/**
 * useTable property
 *
 * @var bool false
 */
	public $useTable = false;

/**
 * schema property
 *
 * @var array
 */
	protected $_schema = array(
		'id' => array('type' => 'integer', 'null' => '', 'default' => '', 'length' => '8'),
		'user_id' => array('type' => 'integer', 'null' => '', 'default' => '', 'length' => '8'),
		'full_name' => array('type' => 'string', 'null' => '', 'default' => '', 'length' => '255'),
		'city' => array('type' => 'string', 'null' => '', 'default' => '', 'length' => '255'),
		'created' => array('type' => 'date', 'null' => '1', 'default' => '', 'length' => ''),
		'updated' => array('type' => 'datetime', 'null' => '1', 'default' => '', 'length' => null)
	);

/**
 * name property
 *
 * @var string 'ValidateProfile'
 */
	public $name = 'ValidateProfile';

/**
 * hasOne property
 *
 * @var array
 */
	public $hasOne = array('ValidateItem' => array(
		'className' => 'ValidateItem', 'foreignKey' => 'profile_id'
	));

/**
 * belongsTo property
 *
 * @var array
 */
	public $belongsTo = array('ValidateUser' => array(
		'className' => 'ValidateUser', 'foreignKey' => 'user_id'
	));

/**
 * beforeValidate method
 *
 * @return void
 */
	public function beforeValidate($options = array()) {
		$this->invalidate('full_name');
		$this->invalidate('city');
		return false;
	}

}

/**
 * ValidateItem class
 *
 * @package	   cake
 * @package       Cake.Test.Case.View.Helper
 */
class ValidateItem extends CakeTestModel {

/**
 * primaryKey property
 *
 * @var string 'id'
 */
	public $primaryKey = 'id';

/**
 * useTable property
 *
 * @var bool false
 */
	public $useTable = false;

/**
 * name property
 *
 * @var string 'ValidateItem'
 */
	public $name = 'ValidateItem';

/**
 * schema property
 *
 * @var array
 */
	protected $_schema = array(
		'id' => array('type' => 'integer', 'null' => '', 'default' => '', 'length' => '8'),
		'profile_id' => array('type' => 'integer', 'null' => '', 'default' => '', 'length' => '8'),
		'name' => array('type' => 'text', 'null' => '', 'default' => '', 'length' => '255'),
		'description' => array(
			'type' => 'string', 'null' => '', 'default' => '', 'length' => '255'
		),
		'created' => array('type' => 'date', 'null' => '1', 'default' => '', 'length' => ''),
		'updated' => array('type' => 'datetime', 'null' => '1', 'default' => '', 'length' => null)
	);

/**
 * belongsTo property
 *
 * @var array
 */
	public $belongsTo = array('ValidateProfile' => array('foreignKey' => 'profile_id'));

/**
 * beforeValidate method
 *
 * @return void
 */
	public function beforeValidate($options = array()) {
		$this->invalidate('description');
		return false;
	}

}

/**
 * TestMail class
 *
 * @package	   cake
 * @package       Cake.Test.Case.View.Helper
 */
class TestMail extends CakeTestModel {

/**
 * primaryKey property
 *
 * @var string 'id'
 */
	public $primaryKey = 'id';

/**
 * useTable property
 *
 * @var bool false
 */
	public $useTable = false;

/**
 * name property
 *
 * @var string 'TestMail'
 */
	public $name = 'TestMail';
}

/**
 * FormHelperTest class
 *
 * @package	   cake
 * @package       Cake.Test.Case.View.Helper
 */
class FormHelperTest extends CakeTestCase {

/**
 * Fixtures to be used
 *
 * @var array
 */
	public $fixtures = array('core.post');

/**
 * Do not load the fixtures by default
 *
 * @var boolean
 */
	public $autoFixtures = false;

/**
 * setUp method
 *
 * @return void
 */
	public function setUp() {
		parent::setUp();

		Configure::write('App.base', '');
		$this->Controller = new ContactTestController();
		$this->View = new View($this->Controller);

		$this->Form = new FormHelper($this->View);
		$this->Form->Html = new HtmlHelper($this->View);
		$this->Form->request = new CakeRequest('contacts/add', false);
		$this->Form->request->here = '/contacts/add';
		$this->Form->request['action'] = 'add';
		$this->Form->request->webroot = '';
		$this->Form->request->base = '';

		ClassRegistry::addObject('Contact', new Contact());
		ClassRegistry::addObject('ContactNonStandardPk', new ContactNonStandardPk());
		ClassRegistry::addObject('OpenidUrl', new OpenidUrl());
		ClassRegistry::addObject('User', new UserForm());
		ClassRegistry::addObject('ValidateItem', new ValidateItem());
		ClassRegistry::addObject('ValidateUser', new ValidateUser());
		ClassRegistry::addObject('ValidateProfile', new ValidateProfile());

		$this->oldSalt = Configure::read('Security.salt');

		$this->dateRegex = array(
			'daysRegex' => 'preg:/(?:<option value="0?([\d]+)">\\1<\/option>[\r\n]*)*/',
			'monthsRegex' => 'preg:/(?:<option value="[\d]+">[\w]+<\/option>[\r\n]*)*/',
			'yearsRegex' => 'preg:/(?:<option value="([\d]+)">\\1<\/option>[\r\n]*)*/',
			'hoursRegex' => 'preg:/(?:<option value="0?([\d]+)">\\1<\/option>[\r\n]*)*/',
			'minutesRegex' => 'preg:/(?:<option value="([\d]+)">0?\\1<\/option>[\r\n]*)*/',
			'meridianRegex' => 'preg:/(?:<option value="(am|pm)">\\1<\/option>[\r\n]*)*/',
		);

		Configure::write('Security.salt', 'foo!');
	}

/**
 * tearDown method
 *
 * @return void
 */
	public function tearDown() {
		parent::tearDown();
		unset($this->Form->Html, $this->Form, $this->Controller, $this->View);
		Configure::write('Security.salt', $this->oldSalt);
	}

/**
 * testFormCreateWithSecurity method
 *
 * Test form->create() with security key.
 *
 * @return void
 */
	public function testCreateWithSecurity() {
		$this->Form->request['_Token'] = array('key' => 'testKey');
		$encoding = strtolower(Configure::read('App.encoding'));
		$result = $this->Form->create('Contact', array('url' => '/contacts/add'));
		$expected = array(
			'form' => array('method' => 'post', 'action' => '/contacts/add', 'accept-charset' => $encoding, 'id' => 'ContactAddForm'),
			'div' => array('style' => 'display:none;'),
			array('input' => array('type' => 'hidden', 'name' => '_method', 'value' => 'POST')),
			array('input' => array(
				'type' => 'hidden', 'name' => 'data[_Token][key]', 'value' => 'testKey', 'id'
			)),
			'/div'
		);
		$this->assertTags($result, $expected);

		$result = $this->Form->create('Contact', array('url' => '/contacts/add', 'id' => 'MyForm'));
		$expected['form']['id'] = 'MyForm';
		$this->assertTags($result, $expected);
	}

/**
 * test that create() clears the fields property so it starts fresh
 *
 * @return void
 */
	public function testCreateClearingFields() {
		$this->Form->fields = array('model_id');
		$this->Form->create('Contact');
		$this->assertEquals(array(), $this->Form->fields);
	}

/**
 * Tests form hash generation with model-less data
 *
 * @return void
 */
	public function testValidateHashNoModel() {
		$this->Form->request['_Token'] = array('key' => 'foo');
		$result = $this->Form->secure(array('anything'));
		$this->assertRegExp('/540ac9c60d323c22bafe997b72c0790f39a8bdef/', $result);
	}

/**
 * Tests that models with identical field names get resolved properly
 *
 * @return void
 */
	public function testDuplicateFieldNameResolution() {
		$result = $this->Form->create('ValidateUser');
		$this->assertEquals(array('ValidateUser'), $this->Form->entity());

		$result = $this->Form->input('ValidateItem.name');
		$this->assertEquals(array('ValidateItem', 'name'), $this->Form->entity());

		$result = $this->Form->input('ValidateUser.name');
		$this->assertEquals(array('ValidateUser', 'name'), $this->Form->entity());
		$this->assertRegExp('/name="data\[ValidateUser\]\[name\]"/', $result);
		$this->assertRegExp('/type="text"/', $result);

		$result = $this->Form->input('ValidateItem.name');
		$this->assertEquals(array('ValidateItem', 'name'), $this->Form->entity());
		$this->assertRegExp('/name="data\[ValidateItem\]\[name\]"/', $result);
		$this->assertRegExp('/<textarea/', $result);

		$result = $this->Form->input('name');
		$this->assertEquals(array('ValidateUser', 'name'), $this->Form->entity());
		$this->assertRegExp('/name="data\[ValidateUser\]\[name\]"/', $result);
		$this->assertRegExp('/type="text"/', $result);
	}

/**
 * Tests that hidden fields generated for checkboxes don't get locked
 *
 * @return void
 */
	public function testNoCheckboxLocking() {
		$this->Form->request['_Token'] = array('key' => 'foo');
		$this->assertSame(array(), $this->Form->fields);

		$this->Form->checkbox('check', array('value' => '1'));
		$this->assertSame($this->Form->fields, array('check'));
	}

/**
 * testFormSecurityFields method
 *
 * Test generation of secure form hash generation.
 *
 * @return void
 */
	public function testFormSecurityFields() {
		$key = 'testKey';
		$fields = array('Model.password', 'Model.username', 'Model.valid' => '0');

		$this->Form->request['_Token'] = array('key' => $key);
		$result = $this->Form->secure($fields);

		$expected = Security::hash(serialize($fields) . Configure::read('Security.salt'));
		$expected .= ':' . 'Model.valid';

		$expected = array(
			'div' => array('style' => 'display:none;'),
			array('input' => array(
				'type' => 'hidden', 'name' => 'data[_Token][fields]',
				'value' => urlencode($expected), 'id' => 'preg:/TokenFields\d+/'
			)),
			array('input' => array(
				'type' => 'hidden', 'name' => 'data[_Token][unlocked]',
				'value' => '', 'id' => 'preg:/TokenUnlocked\d+/'
			)),
			'/div'
		);
		$this->assertTags($result, $expected);
	}

/**
 * Tests correct generation of number fields for double and float fields
 *
 * @return void
 */
	public function testTextFieldGenerationForFloats() {
		$model = ClassRegistry::getObject('Contact');
		$model->setSchema(array('foo' => array(
			'type' => 'float',
			'null' => false,
			'default' => null,
			'length' => null
		)));

		$this->Form->create('Contact');
		$result = $this->Form->input('foo');
		$expected = array(
			'div' => array('class' => 'input number'),
			'label' => array('for' => 'ContactFoo'),
			'Foo',
			'/label',
			array('input' => array(
				'type' => 'number',
				'name' => 'data[Contact][foo]',
				'id' => 'ContactFoo',
				'step' => 'any'
			)),
			'/div'
		);
		$this->assertTags($result, $expected);

		$result = $this->Form->input('foo', array('step' => 0.5));
		$expected = array(
			'div' => array('class' => 'input number'),
			'label' => array('for' => 'ContactFoo'),
			'Foo',
			'/label',
			array('input' => array(
				'type' => 'number',
				'name' => 'data[Contact][foo]',
				'id' => 'ContactFoo',
				'step' => '0.5'
			)),
			'/div'
		);
		$this->assertTags($result, $expected);
	}

/**
 * Tests correct generation of number fields for integer fields
 *
 * @access public
 * @return void
 */
	public function testTextFieldTypeNumberGenerationForIntegers() {
		$model = ClassRegistry::getObject('Contact');
		$model->setSchema(array('foo' => array(
			'type' => 'integer',
			'null' => false,
			'default' => null,
			'length' => null
		)));

		$this->Form->create('Contact');
		$result = $this->Form->input('foo');
		$expected = array(
			'div' => array('class' => 'input number'),
			'label' => array('for' => 'ContactFoo'),
			'Foo',
			'/label',
			array('input' => array(
				'type' => 'number', 'name' => 'data[Contact][foo]',
				'id' => 'ContactFoo'
			)),
			'/div'
		);
		$this->assertTags($result, $expected);
	}

/**
 * testFormSecurityMultipleFields method
 *
 * Test secure() with multiple row form. Ensure hash is correct.
 *
 * @return void
 */
	public function testFormSecurityMultipleFields() {
		$key = 'testKey';

		$fields = array(
			'Model.0.password', 'Model.0.username', 'Model.0.hidden' => 'value',
			'Model.0.valid' => '0', 'Model.1.password', 'Model.1.username',
			'Model.1.hidden' => 'value', 'Model.1.valid' => '0'
		);
		$this->Form->request['_Token'] = array('key' => $key);
		$result = $this->Form->secure($fields);

		$hash  = '51e3b55a6edd82020b3f29c9ae200e14bbeb7ee5%3AModel.0.hidden%7CModel.0.valid';
		$hash .= '%7CModel.1.hidden%7CModel.1.valid';

		$expected = array(
			'div' => array('style' => 'display:none;'),
			array('input' => array(
				'type' => 'hidden', 'name' => 'data[_Token][fields]',
				'value' => $hash, 'id' => 'preg:/TokenFields\d+/'
			)),
			array('input' => array(
				'type' => 'hidden', 'name' => 'data[_Token][unlocked]',
				'value' => '', 'id' => 'preg:/TokenUnlocked\d+/'
			)),
			'/div'
		);
		$this->assertTags($result, $expected);
	}

/**
 * testFormSecurityMultipleSubmitButtons
 *
 * test form submit generation and ensure that _Token is only created on end()
 *
 * @return void
 */
	public function testFormSecurityMultipleSubmitButtons() {
		$key = 'testKey';
		$this->Form->request['_Token'] = array('key' => $key);

		$this->Form->create('Addresses');
		$this->Form->input('Address.title');
		$this->Form->input('Address.first_name');

		$result = $this->Form->submit('Save', array('name' => 'save'));
		$expected = array(
			'div' => array('class' => 'submit'),
			'input' => array('type' => 'submit', 'name' => 'save', 'value' => 'Save'),
			'/div',
		);
		$this->assertTags($result, $expected);
		$result = $this->Form->submit('Cancel', array('name' => 'cancel'));
		$expected = array(
			'div' => array('class' => 'submit'),
			'input' => array('type' => 'submit', 'name' => 'cancel', 'value' => 'Cancel'),
			'/div',
		);
		$this->assertTags($result, $expected);
		$result = $this->Form->end(null);

		$expected = array(
			'div' => array('style' => 'display:none;'),
			array('input' => array(
				'type' => 'hidden', 'name' => 'data[_Token][fields]',
				'value' => 'preg:/.+/', 'id' => 'preg:/TokenFields\d+/'
			)),
			array('input' => array(
				'type' => 'hidden', 'name' => 'data[_Token][unlocked]',
				'value' => 'cancel%7Csave', 'id' => 'preg:/TokenUnlocked\d+/'
			)),
			'/div'
		);
		$this->assertTags($result, $expected);
	}

/**
 * Test that buttons created with foo[bar] name attributes are unlocked correctly.
 *
 * @return void
 */
	public function testSecurityButtonNestedNamed() {
		$key = 'testKey';
		$this->Form->request['_Token'] = array('key' => $key);

		$this->Form->create('Addresses');
		$this->Form->button('Test', array('type' => 'submit', 'name' => 'Address[button]'));
		$result = $this->Form->unlockField();
		$this->assertEquals(array('Address.button'), $result);
	}

/**
 * Test that submit inputs created with foo[bar] name attributes are unlocked correctly.
 *
 * @return void
 */
	public function testSecuritySubmitNestedNamed() {
		$key = 'testKey';
		$this->Form->request['_Token'] = array('key' => $key);

		$this->Form->create('Addresses');
		$this->Form->submit('Test', array('type' => 'submit', 'name' => 'Address[button]'));
		$result = $this->Form->unlockField();
		$this->assertEquals(array('Address.button'), $result);
	}

/**
 * Test that the correct fields are unlocked for image submits with no names.
 *
 * @return void
 */
	public function testSecuritySubmitImageNoName() {
		$key = 'testKey';
		$this->Form->request['_Token'] = array('key' => $key);

		$this->Form->create('User');
		$result = $this->Form->submit('save.png');
		$expected = array(
			'div' => array('class' => 'submit'),
			'input' => array('type' => 'image', 'src' => 'img/save.png'),
			'/div'
		);
		$this->assertTags($result, $expected);
		$this->assertEquals(array('x', 'y'), $this->Form->unlockField());
	}

/**
 * Test that the correct fields are unlocked for image submits with names.
 *
 * @return void
 */
	public function testSecuritySubmitImageName() {
		$key = 'testKey';
		$this->Form->request['_Token'] = array('key' => $key);

		$this->Form->create('User');
		$result = $this->Form->submit('save.png', array('name' => 'test'));
		$expected = array(
			'div' => array('class' => 'submit'),
			'input' => array('type' => 'image', 'name' => 'test', 'src' => 'img/save.png'),
			'/div'
		);
		$this->assertTags($result, $expected);
		$this->assertEquals(array('test', 'test_x', 'test_y'), $this->Form->unlockField());
	}

/**
 * testFormSecurityMultipleInputFields method
 *
 * Test secure form creation with multiple row creation.  Checks hidden, text, checkbox field types
 *
 * @return void
 */
	public function testFormSecurityMultipleInputFields() {
		$key = 'testKey';

		$this->Form->request['_Token'] = array('key' => $key);
		$this->Form->create('Addresses');

		$this->Form->hidden('Addresses.0.id', array('value' => '123456'));
		$this->Form->input('Addresses.0.title');
		$this->Form->input('Addresses.0.first_name');
		$this->Form->input('Addresses.0.last_name');
		$this->Form->input('Addresses.0.address');
		$this->Form->input('Addresses.0.city');
		$this->Form->input('Addresses.0.phone');
		$this->Form->input('Addresses.0.primary', array('type' => 'checkbox'));

		$this->Form->hidden('Addresses.1.id', array('value' => '654321'));
		$this->Form->input('Addresses.1.title');
		$this->Form->input('Addresses.1.first_name');
		$this->Form->input('Addresses.1.last_name');
		$this->Form->input('Addresses.1.address');
		$this->Form->input('Addresses.1.city');
		$this->Form->input('Addresses.1.phone');
		$this->Form->input('Addresses.1.primary', array('type' => 'checkbox'));

		$result = $this->Form->secure($this->Form->fields);

		$hash = 'c9118120e680a7201b543f562e5301006ccfcbe2%3AAddresses.0.id%7CAddresses.1.id';

		$expected = array(
			'div' => array('style' => 'display:none;'),
			array('input' => array(
				'type' => 'hidden', 'name' => 'data[_Token][fields]',
				'value' => $hash, 'id' => 'preg:/TokenFields\d+/'
			)),
			array('input' => array(
				'type' => 'hidden', 'name' => 'data[_Token][unlocked]',
				'value' => '', 'id' => 'preg:/TokenUnlocked\d+/'
			)),
			'/div'
		);
		$this->assertTags($result, $expected);
	}

/**
 * Test form security with Model.field.0 style inputs
 *
 * @return void
 */
	public function testFormSecurityArrayFields() {
		$key = 'testKey';

		$this->Form->request->params['_Token']['key'] = $key;
		$this->Form->create('Address');
		$this->Form->input('Address.primary.1');
		$this->assertEquals('Address.primary', $this->Form->fields[0]);

		$this->Form->input('Address.secondary.1.0');
		$this->assertEquals('Address.secondary', $this->Form->fields[1]);
	}

/**
 * testFormSecurityMultipleInputDisabledFields method
 *
 * test secure form generation with multiple records and disabled fields.
 *
 * @return void
 */
	public function testFormSecurityMultipleInputDisabledFields() {
		$key = 'testKey';
		$this->Form->request->params['_Token'] = array(
			'key' => $key,
			'unlockedFields' => array('first_name', 'address')
		);
		$this->Form->create();

		$this->Form->hidden('Addresses.0.id', array('value' => '123456'));
		$this->Form->input('Addresses.0.title');
		$this->Form->input('Addresses.0.first_name');
		$this->Form->input('Addresses.0.last_name');
		$this->Form->input('Addresses.0.address');
		$this->Form->input('Addresses.0.city');
		$this->Form->input('Addresses.0.phone');
		$this->Form->hidden('Addresses.1.id', array('value' => '654321'));
		$this->Form->input('Addresses.1.title');
		$this->Form->input('Addresses.1.first_name');
		$this->Form->input('Addresses.1.last_name');
		$this->Form->input('Addresses.1.address');
		$this->Form->input('Addresses.1.city');
		$this->Form->input('Addresses.1.phone');

		$result = $this->Form->secure($this->Form->fields);
		$hash = '629b6536dcece48aa41a117045628ce602ccbbb2%3AAddresses.0.id%7CAddresses.1.id';

		$expected = array(
			'div' => array('style' => 'display:none;'),
			array('input' => array(
				'type' => 'hidden', 'name' => 'data[_Token][fields]',
				'value' => $hash, 'id' => 'preg:/TokenFields\d+/'
			)),
			array('input' => array(
				'type' => 'hidden', 'name' => 'data[_Token][unlocked]',
				'value' => 'address%7Cfirst_name', 'id' => 'preg:/TokenUnlocked\d+/'
			)),
			'/div'
		);
		$this->assertTags($result, $expected);
	}

/**
 * testFormSecurityInputDisabledFields method
 *
 * Test single record form with disabled fields.
 *
 * @return void
 */
	public function testFormSecurityInputUnlockedFields() {
		$key = 'testKey';
		$this->Form->request['_Token'] = array(
			'key' => $key,
			'unlockedFields' => array('first_name', 'address')
		);
		$this->Form->create();
		$this->assertEquals($this->Form->request['_Token']['unlockedFields'], $this->Form->unlockField());

		$this->Form->hidden('Addresses.id', array('value' => '123456'));
		$this->Form->input('Addresses.title');
		$this->Form->input('Addresses.first_name');
		$this->Form->input('Addresses.last_name');
		$this->Form->input('Addresses.address');
		$this->Form->input('Addresses.city');
		$this->Form->input('Addresses.phone');

		$result = $this->Form->fields;
		$expected = array(
			'Addresses.id' => '123456', 'Addresses.title', 'Addresses.last_name',
			'Addresses.city', 'Addresses.phone'
		);
		$this->assertEquals($expected, $result);

		$result = $this->Form->secure($expected);

		$hash = '2981c38990f3f6ba935e6561dc77277966fabd6d%3AAddresses.id';
		$expected = array(
			'div' => array('style' => 'display:none;'),
			array('input' => array(
				'type' => 'hidden', 'name' => 'data[_Token][fields]',
				'value' => $hash, 'id' => 'preg:/TokenFields\d+/'
			)),
			array('input' => array(
				'type' => 'hidden', 'name' => 'data[_Token][unlocked]',
				'value' => 'address%7Cfirst_name', 'id' => 'preg:/TokenUnlocked\d+/'
			)),
			'/div'
		);
		$this->assertTags($result, $expected);
	}

/**
 * test securing inputs with custom name attributes.
 *
 * @return void
 */
	public function testFormSecureWithCustomNameAttribute() {
		$this->Form->request->params['_Token']['key'] = 'testKey';

		$this->Form->text('UserForm.published', array('name' => 'data[User][custom]'));
		$this->assertEquals('User.custom', $this->Form->fields[0]);

		$this->Form->text('UserForm.published', array('name' => 'data[User][custom][another][value]'));
		$this->assertEquals('User.custom.another.value', $this->Form->fields[1]);
	}

/**
 * testFormSecuredInput method
 *
 * Test generation of entire secure form, assertions made on input() output.
 *
 * @return void
 */
	public function testFormSecuredInput() {
		$this->Form->request['_Token'] = array('key' => 'testKey');

		$result = $this->Form->create('Contact', array('url' => '/contacts/add'));
		$encoding = strtolower(Configure::read('App.encoding'));
		$expected = array(
			'form' => array('method' => 'post', 'action' => '/contacts/add', 'accept-charset' => $encoding, 'id' => 'ContactAddForm'),
			'div' => array('style' => 'display:none;'),
			array('input' => array('type' => 'hidden', 'name' => '_method', 'value' => 'POST')),
			array('input' => array(
				'type' => 'hidden', 'name' => 'data[_Token][key]',
				'value' => 'testKey', 'id' => 'preg:/Token\d+/'
			)),
			'/div'
		);
		$this->assertTags($result, $expected);

		$result = $this->Form->input('UserForm.published', array('type' => 'text'));
		$expected = array(
			'div' => array('class' => 'input text'),
			'label' => array('for' => 'UserFormPublished'),
			'Published',
			'/label',
			array('input' => array(
				'type' => 'text', 'name' => 'data[UserForm][published]',
				'id' => 'UserFormPublished'
			)),
			'/div'
		);
		$this->assertTags($result, $expected);

		$result = $this->Form->input('UserForm.other', array('type' => 'text'));
		$expected = array(
			'div' => array('class' => 'input text'),
			'label' => array('for' => 'UserFormOther'),
			'Other',
			'/label',
			array('input' => array(
				'type' => 'text', 'name' => 'data[UserForm][other]',
				'id' => 'UserFormOther'
			)),
			'/div'
		);
		$this->assertTags($result, $expected);

		$result = $this->Form->hidden('UserForm.stuff');
		$expected = array('input' => array(
				'type' => 'hidden', 'name' => 'data[UserForm][stuff]',
				'id' => 'UserFormStuff'
		));
		$this->assertTags($result, $expected);

		$result = $this->Form->hidden('UserForm.hidden', array('value' => '0'));
		$expected = array('input' => array(
			'type' => 'hidden', 'name' => 'data[UserForm][hidden]',
			'value' => '0', 'id' => 'UserFormHidden'
		));
		$this->assertTags($result, $expected);

		$result = $this->Form->input('UserForm.something', array('type' => 'checkbox'));
		$expected = array(
			'div' => array('class' => 'input checkbox'),
			array('input' => array(
				'type' => 'hidden', 'name' => 'data[UserForm][something]',
				'value' => '0', 'id' => 'UserFormSomething_'
			)),
			array('input' => array(
				'type' => 'checkbox', 'name' => 'data[UserForm][something]',
				'value' => '1', 'id' => 'UserFormSomething'
			)),
			'label' => array('for' => 'UserFormSomething'),
			'Something',
			'/label',
			'/div'
		);
		$this->assertTags($result, $expected);

		$result = $this->Form->fields;
		$expected = array(
			'UserForm.published', 'UserForm.other', 'UserForm.stuff' => '',
			'UserForm.hidden' => '0', 'UserForm.something'
		);
		$this->assertEquals($expected, $result);

		$hash = 'bd7c4a654e5361f9a433a43f488ff9a1065d0aaf%3AUserForm.hidden%7CUserForm.stuff';

		$result = $this->Form->secure($this->Form->fields);
		$expected = array(
			'div' => array('style' => 'display:none;'),
			array('input' => array(
				'type' => 'hidden', 'name' => 'data[_Token][fields]',
				'value' => $hash, 'id' => 'preg:/TokenFields\d+/'
			)),
			array('input' => array(
				'type' => 'hidden', 'name' => 'data[_Token][unlocked]',
				'value' => '', 'id' => 'preg:/TokenUnlocked\d+/'
			)),
			'/div'
		);
		$this->assertTags($result, $expected);
	}

/**
 * Tests that the correct keys are added to the field hash index
 *
 * @return void
 */
	public function testFormSecuredFileInput() {
		$this->Form->request['_Token'] = array('key' => 'testKey');
		$this->assertEquals(array(), $this->Form->fields);

		$result = $this->Form->file('Attachment.file');
		$expected = array(
			'Attachment.file.name', 'Attachment.file.type', 'Attachment.file.tmp_name',
			'Attachment.file.error', 'Attachment.file.size'
		);
		$this->assertEquals($expected, $this->Form->fields);
	}

/**
 * test that multiple selects keys are added to field hash
 *
 * @return void
 */
	public function testFormSecuredMultipleSelect() {
		$this->Form->request['_Token'] = array('key' => 'testKey');
		$this->assertEquals(array(), $this->Form->fields);
		$options = array('1' => 'one', '2' => 'two');

		$this->Form->select('Model.select', $options);
		$expected = array('Model.select');
		$this->assertEquals($expected, $this->Form->fields);

		$this->Form->fields = array();
		$this->Form->select('Model.select', $options, array('multiple' => true));
		$this->assertEquals($expected, $this->Form->fields);
	}

/**
 * testFormSecuredRadio method
 *
 * @return void
 */
	public function testFormSecuredRadio() {
		$this->Form->request['_Token'] = array('key' => 'testKey');
		$this->assertEquals(array(), $this->Form->fields);
		$options = array('1' => 'option1', '2' => 'option2');

		$this->Form->radio('Test.test', $options);
		$expected = array('Test.test');
		$this->assertEquals($expected, $this->Form->fields);
	}

/**
 * test that forms with disabled inputs + secured forms leave off the inputs from the form
 * hashing.
 *
 * @return void
 */
	public function testFormSecuredAndDisabled() {
		$this->Form->request['_Token'] = array('key' => 'testKey');

		$this->Form->checkbox('Model.checkbox', array('disabled' => true));
		$this->Form->text('Model.text', array('disabled' => true));
		$this->Form->password('Model.text', array('disabled' => true));
		$this->Form->textarea('Model.textarea', array('disabled' => true));
		$this->Form->select('Model.select', array(1, 2), array('disabled' => true));
		$this->Form->radio('Model.radio', array(1, 2), array('disabled' => array(1, 2)));
		$this->Form->year('Model.year', null, null, array('disabled' => true));
		$this->Form->month('Model.month', array('disabled' => true));
		$this->Form->day('Model.day', array('disabled' => true));
		$this->Form->hour('Model.hour', false, array('disabled' => true));
		$this->Form->minute('Model.minute', array('disabled' => true));
		$this->Form->meridian('Model.meridian', array('disabled' => true));

		$expected = array(
			'Model.radio' => ''
		);
		$this->assertEquals($expected, $this->Form->fields);
	}

/**
 * testDisableSecurityUsingForm method
 *
 * @return void
 */
	public function testDisableSecurityUsingForm() {
		$this->Form->request['_Token'] = array(
			'key' => 'testKey',
			'disabledFields' => array()
		);
		$this->Form->create();

		$this->Form->hidden('Addresses.id', array('value' => '123456'));
		$this->Form->input('Addresses.title');
		$this->Form->input('Addresses.first_name', array('secure' => false));
		$this->Form->input('Addresses.city', array('type' => 'textarea', 'secure' => false));
		$this->Form->input('Addresses.zip', array(
			'type' => 'select', 'options' => array(1,2), 'secure' => false
		));

		$result = $this->Form->fields;
		$expected = array(
			'Addresses.id' => '123456', 'Addresses.title',
		);
		$this->assertEquals($expected, $result);
	}

/**
 * test disableField
 *
 * @return void
 */
	public function testUnlockFieldAddsToList() {
		$this->Form->request['_Token'] = array(
			'key' => 'testKey',
			'unlockedFields' => array()
		);
		$this->Form->create('Contact');
		$this->Form->unlockField('Contact.name');
		$this->Form->text('Contact.name');

		$this->assertEquals(array('Contact.name'), $this->Form->unlockField());
		$this->assertEquals(array(), $this->Form->fields);
	}

/**
 * test unlockField removing from fields array.
 *
 * @return void
 */
	public function testUnlockFieldRemovingFromFields() {
		$this->Form->request['_Token'] = array(
			'key' => 'testKey',
			'unlockedFields' => array()
		);
		$this->Form->create('Contact');
		$this->Form->hidden('Contact.id', array('value' => 1));
		$this->Form->text('Contact.name');

		$this->assertEquals(1, $this->Form->fields['Contact.id'], 'Hidden input should be secured.');
		$this->assertTrue(in_array('Contact.name', $this->Form->fields), 'Field should be secured.');

		$this->Form->unlockField('Contact.name');
		$this->Form->unlockField('Contact.id');
		$this->assertEquals(array(), $this->Form->fields);
	}

/**
 * testTagIsInvalid method
 *
 * @return void
 */
	public function testTagIsInvalid() {
		$Contact = ClassRegistry::getObject('Contact');
		$Contact->validationErrors[0]['email'] = array('Please provide an email');

		$this->Form->setEntity('Contact.0.email');
		$result = $this->Form->tagIsInvalid();
		$expected = array('Please provide an email');
		$this->assertEquals($expected, $result);

		$this->Form->setEntity('Contact.1.email');
		$result = $this->Form->tagIsInvalid();
		$expected = false;
		$this->assertIdentical($expected, $result);

		$this->Form->setEntity('Contact.0.name');
		$result = $this->Form->tagIsInvalid();
		$expected = false;
		$this->assertIdentical($expected, $result);
	}

/**
 * testPasswordValidation method
 *
 * test validation errors on password input.
 *
 * @return void
 */
	public function testPasswordValidation() {
		$Contact = ClassRegistry::getObject('Contact');
		$Contact->validationErrors['password'] = array('Please provide a password');

		$result = $this->Form->input('Contact.password');
		$expected = array(
			'div' => array('class' => 'input password error'),
			'label' => array('for' => 'ContactPassword'),
			'Password',
			'/label',
			'input' => array(
				'type' => 'password', 'name' => 'data[Contact][password]',
				'id' => 'ContactPassword', 'class' => 'form-error'
			),
			array('div' => array('class' => 'error-message')),
			'Please provide a password',
			'/div',
			'/div'
		);
		$this->assertTags($result, $expected);
	}

/**
 * testEmptyErrorValidation method
 *
 * test validation error div when validation message is an empty string
 *
 * @access public
 * @return void
 */
	public function testEmptyErrorValidation() {
		$this->Form->validationErrors['Contact']['password'] = '';
		$result = $this->Form->input('Contact.password');
		$expected = array(
			'div' => array('class' => 'input password error'),
			'label' => array('for' => 'ContactPassword'),
			'Password',
			'/label',
			'input' => array(
				'type' => 'password', 'name' => 'data[Contact][password]',
				'id' => 'ContactPassword', 'class' => 'form-error'
			),
			array('div' => array('class' => 'error-message')),
			array(),
			'/div',
			'/div'
		);
		$this->assertTags($result, $expected);
	}

/**
 * testEmptyInputErrorValidation method
 *
 * test validation error div when validation message is overridden by an empty string when calling input()
 *
 * @access public
 * @return void
 */
	public function testEmptyInputErrorValidation() {
		$this->Form->validationErrors['Contact']['password'] = 'Please provide a password';
		$result = $this->Form->input('Contact.password', array('error' => ''));
		$expected = array(
			'div' => array('class' => 'input password error'),
			'label' => array('for' => 'ContactPassword'),
			'Password',
			'/label',
			'input' => array(
				'type' => 'password', 'name' => 'data[Contact][password]',
				'id' => 'ContactPassword', 'class' => 'form-error'
			),
			array('div' => array('class' => 'error-message')),
			array(),
			'/div',
			'/div'
		);
		$this->assertTags($result, $expected);
	}

/**
 * testFormValidationAssociated method
 *
 * test display of form errors in conjunction with model::validates.
 *
 * @return void
 */
	public function testFormValidationAssociated() {
		$this->UserForm = ClassRegistry::getObject('UserForm');
		$this->UserForm->OpenidUrl = ClassRegistry::getObject('OpenidUrl');

		$data = array(
			'UserForm' => array('name' => 'user'),
			'OpenidUrl' => array('url' => 'http://www.cakephp.org')
		);

		$result = $this->UserForm->OpenidUrl->create($data);
		$this->assertFalse(empty($result));
		$this->assertFalse($this->UserForm->OpenidUrl->validates());

		$result = $this->Form->create('UserForm', array('type' => 'post', 'action' => 'login'));
		$encoding = strtolower(Configure::read('App.encoding'));
		$expected = array(
			'form' => array(
				'method' => 'post', 'action' => '/user_forms/login', 'id' => 'UserFormLoginForm',
				'accept-charset' => $encoding
			),
			'div' => array('style' => 'display:none;'),
			'input' => array('type' => 'hidden', 'name' => '_method', 'value' => 'POST'),
			'/div'
		);
		$this->assertTags($result, $expected);

		$result = $this->Form->error(
			'OpenidUrl.openid_not_registered', 'Error, not registered', array('wrap' => false)
		);
		$this->assertEquals('Error, not registered', $result);

		unset($this->UserForm->OpenidUrl, $this->UserForm);
	}

/**
 * testFormValidationAssociatedFirstLevel method
 *
 * test form error display with associated model.
 *
 * @return void
 */
	public function testFormValidationAssociatedFirstLevel() {
		$this->ValidateUser = ClassRegistry::getObject('ValidateUser');
		$this->ValidateUser->ValidateProfile = ClassRegistry::getObject('ValidateProfile');

		$data = array(
			'ValidateUser' => array('name' => 'mariano'),
			'ValidateProfile' => array('full_name' => 'Mariano Iglesias')
		);

		$result = $this->ValidateUser->create($data);
		$this->assertFalse(empty($result));
		$this->assertFalse($this->ValidateUser->validates());
		$this->assertFalse($this->ValidateUser->ValidateProfile->validates());

		$result = $this->Form->create('ValidateUser', array('type' => 'post', 'action' => 'add'));
		$encoding = strtolower(Configure::read('App.encoding'));
		$expected = array(
			'form' => array('method' => 'post', 'action' => '/validate_users/add', 'id','accept-charset' => $encoding),
			'div' => array('style' => 'display:none;'),
			'input' => array('type' => 'hidden', 'name' => '_method', 'value' => 'POST'),
			'/div'
		);
		$this->assertTags($result, $expected);

		$result = $this->Form->error(
			'ValidateUser.email', 'Invalid email', array('wrap' => false)
		);
		$this->assertEquals('Invalid email', $result);

		$result = $this->Form->error(
			'ValidateProfile.full_name', 'Invalid name', array('wrap' => false)
		);
		$this->assertEquals('Invalid name', $result);

		$result = $this->Form->error(
			'ValidateProfile.city', 'Invalid city', array('wrap' => false)
		);
		$this->assertEquals('Invalid city', $result);

		unset($this->ValidateUser->ValidateProfile);
		unset($this->ValidateUser);
	}

/**
 * testFormValidationAssociatedSecondLevel method
 *
 * test form error display with associated model.
 *
 * @return void
 */
	public function testFormValidationAssociatedSecondLevel() {
		$this->ValidateUser = ClassRegistry::getObject('ValidateUser');
		$this->ValidateUser->ValidateProfile = ClassRegistry::getObject('ValidateProfile');
		$this->ValidateUser->ValidateProfile->ValidateItem = ClassRegistry::getObject('ValidateItem');

		$data = array(
			'ValidateUser' => array('name' => 'mariano'),
			'ValidateProfile' => array('full_name' => 'Mariano Iglesias'),
			'ValidateItem' => array('name' => 'Item')
		);

		$result = $this->ValidateUser->create($data);
		$this->assertFalse(empty($result));
		$this->assertFalse($this->ValidateUser->validates());
		$this->assertFalse($this->ValidateUser->ValidateProfile->validates());
		$this->assertFalse($this->ValidateUser->ValidateProfile->ValidateItem->validates());

		$result = $this->Form->create('ValidateUser', array('type' => 'post', 'action' => 'add'));
		$encoding = strtolower(Configure::read('App.encoding'));
		$expected = array(
			'form' => array('method' => 'post', 'action' => '/validate_users/add', 'id','accept-charset' => $encoding),
			'div' => array('style' => 'display:none;'),
			'input' => array('type' => 'hidden', 'name' => '_method', 'value' => 'POST'),
			'/div'
		);
		$this->assertTags($result, $expected);

		$result = $this->Form->error(
			'ValidateUser.email', 'Invalid email', array('wrap' => false)
		);
		$this->assertEquals('Invalid email', $result);

		$result = $this->Form->error(
			'ValidateProfile.full_name', 'Invalid name', array('wrap' => false)
		);
		$this->assertEquals('Invalid name', $result);

		$result = $this->Form->error(
			'ValidateProfile.city', 'Invalid city', array('wrap' => false)
		);

		$result = $this->Form->error(
			'ValidateItem.description', 'Invalid description', array('wrap' => false)
		);
		$this->assertEquals('Invalid description', $result);

		unset($this->ValidateUser->ValidateProfile->ValidateItem);
		unset($this->ValidateUser->ValidateProfile);
		unset($this->ValidateUser);
	}

/**
 * testFormValidationMultiRecord method
 *
 * test form error display with multiple records.
 *
 * @return void
 */
	public function testFormValidationMultiRecord() {
		$Contact = ClassRegistry::getObject('Contact');
		$Contact->validationErrors[2] = array(
			'name' => array('This field cannot be left blank')
		);
		$result = $this->Form->input('Contact.2.name');
		$expected = array(
			'div' => array('class' => 'input text error'),
			'label' => array('for' => 'Contact2Name'),
			'Name',
			'/label',
			'input' => array(
				'type' => 'text', 'name' => 'data[Contact][2][name]', 'id' => 'Contact2Name',
				'class' => 'form-error', 'maxlength' => 255
			),
			array('div' => array('class' => 'error-message')),
			'This field cannot be left blank',
			'/div',
			'/div'
		);
		$this->assertTags($result, $expected);
	}

/**
 * testMultipleInputValidation method
 *
 * test multiple record form validation error display.
 *
 * @return void
 */
	public function testMultipleInputValidation() {
		$Address = ClassRegistry::init(array('class' => 'Address', 'table' => false, 'ds' => 'test'));
		$Address->validationErrors[0] = array(
			'title' => array('This field cannot be empty'),
			'first_name' => array('This field cannot be empty')
		);
		$Address->validationErrors[1] = array(
			'last_name' => array('You must have a last name')
		);
		$this->Form->create();

		$result = $this->Form->input('Address.0.title');
		$expected = array(
			'div' => array('class'),
			'label' => array('for'),
			'preg:/[^<]+/',
			'/label',
			'input' => array(
				'type' => 'text', 'name', 'id', 'class' => 'form-error'
			),
			array('div' => array('class' => 'error-message')),
			'This field cannot be empty',
			'/div',
			'/div'
		);
		$this->assertTags($result, $expected);

		$result = $this->Form->input('Address.0.first_name');
		$expected = array(
			'div' => array('class'),
			'label' => array('for'),
			'preg:/[^<]+/',
			'/label',
			'input' => array('type' => 'text', 'name', 'id', 'class' => 'form-error'),
			array('div' => array('class' => 'error-message')),
			'This field cannot be empty',
			'/div',
			'/div'
		);
		$this->assertTags($result, $expected);

		$result = $this->Form->input('Address.0.last_name');
		$expected = array(
			'div' => array('class'),
			'label' => array('for'),
			'preg:/[^<]+/',
			'/label',
			'input' => array('type' => 'text', 'name', 'id'),
			'/div'
		);
		$this->assertTags($result, $expected);

		$result = $this->Form->input('Address.1.last_name');
		$expected = array(
			'div' => array('class'),
			'label' => array('for'),
			'preg:/[^<]+/',
			'/label',
			'input' => array(
				'type' => 'text', 'name' => 'preg:/[^<]+/',
				'id' => 'preg:/[^<]+/', 'class' => 'form-error'
			),
			array('div' => array('class' => 'error-message')),
			'You must have a last name',
			'/div',
			'/div'
		);
		$this->assertTags($result, $expected);
	}

/**
 * testInput method
 *
 * Test various incarnations of input().
 *
 * @return void
 */
	public function testInput() {
		$result = $this->Form->input('ValidateUser.balance');
		$expected = array(
			'div' => array('class'),
			'label' => array('for'),
			'Balance',
			'/label',
			'input' => array('name', 'type' => 'number', 'maxlength' => 8, 'id'),
			'/div',
		);
		$this->assertTags($result, $expected);

		$result = $this->Form->input('Contact.email', array('id' => 'custom'));
		$expected = array(
			'div' => array('class' => 'input text'),
			'label' => array('for' => 'custom'),
			'Email',
			'/label',
			array('input' => array(
				'type' => 'text', 'name' => 'data[Contact][email]',
				'id' => 'custom', 'maxlength' => 255
			)),
			'/div'
		);
		$this->assertTags($result, $expected);

		$result = $this->Form->input('Contact.email', array('div' => array('class' => false)));
		$expected = array(
			'<div',
			'label' => array('for' => 'ContactEmail'),
			'Email',
			'/label',
			array('input' => array(
				'type' => 'text', 'name' => 'data[Contact][email]',
				'id' => 'ContactEmail', 'maxlength' => 255
			)),
			'/div'
		);
		$this->assertTags($result, $expected);

		$result = $this->Form->hidden('Contact.idontexist');
		$expected = array('input' => array(
				'type' => 'hidden', 'name' => 'data[Contact][idontexist]',
				'id' => 'ContactIdontexist'
		));
		$this->assertTags($result, $expected);

		$result = $this->Form->input('Contact.email', array('type' => 'text'));
		$expected = array(
			'div' => array('class' => 'input text'),
			'label' => array('for' => 'ContactEmail'),
			'Email',
			'/label',
			array('input' => array(
				'type' => 'text', 'name' => 'data[Contact][email]',
				'id' => 'ContactEmail'
			)),
			'/div'
		);
		$this->assertTags($result, $expected);

		$result = $this->Form->input('Contact.5.email', array('type' => 'text'));
		$expected = array(
			'div' => array('class' => 'input text'),
			'label' => array('for' => 'Contact5Email'),
			'Email',
			'/label',
			array('input' => array(
				'type' => 'text', 'name' => 'data[Contact][5][email]',
				'id' => 'Contact5Email'
			)),
			'/div'
		);
		$this->assertTags($result, $expected);

		$result = $this->Form->input('Contact.password');
		$expected = array(
			'div' => array('class' => 'input password'),
			'label' => array('for' => 'ContactPassword'),
			'Password',
			'/label',
			array('input' => array(
				'type' => 'password', 'name' => 'data[Contact][password]',
				'id' => 'ContactPassword'
			)),
			'/div'
		);
		$this->assertTags($result, $expected);

		$result = $this->Form->input('Contact.email', array(
			'type' => 'file', 'class' => 'textbox'
		));
		$expected = array(
			'div' => array('class' => 'input file'),
			'label' => array('for' => 'ContactEmail'),
			'Email',
			'/label',
			array('input' => array(
				'type' => 'file', 'name' => 'data[Contact][email]', 'class' => 'textbox',
				'id' => 'ContactEmail'
			)),
			'/div'
		);
		$this->assertTags($result, $expected);

		$this->Form->request->data = array('Contact' => array('phone' => 'Hello & World > weird chars'));
		$result = $this->Form->input('Contact.phone');
		$expected = array(
			'div' => array('class' => 'input text'),
			'label' => array('for' => 'ContactPhone'),
			'Phone',
			'/label',
			array('input' => array(
				'type' => 'text', 'name' => 'data[Contact][phone]',
				'value' => 'Hello &amp; World &gt; weird chars',
				'id' => 'ContactPhone', 'maxlength' => 255
			)),
			'/div'
		);
		$this->assertTags($result, $expected);

		$this->Form->request->data['Model']['0']['OtherModel']['field'] = 'My value';
		$result = $this->Form->input('Model.0.OtherModel.field', array('id' => 'myId'));
		$expected = array(
			'div' => array('class' => 'input text'),
			'label' => array('for' => 'myId'),
			'Field',
			'/label',
			'input' => array(
				'type' => 'text', 'name' => 'data[Model][0][OtherModel][field]',
				'value' => 'My value', 'id' => 'myId'
			),
			'/div'
		);
		$this->assertTags($result, $expected);

		unset($this->Form->request->data);

		$Contact = ClassRegistry::getObject('Contact');
		$Contact->validationErrors['field'] = array('Badness!');
		$result = $this->Form->input('Contact.field');
		$expected = array(
			'div' => array('class' => 'input text error'),
			'label' => array('for' => 'ContactField'),
			'Field',
			'/label',
			'input' => array(
				'type' => 'text', 'name' => 'data[Contact][field]',
				'id' => 'ContactField', 'class' => 'form-error'
			),
			array('div' => array('class' => 'error-message')),
			'Badness!',
			'/div',
			'/div'
		);
		$this->assertTags($result, $expected);

		$result = $this->Form->input('Contact.field', array(
			'div' => false, 'error' => array('attributes' => array('wrap' => 'span'))
		));
		$expected = array(
			'label' => array('for' => 'ContactField'),
			'Field',
			'/label',
			'input' => array(
				'type' => 'text', 'name' => 'data[Contact][field]',
				'id' => 'ContactField', 'class' => 'form-error'
			),
			array('span' => array('class' => 'error-message')),
			'Badness!',
			'/span'
		);
		$this->assertTags($result, $expected);

		$result = $this->Form->input('Contact.field', array(
			'type' => 'text', 'error' => array('attributes' => array('class' => 'error'))
		));
		$expected = array(
			'div' => array('class' => 'input text error'),
			'label' => array('for' => 'ContactField'),
			'Field',
			'/label',
			'input' => array(
				'type' => 'text', 'name' => 'data[Contact][field]',
				'id' => 'ContactField', 'class' => 'form-error'
			),
			array('div' => array('class' => 'error')),
			'Badness!',
			'/div'
		);
		$this->assertTags($result, $expected);

		$result = $this->Form->input('Contact.field', array(
			'div' => array('tag' => 'span'), 'error' => array('attributes' => array('wrap' => false))
		));
		$expected = array(
			'span' => array('class' => 'input text error'),
			'label' => array('for' => 'ContactField'),
			'Field',
			'/label',
			'input' => array(
				'type' => 'text', 'name' => 'data[Contact][field]',
				'id' => 'ContactField', 'class' => 'form-error'
			),
			'Badness!',
			'/span'
		);
		$this->assertTags($result, $expected);

		$result = $this->Form->input('Contact.field', array('after' => 'A message to you, Rudy'));
		$expected = array(
			'div' => array('class' => 'input text error'),
			'label' => array('for' => 'ContactField'),
			'Field',
			'/label',
			'input' => array(
				'type' => 'text', 'name' => 'data[Contact][field]',
				'id' => 'ContactField', 'class' => 'form-error'
			),
			'A message to you, Rudy',
			array('div' => array('class' => 'error-message')),
			'Badness!',
			'/div',
			'/div'
		);
		$this->assertTags($result, $expected);

		$this->Form->setEntity(null);
		$this->Form->setEntity('Contact.field');
		$result = $this->Form->input('Contact.field', array(
			'after' => 'A message to you, Rudy', 'error' => false
		));
		$expected = array(
			'div' => array('class' => 'input text'),
			'label' => array('for' => 'ContactField'),
			'Field',
			'/label',
			'input' => array('type' => 'text', 'name' => 'data[Contact][field]', 'id' => 'ContactField', 'class' => 'form-error'),
			'A message to you, Rudy',
			'/div'
		);
		$this->assertTags($result, $expected);

		$result = $this->Form->input('Object.field', array('after' => 'A message to you, Rudy'));
		$expected = array(
			'div' => array('class' => 'input text'),
			'label' => array('for' => 'ObjectField'),
			'Field',
			'/label',
			'input' => array('type' => 'text', 'name' => 'data[Object][field]', 'id' => 'ObjectField'),
			'A message to you, Rudy',
			'/div'
		);
		$this->assertTags($result, $expected);

		$Contact->validationErrors['field'] = array('minLength');
		$result = $this->Form->input('Contact.field', array(
			'error' => array(
				'minLength' => 'Le login doit contenir au moins 2 caractères',
				'maxLength' => 'login too large'
			)
		));
		$expected = array(
			'div' => array('class' => 'input text error'),
			'label' => array('for' => 'ContactField'),
			'Field',
			'/label',
			'input' => array('type' => 'text', 'name' => 'data[Contact][field]', 'id' => 'ContactField', 'class' => 'form-error'),
			array('div' => array('class' => 'error-message')),
			'Le login doit contenir au moins 2 caractères',
			'/div',
			'/div'
		);
		$this->assertTags($result, $expected);

		$Contact->validationErrors['field'] = array('maxLength');
		$result = $this->Form->input('Contact.field', array(
			'error' => array(
				'attributes' => array('wrap' => 'span', 'rel' => 'fake'),
				'minLength' => 'Le login doit contenir au moins 2 caractères',
				'maxLength' => 'login too large',
			)
		));
		$expected = array(
			'div' => array('class' => 'input text error'),
			'label' => array('for' => 'ContactField'),
			'Field',
			'/label',
			'input' => array('type' => 'text', 'name' => 'data[Contact][field]', 'id' => 'ContactField', 'class' => 'form-error'),
			array('span' => array('class' => 'error-message', 'rel' => 'fake')),
			'login too large',
			'/span',
			'/div'
		);
		$this->assertTags($result, $expected);
	}

/**
 * test input() with checkbox creation
 *
 * @return void
 */
	public function testInputCheckbox() {
		$result = $this->Form->input('User.active', array('label' => false, 'checked' => true));
		$expected = array(
			'div' => array('class' => 'input checkbox'),
			'input' => array('type' => 'hidden', 'name' => 'data[User][active]', 'value' => '0', 'id' => 'UserActive_'),
			array('input' => array('type' => 'checkbox', 'name' => 'data[User][active]', 'value' => '1', 'id' => 'UserActive', 'checked' => 'checked')),
			'/div'
		);
		$this->assertTags($result, $expected);

		$result = $this->Form->input('User.active', array('label' => false, 'checked' => 1));
		$expected = array(
			'div' => array('class' => 'input checkbox'),
			'input' => array('type' => 'hidden', 'name' => 'data[User][active]', 'value' => '0', 'id' => 'UserActive_'),
			array('input' => array('type' => 'checkbox', 'name' => 'data[User][active]', 'value' => '1', 'id' => 'UserActive', 'checked' => 'checked')),
			'/div'
		);
		$this->assertTags($result, $expected);

		$result = $this->Form->input('User.active', array('label' => false, 'checked' => '1'));
		$expected = array(
			'div' => array('class' => 'input checkbox'),
			'input' => array('type' => 'hidden', 'name' => 'data[User][active]', 'value' => '0', 'id' => 'UserActive_'),
			array('input' => array('type' => 'checkbox', 'name' => 'data[User][active]', 'value' => '1', 'id' => 'UserActive', 'checked' => 'checked')),
			'/div'
		);
		$this->assertTags($result, $expected);
	}

/**
 * test form->input() with time types.
 *
 */
	public function testInputTime() {
		extract($this->dateRegex);
		$result = $this->Form->input('Contact.created', array('type' => 'time', 'timeFormat' => 24));
		$result = explode(':', $result);
		$this->assertRegExp('/option value="23"/', $result[0]);
		$this->assertNotRegExp('/option value="24"/', $result[0]);

		$result = $this->Form->input('Contact.created', array('type' => 'time', 'timeFormat' => 24));
		$result = explode(':', $result);
		$this->assertRegExp('/option value="23"/', $result[0]);
		$this->assertNotRegExp('/option value="24"/', $result[0]);

		$result = $this->Form->input('Model.field', array(
			'type' => 'time', 'timeFormat' => 24, 'interval' => 15
		));
		$result = explode(':', $result);
		$this->assertNotRegExp('#<option value="12"[^>]*>12</option>#', $result[1]);
		$this->assertNotRegExp('#<option value="50"[^>]*>50</option>#', $result[1]);
		$this->assertRegExp('#<option value="15"[^>]*>15</option>#', $result[1]);

		$result = $this->Form->input('Model.field', array(
			'type' => 'time', 'timeFormat' => 12, 'interval' => 15
		));
		$result = explode(':', $result);
		$this->assertNotRegExp('#<option value="12"[^>]*>12</option>#', $result[1]);
		$this->assertNotRegExp('#<option value="50"[^>]*>50</option>#', $result[1]);
		$this->assertRegExp('#<option value="15"[^>]*>15</option>#', $result[1]);

		$result = $this->Form->input('prueba', array(
			'type' => 'time', 'timeFormat' => 24 , 'dateFormat' => 'DMY' , 'minYear' => 2008,
			'maxYear' => date('Y') + 1 ,'interval' => 15
		));
		$result = explode(':', $result);
		$this->assertNotRegExp('#<option value="12"[^>]*>12</option>#', $result[1]);
		$this->assertNotRegExp('#<option value="50"[^>]*>50</option>#', $result[1]);
		$this->assertRegExp('#<option value="15"[^>]*>15</option>#', $result[1]);
		$this->assertRegExp('#<option value="30"[^>]*>30</option>#', $result[1]);

		$result = $this->Form->input('Random.start_time', array(
			'type' => 'time',
			'selected' => '18:15'
		));
		$this->assertRegExp('#<option value="06"[^>]*>6</option>#', $result);
		$this->assertRegExp('#<option value="15"[^>]*>15</option>#', $result);
		$this->assertRegExp('#<option value="pm"[^>]*>pm</option>#', $result);
	}

/**
 * test form->input() with datetime, date and time types
 *
 * @return void
 */
	public function testInputDatetime() {
		extract($this->dateRegex);
		$result = $this->Form->input('prueba', array(
			'type' => 'datetime', 'timeFormat' => 24, 'dateFormat' => 'DMY', 'minYear' => 2008,
			'maxYear' => date('Y') + 1, 'interval' => 15
		));
		$result = explode(':', $result);
		$this->assertNotRegExp('#<option value="12"[^>]*>12</option>#', $result[1]);
		$this->assertNotRegExp('#<option value="50"[^>]*>50</option>#', $result[1]);
		$this->assertRegExp('#<option value="15"[^>]*>15</option>#', $result[1]);
		$this->assertRegExp('#<option value="30"[^>]*>30</option>#', $result[1]);

		//related to ticket #5013
		$result = $this->Form->input('Contact.date', array(
			'type' => 'date', 'class' => 'customClass', 'onChange' => 'function(){}'
		));
		$this->assertRegExp('/class="customClass"/', $result);
		$this->assertRegExp('/onChange="function\(\)\{\}"/', $result);

		$result = $this->Form->input('Contact.date', array(
			'type' => 'date', 'id' => 'customId', 'onChange' => 'function(){}'
		));
		$this->assertRegExp('/id="customIdDay"/', $result);
		$this->assertRegExp('/id="customIdMonth"/', $result);
		$this->assertRegExp('/onChange="function\(\)\{\}"/', $result);

		$result = $this->Form->input('Model.field', array(
			'type' => 'datetime', 'timeFormat' => 24, 'id' => 'customID'
		));
		$this->assertRegExp('/id="customIDDay"/', $result);
		$this->assertRegExp('/id="customIDHour"/', $result);
		$result = explode('</select><select', $result);
		$result = explode(':', $result[1]);
		$this->assertRegExp('/option value="23"/', $result[0]);
		$this->assertNotRegExp('/option value="24"/', $result[0]);

		$result = $this->Form->input('Model.field', array(
			'type' => 'datetime', 'timeFormat' => 12
		));
		$result = explode('</select><select', $result);
		$result = explode(':', $result[1]);
		$this->assertRegExp('/option value="12"/', $result[0]);
		$this->assertNotRegExp('/option value="13"/', $result[0]);

		$this->Form->request->data = array('Contact' => array('created' => null));
		$result = $this->Form->input('Contact.created', array('empty' => 'Date Unknown'));
		$expected = array(
			'div' => array('class' => 'input date'),
			'label' => array('for' => 'ContactCreatedMonth'),
			'Created',
			'/label',
			array('select' => array('name' => 'data[Contact][created][month]', 'id' => 'ContactCreatedMonth')),
			array('option' => array('value' => '')), 'Date Unknown', '/option',
			$monthsRegex,
			'/select', '-',
			array('select' => array('name' => 'data[Contact][created][day]', 'id' => 'ContactCreatedDay')),
			array('option' => array('value' => '')), 'Date Unknown', '/option',
			$daysRegex,
			'/select', '-',
			array('select' => array('name' => 'data[Contact][created][year]', 'id' => 'ContactCreatedYear')),
			array('option' => array('value' => '')), 'Date Unknown', '/option',
			$yearsRegex,
			'/select',
			'/div'
		);
		$this->assertTags($result, $expected);

		$this->Form->request->data = array('Contact' => array('created' => null));
		$result = $this->Form->input('Contact.created', array('type' => 'datetime', 'dateFormat' => 'NONE'));
		$this->assertRegExp('/for\="ContactCreatedHour"/', $result);

		$this->Form->request->data = array('Contact' => array('created' => null));
		$result = $this->Form->input('Contact.created', array('type' => 'datetime', 'timeFormat' => 'NONE'));
		$this->assertRegExp('/for\="ContactCreatedMonth"/', $result);

		$result = $this->Form->input('Contact.created', array(
			'type' => 'date',
			'id' => array('day' => 'created-day', 'month' => 'created-month', 'year' => 'created-year'),
			'timeFormat' => 'NONE'
		));
		$this->assertRegExp('/for\="created-month"/', $result);
	}

/**
 * Test generating checkboxes in a loop.
 *
 * @return void
 */
	public function testInputCheckboxesInLoop() {
		for ($i = 1; $i < 5; $i++) {
			$result = $this->Form->input("Contact.{$i}.email", array('type' => 'checkbox', 'value' => $i));
			$expected = array(
				'div' => array('class' => 'input checkbox'),
				'input' => array('type' => 'hidden', 'name' => "data[Contact][{$i}][email]", 'value' => '0', 'id' => "Contact{$i}Email_"),
				array('input' => array('type' => 'checkbox', 'name' => "data[Contact][{$i}][email]", 'value' => $i, 'id' => "Contact{$i}Email")),
				'label' => array('for' => "Contact{$i}Email"),
				'Email',
				'/label',
				'/div'
			);
			$this->assertTags($result, $expected);
		}
	}

/**
 * test input name with leading integer, ensure attributes are generated correctly.
 *
 * @return void
 */
	public function testInputWithLeadingInteger() {
		$result = $this->Form->text('0.Node.title');
		$expected = array(
			'input' => array('name' => 'data[0][Node][title]', 'id' => '0NodeTitle', 'type' => 'text')
		);
		$this->assertTags($result, $expected);
	}

/**
 * test form->input() with select type inputs.
 *
 * @return void
 */
	public function testInputSelectType() {
		$result = $this->Form->input('email', array(
			'options' => array('è' => 'Firést', 'é' => 'Secoènd'), 'empty' => true)
		);
		$expected = array(
			'div' => array('class' => 'input select'),
			'label' => array('for' => 'email'),
			'Email',
			'/label',
			array('select' => array('name' => 'data[email]', 'id' => 'email')),
			array('option' => array('value' => '')),
			'/option',
			array('option' => array('value' => 'è')),
			'Firést',
			'/option',
			array('option' => array('value' => 'é')),
			'Secoènd',
			'/option',
			'/select',
			'/div'
		);
		$this->assertTags($result, $expected);

		$result = $this->Form->input('email', array(
			'options' => array('First', 'Second'), 'empty' => true)
		);
		$expected = array(
			'div' => array('class' => 'input select'),
			'label' => array('for' => 'email'),
			'Email',
			'/label',
			array('select' => array('name' => 'data[email]', 'id' => 'email')),
			array('option' => array('value' => '')),
			'/option',
			array('option' => array('value' => '0')),
			'First',
			'/option',
			array('option' => array('value' => '1')),
			'Second',
			'/option',
			'/select',
			'/div'
		);
		$this->assertTags($result, $expected);

		$this->View->viewVars['users'] = array('value' => 'good', 'other' => 'bad');
		$this->Form->request->data = array('Model' => array('user_id' => 'value'));

		$result = $this->Form->input('Model.user_id', array('empty' => true));
		$expected = array(
			'div' => array('class' => 'input select'),
			'label' => array('for' => 'ModelUserId'),
			'User',
			'/label',
			'select' => array('name' => 'data[Model][user_id]', 'id' => 'ModelUserId'),
			array('option' => array('value' => '')),
			'/option',
			array('option' => array('value' => 'value', 'selected' => 'selected')),
			'good',
			'/option',
			array('option' => array('value' => 'other')),
			'bad',
			'/option',
			'/select',
			'/div'
		);
		$this->assertTags($result, $expected);

		$this->View->viewVars['users'] = array('value' => 'good', 'other' => 'bad');
		$this->Form->request->data = array('Thing' => array('user_id' => null));
		$result = $this->Form->input('Thing.user_id', array('empty' => 'Some Empty'));
		$expected = array(
			'div' => array('class' => 'input select'),
			'label' => array('for' => 'ThingUserId'),
			'User',
			'/label',
			'select' => array('name' => 'data[Thing][user_id]', 'id' => 'ThingUserId'),
			array('option' => array('value' => '')),
			'Some Empty',
			'/option',
			array('option' => array('value' => 'value')),
			'good',
			'/option',
			array('option' => array('value' => 'other')),
			'bad',
			'/option',
			'/select',
			'/div'
		);
		$this->assertTags($result, $expected);

		$this->View->viewVars['users'] = array('value' => 'good', 'other' => 'bad');
		$this->Form->request->data = array('Thing' => array('user_id' => 'value'));
		$result = $this->Form->input('Thing.user_id', array('empty' => 'Some Empty'));
		$expected = array(
			'div' => array('class' => 'input select'),
			'label' => array('for' => 'ThingUserId'),
			'User',
			'/label',
			'select' => array('name' => 'data[Thing][user_id]', 'id' => 'ThingUserId'),
			array('option' => array('value' => '')),
			'Some Empty',
			'/option',
			array('option' => array('value' => 'value', 'selected' => 'selected')),
			'good',
			'/option',
			array('option' => array('value' => 'other')),
			'bad',
			'/option',
			'/select',
			'/div'
		);
		$this->assertTags($result, $expected);

		$this->View->viewVars['users'] = array('value' => 'good', 'other' => 'bad');
		$this->Form->request->data = array('User' => array('User' => array('value')));
		$result = $this->Form->input('User.User', array('empty' => true));
		$expected = array(
			'div' => array('class' => 'input select'),
			'label' => array('for' => 'UserUser'),
			'User',
			'/label',
			'input' => array('type' => 'hidden', 'name' => 'data[User][User]', 'value' => '', 'id' => 'UserUser_'),
			'select' => array('name' => 'data[User][User][]', 'id' => 'UserUser', 'multiple' => 'multiple'),
			array('option' => array('value' => '')),
			'/option',
			array('option' => array('value' => 'value', 'selected' => 'selected')),
			'good',
			'/option',
			array('option' => array('value' => 'other')),
			'bad',
			'/option',
			'/select',
			'/div'
		);
		$this->assertTags($result, $expected);

		$this->Form->data = array();
		$result = $this->Form->input('Publisher.id', array(
				'label'		=> 'Publisher',
				'type'		=> 'select',
				'multiple'	=> 'checkbox',
				'options'	=> array('Value 1' => 'Label 1', 'Value 2' => 'Label 2')
		));
		$expected = array(
			array('div' => array('class' => 'input select')),
				array('label' => array('for' => 'PublisherId')),
				'Publisher',
				'/label',
				'input' => array('type' => 'hidden', 'name' => 'data[Publisher][id]', 'value' => '', 'id' => 'PublisherId'),
				array('div' => array('class' => 'checkbox')),
				array('input' => array('type' => 'checkbox', 'name' => 'data[Publisher][id][]', 'value' => 'Value 1', 'id' => 'PublisherIdValue1')),
				array('label' => array('for' => 'PublisherIdValue1')),
				'Label 1',
				'/label',
				'/div',
				array('div' => array('class' => 'checkbox')),
				array('input' => array('type' => 'checkbox', 'name' => 'data[Publisher][id][]', 'value' => 'Value 2', 'id' => 'PublisherIdValue2')),
				array('label' => array('for' => 'PublisherIdValue2')),
				'Label 2',
				'/label',
				'/div',
			'/div'
		);
		$this->assertTags($result, $expected);
	}

/**
 * test that input() and a non standard primary key makes a hidden input by default.
 *
 * @return void
 */
	public function testInputWithNonStandardPrimaryKeyMakesHidden() {
		$this->Form->create('User');
		$this->Form->fieldset = array(
			'User' => array(
				'fields' => array(
					'model_id' => array('type' => 'integer')
				),
				'validates' => array(),
				'key' => 'model_id'
			)
		);
		$result = $this->Form->input('model_id');
		$expected = array(
			'input' => array('type' => 'hidden', 'name' => 'data[User][model_id]', 'id' => 'UserModelId'),
		);
		$this->assertTags($result, $expected);
	}

/**
 * test that overriding the magic select type widget is possible
 *
 * @return void
 */
	public function testInputOverridingMagicSelectType() {
		$this->View->viewVars['users'] = array('value' => 'good', 'other' => 'bad');
		$result = $this->Form->input('Model.user_id', array('type' => 'text'));
		$expected = array(
			'div' => array('class' => 'input text'),
			'label' => array('for' => 'ModelUserId'), 'User', '/label',
			'input' => array('name' => 'data[Model][user_id]', 'type' => 'text', 'id' => 'ModelUserId'),
			'/div'
		);
		$this->assertTags($result, $expected);

		//Check that magic types still work for plural/singular vars
		$this->View->viewVars['types'] = array('value' => 'good', 'other' => 'bad');
		$result = $this->Form->input('Model.type');
		$expected = array(
			'div' => array('class' => 'input select'),
			'label' => array('for' => 'ModelType'), 'Type', '/label',
			'select' => array('name' => 'data[Model][type]', 'id' => 'ModelType'),
			array('option' => array('value' => 'value')), 'good', '/option',
			array('option' => array('value' => 'other')), 'bad', '/option',
			'/select',
			'/div'
		);
		$this->assertTags($result, $expected);
	}

/**
 * Test that magic input() selects can easily be converted into radio types without error.
 *
 * @return void
 */
	public function testInputMagicSelectChangeToRadio() {
		$this->View->viewVars['users'] = array('value' => 'good', 'other' => 'bad');
		$result = $this->Form->input('Model.user_id', array('type' => 'radio'));
		$this->assertRegExp('/input type="radio"/', $result);
	}

/**
 * fields with the same name as the model should work.
 *
 * @return void
 */
	public function testInputWithMatchingFieldAndModelName() {
		$this->Form->create('User');
		$this->Form->fieldset = array(
			'User' => array(
				'fields' => array(
					'User' => array('type' => 'text')
				),
				'validates' => array(),
				'key' => 'id'
			)
		);
		$this->Form->request->data['User']['User'] = 'ABC, Inc.';
		$result = $this->Form->input('User', array('type' => 'text'));
		$expected = array(
			'div' => array('class' => 'input text'),
			'label' => array('for' => 'UserUser'), 'User', '/label',
			'input' => array('name' => 'data[User][User]', 'type' => 'text', 'id' => 'UserUser', 'value' => 'ABC, Inc.'),
			'/div'
		);
		$this->assertTags($result, $expected);
	}

/**
 * testFormInputs method
 *
 * test correct results from form::inputs().
 *
 * @return void
 */
	public function testFormInputs() {
		$this->Form->create('Contact');
		$result = $this->Form->inputs('The Legend');
		$expected = array(
			'<fieldset',
			'<legend',
			'The Legend',
			'/legend',
			'*/fieldset',
		);
		$this->assertTags($result, $expected);

		$result = $this->Form->inputs(array('legend' => 'Field of Dreams', 'fieldset' => 'classy-stuff'));
		$expected = array(
			'fieldset' => array('class' => 'classy-stuff'),
			'<legend',
			'Field of Dreams',
			'/legend',
			'*/fieldset'
		);
		$this->assertTags($result, $expected);

		$this->Form->create('Contact');
		$this->Form->request['prefix'] = 'admin';
		$this->Form->request['action'] = 'admin_edit';
		$result = $this->Form->inputs();
		$expected = array(
			'<fieldset',
			'<legend',
			'Edit Contact',
			'/legend',
			'*/fieldset',
		);
		$this->assertTags($result, $expected);

		$this->Form->create('Contact');
		$result = $this->Form->inputs(false);
		$expected = array(
			'input' => array('type' => 'hidden', 'name' => 'data[Contact][id]', 'id' => 'ContactId'),
			array('div' => array('class' => 'input text')),
			'*/div',
			array('div' => array('class' => 'input text')),
			'*/div',
			array('div' => array('class' => 'input text')),
			'*/div',
			array('div' => array('class' => 'input password')),
			'*/div',
			array('div' => array('class' => 'input date')),
			'*/div',
			array('div' => array('class' => 'input date')),
			'*/div',
			array('div' => array('class' => 'input datetime')),
			'*/div',
			array('div' => array('class' => 'input number')),
			'*/div',
			array('div' => array('class' => 'input select')),
			'*/div',
		);
		$this->assertTags($result, $expected);

		$this->Form->create('Contact');
		$result = $this->Form->inputs(array('fieldset' => false, 'legend' => false));
		$expected = array(
			'input' => array('type' => 'hidden', 'name' => 'data[Contact][id]', 'id' => 'ContactId'),
			array('div' => array('class' => 'input text')),
			'*/div',
			array('div' => array('class' => 'input text')),
			'*/div',
			array('div' => array('class' => 'input text')),
			'*/div',
			array('div' => array('class' => 'input password')),
			'*/div',
			array('div' => array('class' => 'input date')),
			'*/div',
			array('div' => array('class' => 'input date')),
			'*/div',
			array('div' => array('class' => 'input datetime')),
			'*/div',
			array('div' => array('class' => 'input number')),
			'*/div',
			array('div' => array('class' => 'input select')),
			'*/div',
		);
		$this->assertTags($result, $expected);

		$this->Form->create('Contact');
		$result = $this->Form->inputs(array('fieldset' => true, 'legend' => false));
		$expected = array(
			'fieldset' => array(),
			'input' => array('type' => 'hidden', 'name' => 'data[Contact][id]', 'id' => 'ContactId'),
			array('div' => array('class' => 'input text')),
			'*/div',
			array('div' => array('class' => 'input text')),
			'*/div',
			array('div' => array('class' => 'input text')),
			'*/div',
			array('div' => array('class' => 'input password')),
			'*/div',
			array('div' => array('class' => 'input date')),
			'*/div',
			array('div' => array('class' => 'input date')),
			'*/div',
			array('div' => array('class' => 'input datetime')),
			'*/div',
			array('div' => array('class' => 'input number')),
			'*/div',
			array('div' => array('class' => 'input select')),
			'*/div',
			'/fieldset'
		);
		$this->assertTags($result, $expected);

		$this->Form->create('Contact');
		$result = $this->Form->inputs(array('fieldset' => false, 'legend' => 'Hello'));
		$expected = array(
			'input' => array('type' => 'hidden', 'name' => 'data[Contact][id]', 'id' => 'ContactId'),
			array('div' => array('class' => 'input text')),
			'*/div',
			array('div' => array('class' => 'input text')),
			'*/div',
			array('div' => array('class' => 'input text')),
			'*/div',
			array('div' => array('class' => 'input password')),
			'*/div',
			array('div' => array('class' => 'input date')),
			'*/div',
			array('div' => array('class' => 'input date')),
			'*/div',
			array('div' => array('class' => 'input datetime')),
			'*/div',
			array('div' => array('class' => 'input number')),
			'*/div',
			array('div' => array('class' => 'input select')),
			'*/div',
		);
		$this->assertTags($result, $expected);

		$this->Form->create('Contact');
		$result = $this->Form->inputs('Hello');
		$expected = array(
			'fieldset' => array(),
			'legend' => array(),
			'Hello',
			'/legend',
			'input' => array('type' => 'hidden', 'name' => 'data[Contact][id]', 'id' => 'ContactId'),
			array('div' => array('class' => 'input text')),
			'*/div',
			array('div' => array('class' => 'input text')),
			'*/div',
			array('div' => array('class' => 'input text')),
			'*/div',
			array('div' => array('class' => 'input password')),
			'*/div',
			array('div' => array('class' => 'input date')),
			'*/div',
			array('div' => array('class' => 'input date')),
			'*/div',
			array('div' => array('class' => 'input datetime')),
			'*/div',
			array('div' => array('class' => 'input number')),
			'*/div',
			array('div' => array('class' => 'input select')),
			'*/div',
			'/fieldset'
		);
		$this->assertTags($result, $expected);

		$this->Form->create('Contact');
		$result = $this->Form->inputs(array('legend' => 'Hello'));
		$expected = array(
			'fieldset' => array(),
			'legend' => array(),
			'Hello',
			'/legend',
			'input' => array('type' => 'hidden', 'name' => 'data[Contact][id]', 'id' => 'ContactId'),
			array('div' => array('class' => 'input text')),
			'*/div',
			array('div' => array('class' => 'input text')),
			'*/div',
			array('div' => array('class' => 'input text')),
			'*/div',
			array('div' => array('class' => 'input password')),
			'*/div',
			array('div' => array('class' => 'input date')),
			'*/div',
			array('div' => array('class' => 'input date')),
			'*/div',
			array('div' => array('class' => 'input datetime')),
			'*/div',
			array('div' => array('class' => 'input number')),
			'*/div',
			array('div' => array('class' => 'input select')),
			'*/div',
			'/fieldset'
		);
		$this->assertTags($result, $expected);
	}

/**
 * testSelectAsCheckbox method
 *
 * test multi-select widget with checkbox formatting.
 *
 * @return void
 */
	public function testSelectAsCheckbox() {
		$result = $this->Form->select('Model.multi_field', array('first', 'second', 'third'), array('multiple' => 'checkbox', 'value' => array(0, 1)));
		$expected = array(
			'input' => array('type' => 'hidden', 'name' => 'data[Model][multi_field]', 'value' => '', 'id' => 'ModelMultiField'),
			array('div' => array('class' => 'checkbox')),
			array('input' => array('type' => 'checkbox', 'name' => 'data[Model][multi_field][]', 'checked' => 'checked', 'value' => '0', 'id' => 'ModelMultiField0')),
			array('label' => array('for' => 'ModelMultiField0', 'class' => 'selected')),
			'first',
			'/label',
			'/div',
			array('div' => array('class' => 'checkbox')),
			array('input' => array('type' => 'checkbox', 'name' => 'data[Model][multi_field][]', 'checked' => 'checked', 'value' => '1', 'id' => 'ModelMultiField1')),
			array('label' => array('for' => 'ModelMultiField1', 'class' => 'selected')),
			'second',
			'/label',
			'/div',
			array('div' => array('class' => 'checkbox')),
			array('input' => array('type' => 'checkbox', 'name' => 'data[Model][multi_field][]', 'value' => '2', 'id' => 'ModelMultiField2')),
			array('label' => array('for' => 'ModelMultiField2')),
			'third',
			'/label',
			'/div',
		);
		$this->assertTags($result, $expected);

		$result = $this->Form->select('Model.multi_field', array('1/2' => 'half'), array('multiple' => 'checkbox'));
		$expected = array(
			'input' => array('type' => 'hidden', 'name' => 'data[Model][multi_field]', 'value' => '', 'id' => 'ModelMultiField'),
			array('div' => array('class' => 'checkbox')),
			array('input' => array('type' => 'checkbox', 'name' => 'data[Model][multi_field][]', 'value' => '1/2', 'id' => 'ModelMultiField12')),
			array('label' => array('for' => 'ModelMultiField12')),
			'half',
			'/label',
			'/div',
		);
		$this->assertTags($result, $expected);
	}

/**
 * testLabel method
 *
 * test label generation.
 *
 * @return void
 */
	public function testLabel() {
		$this->Form->text('Person.name');
		$result = $this->Form->label();
		$this->assertTags($result, array('label' => array('for' => 'PersonName'), 'Name', '/label'));

		$this->Form->text('Person.name');
		$result = $this->Form->label();
		$this->assertTags($result, array('label' => array('for' => 'PersonName'), 'Name', '/label'));

		$result = $this->Form->label('Person.first_name');
		$this->assertTags($result, array('label' => array('for' => 'PersonFirstName'), 'First Name', '/label'));

		$result = $this->Form->label('Person.first_name', 'Your first name');
		$this->assertTags($result, array('label' => array('for' => 'PersonFirstName'), 'Your first name', '/label'));

		$result = $this->Form->label('Person.first_name', 'Your first name', array('class' => 'my-class'));
		$this->assertTags($result, array('label' => array('for' => 'PersonFirstName', 'class' => 'my-class'), 'Your first name', '/label'));

		$result = $this->Form->label('Person.first_name', 'Your first name', array('class' => 'my-class', 'id' => 'LabelID'));
		$this->assertTags($result, array('label' => array('for' => 'PersonFirstName', 'class' => 'my-class', 'id' => 'LabelID'), 'Your first name', '/label'));

		$result = $this->Form->label('Person.first_name', '');
		$this->assertTags($result, array('label' => array('for' => 'PersonFirstName'), '/label'));

		$result = $this->Form->label('Person.2.name', '');
		$this->assertTags($result, array('label' => array('for' => 'Person2Name'), '/label'));
	}

/**
 * testTextbox method
 *
 * test textbox element generation
 *
 * @return void
 */
	public function testTextbox() {
		$result = $this->Form->text('Model.field');
		$this->assertTags($result, array('input' => array('type' => 'text', 'name' => 'data[Model][field]', 'id' => 'ModelField')));

		$result = $this->Form->text('Model.field', array('type' => 'password'));
		$this->assertTags($result, array('input' => array('type' => 'password', 'name' => 'data[Model][field]', 'id' => 'ModelField')));

		$result = $this->Form->text('Model.field', array('id' => 'theID'));
		$this->assertTags($result, array('input' => array('type' => 'text', 'name' => 'data[Model][field]', 'id' => 'theID')));

		$this->Form->request->data['Model']['text'] = 'test <strong>HTML</strong> values';
		$result = $this->Form->text('Model.text');
		$this->assertTags($result, array('input' => array('type' => 'text', 'name' => 'data[Model][text]', 'value' => 'test &lt;strong&gt;HTML&lt;/strong&gt; values', 'id' => 'ModelText')));

		$Contact = ClassRegistry::getObject('Contact');
		$Contact->validationErrors['text'] = array(true);
		$this->Form->request->data['Contact']['text'] = 'test';
		$result = $this->Form->text('Contact.text', array('id' => 'theID'));
		$this->assertTags($result, array('input' => array('type' => 'text', 'name' => 'data[Contact][text]', 'value' => 'test', 'id' => 'theID', 'class' => 'form-error')));

		$this->Form->request->data['Model']['0']['OtherModel']['field'] = 'My value';
		$result = $this->Form->text('Model.0.OtherModel.field', array('id' => 'myId'));
		$expected = array(
			'input' => array('type' => 'text', 'name' => 'data[Model][0][OtherModel][field]', 'value' => 'My value', 'id' => 'myId')
		);
		$this->assertTags($result, $expected);
	}

/**
 * testDefaultValue method
 *
 * Test default value setting
 *
 * @return void
 */
	public function testDefaultValue() {
		$this->Form->request->data['Model']['field'] = 'test';
		$result = $this->Form->text('Model.field', array('default' => 'default value'));
		$this->assertTags($result, array('input' => array('type' => 'text', 'name' => 'data[Model][field]', 'value' => 'test', 'id' => 'ModelField')));

		unset($this->Form->request->data['Model']['field']);
		$result = $this->Form->text('Model.field', array('default' => 'default value'));
		$this->assertTags($result, array('input' => array('type' => 'text', 'name' => 'data[Model][field]', 'value' => 'default value', 'id' => 'ModelField')));
	}

/**
 * testCheckboxDefaultValue method
 *
 * Test default value setting on checkbox() method
 *
 * @return void
 */
	public function testCheckboxDefaultValue() {
		$this->Form->request->data['Model']['field'] = false;
		$result = $this->Form->checkbox('Model.field', array('default' => true, 'hiddenField' => false));
		$this->assertTags($result, array('input' => array('type' => 'checkbox', 'name' => 'data[Model][field]', 'value' => '1', 'id' => 'ModelField')));

		unset($this->Form->request->data['Model']['field']);
		$result = $this->Form->checkbox('Model.field', array('default' => true, 'hiddenField' => false));
		$this->assertTags($result, array('input' => array('type' => 'checkbox', 'name' => 'data[Model][field]', 'value' => '1', 'id' => 'ModelField', 'checked' => 'checked')));

		$this->Form->request->data['Model']['field'] = true;
		$result = $this->Form->checkbox('Model.field', array('default' => false, 'hiddenField' => false));
		$this->assertTags($result, array('input' => array('type' => 'checkbox', 'name' => 'data[Model][field]', 'value' => '1', 'id' => 'ModelField', 'checked' => 'checked')));

		unset($this->Form->request->data['Model']['field']);
		$result = $this->Form->checkbox('Model.field', array('default' => false, 'hiddenField' => false));
		$this->assertTags($result, array('input' => array('type' => 'checkbox', 'name' => 'data[Model][field]', 'value' => '1', 'id' => 'ModelField')));
	}

/**
 * testError method
 *
 * Test field error generation
 *
 * @return void
 */
	public function testError() {
		$Contact = ClassRegistry::getObject('Contact');
		$Contact->validationErrors['field'] = array(1);
		$result = $this->Form->error('Contact.field');
		$this->assertTags($result, array('div' => array('class' => 'error-message'), 'Error in field Field', '/div'));

		$result = $this->Form->error('Contact.field', null, array('wrap' => false));
		$this->assertEquals('Error in field Field', $result);

		$Contact->validationErrors['field'] = array("This field contains invalid input");
		$result = $this->Form->error('Contact.field', null, array('wrap' => false));
		$this->assertEquals('This field contains invalid input', $result);

		$Contact->validationErrors['field'] = array("This field contains invalid input");
		$result = $this->Form->error('Contact.field', null, array('wrap' => 'span'));
		$this->assertTags($result, array('span' => array('class' => 'error-message'), 'This field contains invalid input', '/span'));

		$result = $this->Form->error('Contact.field', 'There is an error fool!', array('wrap' => 'span'));
		$this->assertTags($result, array('span' => array('class' => 'error-message'), 'There is an error fool!', '/span'));

		$result = $this->Form->error('Contact.field', "<strong>Badness!</strong>", array('wrap' => false));
		$this->assertEquals('&lt;strong&gt;Badness!&lt;/strong&gt;', $result);

		$result = $this->Form->error('Contact.field', "<strong>Badness!</strong>", array('wrap' => false, 'escape' => true));
		$this->assertEquals('&lt;strong&gt;Badness!&lt;/strong&gt;', $result);

		$result = $this->Form->error('Contact.field', "<strong>Badness!</strong>", array('wrap' => false, 'escape' => false));
		$this->assertEquals('<strong>Badness!</strong>', $result);

		$Contact->validationErrors['field'] = array("email");
		$result = $this->Form->error('Contact.field', array('attributes' => array('class' => 'field-error'), 'email' => 'No good!'));
		$expected = array(
			'div' => array('class' => 'field-error'),
			'No good!',
			'/div'
		);
		$this->assertTags($result, $expected);

		$Contact->validationErrors['field'] = array('notEmpty', 'email', 'Something else');
		$result = $this->Form->error('Contact.field', array(
			'notEmpty' => 'Cannot be empty',
			'email' => 'No good!'
		));
		$expected = array(
			'div' => array('class' => 'error-message'),
				'ul' => array(),
					'<li', 'Cannot be empty', '/li',
					'<li', 'No good!', '/li',
					'<li', 'Something else', '/li',
				'/ul',
			'/div'
		);
		$this->assertTags($result, $expected);

		/** Testing error messages list options **/
		$Contact->validationErrors['field'] = array('notEmpty', 'email');

		$result = $this->Form->error('Contact.field', null, array('listOptions' => 'ol'));
		$expected = array(
			'div' => array('class' => 'error-message'),
				'ol' => array(),
					'<li', 'notEmpty', '/li',
					'<li', 'email', '/li',
				'/ol',
			'/div'
		);
		$this->assertTags($result, $expected);

		$result = $this->Form->error('Contact.field', null, array('listOptions' => array('tag' => 'ol')));
		$expected = array(
			'div' => array('class' => 'error-message'),
				'ol' => array(),
					'<li', 'notEmpty', '/li',
					'<li', 'email', '/li',
				'/ol',
			'/div'
		);
		$this->assertTags($result, $expected);

		$result = $this->Form->error('Contact.field', null, array(
			'listOptions' => array(
				'class' => 'ul-class',
				'itemOptions' => array(
					'class' => 'li-class'
				)
			)
		));
		$expected = array(
			'div' => array('class' => 'error-message'),
				'ul' => array('class' => 'ul-class'),
					array('li' => array('class' => 'li-class')), 'notEmpty', '/li',
					array('li' => array('class' => 'li-class')), 'email', '/li',
				'/ul',
			'/div'
		);
		$this->assertTags($result, $expected);
	}

/**
 * test error options when using form->input();
 *
 * @return void
 */
	public function testInputErrorEscape() {
		$this->Form->create('ValidateProfile');
		$ValidateProfile = ClassRegistry::getObject('ValidateProfile');
		$ValidateProfile->validationErrors['city'] = array('required<br>');
		$result = $this->Form->input('city',array('error' => array('attributes' => array('escape' => true))));
		$this->assertRegExp('/required&lt;br&gt;/', $result);

		$result = $this->Form->input('city',array('error' => array('attributes' => array('escape' => false))));
		$this->assertRegExp('/required<br>/', $result);
	}

/**
 * testPassword method
 *
 * Test password element generation
 *
 * @return void
 */
	public function testPassword() {
		$Contact = ClassRegistry::getObject('Contact');
		$result = $this->Form->password('Contact.field');
		$this->assertTags($result, array('input' => array('type' => 'password', 'name' => 'data[Contact][field]', 'id' => 'ContactField')));

		$Contact->validationErrors['passwd'] = 1;
		$this->Form->request->data['Contact']['passwd'] = 'test';
		$result = $this->Form->password('Contact.passwd', array('id' => 'theID'));
		$this->assertTags($result, array('input' => array('type' => 'password', 'name' => 'data[Contact][passwd]', 'value' => 'test', 'id' => 'theID', 'class' => 'form-error')));
	}

/**
 * testRadio method
 *
 * Test radio element set generation
 *
 * @return void
 */
	public function testRadio() {
		$result = $this->Form->radio('Model.field', array('option A'));
		$expected = array(
			'input' => array('type' => 'hidden', 'name' => 'data[Model][field]', 'value' => '', 'id' => 'ModelField_'),
			array('input' => array('type' => 'radio', 'name' => 'data[Model][field]', 'value' => '0', 'id' => 'ModelField0')),
			'label' => array('for' => 'ModelField0'),
			'option A',
			'/label'
		);
		$this->assertTags($result, $expected);

		$result = $this->Form->radio('Model.field', array('1/2' => 'half'));
		$expected = array(
			'input' => array('type' => 'hidden', 'name' => 'data[Model][field]', 'value' => '', 'id' => 'ModelField_'),
			array('input' => array('type' => 'radio', 'name' => 'data[Model][field]', 'value' => '1/2', 'id' => 'ModelField12')),
			'label' => array('for' => 'ModelField12'),
			'half',
			'/label'
		);
		$this->assertTags($result, $expected);

		$result = $this->Form->radio('Model.field', array('option A', 'option B'));
		$expected = array(
			'fieldset' => array(),
			'legend' => array(),
			'Field',
			'/legend',
			'input' => array('type' => 'hidden', 'name' => 'data[Model][field]', 'value' => '', 'id' => 'ModelField_'),
			array('input' => array('type' => 'radio', 'name' => 'data[Model][field]', 'value' => '0', 'id' => 'ModelField0')),
			array('label' => array('for' => 'ModelField0')),
			'option A',
			'/label',
			array('input' => array('type' => 'radio', 'name' => 'data[Model][field]', 'value' => '1', 'id' => 'ModelField1')),
			array('label' => array('for' => 'ModelField1')),
			'option B',
			'/label',
			'/fieldset'
		);
		$this->assertTags($result, $expected);

		$result = $this->Form->radio('Model.field', array('option A', 'option B'), array('separator' => '<br/>'));
		$expected = array(
			'fieldset' => array(),
			'legend' => array(),
			'Field',
			'/legend',
			'input' => array('type' => 'hidden', 'name' => 'data[Model][field]', 'value' => '', 'id' => 'ModelField_'),
			array('input' => array('type' => 'radio', 'name' => 'data[Model][field]', 'value' => '0', 'id' => 'ModelField0')),
			array('label' => array('for' => 'ModelField0')),
			'option A',
			'/label',
			'br' => array(),
			array('input' => array('type' => 'radio', 'name' => 'data[Model][field]', 'value' => '1', 'id' => 'ModelField1')),
			array('label' => array('for' => 'ModelField1')),
			'option B',
			'/label',
			'/fieldset'
		);
		$this->assertTags($result, $expected);

		$result = $this->Form->radio('Model.field', array('1' => 'Yes', '0' => 'No'), array('value' => '1'));
		$expected = array(
			'fieldset' => array(),
			'legend' => array(),
			'Field',
			'/legend',
			array('input' => array('type' => 'radio', 'name' => 'data[Model][field]', 'value' => '1', 'id' => 'ModelField1', 'checked' => 'checked')),
			array('label' => array('for' => 'ModelField1')),
			'Yes',
			'/label',
			array('input' => array('type' => 'radio', 'name' => 'data[Model][field]', 'value' => '0', 'id' => 'ModelField0')),
			array('label' => array('for' => 'ModelField0')),
			'No',
			'/label',
			'/fieldset'
		);
		$this->assertTags($result, $expected);

		$result = $this->Form->radio('Model.field', array('1' => 'Yes', '0' => 'No'), array('value' => '0'));
		$expected = array(
			'fieldset' => array(),
			'legend' => array(),
			'Field',
			'/legend',
			array('input' => array('type' => 'radio', 'name' => 'data[Model][field]', 'value' => '1', 'id' => 'ModelField1')),
			array('label' => array('for' => 'ModelField1')),
			'Yes',
			'/label',
			array('input' => array('type' => 'radio', 'name' => 'data[Model][field]', 'value' => '0', 'id' => 'ModelField0', 'checked' => 'checked')),
			array('label' => array('for' => 'ModelField0')),
			'No',
			'/label',
			'/fieldset'
		);
		$this->assertTags($result, $expected);

		$result = $this->Form->radio('Model.field', array('1' => 'Yes', '0' => 'No'), array('value' => null));
		$expected = array(
			'fieldset' => array(),
			'legend' => array(),
			'Field',
			'/legend',
			'input' => array('type' => 'hidden', 'name' => 'data[Model][field]', 'value' => '', 'id' => 'ModelField_'),
			array('input' => array('type' => 'radio', 'name' => 'data[Model][field]', 'value' => '1', 'id' => 'ModelField1')),
			array('label' => array('for' => 'ModelField1')),
			'Yes',
			'/label',
			array('input' => array('type' => 'radio', 'name' => 'data[Model][field]', 'value' => '0', 'id' => 'ModelField0')),
			array('label' => array('for' => 'ModelField0')),
			'No',
			'/label',
			'/fieldset'
		);
		$this->assertTags($result, $expected);

		$result = $this->Form->radio('Model.field', array('1' => 'Yes', '0' => 'No'));
		$expected = array(
			'fieldset' => array(),
			'legend' => array(),
			'Field',
			'/legend',
			'input' => array('type' => 'hidden', 'name' => 'data[Model][field]', 'value' => '', 'id' => 'ModelField_'),
			array('input' => array('type' => 'radio', 'name' => 'data[Model][field]', 'value' => '1', 'id' => 'ModelField1')),
			array('label' => array('for' => 'ModelField1')),
			'Yes',
			'/label',
			array('input' => array('type' => 'radio', 'name' => 'data[Model][field]', 'value' => '0', 'id' => 'ModelField0')),
			array('label' => array('for' => 'ModelField0')),
			'No',
			'/label',
			'/fieldset'
		);
		$this->assertTags($result, $expected);

		$result = $this->Form->input('Newsletter.subscribe', array('legend' => 'Legend title', 'type' => 'radio', 'options' => array('0' => 'Unsubscribe', '1' => 'Subscribe')));
		$expected = array(
			'div' => array('class' => 'input radio'),
			'fieldset' => array(),
			'legend' => array(),
			'Legend title',
			'/legend',
			'input' => array('type' => 'hidden', 'name' => 'data[Newsletter][subscribe]', 'value' => '', 'id' => 'NewsletterSubscribe_'),
			array('input' => array('type' => 'radio', 'name' => 'data[Newsletter][subscribe]', 'value' => '0', 'id' => 'NewsletterSubscribe0')),
			array('label' => array('for' => 'NewsletterSubscribe0')),
			'Unsubscribe',
			'/label',
			array('input' => array('type' => 'radio', 'name' => 'data[Newsletter][subscribe]', 'value' => '1', 'id' => 'NewsletterSubscribe1')),
			array('label' => array('for' => 'NewsletterSubscribe1')),
			'Subscribe',
			'/label',
			'/fieldset',
			'/div'
		);
		$this->assertTags($result, $expected);

		$result = $this->Form->input('Newsletter.subscribe', array('legend' => false, 'type' => 'radio', 'options' => array('0' => 'Unsubscribe', '1' => 'Subscribe')));
		$expected = array(
			'div' => array('class' => 'input radio'),
			'input' => array('type' => 'hidden', 'name' => 'data[Newsletter][subscribe]', 'value' => '', 'id' => 'NewsletterSubscribe_'),
			array('input' => array('type' => 'radio', 'name' => 'data[Newsletter][subscribe]', 'value' => '0', 'id' => 'NewsletterSubscribe0')),
			array('label' => array('for' => 'NewsletterSubscribe0')),
			'Unsubscribe',
			'/label',
			array('input' => array('type' => 'radio', 'name' => 'data[Newsletter][subscribe]', 'value' => '1', 'id' => 'NewsletterSubscribe1')),
			array('label' => array('for' => 'NewsletterSubscribe1')),
			'Subscribe',
			'/label',
			'/div'
		);
		$this->assertTags($result, $expected);

		$result = $this->Form->input('Newsletter.subscribe', array('legend' => 'Legend title', 'label' => false, 'type' => 'radio', 'options' => array('0' => 'Unsubscribe', '1' => 'Subscribe')));
		$expected = array(
			'div' => array('class' => 'input radio'),
			'fieldset' => array(),
			'legend' => array(),
			'Legend title',
			'/legend',
			'input' => array('type' => 'hidden', 'name' => 'data[Newsletter][subscribe]', 'value' => '', 'id' => 'NewsletterSubscribe_'),
			array('input' => array('type' => 'radio', 'name' => 'data[Newsletter][subscribe]', 'value' => '0', 'id' => 'NewsletterSubscribe0')),
			'Unsubscribe',
			array('input' => array('type' => 'radio', 'name' => 'data[Newsletter][subscribe]', 'value' => '1', 'id' => 'NewsletterSubscribe1')),
			'Subscribe',
			'/fieldset',
			'/div'
		);
		$this->assertTags($result, $expected);

		$result = $this->Form->input('Newsletter.subscribe', array('legend' => false, 'label' => false, 'type' => 'radio', 'options' => array('0' => 'Unsubscribe', '1' => 'Subscribe')));
		$expected = array(
			'div' => array('class' => 'input radio'),
			'input' => array('type' => 'hidden', 'name' => 'data[Newsletter][subscribe]', 'value' => '', 'id' => 'NewsletterSubscribe_'),
			array('input' => array('type' => 'radio', 'name' => 'data[Newsletter][subscribe]', 'value' => '0', 'id' => 'NewsletterSubscribe0')),
			'Unsubscribe',
			array('input' => array('type' => 'radio', 'name' => 'data[Newsletter][subscribe]', 'value' => '1', 'id' => 'NewsletterSubscribe1')),
			'Subscribe',
			'/div'
		);
		$this->assertTags($result, $expected);

		$result = $this->Form->input('Newsletter.subscribe', array('legend' => false, 'label' => false, 'type' => 'radio', 'value' => '1', 'options' => array('0' => 'Unsubscribe', '1' => 'Subscribe')));
		$expected = array(
			'div' => array('class' => 'input radio'),
			array('input' => array('type' => 'radio', 'name' => 'data[Newsletter][subscribe]', 'value' => '0', 'id' => 'NewsletterSubscribe0')),
			'Unsubscribe',
			array('input' => array('type' => 'radio', 'name' => 'data[Newsletter][subscribe]', 'value' => '1', 'id' => 'NewsletterSubscribe1', 'checked' => 'checked')),
			'Subscribe',
			'/div'
		);
		$this->assertTags($result, $expected);

		$result = $this->Form->radio('Employee.gender', array('male' => 'Male', 'female' => 'Female'));
		$expected = array(
			'fieldset' => array(),
			'legend' => array(),
			'Gender',
			'/legend',
			'input' => array('type' => 'hidden', 'name' => 'data[Employee][gender]', 'value' => '', 'id' => 'EmployeeGender_'),
			array('input' => array('type' => 'radio', 'name' => 'data[Employee][gender]', 'value' => 'male', 'id' => 'EmployeeGenderMale')),
			array('label' => array('for' => 'EmployeeGenderMale')),
			'Male',
			'/label',
			array('input' => array('type' => 'radio', 'name' => 'data[Employee][gender]', 'value' => 'female', 'id' => 'EmployeeGenderFemale')),
			array('label' => array('for' => 'EmployeeGenderFemale')),
			'Female',
			'/label',
			'/fieldset',
		);
		$this->assertTags($result, $expected);

		$result = $this->Form->radio('Officer.gender', array('male' => 'Male', 'female' => 'Female'));
		$expected = array(
			'fieldset' => array(),
			'legend' => array(),
			'Gender',
			'/legend',
			'input' => array('type' => 'hidden', 'name' => 'data[Officer][gender]', 'value' => '', 'id' => 'OfficerGender_'),
			array('input' => array('type' => 'radio', 'name' => 'data[Officer][gender]', 'value' => 'male', 'id' => 'OfficerGenderMale')),
			array('label' => array('for' => 'OfficerGenderMale')),
			'Male',
			'/label',
			array('input' => array('type' => 'radio', 'name' => 'data[Officer][gender]', 'value' => 'female', 'id' => 'OfficerGenderFemale')),
			array('label' => array('for' => 'OfficerGenderFemale')),
			'Female',
			'/label',
			'/fieldset',
		);
		$this->assertTags($result, $expected);

		$result = $this->Form->radio('Contact.1.imrequired', array('option A'));
		$expected = array(
			'input' => array('type' => 'hidden', 'name' => 'data[Contact][1][imrequired]', 'value' => '', 'id' => 'Contact1Imrequired_'),
			array('input' => array('type' => 'radio', 'name' => 'data[Contact][1][imrequired]', 'value' => '0', 'id' => 'Contact1Imrequired0')),
			'label' => array('for' => 'Contact1Imrequired0'),
			'option A',
			'/label'
		);
		$this->assertTags($result, $expected);

		$result = $this->Form->radio('Model.1.field', array('option A'));
		$expected = array(
			'input' => array('type' => 'hidden', 'name' => 'data[Model][1][field]', 'value' => '', 'id' => 'Model1Field_'),
			array('input' => array('type' => 'radio', 'name' => 'data[Model][1][field]', 'value' => '0', 'id' => 'Model1Field0')),
			'label' => array('for' => 'Model1Field0'),
			'option A',
			'/label'
		);
		$this->assertTags($result, $expected);

		$result = $this->Form->radio('Model.field', array('option A', 'option B'), array('name' => 'data[Model][custom]'));
		$expected = array(
			'fieldset' => array(),
			'legend' => array(),
			'Field',
			'/legend',
			'input' => array('type' => 'hidden', 'name' => 'data[Model][custom]', 'value' => '', 'id' => 'ModelField_'),
			array('input' => array('type' => 'radio', 'name' => 'data[Model][custom]', 'value' => '0', 'id' => 'ModelField0')),
			array('label' => array('for' => 'ModelField0')),
			'option A',
			'/label',
			array('input' => array('type' => 'radio', 'name' => 'data[Model][custom]', 'value' => '1', 'id' => 'ModelField1')),
			array('label' => array('for' => 'ModelField1')),
			'option B',
			'/label',
			'/fieldset'
		);
		$this->assertTags($result, $expected);

		$result = $this->Form->radio(
			'Model.field',
			array('option A', 'option B'),
			array('between' => 'I am between')
		);
		$expected = array(
			'fieldset' => array(),
			'legend' => array(),
			'Field',
			'/legend',
			'I am between',
			'input' => array(
				'type' => 'hidden', 'name' => 'data[Model][field]',
				'value' => '', 'id' => 'ModelField_'
			),
			array('input' => array(
				'type' => 'radio', 'name' => 'data[Model][field]',
				'value' => '0', 'id' => 'ModelField0'
			)),
			array('label' => array('for' => 'ModelField0')),
			'option A',
			'/label',
			array('input' => array(
				'type' => 'radio', 'name' => 'data[Model][field]',
				'value' => '1', 'id' => 'ModelField1'
			)),
			array('label' => array('for' => 'ModelField1')),
			'option B',
			'/label',
			'/fieldset'
		);
		$this->assertTags($result, $expected);
	}

/**
 * test disabled radio options
 *
 * @return void
 */
	public function testRadioDisabled() {
		$result = $this->Form->radio(
			'Model.field',
			array('option A', 'option B'),
			array('disabled' => array('option A'), 'value' => 'option A')
		);
		$expected = array(
			'fieldset' => array(),
			'legend' => array(),
			'Field',
			'/legend',
			array('input' => array('type' => 'radio', 'name' => 'data[Model][field]', 'value' => '0', 'id' => 'ModelField0', 'disabled' => 'disabled', 'checked' => 'checked')),
			array('label' => array('for' => 'ModelField0')),
			'option A',
			'/label',
			array('input' => array('type' => 'radio', 'name' => 'data[Model][field]', 'value' => '1', 'id' => 'ModelField1')),
			array('label' => array('for' => 'ModelField1')),
			'option B',
			'/label',
			'/fieldset'
		);
		$this->assertTags($result, $expected);

		$result = $this->Form->radio(
			'Model.field',
			array('option A', 'option B'),
			array('disabled' => true, 'value' => 'option A')
		);
		$expected = array(
			'fieldset' => array(),
			'legend' => array(),
			'Field',
			'/legend',
			array('input' => array('type' => 'radio', 'name' => 'data[Model][field]', 'value' => '0', 'id' => 'ModelField0', 'disabled' => 'disabled', 'checked' => 'checked')),
			array('label' => array('for' => 'ModelField0')),
			'option A',
			'/label',
			array('input' => array('type' => 'radio', 'name' => 'data[Model][field]', 'value' => '1', 'id' => 'ModelField1', 'disabled' => 'disabled')),
			array('label' => array('for' => 'ModelField1')),
			'option B',
			'/label',
			'/fieldset'
		);
		$this->assertTags($result, $expected);

		$result = $this->Form->radio(
			'Model.field',
			array('option A', 'option B'),
			array('disabled' => 'disabled', 'value' => 'option A')
		);
		$expected = array(
			'fieldset' => array(),
			'legend' => array(),
			'Field',
			'/legend',
			array('input' => array('type' => 'radio', 'name' => 'data[Model][field]', 'value' => '0', 'id' => 'ModelField0', 'disabled' => 'disabled', 'checked' => 'checked')),
			array('label' => array('for' => 'ModelField0')),
			'option A',
			'/label',
			array('input' => array('type' => 'radio', 'name' => 'data[Model][field]', 'value' => '1', 'id' => 'ModelField1', 'disabled' => 'disabled')),
			array('label' => array('for' => 'ModelField1')),
			'option B',
			'/label',
			'/fieldset'
		);
		$this->assertTags($result, $expected);
	}

/**
 * test disabling the hidden input for radio buttons
 *
 * @return void
 */
	public function testRadioHiddenInputDisabling() {
		$result = $this->Form->input('Model.1.field', array(
				'type' => 'radio',
				'options' => array('option A'),
				'hiddenField' => false
			)
		);
		$expected = array(
			'div' => array('class' => 'input radio'),
			'input' => array('type' => 'radio', 'name' => 'data[Model][1][field]', 'value' => '0', 'id' => 'Model1Field0'),
			'label' => array('for' => 'Model1Field0'),
			'option A',
			'/label',
			'/div'
		);
		$this->assertTags($result, $expected);

		$result = $this->Form->radio('Model.1.field', array('option A'), array('hiddenField' => false));
		$expected = array(
			'input' => array('type' => 'radio', 'name' => 'data[Model][1][field]', 'value' => '0', 'id' => 'Model1Field0'),
			'label' => array('for' => 'Model1Field0'),
			'option A',
			'/label'
		);
		$this->assertTags($result, $expected);
	}

/**
 * testSelect method
 *
 * Test select element generation.
 *
 * @return void
 */
	public function testSelect() {
		$result = $this->Form->select('Model.field', array());
		$expected = array(
			'select' => array('name' => 'data[Model][field]', 'id' => 'ModelField'),
			array('option' => array('value' => '')),
			'/option',
			'/select'
		);
		$this->assertTags($result, $expected);

		$this->Form->request->data = array('Model' => array('field' => 'value'));
		$result = $this->Form->select('Model.field', array('value' => 'good', 'other' => 'bad'));
		$expected = array(
			'select' => array('name' => 'data[Model][field]', 'id' => 'ModelField'),
			array('option' => array('value' => '')),
			'/option',
			array('option' => array('value' => 'value', 'selected' => 'selected')),
			'good',
			'/option',
			array('option' => array('value' => 'other')),
			'bad',
			'/option',
			'/select'
		);
		$this->assertTags($result, $expected);

		$this->Form->request->data = array();
		$result = $this->Form->select('Model.field', array('value' => 'good', 'other' => 'bad'));
		$expected = array(
			'select' => array('name' => 'data[Model][field]', 'id' => 'ModelField'),
			array('option' => array('value' => '')),
			'/option',
			array('option' => array('value' => 'value')),
			'good',
			'/option',
			array('option' => array('value' => 'other')),
			'bad',
			'/option',
			'/select'
		);
		$this->assertTags($result, $expected);

		$result = $this->Form->select(
			'Model.field', array('first' => 'first "html" <chars>', 'second' => 'value'),
			array('empty' => false)
		);
		$expected = array(
			'select' => array('name' => 'data[Model][field]', 'id' => 'ModelField'),
			array('option' => array('value' => 'first')),
			'first &quot;html&quot; &lt;chars&gt;',
			'/option',
			array('option' => array('value' => 'second')),
			'value',
			'/option',
			'/select'
		);
		$this->assertTags($result, $expected);

		$result = $this->Form->select(
			'Model.field',
			array('first' => 'first "html" <chars>', 'second' => 'value'),
			array('escape' => false, 'empty' => false)
		);
		$expected = array(
			'select' => array('name' => 'data[Model][field]', 'id' => 'ModelField'),
			array('option' => array('value' => 'first')),
			'first "html" <chars>',
			'/option',
			array('option' => array('value' => 'second')),
			'value',
			'/option',
			'/select'
		);
		$this->assertTags($result, $expected);

		$options = array(
			array('value' => 'first', 'name' => 'First'),
			array('value' => 'first', 'name' => 'Another First'),
		);
		$result = $this->Form->select(
			'Model.field',
			$options,
			array('escape' => false, 'empty' => false)
		);
		$expected = array(
			'select' => array('name' => 'data[Model][field]', 'id' => 'ModelField'),
			array('option' => array('value' => 'first')),
			'First',
			'/option',
			array('option' => array('value' => 'first')),
			'Another First',
			'/option',
			'/select'
		);
		$this->assertTags($result, $expected);

		$this->Form->request->data = array('Model' => array('contact_id' => 228));
		$result = $this->Form->select(
			'Model.contact_id',
			array('228' => '228 value', '228-1' => '228-1 value', '228-2' => '228-2 value'),
			array('escape' => false, 'empty' => 'pick something')
		);

		$expected = array(
			'select' => array('name' => 'data[Model][contact_id]', 'id' => 'ModelContactId'),
			array('option' => array('value' => '')), 'pick something', '/option',
			array('option' => array('value' => '228', 'selected' => 'selected')), '228 value', '/option',
			array('option' => array('value' => '228-1')), '228-1 value', '/option',
			array('option' => array('value' => '228-2')), '228-2 value', '/option',
			'/select'
		);
		$this->assertTags($result, $expected);

		$this->Form->request->data['Model']['field'] = 0;
		$result = $this->Form->select('Model.field', array('0' => 'No', '1' => 'Yes'));
		$expected = array(
			'select' => array('name' => 'data[Model][field]', 'id' => 'ModelField'),
			array('option' => array('value' => '')), '/option',
			array('option' => array('value' => '0', 'selected' => 'selected')), 'No', '/option',
			array('option' => array('value' => '1')), 'Yes', '/option',
			'/select'
		);
		$this->assertTags($result, $expected);
	}

/**
 * test that select() with optiongroups listens to the escape param.
 *
 * @return void
 */
	public function testSelectOptionGroupEscaping() {
		$options = array(
			'>< Key' => array(
				1 => 'One',
				2 => 'Two'
			)
		);
		$result = $this->Form->select('Model.field', $options, array('empty' => false));
		$expected = array(
			'select' => array('name' => 'data[Model][field]', 'id' => 'ModelField'),
			'optgroup' => array('label' => '&gt;&lt; Key'),
			array('option' => array('value' => '1')), 'One', '/option',
			array('option' => array('value' => '2')), 'Two', '/option',
			'/optgroup',
			'/select'
		);
		$this->assertTags($result, $expected);

		$options = array(
			'>< Key' => array(
				1 => 'One',
				2 => 'Two'
			)
		);
		$result = $this->Form->select('Model.field', $options, array('empty' => false, 'escape' => false));
		$expected = array(
			'select' => array('name' => 'data[Model][field]', 'id' => 'ModelField'),
			'optgroup' => array('label' => '>< Key'),
			array('option' => array('value' => '1')), 'One', '/option',
			array('option' => array('value' => '2')), 'Two', '/option',
			'/optgroup',
			'/select'
		);
		$this->assertTags($result, $expected);
	}

/**
 * Tests that FormHelper::select() allows null to be passed in the $attributes parameter
 *
 * @return void
 */
	public function testSelectWithNullAttributes() {
		$result = $this->Form->select('Model.field', array('first', 'second'), array('empty' => false));
		$expected = array(
			'select' => array('name' => 'data[Model][field]', 'id' => 'ModelField'),
			array('option' => array('value' => '0')),
			'first',
			'/option',
			array('option' => array('value' => '1')),
			'second',
			'/option',
			'/select'
		);
		$this->assertTags($result, $expected);
	}

/**
 * testNestedSelect method
 *
 * test select element generation with optgroups
 *
 * @return void
 */
	public function testNestedSelect() {
		$result = $this->Form->select(
			'Model.field',
			array(1 => 'One', 2 => 'Two', 'Three' => array(
				3 => 'Three', 4 => 'Four', 5 => 'Five'
			)), array('empty' => false)
		);
		$expected = array(
			'select' => array('name' => 'data[Model][field]',
					'id' => 'ModelField'),
					array('option' => array('value' => 1)),
					'One',
					'/option',
					array('option' => array('value' => 2)),
					'Two',
					'/option',
					array('optgroup' => array('label' => 'Three')),
						array('option' => array('value' => 4)),
						'Four',
						'/option',
						array('option' => array('value' => 5)),
						'Five',
						'/option',
					'/optgroup',
					'/select'
					);
		$this->assertTags($result, $expected);

		$result = $this->Form->select(
			'Model.field',
			array(1 => 'One', 2 => 'Two', 'Three' => array(3 => 'Three', 4 => 'Four')),
			array('showParents' => true, 'empty' => false)
		);

		$expected = array(
			'select' => array('name' => 'data[Model][field]', 'id' => 'ModelField'),
				array('option' => array('value' => 1)),
				'One',
				'/option',
				array('option' => array('value' => 2)),
				'Two',
				'/option',
				array('optgroup' => array('label' => 'Three')),
					array('option' => array('value' => 3)),
					'Three',
					'/option',
					array('option' => array('value' => 4)),
					'Four',
					'/option',
				'/optgroup',
			'/select'
		);
		$this->assertTags($result, $expected);
	}

/**
 * testSelectMultiple method
 *
 * test generation of multiple select elements
 *
 * @return void
 */
	public function testSelectMultiple() {
		$options = array('first', 'second', 'third');
		$result = $this->Form->select(
			'Model.multi_field', $options, array('multiple' => true)
		);
		$expected = array(
			'input' => array(
				'type' => 'hidden', 'name' => 'data[Model][multi_field]', 'value' => '', 'id' => 'ModelMultiField_'
			),
			'select' => array(
				'name' => 'data[Model][multi_field][]',
				'id' => 'ModelMultiField', 'multiple' => 'multiple'
			),
			array('option' => array('value' => '0')),
			'first',
			'/option',
			array('option' => array('value' => '1')),
			'second',
			'/option',
			array('option' => array('value' => '2')),
			'third',
			'/option',
			'/select'
		);
		$this->assertTags($result, $expected);

		$result = $this->Form->select(
			'Model.multi_field', $options, array('multiple' => 'multiple')
		);
		$expected = array(
			'input' => array(
				'type' => 'hidden', 'name' => 'data[Model][multi_field]', 'value' => '', 'id' => 'ModelMultiField_'
			),
			'select' => array(
				'name' => 'data[Model][multi_field][]',
				'id' => 'ModelMultiField', 'multiple' => 'multiple'
			),
			array('option' => array('value' => '0')),
			'first',
			'/option',
			array('option' => array('value' => '1')),
			'second',
			'/option',
			array('option' => array('value' => '2')),
			'third',
			'/option',
			'/select'
		);
		$this->assertTags($result, $expected);

		$result = $this->Form->select(
			'Model.multi_field', $options, array('multiple' => true, 'value' => array(0, 1))
		);
		$expected = array(
			'input' => array(
				'type' => 'hidden', 'name' => 'data[Model][multi_field]', 'value' => '', 'id' => 'ModelMultiField_'
			),
			'select' => array(
				'name' => 'data[Model][multi_field][]', 'id' => 'ModelMultiField',
				'multiple' => 'multiple'
			),
			array('option' => array('value' => '0', 'selected' => 'selected')),
			'first',
			'/option',
			array('option' => array('value' => '1', 'selected' => 'selected')),
			'second',
			'/option',
			array('option' => array('value' => '2')),
			'third',
			'/option',
			'/select'
		);
		$this->assertTags($result, $expected);

		$result = $this->Form->select(
			'Model.multi_field', $options, array('multiple' => false, 'value' => array(0, 1))
		);
		$expected = array(
			'select' => array(
				'name' => 'data[Model][multi_field]', 'id' => 'ModelMultiField'
			),
			array('option' => array('value' => '0', 'selected' => 'selected')),
			'first',
			'/option',
			array('option' => array('value' => '1', 'selected' => 'selected')),
			'second',
			'/option',
			array('option' => array('value' => '2')),
			'third',
			'/option',
			'/select'
		);
		$this->assertTags($result, $expected);
	}

/**
 * test generation of habtm select boxes.
 *
 * @return void
 */
	public function testHabtmSelectBox() {
		$this->View->viewVars['contactTags'] = array(
			1 => 'blue',
			2 => 'red',
			3 => 'green'
		);
		$this->Form->request->data = array(
			'Contact' => array(),
			'ContactTag' => array(
				array(
					'id' => 1,
					'name' => 'blue'
				),
				array(
					'id' => 3,
					'name' => 'green'
				)
			)
		);
		$this->Form->create('Contact');
		$result = $this->Form->input('ContactTag', array('div' => false, 'label' => false));
		$expected = array(
			'input' => array(
				'type' => 'hidden', 'name' => 'data[ContactTag][ContactTag]', 'value' => '', 'id' => 'ContactTagContactTag_'
			),
			'select' => array(
				'name' => 'data[ContactTag][ContactTag][]', 'id' => 'ContactTagContactTag',
				'multiple' => 'multiple'
			),
			array('option' => array('value' => '1', 'selected' => 'selected')),
			'blue',
			'/option',
			array('option' => array('value' => '2')),
			'red',
			'/option',
			array('option' => array('value' => '3', 'selected' => 'selected')),
			'green',
			'/option',
			'/select'
		);
		$this->assertTags($result, $expected);
	}

/**
 * test generation of multi select elements in checkbox format
 *
 * @return void
 */
	public function testSelectMultipleCheckboxes() {
		$result = $this->Form->select(
			'Model.multi_field',
			array('first', 'second', 'third'),
			array('multiple' => 'checkbox')
		);

		$expected = array(
			'input' => array(
				'type' => 'hidden', 'name' => 'data[Model][multi_field]', 'value' => '', 'id' => 'ModelMultiField'
			),
			array('div' => array('class' => 'checkbox')),
			array('input' => array(
				'type' => 'checkbox', 'name' => 'data[Model][multi_field][]',
				'value' => '0', 'id' => 'ModelMultiField0'
			)),
			array('label' => array('for' => 'ModelMultiField0')),
			'first',
			'/label',
			'/div',
			array('div' => array('class' => 'checkbox')),
			array('input' => array(
				'type' => 'checkbox', 'name' => 'data[Model][multi_field][]',
				'value' => '1', 'id' => 'ModelMultiField1'
			)),
			array('label' => array('for' => 'ModelMultiField1')),
			'second',
			'/label',
			'/div',
			array('div' => array('class' => 'checkbox')),
			array('input' => array(
				'type' => 'checkbox', 'name' => 'data[Model][multi_field][]',
				'value' => '2', 'id' => 'ModelMultiField2'
			)),
			array('label' => array('for' => 'ModelMultiField2')),
			'third',
			'/label',
			'/div'
		);
		$this->assertTags($result, $expected);

		$result = $this->Form->select(
			'Model.multi_field',
			array('a' => 'first', 'b' => 'second', 'c' => 'third'),
			array('multiple' => 'checkbox')
		);
		$expected = array(
			'input' => array(
				'type' => 'hidden', 'name' => 'data[Model][multi_field]', 'value' => '', 'id' => 'ModelMultiField'
			),
			array('div' => array('class' => 'checkbox')),
			array('input' => array(
				'type' => 'checkbox', 'name' => 'data[Model][multi_field][]',
				'value' => 'a', 'id' => 'ModelMultiFieldA'
			)),
			array('label' => array('for' => 'ModelMultiFieldA')),
			'first',
			'/label',
			'/div',
			array('div' => array('class' => 'checkbox')),
			array('input' => array(
				'type' => 'checkbox', 'name' => 'data[Model][multi_field][]',
				'value' => 'b', 'id' => 'ModelMultiFieldB'
			)),
			array('label' => array('for' => 'ModelMultiFieldB')),
			'second',
			'/label',
			'/div',
			array('div' => array('class' => 'checkbox')),
			array('input' => array(
				'type' => 'checkbox', 'name' => 'data[Model][multi_field][]',
				'value' => 'c', 'id' => 'ModelMultiFieldC'
			)),
			array('label' => array('for' => 'ModelMultiFieldC')),
			'third',
			'/label',
			'/div'
		);
		$this->assertTags($result, $expected);

		$result = $this->Form->select(
			'Model.multi_field', array('1' => 'first'), array('multiple' => 'checkbox')
		);
		$expected = array(
			'input' => array(
				'type' => 'hidden', 'name' => 'data[Model][multi_field]', 'value' => '', 'id' => 'ModelMultiField'
			),
			array('div' => array('class' => 'checkbox')),
			array('input' => array(
				'type' => 'checkbox', 'name' => 'data[Model][multi_field][]',
				'value' => '1', 'id' => 'ModelMultiField1'
			)),
			array('label' => array('for' => 'ModelMultiField1')),
			'first',
			'/label',
			'/div'
		);
		$this->assertTags($result, $expected);

		$this->Form->request->data = array('Model' => array('tags' => array(1)));
		$result = $this->Form->select(
			'Model.tags', array('1' => 'first', 'Array' => 'Array'), array('multiple' => 'checkbox')
		);
		$expected = array(
			'input' => array(
				'type' => 'hidden', 'name' => 'data[Model][tags]', 'value' => '', 'id' => 'ModelTags'
			),
			array('div' => array('class' => 'checkbox')),
			array('input' => array(
				'type' => 'checkbox', 'name' => 'data[Model][tags][]',
				'value' => '1', 'id' => 'ModelTags1', 'checked' => 'checked'
			)),
			array('label' => array('for' => 'ModelTags1', 'class' => 'selected')),
			'first',
			'/label',
			'/div',

			array('div' => array('class' => 'checkbox')),
			array('input' => array(
				'type' => 'checkbox', 'name' => 'data[Model][tags][]',
				'value' => 'Array', 'id' => 'ModelTagsArray'
			)),
			array('label' => array('for' => 'ModelTagsArray')),
			'Array',
			'/label',
			'/div'
		);
		$this->assertTags($result, $expected);
	}

/**
 * test multiple checkboxes with div styles.
 *
 * @return void
 */
	public function testSelectMultipleCheckboxDiv() {
		$result = $this->Form->select(
			'Model.tags',
			array('first', 'second'),
			array('multiple' => 'checkbox', 'class' => 'my-class')
		);
		$expected = array(
			'input' => array(
				'type' => 'hidden', 'name' => 'data[Model][tags]', 'value' => '', 'id' => 'ModelTags'
			),
			array('div' => array('class' => 'my-class')),
			array('input' => array(
				'type' => 'checkbox', 'name' => 'data[Model][tags][]',
				'value' => '0', 'id' => 'ModelTags0'
			)),
			array('label' => array('for' => 'ModelTags0')), 'first', '/label',
			'/div',

			array('div' => array('class' => 'my-class')),
			array('input' => array(
				'type' => 'checkbox', 'name' => 'data[Model][tags][]',
				'value' => '1', 'id' => 'ModelTags1'
			)),
			array('label' => array('for' => 'ModelTags1')), 'second', '/label',
			'/div'
		);
		$this->assertTags($result, $expected);

		$result = $this->Form->input('Model.tags', array(
			'options' => array('first', 'second'),
			'multiple' => 'checkbox',
			'class' => 'my-class',
			'div' => false,
			'label' => false
		));
		$this->assertTags($result, $expected);

		$Contact = ClassRegistry::getObject('Contact');
		$Contact->validationErrors['tags'] = 'Select atleast one option';
		$result = $this->Form->input('Contact.tags', array(
			'options' => array('one'),
			'multiple' => 'checkbox',
			'label' => false,
			'div' => false
		));
		$expected = array(
			'input' => array('type' => 'hidden', 'class' => 'form-error', 'name' => 'data[Contact][tags]', 'value' => '', 'id' => 'ContactTags'),
			array('div' => array('class' => 'checkbox form-error')),
			array('input' => array('type' => 'checkbox', 'name' => 'data[Contact][tags][]', 'value' => '0', 'id' => 'ContactTags0')),
			array('label' => array('for' => 'ContactTags0')),
			'one',
			'/label',
			'/div'
		);
		$this->assertTags($result, $expected);

		$result = $this->Form->input('Contact.tags', array(
			'options' => array('one'),
			'multiple' => 'checkbox',
			'class' => 'mycheckbox',
			'label' => false,
			'div' => false
		));
		$expected = array(
			'input' => array('type' => 'hidden', 'class' => 'form-error', 'name' => 'data[Contact][tags]', 'value' => '', 'id' => 'ContactTags'),
			array('div' => array('class' => 'mycheckbox form-error')),
			array('input' => array('type' => 'checkbox', 'name' => 'data[Contact][tags][]', 'value' => '0', 'id' => 'ContactTags0')),
			array('label' => array('for' => 'ContactTags0')),
			'one',
			'/label',
			'/div'
		);
		$this->assertTags($result, $expected);
	}

/**
 * Checks the security hash array generated for multiple-input checkbox elements
 *
 * @return void
 */
	public function testSelectMultipleCheckboxSecurity() {
		$this->Form->request['_Token'] = array('key' => 'testKey');
		$this->assertEquals(array(), $this->Form->fields);

		$result = $this->Form->select(
			'Model.multi_field', array('1' => 'first', '2' => 'second', '3' => 'third'),
			array('multiple' => 'checkbox')
		);
		$this->assertEquals(array('Model.multi_field'), $this->Form->fields);

		$result = $this->Form->secure($this->Form->fields);
		$key = 'f7d573650a295b94e0938d32b323fde775e5f32b%3A';
		$this->assertRegExp('/"' . $key . '"/', $result);
	}

/**
 * testInputMultipleCheckboxes method
 *
 * test input() resulting in multi select elements being generated.
 *
 * @return void
 */
	public function testInputMultipleCheckboxes() {
		$result = $this->Form->input('Model.multi_field', array(
			'options' => array('first', 'second', 'third'),
			'multiple' => 'checkbox'
		));
		$expected = array(
			array('div' => array('class' => 'input select')),
			array('label' => array('for' => 'ModelMultiField')),
			'Multi Field',
			'/label',
			'input' => array('type' => 'hidden', 'name' => 'data[Model][multi_field]', 'value' => '', 'id' => 'ModelMultiField'),
			array('div' => array('class' => 'checkbox')),
			array('input' => array('type' => 'checkbox', 'name' => 'data[Model][multi_field][]', 'value' => '0', 'id' => 'ModelMultiField0')),
			array('label' => array('for' => 'ModelMultiField0')),
			'first',
			'/label',
			'/div',
			array('div' => array('class' => 'checkbox')),
			array('input' => array('type' => 'checkbox', 'name' => 'data[Model][multi_field][]', 'value' => '1', 'id' => 'ModelMultiField1')),
			array('label' => array('for' => 'ModelMultiField1')),
			'second',
			'/label',
			'/div',
			array('div' => array('class' => 'checkbox')),
			array('input' => array('type' => 'checkbox', 'name' => 'data[Model][multi_field][]', 'value' => '2', 'id' => 'ModelMultiField2')),
			array('label' => array('for' => 'ModelMultiField2')),
			'third',
			'/label',
			'/div',
			'/div'
		);
		$this->assertTags($result, $expected);

		$result = $this->Form->input('Model.multi_field', array(
			'options' => array('a' => 'first', 'b' => 'second', 'c' => 'third'),
			'multiple' => 'checkbox'
		));
		$expected = array(
			array('div' => array('class' => 'input select')),
			array('label' => array('for' => 'ModelMultiField')),
			'Multi Field',
			'/label',
			'input' => array('type' => 'hidden', 'name' => 'data[Model][multi_field]', 'value' => '', 'id' => 'ModelMultiField'),
			array('div' => array('class' => 'checkbox')),
			array('input' => array('type' => 'checkbox', 'name' => 'data[Model][multi_field][]', 'value' => 'a', 'id' => 'ModelMultiFieldA')),
			array('label' => array('for' => 'ModelMultiFieldA')),
			'first',
			'/label',
			'/div',
			array('div' => array('class' => 'checkbox')),
			array('input' => array('type' => 'checkbox', 'name' => 'data[Model][multi_field][]', 'value' => 'b', 'id' => 'ModelMultiFieldB')),
			array('label' => array('for' => 'ModelMultiFieldB')),
			'second',
			'/label',
			'/div',
			array('div' => array('class' => 'checkbox')),
			array('input' => array('type' => 'checkbox', 'name' => 'data[Model][multi_field][]', 'value' => 'c', 'id' => 'ModelMultiFieldC')),
			array('label' => array('for' => 'ModelMultiFieldC')),
			'third',
			'/label',
			'/div',
			'/div'
		);
		$this->assertTags($result, $expected);

		$result = $this->Form->input('Model.multi_field', array(
			'options' => array('1' => 'first'),
			'multiple' => 'checkbox',
			'label' => false,
			'div' => false
		));
		$expected = array(
			'input' => array('type' => 'hidden', 'name' => 'data[Model][multi_field]', 'value' => '', 'id' => 'ModelMultiField'),
			array('div' => array('class' => 'checkbox')),
			array('input' => array('type' => 'checkbox', 'name' => 'data[Model][multi_field][]', 'value' => '1', 'id' => 'ModelMultiField1')),
			array('label' => array('for' => 'ModelMultiField1')),
			'first',
			'/label',
			'/div'
		);
		$this->assertTags($result, $expected);

		$result = $this->Form->input('Model.multi_field', array(
			'options' => array('2' => 'second'),
			'multiple' => 'checkbox',
			'label' => false,
			'div' => false
		));
		$expected = array(
			'input' => array('type' => 'hidden', 'name' => 'data[Model][multi_field]', 'value' => '', 'id' => 'ModelMultiField'),
			array('div' => array('class' => 'checkbox')),
			array('input' => array('type' => 'checkbox', 'name' => 'data[Model][multi_field][]', 'value' => '2', 'id' => 'ModelMultiField2')),
			array('label' => array('for' => 'ModelMultiField2')),
			'second',
			'/label',
			'/div'
		);
		$this->assertTags($result, $expected);
	}

/**
 * testSelectHiddenFieldOmission method
 *
 * test that select() with 'hiddenField' => false omits the hidden field
 *
 * @return void
 */
	public function testSelectHiddenFieldOmission() {
		$result = $this->Form->select('Model.multi_field',
			array('first', 'second'),
			array('multiple' => 'checkbox', 'hiddenField' => false, 'value' => null)
		);
		$expected = array(
			array('div' => array('class' => 'checkbox')),
			array('input' => array('type' => 'checkbox', 'name' => 'data[Model][multi_field][]', 'value' => '0', 'id' => 'ModelMultiField0')),
			array('label' => array('for' => 'ModelMultiField0')),
			'first',
			'/label',
			'/div',
			array('div' => array('class' => 'checkbox')),
			array('input' => array('type' => 'checkbox', 'name' => 'data[Model][multi_field][]', 'value' => '1', 'id' => 'ModelMultiField1')),
			array('label' => array('for' => 'ModelMultiField1')),
			'second',
			'/label',
			'/div'
		);
		$this->assertTags($result, $expected);

		$result = $this->Form->input('Model.multi_field', array(
			'options' => array('first', 'second'),
			'multiple' => 'checkbox',
			'hiddenField' => false
		));
		$expected = array(
			array('div' => array('class' => 'input select')),
			array('label' => array('for' => 'ModelMultiField')),
			'Multi Field',
			'/label',
			array('div' => array('class' => 'checkbox')),
			array('input' => array('type' => 'checkbox', 'name' => 'data[Model][multi_field][]', 'value' => '0', 'id' => 'ModelMultiField0')),
			array('label' => array('for' => 'ModelMultiField0')),
			'first',
			'/label',
			'/div',
			array('div' => array('class' => 'checkbox')),
			array('input' => array('type' => 'checkbox', 'name' => 'data[Model][multi_field][]', 'value' => '1', 'id' => 'ModelMultiField1')),
			array('label' => array('for' => 'ModelMultiField1')),
			'second',
			'/label',
			'/div',
			'/div'
		);
		$this->assertTags($result, $expected);
	}

/**
 * test that select() with multiple = checkbox works with overriding name attribute.
 *
 * @return void
 */
	public function testSelectCheckboxMultipleOverrideName() {
		$result = $this->Form->input('category', array(
			'type' => 'select',
			'multiple' => 'checkbox',
			'name' => 'data[fish]',
			'options' => array('1', '2'),
			'div' => false,
			'label' => false,
		));
		$expected = array(
			'input' => array('type' => 'hidden', 'name' => 'data[fish]', 'value' => '', 'id' => 'category'),
			array('div' => array('class' => 'checkbox')),
				array('input' => array('type' => 'checkbox', 'name' => 'data[fish][]', 'value' => '0', 'id' => 'Category0')),
				array('label' => array('for' => 'Category0')), '1', '/label',
			'/div',
			array('div' => array('class' => 'checkbox')),
				array('input' => array('type' => 'checkbox', 'name' => 'data[fish][]', 'value' => '1', 'id' => 'Category1')),
				array('label' => array('for' => 'Category1')), '2', '/label',
			'/div'
		);
		$this->assertTags($result, $expected);
	}

/**
 * Test that 'id' overrides all the checkbox id's as well.
 *
 * @return void
 */
	public function testSelectCheckboxMultipleId() {
		$result = $this->Form->select(
			'Model.multi_field',
			array('first', 'second', 'third'),
			array('multiple' => 'checkbox', 'id' => 'CustomId')
		);

		$expected = array(
			'input' => array(
				'type' => 'hidden', 'name' => 'data[Model][multi_field]', 'value' => '', 'id' => 'CustomId'
			),
			array('div' => array('class' => 'checkbox')),
			array('input' => array(
				'type' => 'checkbox', 'name' => 'data[Model][multi_field][]',
				'value' => '0', 'id' => 'CustomId0'
			)),
			array('label' => array('for' => 'CustomId0')),
			'first',
			'/label',
			'/div',
			array('div' => array('class' => 'checkbox')),
			array('input' => array(
				'type' => 'checkbox', 'name' => 'data[Model][multi_field][]',
				'value' => '1', 'id' => 'CustomId1'
			)),
			array('label' => array('for' => 'CustomId1')),
			'second',
			'/label',
			'/div',
			array('div' => array('class' => 'checkbox')),
			array('input' => array(
				'type' => 'checkbox', 'name' => 'data[Model][multi_field][]',
				'value' => '2', 'id' => 'CustomId2'
			)),
			array('label' => array('for' => 'CustomId2')),
			'third',
			'/label',
			'/div'
		);
		$this->assertTags($result, $expected);
	}

/**
 * testCheckbox method
 *
 * Test generation of checkboxes
 *
 * @return void
 */
	public function testCheckbox() {
		$result = $this->Form->checkbox('Model.field');
		$expected = array(
			'input' => array('type' => 'hidden', 'name' => 'data[Model][field]', 'value' => '0', 'id' => 'ModelField_'),
			array('input' => array('type' => 'checkbox', 'name' => 'data[Model][field]', 'value' => '1', 'id' => 'ModelField'))
		);
		$this->assertTags($result, $expected);

		$result = $this->Form->checkbox('Model.field', array('id' => 'theID', 'value' => 'myvalue'));
		$expected = array(
			'input' => array('type' => 'hidden', 'name' => 'data[Model][field]', 'value' => '0', 'id' => 'theID_'),
			array('input' => array('type' => 'checkbox', 'name' => 'data[Model][field]', 'value' => 'myvalue', 'id' => 'theID'))
		);
		$this->assertTags($result, $expected);

		$Contact = ClassRegistry::getObject('Contact');
		$Contact->validationErrors['field'] = 1;
		$this->Form->request->data['Contact']['field'] = 'myvalue';
		$result = $this->Form->checkbox('Contact.field', array('id' => 'theID', 'value' => 'myvalue'));
		$expected = array(
			'input' => array('type' => 'hidden', 'class' => 'form-error', 'name' => 'data[Contact][field]', 'value' => '0', 'id' => 'theID_'),
			array('input' => array('preg:/[^<]+/', 'value' => 'myvalue', 'id' => 'theID', 'checked' => 'checked', 'class' => 'form-error'))
		);
		$this->assertTags($result, $expected);

		$result = $this->Form->checkbox('Contact.field', array('value' => 'myvalue'));
		$expected = array(
			'input' => array('type' => 'hidden', 'class' => 'form-error', 'name' => 'data[Contact][field]', 'value' => '0', 'id' => 'ContactField_'),
			array('input' => array('preg:/[^<]+/', 'value' => 'myvalue', 'id' => 'ContactField', 'checked' => 'checked', 'class' => 'form-error'))
		);
		$this->assertTags($result, $expected);

		$this->Form->request->data['Contact']['field'] = '';
		$result = $this->Form->checkbox('Contact.field', array('id' => 'theID'));
		$expected = array(
			'input' => array('type' => 'hidden', 'class' => 'form-error', 'name' => 'data[Contact][field]', 'value' => '0', 'id' => 'theID_'),
			array('input' => array('type' => 'checkbox', 'name' => 'data[Contact][field]', 'value' => '1', 'id' => 'theID', 'class' => 'form-error'))
		);
		$this->assertTags($result, $expected);

		$Contact->validationErrors = array();
		$result = $this->Form->checkbox('Contact.field', array('value' => 'myvalue'));
		$expected = array(
			'input' => array('type' => 'hidden', 'name' => 'data[Contact][field]', 'value' => '0', 'id' => 'ContactField_'),
			array('input' => array('type' => 'checkbox', 'name' => 'data[Contact][field]', 'value' => 'myvalue', 'id' => 'ContactField'))
		);
		$this->assertTags($result, $expected);

		$this->Form->request->data['Contact']['published'] = 1;
		$result = $this->Form->checkbox('Contact.published', array('id' => 'theID'));
		$expected = array(
			'input' => array('type' => 'hidden', 'name' => 'data[Contact][published]', 'value' => '0', 'id' => 'theID_'),
			array('input' => array('type' => 'checkbox', 'name' => 'data[Contact][published]', 'value' => '1', 'id' => 'theID', 'checked' => 'checked'))
		);
		$this->assertTags($result, $expected);

		$this->Form->request->data['Contact']['published'] = 0;
		$result = $this->Form->checkbox('Contact.published', array('id' => 'theID'));
		$expected = array(
			'input' => array('type' => 'hidden', 'name' => 'data[Contact][published]', 'value' => '0', 'id' => 'theID_'),
			array('input' => array('type' => 'checkbox', 'name' => 'data[Contact][published]', 'value' => '1', 'id' => 'theID'))
		);
		$this->assertTags($result, $expected);

		$result = $this->Form->checkbox('Model.CustomField.1.value');
		$expected = array(
			'input' => array('type' => 'hidden', 'name' => 'data[Model][CustomField][1][value]', 'value' => '0', 'id' => 'ModelCustomField1Value_'),
			array('input' => array('type' => 'checkbox', 'name' => 'data[Model][CustomField][1][value]', 'value' => '1', 'id' => 'ModelCustomField1Value'))
		);
		$this->assertTags($result, $expected);

		$result = $this->Form->checkbox('CustomField.1.value');
		$expected = array(
			'input' => array('type' => 'hidden', 'name' => 'data[CustomField][1][value]', 'value' => '0', 'id' => 'CustomField1Value_'),
			array('input' => array('type' => 'checkbox', 'name' => 'data[CustomField][1][value]', 'value' => '1', 'id' => 'CustomField1Value'))
		);
		$this->assertTags($result, $expected);
	}

/**
 * test checkbox() with a custom name attribute
 *
 * @return void
 */
	public function testCheckboxCustomNameAttribute() {
		$result = $this->Form->checkbox('Test.test', array('name' => 'myField'));
		$expected = array(
				'input' => array('type' => 'hidden', 'name' => 'myField', 'value' => '0', 'id' => 'TestTest_'),
				array('input' => array('type' => 'checkbox', 'name' => 'myField', 'value' => '1', 'id' => 'TestTest'))
			);
		$this->assertTags($result, $expected);
	}

/**
 * test the checked option for checkboxes.
 *
 * @return void
 */
	public function testCheckboxCheckedOption() {
		$result = $this->Form->checkbox('Model.field', array('checked' => 'checked'));
		$expected = array(
			'input' => array('type' => 'hidden', 'name' => 'data[Model][field]', 'value' => '0', 'id' => 'ModelField_'),
			array('input' => array('type' => 'checkbox', 'name' => 'data[Model][field]', 'value' => '1', 'id' => 'ModelField', 'checked' => 'checked'))
		);
		$this->assertTags($result, $expected);

		$result = $this->Form->checkbox('Model.field', array('checked' => 1));
		$expected = array(
			'input' => array('type' => 'hidden', 'name' => 'data[Model][field]', 'value' => '0', 'id' => 'ModelField_'),
			array('input' => array('type' => 'checkbox', 'name' => 'data[Model][field]', 'value' => '1', 'id' => 'ModelField', 'checked' => 'checked'))
		);
		$this->assertTags($result, $expected);

		$result = $this->Form->checkbox('Model.field', array('checked' => true));
		$expected = array(
			'input' => array('type' => 'hidden', 'name' => 'data[Model][field]', 'value' => '0', 'id' => 'ModelField_'),
			array('input' => array('type' => 'checkbox', 'name' => 'data[Model][field]', 'value' => '1', 'id' => 'ModelField', 'checked' => 'checked'))
		);
		$this->assertTags($result, $expected);

		$result = $this->Form->checkbox('Model.field', array('checked' => false));
		$expected = array(
			'input' => array('type' => 'hidden', 'name' => 'data[Model][field]', 'value' => '0', 'id' => 'ModelField_'),
			array('input' => array('type' => 'checkbox', 'name' => 'data[Model][field]', 'value' => '1', 'id' => 'ModelField'))
		);
		$this->assertTags($result, $expected);

		$this->Form->request->data['Model']['field'] = 1;
		$result = $this->Form->checkbox('Model.field', array('checked' => false));
		$expected = array(
			'input' => array('type' => 'hidden', 'name' => 'data[Model][field]', 'value' => '0', 'id' => 'ModelField_'),
			array('input' => array('type' => 'checkbox', 'name' => 'data[Model][field]', 'value' => '1', 'id' => 'ModelField'))
		);
		$this->assertTags($result, $expected);
	}

/**
 * Test that disabled attribute works on both the checkbox and hidden input.
 *
 * @return void
 */
	public function testCheckboxDisabling() {
		$result = $this->Form->checkbox('Account.show_name', array('disabled' => 'disabled'));
		$expected = array(
			array('input' => array('type' => 'hidden', 'name' => 'data[Account][show_name]', 'value' => '0', 'id' => 'AccountShowName_', 'disabled' => 'disabled')),
			array('input' => array('type' => 'checkbox', 'name' => 'data[Account][show_name]', 'value' => '1', 'id' => 'AccountShowName', 'disabled' => 'disabled'))
		);
		$this->assertTags($result, $expected);

		$result = $this->Form->checkbox('Account.show_name', array('disabled' => false));
		$expected = array(
			array('input' => array('type' => 'hidden', 'name' => 'data[Account][show_name]', 'value' => '0', 'id' => 'AccountShowName_')),
			array('input' => array('type' => 'checkbox', 'name' => 'data[Account][show_name]', 'value' => '1', 'id' => 'AccountShowName'))
		);
		$this->assertTags($result, $expected);
	}

/**
 * Test that the hidden input for checkboxes can be omitted or set to a
 * specific value.
 *
 * @return void
 */
	public function testCheckboxHiddenField() {
		$result = $this->Form->input('UserForm.something', array(
			'type' => 'checkbox',
			'hiddenField' => false
		));
		$expected = array(
			'div' => array('class' => 'input checkbox'),
			array('input' => array(
				'type' => 'checkbox', 'name' => 'data[UserForm][something]',
				'value' => '1', 'id' => 'UserFormSomething'
			)),
			'label' => array('for' => 'UserFormSomething'),
			'Something',
			'/label',
			'/div'
		);
		$this->assertTags($result, $expected);

		$result = $this->Form->input('UserForm.something', array(
			'type' => 'checkbox',
			'value' => 'Y',
			'hiddenField' => 'N',
		));
		$expected = array(
			'div' => array('class' => 'input checkbox'),
			array('input' => array(
				'type' => 'hidden', 'name' => 'data[UserForm][something]',
				'value' => 'N', 'id' => 'UserFormSomething_'
			)),
			array('input' => array(
				'type' => 'checkbox', 'name' => 'data[UserForm][something]',
				'value' => 'Y', 'id' => 'UserFormSomething'
			)),
			'label' => array('for' => 'UserFormSomething'),
			'Something',
			'/label',
			'/div'
		);
		$this->assertTags($result, $expected);
	}

/**
 * testDateTime method
 *
 * Test generation of date/time select elements
 *
 * @return void
 */
	public function testDateTime() {
		extract($this->dateRegex);

		$result = $this->Form->dateTime('Contact.date', 'DMY', '12', array('empty' => false));
		$now = strtotime('now');
		$expected = array(
			array('select' => array('name' => 'data[Contact][date][day]', 'id' => 'ContactDateDay')),
			$daysRegex,
			array('option' => array('value' => date('d', $now), 'selected' => 'selected')),
			date('j', $now),
			'/option',
			'*/select',
			'-',
			array('select' => array('name' => 'data[Contact][date][month]', 'id' => 'ContactDateMonth')),
			$monthsRegex,
			array('option' => array('value' => date('m', $now), 'selected' => 'selected')),
			date('F', $now),
			'/option',
			'*/select',
			'-',
			array('select' => array('name' => 'data[Contact][date][year]', 'id' => 'ContactDateYear')),
			$yearsRegex,
			array('option' => array('value' => date('Y', $now), 'selected' => 'selected')),
			date('Y', $now),
			'/option',
			'*/select',
			array('select' => array('name' => 'data[Contact][date][hour]', 'id' => 'ContactDateHour')),
			$hoursRegex,
			array('option' => array('value' => date('h', $now), 'selected' => 'selected')),
			date('g', $now),
			'/option',
			'*/select',
			':',
			array('select' => array('name' => 'data[Contact][date][min]', 'id' => 'ContactDateMin')),
			$minutesRegex,
			array('option' => array('value' => date('i', $now), 'selected' => 'selected')),
			date('i', $now),
			'/option',
			'*/select',
			' ',
			array('select' => array('name' => 'data[Contact][date][meridian]', 'id' => 'ContactDateMeridian')),
			$meridianRegex,
			array('option' => array('value' => date('a', $now), 'selected' => 'selected')),
			date('a', $now),
			'/option',
			'*/select'
		);
		$this->assertTags($result, $expected);

		$result = $this->Form->dateTime('Contact.date', 'DMY', '12');
		$expected = array(
			array('select' => array('name' => 'data[Contact][date][day]', 'id' => 'ContactDateDay')),
			$daysRegex,
			array('option' => array('value' => '')),
			'/option',
			'*/select',
			'-',
			array('select' => array('name' => 'data[Contact][date][month]', 'id' => 'ContactDateMonth')),
			$monthsRegex,
			array('option' => array('value' => '')),
			'/option',
			'*/select',
			'-',
			array('select' => array('name' => 'data[Contact][date][year]', 'id' => 'ContactDateYear')),
			$yearsRegex,
			array('option' => array('value' => '')),
			'/option',
			'*/select',
			array('select' => array('name' => 'data[Contact][date][hour]', 'id' => 'ContactDateHour')),
			$hoursRegex,
			array('option' => array('value' => '')),
			'/option',
			'*/select',
			':',
			array('select' => array('name' => 'data[Contact][date][min]', 'id' => 'ContactDateMin')),
			$minutesRegex,
			array('option' => array('value' => '')),
			'/option',
			'*/select',
			' ',
			array('select' => array('name' => 'data[Contact][date][meridian]', 'id' => 'ContactDateMeridian')),
			$meridianRegex,
			array('option' => array('value' => '')),
			'/option',
			'*/select'
		);
		$this->assertTags($result, $expected);
		$this->assertNotRegExp('/<option[^<>]+value=""[^<>]+selected="selected"[^>]*>/', $result);

		$result = $this->Form->dateTime('Contact.date', 'DMY', '12', array('value' => false));
		$expected = array(
			array('select' => array('name' => 'data[Contact][date][day]', 'id' => 'ContactDateDay')),
			$daysRegex,
			array('option' => array('value' => '')),
			'/option',
			'*/select',
			'-',
			array('select' => array('name' => 'data[Contact][date][month]', 'id' => 'ContactDateMonth')),
			$monthsRegex,
			array('option' => array('value' => '')),
			'/option',
			'*/select',
			'-',
			array('select' => array('name' => 'data[Contact][date][year]', 'id' => 'ContactDateYear')),
			$yearsRegex,
			array('option' => array('value' => '')),
			'/option',
			'*/select',
			array('select' => array('name' => 'data[Contact][date][hour]', 'id' => 'ContactDateHour')),
			$hoursRegex,
			array('option' => array('value' => '')),
			'/option',
			'*/select',
			':',
			array('select' => array('name' => 'data[Contact][date][min]', 'id' => 'ContactDateMin')),
			$minutesRegex,
			array('option' => array('value' => '')),
			'/option',
			'*/select',
			' ',
			array('select' => array('name' => 'data[Contact][date][meridian]', 'id' => 'ContactDateMeridian')),
			$meridianRegex,
			array('option' => array('value' => '')),
			'/option',
			'*/select'
		);
		$this->assertTags($result, $expected);
		$this->assertNotRegExp('/<option[^<>]+value=""[^<>]+selected="selected"[^>]*>/', $result);

		$result = $this->Form->dateTime('Contact.date', 'DMY', '12', array('value' => ''));
		$expected = array(
			array('select' => array('name' => 'data[Contact][date][day]', 'id' => 'ContactDateDay')),
			$daysRegex,
			array('option' => array('value' => '')),
			'/option',
			'*/select',
			'-',
			array('select' => array('name' => 'data[Contact][date][month]', 'id' => 'ContactDateMonth')),
			$monthsRegex,
			array('option' => array('value' => '')),
			'/option',
			'*/select',
			'-',
			array('select' => array('name' => 'data[Contact][date][year]', 'id' => 'ContactDateYear')),
			$yearsRegex,
			array('option' => array('value' => '')),
			'/option',
			'*/select',
			array('select' => array('name' => 'data[Contact][date][hour]', 'id' => 'ContactDateHour')),
			$hoursRegex,
			array('option' => array('value' => '')),
			'/option',
			'*/select',
			':',
			array('select' => array('name' => 'data[Contact][date][min]', 'id' => 'ContactDateMin')),
			$minutesRegex,
			array('option' => array('value' => '')),
			'/option',
			'*/select',
			' ',
			array('select' => array('name' => 'data[Contact][date][meridian]', 'id' => 'ContactDateMeridian')),
			$meridianRegex,
			array('option' => array('value' => '')),
			'/option',
			'*/select'
		);
		$this->assertTags($result, $expected);
		$this->assertNotRegExp('/<option[^<>]+value=""[^<>]+selected="selected"[^>]*>/', $result);

		$result = $this->Form->dateTime('Contact.date', 'DMY', '12', array('interval' => 5, 'value' => ''));
		$expected = array(
			array('select' => array('name' => 'data[Contact][date][day]', 'id' => 'ContactDateDay')),
			$daysRegex,
			array('option' => array('value' => '')),
			'/option',
			'*/select',
			'-',
			array('select' => array('name' => 'data[Contact][date][month]', 'id' => 'ContactDateMonth')),
			$monthsRegex,
			array('option' => array('value' => '')),
			'/option',
			'*/select',
			'-',
			array('select' => array('name' => 'data[Contact][date][year]', 'id' => 'ContactDateYear')),
			$yearsRegex,
			array('option' => array('value' => '')),
			'/option',
			'*/select',
			array('select' => array('name' => 'data[Contact][date][hour]', 'id' => 'ContactDateHour')),
			$hoursRegex,
			array('option' => array('value' => '')),
			'/option',
			'*/select',
			':',
			array('select' => array('name' => 'data[Contact][date][min]', 'id' => 'ContactDateMin')),
			$minutesRegex,
			array('option' => array('value' => '')),
			'/option',
			array('option' => array('value' => '00')),
			'00',
			'/option',
			array('option' => array('value' => '05')),
			'05',
			'/option',
			array('option' => array('value' => '10')),
			'10',
			'/option',
			'*/select',
			' ',
			array('select' => array('name' => 'data[Contact][date][meridian]', 'id' => 'ContactDateMeridian')),
			$meridianRegex,
			array('option' => array('value' => '')),
			'/option',
			'*/select'
		);
		$this->assertTags($result, $expected);
		$this->assertNotRegExp('/<option[^<>]+value=""[^<>]+selected="selected"[^>]*>/', $result);

		$result = $this->Form->dateTime('Contact.date', 'DMY', '12', array('minuteInterval' => 5, 'value' => ''));
		$expected = array(
			array('select' => array('name' => 'data[Contact][date][day]', 'id' => 'ContactDateDay')),
			$daysRegex,
			array('option' => array('value' => '')),
			'/option',
			'*/select',
			'-',
			array('select' => array('name' => 'data[Contact][date][month]', 'id' => 'ContactDateMonth')),
			$monthsRegex,
			array('option' => array('value' => '')),
			'/option',
			'*/select',
			'-',
			array('select' => array('name' => 'data[Contact][date][year]', 'id' => 'ContactDateYear')),
			$yearsRegex,
			array('option' => array('value' => '')),
			'/option',
			'*/select',
			array('select' => array('name' => 'data[Contact][date][hour]', 'id' => 'ContactDateHour')),
			$hoursRegex,
			array('option' => array('value' => '')),
			'/option',
			'*/select',
			':',
			array('select' => array('name' => 'data[Contact][date][min]', 'id' => 'ContactDateMin')),
			$minutesRegex,
			array('option' => array('value' => '')),
			'/option',
			array('option' => array('value' => '00')),
			'00',
			'/option',
			array('option' => array('value' => '05')),
			'05',
			'/option',
			array('option' => array('value' => '10')),
			'10',
			'/option',
			'*/select',
			' ',
			array('select' => array('name' => 'data[Contact][date][meridian]', 'id' => 'ContactDateMeridian')),
			$meridianRegex,
			array('option' => array('value' => '')),
			'/option',
			'*/select'
		);
		$this->assertTags($result, $expected);
		$this->assertNotRegExp('/<option[^<>]+value=""[^<>]+selected="selected"[^>]*>/', $result);

		$this->Form->request->data['Contact']['data'] = null;
		$result = $this->Form->dateTime('Contact.date', 'DMY', '12');
		$expected = array(
			array('select' => array('name' => 'data[Contact][date][day]', 'id' => 'ContactDateDay')),
			$daysRegex,
			array('option' => array('value' => '')),
			'/option',
			'*/select',
			'-',
			array('select' => array('name' => 'data[Contact][date][month]', 'id' => 'ContactDateMonth')),
			$monthsRegex,
			array('option' => array('value' => '')),
			'/option',
			'*/select',
			'-',
			array('select' => array('name' => 'data[Contact][date][year]', 'id' => 'ContactDateYear')),
			$yearsRegex,
			array('option' => array('value' => '')),
			'/option',
			'*/select',
			array('select' => array('name' => 'data[Contact][date][hour]', 'id' => 'ContactDateHour')),
			$hoursRegex,
			array('option' => array('value' => '')),
			'/option',
			'*/select',
			':',
			array('select' => array('name' => 'data[Contact][date][min]', 'id' => 'ContactDateMin')),
			$minutesRegex,
			array('option' => array('value' => '')),
			'/option',
			'*/select',
			' ',
			array('select' => array('name' => 'data[Contact][date][meridian]', 'id' => 'ContactDateMeridian')),
			$meridianRegex,
			array('option' => array('value' => '')),
			'/option',
			'*/select'
		);
		$this->assertTags($result, $expected);
		$this->assertNotRegExp('/<option[^<>]+value=""[^<>]+selected="selected"[^>]*>/', $result);

		$this->Form->request->data['Model']['field'] = date('Y') . '-01-01 00:00:00';
		$now = strtotime($this->Form->data['Model']['field']);
		$result = $this->Form->dateTime('Model.field', 'DMY', '12', array('empty' => false));
		$expected = array(
			array('select' => array('name' => 'data[Model][field][day]', 'id' => 'ModelFieldDay')),
			$daysRegex,
			array('option' => array('value' => date('d', $now), 'selected' => 'selected')),
			date('j', $now),
			'/option',
			'*/select',
			'-',
			array('select' => array('name' => 'data[Model][field][month]', 'id' => 'ModelFieldMonth')),
			$monthsRegex,
			array('option' => array('value' => date('m', $now), 'selected' => 'selected')),
			date('F', $now),
			'/option',
			'*/select',
			'-',
			array('select' => array('name' => 'data[Model][field][year]', 'id' => 'ModelFieldYear')),
			$yearsRegex,
			array('option' => array('value' => date('Y', $now), 'selected' => 'selected')),
			date('Y', $now),
			'/option',
			'*/select',
			array('select' => array('name' => 'data[Model][field][hour]', 'id' => 'ModelFieldHour')),
			$hoursRegex,
			array('option' => array('value' => date('h', $now), 'selected' => 'selected')),
			date('g', $now),
			'/option',
			'*/select',
			':',
			array('select' => array('name' => 'data[Model][field][min]', 'id' => 'ModelFieldMin')),
			$minutesRegex,
			array('option' => array('value' => date('i', $now), 'selected' => 'selected')),
			date('i', $now),
			'/option',
			'*/select',
			' ',
			array('select' => array('name' => 'data[Model][field][meridian]', 'id' => 'ModelFieldMeridian')),
			$meridianRegex,
			array('option' => array('value' => date('a', $now), 'selected' => 'selected')),
			date('a', $now),
			'/option',
			'*/select'
		);
		$this->assertTags($result, $expected);

		$selected = strtotime('2008-10-26 12:33:00');
		$result = $this->Form->dateTime('Model.field', 'DMY', '12', array('value' => $selected));
		$this->assertRegExp('/<option[^<>]+value="2008"[^<>]+selected="selected"[^>]*>2008<\/option>/', $result);
		$this->assertRegExp('/<option[^<>]+value="10"[^<>]+selected="selected"[^>]*>October<\/option>/', $result);
		$this->assertRegExp('/<option[^<>]+value="26"[^<>]+selected="selected"[^>]*>26<\/option>/', $result);
		$this->assertRegExp('/<option[^<>]+value="12"[^<>]+selected="selected"[^>]*>12<\/option>/', $result);
		$this->assertRegExp('/<option[^<>]+value="33"[^<>]+selected="selected"[^>]*>33<\/option>/', $result);
		$this->assertRegExp('/<option[^<>]+value="pm"[^<>]+selected="selected"[^>]*>pm<\/option>/', $result);

		$this->Form->create('Contact');
		$result = $this->Form->input('published');
		$now = strtotime('now');
		$expected = array(
			'div' => array('class' => 'input date'),
			'label' => array('for' => 'ContactPublishedMonth'),
			'Published',
			'/label',
			array('select' => array('name' => 'data[Contact][published][month]', 'id' => 'ContactPublishedMonth')),
			$monthsRegex,
			array('option' => array('value' => date('m', $now), 'selected' => 'selected')),
			date('F', $now),
			'/option',
			'*/select',
			'-',
			array('select' => array('name' => 'data[Contact][published][day]', 'id' => 'ContactPublishedDay')),
			$daysRegex,
			array('option' => array('value' => date('d', $now), 'selected' => 'selected')),
			date('j', $now),
			'/option',
			'*/select',
			'-',
			array('select' => array('name' => 'data[Contact][published][year]', 'id' => 'ContactPublishedYear')),
			$yearsRegex,
			array('option' => array('value' => date('Y', $now), 'selected' => 'selected')),
			date('Y', $now),
			'/option',
			'*/select',
			'/div'
		);
		$this->assertTags($result, $expected);

		$result = $this->Form->input('published2', array('type' => 'date'));
		$now = strtotime('now');
		$expected = array(
			'div' => array('class' => 'input date'),
			'label' => array('for' => 'ContactPublished2Month'),
			'Published2',
			'/label',
			array('select' => array('name' => 'data[Contact][published2][month]', 'id' => 'ContactPublished2Month')),
			$monthsRegex,
			array('option' => array('value' => date('m', $now), 'selected' => 'selected')),
			date('F', $now),
			'/option',
			'*/select',
			'-',
			array('select' => array('name' => 'data[Contact][published2][day]', 'id' => 'ContactPublished2Day')),
			$daysRegex,
			array('option' => array('value' => date('d', $now), 'selected' => 'selected')),
			date('j', $now),
			'/option',
			'*/select',
			'-',
			array('select' => array('name' => 'data[Contact][published2][year]', 'id' => 'ContactPublished2Year')),
			$yearsRegex,
			array('option' => array('value' => date('Y', $now), 'selected' => 'selected')),
			date('Y', $now),
			'/option',
			'*/select',
			'/div'
		);
		$this->assertTags($result, $expected);

		$this->Form->create('Contact');
		$result = $this->Form->input('published', array('monthNames' => false));
		$now = strtotime('now');
		$expected = array(
			'div' => array('class' => 'input date'),
			'label' => array('for' => 'ContactPublishedMonth'),
			'Published',
			'/label',
			array('select' => array('name' => 'data[Contact][published][month]', 'id' => 'ContactPublishedMonth')),
			'preg:/(?:<option value="([\d])+">[\d]+<\/option>[\r\n]*)*/',
			array('option' => array('value' => date('m', $now), 'selected' => 'selected')),
			date('m', $now),
			'/option',
			'*/select',
			'-',
			array('select' => array('name' => 'data[Contact][published][day]', 'id' => 'ContactPublishedDay')),
			$daysRegex,
			array('option' => array('value' => date('d', $now), 'selected' => 'selected')),
			date('j', $now),
			'/option',
			'*/select',
			'-',
			array('select' => array('name' => 'data[Contact][published][year]', 'id' => 'ContactPublishedYear')),
			$yearsRegex,
			array('option' => array('value' => date('Y', $now), 'selected' => 'selected')),
			date('Y', $now),
			'/option',
			'*/select',
			'/div'
		);
		$this->assertTags($result, $expected);

		$result = $this->Form->input('published', array('type' => 'time'));
		$now = strtotime('now');
		$expected = array(
			'div' => array('class' => 'input time'),
			'label' => array('for' => 'ContactPublishedHour'),
			'Published',
			'/label',
			array('select' => array('name' => 'data[Contact][published][hour]', 'id' => 'ContactPublishedHour')),
			'preg:/(?:<option value="([\d])+">[\d]+<\/option>[\r\n]*)*/',
			array('option' => array('value' => date('h', $now), 'selected' => 'selected')),
			date('g', $now),
			'/option',
			'*/select',
			':',
		);
		$this->assertTags($result, $expected);

		$result = $this->Form->input('published', array(
			'timeFormat' => 24,
			'interval' => 5,
			'selected' => strtotime('2009-09-03 13:37:00'),
			'type' => 'datetime'
		));
		$this->assertRegExp('/<option[^<>]+value="2009"[^<>]+selected="selected"[^>]*>2009<\/option>/', $result);
		$this->assertRegExp('/<option[^<>]+value="09"[^<>]+selected="selected"[^>]*>September<\/option>/', $result);
		$this->assertRegExp('/<option[^<>]+value="03"[^<>]+selected="selected"[^>]*>3<\/option>/', $result);
		$this->assertRegExp('/<option[^<>]+value="13"[^<>]+selected="selected"[^>]*>13<\/option>/', $result);
		$this->assertRegExp('/<option[^<>]+value="35"[^<>]+selected="selected"[^>]*>35<\/option>/', $result);

		$this->assertNoErrors();
		$this->Form->request->data['Contact'] = array(
			'date' => array(
				'day' => '',
				'month' => '',
				'year' => '',
				'hour' => '',
				'min' => '',
				'meridian' => ''
			)
		);
		$result = $this->Form->dateTime('Contact.date', 'DMY', '12', array('empty' => false));
	}

/**
 * test that datetime() and default values work.
 *
 * @return void
 */
	public function testDatetimeWithDefault() {
		$result = $this->Form->dateTime('Contact.updated', 'DMY', '12', array('value' => '2009-06-01 11:15:30'));
		$this->assertRegExp('/<option[^<>]+value="2009"[^<>]+selected="selected"[^>]*>2009<\/option>/', $result);
		$this->assertRegExp('/<option[^<>]+value="01"[^<>]+selected="selected"[^>]*>1<\/option>/', $result);
		$this->assertRegExp('/<option[^<>]+value="06"[^<>]+selected="selected"[^>]*>June<\/option>/', $result);

		$result = $this->Form->dateTime('Contact.updated', 'DMY', '12', array(
			'default' => '2009-06-01 11:15:30'
		));
		$this->assertRegExp('/<option[^<>]+value="2009"[^<>]+selected="selected"[^>]*>2009<\/option>/', $result);
		$this->assertRegExp('/<option[^<>]+value="01"[^<>]+selected="selected"[^>]*>1<\/option>/', $result);
		$this->assertRegExp('/<option[^<>]+value="06"[^<>]+selected="selected"[^>]*>June<\/option>/', $result);
	}

/**
 * test that bogus non-date time data doesn't cause errors.
 *
 * @return void
 */
	public function testDateTimeWithBogusData() {
		$result = $this->Form->dateTime('Contact.updated', 'DMY', '12', array('value' => 'CURRENT_TIMESTAMP'));
		$this->assertNotRegExp('/selected="selected">\d/', $result);
	}

/**
 * testFormDateTimeMulti method
 *
 * test multiple datetime element generation
 *
 * @return void
 */
	public function testFormDateTimeMulti() {
		extract($this->dateRegex);

		$result = $this->Form->dateTime('Contact.1.updated');
		$expected = array(
			array('select' => array('name' => 'data[Contact][1][updated][day]', 'id' => 'Contact1UpdatedDay')),
			$daysRegex,
			array('option' => array('value' => '')),
			'/option',
			'*/select',
			'-',
			array('select' => array('name' => 'data[Contact][1][updated][month]', 'id' => 'Contact1UpdatedMonth')),
			$monthsRegex,
			array('option' => array('value' => '')),
			'/option',
			'*/select',
			'-',
			array('select' => array('name' => 'data[Contact][1][updated][year]', 'id' => 'Contact1UpdatedYear')),
			$yearsRegex,
			array('option' => array('value' => '')),
			'/option',
			'*/select',
			array('select' => array('name' => 'data[Contact][1][updated][hour]', 'id' => 'Contact1UpdatedHour')),
			$hoursRegex,
			array('option' => array('value' => '')),
			'/option',
			'*/select',
			':',
			array('select' => array('name' => 'data[Contact][1][updated][min]', 'id' => 'Contact1UpdatedMin')),
			$minutesRegex,
			array('option' => array('value' => '')),
			'/option',
			'*/select',
			' ',
			array('select' => array('name' => 'data[Contact][1][updated][meridian]', 'id' => 'Contact1UpdatedMeridian')),
			$meridianRegex,
			array('option' => array('value' => '')),
			'/option',
			'*/select'
		);
		$this->assertTags($result, $expected);

		$result = $this->Form->dateTime('Contact.2.updated');
		$expected = array(
			array('select' => array('name' => 'data[Contact][2][updated][day]', 'id' => 'Contact2UpdatedDay')),
			$daysRegex,
			array('option' => array('value' => '')),
			'/option',
			'*/select',
			'-',
			array('select' => array('name' => 'data[Contact][2][updated][month]', 'id' => 'Contact2UpdatedMonth')),
			$monthsRegex,
			array('option' => array('value' => '')),
			'/option',
			'*/select',
			'-',
			array('select' => array('name' => 'data[Contact][2][updated][year]', 'id' => 'Contact2UpdatedYear')),
			$yearsRegex,
			array('option' => array('value' => '')),
			'/option',
			'*/select',
			array('select' => array('name' => 'data[Contact][2][updated][hour]', 'id' => 'Contact2UpdatedHour')),
			$hoursRegex,
			array('option' => array('value' => '')),
			'/option',
			'*/select',
			':',
			array('select' => array('name' => 'data[Contact][2][updated][min]', 'id' => 'Contact2UpdatedMin')),
			$minutesRegex,
			array('option' => array('value' => '')),
			'/option',
			'*/select',
			' ',
			array('select' => array('name' => 'data[Contact][2][updated][meridian]', 'id' => 'Contact2UpdatedMeridian')),
			$meridianRegex,
			array('option' => array('value' => '')),
			'/option',
			'*/select'
		);
		$this->assertTags($result, $expected);
	}

/**
 * When changing the date format, the label should always focus the first select box when
 * clicked.
 *
 * @return void
 */
	public function testDateTimeLabelIdMatchesFirstInput() {
		$result = $this->Form->input('Model.date', array('type' => 'date'));
		$this->assertContains('label for="ModelDateMonth"', $result);

		$result = $this->Form->input('Model.date', array('type' => 'date', 'dateFormat' => 'DMY'));
		$this->assertContains('label for="ModelDateDay"', $result);

		$result = $this->Form->input('Model.date', array('type' => 'date', 'dateFormat' => 'YMD'));
		$this->assertContains('label for="ModelDateYear"', $result);
	}

/**
 * testMonth method
 *
 * @return void
 */
	public function testMonth() {
		$result = $this->Form->month('Model.field');
		$expected = array(
			array('select' => array('name' => 'data[Model][field][month]', 'id' => 'ModelFieldMonth')),
			array('option' => array('value' => '')),
			'/option',
			array('option' => array('value' => '01')),
			date('F', strtotime('2008-01-01 00:00:00')),
			'/option',
			array('option' => array('value' => '02')),
			date('F', strtotime('2008-02-01 00:00:00')),
			'/option',
			'*/select',
		);
		$this->assertTags($result, $expected);

		$result = $this->Form->month('Model.field', array('empty' => true));
		$expected = array(
			array('select' => array('name' => 'data[Model][field][month]', 'id' => 'ModelFieldMonth')),
			array('option' => array('value' => '')),
			'/option',
			array('option' => array('value' => '01')),
			date('F', strtotime('2008-01-01 00:00:00')),
			'/option',
			array('option' => array('value' => '02')),
			date('F', strtotime('2008-02-01 00:00:00')),
			'/option',
			'*/select',
		);
		$this->assertTags($result, $expected);

		$result = $this->Form->month('Model.field', array('monthNames' => false));
		$expected = array(
			array('select' => array('name' => 'data[Model][field][month]', 'id' => 'ModelFieldMonth')),
			array('option' => array('value' => '')),
			'/option',
			array('option' => array('value' => '01')),
			'01',
			'/option',
			array('option' => array('value' => '02')),
			'02',
			'/option',
			'*/select',
		);
		$this->assertTags($result, $expected);

		$monthNames = array(
			'01' => 'Jan', '02' => 'Feb', '03' => 'Mar', '04' => 'Apr', '05' => 'May', '06' => 'Jun',
			'07' => 'Jul', '08' => 'Aug', '09' => 'Sep', '10' => 'Oct', '11' => 'Nov', '12' => 'Dec');
		$result = $this->Form->month('Model.field', array('monthNames' => $monthNames));
		$expected = array(
			array('select' => array('name' => 'data[Model][field][month]', 'id' => 'ModelFieldMonth')),
			array('option' => array('value' => '')),
			'/option',
			array('option' => array('value' => '01')),
			'Jan',
			'/option',
			array('option' => array('value' => '02')),
			'Feb',
			'/option',
			'*/select',
		);
		$this->assertTags($result, $expected);
	}

/**
 * testDay method
 *
 * @return void
 */
	public function testDay() {
		extract($this->dateRegex);

		$result = $this->Form->day('Model.field', array('value' => false));
		$expected = array(
			array('select' => array('name' => 'data[Model][field][day]', 'id' => 'ModelFieldDay')),
			array('option' => array('value' => '')),
			'/option',
			array('option' => array('value' => '01')),
			'1',
			'/option',
			array('option' => array('value' => '02')),
			'2',
			'/option',
			$daysRegex,
			'/select',
		);
		$this->assertTags($result, $expected);

		$this->Form->request->data['Model']['field'] = '2006-10-10 23:12:32';
		$result = $this->Form->day('Model.field');
		$expected = array(
			array('select' => array('name' => 'data[Model][field][day]', 'id' => 'ModelFieldDay')),
			array('option' => array('value' => '')),
			'/option',
			array('option' => array('value' => '01')),
			'1',
			'/option',
			array('option' => array('value' => '02')),
			'2',
			'/option',
			$daysRegex,
			array('option' => array('value' => '10', 'selected' => 'selected')),
			'10',
			'/option',
			$daysRegex,
			'/select',
		);
		$this->assertTags($result, $expected);

		$this->Form->request->data['Model']['field'] = '';
		$result = $this->Form->day('Model.field', array('value' => '10'));
		$expected = array(
			array('select' => array('name' => 'data[Model][field][day]', 'id' => 'ModelFieldDay')),
			array('option' => array('value' => '')),
			'/option',
			array('option' => array('value' => '01')),
			'1',
			'/option',
			array('option' => array('value' => '02')),
			'2',
			'/option',
			$daysRegex,
			array('option' => array('value' => '10', 'selected' => 'selected')),
			'10',
			'/option',
			$daysRegex,
			'/select',
		);
		$this->assertTags($result, $expected);

		$this->Form->request->data['Model']['field'] = '2006-10-10 23:12:32';
		$result = $this->Form->day('Model.field', array('value' => true));
		$expected = array(
			array('select' => array('name' => 'data[Model][field][day]', 'id' => 'ModelFieldDay')),
			array('option' => array('value' => '')),
			'/option',
			array('option' => array('value' => '01')),
			'1',
			'/option',
			array('option' => array('value' => '02')),
			'2',
			'/option',
			$daysRegex,
			array('option' => array('value' => '10', 'selected' => 'selected')),
			'10',
			'/option',
			$daysRegex,
			'/select',
		);
		$this->assertTags($result, $expected);
	}

/**
 * testMinute method
 *
 * @return void
 */
	public function testMinute() {
		extract($this->dateRegex);

		$result = $this->Form->minute('Model.field');
		$expected = array(
			array('select' => array('name' => 'data[Model][field][min]', 'id' => 'ModelFieldMin')),
			array('option' => array('value' => '')),
			'/option',
			array('option' => array('value' => '00')),
			'00',
			'/option',
			array('option' => array('value' => '01')),
			'01',
			'/option',
			array('option' => array('value' => '02')),
			'02',
			'/option',
			$minutesRegex,
			'/select',
		);
		$this->assertTags($result, $expected);

		$this->Form->request->data['Model']['field'] = '2006-10-10 00:12:32';
		$result = $this->Form->minute('Model.field');
		$expected = array(
			array('select' => array('name' => 'data[Model][field][min]', 'id' => 'ModelFieldMin')),
			array('option' => array('value' => '')),
			'/option',
			array('option' => array('value' => '00')),
			'00',
			'/option',
			array('option' => array('value' => '01')),
			'01',
			'/option',
			array('option' => array('value' => '02')),
			'02',
			'/option',
			$minutesRegex,
			array('option' => array('value' => '12', 'selected' => 'selected')),
			'12',
			'/option',
			$minutesRegex,
			'/select',
		);
		$this->assertTags($result, $expected);

		$this->Form->request->data['Model']['field'] = '';
		$result = $this->Form->minute('Model.field', array('interval' => 5));
		$expected = array(
			array('select' => array('name' => 'data[Model][field][min]', 'id' => 'ModelFieldMin')),
			array('option' => array('value' => '')),
			'/option',
			array('option' => array('value' => '00')),
			'00',
			'/option',
			array('option' => array('value' => '05')),
			'05',
			'/option',
			array('option' => array('value' => '10')),
			'10',
			'/option',
			$minutesRegex,
			'/select',
		);
		$this->assertTags($result, $expected);

		$this->Form->request->data['Model']['field'] = '2006-10-10 00:10:32';
		$result = $this->Form->minute('Model.field', array('interval' => 5));
		$expected = array(
			array('select' => array('name' => 'data[Model][field][min]', 'id' => 'ModelFieldMin')),
			array('option' => array('value' => '')),
			'/option',
			array('option' => array('value' => '00')),
			'00',
			'/option',
			array('option' => array('value' => '05')),
			'05',
			'/option',
			array('option' => array('value' => '10', 'selected' => 'selected')),
			'10',
			'/option',
			$minutesRegex,
			'/select',
		);
		$this->assertTags($result, $expected);
	}

/**
 * testHour method
 *
 * @return void
 */
	public function testHour() {
		extract($this->dateRegex);

		$result = $this->Form->hour('Model.field', false);
		$expected = array(
			array('select' => array('name' => 'data[Model][field][hour]', 'id' => 'ModelFieldHour')),
			array('option' => array('value' => '')),
			'/option',
			array('option' => array('value' => '01')),
			'1',
			'/option',
			array('option' => array('value' => '02')),
			'2',
			'/option',
			$hoursRegex,
			'/select',
		);
		$this->assertTags($result, $expected);

		$this->Form->request->data['Model']['field'] = '2006-10-10 00:12:32';
		$result = $this->Form->hour('Model.field', false);
		$expected = array(
			array('select' => array('name' => 'data[Model][field][hour]', 'id' => 'ModelFieldHour')),
			array('option' => array('value' => '')),
			'/option',
			array('option' => array('value' => '01')),
			'1',
			'/option',
			array('option' => array('value' => '02')),
			'2',
			'/option',
			$hoursRegex,
			array('option' => array('value' => '12', 'selected' => 'selected')),
			'12',
			'/option',
			'/select',
		);
		$this->assertTags($result, $expected);

		$this->Form->request->data['Model']['field'] = '';
		$result = $this->Form->hour('Model.field', true, array('value' => '23'));
		$expected = array(
			array('select' => array('name' => 'data[Model][field][hour]', 'id' => 'ModelFieldHour')),
			array('option' => array('value' => '')),
			'/option',
			array('option' => array('value' => '00')),
			'0',
			'/option',
			array('option' => array('value' => '01')),
			'1',
			'/option',
			array('option' => array('value' => '02')),
			'2',
			'/option',
			$hoursRegex,
			array('option' => array('value' => '23', 'selected' => 'selected')),
			'23',
			'/option',
			'/select',
		);
		$this->assertTags($result, $expected);

		$this->Form->request->data['Model']['field'] = '2006-10-10 00:12:32';
		$result = $this->Form->hour('Model.field', true);
		$expected = array(
			array('select' => array('name' => 'data[Model][field][hour]', 'id' => 'ModelFieldHour')),
			array('option' => array('value' => '')),
			'/option',
			array('option' => array('value' => '00', 'selected' => 'selected')),
			'0',
			'/option',
			array('option' => array('value' => '01')),
			'1',
			'/option',
			array('option' => array('value' => '02')),
			'2',
			'/option',
			$hoursRegex,
			'/select',
		);
		$this->assertTags($result, $expected);

		unset($this->Form->request->data['Model']['field']);
		$result = $this->Form->hour('Model.field', true, array('value' => 'now'));
		$thisHour = date('H');
		$optValue = date('G');
		$this->assertRegExp('/<option value="' . $thisHour . '" selected="selected">' . $optValue . '<\/option>/', $result);
	}

/**
 * testYear method
 *
 * @return void
 */
	public function testYear() {
		$result = $this->Form->year('Model.field', 2006, 2007);
		$expected = array(
			array('select' => array('name' => 'data[Model][field][year]', 'id' => 'ModelFieldYear')),
			array('option' => array('value' => '')),
			'/option',
			array('option' => array('value' => '2007')),
			'2007',
			'/option',
			array('option' => array('value' => '2006')),
			'2006',
			'/option',
			'/select',
		);
		$this->assertTags($result, $expected);

		$result = $this->Form->year('Model.field', 2006, 2007, array('orderYear' => 'asc'));
		$expected = array(
			array('select' => array('name' => 'data[Model][field][year]', 'id' => 'ModelFieldYear')),
			array('option' => array('value' => '')),
			'/option',
			array('option' => array('value' => '2006')),
			'2006',
			'/option',
			array('option' => array('value' => '2007')),
			'2007',
			'/option',
			'/select',
		);
		$this->assertTags($result, $expected);

		$this->request->data['Contact']['published'] = '';
		$result = $this->Form->year('Contact.published', 2006, 2007, array('class' => 'year'));
		$expected = array(
			array('select' => array('name' => 'data[Contact][published][year]', 'id' => 'ContactPublishedYear', 'class' => 'year')),
			array('option' => array('value' => '')),
			'/option',
			array('option' => array('value' => '2007')),
			'2007',
			'/option',
			array('option' => array('value' => '2006')),
			'2006',
			'/option',
			'/select',
		);
		$this->assertTags($result, $expected);

		$this->Form->request->data['Contact']['published'] = '2006-10-10';
		$result = $this->Form->year('Contact.published', 2006, 2007, array('empty' => false));
		$expected = array(
			array('select' => array('name' => 'data[Contact][published][year]', 'id' => 'ContactPublishedYear')),
			array('option' => array('value' => '2007')),
			'2007',
			'/option',
			array('option' => array('value' => '2006', 'selected' => 'selected')),
			'2006',
			'/option',
			'/select',
		);
		$this->assertTags($result, $expected);

		$this->Form->request->data['Contact']['published'] = '';
		$result = $this->Form->year('Contact.published', 2006, 2007, array('value' => false));
		$expected = array(
			array('select' => array('name' => 'data[Contact][published][year]', 'id' => 'ContactPublishedYear')),
			array('option' => array('value' => '')),
			'/option',
			array('option' => array('value' => '2007')),
			'2007',
			'/option',
			array('option' => array('value' => '2006')),
			'2006',
			'/option',
			'/select',
		);
		$this->assertTags($result, $expected);

		$this->Form->request->data['Contact']['published'] = '2006-10-10';
		$result = $this->Form->year('Contact.published', 2006, 2007, array('empty' => false, 'value' => false));
		$expected = array(
			array('select' => array('name' => 'data[Contact][published][year]', 'id' => 'ContactPublishedYear')),
			array('option' => array('value' => '2007')),
			'2007',
			'/option',
			array('option' => array('value' => '2006', 'selected' => 'selected')),
			'2006',
			'/option',
			'/select',
		);
		$this->assertTags($result, $expected);

		$this->Form->request->data['Contact']['published'] = '';
		$result = $this->Form->year('Contact.published', 2006, 2007, array('value' => 2007));
		$expected = array(
			array('select' => array('name' => 'data[Contact][published][year]', 'id' => 'ContactPublishedYear')),
			array('option' => array('value' => '')),
			'/option',
			array('option' => array('value' => '2007', 'selected' => 'selected')),
			'2007',
			'/option',
			array('option' => array('value' => '2006')),
			'2006',
			'/option',
			'/select',
		);
		$this->assertTags($result, $expected);

		$this->Form->request->data['Contact']['published'] = '2006-10-10';
		$result = $this->Form->year('Contact.published', 2006, 2007, array('empty' => false, 'value' => 2007));
		$expected = array(
			array('select' => array('name' => 'data[Contact][published][year]', 'id' => 'ContactPublishedYear')),
			array('option' => array('value' => '2007', 'selected' => 'selected')),
			'2007',
			'/option',
			array('option' => array('value' => '2006')),
			'2006',
			'/option',
			'/select',
		);
		$this->assertTags($result, $expected);

		$this->Form->request->data['Contact']['published'] = '';
		$result = $this->Form->year('Contact.published', 2006, 2008, array('empty' => false, 'value' => 2007));
		$expected = array(
			array('select' => array('name' => 'data[Contact][published][year]', 'id' => 'ContactPublishedYear')),
			array('option' => array('value' => '2008')),
			'2008',
			'/option',
			array('option' => array('value' => '2007', 'selected' => 'selected')),
			'2007',
			'/option',
			array('option' => array('value' => '2006')),
			'2006',
			'/option',
			'/select',
		);
		$this->assertTags($result, $expected);

		$this->Form->request->data['Contact']['published'] = '2006-10-10';
		$result = $this->Form->year('Contact.published', 2006, 2008, array('empty' => false));
		$expected = array(
			array('select' => array('name' => 'data[Contact][published][year]', 'id' => 'ContactPublishedYear')),
			array('option' => array('value' => '2008')),
			'2008',
			'/option',
			array('option' => array('value' => '2007')),
			'2007',
			'/option',
			array('option' => array('value' => '2006', 'selected' => 'selected')),
			'2006',
			'/option',
			'/select',
		);
		$this->assertTags($result, $expected);

		$this->Form->request->data = array();
		$this->Form->create('Contact');
		$result = $this->Form->year('published', 2006, 2008, array('empty' => false));
		$expected = array(
			array('select' => array('name' => 'data[Contact][published][year]', 'id' => 'ContactPublishedYear')),
			array('option' => array('value' => '2008')),
			'2008',
			'/option',
			array('option' => array('value' => '2007')),
			'2007',
			'/option',
			array('option' => array('value' => '2006')),
			'2006',
			'/option',
			'/select',
		);
		$this->assertTags($result, $expected);

		$result = $this->Form->year('published', array(), array(), array('empty' => false));
		$this->assertContains('data[Contact][published][year]', $result);
	}

/**
 * testTextArea method
 *
 * @return void
 */
	public function testTextArea() {
		$this->Form->request->data = array('Model' => array('field' => 'some test data'));
		$result = $this->Form->textarea('Model.field');
		$expected = array(
			'textarea' => array('name' => 'data[Model][field]', 'id' => 'ModelField'),
			'some test data',
			'/textarea',
		);
		$this->assertTags($result, $expected);

		$result = $this->Form->textarea('Model.tmp');
		$expected = array(
			'textarea' => array('name' => 'data[Model][tmp]', 'id' => 'ModelTmp'),
			'/textarea',
		);
		$this->assertTags($result, $expected);

		$this->Form->request->data = array('Model' => array('field' => 'some <strong>test</strong> data with <a href="#">HTML</a> chars'));
		$result = $this->Form->textarea('Model.field');
		$expected = array(
			'textarea' => array('name' => 'data[Model][field]', 'id' => 'ModelField'),
			htmlentities('some <strong>test</strong> data with <a href="#">HTML</a> chars'),
			'/textarea',
		);
		$this->assertTags($result, $expected);

		$this->Form->request->data = array('Model' => array('field' => 'some <strong>test</strong> data with <a href="#">HTML</a> chars'));
		$result = $this->Form->textarea('Model.field', array('escape' => false));
		$expected = array(
			'textarea' => array('name' => 'data[Model][field]', 'id' => 'ModelField'),
			'some <strong>test</strong> data with <a href="#">HTML</a> chars',
			'/textarea',
		);
		$this->assertTags($result, $expected);

		$this->Form->request->data['Model']['0']['OtherModel']['field'] = null;
		$result = $this->Form->textarea('Model.0.OtherModel.field');
		$expected = array(
			'textarea' => array('name' => 'data[Model][0][OtherModel][field]', 'id' => 'Model0OtherModelField'),
			'/textarea'
		);
		$this->assertTags($result, $expected);
	}

/**
 * testTextAreaWithStupidCharacters method
 *
 * test text area with non-ascii characters
 *
 * @return void
 */
	public function testTextAreaWithStupidCharacters() {
		$this->loadFixtures('Post');
		$result = $this->Form->input('Post.content', array(
			'label' => 'Current Text', 'value' => "GREAT®", 'rows' => '15', 'cols' => '75'
		));
		$expected = array(
			'div' => array('class' => 'input text'),
				'label' => array('for' => 'PostContent'),
					'Current Text',
				'/label',
				'textarea' => array('name' => 'data[Post][content]', 'id' => 'PostContent', 'rows' => '15', 'cols' => '75'),
				'GREAT®',
				'/textarea',
			'/div'
		);
		$this->assertTags($result, $expected);
	}

/**
 * testHiddenField method
 *
 * @return void
 */
	public function testHiddenField() {
		$Contact = ClassRegistry::getObject('Contact');
		$Contact->validationErrors['field'] = 1;
		$this->Form->request->data['Contact']['field'] = 'test';
		$result = $this->Form->hidden('Contact.field', array('id' => 'theID'));
		$this->assertTags($result, array(
			'input' => array('type' => 'hidden', 'class' => 'form-error', 'name' => 'data[Contact][field]', 'id' => 'theID', 'value' => 'test'))
		);
	}

/**
 * testFileUploadField method
 *
 * @return void
 */
	public function testFileUploadField() {
		$result = $this->Form->file('Model.upload');
		$this->assertTags($result, array('input' => array('type' => 'file', 'name' => 'data[Model][upload]', 'id' => 'ModelUpload')));

		$this->Form->request->data['Model.upload'] = array("name" => "", "type" => "", "tmp_name" => "", "error" => 4, "size" => 0);
		$result = $this->Form->input('Model.upload', array('type' => 'file'));
		$expected = array(
			'div' => array('class' => 'input file'),
			'label' => array('for' => 'ModelUpload'),
			'Upload',
			'/label',
			'input' => array('type' => 'file', 'name' => 'data[Model][upload]', 'id' => 'ModelUpload'),
			'/div'
		);
		$this->assertTags($result, $expected);
	}

/**
 * test File upload input on a model not used in create();
 *
 * @return void
 */
	public function testFileUploadOnOtherModel() {
		$this->Form->create('ValidateUser', array('type' => 'file'));
		$result = $this->Form->file('ValidateProfile.city');
		$expected = array(
			'input' => array('type' => 'file', 'name' => 'data[ValidateProfile][city]', 'id' => 'ValidateProfileCity')
		);
		$this->assertTags($result, $expected);
	}

/**
 * testButton method
 *
 * @return void
 */
	public function testButton() {
		$result = $this->Form->button('Hi');
		$this->assertTags($result, array('button' => array('type' => 'submit'), 'Hi', '/button'));

		$result = $this->Form->button('Clear Form >', array('type' => 'reset'));
		$this->assertTags($result, array('button' => array('type' => 'reset'), 'Clear Form >', '/button'));

		$result = $this->Form->button('Clear Form >', array('type' => 'reset', 'id' => 'clearForm'));
		$this->assertTags($result, array('button' => array('type' => 'reset', 'id' => 'clearForm'), 'Clear Form >', '/button'));

		$result = $this->Form->button('<Clear Form>', array('type' => 'reset', 'escape' => true));
		$this->assertTags($result, array('button' => array('type' => 'reset'), '&lt;Clear Form&gt;', '/button'));

		$result = $this->Form->button('No type', array('type' => false));
		$this->assertTags($result, array('button' => array(), 'No type', '/button'));

		$result = $this->Form->button('Upload Text', array('onClick' => "$('#postAddForm').ajaxSubmit({target: '#postTextUpload', url: '/posts/text'});return false;'", 'escape' => false));
		$this->assertNotRegExp('/\&039/', $result);
	}

/**
 * Test that button() makes unlocked fields by default.
 *
 * @return void
 */
	public function testButtonUnlockedByDefault() {
		$this->Form->request->params['_Token']['key'] = 'secured';
		$this->Form->button('Save', array('name' => 'save'));
		$this->Form->button('Clear');

		$result = $this->Form->unlockField();
		$this->assertEquals(array('save'), $result);
	}

/**
 * testPostButton method
 *
 * @return void
 */
	public function testPostButton() {
		$result = $this->Form->postButton('Hi', '/controller/action');
		$this->assertTags($result, array(
			'form' => array('method' => 'post', 'action' => '/controller/action', 'accept-charset' => 'utf-8', 'style' => 'display:none;'),
			'div' => array('style' => 'display:none;'),
			'input' => array('type' => 'hidden', 'name' => '_method', 'value' => 'POST'),
			'/div',
			'button' => array('type' => 'submit'),
			'Hi',
			'/button',
			'/form'
		));

		$result = $this->Form->postButton('Send', '/', array('data' => array('extra' => 'value')));
		$this->assertTrue(strpos($result, '<input type="hidden" name="data[extra]" value="value"/>') !== false);
	}

/**
 * Test that postButton adds _Token fields.
 *
 * @return void
 */
	public function testSecurePostButton() {
		$this->Form->request->params['_Token'] = array('key' => 'testkey');

		$result = $this->Form->postButton('Delete', '/posts/delete/1');
		$expected = array(
			'form' => array(
				'method' => 'post', 'action' => '/posts/delete/1', 'accept-charset' => 'utf-8',
				'style' => 'display:none;'
			),
			array('div' => array('style' => 'display:none;')),
			array('input' => array('type' => 'hidden', 'name' => '_method', 'value' => 'POST')),
			array('input' => array('type' => 'hidden', 'name' => 'data[_Token][key]', 'value' => 'testkey', 'id' => 'preg:/Token\d+/')),
			'/div',
			'button' => array('type' => 'submit'),
			'Delete',
			'/button',
			array('div' => array('style' => 'display:none;')),
			array('input' => array('type' => 'hidden', 'name' => 'data[_Token][fields]', 'value' => 'preg:/[\w\d%]+/', 'id' => 'preg:/TokenFields\d+/')),
			array('input' => array('type' => 'hidden', 'name' => 'data[_Token][unlocked]', 'value' => '', 'id' => 'preg:/TokenUnlocked\d+/')),
			'/div',
			'/form',
		);
		$this->assertTags($result, $expected);
	}

/**
 * testPostLink method
 *
 * @return void
 */
	public function testPostLink() {
		$result = $this->Form->postLink('Delete', '/posts/delete/1');
		$this->assertTags($result, array(
			'form' => array(
				'method' => 'post', 'action' => '/posts/delete/1',
				'name' => 'preg:/post_\w+/', 'id' => 'preg:/post_\w+/', 'style' => 'display:none;'
			),
			'input' => array('type' => 'hidden', 'name' => '_method', 'value' => 'POST'),
			'/form',
			'a' => array('href' => '#', 'onclick' => 'preg:/document\.post_\w+\.submit\(\); event\.returnValue = false; return false;/'),
			'Delete',
			'/a'
		));

		$result = $this->Form->postLink('Delete', '/posts/delete/1', array(), 'Confirm?');
		$this->assertTags($result, array(
			'form' => array(
				'method' => 'post', 'action' => '/posts/delete/1',
				'name' => 'preg:/post_\w+/', 'id' => 'preg:/post_\w+/', 'style' => 'display:none;'
			),
			'input' => array('type' => 'hidden', 'name' => '_method', 'value' => 'POST'),
			'/form',
			'a' => array('href' => '#', 'onclick' => 'preg:/if \(confirm\(&#039;Confirm\?&#039;\)\) \{ document\.post_\w+\.submit\(\); \} event\.returnValue = false; return false;/'),
			'Delete',
			'/a'
		));

		$result = $this->Form->postLink('Delete', '/posts/delete', array('data' => array('id' => 1)));
		$this->assertContains('<input type="hidden" name="data[id]" value="1"/>', $result);
	}

/**
 * Test that postLink adds _Token fields.
 *
 * @return void
 */
	public function testSecurePostLink() {
		$this->Form->request->params['_Token'] = array('key' => 'testkey');

		$result = $this->Form->postLink('Delete', '/posts/delete/1');
		$expected = array(
			'form' => array(
				'method' => 'post', 'action' => '/posts/delete/1',
				'name' => 'preg:/post_\w+/', 'id' => 'preg:/post_\w+/', 'style' => 'display:none;'
			),
			array('input' => array('type' => 'hidden', 'name' => '_method', 'value' => 'POST')),
			array('input' => array('type' => 'hidden', 'name' => 'data[_Token][key]', 'value' => 'testkey', 'id' => 'preg:/Token\d+/')),
			'div' => array('style' => 'display:none;'),
			array('input' => array('type' => 'hidden', 'name' => 'data[_Token][fields]', 'value' => 'preg:/[\w\d%]+/', 'id' => 'preg:/TokenFields\d+/')),
			array('input' => array('type' => 'hidden', 'name' => 'data[_Token][unlocked]', 'value' => '', 'id' => 'preg:/TokenUnlocked\d+/')),
			'/div',
			'/form',
			'a' => array('href' => '#', 'onclick' => 'preg:/document\.post_\w+\.submit\(\); event\.returnValue = false; return false;/'),
			'Delete',
			'/a'
		);
		$this->assertTags($result, $expected);
	}

/**
 * testSubmitButton method
 *
 * @return void
 */
	public function testSubmitButton() {
		$result = $this->Form->submit('');
		$expected = array(
			'div' => array('class' => 'submit'),
			'input' => array('type' => 'submit', 'value' => ''),
			'/div'
		);
		$this->assertTags($result, $expected);

		$result = $this->Form->submit('Test Submit');
		$expected = array(
			'div' => array('class' => 'submit'),
			'input' => array('type' => 'submit', 'value' => 'Test Submit'),
			'/div'
		);
		$this->assertTags($result, $expected);

		$result = $this->Form->submit('Test Submit', array('div' => array('tag' => 'span')));
		$expected = array(
			'span' => array('class' => 'submit'),
			'input' => array('type' => 'submit', 'value' => 'Test Submit'),
			'/span'
		);
		$this->assertTags($result, $expected);

		$result = $this->Form->submit('Test Submit', array('class' => 'save', 'div' => false));
		$expected = array('input' => array('type' => 'submit', 'value' => 'Test Submit', 'class' => 'save'));
		$this->assertTags($result, $expected);

		$result = $this->Form->submit('Test Submit', array('div' => array('id' => 'SaveButton')));
		$expected = array(
			'div' => array('class' => 'submit', 'id' => 'SaveButton'),
			'input' => array('type' => 'submit', 'value' => 'Test Submit'),
			'/div'
		);
		$this->assertTags($result, $expected);

		$result = $this->Form->submit('Next >');
		$expected = array(
			'div' => array('class' => 'submit'),
			'input' => array('type' => 'submit', 'value' => 'Next &gt;'),
			'/div'
		);
		$this->assertTags($result, $expected);

		$result = $this->Form->submit('Next >', array('escape' => false));
		$expected = array(
			'div' => array('class' => 'submit'),
			'input' => array('type' => 'submit', 'value' => 'Next >'),
			'/div'
		);
		$this->assertTags($result, $expected);

		$result = $this->Form->submit('Reset!', array('type' => 'reset'));
		$expected = array(
			'div' => array('class' => 'submit'),
			'input' => array('type' => 'reset', 'value' => 'Reset!'),
			'/div'
		);
		$this->assertTags($result, $expected);

		$before = '--before--';
		$after = '--after--';
		$result = $this->Form->submit('Test', array('before' => $before));
		$expected = array(
			'div' => array('class' => 'submit'),
			'--before--',
			'input' => array('type' => 'submit', 'value' => 'Test'),
			'/div'
		);
		$this->assertTags($result, $expected);

		$result = $this->Form->submit('Test', array('after' => $after));
		$expected = array(
			'div' => array('class' => 'submit'),
			'input' => array('type' => 'submit', 'value' => 'Test'),
			'--after--',
			'/div'
		);
		$this->assertTags($result, $expected);

		$result = $this->Form->submit('Test', array('before' => $before, 'after' => $after));
		$expected = array(
			'div' => array('class' => 'submit'),
			'--before--',
			'input' => array('type' => 'submit', 'value' => 'Test'),
			'--after--',
			'/div'
		);
		$this->assertTags($result, $expected);
	}

/**
 * test image submit types.
 *
 * @return void
 */
	public function testSubmitImage() {
		$result = $this->Form->submit('http://example.com/cake.power.gif');
		$expected = array(
			'div' => array('class' => 'submit'),
			'input' => array('type' => 'image', 'src' => 'http://example.com/cake.power.gif'),
			'/div'
		);
		$this->assertTags($result, $expected);

		$result = $this->Form->submit('/relative/cake.power.gif');
		$expected = array(
			'div' => array('class' => 'submit'),
			'input' => array('type' => 'image', 'src' => 'relative/cake.power.gif'),
			'/div'
		);
		$this->assertTags($result, $expected);

		$result = $this->Form->submit('cake.power.gif');
		$expected = array(
			'div' => array('class' => 'submit'),
			'input' => array('type' => 'image', 'src' => 'img/cake.power.gif'),
			'/div'
		);
		$this->assertTags($result, $expected);

		$result = $this->Form->submit('Not.an.image');
		$expected = array(
			'div' => array('class' => 'submit'),
			'input' => array('type' => 'submit', 'value' => 'Not.an.image'),
			'/div'
		);
		$this->assertTags($result, $expected);

		$after = '--after--';
		$before = '--before--';
		$result = $this->Form->submit('cake.power.gif', array('after' => $after));
		$expected = array(
			'div' => array('class' => 'submit'),
			'input' => array('type' => 'image', 'src' => 'img/cake.power.gif'),
			'--after--',
			'/div'
		);
		$this->assertTags($result, $expected);

		$result = $this->Form->submit('cake.power.gif', array('before' => $before));
		$expected = array(
			'div' => array('class' => 'submit'),
			'--before--',
			'input' => array('type' => 'image', 'src' => 'img/cake.power.gif'),
			'/div'
		);
		$this->assertTags($result, $expected);

		$result = $this->Form->submit('cake.power.gif', array('before' => $before, 'after' => $after));
		$expected = array(
			'div' => array('class' => 'submit'),
			'--before--',
			'input' => array('type' => 'image', 'src' => 'img/cake.power.gif'),
			'--after--',
			'/div'
		);
		$this->assertTags($result, $expected);

		$result = $this->Form->submit('Not.an.image', array('before' => $before, 'after' => $after));
		$expected = array(
			'div' => array('class' => 'submit'),
			'--before--',
			'input' => array('type' => 'submit', 'value' => 'Not.an.image'),
			'--after--',
			'/div'
		);
		$this->assertTags($result, $expected);
	}

/**
 * Submit buttons should be unlocked by default as there could be multiples, and only one will
 * be submitted at a time.
 *
 * @return void
 */
	public function testSubmitUnlockedByDefault() {
		$this->Form->request->params['_Token']['key'] = 'secured';
		$this->Form->submit('Go go');
		$this->Form->submit('Save', array('name' => 'save'));

		$result = $this->Form->unlockField();
		$this->assertEquals(array('save'), $result, 'Only submits with name attributes should be unlocked.');
	}

/**
 * Test submit image with timestamps.
 *
 * @return void
 */
	public function testSubmitImageTimestamp() {
		Configure::write('Asset.timestamp', 'force');

		$result = $this->Form->submit('cake.power.gif');
		$expected = array(
			'div' => array('class' => 'submit'),
			'input' => array('type' => 'image', 'src' => 'preg:/img\/cake\.power\.gif\?\d*/'),
			'/div'
		);
		$this->assertTags($result, $expected);
	}

/**
 * test the create() method
 *
 * @return void
 */
	public function testCreate() {
		$result = $this->Form->create('Contact');
		$encoding = strtolower(Configure::read('App.encoding'));
		$expected = array(
			'form' => array(
				'id' => 'ContactAddForm', 'method' => 'post', 'action' => '/contacts/add',
				'accept-charset' => $encoding
			),
			'div' => array('style' => 'preg:/display\s*\:\s*none;\s*/'),
			'input' => array('type' => 'hidden', 'name' => '_method', 'value' => 'POST'),
			'/div'
		);
		$this->assertTags($result, $expected);

		$result = $this->Form->create('Contact', array('type' => 'GET'));
		$expected = array('form' => array(
			'id' => 'ContactAddForm', 'method' => 'get', 'action' => '/contacts/add',
			'accept-charset' => $encoding
		));
		$this->assertTags($result, $expected);

		$result = $this->Form->create('Contact', array('type' => 'get'));
		$expected = array('form' => array(
			'id' => 'ContactAddForm', 'method' => 'get', 'action' => '/contacts/add',
			'accept-charset' => $encoding
		));
		$this->assertTags($result, $expected);

		$result = $this->Form->create('Contact', array('type' => 'put'));
		$expected = array(
			'form' => array(
				'id' => 'ContactAddForm', 'method' => 'post', 'action' => '/contacts/add',
				'accept-charset' => $encoding
			),
			'div' => array('style' => 'display:none;'),
			'input' => array('type' => 'hidden', 'name' => '_method', 'value' => 'PUT'),
			'/div'
		);
		$this->assertTags($result, $expected);

		$result = $this->Form->create('Contact', array('type' => 'file'));
		$expected = array(
			'form' => array(
				'id' => 'ContactAddForm', 'method' => 'post', 'action' => '/contacts/add',
				'accept-charset' => $encoding, 'enctype' => 'multipart/form-data'
			),
			'div' => array('style' => 'display:none;'),
			'input' => array('type' => 'hidden', 'name' => '_method', 'value' => 'POST'),
			'/div'
		);
		$this->assertTags($result, $expected);

		$this->Form->request->data['Contact']['id'] = 1;
		$this->Form->request->here = '/contacts/edit/1';
		$this->Form->request['action'] = 'edit';
		$result = $this->Form->create('Contact');
		$expected = array(
			'form' => array(
				'id' => 'ContactEditForm', 'method' => 'post', 'action' => '/contacts/edit/1',
				'accept-charset' => $encoding
			),
			'div' => array('style' => 'display:none;'),
			'input' => array('type' => 'hidden', 'name' => '_method', 'value' => 'PUT'),
			'/div'
		);
		$this->assertTags($result, $expected);

		$this->Form->request->data['Contact']['id'] = 1;
		$this->Form->request->here = '/contacts/edit/1';
		$this->Form->request['action'] = 'edit';
		$result = $this->Form->create('Contact', array('type' => 'file'));
		$expected = array(
			'form' => array(
				'id' => 'ContactEditForm', 'method' => 'post', 'action' => '/contacts/edit/1',
				'accept-charset' => $encoding, 'enctype' => 'multipart/form-data'
			),
			'div' => array('style' => 'display:none;'),
			'input' => array('type' => 'hidden', 'name' => '_method', 'value' => 'PUT'),
			'/div'
		);
		$this->assertTags($result, $expected);

		$this->Form->request->data['ContactNonStandardPk']['pk'] = 1;
		$result = $this->Form->create('ContactNonStandardPk', array('url' => array('action' => 'edit')));
		$expected = array(
			'form' => array(
				'id' => 'ContactNonStandardPkEditForm', 'method' => 'post',
				'action' => '/contact_non_standard_pks/edit/1','accept-charset' => $encoding
			),
			'div' => array('style' => 'display:none;'),
			'input' => array('type' => 'hidden', 'name' => '_method', 'value' => 'PUT'),
			'/div'
		);
		$this->assertTags($result, $expected);

		$result = $this->Form->create('Contact', array('id' => 'TestId'));
		$expected = array(
			'form' => array(
				'id' => 'TestId', 'method' => 'post', 'action' => '/contacts/edit/1',
				'accept-charset' => $encoding
			),
			'div' => array('style' => 'display:none;'),
			'input' => array('type' => 'hidden', 'name' => '_method', 'value' => 'PUT'),
			'/div'
		);
		$this->assertTags($result, $expected);

		$this->Form->request['action'] = 'add';
		$result = $this->Form->create('User', array('url' => array('action' => 'login')));
		$expected = array(
			'form' => array(
				'id' => 'UserAddForm', 'method' => 'post', 'action' => '/users/login',
				'accept-charset' => $encoding
			),
			'div' => array('style' => 'display:none;'),
			'input' => array('type' => 'hidden', 'name' => '_method', 'value' => 'POST'),
			'/div'
		);
		$this->assertTags($result, $expected);

		$result = $this->Form->create('User', array('action' => 'login'));
		$expected = array(
			'form' => array(
				'id' => 'UserLoginForm', 'method' => 'post', 'action' => '/users/login',
				'accept-charset' => $encoding
			),
			'div' => array('style' => 'display:none;'),
			'input' => array('type' => 'hidden', 'name' => '_method', 'value' => 'POST'),
			'/div'
		);
		$this->assertTags($result, $expected);

		$result = $this->Form->create('User', array('url' => '/users/login'));
		$expected = array(
			'form' => array('method' => 'post', 'action' => '/users/login', 'accept-charset' => $encoding, 'id' => 'UserAddForm'),
			'div' => array('style' => 'display:none;'),
			'input' => array('type' => 'hidden', 'name' => '_method', 'value' => 'POST'),
			'/div'
		);
		$this->assertTags($result, $expected);

		$this->Form->request['controller'] = 'pages';
		$result = $this->Form->create('User', array('action' => 'signup'));
		$expected = array(
			'form' => array(
				'id' => 'UserSignupForm', 'method' => 'post', 'action' => '/users/signup',
				'accept-charset' => $encoding
			),
			'div' => array('style' => 'display:none;'),
			'input' => array('type' => 'hidden', 'name' => '_method', 'value' => 'POST'),
			'/div'
		);
		$this->assertTags($result, $expected);

		$this->Form->request->data = array();
		$this->Form->request['controller'] = 'contacts';
		$this->Form->request['models'] = array('Contact' => array('plugin' => null, 'className' => 'Contact'));
		$result = $this->Form->create(array('url' => array('action' => 'index', 'param')));
		$expected = array(
			'form' => array(
				'id' => 'ContactAddForm', 'method' => 'post', 'action' => '/contacts/index/param',
				'accept-charset' => 'utf-8'
			),
			'div' => array('style' => 'display:none;'),
			'input' => array('type' => 'hidden', 'name' => '_method', 'value' => 'POST'),
			'/div'
		);
		$this->assertTags($result, $expected);
	}

/**
 * Test the onsubmit option for create()
 *
 * @return void
 */
	public function testCreateOnSubmit() {
		$this->Form->request->data = array();
		$this->Form->request['controller'] = 'contacts';
		$this->Form->request['models'] = array('Contact' => array('plugin' => null, 'className' => 'Contact'));
		$result = $this->Form->create(array('url' => array('action' => 'index', 'param'), 'default' => false));
		$expected = array(
			'form' => array(
				'id' => 'ContactAddForm', 'method' => 'post', 'onsubmit' => 'event.returnValue = false; return false;', 'action' => '/contacts/index/param',
				'accept-charset' => 'utf-8'
			),
			'div' => array('style' => 'display:none;'),
			'input' => array('type' => 'hidden', 'name' => '_method', 'value' => 'POST'),
			'/div'
		);
		$this->assertTags($result, $expected);

		$this->Form->request->data = array();
		$this->Form->request['controller'] = 'contacts';
		$this->Form->request['models'] = array('Contact' => array('plugin' => null, 'className' => 'Contact'));
		$result = $this->Form->create(array(
			'url' => array('action' => 'index', 'param'),
			'default' => false,
			'onsubmit' => 'someFunction();'
		));

		$expected = array(
			'form' => array(
				'id' => 'ContactAddForm', 'method' => 'post',
				'onsubmit' => 'someFunction();event.returnValue = false; return false;',
				'action' => '/contacts/index/param',
				'accept-charset' => 'utf-8'
			),
			'div' => array('style' => 'display:none;'),
			'input' => array('type' => 'hidden', 'name' => '_method', 'value' => 'POST'),
			'/div'
		);
		$this->assertTags($result, $expected);
	}

/**
 * test create() with automatic url generation
 *
 * @return void
 */
	public function testCreateAutoUrl() {
		Router::setRequestInfo(array(array(), array('base' => '/base_url')));
		$this->Form->request->here = '/base_url/contacts/add/Contact:1';
		$this->Form->request->base = '/base_url';
		$result = $this->Form->create('Contact');
		$expected = array(
			'form' => array(
				'id' => 'ContactAddForm', 'method' => 'post', 'action' => '/base_url/contacts/add/Contact:1',
				'accept-charset' => 'utf-8'
			),
			'div' => array('style' => 'display:none;'),
			'input' => array('type' => 'hidden', 'name' => '_method', 'value' => 'POST'),
			'/div'
		);
		$this->assertTags($result, $expected);

		$this->Form->request['action'] = 'delete';
		$this->Form->request->here = '/base_url/contacts/delete/10/User:42';
		$this->Form->request->base = '/base_url';
		$result = $this->Form->create('Contact');
		$expected = array(
			'form' => array(
				'id' => 'ContactDeleteForm', 'method' => 'post', 'action' => '/base_url/contacts/delete/10/User:42',
				'accept-charset' => 'utf-8'
			),
			'div' => array('style' => 'display:none;'),
			'input' => array('type' => 'hidden', 'name' => '_method', 'value' => 'POST'),
			'/div'
		);
		$this->assertTags($result, $expected);
	}

/**
 * test create() with a custom route
 *
 * @return void
 */
	public function testCreateCustomRoute() {
		Router::connect('/login', array('controller' => 'users', 'action' => 'login'));
		$encoding = strtolower(Configure::read('App.encoding'));

		$result = $this->Form->create('User', array('action' => 'login'));
		$expected = array(
			'form' => array(
				'id' => 'UserLoginForm', 'method' => 'post', 'action' => '/login',
				'accept-charset' => $encoding
			),
			'div' => array('style' => 'display:none;'),
			'input' => array('type' => 'hidden', 'name' => '_method', 'value' => 'POST'),
			'/div'
		);
		$this->assertTags($result, $expected);
	}

/**
 * test that inputDefaults are stored and used.
 *
 * @return void
 */
	public function testCreateWithInputDefaults() {
		$this->Form->create('User', array(
			'inputDefaults' => array(
				'div' => false,
				'label' => false,
				'error' => array('attributes' => array('wrap' => 'small', 'class' => 'error'))
			)
		));
		$result = $this->Form->input('username');
		$expected = array(
			'input' => array('type' => 'text', 'name' => 'data[User][username]', 'id' => 'UserUsername')
		);
		$this->assertTags($result, $expected);

		$result = $this->Form->input('username', array('div' => true, 'label' => 'username'));
		$expected = array(
			'div' => array('class' => 'input text'),
			'label' => array('for' => 'UserUsername'), 'username', '/label',
			'input' => array('type' => 'text', 'name' => 'data[User][username]', 'id' => 'UserUsername'),
			'/div'
		);
		$this->assertTags($result, $expected);

		$User = ClassRegistry::getObject('User');
		$User->validationErrors['username'] = array('empty');
		$result = $this->Form->input('username', array('div' => true, 'label' => 'username', 'error' => array('empty' => __('Required'))));
		$expected = array(
			'div' => array('class' => 'input text error'),
			'label' => array('for' => 'UserUsername'), 'username', '/label',
			'input' => array('class' => 'form-error', 'type' => 'text', 'name' => 'data[User][username]', 'id' => 'UserUsername'),
			'small' => array('class' => 'error'), 'Required', '/small',
			'/div'
		);
		$this->assertTags($result, $expected);
	}

/**
 * test automatic accept-charset overriding
 *
 * @return void
 */
	public function testCreateWithAcceptCharset() {
		$result = $this->Form->create('UserForm', array(
				'type' => 'post', 'action' => 'login','encoding' => 'iso-8859-1'
			)
		);
		$expected = array(
			'form' => array(
				'method' => 'post', 'action' => '/user_forms/login', 'id' => 'UserFormLoginForm',
				'accept-charset' => 'iso-8859-1'
			),
			'div' => array('style' => 'display:none;'),
			'input' => array('type' => 'hidden', 'name' => '_method', 'value' => 'POST'),
			'/div'
		);
		$this->assertTags($result, $expected);
	}

/**
 * Test base form url when url param is passed with multiple parameters (&)
 *
 */
	public function testCreateQuerystringrequest() {
		$encoding = strtolower(Configure::read('App.encoding'));
		$result = $this->Form->create('Contact', array(
			'type' => 'post',
			'escape' => false,
			'url' => array(
				'controller' => 'controller',
				'action' => 'action',
				'?' => array('param1' => 'value1', 'param2' => 'value2')
			)
		));
		$expected = array(
			'form' => array(
				'id' => 'ContactAddForm',
				'method' => 'post',
				'action' => '/controller/action?param1=value1&amp;param2=value2',
				'accept-charset' => $encoding
			),
			'div' => array('style' => 'display:none;'),
			'input' => array('type' => 'hidden', 'name' => '_method', 'value' => 'POST'),
			'/div'
		);
		$this->assertTags($result, $expected);

		$result = $this->Form->create('Contact', array(
			'type' => 'post',
			'url' => array(
				'controller' => 'controller',
				'action' => 'action',
				'?' => array('param1' => 'value1', 'param2' => 'value2')
			)
		));
		$expected = array(
			'form' => array(
				'id' => 'ContactAddForm',
				'method' => 'post',
				'action' => '/controller/action?param1=value1&amp;param2=value2',
				'accept-charset' => $encoding
			),
			'div' => array('style' => 'display:none;'),
			'input' => array('type' => 'hidden', 'name' => '_method', 'value' => 'POST'),
			'/div'
		);
		$this->assertTags($result, $expected);
	}

/**
 * test that create() doesn't cause errors by multiple id's being in the primary key
 * as could happen with multiple select or checkboxes.
 *
 * @return void
 */
	public function testCreateWithMultipleIdInData() {
		$encoding = strtolower(Configure::read('App.encoding'));

		$this->Form->request->data['Contact']['id'] = array(1, 2);
		$result = $this->Form->create('Contact');
		$expected = array(
			'form' => array(
				'id' => 'ContactAddForm',
				'method' => 'post',
				'action' => '/contacts/add',
				'accept-charset' => $encoding
			),
			'div' => array('style' => 'display:none;'),
			'input' => array('type' => 'hidden', 'name' => '_method', 'value' => 'POST'),
			'/div'
		);
		$this->assertTags($result, $expected);
	}

/**
 * test that create() doesn't add in extra passed params.
 *
 * @return void
 */
	public function testCreatePassedArgs() {
		$encoding = strtolower(Configure::read('App.encoding'));
		$this->Form->request->data['Contact']['id'] = 1;
		$result = $this->Form->create('Contact', array(
			'type' => 'post',
			'escape' => false,
			'url' => array(
				'action' => 'edit',
				'myparam'
			)
		));
		$expected = array(
			'form' => array(
				'id' => 'ContactAddForm',
				'method' => 'post',
				'action' => '/contacts/edit/myparam',
				'accept-charset' => $encoding
			),
			'div' => array('style' => 'display:none;'),
			'input' => array('type' => 'hidden', 'name' => '_method', 'value' => 'POST'),
			'/div'
		);
		$this->assertTags($result, $expected);
	}

/**
 * test creating a get form, and get form inputs.
 *
 * @return void
 */
	public function testGetFormCreate() {
		$encoding = strtolower(Configure::read('App.encoding'));
		$result = $this->Form->create('Contact', array('type' => 'get'));
		$this->assertTags($result, array('form' => array(
			'id' => 'ContactAddForm', 'method' => 'get', 'action' => '/contacts/add',
			'accept-charset' => $encoding
		)));

		$result = $this->Form->text('Contact.name');
		$this->assertTags($result, array('input' => array(
			'name' => 'name', 'type' => 'text', 'id' => 'ContactName',
		)));

		$result = $this->Form->password('password');
		$this->assertTags($result, array('input' => array(
			'name' => 'password', 'type' => 'password', 'id' => 'ContactPassword'
		)));
		$this->assertNotRegExp('/<input[^<>]+[^id|name|type|value]=[^<>]*>$/', $result);

		$result = $this->Form->text('user_form');
		$this->assertTags($result, array('input' => array(
			'name' => 'user_form', 'type' => 'text', 'id' => 'ContactUserForm'
		)));
	}

/**
 * test get form, and inputs when the model param is false
 *
 * @return void
 */
	public function testGetFormWithFalseModel() {
		$encoding = strtolower(Configure::read('App.encoding'));
		$this->Form->request['controller'] = 'contact_test';
		$result = $this->Form->create(false, array('type' => 'get', 'url' => array('controller' => 'contact_test')));

		$expected = array('form' => array(
			'id' => 'addForm', 'method' => 'get', 'action' => '/contact_test/add',
			'accept-charset' => $encoding
		));
		$this->assertTags($result, $expected);

		$result = $this->Form->text('reason');
		$expected = array(
			'input' => array('type' => 'text', 'name' => 'reason', 'id' => 'reason')
		);
		$this->assertTags($result, $expected);
	}

/**
 * test that datetime() works with GET style forms.
 *
 * @return void
 */
	public function testDateTimeWithGetForms() {
		extract($this->dateRegex);
		$this->Form->create('Contact', array('type' => 'get'));
		$result = $this->Form->datetime('created');

		$this->assertRegExp('/name="created\[year\]"/', $result, 'year name attribute is wrong.');
		$this->assertRegExp('/name="created\[month\]"/', $result, 'month name attribute is wrong.');
		$this->assertRegExp('/name="created\[day\]"/', $result, 'day name attribute is wrong.');
		$this->assertRegExp('/name="created\[hour\]"/', $result, 'hour name attribute is wrong.');
		$this->assertRegExp('/name="created\[min\]"/', $result, 'min name attribute is wrong.');
		$this->assertRegExp('/name="created\[meridian\]"/', $result, 'meridian name attribute is wrong.');
	}

/**
 * testEditFormWithData method
 *
 * test auto populating form elements from submitted data.
 *
 * @return void
 */
	public function testEditFormWithData() {
		$this->Form->request->data = array('Person' => array(
			'id' => 1,
			'first_name' => 'Nate',
			'last_name' => 'Abele',
			'email' => 'nate@example.com'
		));
		$this->Form->request->addParams(array(
			'models' => array('Person'),
			'controller' => 'people',
			'action' => 'add'
		));
		$options = array(1 => 'Nate', 2 => 'Garrett', 3 => 'Larry');

		$this->Form->create();
		$result = $this->Form->select('People.People', $options, array('multiple' => true));
		$expected = array(
			'input' => array('type' => 'hidden', 'name' => 'data[People][People]', 'value' => '', 'id' => 'PeoplePeople_'),
			'select' => array(
				'name' => 'data[People][People][]', 'multiple' => 'multiple', 'id' => 'PeoplePeople'
			),
			array('option' => array('value' => 1)), 'Nate', '/option',
			array('option' => array('value' => 2)), 'Garrett', '/option',
			array('option' => array('value' => 3)), 'Larry', '/option',
			'/select'
		);
		$this->assertTags($result, $expected);
	}

/**
 * Test that required fields are created for various types of validation.
 *
 * @return void
 */
	public function testFormInputRequiredDetection() {
		$this->Form->create('Contact');

		$result = $this->Form->input('Contact.non_existing');
		$expected = array(
			'div' => array('class' => 'input text required'),
			'label' => array('for' => 'ContactNonExisting'),
			'Non Existing',
			'/label',
			'input' => array(
				'type' => 'text', 'name' => 'data[Contact][non_existing]',
				'id' => 'ContactNonExisting'
			),
			'/div'
		);
		$this->assertTags($result, $expected);

		$result = $this->Form->input('Contact.imrequired');
		$expected = array(
			'div' => array('class' => 'input text required'),
			'label' => array('for' => 'ContactImrequired'),
			'Imrequired',
			'/label',
			'input' => array(
				'type' => 'text', 'name' => 'data[Contact][imrequired]',
				'id' => 'ContactImrequired'
			),
			'/div'
		);
		$this->assertTags($result, $expected);

		$result = $this->Form->input('Contact.imalsorequired');
		$expected = array(
			'div' => array('class' => 'input text required'),
			'label' => array('for' => 'ContactImalsorequired'),
			'Imalsorequired',
			'/label',
			'input' => array(
				'type' => 'text', 'name' => 'data[Contact][imalsorequired]',
				'id' => 'ContactImalsorequired'
			),
			'/div'
		);
		$this->assertTags($result, $expected);

		$result = $this->Form->input('Contact.imrequiredtoo');
		$expected = array(
			'div' => array('class' => 'input text required'),
			'label' => array('for' => 'ContactImrequiredtoo'),
			'Imrequiredtoo',
			'/label',
			'input' => array(
				'type' => 'text', 'name' => 'data[Contact][imrequiredtoo]',
				'id' => 'ContactImrequiredtoo'
			),
			'/div'
		);
		$this->assertTags($result, $expected);

		$result = $this->Form->input('Contact.required_one');
		$expected = array(
			'div' => array('class' => 'input text required'),
			'label' => array('for' => 'ContactRequiredOne'),
			'Required One',
			'/label',
			'input' => array(
				'type' => 'text', 'name' => 'data[Contact][required_one]',
				'id' => 'ContactRequiredOne'
			),
			'/div'
		);
		$this->assertTags($result, $expected);

		$result = $this->Form->input('Contact.string_required');
		$expected = array(
			'div' => array('class' => 'input text required'),
			'label' => array('for' => 'ContactStringRequired'),
			'String Required',
			'/label',
			'input' => array(
				'type' => 'text', 'name' => 'data[Contact][string_required]',
				'id' => 'ContactStringRequired'
			),
			'/div'
		);
		$this->assertTags($result, $expected);

		$result = $this->Form->input('Contact.imnotrequired');
		$expected = array(
			'div' => array('class' => 'input text'),
			'label' => array('for' => 'ContactImnotrequired'),
			'Imnotrequired',
			'/label',
			'input' => array(
				'type' => 'text', 'name' => 'data[Contact][imnotrequired]',
				'id' => 'ContactImnotrequired'
			),
			'/div'
		);
		$this->assertTags($result, $expected);

		$result = $this->Form->input('Contact.imalsonotrequired');
		$expected = array(
			'div' => array('class' => 'input text'),
			'label' => array('for' => 'ContactImalsonotrequired'),
			'Imalsonotrequired',
			'/label',
			'input' => array(
				'type' => 'text', 'name' => 'data[Contact][imalsonotrequired]',
				'id' => 'ContactImalsonotrequired'
			),
			'/div'
		);
		$this->assertTags($result, $expected);

		$result = $this->Form->input('Contact.imnotrequiredeither');
		$expected = array(
			'div' => array('class' => 'input text'),
			'label' => array('for' => 'ContactImnotrequiredeither'),
			'Imnotrequiredeither',
			'/label',
			'input' => array(
				'type' => 'text', 'name' => 'data[Contact][imnotrequiredeither]',
				'id' => 'ContactImnotrequiredeither'
			),
			'/div'
		);
		$this->assertTags($result, $expected);
	}

/**
 * testFormMagicInput method
 *
 * @return void
 */
	public function testFormMagicInput() {
		$encoding = strtolower(Configure::read('App.encoding'));
		$result = $this->Form->create('Contact');
		$expected = array(
			'form' => array(
				'id' => 'ContactAddForm', 'method' => 'post', 'action' => '/contacts/add',
				'accept-charset' => $encoding
			),
			'div' => array('style' => 'display:none;'),
			'input' => array('type' => 'hidden', 'name' => '_method', 'value' => 'POST'),
			'/div'
		);
		$this->assertTags($result, $expected);

		$result = $this->Form->input('name');
		$expected = array(
			'div' => array('class' => 'input text'),
			'label' => array('for' => 'ContactName'),
			'Name',
			'/label',
			'input' => array(
				'type' => 'text', 'name' => 'data[Contact][name]',
				'id' => 'ContactName', 'maxlength' => '255'
			),
			'/div'
		);
		$this->assertTags($result, $expected);

		$result = $this->Form->input('non_existing_field_in_contact_model');
		$expected = array(
			'div' => array('class' => 'input text'),
			'label' => array('for' => 'ContactNonExistingFieldInContactModel'),
			'Non Existing Field In Contact Model',
			'/label',
			'input' => array(
				'type' => 'text', 'name' => 'data[Contact][non_existing_field_in_contact_model]',
				'id' => 'ContactNonExistingFieldInContactModel'
			),
			'/div'
		);
		$this->assertTags($result, $expected);

		$result = $this->Form->input('Address.street');
		$expected = array(
			'div' => array('class' => 'input text'),
			'label' => array('for' => 'AddressStreet'),
			'Street',
			'/label',
			'input' => array(
				'type' => 'text', 'name' => 'data[Address][street]',
				'id' => 'AddressStreet'
			),
			'/div'
		);
		$this->assertTags($result, $expected);

		$result = $this->Form->input('Address.non_existing_field_in_model');
		$expected = array(
			'div' => array('class' => 'input text'),
			'label' => array('for' => 'AddressNonExistingFieldInModel'),
			'Non Existing Field In Model',
			'/label',
			'input' => array(
				'type' => 'text', 'name' => 'data[Address][non_existing_field_in_model]',
				'id' => 'AddressNonExistingFieldInModel'
			),
			'/div'
		);
		$this->assertTags($result, $expected);

		$result = $this->Form->input('name', array('div' => false));
		$expected = array(
			'label' => array('for' => 'ContactName'),
			'Name',
			'/label',
			'input' => array(
				'type' => 'text', 'name' => 'data[Contact][name]',
				'id' => 'ContactName', 'maxlength' => '255'
			)
		);
		$this->assertTags($result, $expected);

		extract($this->dateRegex);
		$now = strtotime('now');

		$result = $this->Form->input('Contact.published', array('div' => false));
		$expected = array(
			'label' => array('for' => 'ContactPublishedMonth'),
			'Published',
			'/label',
			array('select' => array(
				'name' => 'data[Contact][published][month]', 'id' => 'ContactPublishedMonth'
			)),
			$monthsRegex,
			array('option' => array('value' => date('m', $now), 'selected' => 'selected')),
			date('F', $now),
			'/option',
			'*/select',
			'-',
			array('select' => array(
				'name' => 'data[Contact][published][day]', 'id' => 'ContactPublishedDay'
			)),
			$daysRegex,
			array('option' => array('value' => date('d', $now), 'selected' => 'selected')),
			date('j', $now),
			'/option',
			'*/select',
			'-',
			array('select' => array(
				'name' => 'data[Contact][published][year]', 'id' => 'ContactPublishedYear'
			)),
			$yearsRegex,
			array('option' => array('value' => date('Y', $now), 'selected' => 'selected')),
			date('Y', $now),
			'*/select'
		);
		$this->assertTags($result, $expected);

		$result = $this->Form->input('Contact.updated', array('div' => false));
		$expected = array(
			'label' => array('for' => 'ContactUpdatedMonth'),
			'Updated',
			'/label',
			array('select' => array(
				'name' => 'data[Contact][updated][month]', 'id' => 'ContactUpdatedMonth'
			)),
			$monthsRegex,
			array('option' => array('value' => date('m', $now), 'selected' => 'selected')),
			date('F', $now),
			'/option',
			'*/select',
			'-',
			array('select' => array(
				'name' => 'data[Contact][updated][day]', 'id' => 'ContactUpdatedDay'
			)),
			$daysRegex,
			array('option' => array('value' => date('d', $now), 'selected' => 'selected')),
			date('j', $now),
			'/option',
			'*/select',
			'-',
			array('select' => array(
				'name' => 'data[Contact][updated][year]', 'id' => 'ContactUpdatedYear'
			)),
			$yearsRegex,
			array('option' => array('value' => date('Y', $now), 'selected' => 'selected')),
			date('Y', $now),
			'*/select'
		);
		$this->assertTags($result, $expected);

		$result = $this->Form->input('UserForm.stuff');
		$expected = array(
			'div' => array('class' => 'input text'),
			'label' => array('for' => 'UserFormStuff'),
			'Stuff',
			'/label',
			'input' => array(
				'type' => 'text', 'name' => 'data[UserForm][stuff]',
				'id' => 'UserFormStuff', 'maxlength' => 10
			),
			'/div'
		);
		$this->assertTags($result, $expected);
	}

/**
 * testForMagicInputNonExistingNorValidated method
 *
 * @return void
 */
	public function testForMagicInputNonExistingNorValidated() {
		$encoding = strtolower(Configure::read('App.encoding'));
		$result = $this->Form->create('Contact');
		$expected = array(
			'form' => array(
				'id' => 'ContactAddForm', 'method' => 'post', 'action' => '/contacts/add',
				'accept-charset' => $encoding
			),
			'div' => array('style' => 'display:none;'),
			'input' => array('type' => 'hidden', 'name' => '_method', 'value' => 'POST'),
			'/div'
		);
		$this->assertTags($result, $expected);

		$result = $this->Form->input('Contact.non_existing_nor_validated', array('div' => false));
		$expected = array(
			'label' => array('for' => 'ContactNonExistingNorValidated'),
			'Non Existing Nor Validated',
			'/label',
			'input' => array(
				'type' => 'text', 'name' => 'data[Contact][non_existing_nor_validated]',
				'id' => 'ContactNonExistingNorValidated'
			)
		);
		$this->assertTags($result, $expected);

		$result = $this->Form->input('Contact.non_existing_nor_validated', array(
			'div' => false, 'value' => 'my value'
		));
		$expected = array(
			'label' => array('for' => 'ContactNonExistingNorValidated'),
			'Non Existing Nor Validated',
			'/label',
			'input' => array(
				'type' => 'text', 'name' => 'data[Contact][non_existing_nor_validated]',
				'value' => 'my value', 'id' => 'ContactNonExistingNorValidated'
			)
		);
		$this->assertTags($result, $expected);

		$this->Form->request->data = array(
			'Contact' => array('non_existing_nor_validated' => 'CakePHP magic'
		));
		$result = $this->Form->input('Contact.non_existing_nor_validated', array('div' => false));
		$expected = array(
			'label' => array('for' => 'ContactNonExistingNorValidated'),
			'Non Existing Nor Validated',
			'/label',
			'input' => array(
				'type' => 'text', 'name' => 'data[Contact][non_existing_nor_validated]',
				'value' => 'CakePHP magic', 'id' => 'ContactNonExistingNorValidated'
			)
		);
		$this->assertTags($result, $expected);
	}

/**
 * testFormMagicInputLabel method
 *
 * @return void
 */
	public function testFormMagicInputLabel() {
		$encoding = strtolower(Configure::read('App.encoding'));
		$result = $this->Form->create('Contact');
		$expected = array(
			'form' => array(
				'id' => 'ContactAddForm', 'method' => 'post', 'action' => '/contacts/add',
				'accept-charset' => $encoding
			),
			'div' => array('style' => 'display:none;'),
			'input' => array('type' => 'hidden', 'name' => '_method', 'value' => 'POST'),
			'/div'
		);
		$this->assertTags($result, $expected);

		$result = $this->Form->input('Contact.name', array('div' => false, 'label' => false));
		$this->assertTags($result, array('input' => array(
			'name' => 'data[Contact][name]', 'type' => 'text',
			'id' => 'ContactName', 'maxlength' => '255')
		));

		$result = $this->Form->input('Contact.name', array('div' => false, 'label' => 'My label'));
		$expected = array(
			'label' => array('for' => 'ContactName'),
			'My label',
			'/label',
			'input' => array(
				'type' => 'text', 'name' => 'data[Contact][name]',
				'id' => 'ContactName', 'maxlength' => '255'
			)
		);
		$this->assertTags($result, $expected);

		$result = $this->Form->input('Contact.name', array(
			'div' => false, 'label' => array('class' => 'mandatory')
		));
		$expected = array(
			'label' => array('for' => 'ContactName', 'class' => 'mandatory'),
			'Name',
			'/label',
			'input' => array(
				'type' => 'text', 'name' => 'data[Contact][name]',
				'id' => 'ContactName', 'maxlength' => '255'
			)
		);
		$this->assertTags($result, $expected);

		$result = $this->Form->input('Contact.name', array(
			'div' => false, 'label' => array('class' => 'mandatory', 'text' => 'My label')
		));
		$expected = array(
			'label' => array('for' => 'ContactName', 'class' => 'mandatory'),
			'My label',
			'/label',
			'input' => array(
				'type' => 'text', 'name' => 'data[Contact][name]',
				'id' => 'ContactName', 'maxlength' => '255'
			)
		);
		$this->assertTags($result, $expected);

		$result = $this->Form->input('Contact.name', array(
			'div' => false, 'id' => 'my_id', 'label' => array('for' => 'my_id')
		));
		$expected = array(
			'label' => array('for' => 'my_id'),
			'Name',
			'/label',
			'input' => array(
				'type' => 'text', 'name' => 'data[Contact][name]',
				'id' => 'my_id', 'maxlength' => '255'
			)
		);
		$this->assertTags($result, $expected);

		$result = $this->Form->input('1.id');
		$this->assertTags($result, array('input' => array(
			'type' => 'hidden', 'name' => 'data[Contact][1][id]',
			'id' => 'Contact1Id'
		)));

		$result = $this->Form->input("1.name");
		$expected = array(
			'div' => array('class' => 'input text'),
			'label' => array('for' => 'Contact1Name'),
			'Name',
			'/label',
			'input' => array(
				'type' => 'text', 'name' => 'data[Contact][1][name]',
				'id' => 'Contact1Name', 'maxlength' => '255'
			),
			'/div'
		);
		$this->assertTags($result, $expected);

		$result = $this->Form->input('Contact.1.id');
		$this->assertTags($result, array(
			'input' => array(
				'type' => 'hidden', 'name' => 'data[Contact][1][id]',
				'id' => 'Contact1Id'
			)
		));

		$result = $this->Form->input("Model.1.name");
		$expected = array(
			'div' => array('class' => 'input text'),
			'label' => array('for' => 'Model1Name'),
			'Name',
			'/label',
			'input' => array(
				'type' => 'text', 'name' => 'data[Model][1][name]',
				'id' => 'Model1Name'
			),
			'/div'
		);
		$this->assertTags($result, $expected);
	}

/**
 * testFormEnd method
 *
 * @return void
 */
	public function testFormEnd() {
		$this->assertEquals('</form>', $this->Form->end());

		$result = $this->Form->end('');
		$expected = array(
			'div' => array('class' => 'submit'),
			'input' => array('type' => 'submit', 'value' => ''),
			'/div',
			'/form'
		);
		$this->assertTags($result, $expected);

		$result = $this->Form->end(array('label' => ''));
		$expected = array(
			'div' => array('class' => 'submit'),
			'input' => array('type' => 'submit', 'value' => ''),
			'/div',
			'/form'
		);
		$this->assertTags($result, $expected);

		$result = $this->Form->end('save');
		$expected = array(
			'div' => array('class' => 'submit'),
			'input' => array('type' => 'submit', 'value' => 'save'),
			'/div',
			'/form'
		);
		$this->assertTags($result, $expected);

		$result = $this->Form->end(array('label' => 'save'));
		$expected = array(
			'div' => array('class' => 'submit'),
			'input' => array('type' => 'submit', 'value' => 'save'),
			'/div',
			'/form'
		);
		$this->assertTags($result, $expected);

		$result = $this->Form->end(array('label' => 'save', 'name' => 'Whatever'));
		$expected = array(
			'div' => array('class' => 'submit'),
			'input' => array('type' => 'submit', 'value' => 'save', 'name' => 'Whatever'),
			'/div',
			'/form'
		);
		$this->assertTags($result, $expected);

		$result = $this->Form->end(array('name' => 'Whatever'));
		$expected = array(
			'div' => array('class' => 'submit'),
			'input' => array('type' => 'submit', 'value' => 'Submit', 'name' => 'Whatever'),
			'/div',
			'/form'
		);
		$this->assertTags($result, $expected);

		$result = $this->Form->end(array('label' => 'save', 'name' => 'Whatever', 'div' => 'good'));
		$expected = array(
			'div' => array('class' => 'good'),
			'input' => array('type' => 'submit', 'value' => 'save', 'name' => 'Whatever'),
			'/div',
			'/form'
		);
		$this->assertTags($result, $expected);

		$result = $this->Form->end(array(
			'label' => 'save', 'name' => 'Whatever', 'div' => array('class' => 'good')
		));
		$expected = array(
			'div' => array('class' => 'good'),
			'input' => array('type' => 'submit', 'value' => 'save', 'name' => 'Whatever'),
			'/div',
			'/form'
		);
		$this->assertTags($result, $expected);
	}

/**
 * testMultipleFormWithIdFields method
 *
 * @return void
 */
	public function testMultipleFormWithIdFields() {
		$this->Form->create('UserForm');

		$result = $this->Form->input('id');
		$this->assertTags($result, array('input' => array(
			'type' => 'hidden', 'name' => 'data[UserForm][id]', 'id' => 'UserFormId'
		)));

		$result = $this->Form->input('ValidateItem.id');
		$this->assertTags($result, array('input' => array(
			'type' => 'hidden', 'name' => 'data[ValidateItem][id]',
			'id' => 'ValidateItemId'
		)));

		$result = $this->Form->input('ValidateUser.id');
		$this->assertTags($result, array('input' => array(
			'type' => 'hidden', 'name' => 'data[ValidateUser][id]',
			'id' => 'ValidateUserId'
		)));
	}

/**
 * testDbLessModel method
 *
 * @return void
 */
	public function testDbLessModel() {
		$this->Form->create('TestMail');

		$result = $this->Form->input('name');
		$expected = array(
			'div' => array('class' => 'input text'),
			'label' => array('for' => 'TestMailName'),
			'Name',
			'/label',
			'input' => array(
				'name' => 'data[TestMail][name]', 'type' => 'text',
				'id' => 'TestMailName'
			),
			'/div'
		);
		$this->assertTags($result, $expected);

		ClassRegistry::init('TestMail');
		$this->Form->create('TestMail');
		$result = $this->Form->input('name');
		$expected = array(
			'div' => array('class' => 'input text'),
			'label' => array('for' => 'TestMailName'),
			'Name',
			'/label',
			'input' => array(
				'name' => 'data[TestMail][name]', 'type' => 'text',
				'id' => 'TestMailName'
			),
			'/div'
		);
		$this->assertTags($result, $expected);
	}

/**
 * testBrokenness method
 *
 * @return void
 */
	public function testBrokenness() {
		/*
		 * #4 This test has two parents and four children. By default (as of r7117) both
		 * parents are show but the first parent is missing a child. This is the inconsistency
		 * in the default behaviour - one parent has all children, the other does not - dependent
		 * on the data values.
		 */
		$result = $this->Form->select('Model.field', array(
			'Fred' => array(
				'freds_son_1' => 'Fred',
				'freds_son_2' => 'Freddie'
			),
			'Bert' => array(
				'berts_son_1' => 'Albert',
				'berts_son_2' => 'Bertie')
			),
			array('showParents' => true, 'empty' => false)
		);

		$expected = array(
			'select' => array('name' => 'data[Model][field]', 'id' => 'ModelField'),
				array('optgroup' => array('label' => 'Fred')),
					array('option' => array('value' => 'freds_son_1')),
						'Fred',
					'/option',
					array('option' => array('value' => 'freds_son_2')),
						'Freddie',
					'/option',
				'/optgroup',
				array('optgroup' => array('label' => 'Bert')),
					array('option' => array('value' => 'berts_son_1')),
						'Albert',
					'/option',
					array('option' => array('value' => 'berts_son_2')),
						'Bertie',
					'/option',
				'/optgroup',
			'/select'
		);
		$this->assertTags($result, $expected);

		/*
		 * #2 This is structurally identical to the test above (#1) - only the parent name has
		 * changed, so we should expect the same select list data, just with a different name
		 * for the parent.  As of #7117, this test fails because option 3 => 'Three' disappears.
		 * This is where data corruption can occur, because when a select value is missing from
		 * a list a form will substitute the first value in the list - without the user knowing.
		 * If the optgroup name 'Parent' (above) is updated to 'Three' (below), this should not
		 * affect the availability of 3 => 'Three' as a valid option.
		 */
		$options = array(1 => 'One', 2 => 'Two', 'Three' => array(
			3 => 'Three', 4 => 'Four', 5 => 'Five'
		));
		$result = $this->Form->select(
			'Model.field', $options, array('showParents' => true, 'empty' => false)
		);

		$expected = array(
			'select' => array('name' => 'data[Model][field]', 'id' => 'ModelField'),
				array('option' => array('value' => 1)),
					'One',
				'/option',
				array('option' => array('value' => 2)),
					'Two',
				'/option',
				array('optgroup' => array('label' => 'Three')),
					array('option' => array('value' => 3)),
						'Three',
					'/option',
					array('option' => array('value' => 4)),
						'Four',
					'/option',
					array('option' => array('value' => 5)),
						'Five',
					'/option',
				'/optgroup',
			'/select'
		);
		$this->assertTags($result, $expected);
	}

/**
 * Test the generation of fields for a multi record form.
 *
 * @return void
 */
	public function testMultiRecordForm() {
		$this->Form->create('ValidateProfile');
		$this->Form->request->data['ValidateProfile'][1]['ValidateItem'][2]['name'] = 'Value';
		$result = $this->Form->input('ValidateProfile.1.ValidateItem.2.name');
		$expected = array(
			'div' => array('class' => 'input textarea'),
				'label' => array('for' => 'ValidateProfile1ValidateItem2Name'),
					'Name',
				'/label',
				'textarea' => array(
					'id' => 'ValidateProfile1ValidateItem2Name',
					'name' => 'data[ValidateProfile][1][ValidateItem][2][name]',
					'cols' => 30,
					'rows' => 6
				),
				'Value',
				'/textarea',
			'/div'
		);
		$this->assertTags($result, $expected);

		$result = $this->Form->input('ValidateProfile.1.ValidateItem.2.created',array('empty' => true));
		$expected = array(
			'div' => array('class' => 'input date'),
			'label' => array('for' => 'ValidateProfile1ValidateItem2CreatedMonth'),
			'Created',
			'/label',
			array('select' => array(
				'name' => 'data[ValidateProfile][1][ValidateItem][2][created][month]',
				'id' => 'ValidateProfile1ValidateItem2CreatedMonth'
				)
			),
			array('option' => array('value' => '')), '/option',
			$this->dateRegex['monthsRegex'],
			'/select', '-',
			array('select' => array(
				'name' => 'data[ValidateProfile][1][ValidateItem][2][created][day]',
				'id' => 'ValidateProfile1ValidateItem2CreatedDay'
				)
			),
			array('option' => array('value' => '')), '/option',
			$this->dateRegex['daysRegex'],
			'/select', '-',
			array('select' => array(
				'name' => 'data[ValidateProfile][1][ValidateItem][2][created][year]',
				'id' => 'ValidateProfile1ValidateItem2CreatedYear'
				)
			),
			array('option' => array('value' => '')), '/option',
			$this->dateRegex['yearsRegex'],
			'/select',
			'/div'
		);
		$this->assertTags($result, $expected);

		$ValidateProfile = ClassRegistry::getObject('ValidateProfile');
		$ValidateProfile->validationErrors[1]['ValidateItem'][2]['profile_id'] = 'Error';
		$this->Form->request->data['ValidateProfile'][1]['ValidateItem'][2]['profile_id'] = '1';
		$result = $this->Form->input('ValidateProfile.1.ValidateItem.2.profile_id');
		$expected = array(
			'div' => array('class' => 'input select error'),
			'label' => array('for' => 'ValidateProfile1ValidateItem2ProfileId'),
			'Profile',
			'/label',
			'select' => array(
				'name' => 'data[ValidateProfile][1][ValidateItem][2][profile_id]',
				'id' => 'ValidateProfile1ValidateItem2ProfileId',
				'class' => 'form-error'
			),
			'/select',
			array('div' => array('class' => 'error-message')),
			'Error',
			'/div',
			'/div'
		);
		$this->assertTags($result, $expected);
	}

/**
 * test the correct display of multi-record form validation errors.
 *
 * @return void
 */
	public function testMultiRecordFormValidationErrors() {
		$this->Form->create('ValidateProfile');
		$ValidateProfile = ClassRegistry::getObject('ValidateProfile');
		$ValidateProfile->validationErrors[2]['ValidateItem'][1]['name'] = array('Error in field name');
		$result = $this->Form->error('ValidateProfile.2.ValidateItem.1.name');
		$this->assertTags($result, array('div' => array('class' => 'error-message'), 'Error in field name', '/div'));

		$ValidateProfile->validationErrors[2]['city'] = array('Error in field city');
		$result = $this->Form->error('ValidateProfile.2.city');
		$this->assertTags($result, array('div' => array('class' => 'error-message'), 'Error in field city', '/div'));

		$result = $this->Form->error('2.city');
		$this->assertTags($result, array('div' => array('class' => 'error-message'), 'Error in field city', '/div'));
	}

/**
 * tests the ability to change the order of the form input placeholder "input", "label", "before", "between", "after", "error"
 *
 * @return void
 */
	public function testInputTemplate() {
		$result = $this->Form->input('Contact.email', array(
			'type' => 'text', 'format' => array('input')
		));
		$expected = array(
			'div' => array('class' => 'input text'),
			'input' => array(
				'type' => 'text', 'name' => 'data[Contact][email]',
				'id' => 'ContactEmail'
			),
			'/div'
		);
		$this->assertTags($result, $expected);

		$result = $this->Form->input('Contact.email', array(
			'type' => 'text', 'format' => array('input', 'label'),
			'label' => '<em>Email (required)</em>'
		));
		$expected = array(
			'div' => array('class' => 'input text'),
			array('input' => array(
				'type' => 'text', 'name' => 'data[Contact][email]',
				'id' => 'ContactEmail'
			)),
			'label' => array('for' => 'ContactEmail'),
			'em' => array(),
			'Email (required)',
			'/em',
			'/label',
			'/div'
		);
		$this->assertTags($result, $expected);

		$result = $this->Form->input('Contact.email', array(
			'type' => 'text', 'format' => array('input', 'between', 'label', 'after'),
			'between' => '<div>Something in the middle</div>',
			'after' => '<span>Some text at the end</span>'
		));
		$expected = array(
			'div' => array('class' => 'input text'),
			array('input' => array(
				'type' => 'text', 'name' => 'data[Contact][email]',
				'id' => 'ContactEmail'
			)),
			array('div' => array()),
			'Something in the middle',
			'/div',
			'label' => array('for' => 'ContactEmail'),
			'Email',
			'/label',
			'span' => array(),
			'Some text at the end',
			'/span',
			'/div'
		);
		$this->assertTags($result, $expected);

		$result = $this->Form->input('Contact.method', array(
			'type' => 'radio',
			'options' => array('email' => 'Email', 'pigeon' => 'Pigeon'),
			'between' => 'I am between',
		));
		$expected = array(
			'div' => array('class' => 'input radio'),
			'fieldset' => array(),
			'legend' => array(),
			'Method',
			'/legend',
			'I am between',
			'input' => array(
				'type' => 'hidden', 'name' => 'data[Contact][method]',
				'value' => '', 'id' => 'ContactMethod_'
			),
			array('input' => array(
				'type' => 'radio', 'name' => 'data[Contact][method]',
				'value' => 'email', 'id' => 'ContactMethodEmail'
			)),
			array('label' => array('for' => 'ContactMethodEmail')),
			'Email',
			'/label',
			array('input' => array(
				'type' => 'radio', 'name' => 'data[Contact][method]',
				'value' => 'pigeon', 'id' => 'ContactMethodPigeon'
			)),
			array('label' => array('for' => 'ContactMethodPigeon')),
			'Pigeon',
			'/label',
			'/fieldset',
			'/div',
		);
		$this->assertTags($result, $expected);
	}

/**
 * test that some html5 inputs + FormHelper::__call() work
 *
 * @return void
 */
	public function testHtml5Inputs() {
		$result = $this->Form->email('User.email');
		$expected = array(
			'input' => array('type' => 'email', 'name' => 'data[User][email]', 'id' => 'UserEmail')
		);
		$this->assertTags($result, $expected);

		$result = $this->Form->search('User.query');
		$expected = array(
			'input' => array('type' => 'search', 'name' => 'data[User][query]', 'id' => 'UserQuery')
		);
		$this->assertTags($result, $expected);

		$result = $this->Form->search('User.query', array('value' => 'test'));
		$expected = array(
			'input' => array('type' => 'search', 'name' => 'data[User][query]', 'id' => 'UserQuery', 'value' => 'test')
		);
		$this->assertTags($result, $expected);

		$result = $this->Form->search('User.query', array('type' => 'text', 'value' => 'test'));
		$expected = array(
			'input' => array('type' => 'text', 'name' => 'data[User][query]', 'id' => 'UserQuery', 'value' => 'test')
		);
		$this->assertTags($result, $expected);

		$result = $this->Form->input('User.website', array('type' => 'url', 'value' => 'http://domain.tld', 'div' => false, 'label' => false));
		$expected = array(
			'input' => array('type' => 'url', 'name' => 'data[User][website]', 'id' => 'UserWebsite', 'value' => 'http://domain.tld')
		);
		$this->assertTags($result, $expected);
	}

/**
 *
 * @expectedException CakeException
 * @return void
 */
	public function testHtml5InputException() {
		$this->Form->email();
	}

/**
 * Tests that a model can be loaded from the model names passed in the request object
 *
 * @return void
 */
	public function testIntrospectModelFromRequest() {
		$this->loadFixtures('Post');
		App::build(array(
			'Plugin' => array(CAKE . 'Test' . DS . 'test_app' . DS . 'Plugin' . DS)
		));
		CakePlugin::load('TestPlugin');
		$this->Form->request['models'] = array('TestPluginPost' => array('plugin' => 'TestPlugin', 'className' => 'TestPluginPost'));

		$this->assertFalse(ClassRegistry::isKeySet('TestPluginPost'));
		$this->Form->create('TestPluginPost');
		$this->assertTrue(ClassRegistry::isKeySet('TestPluginPost'));
		$this->assertInstanceOf('TestPluginPost', ClassRegistry::getObject('TestPluginPost'));

		CakePlugin::unload();
		App::build();
	}

/**
 * Tests that it is possible to set the validation errors directly in the helper for a field
 *
 * @return void
 */
	public function testCustomValidationErrors() {
		$this->Form->validationErrors['Thing']['field'] = 'Badness!';
		$result = $this->Form->error('Thing.field', null, array('wrap' => false));
		$this->assertEquals('Badness!', $result);
	}
}
