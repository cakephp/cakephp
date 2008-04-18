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
 * Copyright 2005-2008, Cake Software Foundation, Inc.
 *								1785 E. Sahara Avenue, Suite 490-204
 *								Las Vegas, Nevada 89104
 *
 *  Licensed under The Open Group Test Suite License
 *  Redistributions of files must retain the above copyright notice.
 *
 * @filesource
 * @copyright		Copyright 2005-2008, Cake Software Foundation, Inc.
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

	function setUp() {
		Configure::write('log', false);
		if (!defined('SIMPLETESTVENDORPATH')) {
			if (file_exists(APP . DS . 'vendors' . DS . 'simpletest' . DS . 'reporter.php')) {
				define('SIMPLETESTVENDORPATH', 'APP' . DS . 'vendors');
			} else {
				define('SIMPLETESTVENDORPATH', 'CORE' . DS . 'vendors');
			}
		}
	}

	function testOutput() {
		Debugger::invoke(Debugger::getInstance());
		$result = Debugger::output(false);
		$this->assertEqual($result, '');
		$out .= '';
		$result = Debugger::output(true);
	
		$this->assertEqual($result[0]['error'], 'Notice');
		$this->assertEqual($result[0]['description'], 'Undefined variable: out');
		$this->assertPattern('/DebuggerTest::testOutput/', $result[0]['trace']);
		$this->assertPattern('/SimpleInvoker::invoke/', $result[0]['trace']);
		
		ob_start();
		Debugger::output('txt');
		$other .= '';
		$result = ob_get_clean();
		
		$this->assertPattern('/Undefined variable: other/', $result);
		$this->assertPattern('/Context:/', $result);
		$this->assertPattern('/DebuggerTest::testOutput/', $result);
		$this->assertPattern('/SimpleInvoker::invoke/', $result);
		
		set_error_handler('simpleTestErrorHandler');
	}


	function testExportVar() {
		App::import('Controller');
		$Controller = new Controller();
		$Controller->helpers = array('Html', 'Form');
		$View = new View($Controller);
		$result = Debugger::exportVar($View);
		$expected = 'ViewView::$base = NULL
		View::$here = NULL
		View::$plugin = NULL
		View::$name = ""
		View::$action = NULL
		View::$params = array
		View::$passedArgs = array
		View::$data = array
		View::$helpers = array
		View::$viewPath = ""
		View::$viewVars = array
		View::$layout = "default"
		View::$layoutPath = NULL
		View::$pageTitle = false
		View::$autoRender = true
		View::$autoLayout = true
		View::$ext = ".ctp"
		View::$subDir = NULL
		View::$themeWeb = NULL
		View::$cacheAction = false
		View::$validationErrors = array
		View::$hasRendered = false
		View::$loaded = array
		View::$modelScope = false
		View::$model = NULL
		View::$association = NULL
		View::$field = NULL
		View::$fieldSuffix = NULL
		View::$modelId = NULL
		View::$uuids = array
		View::$__passedVars = array
		View::$__scripts = array
		View::$__paths = array
		View::$_log = NULL
		View::$webroot = NULL';
		$result = str_replace(array("\t", "\r\n", "\n"), "", $result);
		$expected =  str_replace(array("\t", "\r\n", "\n"), "", $expected);
		$this->assertEqual($result, $expected);
	}

	function testLog() {
		if (file_exists(LOGS . 'debug.log')) {
			unlink(LOGS . 'debug.log');
		}

		Debugger::log('cool');
		$result = file_get_contents(LOGS . 'debug.log');
		$this->assertPattern('/DebuggerTest::testLog/', $result);
		$this->assertPattern('/"cool"/', $result);

		unlink(TMP . 'logs' . DS . 'debug.log');

		Debugger::log(array('whatever', 'here'));
		$result = file_get_contents(TMP . 'logs' . DS . 'debug.log');

		$this->assertPattern('/DebuggerTest::testLog/', $result);
		$this->assertPattern('/array/', $result);
		$this->assertPattern('/"whatever",/', $result);
		$this->assertPattern('/"here"/', $result);
	}

	function tearDown() {
		Configure::write('log', true);
	}

}
?>