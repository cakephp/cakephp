<?php
/**
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP Project
 * @since         1.2.0
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Test\TestCase\Utility;

use Cake\Controller\Controller;
use Cake\Core\Configure;
use Cake\Log\Log;
use Cake\TestSuite\TestCase;
use Cake\Utility\Debugger;
use Cake\View\View;

/**
 * DebuggerTestCaseDebugger class
 *
 */
class DebuggerTestCaseDebugger extends Debugger {
}

class DebuggableThing {

	public function __debugInfo() {
		return ['foo' => 'bar', 'inner' => new self()];
	}

}

/**
 * DebuggerTest class
 *
 * !!! Be careful with changing code below as it may
 * !!! change line numbers which are used in the tests
 *
 */
class DebuggerTest extends TestCase {

	protected $_restoreError = false;

/**
 * setUp method
 *
 * @return void
 */
	public function setUp() {
		parent::setUp();
		Configure::write('debug', true);
		Configure::write('log', false);
	}

/**
 * tearDown method
 *
 * @return void
 */
	public function tearDown() {
		parent::tearDown();
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
		$this->assertEquals(ini_get('docref_root'), '');
		new Debugger();
		$this->assertEquals(ini_get('docref_root'), 'http://php.net/');
	}

/**
 * test Excerpt writing
 *
 * @return void
 */
	public function testExcerpt() {
		$result = Debugger::excerpt(__FILE__, __LINE__, 2);
		$this->assertTrue(is_array($result));
		$this->assertEquals(5, count($result));
		$this->assertRegExp('/function(.+)testExcerpt/', $result[1]);

		$result = Debugger::excerpt(__FILE__, 2, 2);
		$this->assertTrue(is_array($result));
		$this->assertEquals(4, count($result));

		$pattern = '/<code>.*?<span style\="color\: \#\d+">.*?&lt;\?php/';
		$this->assertRegExp($pattern, $result[0]);

		$result = Debugger::excerpt(__FILE__, 11, 2);
		$this->assertEquals(5, count($result));

		$pattern = '/<span style\="color\: \#\d{6}">\*<\/span>/';
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
		set_error_handler('Cake\Utility\Debugger::showError');
		$this->_restoreError = true;

		$result = Debugger::output(false);
		$this->assertEquals('', $result);
		$out .= '';
		$result = Debugger::output(true);

		$this->assertEquals('Notice', $result[0]['error']);
		$this->assertRegExp('/Undefined variable\:\s+out/', $result[0]['description']);
		$this->assertRegExp('/DebuggerTest::testOutput/i', $result[0]['trace']);

		ob_start();
		Debugger::output('txt');
		$other .= '';
		$result = ob_get_clean();

		$this->assertRegExp('/Undefined variable:\s+other/', $result);
		$this->assertRegExp('/Context:/', $result);
		$this->assertRegExp('/DebuggerTest::testOutput/i', $result);

		ob_start();
		Debugger::output('html');
		$wrong .= '';
		$result = ob_get_clean();
		$this->assertRegExp('/<pre class="cake-error">.+<\/pre>/', $result);
		$this->assertRegExp('/<b>Notice<\/b>/', $result);
		$this->assertRegExp('/variable:\s+wrong/', $result);

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

		$this->assertRegExp('/Undefined variable:\s+buzz/', $result[1]);
		$this->assertRegExp('/<a[^>]+>Code/', $result[1]);
		$this->assertRegExp('/<a[^>]+>Context/', $result[2]);
		$this->assertContains('$wrong = &#039;&#039;', $result[3], 'Context should be HTML escaped.');
	}

/**
 * Tests that changes in output formats using Debugger::output() change the templates used.
 *
 * @return void
 */
	public function testChangeOutputFormats() {
		set_error_handler('Cake\Utility\Debugger::showError');
		$this->_restoreError = true;

		Debugger::output('js', array(
			'traceLine' => '{:reference} - <a href="txmt://open?url=file://{:file}' .
				'&line={:line}">{:path}</a>, line {:line}'
		));
		$result = Debugger::trace();
		$this->assertRegExp('/' . preg_quote('txmt://open?url=file://', '/') . '(\/|[A-Z]:\\\\)' . '/', $result);

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
 * Test that choosing a non-existent format causes an exception
 *
 * @expectedException \Cake\Error\Exception
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
		set_error_handler('Cake\Utility\Debugger::showError');
		$this->_restoreError = true;

		Debugger::addFormat('js', array(
			'traceLine' => '{:reference} - <a href="txmt://open?url=file://{:file}' .
				'&line={:line}">{:path}</a>, line {:line}'
		));
		Debugger::outputAs('js');

		$result = Debugger::trace();
		$this->assertRegExp('/' . preg_quote('txmt://open?url=file://', '/') . '(\/|[A-Z]:\\\\)' . '/', $result);

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
		set_error_handler('Cake\Utility\Debugger::showError');
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
 *
 * @return void
 */
	public function customFormat($error, $strings) {
		return $error['error'] . ': I eated an error ' . $error['file'];
	}

/**
 * testTrimPath method
 *
 * @return void
 */
	public function testTrimPath() {
		$this->assertEquals('APP/', Debugger::trimPath(APP));
		$this->assertEquals('CORE' . DS . 'src' . DS, Debugger::trimPath(CAKE));
		$this->assertEquals('Some/Other/Path', Debugger::trimPath('Some/Other/Path'));
	}

/**
 * testExportVar method
 *
 * @return void
 */
	public function testExportVar() {
		$Controller = new Controller();
		$Controller->helpers = array('Html', 'Form');
		$View = $Controller->createView();
		$View->int = 2;
		$View->float = 1.333;

		$result = Debugger::exportVar($View);
		$expected = <<<TEXT
object(Cake\View\View) {
	Blocks => object(Cake\View\ViewBlock) {}
	plugin => null
	name => ''
	passedArgs => []
	helpers => [
		(int) 0 => 'Html',
		(int) 1 => 'Form'
	]
	viewPath => ''
	view => null
	layout => 'default'
	layoutPath => null
	autoLayout => true
	subDir => null
	theme => null
	cacheAction => false
	validationErrors => []
	hasRendered => false
	uuids => []
	request => object(Cake\Network\Request) {}
	response => object(Cake\Network\Response) {}
	elementCache => 'default'
	elementCacheSettings => []
	viewVars => []
	Html => object(Cake\View\Helper\HtmlHelper) {}
	Form => object(Cake\View\Helper\FormHelper) {}
	int => (int) 2
	float => (float) 1.333
	[protected] _helpers => object(Cake\View\HelperRegistry) {}
	[protected] _ext => '.ctp'
	[protected] _passedVars => [
		(int) 0 => 'viewVars',
		(int) 1 => 'autoLayout',
		(int) 2 => 'helpers',
		(int) 3 => 'view',
		(int) 4 => 'layout',
		(int) 5 => 'name',
		(int) 6 => 'theme',
		(int) 7 => 'layoutPath',
		(int) 8 => 'viewPath',
		(int) 9 => 'plugin',
		(int) 10 => 'passedArgs',
		(int) 11 => 'cacheAction'
	]
	[protected] _scripts => []
	[protected] _paths => []
	[protected] _pathsForPlugin => []
	[protected] _parents => []
	[protected] _current => null
	[protected] _currentType => ''
	[protected] _stack => []
	[protected] _eventManager => object(Cake\Event\EventManager) {}
}
TEXT;

		$this->assertTextEquals($expected, $result);

		$data = array(
			1 => 'Index one',
			5 => 'Index five'
		);
		$result = Debugger::exportVar($data);
		$expected = <<<TEXT
[
	(int) 1 => 'Index one',
	(int) 5 => 'Index five'
]
TEXT;
		$this->assertTextEquals($expected, $result);

		$data = array(
			'key' => array(
				'value'
			)
		);
		$result = Debugger::exportVar($data, 1);
		$expected = <<<TEXT
[
	'key' => [
		[maximum depth reached]
	]
]
TEXT;
		$this->assertTextEquals($expected, $result);

		$data = false;
		$result = Debugger::exportVar($data);
		$expected = <<<TEXT
false
TEXT;
		$this->assertTextEquals($expected, $result);

		$file = fopen('php://output', 'w');
		fclose($file);
		$result = Debugger::exportVar($file);
		$this->assertTextEquals('unknown', $result);
	}

/**
 * Test exporting various kinds of false.
 *
 * @return void
 */
	public function testExportVarZero() {
		$data = array(
			'nothing' => '',
			'null' => null,
			'false' => false,
			'szero' => '0',
			'zero' => 0
		);
		$result = Debugger::exportVar($data);
		$expected = <<<TEXT
[
	'nothing' => '',
	'null' => null,
	'false' => false,
	'szero' => '0',
	'zero' => (int) 0
]
TEXT;
		$this->assertTextEquals($expected, $result);
	}

/**
 * testLog method
 *
 * @return void
 */
	public function testLog() {
		$mock = $this->getMock('Cake\Log\Engine\BaseLog', ['write']);
		Log::config('test', ['engine' => $mock]);

		$mock->expects($this->at(0))
			->method('write')
			->with('debug', $this->logicalAnd(
				$this->stringContains('DebuggerTest::testLog'),
				$this->stringContains('cool')
			));

		$mock->expects($this->at(1))
			->method('write')
			->with('debug', $this->logicalAnd(
				$this->stringContains('DebuggerTest::testLog'),
				$this->stringContains('[main]'),
				$this->stringContains("'whatever',"),
				$this->stringContains("'here'")
			));

		Debugger::log('cool');
		Debugger::log(array('whatever', 'here'));

		Log::drop('test');
	}

/**
 * test log() depth
 *
 * @return void
 */
	public function testLogDepth() {
		$mock = $this->getMock('Cake\Log\Engine\BaseLog', ['write']);
		Log::config('test', ['engine' => $mock]);

		$mock->expects($this->at(0))
			->method('write')
			->with('debug', $this->logicalAnd(
				$this->stringContains('DebuggerTest::testLog'),
				$this->stringContains('test'),
				$this->logicalNot($this->stringContains('val'))
			));

		$val = array(
			'test' => array('key' => 'val')
		);
		Debugger::log($val, LOG_DEBUG, 0);
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
		));
		ob_start();
		Debugger::dump($var);
		$result = ob_get_clean();

		$open = php_sapi_name() === 'cli' ? "\n" : '<pre>';
		$close = php_sapi_name() === 'cli' ? "\n" : '</pre>';
		$expected = <<<TEXT
{$open}[
	'People' => [
		(int) 0 => [
			'name' => 'joeseph',
			'coat' => 'technicolor',
			'hair_color' => 'brown'
		],
		(int) 1 => [
			'name' => 'Shaft',
			'coat' => 'black',
			'hair' => 'black'
		]
	]
]{$close}
TEXT;
		$this->assertTextEquals($expected, $result);

		ob_start();
		Debugger::dump($var, 1);
		$result = ob_get_clean();

		$open = php_sapi_name() === 'cli' ? "\n" : '<pre>';
		$close = php_sapi_name() === 'cli' ? "\n" : '</pre>';
		$expected = <<<TEXT
{$open}[
	'People' => [
		[maximum depth reached]
	]
]{$close}
TEXT;
		$this->assertTextEquals($expected, $result);
	}

/**
 * test getInstance.
 *
 * @return void
 */
	public function testGetInstance() {
		$result = Debugger::getInstance();
		$this->assertInstanceOf('Cake\Utility\Debugger', $result);

		$result = Debugger::getInstance(__NAMESPACE__ . '\DebuggerTestCaseDebugger');
		$this->assertInstanceOf(__NAMESPACE__ . '\DebuggerTestCaseDebugger', $result);

		$result = Debugger::getInstance();
		$this->assertInstanceOf(__NAMESPACE__ . '\DebuggerTestCaseDebugger', $result);

		$result = Debugger::getInstance('Cake\Utility\Debugger');
		$this->assertInstanceOf('Cake\Utility\Debugger', $result);
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
			'datasource' => 'mysql',
			'persistent' => false,
			'host' => 'void.cakephp.org',
			'login' => 'cakephp-user',
			'password' => 'cakephp-password',
			'database' => 'cakephp-database',
			'prefix' => ''
		);

		$output = Debugger::exportVar($config);

		$expectedArray = array(
			'datasource' => 'mysql',
			'persistent' => false,
			'host' => '*****',
			'login' => '*****',
			'password' => '*****',
			'database' => '*****',
			'prefix' => ''
		);
		$expected = Debugger::exportVar($expectedArray);

		$this->assertEquals($expected, $output);
	}

/**
 * Test that exportVar() doesn't loop through recursive structures.
 *
 * @return void
 */
	public function testExportVarRecursion() {
		$output = Debugger::exportVar($GLOBALS);
		$this->assertContains("'GLOBALS' => [recursion]", $output);
	}

/**
 * test trace exclude
 *
 * @return void
 */
	public function testTraceExclude() {
		$result = Debugger::trace();
		$this->assertRegExp('/^Cake\\\Test\\\TestCase\\\Utility\\\DebuggerTest::testTraceExclude/', $result);

		$result = Debugger::trace(array(
			'exclude' => array('Cake\Test\TestCase\Utility\DebuggerTest::testTraceExclude')
		));
		$this->assertNotRegExp('/^Cake\\\Test\\\TestCase\\\Utility\\\DebuggerTest::testTraceExclude/', $result);
	}

/**
 * Tests that __debugInfo is used when available
 *
 * @return void
 */
	public function testDebugInfo() {
		$object = new DebuggableThing();
		$result = Debugger::exportVar($object, 2);
		$expected = <<<eos
object(Cake\Test\TestCase\Utility\DebuggableThing) {

	'foo' => 'bar',
	'inner' => object(Cake\Test\TestCase\Utility\DebuggableThing) {

		[maximum depth reached]
	
	}

}
eos;
		$this->assertEquals($expected, $result);
	}

}
