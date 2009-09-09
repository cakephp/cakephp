<?php
/* SVN FILE: $Id$ */
/**
 * DebuggerTest file
 *
 * Long description for file
 *
 * PHP versions 4 and 5
 *
 * CakePHP(tm) Tests <https://trac.cakephp.org/wiki/Developement/TestSuite>
 * Copyright 2005-2008, Cake Software Foundation, Inc. (http://www.cakefoundation.org)
 *
 *  Licensed under The Open Group Test Suite License
 *  Redistributions of files must retain the above copyright notice.
 *
 * @filesource
 * @copyright     Copyright 2005-2008, Cake Software Foundation, Inc. (http://www.cakefoundation.org)
 * @link          https://trac.cakephp.org/wiki/Developement/TestSuite CakePHP(tm) Tests
 * @package       cake
 * @subpackage    cake.tests.cases.libs
 * @since         CakePHP(tm) v 1.2.0.5432
 * @version       $Revision$
 * @modifiedby    $LastChangedBy$
 * @lastmodified  $Date$
 * @license       http://www.opensource.org/licenses/opengroup.php The Open Group Test Suite License
 */
App::import('Core', 'Debugger');
/**
 * DebugggerTestCaseDebuggger class
 *
 * @package       cake
 * @subpackage    cake.tests.cases.libs
 */
class DebuggerTestCaseDebugger extends Debugger {
}
/**
 * DebuggerTest class
 *
 * @package       cake
 * @subpackage    cake.tests.cases.libs
 */
class DebuggerTest extends CakeTestCase {
// !!!
// !!! Be careful with changing code below as it may
// !!! change line numbers which are used in the tests
// !!!
/**
 * setUp method
 *
 * @access public
 * @return void
 */
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
/**
 * tearDown method
 *
 * @access public
 * @return void
 */
	function tearDown() {
		Configure::write('log', true);
	}
/**
 * testDocRef method
 *
 * @access public
 * @return void
 */
	function testDocRef() {
		ini_set('docref_root', '');
		$this->assertEqual(ini_get('docref_root'), '');
		$debugger = new Debugger();
		$this->assertEqual(ini_get('docref_root'), 'http://php.net/');
	}
/**
 * test Excerpt writing
 *
 * @access public
 * @return void
 */
	function testExcerpt() {
		$return = Debugger::excerpt(__FILE__, 2, 2);
		$this->assertTrue(is_array($return));
		$this->assertEqual(count($return), 4);
		$this->assertPattern('#/*&nbsp;SVN&nbsp;FILE:&nbsp;\$Id\$#', $return[1]);

		$return = Debugger::excerpt('[internal]', 2, 2);
		$this->assertTrue(empty($return));
	}
/**
 * testOutput method
 *
 * @access public
 * @return void
 */
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

		ob_start();
		Debugger::output('html');
		$wrong .= '';
		$result = ob_get_clean();
		$this->assertPattern('/<pre class="cake-debug">.+<\/pre>/', $result);
		$this->assertPattern('/<b>Notice<\/b>/', $result);
		$this->assertPattern('/variable: wrong/', $result);

		ob_start();
		Debugger::output('js');
		$buzz .= '';
		$result = ob_get_clean();
		$this->assertPattern("/<a href\='javascript:void\(0\);' onclick\='/", $result);
		$this->assertPattern('/<b>Notice<\/b>/', $result);
		$this->assertPattern('/Undefined variable: buzz/', $result);
		$this->assertPattern('/<a[^>]+>Code<\/a>/', $result);
		$this->assertPattern('/<a[^>]+>Context<\/a>/', $result);
		set_error_handler('simpleTestErrorHandler');
	}
/**
 * testTrimPath method
 *
 * @access public
 * @return void
 */
	function testTrimPath() {
		$this->assertEqual(Debugger::trimPath(APP), 'APP' . DS);
		$this->assertEqual(Debugger::trimPath(CAKE_CORE_INCLUDE_PATH), 'CORE');
	}
/**
 * testExportVar method
 *
 * @access public
 * @return void
 */
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
		View::$output = false
		View::$__passedVars = array
		View::$__scripts = array
		View::$__paths = array
		View::$_log = NULL
		View::$webroot = NULL';
		$result = str_replace(array("\t", "\r\n", "\n"), "", $result);
		$expected =  str_replace(array("\t", "\r\n", "\n"), "", $expected);
		$this->assertEqual($result, $expected);
	}
/**
 * testLog method
 *
 * @access public
 * @return void
 */
	function testLog() {
		if (file_exists(LOGS . 'debug.log')) {
			unlink(LOGS . 'debug.log');
		}

		Debugger::log('cool');
		$result = file_get_contents(LOGS . 'debug.log');
		$this->assertPattern('/DebuggerTest\:\:testLog/', $result);
		$this->assertPattern('/"cool"/', $result);

		unlink(TMP . 'logs' . DS . 'debug.log');

		Debugger::log(array('whatever', 'here'));
		$result = file_get_contents(TMP . 'logs' . DS . 'debug.log');
		$this->assertPattern('/DebuggerTest\:\:testLog/', $result);
		$this->assertPattern('/array/', $result);
		$this->assertPattern('/"whatever",/', $result);
		$this->assertPattern('/"here"/', $result);
	}
/**
 * testDump method
 *
 * @access public
 * @return void
 */
	function testDump() {
		$var = array('People' => array(
					array(
					'name' => 'joeseph',
					'coat' => 'technicolor',
					'hair_color' => 'brown'
					),
					array(
					'name' => 'Shaft',
					'coat' => 'black',
					'hair' => 'black'
					)
				)
			);
		ob_start();
		Debugger::dump($var);
		$result = ob_get_clean();
		$expected = "<pre>array(\n\t\"People\" => array()\n)</pre>";
		$this->assertEqual($expected, $result);
	}
/**
 * test getInstance.
 *
 * @access public
 * @return void
 */
	function testGetInstance() {
		$result = Debugger::getInstance();
		$this->assertIsA($result, 'Debugger');

		$result = Debugger::getInstance('DebuggerTestCaseDebugger');
		$this->assertIsA($result, 'DebuggerTestCaseDebugger');

		$result = Debugger::getInstance();
		$this->assertIsA($result, 'DebuggerTestCaseDebugger');

		$result = Debugger::getInstance('Debugger');
		$this->assertIsA($result, 'Debugger');
	}
}
?>