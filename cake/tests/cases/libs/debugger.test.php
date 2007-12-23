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
 * Copyright 2005-2007, Cake Software Foundation, Inc.
 *								1785 E. Sahara Avenue, Suite 490-204
 *								Las Vegas, Nevada 89104
 *
 *  Licensed under The Open Group Test Suite License
 *  Redistributions of files must retain the above copyright notice.
 *
 * @filesource
 * @copyright		Copyright 2005-2007, Cake Software Foundation, Inc.
 * @link				https://trac.cakephp.org/wiki/Developement/TestSuite CakePHP(tm) Tests
 * @package			cake.tests
 * @subpackage		cake.tests.cases.libs
 * @since			CakePHP(tm) v 1.2.0.5432
 * @version			$Revision$
 * @modifiedby		$LastChangedBy$
 * @lastmodified	$Date$
 * @license			http://www.opensource.org/licenses/opengroup.php The Open Group Test Suite License
 */
uses('debugger');
/**
 * Short description for class.
 *
 * @package    cake.tests
 * @subpackage cake.tests.cases.libs
 */
class DebuggerTest extends UnitTestCase {

	//do not move code below or it change line numbers which are used in the tests
	function testOutput() {

		Debugger::invoke(Debugger::getInstance());
		$result = Debugger::output(false);
		$this->assertEqual($result, '');
		$out .= '';
		$result = Debugger::output(true);
		$expected = array(array('error' => 'Notice', 'code' => '8', 'description' => 'Undefined variable: out', 'line' => '44', 'file' => 'CORE/cake/tests/cases/libs/debugger.test.php'));
		$this->assertEqual($result, $expected);

		ob_start();
		Debugger::output('txt');
		$other .= '';
		$result = ob_get_clean();
		$expected = "Notice: 8 :: Undefined variable: other on line 51 of CORE/cake/tests/cases/libs/debugger.test.php\n";
		$this->assertEqual($result, $expected);

		set_error_handler('simpleTestErrorHandler');
	}


	function testExportVar() {
		App::import('Controller');
		$Controller = new Controller();
		$Controller->helpers = array('Html', 'Form');
		$View = new View($Controller);
		$result = Debugger::exportVar($View);
		$expected = 'View::$base = "[empty string]"
		View::$here = "[empty string]"
		View::$plugin = "[empty string]"
		View::$name = "[empty string]"
		View::$action = "[empty string]"
		View::$params = array()
		View::$passedArgs = array()
		View::$data = array()
		View::$helpers = array("Html","Form")
		View::$viewPath = "[empty string]"
		View::$viewVars = array()
		View::$layout = "default"
		View::$layoutPath = "[empty string]"
		View::$pageTitle = "[empty string]"
		View::$autoRender = true
		View::$autoLayout = true
		View::$ext = ".ctp"
		View::$subDir = "[empty string]"
		View::$themeWeb = "[empty string]"
		View::$cacheAction = "[empty string]"
		View::$validationErrors = array()
		View::$hasRendered = "[empty string]"
		View::$loaded = array()
		View::$modelScope = "[empty string]"
		View::$model = "[empty string]"
		View::$association = "[empty string]"
		View::$field = "[empty string]"
		View::$fieldSuffix = "[empty string]"
		View::$modelId = "[empty string]"
		View::$uuids = array()
		View::$__passedVars = array("viewVars","action","autoLayout","autoRender","ext","base","webroot","helpers","here","layout","name","pageTitle","layoutPath","viewPath","params","data","webservices","plugin","passedArgs","cacheAction")
		View::$__scripts = array()
		View::$__paths = array()
		View::$_log = "[empty string]"
		View::$webroot = "[empty string]"
		View::$webservices = "[empty string]"
		View::element()
		View::render()
		View::renderElement()
		View::renderLayout()
		View::renderCache()
		View::getVars()
		View::getVar()
		View::addScript()
		View::uuid()
		View::entity()
		View::set()
		View::error()
		View::Object()
		View::toString()
		View::requestAction()
		View::log()
		View::cakeError()';
		$result = str_replace(array("\t", "\r\n", "\n"), "", $result);
		$expected =  str_replace(array("\t", "\r\n", "\n"), "", $expected);
		$this->assertEqual($result, $expected);
	}

}
?>