<?php
/**
 * JavascriptHelperTest file
 *
 * PHP versions 4 and 5
 *
 * CakePHP(tm) Tests <https://trac.cakephp.org/wiki/Developement/TestSuite>
 * Copyright 2006-2010, Cake Software Foundation, Inc.
 *
 *  Licensed under The Open Group Test Suite License
 *  Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright 2006-2010, Cake Software Foundation, Inc.
 * @link          https://trac.cakephp.org/wiki/Developement/TestSuite CakePHP(tm) Tests
 * @package       cake
 * @subpackage    cake.tests.cases.libs.view.helpers
 * @since         CakePHP(tm) v 1.2.0.4206
 * @license       http://www.opensource.org/licenses/opengroup.php The Open Group Test Suite License
 */
App::import('Core', array('Controller', 'View', 'ClassRegistry', 'View'));
App::import('Helper', array('Javascript', 'Html', 'Form'));

/**
 * TheJsTestController class
 *
 * @package       cake
 * @subpackage    cake.tests.cases.libs.view.helpers
 */
class TheJsTestController extends Controller {

/**
 * name property
 *
 * @var string 'TheTest'
 * @access public
 */
	var $name = 'TheTest';

/**
 * uses property
 *
 * @var mixed null
 * @access public
 */
	var $uses = null;
}

/**
 * TheView class
 *
 * @package       cake
 * @subpackage    cake.tests.cases.libs.view.helpers
 */
class TheView extends View {

/**
 * scripts method
 *
 * @access public
 * @return void
 */
	function scripts() {
		return $this->__scripts;
	}
}

/**
 * TestJavascriptObject class
 *
 * @package       cake
 * @subpackage    cake.tests.cases.libs.view.helpers
 */
class TestJavascriptObject {

/**
 * property1 property
 *
 * @var string 'value1'
 * @access public
 */
	var $property1 = 'value1';

/**
 * property2 property
 *
 * @var int 2
 * @access public
 */
	var $property2 = 2;
}

/**
 * JavascriptTest class
 *
 * @package       test_suite
 * @subpackage    test_suite.cases.libs
 * @since         CakePHP Test Suite v 1.0.0.0
 */
class JavascriptTest extends CakeTestCase {

/**
 * Regexp for CDATA start block
 *
 * @var string
 */
	var $cDataStart = 'preg:/^\/\/<!\[CDATA\[[\n\r]*/';

/**
 * Regexp for CDATA end block
 *
 * @var string
 */
	var $cDataEnd = 'preg:/[^\]]*\]\]\>[\s\r\n]*/';

/**
 * setUp method
 *
 * @access public
 * @return void
 */
	function startTest() {
		$this->Javascript =& new JavascriptHelper();
		$this->Javascript->Html =& new HtmlHelper();
		$this->Javascript->Form =& new FormHelper();
		$this->View =& new TheView(new TheJsTestController());
		ClassRegistry::addObject('view', $this->View);
	}

/**
 * tearDown method
 *
 * @access public
 * @return void
 */
	function endTest() {
		unset($this->Javascript->Html);
		unset($this->Javascript->Form);
		unset($this->Javascript);
		ClassRegistry::removeObject('view');
		unset($this->View);
	}

/**
 * testConstruct method
 *
 * @access public
 * @return void
 */
	function testConstruct() {
		$Javascript =& new JavascriptHelper(array('safe'));
		$this->assertTrue($Javascript->safe);

		$Javascript =& new JavascriptHelper(array('safe' => false));
		$this->assertFalse($Javascript->safe);
	}

/**
 * testLink method
 *
 * @access public
 * @return void
 */
	function testLink() {
		Configure::write('Asset.timestamp', false);
		$result = $this->Javascript->link('script.js');
		$expected = '<script type="text/javascript" src="js/script.js"></script>';
		$this->assertEqual($result, $expected);

		$result = $this->Javascript->link('script');
		$expected = '<script type="text/javascript" src="js/script.js"></script>';
		$this->assertEqual($result, $expected);

		$result = $this->Javascript->link('scriptaculous.js?load=effects');
		$expected = '<script type="text/javascript" src="js/scriptaculous.js?load=effects"></script>';
		$this->assertEqual($result, $expected);

		$result = $this->Javascript->link('some.json.libary');
		$expected = '<script type="text/javascript" src="js/some.json.libary.js"></script>';
		$this->assertEqual($result, $expected);

		$result = $this->Javascript->link('jquery-1.1.2');
		$expected = '<script type="text/javascript" src="js/jquery-1.1.2.js"></script>';
		$this->assertEqual($result, $expected);

		$result = $this->Javascript->link('jquery-1.1.2');
		$expected = '<script type="text/javascript" src="js/jquery-1.1.2.js"></script>';
		$this->assertEqual($result, $expected);

		$result = $this->Javascript->link('/plugin/js/jquery-1.1.2');
		$expected = '<script type="text/javascript" src="/plugin/js/jquery-1.1.2.js"></script>';
		$this->assertEqual($result, $expected);

		$result = $this->Javascript->link('/some_other_path/myfile.1.2.2.min.js');
		$expected = '<script type="text/javascript" src="/some_other_path/myfile.1.2.2.min.js"></script>';
		$this->assertEqual($result, $expected);

		$result = $this->Javascript->link('some_other_path/myfile.1.2.2.min.js');
		$expected = '<script type="text/javascript" src="js/some_other_path/myfile.1.2.2.min.js"></script>';
		$this->assertEqual($result, $expected);

		$result = $this->Javascript->link('some_other_path/myfile.1.2.2.min');
		$expected = '<script type="text/javascript" src="js/some_other_path/myfile.1.2.2.min.js"></script>';
		$this->assertEqual($result, $expected);

		$result = $this->Javascript->link('http://example.com/jquery.js');
		$expected = '<script type="text/javascript" src="http://example.com/jquery.js"></script>';
		$this->assertEqual($result, $expected);

		$result = $this->Javascript->link(array('prototype.js', 'scriptaculous.js'));
		$this->assertPattern('/^\s*<script\s+type="text\/javascript"\s+src=".*js\/prototype\.js"[^<>]*><\/script>/', $result);
		$this->assertPattern('/<\/script>\s*<script[^<>]+>/', $result);
		$this->assertPattern('/<script\s+type="text\/javascript"\s+src=".*js\/scriptaculous\.js"[^<>]*><\/script>\s*$/', $result);

		$result = $this->Javascript->link('jquery-1.1.2', false);
		$resultScripts = $this->View->scripts();
		reset($resultScripts);
		$expected = '<script type="text/javascript" src="js/jquery-1.1.2.js"></script>';
		$this->assertNull($result);
		$this->assertEqual(count($resultScripts), 1);
		$this->assertEqual(current($resultScripts), $expected);
	}

/**
 * testFilteringAndTimestamping method
 *
 * @access public
 * @return void
 */
	function testFilteringAndTimestamping() {
		if ($this->skipIf(!is_writable(JS), 'JavaScript directory not writable, skipping JS asset timestamp tests. %s')) {
			return;
		}

		cache(str_replace(WWW_ROOT, '', JS) . '__cake_js_test.js', 'alert("test")', '+999 days', 'public');
		$timestamp = substr(strtotime('now'), 0, 8);

		Configure::write('Asset.timestamp', true);
		$result = $this->Javascript->link('__cake_js_test');
		$this->assertPattern('/^<script[^<>]+src=".*js\/__cake_js_test\.js\?' . $timestamp . '[0-9]{2}"[^<>]*>/', $result);

		$debug = Configure::read('debug');
		Configure::write('debug', 0);
		$result = $this->Javascript->link('__cake_js_test');
		$expected = '<script type="text/javascript" src="js/__cake_js_test.js"></script>';
		$this->assertEqual($result, $expected);

		Configure::write('Asset.timestamp', 'force');
		$result = $this->Javascript->link('__cake_js_test');
		$this->assertPattern('/^<script[^<>]+src=".*js\/__cake_js_test.js\?' . $timestamp . '[0-9]{2}"[^<>]*>/', $result);

		Configure::write('debug', $debug);
		Configure::write('Asset.timestamp', false);

		$old = Configure::read('Asset.filter.js');

		Configure::write('Asset.filter.js', 'js.php');
		$result = $this->Javascript->link('__cake_js_test');
		$this->assertPattern('/^<script[^<>]+src=".*cjs\/__cake_js_test\.js"[^<>]*>/', $result);

		Configure::write('Asset.filter.js', true);
		$result = $this->Javascript->link('jquery-1.1.2');
		$expected = '<script type="text/javascript" src="cjs/jquery-1.1.2.js"></script>';
		$this->assertEqual($result, $expected);

		$result = $this->Javascript->link('folderjs/jquery-1.1.2');
		$expected = '<script type="text/javascript" src="cjs/folderjs/jquery-1.1.2.js"></script>';
		$this->assertEqual($result, $expected);

		if ($old === null) {
			Configure::delete('Asset.filter.js');
		}

		$debug = Configure::read('debug');
		$webroot = $this->Javascript->webroot;

		Configure::write('debug', 0);
		Configure::write('Asset.timestamp', 'force');

		$this->Javascript->webroot = '/testing/';
		$result = $this->Javascript->link('__cake_js_test');
		$this->assertPattern('/^<script[^<>]+src="\/testing\/js\/__cake_js_test\.js\?\d+"[^<>]*>/', $result);

		$this->Javascript->webroot = '/testing/longer/';
		$result = $this->Javascript->link('__cake_js_test');
		$this->assertPattern('/^<script[^<>]+src="\/testing\/longer\/js\/__cake_js_test\.js\?\d+"[^<>]*>/', $result);

		$this->Javascript->webroot = $webroot;
		Configure::write('debug', $debug);

		unlink(JS . '__cake_js_test.js');
	}

/**
 * testValue method
 *
 * @access public
 * @return void
 */
	function testValue() {
		$result = $this->Javascript->value(array('title' => 'New thing', 'indexes' => array(5, 6, 7, 8)));
		$expected = '{"title":"New thing","indexes":[5,6,7,8]}';
		$this->assertEqual($result, $expected);

		$result = $this->Javascript->value(null);
		$this->assertEqual($result, 'null');

		$result = $this->Javascript->value(true);
		$this->assertEqual($result, 'true');

		$result = $this->Javascript->value(false);
		$this->assertEqual($result, 'false');

		$result = $this->Javascript->value(5);
		$this->assertEqual($result, '5');

		$result = $this->Javascript->value(floatval(5.3));
		$this->assertPattern('/^5.3[0]+$/', $result);

		$result = $this->Javascript->value('');
		$expected = '""';
		$this->assertEqual($result, $expected);

		$result = $this->Javascript->value('CakePHP' . "\n" . 'Rapid Development Framework');
		$expected = '"CakePHP\\nRapid Development Framework"';
		$this->assertEqual($result, $expected);

		$result = $this->Javascript->value('CakePHP' . "\r\n" . 'Rapid Development Framework' . "\r" . 'For PHP');
		$expected = '"CakePHP\\nRapid Development Framework\\nFor PHP"';
		$this->assertEqual($result, $expected);

		$result = $this->Javascript->value('CakePHP: "Rapid Development Framework"');
		$expected = '"CakePHP: \\"Rapid Development Framework\\""';
		$this->assertEqual($result, $expected);

		$result = $this->Javascript->value('CakePHP: \'Rapid Development Framework\'');
		$expected = '"CakePHP: \\\'Rapid Development Framework\\\'"';
		$this->assertEqual($result, $expected);
	}

/**
 * testObjectGeneration method
 *
 * @access public
 * @return void
 */
	function testObjectGeneration() {
		$object = array('title' => 'New thing', 'indexes' => array(5, 6, 7, 8));
		$result = $this->Javascript->object($object);
		$expected = '{"title":"New thing","indexes":[5,6,7,8]}';
		$this->assertEqual($result, $expected);

		$result = $this->Javascript->object(array('default' => 0));
		$expected = '{"default":0}';
		$this->assertEqual($result, $expected);

		$result = $this->Javascript->object(array(
			'2007' => array(
				'Spring' => array('1' => array('id' => 1, 'name' => 'Josh'), '2' => array('id' => 2, 'name' => 'Becky')),
				'Fall' => array('1' => array('id' => 1, 'name' => 'Josh'), '2' => array('id' => 2, 'name' => 'Becky'))
			), '2006' => array(
				'Spring' => array('1' => array('id' => 1, 'name' => 'Josh'), '2' => array('id' => 2, 'name' => 'Becky')),
				'Fall' => array('1' => array('id' => 1, 'name' => 'Josh'), '2' => array('id' => 2, 'name' => 'Becky')
			))
		));
		$expected = '{"2007":{"Spring":{"1":{"id":1,"name":"Josh"},"2":{"id":2,"name":"Becky"}},"Fall":{"1":{"id":1,"name":"Josh"},"2":{"id":2,"name":"Becky"}}},"2006":{"Spring":{"1":{"id":1,"name":"Josh"},"2":{"id":2,"name":"Becky"}},"Fall":{"1":{"id":1,"name":"Josh"},"2":{"id":2,"name":"Becky"}}}}';
		$this->assertEqual($result, $expected);

		if (ini_get('precision') >= 12) {
			$number = 3.141592653589;
			if (!$this->Javascript->useNative) {
				$number = sprintf("%.11f", $number);
			}

			$result = $this->Javascript->object(array('Object' => array(true, false, 1, '02101', 0, -1, 3.141592653589, "1")));
			$expected = '{"Object":[true,false,1,"02101",0,-1,' . $number . ',"1"]}';
			$this->assertEqual($result, $expected);

			$result = $this->Javascript->object(array('Object' => array(true => true, false, -3.141592653589, -10)));
			$expected = '{"Object":{"1":true,"2":false,"3":' . (-1 * $number) . ',"4":-10}}';
			$this->assertEqual($result, $expected);
		}

		$result = $this->Javascript->object(new TestJavascriptObject());
		$expected = '{"property1":"value1","property2":2}';
		$this->assertEqual($result, $expected);

		$object = array('title' => 'New thing', 'indexes' => array(5, 6, 7, 8));
		$result = $this->Javascript->object($object, array('block' => true));
		$expected = array(
			'script' => array('type' => 'text/javascript'),
			$this->cDataStart,
			'{"title":"New thing","indexes":[5,6,7,8]}',
			$this->cDataEnd,
			'/script'
		);
		$this->assertTags($result, $expected);

		$object = array('title' => 'New thing', 'indexes' => array(5, 6, 7, 8), 'object' => array('inner' => array('value' => 1)));
		$result = $this->Javascript->object($object);
		$expected = '{"title":"New thing","indexes":[5,6,7,8],"object":{"inner":{"value":1}}}';
		$this->assertEqual($result, $expected);

		foreach (array('true' => true, 'false' => false, 'null' => null) as $expected => $data) {
			$result = $this->Javascript->object($data);
			$this->assertEqual($result, $expected);
		}

		if ($this->Javascript->useNative) {
			$this->Javascript->useNative = false;
			$this->testObjectGeneration();
			$this->Javascript->useNative = true;
		}
	}

/**
 * testObjectNonNative method
 *
 * @access public
 * @return void
 */
	function testObjectNonNative() {
		$oldNative = $this->Javascript->useNative;
		$this->Javascript->useNative = false;

		$object = array(
			'Object' => array(
				'key1' => 'val1',
				'key2' => 'val2',
				'key3' => 'val3'
			)
		);

		$expected = '{"Object":{"key1":val1,"key2":"val2","key3":val3}}';
		$result = $this->Javascript->object($object, array('quoteKeys' => false, 'stringKeys' => array('key1', 'key3')));
		$this->assertEqual($result, $expected);

		$this->Javascript->useNative = $oldNative;
	}

/**
 * testScriptBlock method
 *
 * @access public
 * @return void
 */
	function testScriptBlock() {
		$result = $this->Javascript->codeBlock('something');
		$expected = array(
			'script' => array('type' => 'text/javascript'),
			$this->cDataStart,
			'something',
			$this->cDataEnd,
			'/script'
		);
		$this->assertTags($result, $expected);

		$result = $this->Javascript->codeBlock('something', array('allowCache' => true, 'safe' => false));
		$expected = array(
			'script' => array('type' => 'text/javascript'),
			'something',
			'/script'
		);
		$this->assertTags($result, $expected);

		$result = $this->Javascript->codeBlock('something', array('allowCache' => false, 'safe' => false));
		$expected = array(
			'script' => array('type' => 'text/javascript'),
			'something',
			'/script'
		);
		$this->assertTags($result, $expected);

		$result = $this->Javascript->codeBlock('something', true);
		$expected = array(
			'script' => array('type' => 'text/javascript'),
			$this->cDataStart,
			'something',
			$this->cDataEnd,
			'/script'
		);
		$this->assertTags($result, $expected);

		$result = $this->Javascript->codeBlock('something', false);
		$expected = array(
			'script' => array('type' => 'text/javascript'),
			$this->cDataStart,
			'something',
			$this->cDataEnd,
			'/script'
		);
		$this->assertTags($result, $expected);

		$result = $this->Javascript->codeBlock('something', array('safe' => false));
		$expected = array(
			'script' => array('type' => 'text/javascript'),
			'something',
			'/script'
		);
		$this->assertTags($result, $expected);

		$result = $this->Javascript->codeBlock('something', array('safe' => true));
		$expected = array(
			'script' => array('type' => 'text/javascript'),
			$this->cDataStart,
			'something',
			$this->cDataEnd,
			'/script'
		);
		$this->assertTags($result, $expected);

		$result = $this->Javascript->codeBlock(null, array('safe' => true, 'allowCache' => false));
		$this->assertNull($result);
		echo 'this is some javascript';

		$result = $this->Javascript->blockEnd();
		$expected = array(
			'script' => array('type' => 'text/javascript'),
			$this->cDataStart,
			'this is some javascript',
			$this->cDataEnd,
			'/script'
		);
		$this->assertTags($result, $expected);

		$result = $this->Javascript->codeBlock();
		$this->assertNull($result);
		echo "alert('hey');";
		$result = $this->Javascript->blockEnd();

		$expected = array(
			'script' => array('type' => 'text/javascript'),
			$this->cDataStart,
			"alert('hey');",
			$this->cDataEnd,
			'/script'
		);
		$this->assertTags($result, $expected);

		$this->Javascript->cacheEvents(false, true);
		$this->assertFalse($this->Javascript->inBlock);

		$result = $this->Javascript->codeBlock();
		$this->assertIdentical($result, null);
		$this->assertTrue($this->Javascript->inBlock);
		echo 'alert("this is a buffered script");';

		$result = $this->Javascript->blockEnd();
		$this->assertIdentical($result, null);
		$this->assertFalse($this->Javascript->inBlock);

		$result = $this->Javascript->getCache();
		$this->assertEqual('alert("this is a buffered script");', $result);
	}

/**
 * testOutOfLineScriptWriting method
 *
 * @access public
 * @return void
 */
	function testOutOfLineScriptWriting() {
		echo $this->Javascript->codeBlock('$(document).ready(function() { });', array('inline' => false));

		$this->Javascript->codeBlock(null, array('inline' => false));
		echo '$(function(){ });';
		$this->Javascript->blockEnd();
		$script = $this->View->scripts();

		$this->assertEqual(count($script), 2);
		$this->assertPattern('/' . preg_quote('$(document).ready(function() { });', '/') . '/', $script[0]);
		$this->assertPattern('/' . preg_quote('$(function(){ });', '/') . '/', $script[1]);
	}

/**
 * testEvent method
 *
 * @access public
 * @return void
 */
	function testEvent() {
		$result = $this->Javascript->event('myId', 'click', 'something();');
		$expected = array(
			'script' => array('type' => 'text/javascript'),
			$this->cDataStart,
			"Event.observe($('myId'), 'click', function(event) { something(); }, false);",
			$this->cDataEnd,
			'/script'
		);
		$this->assertTags($result, $expected);

		$result = $this->Javascript->event('myId', 'click', 'something();', array('safe' => false));
		$expected = array(
			'script' => array('type' => 'text/javascript'),
			"Event.observe($('myId'), 'click', function(event) { something(); }, false);",
			'/script'
		);
		$this->assertTags($result, $expected);

		$result = $this->Javascript->event('myId', 'click');
		$expected = array(
			'script' => array('type' => 'text/javascript'),
			$this->cDataStart,
			"Event.observe($('myId'), 'click', function(event) {  }, false);",
			$this->cDataEnd,
			'/script'
		);
		$this->assertTags($result, $expected);

		$result = $this->Javascript->event('myId', 'click', 'something();', false);
		$expected = array(
			'script' => array('type' => 'text/javascript'),
			$this->cDataStart,
			"Event.observe($('myId'), 'click', function(event) { something(); }, false);",
			$this->cDataEnd,
			'/script'
		);
		$this->assertTags($result, $expected);

		$result = $this->Javascript->event('myId', 'click', 'something();', array('useCapture' => true));
		$expected = array(
			'script' => array('type' => 'text/javascript'),
			$this->cDataStart,
			"Event.observe($('myId'), 'click', function(event) { something(); }, true);",
			$this->cDataEnd,
			'/script'
		);
		$this->assertTags($result, $expected);

		$result = $this->Javascript->event('document', 'load');
		$expected = array(
			'script' => array('type' => 'text/javascript'),
			$this->cDataStart,
			"Event.observe(document, 'load', function(event) {  }, false);",
			$this->cDataEnd,
			'/script'
		);
		$this->assertTags($result, $expected);

		$result = $this->Javascript->event('$(\'myId\')', 'click', 'something();', array('safe' => false));
		$expected = array(
			'script' => array('type' => 'text/javascript'),
			"Event.observe($('myId'), 'click', function(event) { something(); }, false);",
			'/script'
		);
		$this->assertTags($result, $expected);

		$result = $this->Javascript->event('\'document\'', 'load', 'something();', array('safe' => false));
		$expected = array(
			'script' => array('type' => 'text/javascript'),
			"Event.observe('document', 'load', function(event) { something(); }, false);",
			'/script'
		);
		$this->assertTags($result, $expected);

		$this->Javascript->cacheEvents();
		$result = $this->Javascript->event('myId', 'click', 'something();');
		$this->assertNull($result);

		$result = $this->Javascript->getCache();
		$this->assertPattern('/^' . str_replace('/', '\\/', preg_quote('Event.observe($(\'myId\'), \'click\', function(event) { something(); }, false);')) . '$/s', $result);

		$result = $this->Javascript->event('#myId', 'alert(event);');
		$this->assertNull($result);

		$result = $this->Javascript->getCache();
		$this->assertPattern('/^\s*var Rules = {\s*\'#myId\': function\(element, event\)\s*{\s*alert\(event\);\s*}\s*}\s*EventSelectors\.start\(Rules\);\s*$/s', $result);
	}

/**
 * testWriteEvents method
 *
 * @access public
 * @return void
 */
	function testWriteEvents() {
		$this->Javascript->cacheEvents();
		$result = $this->Javascript->event('myId', 'click', 'something();');
		$this->assertNull($result);

		$result = $this->Javascript->writeEvents();
		$expected = array(
			'script' => array('type' => 'text/javascript'),
			$this->cDataStart,
			"Event.observe($('myId'), 'click', function(event) { something(); }, false);",
			$this->cDataEnd,
			'/script'
		);
		$this->assertTags($result, $expected);

		$result = $this->Javascript->getCache();
		$this->assertTrue(empty($result));

		$this->Javascript->cacheEvents();
		$result = $this->Javascript->event('myId', 'click', 'something();');
		$this->assertNull($result);

		$result = $this->Javascript->writeEvents(false);
		$resultScripts = $this->View->scripts();
		reset($resultScripts);
		$this->assertNull($result);
		$this->assertEqual(count($resultScripts), 1);
		$result = current($resultScripts);

		$expected = array(
			'script' => array('type' => 'text/javascript'),
			$this->cDataStart,
			"Event.observe($('myId'), 'click', function(event) { something(); }, false);",
			$this->cDataEnd,
			'/script'
		);
		$this->assertTags($result, $expected);

		$result = $this->Javascript->getCache();
		$this->assertTrue(empty($result));
	}

/**
 * testEscapeScript method
 *
 * @access public
 * @return void
 */
	function testEscapeScript() {
		$result = $this->Javascript->escapeScript('');
		$expected = '';
		$this->assertEqual($result, $expected);

		$result = $this->Javascript->escapeScript('CakePHP' . "\n" . 'Rapid Development Framework');
		$expected = 'CakePHP\\nRapid Development Framework';
		$this->assertEqual($result, $expected);

		$result = $this->Javascript->escapeScript('CakePHP' . "\r\n" . 'Rapid Development Framework' . "\r" . 'For PHP');
		$expected = 'CakePHP\\nRapid Development Framework\\nFor PHP';
		$this->assertEqual($result, $expected);

		$result = $this->Javascript->escapeScript('CakePHP: "Rapid Development Framework"');
		$expected = 'CakePHP: \\"Rapid Development Framework\\"';
		$this->assertEqual($result, $expected);

		$result = $this->Javascript->escapeScript('CakePHP: \'Rapid Development Framework\'');
		$expected = 'CakePHP: \\\'Rapid Development Framework\\\'';
		$this->assertEqual($result, $expected);
	}

/**
 * testEscapeString method
 *
 * @access public
 * @return void
 */
	function testEscapeString() {
		$result = $this->Javascript->escapeString('');
		$expected = '';
		$this->assertEqual($result, $expected);

		$result = $this->Javascript->escapeString('CakePHP' . "\n" . 'Rapid Development Framework');
		$expected = 'CakePHP\\nRapid Development Framework';
		$this->assertEqual($result, $expected);

		$result = $this->Javascript->escapeString('CakePHP' . "\r\n" . 'Rapid Development Framework' . "\r" . 'For PHP');
		$expected = 'CakePHP\\nRapid Development Framework\\nFor PHP';
		$this->assertEqual($result, $expected);

		$result = $this->Javascript->escapeString('CakePHP: "Rapid Development Framework"');
		$expected = 'CakePHP: \\"Rapid Development Framework\\"';
		$this->assertEqual($result, $expected);

		$result = $this->Javascript->escapeString('CakePHP: \'Rapid Development Framework\'');
		$expected = "CakePHP: \\'Rapid Development Framework\\'";
		$this->assertEqual($result, $expected);

		$result = $this->Javascript->escapeString('my \\"string\\"');
		$expected = 'my \\\\\\"string\\\\\\"';
		$this->assertEqual($result, $expected);

		$result = $this->Javascript->escapeString('my string\nanother line');
		$expected = 'my string\\\nanother line';
		$this->assertEqual($result, $expected);

		$result = $this->Javascript->escapeString('String with \n string that looks like newline');
		$expected = 'String with \\\n string that looks like newline';
		$this->assertEqual($result, $expected);

		$result = $this->Javascript->escapeString('String with \n string that looks like newline');
		$expected = 'String with \\\n string that looks like newline';
		$this->assertEqual($result, $expected);
	}

/**
 * test string escaping and compare to json_encode()
 *
 * @return void
 */
	function testStringJsonEncodeCompliance() {
		if (!function_exists('json_encode')) {
			return;
		}
		$this->Javascript->useNative = false;
		$data = array();
		$data['mystring'] = "simple string";
		$this->assertEqual(json_encode($data), $this->Javascript->object($data));

		$data['mystring'] = "strÃ¯ng with spÃ©cial chÃ¢rs";
		$this->assertEqual(json_encode($data), $this->Javascript->object($data));

		$data['mystring'] = "a two lines\nstring";
		$this->assertEqual(json_encode($data), $this->Javascript->object($data));

		$data['mystring'] = "a \t tabbed \t string";
		$this->assertEqual(json_encode($data), $this->Javascript->object($data));

		$data['mystring'] = "a \"double-quoted\" string";
		$this->assertEqual(json_encode($data), $this->Javascript->object($data));

		$data['mystring'] = 'a \\"double-quoted\\" string';
		$this->assertEqual(json_encode($data), $this->Javascript->object($data));
	}

/**
 * test that text encoded with Javascript::object decodes properly
 *
 * @return void
 */
	function testObjectDecodeCompatibility() {
		if (!function_exists('json_decode')) {
			return;
		}
		$this->Javascript->useNative = false;

		$data = array("simple string");
		$result = $this->Javascript->object($data);
		$this->assertEqual(json_decode($result), $data);

		$data = array('my \"string\"');
		$result = $this->Javascript->object($data);
		$this->assertEqual(json_decode($result), $data);

		$data = array('my \\"string\\"');
		$result = $this->Javascript->object($data);
		$this->assertEqual(json_decode($result), $data);
	}

/**
 * testAfterRender method
 *
 * @access public
 * @return void
 */
	function testAfterRender() {
		$this->Javascript->cacheEvents();
		$result = $this->Javascript->event('myId', 'click', 'something();');
		$this->assertNull($result);

		ob_start();
		$this->Javascript->afterRender();
		$result = ob_get_clean();

		$expected = array(
			'script' => array('type' => 'text/javascript'),
			$this->cDataStart,
			"Event.observe($('myId'), 'click', function(event) { something(); }, false);",
			$this->cDataEnd,
			'/script'
		);
		$this->assertTags($result, $expected);

		$result = $this->Javascript->getCache();
		$this->assertTrue(empty($result));

		$old = $this->Javascript->enabled;
		$this->Javascript->enabled = false;

		$this->Javascript->cacheEvents();
		$result = $this->Javascript->event('myId', 'click', 'something();');
		$this->assertNull($result);

		ob_start();
		$this->Javascript->afterRender();
		$result = ob_get_clean();

		$this->assertTrue(empty($result));

		$result = $this->Javascript->getCache();
		$this->assertPattern('/^' . str_replace('/', '\\/', preg_quote('Event.observe($(\'myId\'), \'click\', function(event) { something(); }, false);')) . '$/s', $result);

		$this->Javascript->enabled = $old;
	}
}
?>