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
	require_once LIBS.'../app_helper.php';
	require_once LIBS.'class_registry.php';
	require_once LIBS.DS.'view'.DS.'view.php';
	require_once LIBS.DS.'view'.DS.'helper.php';
	require_once LIBS.DS.'view'.DS.'helpers'.DS.'html.php';
	require_once LIBS.DS.'view'.DS.'helpers'.DS.'form.php';
	require_once LIBS.DS.'controller'.DS.'controller.php';

class TheTestController extends Controller {
	var $name = 'TheTest';
	var $uses = null;
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
		$this->form = new FormHelper();
		$this->form->Html = new HtmlHelper();
		$view = new View(new TheTestController());
		ClassRegistry::addObject('view', $view);
	}

	function testFormInput() {
		$result = $this->form->input('Model/field', array('type' => 'text'));
		$expected = '<div class="input"><label for="ModelField">Field</label><input name="data[Model][field]" value="" id="ModelField" type="text" /></div>';
		//$this->assertEqual($result, $expected);

		$result = $this->form->input('Model/password');
		$expected = '<div class="input"><label for="ModelPassword">Password</label><input type="password" name="data[Model][password]" value="" id="ModelPassword" /></div>';
		$this->assertEqual($result, $expected);
	}

	function testTextbox() {
		$result = $this->form->text('Model/field');
		$expected = '<input name="data[Model][field]" type="text" value="" id="ModelField" />';
		$this->assertEqual($result, $expected);

		$result = $this->form->text('Model/field', array('type' => 'password'));
		$expected = '<input name="data[Model][field]" type="password" value="" id="ModelField" />';
		$this->assertEqual($result, $expected);

		$result = $this->form->text('Model/field', array('id' => 'theID'));
		$expected = '<input name="data[Model][field]" type="text" id="theID" value="" />';
		$this->assertEqual($result, $expected);
	}

	function tearDown() {
		unset($this->form);
	}
}

?>