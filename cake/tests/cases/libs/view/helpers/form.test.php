<?php
/* SVN FILE: $Id$ */
/**
 * FormHelperTest file
 *
 * Long description for file
 *
 * PHP versions 4 and 5
 *
 * CakePHP(tm) Tests <https://trac.cakephp.org/wiki/Developement/TestSuite>
 * Copyright 2006-2010, Cake Software Foundation, Inc.
 *
 *  Licensed under The Open Group Test Suite License
 *  Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright 2006-2010, Cake Software Foundation, Inc.
 * @link          https://trac.cakephp.org/wiki/Developement/TestSuite CakePHP(tm) Tests
 * @package       cake
 * @subpackage    cake.tests.cases.libs.view.helpers
 * @since         CakePHP(tm) v 1.2.0.4206
 * @version       $Revision$
 * @modifiedby    $LastChangedBy$
 * @lastmodified  $Date$
 * @license       http://www.opensource.org/licenses/opengroup.php The Open Group Test Suite License
 */
if (!defined('CAKEPHP_UNIT_TEST_EXECUTION')) {
	define('CAKEPHP_UNIT_TEST_EXECUTION', 1);
}
App::import('Core', array('ClassRegistry', 'Controller', 'View', 'Model', 'Security'));
App::import('Helper', 'Html');
App::import('Helper', 'Form');
/**
 * ContactTestController class
 *
 * @package       cake
 * @subpackage    cake.tests.cases.libs.view.helpers
 */
class ContactTestController extends Controller {
/**
 * name property
 *
 * @var string 'ContactTest'
 * @access public
 */
	var $name = 'ContactTest';
/**
 * uses property
 *
 * @var mixed null
 * @access public
 */
	var $uses = null;
}
/**
 * Contact class
 *
 * @package       cake
 * @subpackage    cake.tests.cases.libs.view.helpers
 */
class Contact extends CakeTestModel {
/**
 * primaryKey property
 *
 * @var string 'id'
 * @access public
 */
	var $primaryKey = 'id';
/**
 * useTable property
 *
 * @var bool false
 * @access public
 */
	var $useTable = false;
/**
 * name property
 *
 * @var string 'Contact'
 * @access public
 */
	var $name = 'Contact';
/**
 * Default schema
 *
 * @var array
 * @access public
 */
	var $_schema = array(
		'id' => array('type' => 'integer', 'null' => '', 'default' => '', 'length' => '8'),
		'name' => array('type' => 'string', 'null' => '', 'default' => '', 'length' => '255'),
		'email' => array('type' => 'string', 'null' => '', 'default' => '', 'length' => '255'),
		'phone' => array('type' => 'string', 'null' => '', 'default' => '', 'length' => '255'),
		'password' => array('type' => 'string', 'null' => '', 'default' => '', 'length' => '255'),
		'published' => array('type' => 'date', 'null' => true, 'default' => null, 'length' => null),
		'created' => array('type' => 'date', 'null' => '1', 'default' => '', 'length' => ''),
		'updated' => array('type' => 'datetime', 'null' => '1', 'default' => '', 'length' => null)
	);
/**
 * validate property
 *
 * @var array
 * @access public
 */
	var $validate = array(
		'non_existing' => array(),
		'idontexist' => array(),
		'imrequired' => array('rule' => array('between', 5, 30), 'required' => true),
		'imalsorequired' => array('rule' => 'alphaNumeric', 'required' => true),
		'imnotrequired' => array('required' => false, 'rule' => 'alphaNumeric'),
		'imalsonotrequired' => array('alpha' => array('rule' => 'alphaNumeric','required' => false),
		'between' => array('rule' => array('between', 5, 30))));
/**
 * schema method
 *
 * @access public
 * @return void
 */
	function setSchema($schema) {
		$this->_schema = $schema;
	}
/**
 * hasAndBelongsToMany property
 *
 * @var array
 * @access public
 */
	var $hasAndBelongsToMany = array('ContactTag' => array('with' => 'ContactTagsContact'));
}
/**
 * ContactTagsContact class
 *
 * @package       cake
 * @subpackage    cake.tests.cases.libs.view.helpers
 */
class ContactTagsContact extends CakeTestModel {
/**
 * useTable property
 *
 * @var bool false
 * @access public
 */
	var $useTable = false;
/**
 * name property
 *
 * @var string 'Contact'
 * @access public
 */
	var $name = 'ContactTagsContact';
/**
 * Default schema
 *
 * @var array
 * @access public
 */
	var $_schema = array(
		'contact_id' => array('type' => 'integer', 'null' => '', 'default' => '', 'length' => '8'),
		'contact_tag_id' => array(
			'type' => 'integer', 'null' => '', 'default' => '', 'length' => '8'
		)
	);
/**
 * schema method
 *
 * @access public
 * @return void
 */
	function setSchema($schema) {
		$this->_schema = $schema;
	}
}
/**
 * ContactNonStandardPk class
 *
 * @package       cake
 * @subpackage    cake.tests.cases.libs.view.helpers
 */
class ContactNonStandardPk extends Contact {
/**
 * primaryKey property
 *
 * @var string 'pk'
 * @access public
 */
	var $primaryKey = 'pk';
/**
 * name property
 *
 * @var string 'ContactNonStandardPk'
 * @access public
 */
	var $name = 'ContactNonStandardPk';
/**
 * schema method
 *
 * @access public
 * @return void
 */
	function schema() {
		$this->_schema = parent::schema();
		$this->_schema['pk'] = $this->_schema['id'];
		unset($this->_schema['id']);
		return $this->_schema;
	}
}
/**
 * ContactTag class
 *
 * @package       cake
 * @subpackage    cake.tests.cases.libs.view.helpers
 */
class ContactTag extends Model {
/**
 * useTable property
 *
 * @var bool false
 * @access public
 */
	var $useTable = false;
/**
 * schema definition
 *
 * @var array
 * @access protected
 */
	var $_schema = array(
		'id' => array('type' => 'integer', 'null' => false, 'default' => '', 'length' => '8'),
		'name' => array('type' => 'string', 'null' => false, 'default' => '', 'length' => '255'),
		'created' => array('type' => 'date', 'null' => true, 'default' => '', 'length' => ''),
		'modified' => array('type' => 'datetime', 'null' => true, 'default' => '', 'length' => null)
	);
}
/**
 * UserForm class
 *
 * @package       cake
 * @subpackage    cake.tests.cases.libs.view.helpers
 */
class UserForm extends CakeTestModel {
/**
 * useTable property
 *
 * @var bool false
 * @access public
 */
	var $useTable = false;
/**
 * primaryKey property
 *
 * @var string 'id'
 * @access public
 */
	var $primaryKey = 'id';
/**
 * name property
 *
 * @var string 'UserForm'
 * @access public
 */
	var $name = 'UserForm';
/**
 * hasMany property
 *
 * @var array
 * @access public
 */
	var $hasMany = array(
		'OpenidUrl' => array('className' => 'OpenidUrl', 'foreignKey' => 'user_form_id'
	));
/**
 * schema definition
 *
 * @var array
 * @access protected
 */
	var $_schema = array(
		'id' => array('type' => 'integer', 'null' => '', 'default' => '', 'length' => '8'),
		'published' => array('type' => 'date', 'null' => true, 'default' => null, 'length' => null),
		'other' => array('type' => 'text', 'null' => true, 'default' => null, 'length' => null),
		'stuff' => array('type' => 'string', 'null' => true, 'default' => null, 'length' => 255),
		'something' => array('type' => 'string', 'null' => true, 'default' => null, 'length' => 255),
		'created' => array('type' => 'date', 'null' => '1', 'default' => '', 'length' => ''),
		'updated' => array('type' => 'datetime', 'null' => '1', 'default' => '', 'length' => null)
	);
}
/**
 * OpenidUrl class
 *
 * @package       cake
 * @subpackage    cake.tests.cases.libs.view.helpers
 */
class OpenidUrl extends CakeTestModel {
/**
 * useTable property
 *
 * @var bool false
 * @access public
 */
	var $useTable = false;
/**
 * primaryKey property
 *
 * @var string 'id'
 * @access public
 */
	var $primaryKey = 'id';
/**
 * name property
 *
 * @var string 'OpenidUrl'
 * @access public
 */
	var $name = 'OpenidUrl';
/**
 * belongsTo property
 *
 * @var array
 * @access public
 */
	var $belongsTo = array('UserForm' => array(
		'className' => 'UserForm', 'foreignKey' => 'user_form_id'
	));
/**
 * validate property
 *
 * @var array
 * @access public
 */
	var $validate = array('openid_not_registered' => array());
/**
 * schema method
 *
 * @var array
 * @access protected
 */
	var $_schema = array(
		'id' => array('type' => 'integer', 'null' => '', 'default' => '', 'length' => '8'),
		'user_form_id' => array(
			'type' => 'user_form_id', 'null' => '', 'default' => '', 'length' => '8'
		),
		'url' => array('type' => 'string', 'null' => '', 'default' => '', 'length' => '255'),
	);
/**
 * beforeValidate method
 *
 * @access public
 * @return void
 */
	function beforeValidate() {
		$this->invalidate('openid_not_registered');
		return true;
	}
}
/**
 * ValidateUser class
 *
 * @package       cake
 * @subpackage    cake.tests.cases.libs.view.helpers
 */
class ValidateUser extends CakeTestModel {
/**
 * primaryKey property
 *
 * @var string 'id'
 * @access public
 */
	var $primaryKey = 'id';
/**
 * useTable property
 *
 * @var bool false
 * @access public
 */
	var $useTable = false;
/**
 * name property
 *
 * @var string 'ValidateUser'
 * @access public
 */
	var $name = 'ValidateUser';
/**
 * hasOne property
 *
 * @var array
 * @access public
 */
	var $hasOne = array('ValidateProfile' => array(
		'className' => 'ValidateProfile', 'foreignKey' => 'user_id'
	));
/**
 * schema method
 *
 * @var array
 * @access protected
 */
	var $_schema = array(
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
 * @access public
 * @return void
 */
	function beforeValidate() {
		$this->invalidate('email');
		return false;
	}
}
/**
 * ValidateProfile class
 *
 * @package       cake
 * @subpackage    cake.tests.cases.libs.view.helpers
 */
class ValidateProfile extends CakeTestModel {
/**
 * primaryKey property
 *
 * @var string 'id'
 * @access public
 */
	var $primaryKey = 'id';
/**
 * useTable property
 *
 * @var bool false
 * @access public
 */
	var $useTable = false;
/**
 * schema property
 *
 * @var array
 * @access protected
 */
	var $_schema = array(
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
 * @access public
 */
	var $name = 'ValidateProfile';
/**
 * hasOne property
 *
 * @var array
 * @access public
 */
	var $hasOne = array('ValidateItem' => array(
		'className' => 'ValidateItem', 'foreignKey' => 'profile_id'
	));
/**
 * belongsTo property
 *
 * @var array
 * @access public
 */
	var $belongsTo = array('ValidateUser' => array(
		'className' => 'ValidateUser', 'foreignKey' => 'user_id'
	));
/**
 * beforeValidate method
 *
 * @access public
 * @return void
 */
	function beforeValidate() {
		$this->invalidate('full_name');
		$this->invalidate('city');
		return false;
	}
}
/**
 * ValidateItem class
 *
 * @package       cake
 * @subpackage    cake.tests.cases.libs.view.helpers
 */
class ValidateItem extends CakeTestModel {
/**
 * primaryKey property
 *
 * @var string 'id'
 * @access public
 */
	var $primaryKey = 'id';
/**
 * useTable property
 *
 * @var bool false
 * @access public
 */
	var $useTable = false;
/**
 * name property
 *
 * @var string 'ValidateItem'
 * @access public
 */
	var $name = 'ValidateItem';
/**
 * schema property
 *
 * @var array
 * @access protected
 */
	var $_schema = array(
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
 * @access public
 */
	var $belongsTo = array('ValidateProfile' => array('foreignKey' => 'profile_id'));
/**
 * beforeValidate method
 *
 * @access public
 * @return void
 */
	function beforeValidate() {
		$this->invalidate('description');
		return false;
	}
}
/**
 * TestMail class
 *
 * @package       cake
 * @subpackage    cake.tests.cases.libs.view.helpers
 */
class TestMail extends CakeTestModel {
/**
 * primaryKey property
 *
 * @var string 'id'
 * @access public
 */
	var $primaryKey = 'id';
/**
 * useTable property
 *
 * @var bool false
 * @access public
 */
	var $useTable = false;
/**
 * name property
 *
 * @var string 'TestMail'
 * @access public
 */
	var $name = 'TestMail';
}
/**
 * FormHelperTest class
 *
 * @package       cake
 * @subpackage    cake.tests.cases.libs.view.helpers
 */
class FormHelperTest extends CakeTestCase {
/**
 * fixtures property
 *
 * @var array
 * @access public
 */
	var $fixtures = array(null);
/**
 * setUp method
 *
 * @access public
 * @return void
 */
	function setUp() {
		parent::setUp();
		Router::reload();

		$this->Form =& new FormHelper();
		$this->Form->Html =& new HtmlHelper();
		$this->Controller =& new ContactTestController();
		$this->View =& new View($this->Controller);

		ClassRegistry::addObject('view', $view);
		ClassRegistry::addObject('Contact', new Contact());
		ClassRegistry::addObject('ContactNonStandardPk', new ContactNonStandardPk());
		ClassRegistry::addObject('OpenidUrl', new OpenidUrl());
		ClassRegistry::addObject('UserForm', new UserForm());
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
 * @access public
 * @return void
 */
	function tearDown() {
		ClassRegistry::removeObject('view');
		ClassRegistry::removeObject('Contact');
		ClassRegistry::removeObject('ContactNonStandardPk');
		ClassRegistry::removeObject('ContactTag');
		ClassRegistry::removeObject('OpenidUrl');
		ClassRegistry::removeObject('UserForm');
		ClassRegistry::removeObject('ValidateItem');
		ClassRegistry::removeObject('ValidateUser');
		ClassRegistry::removeObject('ValidateProfile');
		unset($this->Form->Html, $this->Form, $this->Controller, $this->View);
		Configure::write('Security.salt', $this->oldSalt);
	}
/**
 * testFormCreateWithSecurity method
 *
 * Test form->create() with security key.
 *
 * @access public
 * @return void
 */
	function testFormCreateWithSecurity() {
		$this->Form->params['_Token'] = array('key' => 'testKey');

		$result = $this->Form->create('Contact', array('url' => '/contacts/add'));
		$expected = array(
			'form' => array('method' => 'post', 'action' => '/contacts/add'),
			'fieldset' => array('style' => 'display:none;'),
			array('input' => array('type' => 'hidden', 'name' => '_method', 'value' => 'POST')),
			array('input' => array(
				'type' => 'hidden', 'name' => 'data[_Token][key]', 'value' => 'testKey', 'id'
			)),
			'/fieldset'
		);
		$this->assertTags($result, $expected);

		$result = $this->Form->create('Contact', array('url' => '/contacts/add', 'id' => 'MyForm'));
		$expected['form']['id'] = 'MyForm';
		$this->assertTags($result, $expected);
	}
/**
 * Tests form hash generation with model-less data
 *
 * @access public
 * @return void
 */
	function testValidateHashNoModel() {
		$this->Form->params['_Token'] = array('key' => 'foo');
		$result = $this->Form->secure(array('anything'));
		$this->assertPattern('/540ac9c60d323c22bafe997b72c0790f39a8bdef/', $result);
	}
/**
 * Tests that models with identical field names get resolved properly
 *
 * @access public
 * @return void
 */
	function testDuplicateFieldNameResolution() {
		$result = $this->Form->create('ValidateUser');
		$this->assertEqual($this->View->entity(), array('ValidateUser'));

		$result = $this->Form->input('ValidateItem.name');
		$this->assertEqual($this->View->entity(), array('ValidateItem', 'name'));

		$result = $this->Form->input('ValidateUser.name');
		$this->assertEqual($this->View->entity(), array('ValidateUser', 'name'));
		$this->assertPattern('/name="data\[ValidateUser\]\[name\]"/', $result);
		$this->assertPattern('/type="text"/', $result);

		$result = $this->Form->input('ValidateItem.name');
		$this->assertEqual($this->View->entity(), array('ValidateItem', 'name'));
		$this->assertPattern('/name="data\[ValidateItem\]\[name\]"/', $result);
		$this->assertPattern('/<textarea/', $result);

		$result = $this->Form->input('name');
		$this->assertEqual($this->View->entity(), array('ValidateUser', 'name'));
		$this->assertPattern('/name="data\[ValidateUser\]\[name\]"/', $result);
		$this->assertPattern('/type="text"/', $result);
	}
/**
 * Tests that hidden fields generated for checkboxes don't get locked
 *
 * @access public
 * @return void
 */
	function testNoCheckboxLocking() {
		$this->Form->params['_Token'] = array('key' => 'foo');
		$this->assertIdentical($this->Form->fields, array());

		$this->Form->checkbox('check', array('value' => '1'));
		$this->assertIdentical($this->Form->fields, array('check'));
	}
/**
 * testFormSecurityFields method
 *
 * Test generation of secure form hash generation.
 *
 * @access public
 * @return void
 */
	function testFormSecurityFields() {
		$key = 'testKey';
		$fields = array('Model.password', 'Model.username', 'Model.valid' => '0');
		$this->Form->params['_Token']['key'] = $key;
		$result = $this->Form->secure($fields);

		$expected = Security::hash(serialize($fields) . Configure::read('Security.salt'));
		$expected .= ':' . str_rot13(serialize(array('Model.valid')));

		$expected = array(
			'fieldset' => array('style' => 'display:none;'),
			'input' => array(
				'type' => 'hidden', 'name' => 'data[_Token][fields]',
				'value' => urlencode($expected), 'id' => 'preg:/TokenFields\d+/'
			),
			'/fieldset'
		);
		$this->assertTags($result, $expected);
	}
/**
 * Tests correct generation of text fields for double and float fields
 *
 * @access public
 * @return void
 */
	function testTextFieldGenerationForFloats() {
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
			'div' => array('class' => 'input text'),
			'label' => array('for' => 'ContactFoo'),
			'Foo',
			'/label',
			array('input' => array(
				'type' => 'text', 'name' => 'data[Contact][foo]',
				'value' => '', 'id' => 'ContactFoo'
			)),
			'/div'
		);
	}
/**
 * testFormSecurityMultipleFields method
 *
 * Test secure() with multiple row form. Ensure hash is correct.
 *
 * @access public
 * @return void
 */
	function testFormSecurityMultipleFields() {
		$key = 'testKey';

		$fields = array(
			'Model.0.password', 'Model.0.username', 'Model.0.hidden' => 'value',
			'Model.0.valid' => '0', 'Model.1.password', 'Model.1.username',
			'Model.1.hidden' => 'value', 'Model.1.valid' => '0'
		);
		$this->Form->params['_Token']['key'] = $key;
		$result = $this->Form->secure($fields);

		$hash  = '51e3b55a6edd82020b3f29c9ae200e14bbeb7ee5%3An%3A4%3A%7Bv%3A0%3Bf%3A14%3A%22Zbqry.';
		$hash .= '0.uvqqra%22%3Bv%3A1%3Bf%3A13%3A%22Zbqry.0.inyvq%22%3Bv%3A2%3Bf%3A14%3A%22Zbqry.1';
		$hash .= '.uvqqra%22%3Bv%3A3%3Bf%3A13%3A%22Zbqry.1.inyvq%22%3B%7D';

		$expected = array(
			'fieldset' => array('style' => 'display:none;'),
			'input' => array(
				'type' => 'hidden', 'name' => 'data[_Token][fields]',
				'value' => $hash, 'id' => 'preg:/TokenFields\d+/'
			),
			'/fieldset'
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
	function testFormSecurityMultipleSubmitButtons() {
		$key = 'testKey';
		$this->Form->params['_Token']['key'] = $key;

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
			'fieldset' => array('style' => 'display:none;'),
			'input' => array(
				'type' => 'hidden', 'name' => 'data[_Token][fields]',
				'value' => 'preg:/.+/', 'id' => 'preg:/TokenFields\d+/'
			),
			'/fieldset'
		);
		$this->assertTags($result, $expected);
	}
/**
 * testFormSecurityMultipleInputFields method
 *
 * Test secure form creation with multiple row creation.  Checks hidden, text, checkbox field types
 *
 * @access public
 * @return void
 */
	function testFormSecurityMultipleInputFields() {
		$key = 'testKey';

		$this->Form->params['_Token']['key'] = $key;
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

		$hash = 'c9118120e680a7201b543f562e5301006ccfcbe2%3An%3A2%3A%7Bv%3A0%3Bf%3A14%';
		$hash .= '3A%22Nqqerffrf.0.vq%22%3Bv%3A1%3Bf%3A14%3A%22Nqqerffrf.1.vq%22%3B%7D';

		$expected = array(
			'fieldset' => array('style' => 'display:none;'),
			'input' => array(
				'type' => 'hidden', 'name' => 'data[_Token][fields]',
				'value' => $hash, 'id' => 'preg:/TokenFields\d+/'
			),
			'/fieldset'
		);
		$this->assertTags($result, $expected);
	}
/**
 * testFormSecurityMultipleInputDisabledFields method
 *
 * test secure form generation with multiple records and disabled fields.
 *
 * @access public
 * @return void
 */
	function testFormSecurityMultipleInputDisabledFields() {
		$key = 'testKey';
		$this->Form->params['_Token']['key'] = $key;
		$this->Form->params['_Token']['disabledFields'] = array('first_name', 'address');
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
		$hash = '774df31936dc850b7d8a5277dc0b890123788b09%3An%3A2%3A%7Bv%3A0%3Bf%3A14%3A%22Nqqerf';
		$hash .= 'frf.0.vq%22%3Bv%3A1%3Bf%3A14%3A%22Nqqerffrf.1.vq%22%3B%7D';

		$expected = array(
			'fieldset' => array('style' => 'display:none;'),
			'input' => array(
				'type' => 'hidden', 'name' => 'data[_Token][fields]',
				'value' => $hash, 'id' => 'preg:/TokenFields\d+/'
			),
			'/fieldset'
		);
		$this->assertTags($result, $expected);
	}
/**
 * testFormSecurityInputDisabledFields method
 *
 * Test single record form with disabled fields.
 *
 * @access public
 * @return void
 */
	function testFormSecurityInputDisabledFields() {
		$key = 'testKey';
		$this->Form->params['_Token']['key'] = $key;
		$this->Form->params['_Token']['disabledFields'] = array('first_name', 'address');
		$this->Form->create();

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
		$this->assertEqual($result, $expected);

		$result = $this->Form->secure($expected);

		$hash = '449b7e889128e8e52c5e81d19df68f5346571492%3An%3A1%3A%';
		$hash .= '7Bv%3A0%3Bf%3A12%3A%22Nqqerffrf.vq%22%3B%7D';
		$expected = array(
			'fieldset' => array('style' => 'display:none;'),
			'input' => array(
				'type' => 'hidden', 'name' => 'data[_Token][fields]',
				'value' => $hash, 'id' => 'preg:/TokenFields\d+/'
			),
			'/fieldset'
		);
		$this->assertTags($result, $expected);
	}
/**
 * testFormSecuredInput method
 *
 * Test generation of entire secure form, assertions made on input() output.
 *
 * @access public
 * @return void
 */
	function testFormSecuredInput() {
		$this->Form->params['_Token']['key'] = 'testKey';

		$result = $this->Form->create('Contact', array('url' => '/contacts/add'));
		$expected = array(
			'form' => array('method' => 'post', 'action' => '/contacts/add'),
			'fieldset' => array('style' => 'display:none;'),
			array('input' => array('type' => 'hidden', 'name' => '_method', 'value' => 'POST')),
			array('input' => array(
				'type' => 'hidden', 'name' => 'data[_Token][key]',
				'value' => 'testKey', 'id' => 'preg:/Token\d+/'
			)),
			'/fieldset'
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
				'value' => '', 'id' => 'UserFormPublished'
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
				'value' => '', 'id' => 'UserFormOther'
			)),
			'/div'
		);
		$this->assertTags($result, $expected);

		$result = $this->Form->hidden('UserForm.stuff');
		$expected = array('input' => array(
				'type' => 'hidden', 'name' => 'data[UserForm][stuff]',
				'value' => '', 'id' => 'UserFormStuff'
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
		$this->assertEqual($result, $expected);

		$hash = 'bd7c4a654e5361f9a433a43f488ff9a1065d0aaf%3An%3A2%3A%7Bv%3A0%3Bf%3A15%3';
		$hash .= 'A%22HfreSbez.uvqqra%22%3Bv%3A1%3Bf%3A14%3A%22HfreSbez.fghss%22%3B%7D';

		$result = $this->Form->secure($this->Form->fields);
		$expected = array(
			'fieldset' => array('style' => 'display:none;'),
			array('input' => array(
				'type' => 'hidden', 'name' => 'data[_Token][fields]',
				'value' => $hash, 'id' => 'preg:/TokenFields\d+/'
			)),
			'/fieldset'
		);
		$this->assertTags($result, $expected);
	}
/**
 * Tests that the correct keys are added to the field hash index
 *
 * @access public
 * @return void
 */
	function testFormSecuredFileInput() {
		$this->Form->params['_Token']['key'] = 'testKey';
		$this->assertEqual($this->Form->fields, array());

		$result = $this->Form->file('Attachment.file');
		$expected = array (
			'Attachment.file.name', 'Attachment.file.type', 'Attachment.file.tmp_name',
			'Attachment.file.error', 'Attachment.file.size'
		);
		$this->assertEqual($this->Form->fields, $expected);
	}
/**
 * test that multiple selects keys are added to field hash
 *
 * @access public
 * @return void
 */
	function testFormSecuredMultipleSelect() {
		$this->Form->params['_Token']['key'] = 'testKey';
		$this->assertEqual($this->Form->fields, array());
		$options = array('1' => 'one', '2' => 'two');

		$this->Form->select('Model.select', $options);
		$expected = array('Model.select');
		$this->assertEqual($this->Form->fields, $expected);

		$this->Form->fields = array();
		$this->Form->select('Model.select', $options, null, array('multiple' => true));
		$this->assertEqual($this->Form->fields, $expected);
	}
/**
 * testFormSecuredRadio method
 *
 * @access public
 * @return void
 */
	function testFormSecuredRadio() {
		$this->Form->params['_Token']['key'] = 'testKey';
		$this->assertEqual($this->Form->fields, array());
		$options = array('1' => 'option1', '2' => 'option2');

		$this->Form->radio('Test.test', $options);
		$expected = array('Test.test');
		$this->assertEqual($this->Form->fields, $expected);
	}
/**
 * testPasswordValidation method
 *
 * test validation errors on password input.
 *
 * @access public
 * @return void
 */
	function testPasswordValidation() {
		$this->Form->validationErrors['Contact']['password'] = 'Please provide a password';
		$result = $this->Form->input('Contact.password');
		$expected = array(
			'div' => array('class' => 'input password error'),
			'label' => array('for' => 'ContactPassword'),
			'Password',
			'/label',
			'input' => array(
				'type' => 'password', 'name' => 'data[Contact][password]',
				'value' => '', 'id' => 'ContactPassword', 'class' => 'form-error'
			),
			array('div' => array('class' => 'error-message')),
			'Please provide a password',
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
 * @access public
 * @return void
 */
	function testFormValidationAssociated() {
		$this->UserForm =& ClassRegistry::getObject('UserForm');
		$this->UserForm->OpenidUrl =& ClassRegistry::getObject('OpenidUrl');

		$data = array(
			'UserForm' => array('name' => 'user'),
			'OpenidUrl' => array('url' => 'http://www.cakephp.org')
		);

		$this->assertTrue($this->UserForm->OpenidUrl->create($data));
		$this->assertFalse($this->UserForm->OpenidUrl->validates());

		$result = $this->Form->create('UserForm', array('type' => 'post', 'action' => 'login'));
		$expected = array(
			'form' => array(
				'method' => 'post', 'action' => '/user_forms/login/', 'id' => 'UserFormLoginForm'
			),
			'fieldset' => array('style' => 'display:none;'),
			'input' => array('type' => 'hidden', 'name' => '_method', 'value' => 'POST'),
			'/fieldset'
		);
		$this->assertTags($result, $expected);

		$expected = array('OpenidUrl' => array('openid_not_registered' => 1));
		$this->assertEqual($this->Form->validationErrors, $expected);

		$result = $this->Form->error(
			'OpenidUrl.openid_not_registered', 'Error, not registered', array('wrap' => false)
		);
		$this->assertEqual($result, 'Error, not registered');

		unset($this->UserForm->OpenidUrl, $this->UserForm);
	}
/**
 * testFormValidationAssociatedFirstLevel method
 *
 * test form error display with associated model.
 *
 * @access public
 * @return void
 */
	function testFormValidationAssociatedFirstLevel() {
		$this->ValidateUser =& ClassRegistry::getObject('ValidateUser');
		$this->ValidateUser->ValidateProfile =& ClassRegistry::getObject('ValidateProfile');

		$data = array(
			'ValidateUser' => array('name' => 'mariano'),
			'ValidateProfile' => array('full_name' => 'Mariano Iglesias')
		);

		$this->assertTrue($this->ValidateUser->create($data));
		$this->assertFalse($this->ValidateUser->validates());
		$this->assertFalse($this->ValidateUser->ValidateProfile->validates());

		$result = $this->Form->create('ValidateUser', array('type' => 'post', 'action' => 'add'));
		$expected = array(
			'form' => array('method' => 'post', 'action' => '/validate_users/add/', 'id'),
			'fieldset' => array('style' => 'display:none;'),
			'input' => array('type' => 'hidden', 'name' => '_method', 'value' => 'POST'),
			'/fieldset'
		);
		$this->assertTags($result, $expected);

		$expected = array(
			'ValidateUser' => array('email' => 1),
			'ValidateProfile' => array('full_name' => 1, 'city' => 1)
		);
		$this->assertEqual($this->Form->validationErrors, $expected);

		unset($this->ValidateUser->ValidateProfile);
		unset($this->ValidateUser);
	}
/**
 * testFormValidationAssociatedSecondLevel method
 *
 * test form error display with associated model.
 *
 * @access public
 * @return void
 */
	function testFormValidationAssociatedSecondLevel() {
		$this->ValidateUser =& ClassRegistry::getObject('ValidateUser');
		$this->ValidateUser->ValidateProfile =& ClassRegistry::getObject('ValidateProfile');
		$this->ValidateUser->ValidateProfile->ValidateItem =& ClassRegistry::getObject('ValidateItem');

		$data = array(
			'ValidateUser' => array('name' => 'mariano'),
			'ValidateProfile' => array('full_name' => 'Mariano Iglesias'),
			'ValidateItem' => array('name' => 'Item')
		);

		$this->assertTrue($this->ValidateUser->create($data));
		$this->assertFalse($this->ValidateUser->validates());
		$this->assertFalse($this->ValidateUser->ValidateProfile->validates());
		$this->assertFalse($this->ValidateUser->ValidateProfile->ValidateItem->validates());

		$result = $this->Form->create('ValidateUser', array('type' => 'post', 'action' => 'add'));
		$expected = array(
			'form' => array('method' => 'post', 'action' => '/validate_users/add/', 'id'),
			'fieldset' => array('style' => 'display:none;'),
			'input' => array('type' => 'hidden', 'name' => '_method', 'value' => 'POST'),
			'/fieldset'
		);
		$this->assertTags($result, $expected);

		$expected = array(
			'ValidateUser' => array('email' => 1),
			'ValidateProfile' => array('full_name' => 1, 'city' => 1),
			'ValidateItem' => array('description' => 1)
		);
		$this->assertEqual($this->Form->validationErrors, $expected);

		unset($this->ValidateUser->ValidateProfile->ValidateItem);
		unset($this->ValidateUser->ValidateProfile);
		unset($this->ValidateUser);
	}
/**
 * testFormValidationMultiRecord method
 *
 * test form error display with multiple records.
 *
 * @access public
 * @return void
 */
	function testFormValidationMultiRecord() {
		$this->Form->validationErrors['Contact'] = array(2 => array(
			'name' => 'This field cannot be left blank'
		));
		$result = $this->Form->input('Contact.2.name');
		$expected = array(
			'div' => array('class'),
			'label' => array('for'),
			'preg:/[^<]+/',
			'/label',
			'input' => array(
				'type' => 'text', 'name', 'value' => '', 'id',
				'class' => 'form-error', 'maxlength' => 255
			),
			array('div' => array('class' => 'error-message')),
			'This field cannot be left blank',
			'/div',
			'/div'
		);
		$this->assertTags($result, $expected);

		$this->Form->validationErrors['UserForm'] = array(
			'OpenidUrl' => array('url' => 'You must provide a URL'
		));
		$this->Form->create('UserForm');
		$result = $this->Form->input('OpenidUrl.url');
		$expected = array(
			'div' => array('class'),
			'label' => array('for'),
			'preg:/[^<]+/',
			'/label',
			'input' => array(
				'type' => 'text', 'name', 'value' => '', 'id', 'class' => 'form-error'
			),
			array('div' => array('class' => 'error-message')),
			'You must provide a URL',
			'/div',
			'/div'
		);
	}
/**
 * testMultipleInputValidation method
 *
 * test multiple record form validation error display.
 *
 * @access public
 * @return void
 */
	function testMultipleInputValidation() {
		$this->Form->create();
		$this->Form->validationErrors['Address'][0]['title'] = 'This field cannot be empty';
		$this->Form->validationErrors['Address'][0]['first_name'] = 'This field cannot be empty';
		$this->Form->validationErrors['Address'][1]['last_name'] = 'You must have a last name';

		$result = $this->Form->input('Address.0.title');
		$expected = array(
			'div' => array('class'),
			'label' => array('for'),
			'preg:/[^<]+/',
			'/label',
			'input' => array(
				'type' => 'text', 'name', 'value' => '', 'id', 'class' => 'form-error'
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
			'input' => array('type' => 'text', 'name', 'value' => '', 'id', 'class' => 'form-error'),
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
			'input' => array('type' => 'text', 'name', 'value' => '', 'id', 'class' => 'form-error'),
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
				'type' => 'text', 'name' => 'preg:/[^<]+/', 'value' => '',
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
 * testFormInput method
 *
 * Test various incarnations of input().
 *
 * @access public
 * @return void
 */
	function testFormInput() {
		$result = $this->Form->input('ValidateUser.balance');
		$expected = array(
			'div' => array('class'),
			'label' => array('for'),
			'Balance',
			'/label',
			'input' => array('name', 'type' => 'text', 'maxlength' => 8, 'value' => '', 'id'),
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
				'type' => 'text', 'name' => 'data[Contact][email]', 'value' => '',
				'id' => 'custom', 'maxlength' => 255
			)),
			'/div'
		);
		$this->assertTags($result, $expected);

		$result = $this->Form->hidden('Contact.idontexist');
		$expected = array('input' => array(
				'type' => 'hidden', 'name' => 'data[Contact][idontexist]',
				'value' => '', 'id' => 'ContactIdontexist'
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
				'value' => '', 'id' => 'ContactEmail'
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
				'value' => '', 'id' => 'Contact5Email'
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
				'value' => '', 'id' => 'ContactPassword'
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
				'value' => '', 'id' => 'ContactEmail'
			)),
			'/div'
		);
		$this->assertTags($result, $expected);

		$this->Form->data = array('Contact' => array('phone' => 'Hello & World > weird chars'));
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

		$this->Form->data['Model']['0']['OtherModel']['field'] = 'My value';
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

		unset($this->Form->data);

		$this->Form->validationErrors['Model']['field'] = 'Badness!';
		$result = $this->Form->input('Model.field');
		$expected = array(
			'div' => array('class' => 'input text error'),
			'label' => array('for' => 'ModelField'),
			'Field',
			'/label',
			'input' => array(
				'type' => 'text', 'name' => 'data[Model][field]', 'value' => '',
				'id' => 'ModelField', 'class' => 'form-error'
			),
			array('div' => array('class' => 'error-message')),
			'Badness!',
			'/div',
			'/div'
		);
		$this->assertTags($result, $expected);

		$result = $this->Form->input('Model.field', array(
			'div' => false, 'error' => array('wrap' => 'span')
		));
		$expected = array(
			'label' => array('for' => 'ModelField'),
			'Field',
			'/label',
			'input' => array(
				'type' => 'text', 'name' => 'data[Model][field]', 'value' => '',
				'id' => 'ModelField', 'class' => 'form-error'
			),
			array('span' => array('class' => 'error-message')),
			'Badness!',
			'/span'
		);
		$this->assertTags($result, $expected);

		$result = $this->Form->input('Model.field', array(
			'div' => array('tag' => 'span'), 'error' => array('wrap' => false)
		));
		$expected = array(
			'span' => array('class' => 'input text error'),
			'label' => array('for' => 'ModelField'),
			'Field',
			'/label',
			'input' => array(
				'type' => 'text', 'name' => 'data[Model][field]', 'value' => '',
				'id' => 'ModelField', 'class' => 'form-error'
			),
			'Badness!',
			'/span'
		);
		$this->assertTags($result, $expected);

		$result = $this->Form->input('Model.field', array('after' => 'A message to you, Rudy'));
		$expected = array(
			'div' => array('class' => 'input text error'),
			'label' => array('for' => 'ModelField'),
			'Field',
			'/label',
			'input' => array(
				'type' => 'text', 'name' => 'data[Model][field]', 'value' => '',
				'id' => 'ModelField', 'class' => 'form-error'
			),
			'A message to you, Rudy',
			array('div' => array('class' => 'error-message')),
			'Badness!',
			'/div',
			'/div'
		);
		$this->assertTags($result, $expected);

		$this->Form->setEntity(null);
		$this->Form->setEntity('Model.field');
		$result = $this->Form->input('Model.field', array(
			'after' => 'A message to you, Rudy', 'error' => false
		));
		$expected = array(
			'div' => array('class' => 'input text'),
			'label' => array('for' => 'ModelField'),
			'Field',
			'/label',
			'input' => array('type' => 'text', 'name' => 'data[Model][field]', 'value' => '', 'id' => 'ModelField', 'class' => 'form-error'),
			'A message to you, Rudy',
			'/div'
		);
		$this->assertTags($result, $expected);

		unset($this->Form->validationErrors['Model']['field']);
		$result = $this->Form->input('Model.field', array('after' => 'A message to you, Rudy'));
		$expected = array(
			'div' => array('class' => 'input text'),
			'label' => array('for' => 'ModelField'),
			'Field',
			'/label',
			'input' => array('type' => 'text', 'name' => 'data[Model][field]', 'value' => '', 'id' => 'ModelField'),
			'A message to you, Rudy',
			'/div'
		);
		$this->assertTags($result, $expected);

		$this->Form->validationErrors['Model']['field'] = 'minLength';
		$result = $this->Form->input('Model.field', array('error' => array('minLength' => __('Le login doit contenir au moins 2 caractères', true))));
		$expected = array(
			'div' => array('class' => 'input text error'),
			'label' => array('for' => 'ModelField'),
			'Field',
			'/label',
			'input' => array('type' => 'text', 'name' => 'data[Model][field]', 'value' => '', 'id' => 'ModelField', 'class' => 'form-error'),
			array('div' => array('class' => 'error-message')),
			'Le login doit contenir au moins 2 caractères',
			'/div',
			'/div'
		);
		$this->assertTags($result, $expected);
	}
/**
 * test form->input() with datetime, date and time types
 *
 * @return void
 **/
	function testInputDatetime() {
		extract($this->dateRegex);
		$result = $this->Form->input('Contact.created', array('type' => 'time', 'timeFormat' => 24));
		$result = explode(':', $result);
		$this->assertPattern('/option value="23"/', $result[0]);
		$this->assertNoPattern('/option value="24"/', $result[0]);

		$result = $this->Form->input('Contact.created', array('type' => 'time', 'timeFormat' => 24));
		$result = explode(':', $result);
		$this->assertPattern('/option value="23"/', $result[0]);
		$this->assertNoPattern('/option value="24"/', $result[0]);

		$result = $this->Form->input('Model.field', array(
			'type' => 'time', 'timeFormat' => 24, 'interval' => 15
		));
		$result = explode(':', $result);
		$this->assertNoPattern('#<option value="12"[^>]*>12</option>#', $result[1]);
		$this->assertNoPattern('#<option value="50"[^>]*>50</option>#', $result[1]);
		$this->assertPattern('#<option value="15"[^>]*>15</option>#', $result[1]);

		$result = $this->Form->input('Model.field', array(
			'type' => 'time', 'timeFormat' => 12, 'interval' => 15
		));
		$result = explode(':', $result);
		$this->assertNoPattern('#<option value="12"[^>]*>12</option>#', $result[1]);
		$this->assertNoPattern('#<option value="50"[^>]*>50</option>#', $result[1]);
		$this->assertPattern('#<option value="15"[^>]*>15</option>#', $result[1]);

		$result = $this->Form->input('prueba', array(
			'type' => 'time', 'timeFormat'=> 24 , 'dateFormat'=>'DMY' , 'minYear' => 2008,
			'maxYear' => date('Y') + 1 ,'interval' => 15
		));
		$result = explode(':', $result);
		$this->assertNoPattern('#<option value="12"[^>]*>12</option>#', $result[1]);
		$this->assertNoPattern('#<option value="50"[^>]*>50</option>#', $result[1]);
		$this->assertPattern('#<option value="15"[^>]*>15</option>#', $result[1]);
		$this->assertPattern('#<option value="30"[^>]*>30</option>#', $result[1]);

		$result = $this->Form->input('prueba', array(
			'type' => 'datetime', 'timeFormat'=> 24 , 'dateFormat'=>'DMY' , 'minYear' => 2008,
			'maxYear' => date('Y') + 1 ,'interval' => 15
		));
		$result = explode(':', $result);
		$this->assertNoPattern('#<option value="12"[^>]*>12</option>#', $result[1]);
		$this->assertNoPattern('#<option value="50"[^>]*>50</option>#', $result[1]);
		$this->assertPattern('#<option value="15"[^>]*>15</option>#', $result[1]);
		$this->assertPattern('#<option value="30"[^>]*>30</option>#', $result[1]);

		//related to ticket #5013
		$result = $this->Form->input('Contact.date', array(
			'type' => 'date', 'class' => 'customClass', 'onChange' => 'function(){}'
		));
		$this->assertPattern('/class="customClass"/', $result);
		$this->assertPattern('/onChange="function\(\)\{\}"/', $result);

		$result = $this->Form->input('Contact.date', array(
			'type' => 'date', 'id' => 'customId', 'onChange' => 'function(){}'
		));
		$this->assertPattern('/id="customIdDay"/', $result);
		$this->assertPattern('/id="customIdMonth"/', $result);
		$this->assertPattern('/onChange="function\(\)\{\}"/', $result);

		$result = $this->Form->input('Model.field', array(
			'type' => 'datetime', 'timeFormat' => 24, 'id' => 'customID'
		));
		$this->assertPattern('/id="customIDDay"/', $result);
		$this->assertPattern('/id="customIDHour"/', $result);
		$result = explode('</select><select', $result);
		$result = explode(':', $result[1]);
		$this->assertPattern('/option value="23"/', $result[0]);
		$this->assertNoPattern('/option value="24"/', $result[0]);

		$result = $this->Form->input('Model.field', array(
			'type' => 'datetime', 'timeFormat' => 12
		));
		$result = explode('</select><select', $result);
		$result = explode(':', $result[1]);
		$this->assertPattern('/option value="12"/', $result[0]);
		$this->assertNoPattern('/option value="13"/', $result[0]);

		$this->Form->data = array('Contact' => array('created' => null));
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

		$this->Form->data = array('Contact' => array('created' => null));
		$result = $this->Form->input('Contact.created', array('type' => 'datetime', 'dateFormat' => 'NONE'));
		$this->assertPattern('/for\="ContactCreatedHour"/', $result);
		
		$this->Form->data = array('Contact' => array('created' => null));
		$result = $this->Form->input('Contact.created', array('type' => 'datetime', 'timeFormat' => 'NONE'));
		$this->assertPattern('/for\="ContactCreatedMonth"/', $result);
	}
/**
 * Test generating checkboxes in a loop.
 *
 * @return void
 **/
	function testInputCheckboxesInLoop() {
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
	function testInputWithLeadingInteger() {
		$result = $this->Form->text('0.Node.title');
		$expected = array(
			'input' => array('name' => 'data[0][Node][title]', 'id' => '0NodeTitle', 'value' => '', 'type' => 'text')
		);
		$this->assertTags($result, $expected);
	}

/**
 * test form->input() with select type inputs.
 *
 * @return void
 **/
	function testInputSelectType() {
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

		$this->Form->data = array('Model' => array('user_id' => 'value'));
		$view =& ClassRegistry::getObject('view');
		$view->viewVars['users'] = array('value' => 'good', 'other' => 'bad');
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

		$this->Form->data = array('Model' => array('user_id' => null));
		$view =& ClassRegistry::getObject('view');
		$view->viewVars['users'] = array('value' => 'good', 'other' => 'bad');
		$result = $this->Form->input('Model.user_id', array('empty' => 'Some Empty'));
		$expected = array(
			'div' => array('class' => 'input select'),
			'label' => array('for' => 'ModelUserId'),
			'User',
			'/label',
			'select' => array('name' => 'data[Model][user_id]', 'id' => 'ModelUserId'),
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

		$this->Form->data = array('Model' => array('user_id' => 'value'));
		$view =& ClassRegistry::getObject('view');
		$view->viewVars['users'] = array('value' => 'good', 'other' => 'bad');
		$result = $this->Form->input('Model.user_id', array('empty' => 'Some Empty'));
		$expected = array(
			'div' => array('class' => 'input select'),
			'label' => array('for' => 'ModelUserId'),
			'User',
			'/label',
			'select' => array('name' => 'data[Model][user_id]', 'id' => 'ModelUserId'),
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

		$this->Form->data = array('User' => array('User' => array('value')));
		$view =& ClassRegistry::getObject('view');
		$view->viewVars['users'] = array('value' => 'good', 'other' => 'bad');
		$result = $this->Form->input('User.User', array('empty' => true));
		$expected = array(
			'div' => array('class' => 'input select'),
			'label' => array('for' => 'UserUser'),
			'User',
			'/label',
			'input' => array('type' => 'hidden', 'name' => 'data[User][User]', 'value' => ''),
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
	}
/**
 * testFormInputs method
 *
 * test correct results from form::inputs().
 *
 * @access public
 * @return void
 */
	function testFormInputs() {
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

		$View = ClassRegistry::getObject('view');
		$this->Form->create('Contact');
		$this->Form->params['prefix'] = 'admin';
		$this->Form->action = 'admin_edit';
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
			'input' => array('type' => 'hidden', 'name' => 'data[Contact][id]', 'value' => '', 'id' => 'ContactId'),
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
			array('div' => array('class' => 'input select')),
			'*/div',
		);
		$this->assertTags($result, $expected);

		$this->Form->create('Contact');
		$result = $this->Form->inputs(array('fieldset' => false, 'legend' => false));
		$expected = array(
			'input' => array('type' => 'hidden', 'name' => 'data[Contact][id]', 'value' => '', 'id' => 'ContactId'),
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
			array('div' => array('class' => 'input select')),
			'*/div',
		);
		$this->assertTags($result, $expected);

		$this->Form->create('Contact');
		$result = $this->Form->inputs(array('fieldset' => true, 'legend' => false));
		$expected = array(
			'fieldset' => array(),
			'input' => array('type' => 'hidden', 'name' => 'data[Contact][id]', 'value' => '', 'id' => 'ContactId'),
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
			array('div' => array('class' => 'input select')),
			'*/div',
			'/fieldset'
		);
		$this->assertTags($result, $expected);

		$this->Form->create('Contact');
		$result = $this->Form->inputs(array('fieldset' => false, 'legend' => 'Hello'));
		$expected = array(
			'input' => array('type' => 'hidden', 'name' => 'data[Contact][id]', 'value' => '', 'id' => 'ContactId'),
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
			'input' => array('type' => 'hidden', 'name' => 'data[Contact][id]', 'value' => '', 'id' => 'ContactId'),
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
			'input' => array('type' => 'hidden', 'name' => 'data[Contact][id]', 'value' => '', 'id' => 'ContactId'),
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
 * @access public
 * @return void
 */
	function testSelectAsCheckbox() {
		$result = $this->Form->select('Model.multi_field', array('first', 'second', 'third'), array(0, 1), array('multiple' => 'checkbox'));
		$expected = array(
			'input' => array('type' => 'hidden', 'name' => 'data[Model][multi_field]', 'value' => ''),
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
	}
/**
 * testLabel method
 *
 * test label generation.
 *
 * @access public
 * @return void
 */
	function testLabel() {
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
 * @access public
 * @return void
 */
	function testTextbox() {
		$result = $this->Form->text('Model.field');
		$this->assertTags($result, array('input' => array('type' => 'text', 'name' => 'data[Model][field]', 'value' => '', 'id' => 'ModelField')));

		$result = $this->Form->text('Model.field', array('type' => 'password'));
		$this->assertTags($result, array('input' => array('type' => 'password', 'name' => 'data[Model][field]', 'value' => '', 'id' => 'ModelField')));

		$result = $this->Form->text('Model.field', array('id' => 'theID'));
		$this->assertTags($result, array('input' => array('type' => 'text', 'name' => 'data[Model][field]', 'value' => '', 'id' => 'theID')));

		$this->Form->data['Model']['text'] = 'test <strong>HTML</strong> values';
		$result = $this->Form->text('Model.text');
		$this->assertTags($result, array('input' => array('type' => 'text', 'name' => 'data[Model][text]', 'value' => 'test &lt;strong&gt;HTML&lt;/strong&gt; values', 'id' => 'ModelText')));

		$this->Form->validationErrors['Model']['text'] = 1;
		$this->Form->data['Model']['text'] = 'test';
		$result = $this->Form->text('Model.text', array('id' => 'theID'));
		$this->assertTags($result, array('input' => array('type' => 'text', 'name' => 'data[Model][text]', 'value' => 'test', 'id' => 'theID', 'class' => 'form-error')));

		$this->Form->data['Model']['0']['OtherModel']['field'] = 'My value';
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
 * @access public
 * @return void
 */
	function testDefaultValue() {
		$this->Form->data['Model']['field'] = 'test';
		$result = $this->Form->text('Model.field', array('default' => 'default value'));
		$this->assertTags($result, array('input' => array('type' => 'text', 'name' => 'data[Model][field]', 'value' => 'test', 'id' => 'ModelField')));

		unset($this->Form->data['Model']['field']);
		$result = $this->Form->text('Model.field', array('default' => 'default value'));
		$this->assertTags($result, array('input' => array('type' => 'text', 'name' => 'data[Model][field]', 'value' => 'default value', 'id' => 'ModelField')));
	}
/**
 * testError method
 *
 * Test field error generation
 *
 * @access public
 * @return void
 */
	function testError() {
		$this->Form->validationErrors['Model']['field'] = 1;
		$result = $this->Form->error('Model.field');
		$this->assertTags($result, array('div' => array('class' => 'error-message'), 'Error in field Field', '/div'));

		$result = $this->Form->error('Model.field', null, array('wrap' => false));
		$this->assertEqual($result, 'Error in field Field');

		$this->Form->validationErrors['Model']['field'] = "This field contains invalid input";
		$result = $this->Form->error('Model.field', null, array('wrap' => false));
		$this->assertEqual($result, 'This field contains invalid input');

		$this->Form->validationErrors['Model']['field'] = "This field contains invalid input";
		$result = $this->Form->error('Model.field', null, array('wrap' => 'span'));
		$this->assertTags($result, array('span' => array('class' => 'error-message'), 'This field contains invalid input', '/span'));

		$result = $this->Form->error('Model.field', 'There is an error fool!', array('wrap' => 'span'));
		$this->assertTags($result, array('span' => array('class' => 'error-message'), 'There is an error fool!', '/span'));

		$result = $this->Form->error('Model.field', "<strong>Badness!</strong>", array('wrap' => false));
		$this->assertEqual($result, '&lt;strong&gt;Badness!&lt;/strong&gt;');

		$result = $this->Form->error('Model.field', "<strong>Badness!</strong>", array('wrap' => false, 'escape' => true));
		$this->assertEqual($result, '&lt;strong&gt;Badness!&lt;/strong&gt;');

		$result = $this->Form->error('Model.field', "<strong>Badness!</strong>", array('wrap' => false, 'escape' => false));
		$this->assertEqual($result, '<strong>Badness!</strong>');

		$this->Form->validationErrors['Model']['field'] = "email";
		$result = $this->Form->error('Model.field', array('class' => 'field-error', 'email' => 'No good!'));
		$expected = array(
			'div' => array('class' => 'field-error'),
			'No good!',
			'/div'
		);
		$this->assertTags($result, $expected);
	}
/**
 * testPassword method
 *
 * Test password element generation
 *
 * @access public
 * @return void
 */
	function testPassword() {
		$result = $this->Form->password('Model.field');
		$this->assertTags($result, array('input' => array('type' => 'password', 'name' => 'data[Model][field]', 'value' => '', 'id' => 'ModelField')));

		$this->Form->validationErrors['Model']['passwd'] = 1;
		$this->Form->data['Model']['passwd'] = 'test';
		$result = $this->Form->password('Model.passwd', array('id' => 'theID'));
		$this->assertTags($result, array('input' => array('type' => 'password', 'name' => 'data[Model][passwd]', 'value' => 'test', 'id' => 'theID', 'class' => 'form-error')));
	}
/**
 * testRadio method
 *
 * Test radio element set generation
 *
 * @access public
 * @return void
 */
	function testRadio() {
		$result = $this->Form->radio('Model.field', array('option A'));
		$expected = array(
			'input' => array('type' => 'hidden', 'name' => 'data[Model][field]', 'value' => '', 'id' => 'ModelField_'),
			array('input' => array('type' => 'radio', 'name' => 'data[Model][field]', 'value' => '0', 'id' => 'ModelField0')),
			'label' => array('for' => 'ModelField0'),
			'option A',
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
	}
/**
 * testSelect method
 *
 * Test select element generation.
 *
 * @access public
 * @return void
 */
	function testSelect() {
		$result = $this->Form->select('Model.field', array());
		$expected = array(
			'select' => array('name' => 'data[Model][field]', 'id' => 'ModelField'),
			array('option' => array('value' => '')),
			'/option',
			'/select'
		);
		$this->assertTags($result, $expected);

		$this->Form->data = array('Model' => array('field' => 'value'));
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

		$this->Form->data = array();
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
			null, array(), false
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
			null, array('escape' => false), false
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

		$this->Form->data = array('Model' => array('contact_id' => 228));
		$result = $this->Form->select(
			'Model.contact_id',
			array('228' => '228 value', '228-1' => '228-1 value', '228-2' => '228-2 value'),
			null, array('escape' => false), 'pick something'
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
	}
/**
 * Tests that FormHelper::select() allows null to be passed in the $attributes parameter
 *
 * @access public
 * @return void
 */
	function testSelectWithNullAttributes() {
		$result = $this->Form->select('Model.field', array('first', 'second'), null, null, false);
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
 * @access public
 * @return void
 */
	function testNestedSelect() {
		$result = $this->Form->select(
			'Model.field',
			array(1 => 'One', 2 => 'Two', 'Three' => array(
				3 => 'Three', 4 => 'Four', 5 => 'Five'
			)), null, array(), false
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
			array(1 => 'One', 2 => 'Two', 'Three' => array(3 => 'Three', 4 => 'Four')), null,
			array('showParents' => true), false
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
 * @access public
 * @return void
 */
	function testSelectMultiple() {
		$options = array('first', 'second', 'third');
		$result = $this->Form->select(
			'Model.multi_field', $options, null, array('multiple' => true)
		);
		$expected = array(
			'input' => array(
				'type' => 'hidden', 'name' => 'data[Model][multi_field]', 'value' => ''
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
			'Model.multi_field', $options, null, array('multiple' => 'multiple')
		);
		$expected = array(
			'input' => array(
				'type' => 'hidden', 'name' => 'data[Model][multi_field]', 'value' => ''
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
			'Model.multi_field', $options, array(0, 1), array('multiple' => true)
		);
		$expected = array(
			'input' => array(
				'type' => 'hidden', 'name' => 'data[Model][multi_field]', 'value' => ''
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
	}
/**
 * test generation of multi select elements in checkbox format
 *
 * @access public
 * @return void
 */
	function testSelectMultipleCheckboxes() {
		$result = $this->Form->select(
			'Model.multi_field',
			array('first', 'second', 'third'), null,
			array('multiple' => 'checkbox')
		);

		$expected = array(
			'input' => array(
				'type' => 'hidden', 'name' => 'data[Model][multi_field]', 'value' => ''
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
			array('a' => 'first', 'b' => 'second', 'c' => 'third'), null,
			array('multiple' => 'checkbox')
		);
		$expected = array(
			'input' => array(
				'type' => 'hidden', 'name' => 'data[Model][multi_field]', 'value' => ''
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
			'Model.multi_field', array('1' => 'first'), null, array('multiple' => 'checkbox')
		);
		$expected = array(
			'input' => array(
				'type' => 'hidden', 'name' => 'data[Model][multi_field]', 'value' => ''
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
	}
/**
 * Checks the security hash array generated for multiple-input checkbox elements
 *
 * @access public
 * @return void
 */
	function testSelectMultipleCheckboxSecurity() {
		$this->Form->params['_Token']['key'] = 'testKey';
		$this->assertEqual($this->Form->fields, array());

		$result = $this->Form->select(
			'Model.multi_field', array('1' => 'first', '2' => 'second', '3' => 'third'),
			null, array('multiple' => 'checkbox')
		);
		$this->assertEqual($this->Form->fields, array('Model.multi_field'));

		$result = $this->Form->secure($this->Form->fields);
		$key = 'f7d573650a295b94e0938d32b323fde775e5f32b%3An%3A0%3A%7B%7D';
		$this->assertPattern('/"' . $key . '"/', $result);
	}
/**
 * testInputMultipleCheckboxes method
 *
 * test input() resulting in multi select elements being generated.
 *
 * @access public
 * @return void
 */
	function testInputMultipleCheckboxes() {
		$result = $this->Form->input('Model.multi_field', array('options' => array('first', 'second', 'third'), 'multiple' => 'checkbox'));
		$expected = array(
			array('div' => array('class' => 'input select')),
			array('label' => array('for' => 'ModelMultiField')),
			'Multi Field',
			'/label',
			'input' => array('type' => 'hidden', 'name' => 'data[Model][multi_field]', 'value' => ''),
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

		$result = $this->Form->input('Model.multi_field', array('options' => array('a' => 'first', 'b' => 'second', 'c' => 'third'), 'multiple' => 'checkbox'));
		$expected = array(
			array('div' => array('class' => 'input select')),
			array('label' => array('for' => 'ModelMultiField')),
			'Multi Field',
			'/label',
			'input' => array('type' => 'hidden', 'name' => 'data[Model][multi_field]', 'value' => ''),
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

		$result = $this->Form->input('Model.multi_field', array('options' => array('1' => 'first'), 'multiple' => 'checkbox', 'label' => false, 'div' => false));
		$expected = array(
			'input' => array('type' => 'hidden', 'name' => 'data[Model][multi_field]', 'value' => ''),
			array('div' => array('class' => 'checkbox')),
			array('input' => array('type' => 'checkbox', 'name' => 'data[Model][multi_field][]', 'value' => '1', 'id' => 'ModelMultiField1')),
			array('label' => array('for' => 'ModelMultiField1')),
			'first',
			'/label',
			'/div'
		);
		$this->assertTags($result, $expected);

		$result = $this->Form->input('Model.multi_field', array('options' => array('2' => 'second'), 'multiple' => 'checkbox', 'label' => false, 'div' => false));
		$expected = array(
			'input' => array('type' => 'hidden', 'name' => 'data[Model][multi_field]', 'value' => ''),
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
 * testCheckbox method
 *
 * Test generation of checkboxes
 *
 * @access public
 * @return void
 */
	function testCheckbox() {
		$result = $this->Form->checkbox('Model.field', array('id' => 'theID', 'value' => 'myvalue'));
		$expected = array(
			'input' => array('type' => 'hidden', 'name' => 'data[Model][field]', 'value' => '0', 'id' => 'theID_'),
			array('input' => array('type' => 'checkbox', 'name' => 'data[Model][field]', 'value' => 'myvalue', 'id' => 'theID'))
		);
		$this->assertTags($result, $expected);

		$this->Form->validationErrors['Model']['field'] = 1;
		$this->Form->data['Model']['field'] = 'myvalue';
		$result = $this->Form->checkbox('Model.field', array('id' => 'theID', 'value' => 'myvalue'));
		$expected = array(
			'input' => array('type' => 'hidden', 'name' => 'data[Model][field]', 'value' => '0', 'id' => 'theID_'),
			array('input' => array('preg:/[^<]+/', 'value' => 'myvalue', 'id' => 'theID', 'checked' => 'checked', 'class' => 'form-error'))
		);
		$this->assertTags($result, $expected);

		$result = $this->Form->checkbox('Model.field', array('value' => 'myvalue'));
		$expected = array(
			'input' => array('type' => 'hidden', 'name' => 'data[Model][field]', 'value' => '0', 'id' => 'ModelField_'),
			array('input' => array('preg:/[^<]+/', 'value' => 'myvalue', 'id' => 'ModelField', 'checked' => 'checked', 'class' => 'form-error'))
		);
		$this->assertTags($result, $expected);

		$this->Form->data['Model']['field'] = '';
		$result = $this->Form->checkbox('Model.field', array('id' => 'theID'));
		$expected = array(
			'input' => array('type' => 'hidden', 'name' => 'data[Model][field]', 'value' => '0', 'id' => 'theID_'),
			array('input' => array('type' => 'checkbox', 'name' => 'data[Model][field]', 'value' => '1', 'id' => 'theID', 'class' => 'form-error'))
		);
		$this->assertTags($result, $expected);

		unset($this->Form->validationErrors['Model']['field']);
		$result = $this->Form->checkbox('Model.field', array('value' => 'myvalue'));
		$expected = array(
			'input' => array('type' => 'hidden', 'name' => 'data[Model][field]', 'value' => '0', 'id' => 'ModelField_'),
			array('input' => array('type' => 'checkbox', 'name' => 'data[Model][field]', 'value' => 'myvalue', 'id' => 'ModelField'))
		);
		$this->assertTags($result, $expected);

		$result = $this->Form->checkbox('Contact.name', array('value' => 'myvalue'));
		$expected = array(
			'input' => array('type' => 'hidden', 'name' => 'data[Contact][name]', 'value' => '0', 'id' => 'ContactName_'),
			array('input' => array('type' => 'checkbox', 'name' => 'data[Contact][name]', 'value' => 'myvalue', 'id' => 'ContactName'))
		);
		$this->assertTags($result, $expected);

		$result = $this->Form->checkbox('Model.field');
		$expected = array(
			'input' => array('type' => 'hidden', 'name' => 'data[Model][field]', 'value' => '0', 'id' => 'ModelField_'),
			array('input' => array('type' => 'checkbox', 'name' => 'data[Model][field]', 'value' => '1', 'id' => 'ModelField'))
		);
		$this->assertTags($result, $expected);

		$result = $this->Form->checkbox('Model.field', array('checked' => false));
		$expected = array(
			'input' => array('type' => 'hidden', 'name' => 'data[Model][field]', 'value' => '0', 'id' => 'ModelField_'),
			array('input' => array('type' => 'checkbox', 'name' => 'data[Model][field]', 'value' => '1', 'id' => 'ModelField'))
		);
		$this->assertTags($result, $expected);

		$result = $this->Form->checkbox('Model.field', array('checked' => 'checked'));
		$expected = array(
			'input' => array('type' => 'hidden', 'name' => 'data[Model][field]', 'value' => '0', 'id' => 'ModelField_'),
			array('input' => array('type' => 'checkbox', 'name' => 'data[Model][field]', 'value' => '1', 'id' => 'ModelField', 'checked' => 'checked'))
		);
		$this->assertTags($result, $expected);

		$this->Form->validationErrors['Model']['field'] = 1;
		$this->Form->data['Contact']['published'] = 1;
		$result = $this->Form->checkbox('Contact.published', array('id'=>'theID'));
		$expected = array(
			'input' => array('type' => 'hidden', 'name' => 'data[Contact][published]', 'value' => '0', 'id' => 'theID_'),
			array('input' => array('type' => 'checkbox', 'name' => 'data[Contact][published]', 'value' => '1', 'id' => 'theID', 'checked' => 'checked'))
		);
		$this->assertTags($result, $expected);

		$this->Form->validationErrors['Model']['field'] = 1;
		$this->Form->data['Contact']['published'] = 0;
		$result = $this->Form->checkbox('Contact.published', array('id'=>'theID'));
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

		$result = $this->Form->checkbox('Test.test', array('name' => 'myField'));
		$expected = array(
				'input' => array('type' => 'hidden', 'name' => 'myField', 'value' => '0', 'id' => 'TestTest_'),
				array('input' => array('type' => 'checkbox', 'name' => 'myField', 'value' => '1', 'id' => 'TestTest'))
			);
		$this->assertTags($result, $expected);
	}
/**
 * Test that disabling a checkbox also disables the hidden input so no value is submitted
 *
 * @return void
 **/
	function testCheckboxDisabling() {
		$result = $this->Form->checkbox('Account.show_name', array('disabled' => 'disabled'));
		$expected = array(
			array('input' => array('type' => 'hidden', 'name' => 'data[Account][show_name]', 'value' => '0', 'id' => 'AccountShowName_', 'disabled' => 'disabled')),
			array('input' => array('type' => 'checkbox', 'name' => 'data[Account][show_name]', 'value' => '1', 'id' => 'AccountShowName', 'disabled' => 'disabled'))
		);
		$this->assertTags($result, $expected);
	}
	
/**
 * Test that specifying false in the 'disabled' option will not disable either the hidden input or the checkbox input
 *
 * @return void
 **/
	function testCheckboxHiddenDisabling() {
		$result = $this->Form->checkbox('Account.show_name', array('disabled' => false));
		$expected = array(
			array('input' => array('type' => 'hidden', 'name' => 'data[Account][show_name]', 'value' => '0', 'id' => 'AccountShowName_')),
			array('input' => array('type' => 'checkbox', 'name' => 'data[Account][show_name]', 'value' => '1', 'id' => 'AccountShowName'))
		);
		$this->assertTags($result, $expected);
	}
	
/**
 * testDateTime method
 *
 * Test generation of date/time select elements
 *
 * @access public
 * @return void
 */
	function testDateTime() {
		extract($this->dateRegex);

		$result = $this->Form->dateTime('Contact.date', 'DMY', '12', null, array(), false);
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
			array('option' => array('value' => intval(date('i', $now)), 'selected' => 'selected')),
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
		$this->assertNoPattern('/<option[^<>]+value=""[^<>]+selected="selected"[^>]*>/', $result);

		$result = $this->Form->dateTime('Contact.date', 'DMY', '12', false);
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
		$this->assertNoPattern('/<option[^<>]+value=""[^<>]+selected="selected"[^>]*>/', $result);

		$result = $this->Form->dateTime('Contact.date', 'DMY', '12', '');
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
		$this->assertNoPattern('/<option[^<>]+value=""[^<>]+selected="selected"[^>]*>/', $result);

		$result = $this->Form->dateTime('Contact.date', 'DMY', '12', '', array('interval' => 5));
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
			array('option' => array('value' => '0')),
			'00',
			'/option',
			array('option' => array('value' => '5')),
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
		$this->assertNoPattern('/<option[^<>]+value=""[^<>]+selected="selected"[^>]*>/', $result);

		$result = $this->Form->dateTime('Contact.date', 'DMY', '12', '', array('minuteInterval' => 5));
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
			array('option' => array('value' => '0')),
			'00',
			'/option',
			array('option' => array('value' => '5')),
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
		$this->assertNoPattern('/<option[^<>]+value=""[^<>]+selected="selected"[^>]*>/', $result);

		$this->Form->data['Contact']['data'] = null;
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
		$this->assertNoPattern('/<option[^<>]+value=""[^<>]+selected="selected"[^>]*>/', $result);

		$this->Form->data['Model']['field'] = date('Y') . '-01-01 00:00:00';
		$now = strtotime($this->Form->data['Model']['field']);
		$result = $this->Form->dateTime('Model.field', 'DMY', '12', null, array(), false);
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
			array('option' => array('value' => intval(date('i', $now)), 'selected' => 'selected')),
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

		$selected = strtotime('2008-10-26 10:33:00');
		$result = $this->Form->dateTime('Model.field', 'DMY', '12', $selected);
		$this->assertPattern('/<option[^<>]+value="2008"[^<>]+selected="selected"[^>]*>2008<\/option>/', $result);
		$this->assertPattern('/<option[^<>]+value="10"[^<>]+selected="selected"[^>]*>10<\/option>/', $result);
		$this->assertPattern('/<option[^<>]+value="26"[^<>]+selected="selected"[^>]*>26<\/option>/', $result);
		$this->assertPattern('/<option[^<>]+value="10"[^<>]+selected="selected"[^>]*>10<\/option>/', $result);
		$this->assertPattern('/<option[^<>]+value="33"[^<>]+selected="selected"[^>]*>33<\/option>/', $result);

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

		$result = $this->Form->input('ContactTag');
		$expected = array(
			'div' => array('class' => 'input select'),
			'label' => array('for' => 'ContactTagContactTag'),
			'Contact Tag',
			'/label',
			array('input' => array('type' => 'hidden', 'name' => 'data[ContactTag][ContactTag]', 'value' => '')),
			array('select' => array('name' => 'data[ContactTag][ContactTag][]', 'multiple' => 'multiple', 'id' => 'ContactTagContactTag')),
			'/select',
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
		$this->assertPattern('/<option[^<>]+value="2009"[^<>]+selected="selected"[^>]*>2009<\/option>/', $result);
		$this->assertPattern('/<option[^<>]+value="09"[^<>]+selected="selected"[^>]*>September<\/option>/', $result);
		$this->assertPattern('/<option[^<>]+value="03"[^<>]+selected="selected"[^>]*>3<\/option>/', $result);
		$this->assertPattern('/<option[^<>]+value="13"[^<>]+selected="selected"[^>]*>13<\/option>/', $result);
		$this->assertPattern('/<option[^<>]+value="35"[^<>]+selected="selected"[^>]*>35<\/option>/', $result);
	}
/**
 * testFormDateTimeMulti method
 *
 * test multiple datetime element generation
 *
 * @access public
 * @return void
 */
	function testFormDateTimeMulti() {
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
 * testMonth method
 *
 * @access public
 * @return void
 */
	function testMonth() {
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

		$result = $this->Form->month('Model.field', null, array(), true, false);
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
	}
/**
 * testDay method
 *
 * @access public
 * @return void
 */
	function testDay() {
		extract($this->dateRegex);

		$result = $this->Form->day('Model.field', false);
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

		$this->Form->data['Model']['field'] = '2006-10-10 23:12:32';
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

		$this->Form->data['Model']['field'] = '';
		$result = $this->Form->day('Model.field', '10');
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

		$this->Form->data['Model']['field'] = '2006-10-10 23:12:32';
		$result = $this->Form->day('Model.field', true);
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
 * @access public
 * @return void
 */
	function testMinute() {
		extract($this->dateRegex);

		$result = $this->Form->minute('Model.field');
		$expected = array(
			array('select' => array('name' => 'data[Model][field][min]', 'id' => 'ModelFieldMin')),
			array('option' => array('value' => '')),
			'/option',
			array('option' => array('value' => '0')),
			'00',
			'/option',
			array('option' => array('value' => '1')),
			'01',
			'/option',
			array('option' => array('value' => '2')),
			'02',
			'/option',
			$minutesRegex,
			'/select',
		);
		$this->assertTags($result, $expected);

		$this->Form->data['Model']['field'] = '2006-10-10 00:12:32';
		$result = $this->Form->minute('Model.field');
		$expected = array(
			array('select' => array('name' => 'data[Model][field][min]', 'id' => 'ModelFieldMin')),
			array('option' => array('value' => '')),
			'/option',
			array('option' => array('value' => '0')),
			'00',
			'/option',
			array('option' => array('value' => '1')),
			'01',
			'/option',
			array('option' => array('value' => '2')),
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

		$this->Form->data['Model']['field'] = '';
		$result = $this->Form->minute('Model.field', null, array('interval' => 5));
		$expected = array(
			array('select' => array('name' => 'data[Model][field][min]', 'id' => 'ModelFieldMin')),
			array('option' => array('value' => '')),
			'/option',
			array('option' => array('value' => '0')),
			'00',
			'/option',
			array('option' => array('value' => '5')),
			'05',
			'/option',
			array('option' => array('value' => '10')),
			'10',
			'/option',
			$minutesRegex,
			'/select',
		);
		$this->assertTags($result, $expected);

		$this->Form->data['Model']['field'] = '2006-10-10 00:10:32';
		$result = $this->Form->minute('Model.field', null, array('interval' => 5));
		$expected = array(
			array('select' => array('name' => 'data[Model][field][min]', 'id' => 'ModelFieldMin')),
			array('option' => array('value' => '')),
			'/option',
			array('option' => array('value' => '0')),
			'00',
			'/option',
			array('option' => array('value' => '5')),
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
 * @access public
 * @return void
 */
	function testHour() {
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

		$this->Form->data['Model']['field'] = '2006-10-10 00:12:32';
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

		$this->Form->data['Model']['field'] = '';
		$result = $this->Form->hour('Model.field', true, '23');
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

		$this->Form->data['Model']['field'] = '2006-10-10 00:12:32';
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

		unset($this->Form->data['Model']['field']);
		$result = $this->Form->hour('Model.field', true, 'now');
		$thisHour = date('H');
		$optValue = date('G');
		$this->assertPattern('/<option value="' . $thisHour . '" selected="selected">'. $optValue .'<\/option>/', $result);
	}
/**
 * testYear method
 *
 * @access public
 * @return void
 */
	function testYear() {
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

		$this->data['Contact']['published'] = '';
		$result = $this->Form->year('Contact.published', 2006, 2007, null, array('class' => 'year'));
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

		$this->Form->data['Contact']['published'] = '2006-10-10';
		$result = $this->Form->year('Contact.published', 2006, 2007, null, array(), false);
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

		$this->Form->data['Contact']['published'] = '';
		$result = $this->Form->year('Contact.published', 2006, 2007, false);
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

		$this->Form->data['Contact']['published'] = '2006-10-10';
		$result = $this->Form->year('Contact.published', 2006, 2007, false, array(), false);
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

		$this->Form->data['Contact']['published'] = '';
		$result = $this->Form->year('Contact.published', 2006, 2007, 2007);
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

		$this->Form->data['Contact']['published'] = '2006-10-10';
		$result = $this->Form->year('Contact.published', 2006, 2007, 2007, array(), false);
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

		$this->Form->data['Contact']['published'] = '';
		$result = $this->Form->year('Contact.published', 2006, 2008, 2007, array(), false);
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

		$this->Form->data['Contact']['published'] = '2006-10-10';
		$result = $this->Form->year('Contact.published', 2006, 2008, null, array(), false);
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
	}
/**
 * testTextArea method
 *
 * @access public
 * @return void
 */
	function testTextArea() {
		$this->Form->data = array('Model' => array('field' => 'some test data'));
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

		$this->Form->data = array('Model' => array('field' => 'some <strong>test</strong> data with <a href="#">HTML</a> chars'));
		$result = $this->Form->textarea('Model.field');
		$expected = array(
			'textarea' => array('name' => 'data[Model][field]', 'id' => 'ModelField'),
			htmlentities('some <strong>test</strong> data with <a href="#">HTML</a> chars'),
			'/textarea',
		);
		$this->assertTags($result, $expected);

		$this->Form->data = array('Model' => array('field' => 'some <strong>test</strong> data with <a href="#">HTML</a> chars'));
		$result = $this->Form->textarea('Model.field', array('escape' => false));
		$expected = array(
			'textarea' => array('name' => 'data[Model][field]', 'id' => 'ModelField'),
			'some <strong>test</strong> data with <a href="#">HTML</a> chars',
			'/textarea',
		);
		$this->assertTags($result, $expected);

		$this->Form->data['Model']['0']['OtherModel']['field'] = null;
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
 * @access public
 * @return void
 */
	function testTextAreaWithStupidCharacters() {
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
 * @access public
 * @return void
 */
	function testHiddenField() {
		$this->Form->validationErrors['Model']['field'] = 1;
		$this->Form->data['Model']['field'] = 'test';
		$result = $this->Form->hidden('Model.field', array('id' => 'theID'));
		$this->assertTags($result, array('input' => array('type' => 'hidden', 'name' => 'data[Model][field]', 'id' => 'theID', 'value' => 'test')));
	}
/**
 * testFileUploadField method
 *
 * @access public
 * @return void
 */
	function testFileUploadField() {
		$result = $this->Form->file('Model.upload');
		$this->assertTags($result, array('input' => array('type' => 'file', 'name' => 'data[Model][upload]', 'id' => 'ModelUpload', 'value' => '')));

		$this->Form->data['Model.upload'] = array("name" => "", "type" => "", "tmp_name" => "", "error" => 4, "size" => 0);
		$result = $this->Form->input('Model.upload', array('type' => 'file'));
		$expected = array(
			'div' => array('class' => 'input file'),
			'label' => array('for' => 'ModelUpload'),
			'Upload',
			'/label',
			'input' => array('type' => 'file', 'name' => 'data[Model][upload]', 'id' => 'ModelUpload', 'value' => ''),
			'/div'
		);
		$this->assertTags($result, $expected);
	}
/**
 * test File upload input on a model not used in create();
 *
 * @return void
 **/
	function testFileUploadOnOtherModel() {
		ClassRegistry::removeObject('view');
		$controller =& new Controller();
		$controller->name = 'ValidateUsers';
		$controller->uses = array('ValidateUser');
		$controller->constructClasses();
		$view =& new View($controller, true);

		$this->Form->create('ValidateUser', array('type' => 'file'));
		$result = $this->Form->file('ValidateProfile.city');
		$expected = array(
			'input' => array('type' => 'file', 'name' => 'data[ValidateProfile][city]', 'value' => '', 'id' => 'ValidateProfileCity')
		);
		$this->assertTags($result, $expected);
	}
/**
 * testButton method
 *
 * @access public
 * @return void
 */
	function testButton() {
		$result = $this->Form->button('Hi');
		$this->assertTags($result, array('input' => array('type' => 'button', 'value' => 'Hi')));

		$result = $this->Form->button('Clear Form', array('type' => 'clear'));
		$this->assertTags($result, array('input' => array('type' => 'clear', 'value' => 'Clear Form')));

		$result = $this->Form->button('Reset Form', array('type' => 'reset'));
		$this->assertTags($result, array('input' => array('type' => 'reset', 'value' => 'Reset Form')));

		$result = $this->Form->button('Options', array('type' => 'reset', 'name' => 'Post.options'));
		$this->assertTags($result, array('input' => array('type' => 'reset', 'name' => 'data[Post][options]', 'id' => 'PostOptions', 'value' => 'Options')));

		$result = $this->Form->button('Options', array('type' => 'reset', 'name' => 'Post.options', 'id' => 'Opt'));
		$this->assertTags($result, array('input' => array('type' => 'reset', 'name' => 'data[Post][options]', 'id' => 'Opt', 'value' => 'Options')));

		$result = $this->Form->button('Upload Text', array('onClick' => "$('#postAddForm').ajaxSubmit({target: '#postTextUpload', url: '/posts/text'});return false;'", 'escape' => false));
		$this->assertNoPattern('/\&039/', $result);
	}
/**
 * testSubmitButton method
 *
 * @access public
 * @return void
 */
	function testSubmitButton() {
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
	}
/**
 * testFormCreate method
 *
 * @access public
 * @return void
 */
	function testFormCreate() {
		$result = $this->Form->create('Contact');
		$expected = array(
			'form' => array(
				'id' => 'ContactAddForm', 'method' => 'post', 'action' => '/contacts/add/'
			),
			'fieldset' => array('style' => 'preg:/display\s*\:\s*none;\s*/'),
			'input' => array('type' => 'hidden', 'name' => '_method', 'value' => 'POST'),
			'/fieldset'
		);
		$this->assertTags($result, $expected);

		$result = $this->Form->create('Contact', array('type' => 'GET'));
		$expected = array('form' => array(
			'id' => 'ContactAddForm', 'method' => 'get', 'action' => '/contacts/add/'
		));
		$this->assertTags($result, $expected);

		$result = $this->Form->create('Contact', array('type' => 'get'));
		$expected = array('form' => array(
			'id' => 'ContactAddForm', 'method' => 'get', 'action' => '/contacts/add/'
		));
		$this->assertTags($result, $expected);

		$result = $this->Form->create('Contact', array('type' => 'put'));
		$expected = array(
			'form' => array(
				'id' => 'ContactAddForm', 'method' => 'post', 'action' => '/contacts/add/'
			),
			'fieldset' => array('style' => 'preg:/display\s*\:\s*none;\s*/'),
			'input' => array('type' => 'hidden', 'name' => '_method', 'value' => 'PUT'),
			'/fieldset'
		);
		$this->assertTags($result, $expected);

		$this->Form->data['Contact']['id'] = 1;
		$result = $this->Form->create('Contact');
		$expected = array(
			'form' => array(
				'id' => 'ContactEditForm', 'method' => 'post', 'action' => '/contacts/edit/1'
			),
			'fieldset' => array('style' => 'preg:/display\s*\:\s*none;\s*/'),
			'input' => array('type' => 'hidden', 'name' => '_method', 'value' => 'PUT'),
			'/fieldset'
		);
		$this->assertTags($result, $expected);

		$this->Form->data['ContactNonStandardPk']['pk'] = 1;
		$result = $this->Form->create('ContactNonStandardPk');
		$expected = array(
			'form' => array(
				'id' => 'ContactNonStandardPkEditForm', 'method' => 'post',
				'action' => '/contact_non_standard_pks/edit/1'
			),
			'fieldset' => array('style' => 'preg:/display\s*\:\s*none;\s*/'),
			'input' => array('type' => 'hidden', 'name' => '_method', 'value' => 'PUT'),
			'/fieldset'
		);
		$this->assertTags($result, $expected);

		$result = $this->Form->create('Contact', array('id' => 'TestId'));
		$expected = array(
			'form' => array('id' => 'TestId', 'method' => 'post', 'action' => '/contacts/edit/1'),
			'fieldset' => array('style' => 'preg:/display\s*\:\s*none;\s*/'),
			'input' => array('type' => 'hidden', 'name' => '_method', 'value' => 'PUT'),
			'/fieldset'
		);
		$this->assertTags($result, $expected);

		$result = $this->Form->create('User', array('url' => array('action' => 'login')));
		$expected = array(
			'form' => array('id' => 'UserAddForm', 'method' => 'post', 'action' => '/users/login/'),
			'fieldset' => array('style' => 'preg:/display\s*\:\s*none;\s*/'),
			'input' => array('type' => 'hidden', 'name' => '_method', 'value' => 'POST'),
			'/fieldset'
		);
		$this->assertTags($result, $expected);

		$result = $this->Form->create('User', array('action' => 'login'));
		$expected = array(
			'form' => array(
				'id' => 'UserLoginForm', 'method' => 'post', 'action' => '/users/login/'
			),
			'fieldset' => array('style' => 'preg:/display\s*\:\s*none;\s*/'),
			'input' => array('type' => 'hidden', 'name' => '_method', 'value' => 'POST'),
			'/fieldset'
		);
		$this->assertTags($result, $expected);

		$result = $this->Form->create('User', array('url' => '/users/login'));
		$expected = array(
			'form' => array('method' => 'post', 'action' => '/users/login'),
			'fieldset' => array('style' => 'preg:/display\s*\:\s*none;\s*/'),
			'input' => array('type' => 'hidden', 'name' => '_method', 'value' => 'POST'),
			'/fieldset'
		);
		$this->assertTags($result, $expected);

		$this->Form->params['controller'] = 'pages';
		$this->Form->params['models'] = array('User', 'Post');
		$result = $this->Form->create('User', array('action' => 'signup'));
		$expected = array(
			'form' => array(
				'id' => 'UserSignupForm', 'method' => 'post', 'action' => '/users/signup/'
			),
			'fieldset' => array('style' => 'preg:/display\s*\:\s*none;\s*/'),
			'input' => array('type' => 'hidden', 'name' => '_method', 'value' => 'POST'),
			'/fieldset'
		);
		$this->assertTags($result, $expected);

		$this->Form->data = array();
		$this->Form->params['controller'] = 'contacts';
		$this->Form->params['models'] = array('Contact');
		$result = $this->Form->create(array('url' => array('action' => 'index', 'param')));
		$expected = array(
			'form' => array(
				'id' => 'ContactAddForm', 'method' => 'post', 'action' => '/contacts/index/param'
			),
			'fieldset' => array('style' => 'preg:/display\s*\:\s*none;\s*/'),
			'input' => array('type' => 'hidden', 'name' => '_method', 'value' => 'POST'),
			'/fieldset'
		);
		$this->assertTags($result, $expected);
		
	}
/**
 * Test base form url when url param is passed with multiple parameters (&)
 *
 */
	function testFormCreateQuerystringParams() {
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
				'action' => '/controller/action/?param1=value1&amp;param2=value2'
			),
			'fieldset' => array('style' => 'preg:/display\s*\:\s*none;\s*/'),
			'input' => array('type' => 'hidden', 'name' => '_method', 'value' => 'POST'),
			'/fieldset'
		);
		$this->assertTags($result, $expected, true);
	}
/**
 * testGetFormCreate method
 *
 * @access public
 * @return void
 */
	function testGetFormCreate() {
		$result = $this->Form->create('Contact', array('type' => 'get'));
		$this->assertTags($result, array('form' => array(
			'id' => 'ContactAddForm', 'method' => 'get', 'action' => '/contacts/add/'
		)));

		$result = $this->Form->text('Contact.name');
		$this->assertTags($result, array('input' => array(
			'name' => 'name', 'type' => 'text', 'value' => '', 'id' => 'ContactName'
		)));

		$result = $this->Form->password('password');
		$this->assertTags($result, array('input' => array(
			'name' => 'password', 'type' => 'password', 'value' => '', 'id' => 'ContactPassword'
		)));
		$this->assertNoPattern('/<input[^<>]+[^id|name|type|value]=[^<>]*>$/', $result);

		$result = $this->Form->text('user_form');
		$this->assertTags($result, array('input' => array(
			'name' => 'user_form', 'type' => 'text', 'value' => '', 'id' => 'ContactUserForm'
		)));
	}
/**
 * test that datetime() works with GET style forms.
 *
 * @return void
 */
	function testDateTimeWithGetForms() {
		extract($this->dateRegex);
		$this->Form->create('Contact', array('type' => 'get'));
		$result = $this->Form->datetime('created');

		$this->assertPattern('/name="created\[year\]"/', $result, 'year name attribute is wrong.');
		$this->assertPattern('/name="created\[month\]"/', $result, 'month name attribute is wrong.');
		$this->assertPattern('/name="created\[day\]"/', $result, 'day name attribute is wrong.');
		$this->assertPattern('/name="created\[hour\]"/', $result, 'hour name attribute is wrong.');
		$this->assertPattern('/name="created\[min\]"/', $result, 'min name attribute is wrong.');
		$this->assertPattern('/name="created\[meridian\]"/', $result, 'meridian name attribute is wrong.');
	}
/**
 * testEditFormWithData method
 *
 * test auto populating form elements from submitted data.
 *
 * @access public
 * @return void
 */
	function testEditFormWithData() {
		$this->Form->data = array('Person' => array(
			'id'			=> 1,
			'first_name'	=> 'Nate',
			'last_name'		=> 'Abele',
			'email'			=> 'nate@example.com'
		));
		$this->Form->params = array('models' => array('Person'), 'controller'	=> 'people');
		$options = array(1 => 'Nate', 2 => 'Garrett', 3 => 'Larry');

		$this->Form->create();
		$result = $this->Form->select('People.People', $options, null, array('multiple' => true));
		$expected = array(
			'input' => array('type' => 'hidden', 'name' => 'data[People][People]', 'value' => ''),
			'select' => array(
				'name' => 'data[People][People][]', 'multiple' => 'multiple', 'id' => 'PeoplePeople'
			),
			array('option' => array('value' => 1)),
			'Nate',
			'/option',
			array('option' => array('value' => 2)),
			'Garrett',
			'/option',
			array('option' => array('value' => 3)),
			'Larry',
			'/option',
			'/select'
		);
		$this->assertTags($result, $expected);
	}
/**
 * testFormMagicInput method
 *
 * @access public
 * @return void
 */
	function testFormMagicInput() {
		$result = $this->Form->create('Contact');
		$expected = array(
			'form' => array(
				'id' => 'ContactAddForm', 'method' => 'post', 'action' => '/contacts/add/'
			),
			'fieldset' => array('style' => 'preg:/display\s*\:\s*none;\s*/'),
			'input' => array('type' => 'hidden', 'name' => '_method', 'value' => 'POST'),
			'/fieldset'
		);
		$this->assertTags($result, $expected);

		$result = $this->Form->input('name');
		$expected = array(
			'div' => array('class' => 'input text'),
			'label' => array('for' => 'ContactName'),
			'Name',
			'/label',
			'input' => array(
				'type' => 'text', 'name' => 'data[Contact][name]', 'value' => '',
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
				'value' => '', 'id' => 'ContactNonExistingFieldInContactModel'
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
				'value' => '', 'id' => 'AddressStreet'
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
				'value' => '', 'id' => 'AddressNonExistingFieldInModel'
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
				'value' => '', 'id' => 'ContactName', 'maxlength' => '255'
			)
		);
		$this->assertTags($result, $expected);

		$result = $this->Form->input('Contact.non_existing');
		$expected = array(
			'div' => array('class' => 'input text required'),
			'label' => array('for' => 'ContactNonExisting'),
			'Non Existing',
			'/label',
			'input' => array(
				'type' => 'text', 'name' => 'data[Contact][non_existing]',
				'value' => '', 'id' => 'ContactNonExisting'
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
				'value' => '', 'id' => 'ContactImrequired'
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
				'value' => '', 'id' => 'ContactImalsorequired'
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
				'value' => '', 'id' => 'ContactImnotrequired'
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
				'value' => '', 'id' => 'ContactImalsonotrequired'
			),
			'/div'
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
	}
/**
 * testForMagicInputNonExistingNorValidated method
 *
 * @access public
 * @return void
 */
	function testForMagicInputNonExistingNorValidated() {
		$result = $this->Form->create('Contact');
		$expected = array(
			'form' => array(
				'id' => 'ContactAddForm', 'method' => 'post', 'action' => '/contacts/add/'
			),
			'fieldset' => array('style' => 'preg:/display\s*\:\s*none;\s*/'),
			'input' => array('type' => 'hidden', 'name' => '_method', 'value' => 'POST'),
			'/fieldset'
		);
		$this->assertTags($result, $expected);

		$result = $this->Form->input('Contact.non_existing_nor_validated', array('div' => false));
		$expected = array(
			'label' => array('for' => 'ContactNonExistingNorValidated'),
			'Non Existing Nor Validated',
			'/label',
			'input' => array(
				'type' => 'text', 'name' => 'data[Contact][non_existing_nor_validated]',
				'value' => '', 'id' => 'ContactNonExistingNorValidated'
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

		$this->Form->data = array(
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
 * @access public
 * @return void
 */
	function testFormMagicInputLabel() {
		$result = $this->Form->create('Contact');
		$expected = array(
			'form' => array(
				'id' => 'ContactAddForm', 'method' => 'post', 'action' => '/contacts/add/'
			),
			'fieldset' => array('style' => 'preg:/display\s*\:\s*none;\s*/'),
			'input' => array('type' => 'hidden', 'name' => '_method', 'value' => 'POST'),
			'/fieldset'
		);
		$this->assertTags($result, $expected);

		$result = $this->Form->input('Contact.name', array('div' => false, 'label' => false));
		$this->assertTags($result, array('input' => array(
			'name' => 'data[Contact][name]', 'type' => 'text',
			'value' => '', 'id' => 'ContactName', 'maxlength' => '255')
		));

		$result = $this->Form->input('Contact.name', array('div' => false, 'label' => 'My label'));
		$expected = array(
			'label' => array('for' => 'ContactName'),
			'My label',
			'/label',
			'input' => array(
				'type' => 'text', 'name' => 'data[Contact][name]',
				'value' => '', 'id' => 'ContactName', 'maxlength' => '255'
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
				'type' => 'text', 'name' => 'data[Contact][name]', 'value' => '',
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
				'type' => 'text', 'name' => 'data[Contact][name]', 'value' => '',
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
				'value' => '', 'id' => 'my_id', 'maxlength' => '255'
			)
		);
		$this->assertTags($result, $expected);

		$result = $this->Form->input('1.id');
		$this->assertTags($result, array('input' => array(
			'type' => 'hidden', 'name' => 'data[Contact][1][id]',
			'value' => '', 'id' => 'Contact1Id'
		)));

		$result = $this->Form->input("1.name");
		$expected = array(
			'div' => array('class' => 'input text'),
			'label' => array('for' => 'Contact1Name'),
			'Name',
			'/label',
			'input' => array(
				'type' => 'text', 'name' => 'data[Contact][1][name]', 'value' => '',
				'id' => 'Contact1Name', 'maxlength' => '255'
			),
			'/div'
		);
		$this->assertTags($result, $expected);

		$result = $this->Form->input('Contact.1.id');
		$this->assertTags($result, array(
			'input' => array(
				'type' => 'hidden', 'name' => 'data[Contact][1][id]',
				'value' => '', 'id' => 'Contact1Id'
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
				'value' => '', 'id' => 'Model1Name'
			),
			'/div'
		);
		$this->assertTags($result, $expected);
	}
/**
 * testFormEnd method
 *
 * @access public
 * @return void
 */
	function testFormEnd() {
		$this->assertEqual($this->Form->end(), '</form>');

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
 * @access public
 * @return void
 */
	function testMultipleFormWithIdFields() {
		$this->Form->create('UserForm');

		$result = $this->Form->input('id');
		$this->assertTags($result, array('input' => array(
			'type' => 'hidden', 'name' => 'data[UserForm][id]', 'value' => '', 'id' => 'UserFormId'
		)));

		$result = $this->Form->input('ValidateItem.id');
		$this->assertTags($result, array('input' => array(
			'type' => 'hidden', 'name' => 'data[ValidateItem][id]',
			'value' => '', 'id' => 'ValidateItemId'
		)));

		$result = $this->Form->input('ValidateUser.id');
		$this->assertTags($result, array('input' => array(
			'type' => 'hidden', 'name' => 'data[ValidateUser][id]',
			'value' => '', 'id' => 'ValidateUserId'
		)));
	}
/**
 * testDbLessModel method
 *
 * @access public
 * @return void
 */
	function testDbLessModel() {
		$this->Form->create('TestMail');

		$result = $this->Form->input('name');
		$expected = array(
			'div' => array('class' => 'input text'),
			'label' => array('for' => 'TestMailName'),
			'Name',
			'/label',
			'input' => array(
				'name' => 'data[TestMail][name]', 'type' => 'text',
				'value' => '', 'id' => 'TestMailName'
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
				'value' => '', 'id' => 'TestMailName'
			),
			'/div'
		);
		$this->assertTags($result, $expected);
	}
/**
 * testBrokenness method
 *
 * @access public
 * @return void
 */
	function testBrokenness() {
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
			null,
			array('showParents' => true),
			false
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
			'Model.field', $options, null, array('showParents' => true), false
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
}
?>
