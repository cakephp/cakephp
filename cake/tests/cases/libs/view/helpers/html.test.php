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
	require_once LIBS.DS.'controller'.DS.'controller.php';

class TheHtmlTestController extends Controller {
	var $name = 'TheTest';
	var $uses = null;
}

class HtmlHelperTest extends UnitTestCase {
	var $html = null;
	
	function setUp() {
		$this->html = new HtmlHelper();
		$view = new View(new TheHtmlTestController());
		ClassRegistry::addObject('view', $view);
	}

	function testSelectTag() {
		@$result = $this->html->selectTag('Model/field', array());
		$this->assertPattern('/^<select [^<>]+>\n<option [^<>]+>/', $result);
		$this->assertPattern('/<option value="" ><\/option>/', $result);
		$this->assertPattern('/<\/select>$/', $result);
		$this->assertPattern('/<select[^<>]+name="data\[Model\]\[field\]"[^<>]*>/', $result);
		$this->assertPattern('/<select[^<>]+id="ModelField"[^<>]*>/', $result);

		$this->html->data = array('Model' => array('field' => 'value'));
		@$result = $this->html->selectTag('Model/field', array('value' => 'good', 'other' => 'bad'));
		$this->assertPattern('/option value=""/', $result);
		$this->assertPattern('/option value="value"/', $result);
		$this->assertPattern('/option value="other"/', $result);
		debug($result, true);
	}

	function tearDown() {
		unset($this->html);
	}
}
?>