<?php
/* SVN FILE: $Id$ */
/**
 * Short description for file.
 *
 * Long description for file
 *
 * PHP versions 4 and 5
 *
 * CakePHP Test Suite <https://trac.cakephp.org/wiki/Developement/TestSuite>
 * Copyright (c) 2006, Larry E. Masters Shorewood, IL. 60431
 * Author(s): Larry E. Masters aka PhpNut <phpnut@gmail.com>
 *
 *  Licensed under The Open Group Test Suite License
 *  Redistributions of files must retain the above copyright notice.
 *
 * @filesource
 * @author       Larry E. Masters aka PhpNut <phpnut@gmail.com>
 * @copyright    Copyright (c) 2006, Larry E. Masters Shorewood, IL. 60431
 * @link         http://www.phpnut.com/projects/
 * @package      test_suite
 * @subpackage   test_suite.cases.app
 * @since        CakePHP Test Suite v 1.0.0.0
 * @version      $Revision$
 * @modifiedby   $LastChangedBy$
 * @lastmodified $Date$
 * @license      http://www.opensource.org/licenses/opengroup.php The Open Group Test Suite License
 */
	if (!defined('CAKEPHP_UNIT_TEST_EXECUTION')) {
		define('CAKEPHP_UNIT_TEST_EXECUTION', 1);
	}

	require_once LIBS.'../app_helper.php';
	require_once LIBS.'class_registry.php';
	require_once LIBS.DS.'view'.DS.'view.php';
	require_once LIBS.DS.'view'.DS.'helper.php';
	require_once LIBS.DS.'view'.DS.'helpers'.DS.'html.php';
	require_once LIBS.DS.'view'.DS.'helpers'.DS.'form.php';
	require_once LIBS.DS.'controller'.DS.'controller.php';
	require_once LIBS.DS.'model'.DS.'model.php';

	if (!class_exists('TheTestController')) {
		class TheTestController extends Controller {
			var $name = 'TheTest';
			var $uses = null;
		}
	}

	class Contact extends Model {

		var $primaryKey = 'id';
		var $useTable = false;

		function loadInfo() {
			return new Set(array(
				array('name' => 'id', 'type' => 'integer', 'null' => '', 'default' => '', 'length' => '8'),
				array('name' => 'name', 'type' => 'string', 'null' => '', 'default' => '', 'length' => '255'),
				array('name' => 'created', 'type' => 'date', 'null' => '1', 'default' => '', 'length' => ''),
				array('name' => 'updated', 'type' => 'datetime', 'null' => '1', 'default' => '', 'length' => null)
			));
		}
	}

/**
 * Short description for class.
 *
 * @package    test_suite
 * @subpackage test_suite.cases.libs
 * @since      CakePHP Test Suite v 1.0.0.0
 */
class FormHelperTest extends UnitTestCase {

	function setUp() {
		$this->Form = new FormHelper();
		$this->Form->Html = new HtmlHelper();
		$view = new View(new TheTestController());
		ClassRegistry::addObject('view', $view);
		ClassRegistry::addObject('Contact', new Contact());
	}

	function testFormInput() {
		$result = $this->Form->input('Model/field', array('type' => 'text'));
		$expected = '<div class="input"><label for="ModelField">Field</label><input name="data[Model][field]" value="" id="ModelField" type="text" /></div>';
		//$this->assertEqual($result, $expected);

		$result = $this->Form->input('Model/password');
		$expected = '<div class="input"><label for="ModelPassword">Password</label><input type="password" name="data[Model][password]" value="" id="ModelPassword" /></div>';
		$this->assertEqual($result, $expected);
	}

	function testLabel() {
		$this->Form->text('Person/name');
		$result = $this->Form->label();
		$this->assertEqual($result, '<label for="PersonName">Name</label>');

		$result = $this->Form->label('first_name');
		$this->assertEqual($result, '<label for="first_name">First Name</label>');

		$result = $this->Form->label('first_name', 'Your first name');
		$this->assertEqual($result, '<label for="first_name">Your first name</label>');

		$result = $this->Form->label('first_name', 'Your first name', array('class' => 'my-class'));
		$this->assertEqual($result, '<label for="first_name" class="my-class">Your first name</label>');

		$result = $this->Form->label('first_name', 'Your first name', array('class' => 'my-class', 'id' => 'LabelID'));
		$this->assertEqual($result, '<label for="first_name" class="my-class" id="LabelID">Your first name</label>');
	}

	function testTextbox() {
		$result = $this->Form->text('Model/field');
		$this->assertPattern('/^<input[^<>]+name="data\[Model\]\[field\]"[^<>]+\/>$/', $result);
		$this->assertPattern('/^<input[^<>]+type="text"[^<>]+\/>$/', $result);
		$this->assertPattern('/^<input[^<>]+value=""[^<>]+\/>$/', $result);
		$this->assertPattern('/^<input[^<>]+id="ModelField"[^<>]+\/>$/', $result);
		$this->assertNoPattern('/^<input[^<>]+name="[^<>]+name="[^<>]+\/>$/', $result);
		$this->assertNoPattern('/<input[^<>]+[^type|name|id|value]=[^<>]*>/', $result);

		$result = $this->Form->text('Model/field', array('type' => 'password'));
		$this->assertPattern('/^<input[^<>]+name="data\[Model\]\[field\]"[^<>]+\/>$/', $result);
		$this->assertPattern('/^<input[^<>]+type="password"[^<>]+\/>$/', $result);
		$this->assertPattern('/^<input[^<>]+value=""[^<>]+\/>$/', $result);
		$this->assertPattern('/^<input[^<>]+id="ModelField"[^<>]+\/>$/', $result);
		$this->assertNoPattern('/^<input[^<>]+name="[^<>]+name="[^<>]+\/>$/', $result);
		$this->assertNoPattern('/<input[^<>]+[^type|name|id|value]=[^<>]*>/', $result);

		$result = $this->Form->text('Model/field', array('id' => 'theID'));
		$expected = '<input name="data[Model][field]" type="text" id="theID" value="" />';
		$this->assertEqual($result, $expected);

		$this->Form->validationErrors['Model']['text'] = 1;
		$this->Form->data['Model']['text'] = 'test';
		$result = $this->Form->text('Model/text', array('id' => 'theID'));
		$this->assertPattern('/^<input[^<>]+name="data\[Model\]\[text\]"[^<>]+id="theID"[^<>]+\/>$/', $result);
		$this->assertPattern('/^<input[^<>]+value="test"[^<>]+class="form-error"[^<>]+\/>$/', $result);
		$this->assertNoPattern('/^<input[^<>]+name="[^<>]+name="[^<>]+\/>$/', $result);
		$this->assertNoPattern('/<input[^<>]+[^type|name|id|value|class]=[^<>]*>/', $result);
	}

	function testPassword() {
		$result = $this->Form->password('Model/field');
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

	function testSelect() {
		$result = $this->Form->select('Model/field', array());
		$this->assertPattern('/^<select [^<>]+>\n<option [^<>]+>/', $result);
		$this->assertPattern('/<option value="" ><\/option>/', $result);
		$this->assertPattern('/<\/select>$/', $result);
		$this->assertPattern('/<select[^<>]+name="data\[Model\]\[field\]"[^<>]*>/', $result);
		$this->assertPattern('/<select[^<>]+id="ModelField"[^<>]*>/', $result);
		$this->assertNoPattern('/^<select[^<>]+name="[^<>]+name="[^<>]+>$/', $result);

		$this->Form->data = array('Model' => array('field' => 'value'));
		$result = $this->Form->select('Model/field', array('value' => 'good', 'other' => 'bad'));
		$this->assertPattern('/option value=""/', $result);
		$this->assertPattern('/option value="value"\s+selected="selected"/', $result);
		$this->assertPattern('/option value="other"/', $result);
		$this->assertPattern('/<\/option>\s+<option/', $result);
		$this->assertPattern('/<\/option>\s+<\/select>/', $result);
		$this->assertNoPattern('/option value="other"\s+selected="selected"/', $result);
		$this->assertNoPattern('/<select[^<>]+[^name|id]=[^<>]*>/', $result);
		$this->assertNoPattern('/<option[^<>]+[^value|selected]=[^<>]*>/', $result);
	}

	function testTextArea() {
		$this->Form->data = array('Model' => array('field' => 'some test data'));
		$result = $this->Form->textarea('Model/field');
		$this->assertPattern('/^<textarea[^<>]+name="data\[Model\]\[field\]"[^<>]+id="ModelField"/', $result);
		$this->assertPattern('/^<textarea[^<>]+>some test data<\/textarea>$/', $result);
		$this->assertNoPattern('/^<textarea[^<>]+value="[^<>]+>/', $result);
		$this->assertNoPattern('/^<textarea[^<>]+name="[^<>]+name="[^<>]+>$/', $result);
		$this->assertNoPattern('/<textarea[^<>]+[^name|id]=[^<>]*>/', $result);

		$result = $this->Form->textarea('Model/tmp');
		$this->assertPattern('/^<textarea[^<>]+name="data\[Model\]\[tmp\]"[^<>]+><\/textarea>/', $result);
	}

	function testHiddenField() {
		$this->Form->validationErrors['Model']['field'] = 1;
		$this->Form->data['Model']['field'] = 'test';
		$result = $this->Form->hidden('Model/field', array('id' => 'theID'));
		$this->assertPattern('/^<input[^<>]+type="hidden"[^<>]+\/>$/', $result);
		$this->assertPattern('/^<input[^<>]+name="data\[Model\]\[field\]"[^<>]+id="theID"[^<>]+value="test"[^<>]+\/>$/', $result);
		$this->assertNoPattern('/^<input[^<>]+name="[^<>]+name="[^<>]+\/>$/', $result);
		$this->assertNoPattern('/<input[^<>]+[^type|name|id|value]=[^<>]*>/', $result);
	}

	function testFileUploadField() {
		$result = $this->Form->file('Model/upload');
		$this->assertPattern('/^<input type="file"[^<>]+name="data\[Model\]\[upload\]"[^<>]+\/>$/', $result);
		$this->assertPattern('/^<input type="file"[^<>]+value=""[^<>]+\/>$/', $result);
		$this->assertPattern('/^<input type="file"[^<>]+id="ModelUpload"[^<>]+\/>$/', $result);
		$this->assertNoPattern('/^<input[^<>]+name="[^<>]+name="[^<>]+\/>$/', $result);
		$this->assertNoPattern('/<input[^<>]+[^type|name|value|id]=[^<>]*>/', $result);
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
	}

	function testFormEnd() {
		$this->assertEqual($this->Form->end(), '</form>');
	}

	function tearDown() {
		unset($this->Form);
	}
}

?>