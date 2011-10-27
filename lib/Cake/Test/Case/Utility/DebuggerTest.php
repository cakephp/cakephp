<?php
/**
 * DebuggerTest file
 *
 * PHP 5
 *
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright 2005-2011, Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright 2005-2011, Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP Project
 * @package       Cake.Test.Case.Utility
 * @since         CakePHP(tm) v 1.2.0.5432
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
App::uses('Debugger', 'Utility');

/**
 * DebugggerTestCaseDebuggger class
 *
 * @package       Cake.Test.Case.Utility
 */
class DebuggerTestCaseDebugger extends Debugger {
}

/**
 * DebuggerTest class
 *
 * @package       Cake.Test.Case.Utility
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
 * @return void
 */
	public function setUp() {
		parent::setup();
		Configure::write('debug', 2);
		Configure::write('log', false);
	}

/**
 * tearDown method
 *
 * @return void
 */
	public function tearDown() {
		parent::teardown();
		Configure::write('log', true);
		if ($this->_restoreError) {
			restore_error_handler();
		}
	}

/**
 * testDocRef method
 *
 * @return void
 */
	public function testDocRef() {
		ini_set('docref_root', '');
		$this->assertEqual(ini_get('docref_root'), '');
		$debugger = new Debugger();
		$this->assertEqual(ini_get('docref_root'), 'http://php.net/');
	}

/**
 * test Excerpt writing
 *
 * @return void
 */
	public function testExcerpt() {
		$result = Debugger::excerpt(__FILE__, __LINE__, 2);
		$this->assertTrue(is_array($result));
		$this->assertEqual(count($result), 5);
		$this->assertPattern('/function(.+)testExcerpt/', $result[1]);

		$result = Debugger::excerpt(__FILE__, 2, 2);
		$this->assertTrue(is_array($result));
		$this->assertEqual(count($result), 4);

		$pattern = '/<code><span style\="color\: \#\d+">.*?&lt;\?php/';
		$this->assertRegExp($pattern, $result[0]);

		$return = Debugger::excerpt('[internal]', 2, 2);
		$this->assertTrue(empty($return));
	}

/**
 * testOutput method
 *
 * @return void
 */
	public function testOutput() {
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
		$this->assertPattern('/<pre class="cake-error">.+<\/pre>/', $result);
		$this->assertPattern('/<b>Notice<\/b>/', $result);
		$this->assertPattern('/variable:\s+wrong/', $result);

		ob_start();
		Debugger::output('js');
		$buzz .= '';
		$result = explode('</a>', ob_get_clean());
		$this->assertTags($result[0], array(
			'pre' => array('class' => 'cake-error'),
			'a' => array(
				'href' => "javascript:void(0);",
				'onclick' => "preg:/document\.getElementById\('cakeErr[a-z0-9]+\-trace'\)\.style\.display = " .
				             "\(document\.getElementById\('cakeErr[a-z0-9]+\-trace'\)\.style\.display == 'none'" .
				             " \? '' \: 'none'\);/"
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
	public function testChangeOutputFormats() {
		set_error_handler('Debugger::showError');
		$this->_restoreError = true;

		Debugger::output('js', array(
			'traceLine' => '{:reference} - <a href="txmt://open?url=file://{:file}' .
			               '&line={:line}">{:path}</a>, line {:line}'
		));
		$result = Debugger::trace();
		$this->assertPattern('/' . preg_quote('txmt://open?url=file://', '/') . '(\/|[A-Z]:\\\\)' . '/', $result);

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
 * Test that outputAs works.
 *
 * @return void
 */
	public function testOutputAs() {
		Debugger::outputAs('html');
		$this->assertEquals('html', Debugger::outputAs());
	}

/**
 * Test that choosing a non-existant format causes an exception
 *
 * @expectedException CakeException
 * @return void
 */
	public function testOutputAsException() {
		Debugger::outputAs('Invalid junk');
	}

/**
 * Tests that changes in output formats using Debugger::output() change the templates used.
 *
 * @return void
 */
	public function testAddFormat() {
		set_error_handler('Debugger::showError');
		$this->_restoreError = true;

		Debugger::addFormat('js', array(
			'traceLine' => '{:reference} - <a href="txmt://open?url=file://{:file}' .
			               '&line={:line}">{:path}</a>, line {:line}'
		));
		Debugger::outputAs('js');

		$result = Debugger::trace();
		$this->assertPattern('/' . preg_quote('txmt://open?url=file://', '/') . '(\/|[A-Z]:\\\\)' . '/', $result);

		Debugger::addFormat('xml', array(
			'error' => '<error><code>{:code}</code><file>{:file}</file><line>{:line}</line>' .
			           '{:description}</error>',
		));
		Debugger::outputAs('xml');

		ob_start();
		$foo .= '';
		$result = ob_get_clean();

		$data = array(
			'<error',
			'<code', '8', '/code',
			'<file', 'preg:/[^<]+/', '/file',
			'<line', '' . (intval(__LINE__) - 7), '/line',
			'preg:/Undefined variable:\s+foo/',
			'/error'
		);
		$this->assertTags($result, $data, true);
	}

/**
 * Test adding a format that is handled by a callback.
 *
 * @return void
 */
	public function testAddFormatCallback() {
		set_error_handler('Debugger::showError');
		$this->_restoreError = true;

		Debugger::addFormat('callback', array('callback' => array($this, 'customFormat')));
		Debugger::outputAs('callback');

		ob_start();
		$foo .= '';
		$result = ob_get_clean();
		$this->assertContains('Notice: I eated an error', $result);
		$this->assertContains('DebuggerTest.php', $result);
	}

/**
 * Test method for testing addFormat with callbacks.
 */
	public function customFormat($error, $strings) {
		return $error['error'] . ': I eated an error ' . $error['path'];
	}

/**
 * testTrimPath method
 *
 * @return void
 */
	public function testTrimPath() {
		$this->assertEqual(Debugger::trimPath(APP), 'APP' . DS);
		$this->assertEqual(Debugger::trimPath(CAKE_CORE_INCLUDE_PATH), 'CORE');
	}

/**
 * testExportVar method
 *
 * @return void
 */
	public function testExportVar() {
		App::uses('Controller', 'Controller');
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
		View::$view = NULL
		View::$layout = "default"
		View::$layoutPath = NULL
		View::$autoLayout = true
		View::$ext = ".ctp"
		View::$subDir = NULL
		View::$theme = NULL
		View::$cacheAction = false
		View::$validationErrors = array
		View::$hasRendered = false
		View::$uuids = array
		View::$output = false
		View::$request = NULL
		View::$elementCache = "default"';

		$result = str_replace(array("\t", "\r\n", "\n"), "", $result);
		$expected =  str_replace(array("\t", "\r\n", "\n"), "", $expected);
		$this->assertEqual($expected, $result);
	}

/**
 * testLog method
 *
 * @return void
 */
	public function testLog() {
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
 * @return void
 */
	public function testDump() {
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
 * @return void
 */
	public function testGetInstance() {
		$result = Debugger::getInstance();
		$this->assertIsA($result, 'Debugger');

		$result = Debugger::getInstance('DebuggerTestCaseDebugger');
		$this->assertIsA($result, 'DebuggerTestCaseDebugger');

		$result = Debugger::getInstance();
		$this->assertIsA($result, 'DebuggerTestCaseDebugger');

		$result = Debugger::getInstance('Debugger');
		$this->assertIsA($result, 'Debugger');
	}

/**
 * testNoDbCredentials
 *
 * If a connection error occurs, the config variable is passed through exportVar
 * *** our database login credentials such that they are never visible
 *
 * @return void
 */
	public function testNoDbCredentials() {
		$config = array(
			'driver' => 'mysql',
			'persistent' => false,
			'host' => 'void.cakephp.org',
			'login' => 'cakephp-user',
			'password' => 'cakephp-password',
			'database' => 'cakephp-database',
			'prefix' => ''
		);

		$output = Debugger::exportVar($config);

		$expectedArray = array(
			'driver' => 'mysql',
			'persistent' => false,
			'host' => '*****',
			'login' => '*****',
			'password' => '*****',
			'database' => '*****',
			'prefix' => ''
		);
		$expected = Debugger::exportVar($expectedArray);

		$this->assertEqual($expected, $output);
	}

/**
 * test trace exclude
 *
 * @return void
 */
	public function testTraceExclude() {
		$result = Debugger::trace();
		$this->assertPattern('/^DebuggerTest::testTraceExclude/', $result);

		$result = Debugger::trace(array(
			'exclude' => array('DebuggerTest::testTraceExclude')
		));
		$this->assertNoPattern('/^DebuggerTest::testTraceExclude/', $result);
	}
}
