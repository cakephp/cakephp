<?php
/**
 * DebuggerTest file
 *
 * PHP 5
 *
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright 2005-2010, Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright 2005-2010, Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP Project
 * @package       cake.tests.cases.libs
 * @since         CakePHP(tm) v 1.2.0.5432
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
App::import('Core', 'Debugger');

/**
 * DebugggerTestCaseDebuggger class
 *
 * @package       cake.tests.cases.libs
 */
class DebuggerTestCaseDebugger extends Debugger {
}

/**
 * DebuggerTest class
 *
 * @package       cake.tests.cases.libs
 */
class DebuggerTest extends CakeTestCase {
// !!!
// !!! Be careful with changing code below as it may
// !!! change line numbers which are used in the tests
// !!!
	protected $_restoreError = false;

/**
 * setUp method
 *
 * @access public
 * @return void
 */
	function setUp() {
		parent::setup();
		Configure::write('debug', 2);
		Configure::write('log', false);
	}

/**
 * tearDown method
 *
 * @access public
 * @return void
 */
	function tearDown() {
		parent::teardown();
		Configure::write('log', true);
		if ($this->_restoreError) {
			restore_error_handler();
		}
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
		$result = Debugger::excerpt(__FILE__, __LINE__, 2);
		$this->assertTrue(is_array($result));
		$this->assertEqual(count($result), 5);
		$this->assertPattern('/function(.+)testExcerpt/', $result[1]);

		$result = Debugger::excerpt(__FILE__, 2, 2);
		$this->assertTrue(is_array($result));
		$this->assertEqual(count($result), 4);

		$expected = '<code><span style="color: #000000">&lt;?php';
		$expected .= '</span></code>';
		$this->assertEqual($result[0], $expected);

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
		set_error_handler('Debugger::showError');
		$this->_restoreError = true;

		$result = Debugger::output(false);
		$this->assertEqual($result, '');
		$out .= '';
		$result = Debugger::output(true);

		$this->assertEqual($result[0]['error'], 'Notice');
		$this->assertPattern('/Undefined variable\:\s+out/', $result[0]['description']);
		$this->assertPattern('/DebuggerTest::testOutput/i', $result[0]['trace']);

		ob_start();
		Debugger::output('txt');
		$other .= '';
		$result = ob_get_clean();

		$this->assertPattern('/Undefined variable:\s+other/', $result);
		$this->assertPattern('/Context:/', $result);
		$this->assertPattern('/DebuggerTest::testOutput/i', $result);

		ob_start();
		Debugger::output('html');
		$wrong .= '';
		$result = ob_get_clean();
		$this->assertPattern('/<pre class="cake-debug">.+<\/pre>/', $result);
		$this->assertPattern('/<b>Notice<\/b>/', $result);
		$this->assertPattern('/variable:\s+wrong/', $result);

		ob_start();
		Debugger::output('js');
		$buzz .= '';
		$result = explode('</a>', ob_get_clean());
		$this->assertTags($result[0], array(
			'pre' => array('class' => 'cake-debug'),
			'a' => array(
				'href' => "javascript:void(0);",
				'onclick' => "document.getElementById('cakeErr9-trace').style.display = " .
				             "(document.getElementById('cakeErr9-trace').style.display == 'none'" .
				             " ? '' : 'none');"
			),
			'b' => array(), 'Notice', '/b', ' (8)',
		));

		$this->assertPattern('/Undefined variable:\s+buzz/', $result[1]);
		$this->assertPattern('/<a[^>]+>Code/', $result[1]);
		$this->assertPattern('/<a[^>]+>Context/', $result[2]);
	}

/**
 * Tests that changes in output formats using Debugger::output() change the templates used.
 *
 * @return void
 */
	function testChangeOutputFormats() {
		set_error_handler('Debugger::showError');
		$this->_restoreError = true;

		Debugger::output('js', array(
			'traceLine' => '{:reference} - <a href="txmt://open?url=file://{:file}' .
			               '&line={:line}">{:path}</a>, line {:line}'
		));
		$result = Debugger::trace();
		$this->assertPattern('/' . preg_quote('txmt://open?url=file:///', '/') . '/', $result);

		Debugger::output('xml', array(
			'error' => '<error><code>{:code}</code><file>{:file}</file><line>{:line}</line>' .
			           '{:description}</error>',
			'context' => "<context>{:context}</context>",
			'trace' => "<stack>{:trace}</stack>",
		));
		Debugger::output('xml');

		ob_start();
		$foo .= '';
		$result = ob_get_clean();

		$data = array(
			'error' => array(),
			'code' => array(), '8', '/code',
			'file' => array(), 'preg:/[^<]+/', '/file',
			'line' => array(), '' . (intval(__LINE__) - 7), '/line',
			'preg:/Undefined variable:\s+foo/',
			'/error'
		);
		$this->assertTags($result, $data, true);
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
		$expected = 'View
		View::$Helpers = HelperCollection object
		View::$plugin = NULL
		View::$name = ""
		View::$passedArgs = array
		View::$helpers = array
		View::$viewPath = ""
		View::$viewVars = array
		View::$layout = "default"
		View::$layoutPath = NULL
		View::$autoLayout = true
		View::$ext = ".ctp"
		View::$subDir = NULL
		View::$theme = NULL
		View::$cacheAction = false
		View::$validationErrors = array
		View::$hasRendered = false
		View::$modelScope = false
		View::$model = NULL
		View::$association = NULL
		View::$field = NULL
		View::$fieldSuffix = NULL
		View::$modelId = NULL
		View::$uuids = array
		View::$output = false
		View::$request = NULL
		View::$elementCache = "default"';

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
		$this->assertPattern('/DebuggerTest\:\:testLog/i', $result);
		$this->assertPattern('/"cool"/', $result);

		unlink(TMP . 'logs' . DS . 'debug.log');

		Debugger::log(array('whatever', 'here'));
		$result = file_get_contents(TMP . 'logs' . DS . 'debug.log');
		$this->assertPattern('/DebuggerTest\:\:testLog/i', $result);
		$this->assertPattern('/\[main\]/', $result);
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
