<?php
/* SVN FILE: $Id$ */
/**
 * Short description for file.
 *
 * Long description for file
 *
 * PHP versions 4 and 5
 *
 * CakePHP(tm) Tests <https://trac.cakephp.org/wiki/Developement/TestSuite>
 * Copyright 2006-2008, Cake Software Foundation, Inc.
 *								1785 E. Sahara Avenue, Suite 490-204
 *								Las Vegas, Nevada 89104
 *
 *  Licensed under The Open Group Test Suite License
 *  Redistributions of files must retain the above copyright notice.
 *
 * @filesource
 * @copyright		Copyright 2006-2008, Cake Software Foundation, Inc.
 * @link				https://trac.cakephp.org/wiki/Developement/TestSuite CakePHP(tm) Tests
 * @package			cake.tests
 * @subpackage		cake.tests.cases.libs.view.helpers
 * @since			CakePHP(tm) v 1.2.0.4206
 * @version			$Revision$
 * @modifiedby		$LastChangedBy$
 * @lastmodified	$Date$
 * @license			http://www.opensource.org/licenses/opengroup.php The Open Group Test Suite License
 */
if (!defined('CAKEPHP_UNIT_TEST_EXECUTION')) {
	define('CAKEPHP_UNIT_TEST_EXECUTION', 1);
}

uses('view'.DS.'helpers'.DS.'app_helper',
	'class_registry', 'controller'.DS.'controller', 'model'.DS.'model',
	'view'.DS.'helper', 'view'.DS.'helpers'.DS.'html', 'view'.DS.'view',
	'view'.DS.'helpers'.DS.'form');

class ContactTestController extends Controller {
	var $name = 'ContactTest';
	var $uses = null;
}

class Contact extends CakeTestModel {
	var $primaryKey = 'id';
	var $useTable = false;
	var $name = 'Contact';
	var $validate = array('non_existing' => array(), 'idontexist' => array(), 'imnotrequired' => array('required' => false, 'rule' => 'alphaNumeric'));

	function schema() {
		$this->_schema = array(
			'id' => array('type' => 'integer', 'null' => '', 'default' => '', 'length' => '8'),
			'name' => array('type' => 'string', 'null' => '', 'default' => '', 'length' => '255'),
			'email' => array('type' => 'string', 'null' => '', 'default' => '', 'length' => '255'),
			'phone' => array('type' => 'string', 'null' => '', 'default' => '', 'length' => '255'),
			'password' => array('type' => 'string', 'null' => '', 'default' => '', 'length' => '255'),
			'published' => array('type' => 'date', 'null' => true, 'default' => null, 'length' => null),
			'created' => array('type' => 'date', 'null' => '1', 'default' => '', 'length' => ''),
			'updated' => array('type' => 'datetime', 'null' => '1', 'default' => '', 'length' => null)
		);
		return $this->_schema;
	}

	var $hasAndBelongsToMany = array('ContactTag' => array());
}
class ContactTag extends Model {

	var $useTable = false;
	function schema() {
		$this->_schema = array(
			'id' => array('type' => 'integer', 'null' => false, 'default' => '', 'length' => '8'),
			'name' => array('type' => 'string', 'null' => false, 'default' => '', 'length' => '255'),
			'created' => array('type' => 'date', 'null' => true, 'default' => '', 'length' => ''),
			'modified' => array('type' => 'datetime', 'null' => true, 'default' => '', 'length' => null)
		);
		return $this->_schema;
	}
}
class UserForm extends CakeTestModel {
	var $useTable = false;
	var $primaryKey = 'id';
	var $name = 'UserForm';
	var $hasMany = array('OpenidUrl' => array('className' => 'OpenidUrl', 'foreignKey' => 'user_form_id'));

	function schema() {
		$this->_schema = array(
			'id' => array('type' => 'integer', 'null' => '', 'default' => '', 'length' => '8'),
			'published' => array('type' => 'date', 'null' => true, 'default' => null, 'length' => null),
			'other' => array('type' => 'text', 'null' => true, 'default' => null, 'length' => null),
			'stuff' => array('type' => 'string', 'null' => true, 'default' => null, 'length' => 255),
			'something' => array('type' => 'string', 'null' => true, 'default' => null, 'length' => 255),
			'created' => array('type' => 'date', 'null' => '1', 'default' => '', 'length' => ''),
			'updated' => array('type' => 'datetime', 'null' => '1', 'default' => '', 'length' => null)
		);
		return $this->_schema;
	}

}

class OpenidUrl extends CakeTestModel {
	var $useTable = false;
	var $primaryKey = 'id';
	var $name = 'OpenidUrl';
	var $belongsTo = array('UserForm' => array('className' => 'UserForm', 'foreignKey' => 'user_form_id'));
	var $validate = array('openid_not_registered' => array());

	function schema() {
		$this->_schema = array(
			'id' => array('type' => 'integer', 'null' => '', 'default' => '', 'length' => '8'),
			'user_form_id' => array('type' => 'user_form_id', 'null' => '', 'default' => '', 'length' => '8'),
			'url' => array('type' => 'string', 'null' => '', 'default' => '', 'length' => '255'),
		);
		return $this->_schema;
	}

	function beforeValidate() {
		$this->invalidate('openid_not_registered');
		return true;
	}
}

class ValidateUser extends CakeTestModel {
	var $primaryKey = 'id';
	var $useTable = false;
	var $name = 'ValidateUser';
	var $hasOne = array('ValidateProfile' => array('className' => 'ValidateProfile', 'foreignKey' => 'user_id'));

	function schema() {
		$this->_schema = array(
			'id' => array('type' => 'integer', 'null' => '', 'default' => '', 'length' => '8'),
			'name' => array('type' => 'string', 'null' => '', 'default' => '', 'length' => '255'),
			'email' => array('type' => 'string', 'null' => '', 'default' => '', 'length' => '255'),
			'created' => array('type' => 'date', 'null' => '1', 'default' => '', 'length' => ''),
			'updated' => array('type' => 'datetime', 'null' => '1', 'default' => '', 'length' => null)
		);
		return $this->_schema;
	}

	function beforeValidate() {
		$this->invalidate('email');
		return false;
	}
}

class ValidateProfile extends CakeTestModel {
	var $primaryKey = 'id';
	var $useTable = false;
	var $name = 'ValidateProfile';
	var $hasOne = array('ValidateItem' => array('className' => 'ValidateItem', 'foreignKey' => 'profile_id'));
	var $belongsTo = array('ValidateUser' => array('className' => 'ValidateUser', 'foreignKey' => 'user_id'));

	function schema() {
		$this->_schema = array(
			'id' => array('type' => 'integer', 'null' => '', 'default' => '', 'length' => '8'),
			'user_id' => array('type' => 'integer', 'null' => '', 'default' => '', 'length' => '8'),
			'full_name' => array('type' => 'string', 'null' => '', 'default' => '', 'length' => '255'),
			'city' => array('type' => 'string', 'null' => '', 'default' => '', 'length' => '255'),
			'created' => array('type' => 'date', 'null' => '1', 'default' => '', 'length' => ''),
			'updated' => array('type' => 'datetime', 'null' => '1', 'default' => '', 'length' => null)
		);
		return $this->_schema;
	}

	function beforeValidate() {
		$this->invalidate('full_name');
		$this->invalidate('city');
		return false;
	}
}

class ValidateItem extends CakeTestModel {
	var $primaryKey = 'id';
	var $useTable = false;
	var $name = 'ValidateItem';
	var $belongsTo = array('ValidateProfile' => array('className' => 'ValidateProfile', 'foreignKey' => 'profile_id'));

	function schema() {
		$this->_schema = array(
			'id' => array('type' => 'integer', 'null' => '', 'default' => '', 'length' => '8'),
			'profile_id' => array('type' => 'integer', 'null' => '', 'default' => '', 'length' => '8'),
			'name' => array('type' => 'string', 'null' => '', 'default' => '', 'length' => '255'),
			'description' => array('type' => 'string', 'null' => '', 'default' => '', 'length' => '255'),
			'created' => array('type' => 'date', 'null' => '1', 'default' => '', 'length' => ''),
			'updated' => array('type' => 'datetime', 'null' => '1', 'default' => '', 'length' => null)
		);
		return $this->_schema;
	}

	function beforeValidate() {
		$this->invalidate('description');
		return false;
	}
}

class TestMail extends CakeTestModel {
	var $primaryKey = 'id';
	var $useTable = false;
	var $name = 'TestMail';
}
/**
 * Short description for class.
 *
 * @package		cake.tests
 * @subpackage	cake.tests.cases.libs.view.helpers
 */
class FormHelperTest extends CakeTestCase {
	var $fixtures = array(null);

	function setUp() {
		parent::setUp();
		Router::reload();

		$this->Form =& new FormHelper();
		$this->Form->Html =& new HtmlHelper();
		$this->Controller =& new ContactTestController();
		$this->View =& new View($this->Controller);

		ClassRegistry::addObject('view', $view);
		ClassRegistry::addObject('Contact', new Contact());
		ClassRegistry::addObject('OpenidUrl', new OpenidUrl());
		ClassRegistry::addObject('UserForm', new UserForm());
		ClassRegistry::addObject('ValidateItem', new ValidateItem());
		ClassRegistry::addObject('ValidateUser', new ValidateUser());
		ClassRegistry::addObject('ValidateProfile', new ValidateProfile());
	}

	function testFormCreateWithSecurity() {
		$this->Form->params['_Token'] = array('key' => 'testKey');

		$result = $this->Form->create('Contact', array('url' => '/contacts/add'));
		$this->assertPattern('/^<form[^<>]*>.+$/', $result);
		$this->assertPattern('/^<form[^<>]+method="post"[^<>]*>.+$/', $result);
		$this->assertPattern('/^<form[^<>]+action="[^"]+"[^<>]*>.+$/', $result);
		$this->assertNoPattern('/^<form[^<>]+[^id|method|action]=[^<>]*>/', $result);
		$this->assertPattern('/<input[^<>]+type="hidden"[^<>]*\/>/', $result);
		$this->assertPattern('/<input[^<>]+name="data\[__Token\]\[key\]"[^<>]*\/>/', $result);
		$this->assertPattern('/<input[^<>]+value="testKey"[^<>]*\/>/', $result);
		$this->assertPattern('/<input[^<>]+id="\w+"[^<>]*\/>/', $result);
		$this->assertNoPattern('/<input[^<>]+[^type|name|value|id]=[^<>]*>/', $result);

		$result = $this->Form->create('Contact', array('url' => '/contacts/add', 'id' => 'MyForm'));
		$this->assertPattern('/^<form[^<>]+id="MyForm"[^<>]*>.+$/', $result);
		$this->assertPattern('/^<form[^<>]+method="post"[^<>]*>.+$/', $result);
		$this->assertPattern('/^<form[^<>]+action="[^"]+"[^<>]*>.+$/', $result);
		$this->assertNoPattern('/^<form[^<>]+[^id|method|action]=[^<>]*>/', $result);
		$this->assertPattern('/<input[^<>]+type="hidden"[^<>]*\/>/', $result);
		$this->assertPattern('/<input[^<>]+name="data\[__Token\]\[key\]"[^<>]*\/>/', $result);
		$this->assertPattern('/<input[^<>]+value="testKey"[^<>]*\/>/', $result);
		$this->assertPattern('/<input[^<>]+id="\w+"[^<>]*\/>/', $result);
		$this->assertNoPattern('/<input[^<>]+[^type|name|value|id]=[^<>]*>/', $result);
	}

	function testFormSecurityFields() {
		$key = 'testKey';
		$fields = array(
			'Model' => array('password', 'username', 'valid'),
			'_Model' => array('valid' => '0'),
			'__Token' => array('key' => $key)
		);
		$this->Form->params['_Token']['key'] = $key;
		$result = $this->Form->secure($fields);
		$expected = urlencode(Security::hash(serialize($fields) . Configure::read('Security.salt')));
		$this->assertPattern('/'.$expected.'/', $result);
		$this->assertPattern('/input type="hidden" name="data\[__Token\]\[fields\]" value="'.$expected.'"/', $result);
	}

	function testFormSecuredInput() {
		$fields = array(
			'UserForm' => array('0' => 'published', '1' => 'other', '2' => 'something'),
			'_UserForm' => array('stuff' => '', 'something' => '0'),
			'__Token' => array('key' => 'testKey'
		));

		$fields = $this->__sortFields($fields);
		$fieldsKey = urlencode(Security::hash(serialize($fields) . Configure::read('Security.salt')));
		$fields['__Token']['fields'] = $fieldsKey;

		$this->Form->params['_Token']['key'] = 'testKey';

		$result = $this->Form->create('Contact', array('url' => '/contacts/add'));
		$expected = '/^<form method="post" action="\/contacts\/add"(.+)<input type="hidden" name="data\[__Token\]\[key\]" value="testKey"(.+)<\/fieldset>$/';
		$this->assertPattern($expected, $result);

		$result = $this->Form->input('UserForm.published', array('type' => 'text'));
		$expected = '<div class="input"><label for="UserFormPublished">Published</label><input name="data[UserForm][published]" type="text" value="" id="UserFormPublished" /></div>';
		$this->assertEqual($result, $expected);

		$result = $this->Form->input('UserForm.other', array('type' => 'text'));
		$expected = '<div class="input"><label for="UserFormOther">Other</label><input name="data[UserForm][other]" type="text" value="" id="UserFormOther" /></div>';
		$this->assertEqual($result, $expected);

		$result = $this->Form->hidden('UserForm.stuff', array('type' => 'text'));
		$expected = '<input type="hidden" name="data[_UserForm][stuff]" type="text" value="" id="UserFormStuff" />';
		$this->assertEqual($result, $expected);

		$result = $this->Form->input('UserForm.something', array('type' => 'checkbox'));
		$expected = '<div class="input"><input type="hidden" name="data[_UserForm][something]" value="0" id="UserFormSomething_" /><input type="checkbox" name="data[UserForm][something]" value="1" id="UserFormSomething" /><label for="UserFormSomething">Something</label></div>';
		$this->assertEqual($result, $expected);

		$result = $this->Form->secure($this->Form->fields);
		$expected = '/<fieldset style="display:none;"><input type="hidden" name="data\[__Token\]\[fields\]" value="'.$fieldsKey.'" id="(.+)" \/><\/fieldset>$/';
		$this->assertPattern($expected, $result);

		$result = $this->Form->fields;
		$result = $this->__sortFields($result);
		$this->assertEqual($result, $fields);
	}

	function testFormValidationAssociated() {
		$this->UserForm =& ClassRegistry::getObject('UserForm');
		$this->UserForm->OpenidUrl =& ClassRegistry::getObject('OpenidUrl');

		$data = array('UserForm' => array('name' => 'user'), 'OpenidUrl' => array('url' => 'http://www.cakephp.org'));

		$this->assertTrue($this->UserForm->OpenidUrl->create($data));
		$this->assertFalse($this->UserForm->OpenidUrl->validates());

		$result = $this->Form->create('UserForm', array('type' => 'post', 'action' => 'login'));
		$this->assertPattern('/^<form\s+[^<>]+><fieldset\s+[^<>]+><input\s+[^<>]+\/><\/fieldset>$/', $result);
		$this->assertPattern('/^<form[^<>]+id="UserFormLoginForm"[^<>]*>/', $result);
		$this->assertPattern('/^<form[^<>]+method="post"[^<>]*>/', $result);
		$this->assertPattern('/^<form[^<>]+action="\/user_forms\/login\/"[^<>]*>/', $result);
		$this->assertNoPattern('/<form[^<>]+[^id|method|action]=[^<>\/]*>/', $result);

		$this->assertPattern('/<input[^<>]+type="hidden"[^<>]*\/>/', $result);
		$this->assertPattern('/<input[^<>]+name="_method"[^<>]*\/>/', $result);
		$this->assertPattern('/<input[^<>]+value="POST"[^<>]*\/>/', $result);
		$this->assertNoPattern('/<input[^<>]+[^type|name|value]=[^<>\/]*\/>/', $result);

		$expected = array('OpenidUrl' => array('openid_not_registered' => 1));
		$this->assertEqual($this->Form->validationErrors, $expected);

		$result = $this->Form->error('OpenidUrl.openid_not_registered', 'Error, not registered', array('wrap' => false));
		$this->assertEqual($result, 'Error, not registered');

		unset($this->UserForm->OpenidUrl);
		unset($this->UserForm);
	}

	function testFormValidationAssociatedFirstLevel() {
		$this->ValidateUser =& ClassRegistry::getObject('ValidateUser');
		$this->ValidateUser->ValidateProfile =& ClassRegistry::getObject('ValidateProfile');

		$data = array('ValidateUser' => array('name' => 'mariano'), 'ValidateProfile' => array('full_name' => 'Mariano Iglesias'));

		$this->assertTrue($this->ValidateUser->create($data));
		$this->assertFalse($this->ValidateUser->validates());
		$this->assertFalse($this->ValidateUser->ValidateProfile->validates());

		$result = $this->Form->create('ValidateUser', array('type' => 'post', 'action' => 'add'));
		$this->assertPattern('/^<form\s+id="[^"]+"\s+method="post"\s+action="\/validate_users\/add\/"[^>]*><fieldset\s+[^<>]+><input\s+[^<>]+\/><\/fieldset>$/', $result);

		$expected = array(
			'ValidateUser' => array('email' => 1),
			'ValidateProfile' => array('full_name' => 1, 'city' => 1)
		);
		$this->assertEqual($this->Form->validationErrors, $expected);

		unset($this->ValidateUser->ValidateProfile);
		unset($this->ValidateUser);
	}

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
		$this->assertPattern('/^<form\s+id="[^"]+"\s+method="post"\s+action="\/validate_users\/add\/"[^>]*><fieldset\s+[^<>]+><input\s+[^<>]+\/><\/fieldset>$/', $result);

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

	function testFormValidationMultiRecord() {
		$this->Form->validationErrors['Contact'] = array(2 => array('name' => 'This field cannot be left blank'));
		$result = $this->Form->input('Contact.2.name');
		$this->assertPattern('/<div[^<>]*class="error-message"[^<>]*>This field cannot be left blank<\/div>/', $result);

		$this->Form->validationErrors['UserForm'] = array('OpenidUrl' => array('url' => 'You must provide a URL'));
		$this->Form->create('UserForm');
		$result = $this->Form->input('OpenidUrl.url');
		$this->assertPattern('/<div[^<>]*class="error-message"[^<>]*>You must provide a URL<\/div>/', $result);
	}

	function testFormInput() {
		$result = $this->Form->input('Contact.email', array('id' => 'custom'));
		$expected = '<div class="input"><label for="custom">Email</label><input name="data[Contact][email]" type="text" id="custom" value="" /></div>';
		$this->assertEqual($result, $expected);

		$result = $this->Form->hidden('Contact/idontexist');
		$expected = '<input type="hidden" name="data[Contact][idontexist]" value="" id="ContactIdontexist" />';
		$this->assertEqual($result, $expected);

		$result = $this->Form->input('Contact.email', array('type' => 'text'));
		$expected = '<div class="input"><label for="ContactEmail">Email</label><input name="data[Contact][email]" type="text" value="" id="ContactEmail" /></div>';
		$this->assertEqual($result, $expected);

		$result = $this->Form->input('Contact.5.email', array('type' => 'text'));
		$expected = '<div class="input"><label for="Contact5Email">Email</label><input name="data[Contact][5][email]" type="text" value="" id="Contact5Email" /></div>';
		$this->assertEqual($result, $expected);

		$result = $this->Form->input('Contact/password');
		$expected = '<div class="input"><label for="ContactPassword">Password</label><input type="password" name="data[Contact][password]" value="" id="ContactPassword" /></div>';
		$this->assertEqual($result, $expected);

		$result = $this->Form->input('email', array('options' => array('First', 'Second'), 'empty' => true));
		$this->assertPattern('/<select [^<>]+>\s+<option value=""\s*><\/option>\s+<option value="0"/', $result);

		$result = $this->Form->input('Contact.email', array('type' => 'file', 'class' => 'textbox'));
		$this->assertPattern('/class="textbox"/', $result);

		$result = $this->Form->input('Contact.created', array('type' => 'time', 'timeFormat' => 24));
		$result = explode(':', $result);
		$this->assertPattern('/option value="23"/', $result[0]);
		$this->assertNoPattern('/option value="24"/', $result[0]);

		$result = $this->Form->input('Model.field', array('type' => 'time', 'timeFormat' => 12));
		$result = explode(':', $result);
		$this->assertPattern('/option value="12"/', $result[0]);
		$this->assertNoPattern('/option value="13"/', $result[0]);

		$result = $this->Form->input('Model.field', array('type' => 'datetime', 'timeFormat' => 24, 'id' => 'customID'));
		$this->assertPattern('/id="customIDDay"/', $result);
		$this->assertPattern('/id="customIDHour"/', $result);
		$result = explode('</select><select', $result);
		$result = explode(':', $result[1]);
		$this->assertPattern('/option value="23"/', $result[0]);
		$this->assertNoPattern('/option value="24"/', $result[0]);

		$result = $this->Form->input('Model.field', array('type' => 'datetime', 'timeFormat' => 12));
		$result = explode('</select><select', $result);
		$result = explode(':', $result[1]);
		$this->assertPattern('/option value="12"/', $result[0]);
		$this->assertNoPattern('/option value="13"/', $result[0]);

		$this->Form->data = array('Contact' => array('phone' => 'Hello & World > weird chars' ));
		$result = $this->Form->input('Contact.phone');
		$expected = '<div class="input"><label for="ContactPhone">Phone</label><input name="data[Contact][phone]" type="text" value="Hello &amp; World &gt; weird chars" id="ContactPhone" /></div>';
		$this->assertEqual($result, $expected);

		unset($this->Form->data);

		$this->Form->validationErrors['Model']['field'] = 'Badness!';
		$result = $this->Form->input('Model.field');
		$expected = '<div class="input"><label for="ModelField">Field</label><input name="data[Model][field]" type="text" value="" id="ModelField" /></div>';
		$this->assertPattern('/^<div[^<>]+class="input"[^<>]*><label[^<>]+for="ModelField"[^<>]*>Field<\/label><input[^<>]+class="[^"]*form-error"[^<>]+\/><div[^<>]+class="error-message">Badness!<\/div><\/div>$/', $result);

		$result = $this->Form->input('Model.field', array('after' => 'A message to you, Rudy'));
		$this->assertPattern('/^<div[^<>]+class="input"[^<>]*><label[^<>]+for="ModelField"[^<>]*>Field<\/label><input[^<>]+class="[^"]*form-error"[^<>]+\/>A message to you, Rudy<div[^<>]+class="error-message">Badness!<\/div><\/div>$/', $result);
		$this->Form->setEntity(null);
		$this->Form->setEntity('Model.field');

		$result = $this->Form->input('Model.field', array('after' => 'A message to you, Rudy', 'error' => false));
		$this->assertPattern('/^<div[^<>]+class="input"[^<>]*><label[^<>]+for="ModelField"[^<>]*>Field<\/label><input[^<>]+class="[^"]*form-error"[^<>]+\/>A message to you, Rudy<\/div>$/', $result);

		unset($this->Form->validationErrors['Model']['field']);
		$result = $this->Form->input('Model.field', array('after' => 'A message to you, Rudy'));
		$this->assertPattern('/^<div[^<>]+class="input"[^<>]*><label[^<>]+for="ModelField"[^<>]*>Field<\/label><input[^<>]+\/>A message to you, Rudy<\/div>$/', $result);
		$this->assertNoPattern('/form-error/', $result);
		$this->assertNoPattern('/error-message/', $result);

		$this->Form->data = array('Model' => array('user_id' => 'value'));
		$view =& ClassRegistry::getObject('view');
		$view->viewVars['users'] = array('value' => 'good', 'other' => 'bad');
		$result = $this->Form->input('Model.user_id', array('empty' => true));
		$this->assertPattern('/^<div[^<>]+class="input"[^<>]*><label[^<>]+for="ModelUserId"[^<>]*>User<\/label>/', $result);
		$this->assertPattern('/<select [^<>]+>\s+<option value=""\s*><\/option>\s+<option value="value"/', $result);

		$this->Form->data = array('User' => array('User' => array('value')));
		$view =& ClassRegistry::getObject('view');
		$view->viewVars['users'] = array('value' => 'good', 'other' => 'bad');
		$result = $this->Form->input('User.User', array('empty' => true));
		$this->assertPattern('/^<div[^<>]+class="input"[^<>]*><label[^<>]+for="UserUser"[^<>]*>User<\/label>/', $result);
		$this->assertPattern('/<select[^<>]+>\s+<option value=""\s*><\/option>\s+<option value="value"/', $result);
		$this->assertPattern('/<select[^<>]+multiple="multiple"[^<>\/]*>/', $result);
		$this->assertNoPattern('/<select[^<>]+[^(name|id|multipl)]=[^<>\/]*>/', $result);
	}

	function testFormInputs() {
		$this->Form->create('Contact');
		$result = $this->Form->inputs('The Legend');
		$this->assertPattern('/<legend>The Legend<\/legend>/', $result);

		$View = ClassRegistry::getObject('view');
		$this->Form->create('Contact');
		$this->Form->params['prefix'] = 'admin';
		$this->Form->action = 'admin_edit';
		$result = $this->Form->inputs();
		$this->assertPattern('/<legend>Edit Contact<\/legend>/', $result);

		$this->Form->create('Contact');
		$result = $this->Form->inputs(false);
		$this->assertNoPattern('/<fieldset[^<>]*>/', $result);
		$this->assertNoPattern('/<legend>[^<>]*<\/legend>/', $result);

		$this->Form->create('Contact');
		$result = $this->Form->inputs(array('fieldset' => false, 'legend' => false));
		$this->assertNoPattern('/<fieldset[^<>]*>/', $result);
		$this->assertNoPattern('/<legend>[^<>]*<\/legend>/', $result);

		$this->Form->create('Contact');
		$result = $this->Form->inputs(array('fieldset' => true, 'legend' => false));
		$this->assertPattern('/<fieldset[^<>]*>/', $result);
		$this->assertNoPattern('/<legend>[^<>]*<\/legend>/', $result);

		$this->Form->create('Contact');
		$result = $this->Form->inputs(array('fieldset' => false, 'legend' => 'Hello'));
		$this->assertNoPattern('/<fieldset[^<>]*>/', $result);
		$this->assertNoPattern('/<legend>[^<>]*<\/legend>/', $result);

		$this->Form->create('Contact');
		$result = $this->Form->inputs('Hello');
		$this->assertPattern('/^<fieldset><legend[^<>]*>Hello<\/legend>.+<\/fieldset>$/s', $result);

		$this->Form->create('Contact');
		$result = $this->Form->inputs(array('legend' => 'Hello'));
		$this->assertPattern('/^<fieldset><legend[^<>]*>Hello<\/legend>.+<\/fieldset>$/s', $result);
	}

	function testSelectAsCheckbox() {
		$result = $this->Form->select('Model.multi_field', array('first', 'second', 'third'), array(0, 1), array('multiple' => 'checkbox'));
		$this->assertPattern('/^<input type="hidden"[^<>]+ \/>\s*(<div[^<>]+>\<input[^<>]+>\s*<label[^<>]*>\w+<\/label><\/div>\s*){3}$/', $result);
		$this->assertNoPattern('/<label[^<>]*>[^(first|second)]<\/label>/', $result);

		/*$this->assertPattern('/^<input type="checkbox"[^<>]+name="data\[Model\]\[multi_field\]\[\]+value="0"[^<>]+checked="checked">/', $result);
		$this->assertPattern('/<input type="checkbox"[^<>]+name="data\[Model\]\[multi_field\]\[\]+value="1"[^<>]+checked="checked">/', $result);
		$this->assertPattern('/<input type="checkbox"[^<>]+name="data\[Model\]\[multi_field\]\[\]+value="2">third/', $result);
		$this->assertNoPattern('/<input type="checkbox"[^<>]+name="data\[Model\]\[multi_field\]\[\]+value="[^012]"[^<>\/]*>$/', $result);*/
		$this->assertNoPattern('/<select[^<>]*>/', $result);
		$this->assertNoPattern('/<\/select>/', $result);
	}

	function testLabel() {
		$this->Form->text('Person/name');
		$result = $this->Form->label();
		$this->assertEqual($result, '<label for="PersonName">Name</label>');

		$this->Form->text('Person.name');
		$result = $this->Form->label();
		$this->assertEqual($result, '<label for="PersonName">Name</label>');

		$this->Form->text('Person.Name');
		$result = $this->Form->label();
		$this->assertEqual($result, '<label for="PersonName">Name</label>');

		$result = $this->Form->label('Person.first_name');
		$this->assertEqual($result, '<label for="PersonFirstName">First Name</label>');

		$result = $this->Form->label('Person.first_name', 'Your first name');
		$this->assertEqual($result, '<label for="PersonFirstName">Your first name</label>');

		$result = $this->Form->label('Person.first_name', 'Your first name', array('class' => 'my-class'));
		$this->assertPattern('/^<label[^<>]+>Your first name<\/label>$/', $result);
		$this->assertPattern('/^<label[^<>]+for="PersonFirstName"[^<>]*>/', $result);
		$this->assertPattern('/^<label[^<>]+class="my-class"[^<>]*>/', $result);

		$result = $this->Form->label('Person.first_name', 'Your first name', array('class' => 'my-class', 'id' => 'LabelID'));
		$this->assertEqual($result, '<label for="PersonFirstName" class="my-class" id="LabelID">Your first name</label>');

		$result = $this->Form->label('Person.first_name', '');
		$this->assertEqual($result, '<label for="PersonFirstName"></label>');

		$result = $this->Form->label('Person.2.name', '');
		$this->assertEqual($result, '<label for="Person2Name"></label>');
	}

	function testTextbox() {
		$result = $this->Form->text('Model.field');
		$this->assertPattern('/^<input[^<>]+name="data\[Model\]\[field\]"[^<>]+\/>$/', $result);
		$this->assertPattern('/^<input[^<>]+type="text"[^<>]+\/>$/', $result);
		$this->assertPattern('/^<input[^<>]+value=""[^<>]+\/>$/', $result);
		$this->assertPattern('/^<input[^<>]+id="ModelField"[^<>]+\/>$/', $result);
		$this->assertNoPattern('/^<input[^<>]+name="[^<>]+name="[^<>]+\/>$/', $result);
		$this->assertNoPattern('/<input[^<>]+[^type|name|id|value]=[^<>]*>/', $result);

		$result = $this->Form->text('Model.field', array('type' => 'password'));
		$this->assertPattern('/^<input[^<>]+name="data\[Model\]\[field\]"[^<>]+\/>$/', $result);
		$this->assertPattern('/^<input[^<>]+type="password"[^<>]+\/>$/', $result);
		$this->assertPattern('/^<input[^<>]+value=""[^<>]+\/>$/', $result);
		$this->assertPattern('/^<input[^<>]+id="ModelField"[^<>]+\/>$/', $result);
		$this->assertNoPattern('/^<input[^<>]+name="[^<>]+name="[^<>]+\/>$/', $result);
		$this->assertNoPattern('/<input[^<>]+[^type|name|id|value]=[^<>]*>/', $result);

		$result = $this->Form->text('Model.field', array('id' => 'theID'));
		$expected = '<input name="data[Model][field]" type="text" id="theID" value="" />';
		$this->assertEqual($result, $expected);

		$this->Form->data['Model']['text'] = 'test <strong>HTML</strong> values';
		$result = $this->Form->text('Model/text');
		$this->assertPattern('/^<input[^<>]+type="text"[^<>]+\/>$/', $result);
		$this->assertPattern('/^<input[^<>]+name="data\[Model\]\[text\]"[^<>]+\/>$/', $result);
		$this->assertPattern('/^<input[^<>]+value="test &lt;strong&gt;HTML&lt;\/strong&gt; values"[^<>]+\/>$/', $result);
		$this->assertNoPattern('/^<input[^<>]+name="[^<>]+name="[^<>]+\/>$/', $result);
		$this->assertNoPattern('/<input[^<>]+[^type|name|id|value|class]=[^<>]*>/', $result);

		$this->Form->validationErrors['Model']['text'] = 1;
		$this->Form->data['Model']['text'] = 'test';
		$result = $this->Form->text('Model/text', array('id' => 'theID'));
		$this->assertPattern('/^<input[^<>]+name="data\[Model\]\[text\]"[^<>]+id="theID"[^<>]+\/>$/', $result);
		$this->assertPattern('/^<input[^<>]+value="test"[^<>]+class="form-error"[^<>]+\/>$/', $result);
		$this->assertNoPattern('/^<input[^<>]+name="[^<>]+name="[^<>]+\/>$/', $result);
		$this->assertNoPattern('/<input[^<>]+[^type|name|id|value|class]=[^<>]*>/', $result);
	}

	function testDefaultValue() {
		$this->Form->data['Model']['field'] = 'test';
		$result = $this->Form->text('Model.field', array('default' => 'default value'));
		$this->assertPattern('/^<input[^<>]+value="test"[^<>]+\/>$/', $result);

		unset($this->Form->data['Model']['field']);
		$result = $this->Form->text('Model.field', array('default' => 'default value'));
		$this->assertPattern('/^<input[^<>]+value="default value"[^<>]+\/>$/', $result);
	}

	function testFieldError() {
		$this->Form->validationErrors['Model']['field'] = 1;
		$result = $this->Form->error('Model.field');
		$this->assertEqual($result, '<div class="error-message">Error in field Field</div>');

		$result = $this->Form->error('Model.field', null, array('wrap' => false));
		$this->assertEqual($result, 'Error in field Field');

		$this->Form->validationErrors['Model']['field'] = "This field contains invalid input";
		$result = $this->Form->error('Model.field', null, array('wrap' => false));
		$this->assertEqual($result, 'This field contains invalid input');

		$result = $this->Form->error('Model.field', "<strong>Badness!</strong>", array('wrap' => false));
		$this->assertEqual($result, '&lt;strong&gt;Badness!&lt;/strong&gt;');

		$result = $this->Form->error('Model.field', "<strong>Badness!</strong>", array('wrap' => false, 'escape' => true));
		$this->assertEqual($result, '&lt;strong&gt;Badness!&lt;/strong&gt;');

		$result = $this->Form->error('Model.field', "<strong>Badness!</strong>", array('wrap' => false, 'escape' => false));
		$this->assertEqual($result, '<strong>Badness!</strong>');
	}

	function testPassword() {
		$result = $this->Form->password('Model.field');
		$expected = '<input name="data[Model][field]" type="password" value="" id="ModelField" />';
		$this->assertPattern('/^<input[^<>]+name="data\[Model\]\[field\]"[^<>]+\/>$/', $result);
		$this->assertPattern('/^<input[^<>]+type="password"[^<>]+\/>$/', $result);
		$this->assertPattern('/^<input[^<>]+value=""[^<>]+\/>$/', $result);
		$this->assertPattern('/^<input[^<>]+id="ModelField"[^<>]+\/>$/', $result);
		$this->assertNoPattern('/^<input[^<>]+name="[^<>]+name="[^<>]+\/>$/', $result);
		$this->assertNoPattern('/<input[^<>]+[^type|name|id|value]=[^<>]*>/', $result);

		$this->Form->validationErrors['Model']['passwd'] = 1;
		$this->Form->data['Model']['passwd'] = 'test';
		$result = $this->Form->password('Model/passwd', array('id' => 'theID'));
		$this->assertPattern('/^<input[^<>]+name="data\[Model\]\[passwd\]"[^<>]+id="theID"[^<>]+\/>$/', $result);
		$this->assertPattern('/^<input[^<>]+value="test"[^<>]+class="form-error"[^<>]+\/>$/', $result);
		$this->assertNoPattern('/^<input[^<>]+name="[^<>]+name="[^<>]+\/>$/', $result);
		$this->assertNoPattern('/<input[^<>]+[^type|name|id|value|class]=[^<>]*>/', $result);
	}

	function testRadio() {
		$result = $this->Form->radio('Model.field', array('option A'));
		$this->assertPattern('/id="ModelField0"/', $result);
		$this->assertNoPattern('/^<fieldset><legend>Field<\/legend>$/', $result);
		$this->assertPattern('/(<input[^<>]+name="data\[Model\]\[field\]"[^<>]+>.+){1}/', $result);

		$result = $this->Form->radio('Model.field', array('option A', 'option B'));
		$this->assertPattern('/id="ModelField0"/', $result);
		$this->assertPattern('/id="ModelField1"/', $result);
		$this->assertNoPattern('/checked="checked"/', $result);
		$this->assertPattern('/^<fieldset><legend>Field<\/legend><input[^<>]+>(<input[^<>]+><label[^<>]+>option [AB]<\/label>)+<\/fieldset>$/', $result);
		$this->assertPattern('/(<input[^<>]+name="data\[Model\]\[field\]"[^<>]+>.+){2}/', $result);

		$result = $this->Form->radio('Model.field', array('option A', 'option B'), array('separator' => '<br/>'));
		$this->assertPattern('/id="ModelField0"/', $result);
		$this->assertPattern('/id="ModelField1"/', $result);
		$this->assertNoPattern('/checked="checked"/', $result);
		$this->assertPattern('/^<fieldset><legend>Field<\/legend><input[^<>]+><input[^<>]+><label[^<>]+>option A<\/label><br[^<>+]><input[^<>]+><label[^<>]+>option B<\/label><\/fieldset>$/', $result);
		$this->assertPattern('/(<input[^<>]+name="data\[Model\]\[field\]"[^<>]+>.+){2}/', $result);

		$result = $this->Form->radio('Model.field', array('1' => 'Yes', '0' => 'No'), array('value' => '1'));
		$this->assertPattern('/id="ModelField1".*checked="checked"/', $result);
		$this->assertPattern('/id="ModelField0"/', $result);
		$this->assertPattern('/(<input[^<>]+name="data\[Model\]\[field\]"[^<>]+>.+){2}/', $result);

		$result = $this->Form->radio('Model.field', array('1' => 'Yes', '0' => 'No'), array('value' => '0'));
		$this->assertPattern('/id="ModelField1"/', $result);
		$this->assertPattern('/id="ModelField0".*checked="checked"/', $result);
		$this->assertPattern('/(<input[^<>]+name="data\[Model\]\[field\]"[^<>]+>.+){2}/', $result);

		$result = $this->Form->input('Newsletter.subscribe', array('legend' => 'Legend title', 'type' => 'radio', 'options' => array('0' => 'Unsubscribe', '1' => 'Subscribe')));
		$expected = '<div class="input"><fieldset><legend>Legend title</legend><input type="hidden" name="data[Newsletter][subscribe]" value="" id="NewsletterSubscribe_" /><input type="radio" name="data[Newsletter][subscribe]" id="NewsletterSubscribe0" value="0"  /><label for="NewsletterSubscribe0">Unsubscribe</label><input type="radio" name="data[Newsletter][subscribe]" id="NewsletterSubscribe1" value="1"  /><label for="NewsletterSubscribe1">Subscribe</label></fieldset></div>';
		$this->assertEqual($result, $expected);

		$result = $this->Form->input('Newsletter.subscribe', array('legend' => false, 'type' => 'radio', 'options' => array('0' => 'Unsubscribe', '1' => 'Subscribe')));
		$expected = '<div class="input"><input type="hidden" name="data[Newsletter][subscribe]" value="" id="NewsletterSubscribe_" /><input type="radio" name="data[Newsletter][subscribe]" id="NewsletterSubscribe0" value="0"  /><label for="NewsletterSubscribe0">Unsubscribe</label><input type="radio" name="data[Newsletter][subscribe]" id="NewsletterSubscribe1" value="1"  /><label for="NewsletterSubscribe1">Subscribe</label></div>';
		$this->assertEqual($result, $expected);

		$result = $this->Form->input('Newsletter.subscribe', array('legend' => 'Legend title', 'label' => false, 'type' => 'radio', 'options' => array('0' => 'Unsubscribe', '1' => 'Subscribe')));
		$expected = '<div class="input"><fieldset><legend>Legend title</legend><input type="hidden" name="data[Newsletter][subscribe]" value="" id="NewsletterSubscribe_" /><input type="radio" name="data[Newsletter][subscribe]" id="NewsletterSubscribe0" value="0"  />Unsubscribe<input type="radio" name="data[Newsletter][subscribe]" id="NewsletterSubscribe1" value="1"  />Subscribe</fieldset></div>';
		$this->assertEqual($result, $expected);

		$result = $this->Form->input('Newsletter.subscribe', array('legend' => false, 'label' => false, 'type' => 'radio', 'options' => array('0' => 'Unsubscribe', '1' => 'Subscribe')));
		$expected = '<div class="input"><input type="hidden" name="data[Newsletter][subscribe]" value="" id="NewsletterSubscribe_" /><input type="radio" name="data[Newsletter][subscribe]" id="NewsletterSubscribe0" value="0"  />Unsubscribe<input type="radio" name="data[Newsletter][subscribe]" id="NewsletterSubscribe1" value="1"  />Subscribe</div>';
		$this->assertEqual($result, $expected);

		$result = $this->Form->radio('Employee.gender', array('male' => 'Male', 'female' => 'Female'));
		$expected = '<fieldset><legend>Gender</legend><input type="hidden" name="data[Employee][gender]" value="" id="EmployeeGender_" /><input type="radio" name="data[Employee][gender]" id="EmployeeGenderMale" value="male"  /><label for="EmployeeGenderMale">Male</label><input type="radio" name="data[Employee][gender]" id="EmployeeGenderFemale" value="female"  /><label for="EmployeeGenderFemale">Female</label></fieldset>';
		$this->assertEqual($result, $expected);

		$result = $this->Form->radio('Officer.gender', array('male' => 'Male', 'female' => 'Female'));
		$expected = '<fieldset><legend>Gender</legend><input type="hidden" name="data[Officer][gender]" value="" id="OfficerGender_" /><input type="radio" name="data[Officer][gender]" id="OfficerGenderMale" value="male"  /><label for="OfficerGenderMale">Male</label><input type="radio" name="data[Officer][gender]" id="OfficerGenderFemale" value="female"  /><label for="OfficerGenderFemale">Female</label></fieldset>';
		$this->assertEqual($result, $expected);
	}

	function testSelect() {
		$result = $this->Form->select('Model.field', array());
		$this->assertPattern('/^<select [^<>]+>\n<option [^<>]+>/', $result);
		$this->assertPattern('/<option value=""><\/option>/', $result);
		$this->assertPattern('/<\/select>$/', $result);
		$this->assertPattern('/<select[^<>]+name="data\[Model\]\[field\]"[^<>]*>/', $result);
		$this->assertPattern('/<select[^<>]+id="ModelField"[^<>]*>/', $result);
		$this->assertNoPattern('/^<select[^<>]+name="[^<>]+name="[^<>]+>$/', $result);

		$this->Form->data = array('Model' => array('field' => 'value'));
		$result = $this->Form->select('Model.field', array('value' => 'good', 'other' => 'bad'));
		$this->assertPattern('/option value=""/', $result);
		$this->assertPattern('/option value="value"\s+selected="selected"/', $result);
		$this->assertPattern('/option value="other"/', $result);
		$this->assertPattern('/<\/option>\s+<option/', $result);
		$this->assertPattern('/<\/option>\s+<\/select>/', $result);
		$this->assertNoPattern('/option value="other"\s+selected="selected"/', $result);
		$this->assertNoPattern('/<select[^<>]+[^name|id]=[^<>]*>/', $result);
		$this->assertNoPattern('/<option[^<>]+[^value|selected]=[^<>]*>/', $result);

		$this->Form->data = array();
		$result = $this->Form->select('Model.field', array('value' => 'good', 'other' => 'bad'));
		$this->assertPattern('/option value=""/', $result);
		$this->assertPattern('/option value="value"/', $result);
		$this->assertPattern('/option value="other"/', $result);
		$this->assertPattern('/<\/option>\s+<option/', $result);
		$this->assertPattern('/<\/option>\s+<\/select>/', $result);
		$this->assertNoPattern('/option value="field"\s+selected="selected"/', $result);
		$this->assertNoPattern('/option value="other"\s+selected="selected"/', $result);

		$result = $this->Form->select('Model.field', array('first' => 'first "html" <chars>', 'second' => 'value'), null, array(), false);
		$this->assertPattern('/' .
			'<select[^>]*>\s*' .
			'<option\s+value="first"[^>]*>first &quot;html&quot; &lt;chars&gt;<\/option>\s+'.
			'<option\s+value="second"[^>]*>value<\/option>\s+'.
			'<\/select>/i',
		$result);

		$result = $this->Form->select('Model.field', array('first' => 'first "html" <chars>', 'second' => 'value'), null, array('escape' => false), false);
		$this->assertPattern('/' .
			'<select[^>]*>\s*' .
			'<option[^<>\/]+value="first"[^>]*>first "html" <chars><\/option>\s+'.
			'<option[^<>\/]+value="second"[^>]*>value<\/option>\s+'.
			'<\/select>'.
			'/i',
		$result);
	}

	function testSelectMultiple() {
		$result = $this->Form->select('Model.multi_field', array('first', 'second', 'third'), null, array('multiple' => true));
		$this->assertPattern('/^<input type="hidden"[^<>]+ \/>\s*<select[^<>]+name="data\[Model\]\[multi_field\]\[\]"[^<>\/]*>/', $result);
		$this->assertPattern('/^<input type="hidden"[^<>]+ \/>\s*<select[^<>]+id="ModelMultiField"[^<>\/]*>/', $result);
		$this->assertPattern('/^<input type="hidden"[^<>]+ \/>\s*<select[^<>]+multiple="multiple"[^<>\/]*>/', $result);
		$this->assertNoPattern('/^<select[^<>]+[^name|id|multiple]=[^<>\/]*>/', $result);
		$this->assertNoPattern('/option value=""/', $result);
		$this->assertNoPattern('/selected/', $result);
		$this->assertPattern('/<option[^<>]+value="0">first/', $result);
		$this->assertPattern('/<option[^<>]+value="1">second/', $result);
		$this->assertPattern('/<option[^<>]+value="2">third/', $result);
		$this->assertNoPattern('/<option[^<>]+value="[^012]"[^<>\/]*>/', $result);
		$this->assertPattern('/<\/select>$/', $result);

		$result = $this->Form->select('Model.multi_field', array('first', 'second', 'third'), null, array('multiple' => 'multiple'));
		$this->assertPattern('/^<input type="hidden"[^<>]+ \/>\s*<select[^<>]+multiple="multiple"[^<>\/]*>/', $result);
		$this->assertNoPattern('/^<select[^<>]+[^name|id|multiple]=[^<>\/]*>/', $result);

		$result = $this->Form->select('Model.multi_field', array('first', 'second', 'third'), array(0, 1), array('multiple' => true));
		$this->assertPattern('/<option[^<>]+value="0"[^<>]+selected="selected">first/', $result);
		$this->assertPattern('/<option[^<>]+value="1"[^<>]+selected="selected">second/', $result);
		$this->assertPattern('/<option[^<>]+value="2">third/', $result);
		$this->assertNoPattern('/<option[^<>]+value="[^012]"[^<>\/]*>/', $result);
	}

	function testSelectMultipleCheckboxes() {
		$result = $this->Form->select('Model.multi_field', array('first', 'second', 'third'), null, array('multiple' => 'checkbox'));

		$this->assertPattern('/<input[^<>]+\/><label[^<>]+>first<\/label>/', $result);
		$this->assertPattern('/<input[^<>]+name="data\[Model\]\[multi_field\]\[\]"[^<>]+\/><label[^<>]+>first<\/label>/', $result);
		$this->assertPattern('/<input[^<>]+type="checkbox"[^<>]+\/><label[^<>]+>first<\/label>/', $result);
		$this->assertPattern('/<input[^<>]+value="0"[^<>]+\/><label[^<>]+>first<\/label>/', $result);
		$this->assertNoPattern('/<input[^<>]+[^name|type|value]=[^<>\/]*><label[^<>]+>first<\/label>/', $result);
		$this->assertPattern('/<input[^<>]+\/><label[^<>]+>second<\/label>/', $result);
		$this->assertPattern('/<input[^<>]+name="data\[Model\]\[multi_field\]\[\]"[^<>]+\/><label[^<>]+>second<\/label>/', $result);
		$this->assertPattern('/<input[^<>]+type="checkbox"[^<>]+\/><label[^<>]+>second<\/label>/', $result);
		$this->assertPattern('/<input[^<>]+value="1"[^<>]+\/><label[^<>]+>second<\/label>/', $result);
		$this->assertNoPattern('/<input[^<>]+[^name|type|value]=[^<>\/]*><label[^<>]+>second<\/label>/', $result);
		$this->assertPattern('/<input[^<>]+\/><label[^<>]+>third<\/label>/', $result);
		$this->assertPattern('/<input[^<>]+name="data\[Model\]\[multi_field\]\[\]"[^<>]+\/><label[^<>]+>third<\/label>/', $result);
		$this->assertPattern('/<input[^<>]+type="checkbox"[^<>]+\/><label[^<>]+>third<\/label>/', $result);
		$this->assertPattern('/<input[^<>]+value="2"[^<>]+\/><label[^<>]+>third<\/label>/', $result);
		$this->assertNoPattern('/<input[^<>]+[^name|type|value]=[^<>\/]*><label[^<>]+>third<\/label>/', $result);
		$this->assertNoPattern('/<input[^<>]+value="[^012]"[^<>\/]*>/', $result);

		$result = $this->Form->select('Model.multi_field', array('a' => 'first', 'b' => 'second', 'c' => 'third'), null, array('multiple' => 'checkbox'));

		$this->assertPattern('/<input[^<>]+\/><label[^<>]+>first<\/label>/', $result);
		$this->assertPattern('/<input[^<>]+name="data\[Model\]\[multi_field\]\[\]"[^<>]+\/><label[^<>]+>first<\/label>/', $result);
		$this->assertPattern('/<input[^<>]+type="checkbox"[^<>]+\/><label[^<>]+>first<\/label>/', $result);
		$this->assertPattern('/<input[^<>]+value="a"[^<>]+\/><label[^<>]+>first<\/label>/', $result);
		$this->assertNoPattern('/<input[^<>]+[^name|type|value]=[^<>\/]*><label[^<>]+>first<\/label>/', $result);
		$this->assertPattern('/<input[^<>]+\/><label[^<>]+>second<\/label>/', $result);
		$this->assertPattern('/<input[^<>]+name="data\[Model\]\[multi_field\]\[\]"[^<>]+\/><label[^<>]+>second<\/label>/', $result);
		$this->assertPattern('/<input[^<>]+type="checkbox"[^<>]+\/><label[^<>]+>second<\/label>/', $result);
		$this->assertPattern('/<input[^<>]+value="b"[^<>]+\/><label[^<>]+>second<\/label>/', $result);
		$this->assertNoPattern('/<input[^<>]+[^name|type|value]=[^<>\/]*><label[^<>]+>second<\/label>/', $result);
		$this->assertPattern('/<input[^<>]+\/><label[^<>]+>third<\/label>/', $result);
		$this->assertPattern('/<input[^<>]+name="data\[Model\]\[multi_field\]\[\]"[^<>]+\/><label[^<>]+>third<\/label>/', $result);
		$this->assertPattern('/<input[^<>]+type="checkbox"[^<>]+\/><label[^<>]+>third<\/label>/', $result);
		$this->assertPattern('/<input[^<>]+value="c"[^<>]+\/><label[^<>]+>third<\/label>/', $result);
		$this->assertNoPattern('/<input[^<>]+[^name|type|value]=[^<>\/]*><label[^<>]+>third<\/label>/', $result);
		$this->assertNoPattern('/<input[^<>]+value="[^abc]"[^<>\/]*>/', $result);

		$result = $this->Form->select('Model.multi_field', array('1' => 'first'), null, array('multiple' => 'checkbox'));

		$this->assertPattern('/^<input type="hidden"[^<>]+ \/>\s*<div[^<>]+><input[^<>]+\/><label[^<>]+>first<\/label><\/div>$/', $result);
		$this->assertPattern('/^<input type="hidden"[^<>]+ \/>\s*<div[^<>]+><input[^<>]+name="data\[Model\]\[multi_field\]\[\]"[^<>]+\/><label[^<>]+>first<\/label><\/div>$/', $result);
		$this->assertPattern('/^<input type="hidden"[^<>]+ \/>\s*<div[^<>]+><input[^<>]+type="checkbox"[^<>]+\/><label[^<>]+>first<\/label><\/div>$/', $result);
		$this->assertPattern('/^<input type="hidden"[^<>]+ \/>\s*<div[^<>]+><input[^<>]+value="1"[^<>]+\/><label[^<>]+>first<\/label><\/div>$/', $result);
		$this->assertNoPattern('/^<input type="hidden"[^<>]+ \/>\s*<div[^<>]+><input[^<>]+[^name|type|value]=[^<>\/]*><label[^<>]+>first<\/label><\/div>$/', $result);

		$result = $this->Form->select('Model.multi_field', array('2' => 'second'), null, array('multiple' => 'checkbox'));

		$this->assertPattern('/^<input type="hidden"[^<>]+ \/>\s*<div[^<>]+><input[^<>]+\/><label[^<>]+>second<\/label><\/div>$/', $result);
		$this->assertPattern('/^<input type="hidden"[^<>]+ \/>\s*<div[^<>]+><input[^<>]+name="data\[Model\]\[multi_field\]\[\]"[^<>]+\/><label[^<>]+>second<\/label><\/div>$/', $result);
		$this->assertPattern('/^<input type="hidden"[^<>]+ \/>\s*<div[^<>]+><input[^<>]+type="checkbox"[^<>]+\/><label[^<>]+>second<\/label><\/div>$/', $result);
		$this->assertPattern('/^<input type="hidden"[^<>]+ \/>\s*<div[^<>]+><input[^<>]+value="2"[^<>]+\/><label[^<>]+>second<\/label><\/div>$/', $result);
		$this->assertNoPattern('/^<input type="hidden"[^<>]+ \/>\s*<div[^<>]+><input[^<>]+[^name|type|value]=[^<>\/]*><label[^<>]+>second<\/label><\/div>$/', $result);
	}

	function testInputMultipleCheckboxes() {
		$result = $this->Form->input('Model.multi_field', array('options' => array('first', 'second', 'third'), 'multiple' => 'checkbox'));

		$this->assertPattern('/<input[^<>]+\/><label[^<>]+>first<\/label>/', $result);
		$this->assertPattern('/<input[^<>]+name="data\[Model\]\[multi_field\]\[\]"[^<>]+\/><label[^<>]+>first<\/label>/', $result);
		$this->assertPattern('/<input[^<>]+type="checkbox"[^<>]+\/><label[^<>]+>first<\/label>/', $result);
		$this->assertPattern('/<input[^<>]+value="0"[^<>]+\/><label[^<>]+>first<\/label>/', $result);
		$this->assertNoPattern('/<input[^<>]+[^name|type|value]=[^<>\/]*><label[^<>]+>first<\/label>/', $result);
		$this->assertPattern('/<input[^<>]+\/><label[^<>]+>second<\/label>/', $result);
		$this->assertPattern('/<input[^<>]+name="data\[Model\]\[multi_field\]\[\]"[^<>]+\/><label[^<>]+>second<\/label>/', $result);
		$this->assertPattern('/<input[^<>]+type="checkbox"[^<>]+\/><label[^<>]+>second<\/label>/', $result);
		$this->assertPattern('/<input[^<>]+value="1"[^<>]+\/><label[^<>]+>second<\/label>/', $result);
		$this->assertNoPattern('/<input[^<>]+[^name|type|value]=[^<>\/]*><label[^<>]+>second<\/label>/', $result);
		$this->assertPattern('/<input[^<>]+\/><label[^<>]+>third<\/label>/', $result);
		$this->assertPattern('/<input[^<>]+name="data\[Model\]\[multi_field\]\[\]"[^<>]+\/><label[^<>]+>third<\/label>/', $result);
		$this->assertPattern('/<input[^<>]+type="checkbox"[^<>]+\/><label[^<>]+>third<\/label>/', $result);
		$this->assertPattern('/<input[^<>]+value="2"[^<>]+\/><label[^<>]+>third<\/label>/', $result);
		$this->assertNoPattern('/<input[^<>]+[^name|type|value]=[^<>\/]*><label[^<>]+>third<\/label>/', $result);
		$this->assertNoPattern('/<input[^<>]+value="[^012]"[^<>\/]*>/', $result);

		$result = $this->Form->input('Model.multi_field', array('options' => array('a' => 'first', 'b' => 'second', 'c' => 'third'), 'multiple' => 'checkbox'));

		$this->assertPattern('/<input[^<>]+\/><label[^<>]+>first<\/label>/', $result);
		$this->assertPattern('/<input[^<>]+name="data\[Model\]\[multi_field\]\[\]"[^<>]+\/><label[^<>]+>first<\/label>/', $result);
		$this->assertPattern('/<input[^<>]+type="checkbox"[^<>]+\/><label[^<>]+>first<\/label>/', $result);
		$this->assertPattern('/<input[^<>]+value="a"[^<>]+\/><label[^<>]+>first<\/label>/', $result);
		$this->assertNoPattern('/<input[^<>]+[^name|type|value]=[^<>\/]*><label[^<>]+>first<\/label>/', $result);
		$this->assertPattern('/<input[^<>]+\/><label[^<>]+>second<\/label>/', $result);
		$this->assertPattern('/<input[^<>]+name="data\[Model\]\[multi_field\]\[\]"[^<>]+\/><label[^<>]+>second<\/label>/', $result);
		$this->assertPattern('/<input[^<>]+type="checkbox"[^<>]+\/><label[^<>]+>second<\/label>/', $result);
		$this->assertPattern('/<input[^<>]+value="b"[^<>]+\/><label[^<>]+>second<\/label>/', $result);
		$this->assertNoPattern('/<input[^<>]+[^name|type|value]=[^<>\/]*><label[^<>]+>second<\/label>/', $result);
		$this->assertPattern('/<input[^<>]+\/><label[^<>]+>third<\/label>/', $result);
		$this->assertPattern('/<input[^<>]+name="data\[Model\]\[multi_field\]\[\]"[^<>]+\/><label[^<>]+>third<\/label>/', $result);
		$this->assertPattern('/<input[^<>]+type="checkbox"[^<>]+\/><label[^<>]+>third<\/label>/', $result);
		$this->assertPattern('/<input[^<>]+value="c"[^<>]+\/><label[^<>]+>third<\/label>/', $result);
		$this->assertNoPattern('/<input[^<>]+[^name|type|value]=[^<>\/]*><label[^<>]+>third<\/label>/', $result);
		$this->assertNoPattern('/<input[^<>]+value="[^abc]"[^<>\/]*>/', $result);

		$result = $this->Form->input('Model.multi_field', array('options' => array('1' => 'first'), 'multiple' => 'checkbox', 'label' => false, 'div' => false));

		$this->assertPattern('/^<input type="hidden"[^<>]+ \/>\s*<div[^<>]+><input[^<>]+\/><label[^<>]+>first<\/label><\/div>$/', $result);
		$this->assertPattern('/^<input type="hidden"[^<>]+ \/>\s*<div[^<>]+><input[^<>]+name="data\[Model\]\[multi_field\]\[\]"[^<>]+\/><label[^<>]+>first<\/label><\/div>$/', $result);
		$this->assertPattern('/^<input type="hidden"[^<>]+ \/>\s*<div[^<>]+><input[^<>]+type="checkbox"[^<>]+\/><label[^<>]+>first<\/label><\/div>$/', $result);
		$this->assertPattern('/^<input type="hidden"[^<>]+ \/>\s*<div[^<>]+><input[^<>]+value="1"[^<>]+\/><label[^<>]+>first<\/label><\/div>$/', $result);
		$this->assertNoPattern('/^<input type="hidden"[^<>]+ \/>\s*<div[^<>]+><input[^<>]+[^name|type|value]=[^<>\/]*><label[^<>]+>first<\/label><\/div>$/', $result);

		$result = $this->Form->input('Model.multi_field', array('options' => array('2' => 'second'), 'multiple' => 'checkbox', 'label' => false, 'div' => false));

		$this->assertPattern('/^<input type="hidden"[^<>]+ \/>\s*<div[^<>]+><input[^<>]+\/><label[^<>]+>second<\/label><\/div>$/', $result);
		$this->assertPattern('/^<input type="hidden"[^<>]+ \/>\s*<div[^<>]+><input[^<>]+name="data\[Model\]\[multi_field\]\[\]"[^<>]+\/><label[^<>]+>second<\/label><\/div>$/', $result);
		$this->assertPattern('/^<input type="hidden"[^<>]+ \/>\s*<div[^<>]+><input[^<>]+type="checkbox"[^<>]+\/><label[^<>]+>second<\/label><\/div>$/', $result);
		$this->assertPattern('/^<input type="hidden"[^<>]+ \/>\s*<div[^<>]+><input[^<>]+value="2"[^<>]+\/><label[^<>]+>second<\/label><\/div>$/', $result);
		$this->assertNoPattern('/^<input type="hidden"[^<>]+ \/>\s*<div[^<>]+><input[^<>]+[^name|type|value]=[^<>\/]*><label[^<>]+>second<\/label><\/div>$/', $result);
	}

	function testCheckbox() {
		$result = $this->Form->checkbox('Model.field', array('id' => 'theID', 'value' => 'myvalue'));

		$this->assertPattern('/^<input[^<>]+type="hidden"[^<>]+\/><input[^<>]+type="checkbox"[^<>]+\/>$/', $result);
		$this->assertNoPattern('/^<input[^<>]+[^type|name|id|value]=[^<>]*\/><input[^<>]+\/>$/', $result);
		$this->assertNoPattern('/^<input[^<>]+\/><input[^<>]+[^type|name|id|value]=[^<>]*>$/', $result);
		$this->assertPattern('/^<input[^<>]+name="data\[Model\]\[field\]"[^<>]+\/><input[^<>]+\/>$/', $result);
		$this->assertPattern('/^<input[^<>]+type="hidden"[^<>]+\/><input[^<>]+\/>$/', $result);
		$this->assertPattern('/^<input[^<>]+value="0"[^<>]+\/><input[^<>]+\/>$/', $result);
		$this->assertPattern('/^<input[^<>]+id="theID_"[^<>]+\/><input[^<>]+\/>$/', $result);
		$this->assertPattern('/^<input[^<>]+\/><input[^<>]+name="data\[Model\]\[field\]"[^<>]+\/>$/', $result);
		$this->assertPattern('/^<input[^<>]+\/><input[^<>]+type="checkbox"[^<>]+\/>$/', $result);
		$this->assertPattern('/^<input[^<>]+\/><input[^<>]+value="myvalue"[^<>]+\/>$/', $result);
		$this->assertPattern('/^<input[^<>]+\/><input[^<>]+id="theID"[^<>]+\/>$/', $result);

		$this->Form->validationErrors['Model']['field'] = 1;
		$this->Form->data['Model']['field'] = 'myvalue';
		$result = $this->Form->checkbox('Model.field', array('id' => 'theID', 'value' => 'myvalue'));

		$this->assertNoPattern('/^<input[^<>]+[^type|name|id|value]=[^<>]*\/><input[^<>]+\/>$/', $result);
		$this->assertNoPattern('/^<input[^<>]+\/><input[^<>]+[^type|name|id|value|class|checked]=[^<>]*>$/', $result);
		$this->assertPattern('/^<input[^<>]+\/><input[^<>]+class="form-error"[^<>]+\/>$/', $result);
		$this->assertPattern('/^<input[^<>]+\/><input[^<>]+checked="checked"[^<>]+\/>$/', $result);

		$result = $this->Form->checkbox('Model.field', array('value' => 'myvalue'));

		$this->assertNoPattern('/^<input[^<>]+[^type|name|id|value]=[^<>]*\/><input[^<>]+\/>$/', $result);
		$this->assertNoPattern('/^<input[^<>]+\/><input[^<>]+[^type|name|id|value|class|checked]=[^<>]*>$/', $result);
		$this->assertPattern('/^<input[^<>]+id="ModelField_"[^<>]+\/><input[^<>]+\/>$/', $result);
		$this->assertPattern('/^<input[^<>]+value="0"[^<>]+\/><input[^<>]+\/>$/', $result);
		$this->assertPattern('/^<input[^<>]+\/><input[^<>]+id="ModelField"[^<>]+\/>$/', $result);
		$this->assertPattern('/^<input[^<>]+\/><input[^<>]+value="myvalue"[^<>]+\/>$/', $result);
		$this->assertPattern('/^<input[^<>]+\/><input[^<>]+checked="checked"[^<>]+\/>$/', $result);

		$this->Form->data['Model']['field'] = '';
		$result = $this->Form->checkbox('Model.field', array('id' => 'theID'));

		$this->assertNoPattern('/^<input[^<>]+[^type|name|id|value]=[^<>]*\/><input[^<>]+\/>$/', $result);
		$this->assertNoPattern('/^<input[^<>]+\/><input[^<>]+[^type|name|id|value|class|checked]=[^<>]*>$/', $result);
		$this->assertPattern('/^<input[^<>]+id="theID_"[^<>]+\/><input[^<>]+\/>$/', $result);
		$this->assertPattern('/^<input[^<>]+value="0"[^<>]+\/><input[^<>]+\/>$/', $result);
		$this->assertPattern('/^<input[^<>]+\/><input[^<>]+id="theID"[^<>]+\/>$/', $result);
		$this->assertPattern('/^<input[^<>]+\/><input[^<>]+value="1"[^<>]+\/>$/', $result);
		$this->assertNoPattern('/^<input[^<>]+\/><input[^<>]+checked="checked"[^<>]+\/>$/', $result);

		unset($this->Form->validationErrors['Model']['field']);
		$result = $this->Form->checkbox('Model.field', array('value' => 'myvalue'));

		$this->assertNoPattern('/^<input[^<>]+[^type|name|id|value]=[^<>]*\/><input[^<>]+\/>$/', $result);
		$this->assertNoPattern('/^<input[^<>]+\/><input[^<>]+[^type|name|id|value|class|checked]=[^<>]*>$/', $result);
		$this->assertPattern('/^<input[^<>]+id="ModelField_"[^<>]+\/><input[^<>]+\/>$/', $result);
		$this->assertPattern('/^<input[^<>]+value="0"[^<>]+\/><input[^<>]+\/>$/', $result);
		$this->assertPattern('/^<input[^<>]+\/><input[^<>]+id="ModelField"[^<>]+\/>$/', $result);
		$this->assertPattern('/^<input[^<>]+\/><input[^<>]+value="myvalue"[^<>]+\/>$/', $result);
		$this->assertNoPattern('/^<input[^<>]+\/><input[^<>]+checked="checked"[^<>]+\/>$/', $result);

		$result = $this->Form->checkbox('Contact.name', array('value' => 'myvalue'));
		$this->assertEqual($result, '<input type="hidden" name="data[Contact][name]" value="0" id="ContactName_" /><input type="checkbox" name="data[Contact][name]" value="myvalue" id="ContactName" />');

		$result = $this->Form->checkbox('Model.field');
		$this->assertNoPattern('/^<input[^<>]+[^type|name|id|value]=[^<>]*\/><input[^<>]+\/>$/', $result);
		$this->assertNoPattern('/^<input[^<>]+\/><input[^<>]+[^type|name|id|value|class|checked]=[^<>]*>$/', $result);
		$this->assertPattern('/^<input[^<>]+id="ModelField_"[^<>]+\/><input[^<>]+\/>$/', $result);
		$this->assertPattern('/^<input[^<>]+value="0"[^<>]+\/><input[^<>]+\/>$/', $result);
		$this->assertPattern('/^<input[^<>]+\/><input[^<>]+id="ModelField"[^<>]+\/>$/', $result);
		$this->assertPattern('/^<input[^<>]+\/><input[^<>]+value="1"[^<>]+\/>$/', $result);
		$this->assertNoPattern('/^<input[^<>]+\/><input[^<>]+checked="checked"[^<>]+\/>$/', $result);

		$result = $this->Form->checkbox('Model.field', array('checked' => false));
		$this->assertNoPattern('/^<input[^<>]+[^type|name|id|value]=[^<>]*\/><input[^<>]+\/>$/', $result);
		$this->assertNoPattern('/^<input[^<>]+\/><input[^<>]+[^type|name|id|value|class|checked]=[^<>]*>$/', $result);
		$this->assertPattern('/^<input[^<>]+id="ModelField_"[^<>]+\/><input[^<>]+\/>$/', $result);
		$this->assertPattern('/^<input[^<>]+value="0"[^<>]+\/><input[^<>]+\/>$/', $result);
		$this->assertPattern('/^<input[^<>]+\/><input[^<>]+id="ModelField"[^<>]+\/>$/', $result);
		$this->assertPattern('/^<input[^<>]+\/><input[^<>]+value="1"[^<>]+\/>$/', $result);
		$this->assertNoPattern('/^<input[^<>]+\/><input[^<>]+checked="checked"[^<>]+\/>$/', $result);

		$this->Form->validationErrors['Model']['field'] = 1;
		$this->Form->data['Contact']['published'] = 1;
		$result = $this->Form->checkbox('Contact.published', array('id'=>'theID'));

		$this->assertNoPattern('/^<input[^<>]+[^type|name|id|value]=[^<>]*\/><input[^<>]+\/>$/', $result);
		$this->assertNoPattern('/^<input[^<>]+\/><input[^<>]+[^type|name|id|value|class|checked]=[^<>]*>$/', $result);
		$this->assertPattern('/^<input[^<>]+id="theID_"[^<>]+\/><input[^<>]+\/>$/', $result);
		$this->assertPattern('/^<input[^<>]+value="0"[^<>]+\/><input[^<>]+\/>$/', $result);
		$this->assertPattern('/^<input[^<>]+\/><input[^<>]+id="theID"[^<>]+\/>$/', $result);
		$this->assertPattern('/^<input[^<>]+\/><input[^<>]+value="1"[^<>]+\/>$/', $result);
		$this->assertPattern('/^<input[^<>]+\/><input[^<>]+checked="checked"[^<>]+\/>$/', $result);

		$this->Form->validationErrors['Model']['field'] = 1;
		$this->Form->data['Contact']['published'] = 0;
		$result = $this->Form->checkbox('Contact.published', array('id'=>'theID'));

		$this->assertNoPattern('/^<input[^<>]+[^type|name|id|value]=[^<>]*\/><input[^<>]+\/>$/', $result);
		$this->assertNoPattern('/^<input[^<>]+\/><input[^<>]+[^type|name|id|value|class|checked]=[^<>]*>$/', $result);
		$this->assertPattern('/^<input[^<>]+id="theID_"[^<>]+\/><input[^<>]+\/>$/', $result);
		$this->assertPattern('/^<input[^<>]+value="0"[^<>]+\/><input[^<>]+\/>$/', $result);
		$this->assertPattern('/^<input[^<>]+\/><input[^<>]+id="theID"[^<>]+\/>$/', $result);
		$this->assertPattern('/^<input[^<>]+\/><input[^<>]+value="1"[^<>]+\/>$/', $result);
		$this->assertNoPattern('/^<input[^<>]+\/><input[^<>]+checked="checked"[^<>]+\/>$/', $result);
	}

	function testDateTime() {
		$result = $this->Form->dateTime('Contact.date', 'DMY', '12', null, array(), false);
		$this->assertPattern('/<option[^<>]+value="'.date('m').'"[^<>]+selected="selected"[^>]*>/', $result);

		$result = $this->Form->dateTime('Contact.date', 'DMY', '12');
		$this->assertPattern('/<option\s+value=""[^>]*>/', $result);
		$this->assertNoPattern('/<option[^<>]+value=""[^<>]+selected="selected"[^>]*>/', $result);

		$result = $this->Form->dateTime('Contact.date', 'DMY', '12', false);
		$this->assertPattern('/<option\s+value=""[^>]*>/', $result);
		$this->assertNoPattern('/<option[^<>]+selected="selected"[^>]*>/', $result);

		$result = $this->Form->dateTime('Contact.date', 'DMY', '12', '');
		$this->assertPattern('/<option\s+value=""[^>]*>/', $result);
		$this->assertNoPattern('/<option[^<>]+selected="selected"[^>]*>/', $result);

		$result = $this->Form->dateTime('Contact.date', 'DMY', '12', '', array('interval' => 5));
		$this->assertPattern('/<option\s+value=""[^>]*>/', $result);
		$this->assertPattern('/option value="55"/', $result);
		$this->assertNoPattern('/option value="59"/', $result);
		$this->assertNoPattern('/<option[^<>]+selected="selected"[^>]*>/', $result);

		$this->Form->data['Contact']['data'] = null;
		$result = $this->Form->dateTime('Contact.date', 'DMY', '12');
		$this->assertPattern('/<option\s+value=""[^>]*>/', $result);
		$this->assertNoPattern('/<option[^<>]+selected="selected"[^>]*>/', $result);

		$this->Form->data['Model']['field'] = '2008-01-01 00:00:00';
		$result = $this->Form->dateTime('Model.field', 'DMY', '12', null, array(), false);
		$this->assertPattern('/option value="12" selected="selected"/', $result);

		$this->Form->create('Contact');
		$result = $this->Form->input('published');
		$this->assertPattern('/name="data\[Contact\]\[published\]\[month\]"/', $result);
		$this->assertPattern('/name="data\[Contact\]\[published\]\[day\]"/', $result);
		$this->assertPattern('/name="data\[Contact\]\[published\]\[year\]"/', $result);

		$result = $this->Form->input('published2', array('type' => 'date'));
		$this->assertPattern('/name="data\[Contact\]\[published2\]\[month\]"/', $result);
		$this->assertPattern('/name="data\[Contact\]\[published2\]\[day\]"/', $result);
		$this->assertPattern('/name="data\[Contact\]\[published2\]\[year\]"/', $result);

		$result = $this->Form->input('ContactTag');
		$this->assertPattern('/name="data\[ContactTag\]\[ContactTag\]\[\]"/', $result);
	}

	function testFormDateTimeMulti() {
		$result = $this->Form->dateTime('Contact.1.updated');
		$this->assertPattern('/name="data\[Contact\]\[1\]\[updated\]\[day\]"/', $result);
		$this->assertPattern('/id="Contact1UpdatedDay"/', $result);
		$this->assertPattern('/name="data\[Contact\]\[1\]\[updated\]\[month\]"/', $result);
		$this->assertPattern('/id="Contact1UpdatedMonth"/', $result);
		$this->assertPattern('/name="data\[Contact\]\[1\]\[updated\]\[year\]"/', $result);
		$this->assertPattern('/id="Contact1UpdatedYear"/', $result);
		$this->assertPattern('/name="data\[Contact\]\[1\]\[updated\]\[hour\]"/', $result);
		$this->assertPattern('/id="Contact1UpdatedHour"/', $result);
		$this->assertPattern('/name="data\[Contact\]\[1\]\[updated\]\[min\]"/', $result);
		$this->assertPattern('/id="Contact1UpdatedMin"/', $result);
		$this->assertPattern('/name="data\[Contact\]\[1\]\[updated\]\[meridian\]"/', $result);
		$this->assertPattern('/id="Contact1UpdatedMeridian"/', $result);

		$result = $this->Form->dateTime('Contact.2.updated');
		$this->assertPattern('/name="data\[Contact\]\[2\]\[updated\]\[day\]"/', $result);
		$this->assertPattern('/id="Contact2UpdatedDay"/', $result);
		$this->assertPattern('/name="data\[Contact\]\[2\]\[updated\]\[month\]"/', $result);
		$this->assertPattern('/id="Contact2UpdatedMonth"/', $result);
		$this->assertPattern('/name="data\[Contact\]\[2\]\[updated\]\[year\]"/', $result);
		$this->assertPattern('/id="Contact2UpdatedYear"/', $result);
		$this->assertPattern('/name="data\[Contact\]\[2\]\[updated\]\[hour\]"/', $result);
		$this->assertPattern('/id="Contact2UpdatedHour"/', $result);
		$this->assertPattern('/name="data\[Contact\]\[2\]\[updated\]\[min\]"/', $result);
		$this->assertPattern('/id="Contact2UpdatedMin"/', $result);
		$this->assertPattern('/name="data\[Contact\]\[2\]\[updated\]\[meridian\]"/', $result);
		$this->assertPattern('/id="Contact2UpdatedMeridian"/', $result);
	}

	function testMonth() {
		$result = $this->Form->month('Model.field');
		$this->assertPattern('/^<select[^<>]+name="data\[Model\]\[field\]\[month\]"[^<>]*>/', $result);
		$this->assertPattern('/<option\s+value="01"[^>]*>January<\/option>\s+/i', $result);
		$this->assertPattern('/<option\s+value="02"[^>]*>February<\/option>\s+/i', $result);
	}

	function testDay() {
		$result = $this->Form->day('Model.field', false);
		$this->assertPattern('/option value="12"/', $result);
		$this->assertPattern('/option value="13"/', $result);

		$this->Form->data['Model']['field'] = '2006-10-10 23:12:32';
		$result = $this->Form->day('Model.field');
		$this->assertPattern('/option value="10" selected="selected"/', $result);
		$this->assertNoPattern('/option value="32"/', $result);

		$this->Form->data['Model']['field'] = '';
		$result = $this->Form->day('Model.field', '10');
		$this->assertPattern('/option value="10" selected="selected"/', $result);
		$this->assertPattern('/option value="23"/', $result);
		$this->assertPattern('/option value="24"/', $result);

		$this->Form->data['Model']['field'] = '2006-10-10 23:12:32';
		$result = $this->Form->day('Model.field', true);
		$this->assertPattern('/option value="10" selected="selected"/', $result);
		$this->assertPattern('/option value="23"/', $result);

	}

	function testMinute() {
		$result = $this->Form->minute('Model.field');
		$this->assertPattern('/option value="59"/', $result);
		$this->assertNoPattern('/option value="60"/', $result);

		$this->Form->data['Model']['field'] = '2006-10-10 00:12:32';
		$result = $this->Form->minute('Model.field');
		$this->assertPattern('/option value="12" selected="selected"/', $result);

		$this->Form->data['Model']['field'] = '';
		$result = $this->Form->minute('Model.field', null, array('interval' => 5));
		$this->assertPattern('/option value="55"/', $result);
		$this->assertNoPattern('/option value="59"/', $result);

		$this->Form->data['Model']['field'] = '2006-10-10 00:10:32';
		$result = $this->Form->minute('Model.field', null, array('interval' => 5));
		$this->assertPattern('/option value="10" selected="selected"/', $result);
	}

	function testHour() {
		$result = $this->Form->hour('Model.field', false);
		$this->assertPattern('/option value="12"/', $result);
		$this->assertNoPattern('/option value="13"/', $result);

		$this->Form->data['Model']['field'] = '2006-10-10 00:12:32';
		$result = $this->Form->hour('Model.field', false);
		$this->assertPattern('/option value="12" selected="selected"/', $result);
		$this->assertNoPattern('/option value="13"/', $result);

		$this->Form->data['Model']['field'] = '';
		$result = $this->Form->hour('Model.field', true);
		$this->assertPattern('/option value="23"/', $result);
		$this->assertNoPattern('/option value="24"/', $result);

		$this->Form->data['Model']['field'] = '2006-10-10 00:12:32';
		$result = $this->Form->hour('Model.field', true);
		$this->assertPattern('/option value="23"/', $result);
		$this->assertPattern('/option value="00" selected="selected"/', $result);
		$this->assertNoPattern('/option value="24"/', $result);
	}

	function testYear() {
		$result = $this->Form->year('Model.field', 2006, 2007);
		$this->assertPattern('/option value="2006"/', $result);
		$this->assertPattern('/option value="2007"/', $result);
		$this->assertNoPattern('/option value="2005"/', $result);
		$this->assertNoPattern('/option value="2008"/', $result);

		$this->data['Contact']['published'] = '';
		$result = $this->Form->year('Contact.published', 2006, 2007, null, array('class' => 'year'));
		$expecting = "<select name=\"data[Contact][published][year]\" class=\"year\" id=\"ContactPublishedYear\">\n<option value=\"\"></option>\n<option value=\"2007\">2007</option>\n<option value=\"2006\">2006</option>\n</select>";
		$this->assertEqual($result, $expecting);

		$this->Form->data['Contact']['published'] = '2006-10-10';
		$result = $this->Form->year('Contact.published', 2006, 2007, null, array(), false);
		$expecting = "<select name=\"data[Contact][published][year]\" id=\"ContactPublishedYear\">\n<option value=\"2007\">2007</option>\n<option value=\"2006\" selected=\"selected\">2006</option>\n</select>";
		$this->assertEqual($result, $expecting);

		$this->Form->data['Contact']['published'] = '';
		$result = $this->Form->year('Contact.published', 2006, 2007, false);
		$expecting = "<select name=\"data[Contact][published][year]\" id=\"ContactPublishedYear\">\n<option value=\"\"></option>\n<option value=\"2007\">2007</option>\n<option value=\"2006\">2006</option>\n</select>";
		$this->assertEqual($result, $expecting);

		$this->Form->data['Contact']['published'] = '2006-10-10';
		$result = $this->Form->year('Contact.published', 2006, 2007, false, array(), false);
		$expecting = "<select name=\"data[Contact][published][year]\" id=\"ContactPublishedYear\">\n<option value=\"2007\">2007</option>\n<option value=\"2006\" selected=\"selected\">2006</option>\n</select>";
		$this->assertEqual($result, $expecting);

		$this->Form->data['Contact']['published'] = '';
		$result = $this->Form->year('Contact.published', 2006, 2007, 2007);
		$expecting = "<select name=\"data[Contact][published][year]\" id=\"ContactPublishedYear\">\n<option value=\"\"></option>\n<option value=\"2007\" selected=\"selected\">2007</option>\n<option value=\"2006\">2006</option>\n</select>";
		$this->assertEqual($result, $expecting);

		$this->Form->data['Contact']['published'] = '2006-10-10';
		$result = $this->Form->year('Contact.published', 2006, 2007, 2007, array(), false);
		$expecting = "<select name=\"data[Contact][published][year]\" id=\"ContactPublishedYear\">\n<option value=\"2007\" selected=\"selected\">2007</option>\n<option value=\"2006\">2006</option>\n</select>";
		$this->assertEqual($result, $expecting);

		$this->Form->data['Contact']['published'] = '';
		$result = $this->Form->year('Contact.published', 2006, 2008, 2007, array(), false);
		$expecting = "<select name=\"data[Contact][published][year]\" id=\"ContactPublishedYear\">\n<option value=\"2008\">2008</option>\n<option value=\"2007\" selected=\"selected\">2007</option>\n<option value=\"2006\">2006</option>\n</select>";
		$this->assertEqual($result, $expecting);

		$this->Form->data['Contact']['published'] = '2006-10-10';
		$result = $this->Form->year('Contact.published', 2006, 2008, null, array(), false);
		$expecting = "<select name=\"data[Contact][published][year]\" id=\"ContactPublishedYear\">\n<option value=\"2008\">2008</option>\n<option value=\"2007\">2007</option>\n<option value=\"2006\" selected=\"selected\">2006</option>\n</select>";
		$this->assertEqual($result, $expecting);

	}

	function testTextArea() {
		$this->Form->data = array('Model' => array('field' => 'some test data'));
		$result = $this->Form->textarea('Model.field');

		$this->assertPattern('/^<textarea[^<>]+name="data\[Model\]\[field\]"[^<>]*>/', $result);
		$this->assertPattern('/^<textarea[^<>]+id="ModelField"[^<>]*>/', $result);
		$this->assertPattern('/^<textarea[^<>]+>some test data<\/textarea>$/', $result);
		$this->assertNoPattern('/^<textarea[^<>]+name="[^<>]+name="[^<>]+>$/', $result);
		$this->assertNoPattern('/<textarea[^<>]+[^name|id]=[^<>]*>/', $result);

		$result = $this->Form->textarea('Model/tmp');
		$this->assertPattern('/^<textarea[^<>]+name="data\[Model\]\[tmp\]"[^<>]+><\/textarea>/', $result);

		$this->Form->data = array('Model' => array('field' => 'some <strong>test</strong> data with <a href="#">HTML</a> chars'));
		$result = $this->Form->textarea('Model.field');
		$this->assertPattern('/^<textarea[^<>]+name="data\[Model\]\[field\]"[^<>]*>/', $result);
		$this->assertPattern('/^<textarea[^<>]+id="ModelField"[^<>]*>/', $result);
		$this->assertPattern('/^<textarea[^<>]+>some &lt;strong&gt;test&lt;\/strong&gt; data with &lt;a href=&quot;#&quot;&gt;HTML&lt;\/a&gt; chars<\/textarea>$/', $result);
		$this->assertNoPattern('/^<textarea[^<>]+value="[^<>]+>/', $result);
		$this->assertNoPattern('/^<textarea[^<>]+name="[^<>]+name="[^<>]+>$/', $result);
		$this->assertNoPattern('/<textarea[^<>]+[^name|id]=[^<>]*>/', $result);
	}

	function testHiddenField() {
		$this->Form->validationErrors['Model']['field'] = 1;
		$this->Form->data['Model']['field'] = 'test';
		$result = $this->Form->hidden('Model.field', array('id' => 'theID'));
		$this->assertPattern('/^<input[^<>]+type="hidden"[^<>]+\/>$/', $result);
		$this->assertPattern('/^<input[^<>]+name="data\[Model\]\[field\]"[^<>]+id="theID"[^<>]+value="test"[^<>]+\/>$/', $result);
		$this->assertNoPattern('/^<input[^<>]+name="[^<>]+name="[^<>]+\/>$/', $result);
		$this->assertNoPattern('/<input[^<>]+[^type|name|id|value|class]=[^<>]*>/', $result);
	}

	function testFileUploadField() {
		$result = $this->Form->file('Model.upload');
		$this->assertPattern('/^<input type="file"[^<>]+name="data\[Model\]\[upload\]"[^<>]+\/>$/', $result);
		$this->assertPattern('/^<input type="file"[^<>]+value=""[^<>]+\/>$/', $result);
		$this->assertPattern('/^<input type="file"[^<>]+id="ModelUpload"[^<>]+\/>$/', $result);
		$this->assertNoPattern('/^<input[^<>]+name="[^<>]+name="[^<>]+\/>$/', $result);
		$this->assertNoPattern('/<input[^<>]+[^type|name|value|id]=[^<>]*>$/', $result);

		$this->Form->data['Model.upload'] = array("name" => "", "type" => "", "tmp_name" => "", "error" => 4, "size" => 0);
		$result = $this->Form->file('Model.upload');
		$result = $this->Form->input('Model.upload', array('type' => 'file'));

		$this->assertPattern('/<input[^<>]+type="file"[^<>]+\/>/', $result);
		$this->assertPattern('/<input[^<>]+name="data\[Model\]\[upload\]"[^<>]+\/>/', $result);
		$this->assertPattern('/<input[^<>]+value=""[^<>]+\/>/', $result);
		$this->assertPattern('/<input[^<>]+id="ModelUpload"[^<>]+\/>/', $result);
		$this->assertNoPattern('/<input[^<>]+[^(type|name|value|id)]=[^<>]*>$/', $result);
	}

	function testButton() {
		$result = $this->Form->button('Hi');
		$expected = '<input type="button" value="Hi" />';
		$this->assertEqual($result, $expected);

		$result = $this->Form->button('Clear Form', array('type' => 'clear'));
		$expected = '<input type="clear" value="Clear Form" />';
		$this->assertEqual($result, $expected);

		$result = $this->Form->button('Reset Form', array('type' => 'reset'));
		$expected = '<input type="reset" value="Reset Form" />';
		$this->assertEqual($result, $expected);

		$result = $this->Form->button('Options', array('type' => 'reset', 'name' => 'Post.options'));
		$this->assertPattern('/^<input type="reset" [^<>]+ \/>$/', $result);
		$this->assertPattern('/^<input [^<>]+value="Options"[^<>]+\/>$/', $result);
		$this->assertPattern('/^<input [^<>]+name="data\[Post\]\[options\]"[^<>]+\/>$/', $result);
		$this->assertPattern('/^<input [^<>]+id="PostOptions"[^<>]+\/>$/', $result);

		$result = $this->Form->button('Options', array('type' => 'reset', 'name' => 'Post.options', 'id' => 'Opt'));
		$this->assertPattern('/^<input [^<>]+id="Opt"[^<>]+\/>$/', $result);
		$this->assertNoPattern('/^<input [^<>]+id=[^<>]+id=/', $result);
	}

	function testSubmitButton() {
		$result = $this->Form->submit('Test Submit');
		$this->assertPattern('/^<div\s+class="submit"><input type="submit"[^<>]+value="Test Submit"[^<>]+\/><\/div>$/', $result);

		$result = $this->Form->submit('Test Submit', array('class' => 'save', 'div' => false));
		$this->assertPattern('/^<input type="submit"[^<>]+value="Test Submit"[^<>]+\/>$/', $result);
		$this->assertPattern('/^<[^<>]+class="save"[^<>]+\/>$/', $result);
		$this->assertNoPattern('/<input[^<>]+[^type|class|value]=[^<>]*>/', $result);

		$result = $this->Form->submit('Test Submit', array('div' => array('id' => 'SaveButton')));
		$this->assertPattern('/^<div[^<>]+id="SaveButton"[^<>]*><input type="submit"[^<>]+value="Test Submit"[^<>]+\/><\/div>$/', $result);
		$this->assertNoPattern('/<input[^<>]+[^type|value]=[^<>]*>/', $result);

		$result = $this->Form->submit('Next >');
		$this->assertPattern('/^<div\s+class="submit"><input type="submit"[^<>]+value="Next &gt;"[^<>]+\/><\/div>$/', $result);

		$result = $this->Form->submit('Next >', array('escape' => false));
		$this->assertPattern('/^<div\s+class="submit"><input type="submit"[^<>]+value="Next >"[^<>]+\/><\/div>$/', $result);

		$result = $this->Form->submit('http://example.com/cake.power.gif');
		$this->assertEqual('<div class="submit"><input type="image" src="http://example.com/cake.power.gif" /></div>', $result);

		$result = $this->Form->submit('/relative/cake.power.gif');
		$this->assertEqual('<div class="submit"><input type="image" src="relative/cake.power.gif" /></div>', $result);

		$result = $this->Form->submit('cake.power.gif');
		$this->assertEqual('<div class="submit"><input type="image" src="img/cake.power.gif" /></div>', $result);

		$result = $this->Form->submit('Not.an.image');
		$this->assertEqual('<div class="submit"><input type="submit" value="Not.an.image" /></div>', $result);
	}

	function testFormCreate() {
		$result = $this->Form->create('Contact');
		$this->assertPattern('/^<form [^<>]+>/', $result);
		$this->assertPattern('/\s+id="ContactAddForm"/', $result);
		$this->assertPattern('/\s+method="post"/', $result);
		$this->assertPattern('/\s+action="\/contacts\/add\/"/', $result);

		$result = $this->Form->create('Contact', array('type' => 'GET'));
		$this->assertPattern('/^<form [^<>]+method="get"[^<>]+>$/', $result);
		$result = $this->Form->create('Contact', array('type' => 'get'));
		$this->assertPattern('/^<form [^<>]+method="get"[^<>]+>$/', $result);

		$result = $this->Form->create('Contact', array('type' => 'put'));
		$this->assertPattern('/^<form [^<>]+method="post"[^<>]+>/', $result);

		$this->Form->data['Contact']['id'] = 1;
		$result = $this->Form->create('Contact');
		$this->assertPattern('/^<form[^<>]+method="post"[^<>]+>/', $result);
		$this->assertPattern('/^<form[^<>]+id="ContactEditForm"[^<>]+>/', $result);
		$this->assertPattern('/^<form[^<>]+action="\/contacts\/edit\/1"[^<>]*>/', $result);
		$this->assertNoPattern('/^<form[^<>]+[^id|method|action]=[^<>]*>/', $result);

		$result = $this->Form->create('Contact', array('id' => 'TestId'));
		$this->assertPattern('/id="TestId"/', $result);

		$result = $this->Form->create('User', array('url' => array('action' => 'login')));
		$this->assertPattern('/id="UserAddForm"/', $result);
		$this->assertPattern('/action="\/users\/login(\/)?"/', $result);

		$result = $this->Form->create('User', array('action' => 'login'));
		$this->assertPattern('/id="UserLoginForm"/', $result);
		$this->assertPattern('/action="\/users\/login(\/)?"/', $result);

		$result = $this->Form->create('User', array('url' => '/users/login'));
		$this->assertPattern('/method="post"/', $result);
		$this->assertPattern('/action="\/users\/login(\/)?"/', $result);
		$this->assertNoPattern('/^<form[^<>]+[^method|action]=[^<>]*>/', $result);

		$this->Form->params['controller'] = 'pages';
		$this->Form->params['models'] = array('User', 'Post');

		$result = $this->Form->create('User', array('action' => 'signup'));
		$this->assertPattern('/id="UserSignupForm"/', $result);
		$this->assertPattern('/action="\/users\/signup[\/]"/', $result);
	}

	function testGetFormCreate() {
		$result = $this->Form->create('Contact', array('type' => 'get'));
		$this->assertPattern('/^<form [^<>]+>/', $result);
		$this->assertPattern('/\s+id="ContactAddForm"/', $result);
		$this->assertPattern('/\s+method="get"/', $result);
		$this->assertPattern('/\s+action="\/contacts\/add\/"/', $result);
		$this->assertNoPattern('/^<form[^<>]+[^method|action|id]=[^<>]*>/', $result);

		$result = $this->Form->text('Contact.name');
		$this->assertPattern('/^<input[^<>]+name="name"[^<>]+\/>$/', $result);
		$this->assertPattern('/^<input[^<>]+type="text"[^<>]+\/>$/', $result);
		$this->assertPattern('/^<input[^<>]+value=""[^<>]+\/>$/', $result);
		$this->assertPattern('/^<input[^<>]+id="ContactName"[^<>]+\/>$/', $result);
		$this->assertNoPattern('/<input[^<>]+[^id|name|type|value]=[^<>]*>$/', $result);

		$result = $this->Form->password('password');
		$this->assertPattern('/^<input[^<>]+name="password"[^<>]+\/>$/', $result);
		$this->assertPattern('/^<input[^<>]+type="password"[^<>]+\/>$/', $result);
		$this->assertPattern('/^<input[^<>]+value=""[^<>]+\/>$/', $result);
		$this->assertPattern('/^<input[^<>]+id="ContactPassword"[^<>]+\/>$/', $result);
		$this->assertNoPattern('/<input[^<>]+[^id|name|type|value]=[^<>]*>$/', $result);

		$result = $this->Form->text('user_form');
		$this->assertPattern('/^<input[^<>]+name="user_form"[^<>]+\/>$/', $result);
		$this->assertPattern('/^<input[^<>]+type="text"[^<>]+\/>$/', $result);
		$this->assertPattern('/^<input[^<>]+value=""[^<>]+\/>$/', $result);
		$this->assertPattern('/^<input[^<>]+id="ContactUserForm"[^<>]+\/>$/', $result);
		$this->assertNoPattern('/<input[^<>]+[^id|name|type|value]=[^<>]*>$/', $result);
	}

	function testEditFormWithData() {
		$this->Form->data = array('Person' => array(
			'id'			=> 1,
			'first_name'	=> 'Nate',
			'last_name'		=> 'Abele',
			'email'			=> 'nate@cakephp.org'
		));
		$this->Form->params = array('models' => array('Person'), 'controller'	=> 'people');
		$options = array(1 => 'Nate', 2 => 'Garrett', 3 => 'Larry');

		$this->Form->create();
		$result = $this->Form->select('People.People', $options, null, array('multiple' => true));
		$this->assertPattern('/^<input type="hidden"[^<>]+ \/>\s*<select[^<>]+>\s*(<option[^<>]+>.+<\/option>\s*){3}<\/select>$/', $result);
		$this->assertPattern('/^<input type="hidden"[^<>]+ \/>\s*<select[^<>]+name="data\[People\]\[People\]\[\]"[^<>]*>/', $result);
		$this->assertPattern('/^<input type="hidden"[^<>]+ \/>\s*<select[^<>]+multiple="multiple"[^<>]*>/', $result);
		$this->assertPattern('/^<input type="hidden"[^<>]+ \/>\s*<select[^<>]+id="PeoplePeople"[^<>]*>/', $result);
		$this->assertNoPattern('/<select[^<>]+[^id|name|multiple]=[^<>]*>$/', $result);
	}

	function testFormMagicInput() {
		$result = $this->Form->create('Contact');
		$this->assertPattern('/^<form\s+id="ContactAddForm"\s+method="post"\s+action="\/contacts\/add\/"\s*><fieldset[^<>]+><input\s+[^<>]+\/><\/fieldset>$/', $result);
		$this->assertNoPattern('/^<form[^<>]+[^id|method|action]=[^<>]*>/', $result);

		$result = $this->Form->input('name');
		$this->assertPattern('/^<div[^<>]+><label[^<>]+>Name<\/label><input [^<>]+ \/><\/div>$/', $result);
		$this->assertPattern('/^<div[^<>]+class="input">/', $result);
		$this->assertPattern('/<label[^<>]+for="ContactName">/', $result);
		$this->assertPattern('/<input[^<>]+name="data\[Contact\]\[name\]"[^<>]+\/>/', $result);
		$this->assertPattern('/<input[^<>]+type="text"[^<>]+\/>/', $result);
		$this->assertPattern('/<input[^<>]+maxlength="255"[^<>]+\/>/', $result);
		$this->assertPattern('/<input[^<>]+value=""[^<>]+\/>/', $result);
		$this->assertPattern('/<input[^<>]+id="ContactName"[^<>]+\/>/', $result);
		$this->assertNoPattern('/<input[^<>]+[^id|maxlength|name|type|value]=[^<>]*>/', $result);

		$result = $this->Form->input('Address.street');
		$this->assertPattern('/^<div\s+[^<>]+><label\s+[^<>]+>[^<>]+<\/label><input\s+[^<>]+\/><\/div>$/', $result);
		$this->assertPattern('/<div\s+class="input">/', $result);
		$this->assertPattern('/<label\s+for="AddressStreet">Street<\/label>/', $result);
		$this->assertPattern('/<input[^<>]+name="data\[Address\]\[street\]"[^<>]+\/>/', $result);
		$this->assertPattern('/<input[^<>]+type="text"[^<>]+\/>/', $result);
		$this->assertPattern('/<input[^<>]+value=""[^<>]+\/>/', $result);
		$this->assertPattern('/<input[^<>]+id="AddressStreet"[^<>]+\/>/', $result);
		$this->assertNoPattern('/<input[^<>]+[^id|name|type|value]=[^<>]*>/', $result);

		$result = $this->Form->input('name', array('div' => false));
		$this->assertPattern('/^<label\s+[^<>]+>Name<\/label><input\s+[^<>]+\/>$/', $result);
		$this->assertPattern('/<label[^<>]+for="ContactName"[^<>]*>Name<\/label>/', $result);
		$this->assertNoPattern('/<label[^<>]+[^for]=[^<>]*>/', $result);
		$this->assertPattern('/<input[^<>]+name="data\[Contact\]\[name\]"[^<>]*\/>$/', $result);
		$this->assertPattern('/<input[^<>]+type="text"[^<>]*\/>$/', $result);
		$this->assertPattern('/<input[^<>]+maxlength="255"[^<>]*\/>$/', $result);
		$this->assertPattern('/<input[^<>]+value=""[^<>]*\/>$/', $result);
		$this->assertPattern('/<input[^<>]+id="ContactName"[^<>]*\/>$/', $result);
		$this->assertNoPattern('/<input[^<>]+[^id|maxlength|name|type|value]=[^<>]*>/', $result);

		$result = $this->Form->input('Contact.non_existing');
		$this->assertPattern('/^<div class="input required">' .
							 '<label for="ContactNonExisting">Non Existing<\/label>' .
							 '<input name="data\[Contact\]\[non_existing\]" type="text" value="" id="ContactNonExisting" \/>'.
							 '<\/div>$/', $result);

		$result = $this->Form->input('Contact.imnotrequired');
		$this->assertPattern('/^<div class="input">' .
							 '<label for="ContactImnotrequired">Imnotrequired<\/label>' .
							 '<input name="data\[Contact\]\[imnotrequired\]" type="text" value="" id="ContactImnotrequired" \/>'.
							 '<\/div>$/', $result);

		$result = $this->Form->input('Contact.published', array('div' => false));
		$this->assertPattern('/^<label for="ContactPublishedMonth">Published<\/label>' .
							 '<select name="data\[Contact\]\[published\]\[month\]"\s+id="ContactPublishedMonth">/', $result);

		$result = $this->Form->input('Contact.updated', array('div' => false));
		$this->assertPattern('/^<label for="ContactUpdatedMonth">Updated<\/label>' .
												 '<select name="data\[Contact\]\[updated\]\[month\]"\s+id="ContactUpdatedMonth">/', $result);
	}

	function testForMagicInputNonExistingNorValidated() {
		$result = $this->Form->create('Contact');
		$this->assertPattern('/^<form\s+id="ContactAddForm"\s+method="post"\s+action="\/contacts\/add\/"\s*><fieldset[^<>]+><input\s+[^<>]+\/><\/fieldset>$/', $result);
		$this->assertNoPattern('/^<form[^<>]+[^id|method|action]=[^<>]*>/', $result);

		$result = $this->Form->input('Contact.non_existing_nor_validated', array('div' => false));
		$this->assertPattern('/^<label\s+[^<>]+>Non Existing Nor Validated<\/label><input\s+[^<>]+\/>$/', $result);
		$this->assertPattern('/<label[^<>]+for="ContactNonExistingNorValidated"[^<>]*>[^<>]+<\/label>/', $result);
		$this->assertNoPattern('/<label[^<>]+[^for]=[^<>]*>/', $result);
		$this->assertPattern('/<input[^<>]+name="data\[Contact\]\[non_existing_nor_validated\]"[^<>]*\/>$/', $result);
		$this->assertPattern('/<input[^<>]+type="text"[^<>]*\/>$/', $result);
		$this->assertPattern('/<input[^<>]+value=""[^<>]*\/>$/', $result);
		$this->assertPattern('/<input[^<>]+id="ContactNonExistingNorValidated"[^<>]*\/>$/', $result);

		$result = $this->Form->input('Contact.non_existing_nor_validated', array('div' => false, 'value' => 'my value'));
		$this->assertPattern('/^<label\s+[^<>]+>Non Existing Nor Validated<\/label><input\s+[^<>]+\/>$/', $result);
		$this->assertPattern('/<label[^<>]+for="ContactNonExistingNorValidated"[^<>]*>[^<>]+<\/label>/', $result);
		$this->assertNoPattern('/<label[^<>]+[^for]=[^<>]*>/', $result);
		$this->assertPattern('/<input[^<>]+name="data\[Contact\]\[non_existing_nor_validated\]"[^<>]*\/>$/', $result);
		$this->assertPattern('/<input[^<>]+type="text"[^<>]*\/>$/', $result);
		$this->assertPattern('/<input[^<>]+value="my value"[^<>]*\/>$/', $result);
		$this->assertPattern('/<input[^<>]+id="ContactNonExistingNorValidated"[^<>]*\/>$/', $result);

		$this->Form->data = array('Contact' => array('non_existing_nor_validated' => 'CakePHP magic' ));
		$result = $this->Form->input('Contact.non_existing_nor_validated', array('div' => false));
		$this->assertPattern('/^<label\s+[^<>]+>Non Existing Nor Validated<\/label><input\s+[^<>]+\/>$/', $result);
		$this->assertPattern('/<label[^<>]+for="ContactNonExistingNorValidated"[^<>]*>[^<>]+<\/label>/', $result);
		$this->assertNoPattern('/<label[^<>]+[^for]=[^<>]*>/', $result);
		$this->assertPattern('/<input[^<>]+name="data\[Contact\]\[non_existing_nor_validated\]"[^<>]*\/>$/', $result);
		$this->assertPattern('/<input[^<>]+type="text"[^<>]*\/>$/', $result);
		$this->assertPattern('/<input[^<>]+value="CakePHP magic"[^<>]*\/>$/', $result);
		$this->assertPattern('/<input[^<>]+id="ContactNonExistingNorValidated"[^<>]*\/>$/', $result);
	}

	function testFormMagicInputLabel() {
		$result = $this->Form->create('Contact');
		$this->assertPattern('/^<form\s+id="ContactAddForm"\s+method="post"\s+action="\/contacts\/add\/"\s*><fieldset[^<>]+><input\s+[^<>]+\/><\/fieldset>$/', $result);

		$result = $this->Form->input('Contact.name', array('div' => false, 'label' => false));
		$this->assertPattern('/^<input name="data\[Contact\]\[name\]" type="text" maxlength="255" value="" id="ContactName" \/>$/', $result);

		$result = $this->Form->input('Contact.name', array('div' => false, 'label' => 'My label'));
		$this->assertPattern('/^<label for="ContactName">My label<\/label>' .
												 '<input name="data\[Contact\]\[name\]" type="text" maxlength="255" value="" id="ContactName" \/>$/', $result);

		$result = $this->Form->input('Contact.name', array('div' => false, 'label' => array('class' => 'mandatory')));
		$this->assertPattern('/^<label[^<>]+>Name<\/label><input [^<>]+ \/>$/', $result);
		$this->assertPattern('/<label[^<>]+for="ContactName"[^<>]*>/', $result);
		$this->assertPattern('/<label[^<>]+class="mandatory"[^<>]*>/', $result);
		$this->assertPattern('/<input[^<>]+name="data\[Contact\]\[name\]"[^<>]+\/>/', $result);
		$this->assertPattern('/<input[^<>]+type="text"[^<>]+\/>/', $result);
		$this->assertPattern('/<input[^<>]+maxlength="255"[^<>]+\/>/', $result);
		$this->assertPattern('/<input[^<>]+value=""[^<>]+\/>/', $result);
		$this->assertPattern('/<input[^<>]+id="ContactName"[^<>]+\/>/', $result);
		$this->assertNoPattern('/<input[^<>]+[^name|type|maxlength|value|id]=[^<>]*>/', $result);
		$this->assertNoPattern('/^<label[^<>]+[^for|class]=[^<>]*>/', $result);

		$result = $this->Form->input('Contact.name', array('div' => false, 'label' => array('class' => 'mandatory', 'text' => 'My label')));
		$this->assertPattern('/^<label[^<>]+>My label<\/label><input[^<>]+\/>$/', $result);
		$this->assertPattern('/<label[^<>]+for="ContactName"[^<>]*>/', $result);
		$this->assertPattern('/<label[^<>]+class="mandatory"[^<>]*>/', $result);
		$this->assertNoPattern('/^<label[^<>]+[^for|class]=[^<>]*>/', $result);

		$result = $this->Form->input('Contact.name', array('div' => false, 'id' => 'my_id', 'label' => array('for' => 'my_id')));
		$this->assertPattern('/^<label for="my_id">Name<\/label>' .
												 '<input name="data\[Contact\]\[name\]" type="text" id="my_id" maxlength="255" value="" \/>$/', $result);

		$result = $this->Form->input('1.id');
		$this->assertPattern('/<input[^<>]+id="Contact1Id"[^<>]*>/', $result);

		$result = $this->Form->input("1.name");
		$this->assertPattern('/<label\s+[^<>]+>Name<\/label[^<>]*>/', $result);
		$this->assertPattern('/<label[^<>]+for="Contact1Name"[^<>]*>/', $result);
		$this->assertPattern('/<input[^<>]+id="Contact1Name"[^<>]*>/', $result);

		$result = $this->Form->input("Model.1.id");
		$this->assertPattern('/<input[^<>]+id="Model1Id"[^<>]*>/', $result);

		$result = $this->Form->input("Model.1.name");
		$this->assertPattern('/<label\s+[^<>]+>Name<\/label[^<>]*>/', $result);
		$this->assertPattern('/<label[^<>]+for="Model1Name"[^<>]*>/', $result);
		$this->assertPattern('/<input[^<>]+id="Model1Name"[^<>]*>/', $result);
	}

	function testFormEnd() {
		$this->assertEqual($this->Form->end(), '</form>');

		$result = $this->Form->end('save');
		$this->assertEqual($result, '<div class="submit"><input type="submit" value="save" /></div></form>');

		$result = $this->Form->end(array('label' => 'save'));
		$this->assertEqual($result, '<div class="submit"><input type="submit" value="save" /></div></form>');

		$result = $this->Form->end(array('label' => 'save', 'name' => 'Whatever'));
		$this->assertEqual($result, '<div class="submit"><input type="submit" name="Whatever" value="save" /></div></form>');

		$result = $this->Form->end(array('name' => 'Whatever'));
		$this->assertEqual($result, '<div class="submit"><input type="submit" name="Whatever" value="Submit" /></div></form>');

		$result = $this->Form->end(array('label' => 'save', 'name' => 'Whatever', 'div' => 'good'));
		$this->assertEqual($result, '<div class="good"><input type="submit" name="Whatever" value="save" /></div></form>');

		$result = $this->Form->end(array('label' => 'save', 'name' => 'Whatever', 'div' => array('class' => 'good')));
		$this->assertEqual($result, '<div class="good"><input type="submit" name="Whatever" value="save" /></div></form>');
	}

	function testMultipleFormWithIdFields() {
		$this->Form->create('UserForm');

		$result = $this->Form->input('id');
		$this->assertEqual($result, '<input type="hidden" name="data[UserForm][id]" value="" id="UserFormId" />');

		$result = $this->Form->input('My.id');
		$this->assertEqual($result, '<input type="hidden" name="data[My][id]" value="" id="MyId" />');

		$result = $this->Form->input('MyOther.id');
		$this->assertEqual($result, '<input type="hidden" name="data[MyOther][id]" value="" id="MyOtherId" />');
	}

	function testDbLessModel() {
		$this->Form->create('TestMail');

		$result = $this->Form->input('name');
		$this->assertEqual($result, '<div class="input"><label for="TestMailName">Name</label><input name="data[TestMail][name]" type="text" value="" id="TestMailName" /></div>');

		ClassRegistry::init('TestMail');
		$this->Form->create('TestMail');
		$result = $this->Form->input('name');
		$this->assertEqual($result, '<div class="input"><label for="TestMailName">Name</label><input name="data[TestMail][name]" type="text" value="" id="TestMailName" /></div>');
	}

	function tearDown() {
		ClassRegistry::removeObject('view');
		ClassRegistry::removeObject('Contact');
		ClassRegistry::removeObject('ContactTag');
		ClassRegistry::removeObject('OpenidUrl');
		ClassRegistry::removeObject('UserForm');
		ClassRegistry::removeObject('ValidateItem');
		ClassRegistry::removeObject('ValidateUser');
		ClassRegistry::removeObject('ValidateProfile');
		unset($this->Form->Html, $this->Form, $this->Controller, $this->View);
	}

	function __sortFields($fields) {
		foreach ($fields as $key => $value) {
			if ($key{0} !==  '_') {
				sort($fields[$key]);
			}
		}
		ksort($fields);
		return $fields;
	}
}

?>