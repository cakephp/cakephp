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
	function testOutput() {
		if (file_exists(APP . DS . 'vendors' . DS . 'simpletest' . DS . 'reporter.php')) {
			define('SIMPLETESTVENDORPATH', 'APP' . DS . 'vendors');
		} else {
			define('SIMPLETESTVENDORPATH', 'CORE' . DS . 'vendors');
		}
		Debugger::invoke(Debugger::getInstance());
		$result = Debugger::output(false);
		$this->assertEqual($result, '');
		$out .= '';
		$result = Debugger::output(true);
		$expected = array(array(
						'error' => 'Notice', 'code' => '8', 'description' => 'Undefined variable: out', 'line' => '48', 'file' => 'CORE/cake/tests/cases/libs/debugger.test.php',
						'context' => array("\$result\t=\tnull"),
						'trace' => "DebuggerTest::testOutput() - CORE/cake/tests/cases/libs/debugger.test.php, line 48
SimpleInvoker::invoke() - " . SIMPLETESTVENDORPATH . "/simpletest/invoker.php, line 68
SimpleInvokerDecorator::invoke() - " . SIMPLETESTVENDORPATH . "/simpletest/invoker.php, line 126
SimpleErrorTrappingInvoker::invoke() - " . SIMPLETESTVENDORPATH . "/simpletest/errors.php, line 48
SimpleInvokerDecorator::invoke() - " . SIMPLETESTVENDORPATH . "/simpletest/invoker.php, line 126
SimpleExceptionTrappingInvoker::invoke() - " . SIMPLETESTVENDORPATH . "/simpletest/exceptions.php, line 42
SimpleTestCase::run() - " . SIMPLETESTVENDORPATH . "/simpletest/test_case.php, line 135
TestSuite::run() - " . SIMPLETESTVENDORPATH . "/simpletest/test_case.php, line 588
TestSuite::run() - " . SIMPLETESTVENDORPATH . "/simpletest/test_case.php, line 591
TestManager::runTestCase() - CORE/cake/tests/lib/test_manager.php, line 93
[main] - APP/webroot/test.php, line 240"
						)
				);
		$result = str_replace(array("\t", "\r\n", "\n"), "", $result);
		$expected =  str_replace(array("\t", "\r\n", "\n"), "", $expected);
		$this->assertEqual($result, $expected);
		ob_start();
		Debugger::output('txt');
		$other .= '';
		$result = ob_get_clean();
		$expected = "Notice: 8 :: Undefined variable: other on line 71 of CORE/cake/tests/cases/libs/debugger.test.php\n";
		$expected .= 'Context:
$result	=	array(array("error" => "Notice","code" => 8,"description" => "Undefined variable: out","line" => 48,"file" => "CORE/cake/tests/cases/libs/debugger.test.php","context" => array("$result	=	null"),"trace" => "DebuggerTest::testOutput() - CORE/cake/tests/cases/libs/debugger.test.php, line 48
SimpleInvoker::invoke() - ' . SIMPLETESTVENDORPATH . '/simpletest/invoker.php, line 68
SimpleInvokerDecorator::invoke() - ' . SIMPLETESTVENDORPATH . '/simpletest/invoker.php, line 126
SimpleErrorTrappingInvoker::invoke() - ' . SIMPLETESTVENDORPATH . '/simpletest/errors.php, line 48
SimpleInvokerDecorator::invoke() - ' . SIMPLETESTVENDORPATH . '/simpletest/invoker.php, line 126
SimpleExceptionTrappingInvoker::invoke() - ' . SIMPLETESTVENDORPATH . '/simpletest/exceptions.php, line 42
SimpleTestCase::run() - ' . SIMPLETESTVENDORPATH . '/simpletest/test_case.php, line 135
TestSuite::run() - ' . SIMPLETESTVENDORPATH . '/simpletest/test_case.php, line 588
TestSuite::run() - ' . SIMPLETESTVENDORPATH . '/simpletest/test_case.php, line 591
TestManager::runTestCase() - CORE/cake/tests/lib/test_manager.php, line 93
[main] - APP/webroot/test.php, line 240"))
$out	=	"[empty string]"
$expected	=	array(array("error" => "Notice","code" => "8","description" => "Undefined variable: out","line" => "48","file" => "CORE/cake/tests/cases/libs/debugger.test.php","context" => array("$result	=	null"),"trace" => "DebuggerTest::testOutput() - CORE/cake/tests/cases/libs/debugger.test.php, line 48
SimpleInvoker::invoke() - ' . SIMPLETESTVENDORPATH . '/simpletest/invoker.php, line 68
SimpleInvokerDecorator::invoke() - ' . SIMPLETESTVENDORPATH . '/simpletest/invoker.php, line 126
SimpleErrorTrappingInvoker::invoke() - ' . SIMPLETESTVENDORPATH . '/simpletest/errors.php, line 48
SimpleInvokerDecorator::invoke() - ' . SIMPLETESTVENDORPATH . '/simpletest/invoker.php, line 126
SimpleExceptionTrappingInvoker::invoke() - ' . SIMPLETESTVENDORPATH . '/simpletest/exceptions.php, line 42
SimpleTestCase::run() - ' . SIMPLETESTVENDORPATH . '/simpletest/test_case.php, line 135
TestSuite::run() - ' . SIMPLETESTVENDORPATH . '/simpletest/test_case.php, line 588
TestSuite::run() - ' . SIMPLETESTVENDORPATH . '/simpletest/test_case.php, line 591
TestManager::runTestCase() - CORE/cake/tests/lib/test_manager.php, line 93
[main] - APP/webroot/test.php, line 240"))
';
	$expected .= 'Trace:
DebuggerTest::testOutput() - CORE/cake/tests/cases/libs/debugger.test.php, line 71
SimpleInvoker::invoke() - ' . SIMPLETESTVENDORPATH . '/simpletest/invoker.php, line 68
SimpleInvokerDecorator::invoke() - ' . SIMPLETESTVENDORPATH . '/simpletest/invoker.php, line 126
SimpleErrorTrappingInvoker::invoke() - ' . SIMPLETESTVENDORPATH . '/simpletest/errors.php, line 48
SimpleInvokerDecorator::invoke() - ' . SIMPLETESTVENDORPATH . '/simpletest/invoker.php, line 126
SimpleExceptionTrappingInvoker::invoke() - ' . SIMPLETESTVENDORPATH . '/simpletest/exceptions.php, line 42
SimpleTestCase::run() - ' . SIMPLETESTVENDORPATH . '/simpletest/test_case.php, line 135
TestSuite::run() - ' . SIMPLETESTVENDORPATH . '/simpletest/test_case.php, line 588
TestSuite::run() - ' . SIMPLETESTVENDORPATH . '/simpletest/test_case.php, line 591
TestManager::runTestCase() - CORE/cake/tests/lib/test_manager.php, line 93
[main] - APP/webroot/test.php, line 240';

		$result = str_replace(array("\t", "\r\n", "\n"), "", $result);
		$expected =  str_replace(array("\t", "\r\n", "\n"), "", $expected);
		$this->assertEqual($result, $expected);
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
		View::$name = "[empty string]"
		View::$action = NULL
		View::$params = array()
		View::$passedArgs = array()
		View::$data = array()
		View::$helpers = array("Html","Form")
		View::$viewPath = "[empty string]"
		View::$viewVars = array()
		View::$layout = "default"
		View::$layoutPath = NULL
		View::$pageTitle = false
		View::$autoRender = true
		View::$autoLayout = true
		View::$ext = ".ctp"
		View::$subDir = NULL
		View::$themeWeb = NULL
		View::$cacheAction = false
		View::$validationErrors = array()
		View::$hasRendered = false
		View::$loaded = array()
		View::$modelScope = false
		View::$model = NULL
		View::$association = NULL
		View::$field = NULL
		View::$fieldSuffix = NULL
		View::$modelId = NULL
		View::$uuids = array()
		View::$__passedVars = array("viewVars","action","autoLayout","autoRender","ext","base","webroot","helpers","here","layout","name","pageTitle","layoutPath","viewPath","params","data","webservices","plugin","passedArgs","cacheAction")
		View::$__scripts = array()
		View::$__paths = array()
		View::$_log = NULL
		View::$webroot = NULL
		View::$webservices = NULL
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

	function setUp() {
		Configure::write('log', false);
	}
	function tearDown() {
		Configure::write('log', true);
	}

}
?>