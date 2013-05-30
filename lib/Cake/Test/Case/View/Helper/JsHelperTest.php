<?php
/**
 * JsHelper Test Case
 *
 * TestCase for the JsHelper
 *
 * PHP 5
 *
 * CakePHP(tm) Tests <http://book.cakephp.org/2.0/en/development/testing.html>
 * Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://book.cakephp.org/2.0/en/development/testing.html CakePHP(tm) Tests
 * @package       Cake.Test.Case.View.Helper
 * @since         CakePHP(tm) v 1.3
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */

App::uses('HtmlHelper', 'View/Helper');
App::uses('JsHelper', 'View/Helper');
App::uses('JsBaseEngineHelper', 'View/Helper');
App::uses('FormHelper', 'View/Helper');
App::uses('View', 'View');
App::uses('ClassRegistry', 'Utility');

/**
 * Class JsEncodingObject
 *
 * @package       Cake.Test.Case.View.Helper
 */
class JsEncodingObject {

	protected $_title = 'Old thing';

	//@codingStandardsIgnoreStart
	private $__noshow = 'Never ever';
	//@codingStandardsIgnoreEnd

}

/**
 * Class OptionEngineHelper
 *
 * @package       Cake.Test.Case.View.Helper
 */
class OptionEngineHelper extends JsBaseEngineHelper {

	protected $_optionMap = array(
		'request' => array(
			'complete' => 'success',
			'request' => 'beforeSend',
			'type' => 'dataType'
		)
	);

/**
 * test method for testing option mapping
 *
 * @param array $options
 * @return array
 */
	public function testMap($options = array()) {
		return $this->_mapOptions('request', $options);
	}

/**
 * test method for option parsing
 *
 * @param $options
 * @param array $safe
 * @return void
 */
	public function testParseOptions($options, $safe = array()) {
		return $this->_parseOptions($options, $safe);
	}

	public function get($selector) {
	}

	public function event($type, $callback, $options = array()) {
	}

	public function domReady($functionBody) {
	}

	public function each($callback) {
	}

	public function effect($name, $options = array()) {
	}

	public function request($url, $options = array()) {
	}

	public function drag($options = array()) {
	}

	public function drop($options = array()) {
	}

	public function sortable($options = array()) {
	}

	public function slider($options = array()) {
	}

	public function serializeForm($options = array()) {
	}

}

/**
 * JsHelper TestCase.
 *
 * @package       Cake.Test.Case.View.Helper
 */
class JsHelperTest extends CakeTestCase {

/**
 * Regexp for CDATA start block
 *
 * @var string
 */
	public $cDataStart = 'preg:/^\/\/<!\[CDATA\[[\n\r]*/';

/**
 * Regexp for CDATA end block
 *
 * @var string
 */
	public $cDataEnd = 'preg:/[^\]]*\]\]\>[\s\r\n]*/';

/**
 * setUp method
 *
 * @return void
 */
	public function setUp() {
		parent::setUp();

		Configure::write('Asset.timestamp', false);

		$controller = null;
		$this->View = $this->getMock('View', array('append'), array(&$controller));
		$this->Js = new JsHelper($this->View, 'Option');
		$request = new CakeRequest(null, false);
		$this->Js->request = $request;
		$this->Js->Html = new HtmlHelper($this->View);
		$this->Js->Html->request = $request;
		$this->Js->Form = new FormHelper($this->View);

		$this->Js->Form->request = $request;
		$this->Js->Form->Html = $this->Js->Html;
		$this->Js->OptionEngine = new OptionEngineHelper($this->View);
	}

/**
 * tearDown method
 *
 * @return void
 */
	public function tearDown() {
		parent::tearDown();
		unset($this->Js);
	}

/**
 * Switches $this->Js to a mocked engine.
 *
 * @return void
 */
	protected function _useMock() {
		$request = new CakeRequest(null, false);

		if (!class_exists('TestJsEngineHelper', false)) {
			$this->getMock('JsBaseEngineHelper', array(), array($this->View), 'TestJsEngineHelper');
		}

		$this->Js = new JsHelper($this->View, array('TestJs'));
		$this->Js->TestJsEngine = new TestJsEngineHelper($this->View);
		$this->mockObjects[] = $this->Js->TestJsEngine;
		$this->Js->request = $request;
		$this->Js->Html = new HtmlHelper($this->View);
		$this->Js->Html->request = $request;
		$this->Js->Form = new FormHelper($this->View);
		$this->Js->Form->request = $request;
		$this->Js->Form->Html = new HtmlHelper($this->View);
	}

/**
 * test object construction
 *
 * @return void
 */
	public function testConstruction() {
		$js = new JsHelper($this->View);
		$this->assertEquals(array('Html', 'Form', 'JqueryEngine'), $js->helpers);

		$js = new JsHelper($this->View, array('mootools'));
		$this->assertEquals(array('Html', 'Form', 'mootoolsEngine'), $js->helpers);

		$js = new JsHelper($this->View, 'prototype');
		$this->assertEquals(array('Html', 'Form', 'prototypeEngine'), $js->helpers);

		$js = new JsHelper($this->View, 'MyPlugin.Dojo');
		$this->assertEquals(array('Html', 'Form', 'MyPlugin.DojoEngine'), $js->helpers);
	}

/**
 * test that methods dispatch internally and to the engine class
 *
 * @expectedException PHPUnit_Framework_Error_Warning
 * @return void
 */
	public function testMethodDispatching() {
		$this->_useMock();

		$this->Js->TestJsEngine
			->expects($this->once())
			->method('event')
			->with('click', 'callback');

		$this->Js->event('click', 'callback');

		$this->Js->TestJsEngine = new StdClass();
		$this->Js->someMethodThatSurelyDoesntExist();
	}

/**
 * Test that method dispatching for events respects buffer parameters and bufferedMethods Lists.
 *
 * @return void
 */
	public function testEventDispatchWithBuffering() {
		$this->_useMock();

		$this->Js->TestJsEngine->bufferedMethods = array('event', 'sortables');
		$this->Js->TestJsEngine->expects($this->exactly(3))
			->method('event')
			->will($this->returnValue('This is an event call'));

		$this->Js->event('click', 'foo');
		$result = $this->Js->getBuffer();
		$this->assertEquals(1, count($result));
		$this->assertEquals('This is an event call', $result[0]);

		$result = $this->Js->event('click', 'foo', array('buffer' => false));
		$buffer = $this->Js->getBuffer();
		$this->assertTrue(empty($buffer));
		$this->assertEquals('This is an event call', $result);

		$result = $this->Js->event('click', 'foo', false);
		$buffer = $this->Js->getBuffer();
		$this->assertTrue(empty($buffer));
		$this->assertEquals('This is an event call', $result);
	}

/**
 * Test that method dispatching for effects respects buffer parameters and bufferedMethods Lists.
 *
 * @return void
 */
	public function testEffectDispatchWithBuffering() {
		$this->_useMock();
		$this->Js->TestJsEngine->expects($this->exactly(4))
			->method('effect')
			->will($this->returnValue('I am not buffered.'));

		$result = $this->Js->effect('slideIn');
		$buffer = $this->Js->getBuffer();
		$this->assertTrue(empty($buffer));
		$this->assertEquals('I am not buffered.', $result);

		$result = $this->Js->effect('slideIn', true);
		$buffer = $this->Js->getBuffer();
		$this->assertNull($result);
		$this->assertEquals(1, count($buffer));
		$this->assertEquals('I am not buffered.', $buffer[0]);

		$result = $this->Js->effect('slideIn', array('speed' => 'slow'), true);
		$buffer = $this->Js->getBuffer();
		$this->assertNull($result);
		$this->assertEquals(1, count($buffer));
		$this->assertEquals('I am not buffered.', $buffer[0]);

		$result = $this->Js->effect('slideIn', array('speed' => 'slow', 'buffer' => true));
		$buffer = $this->Js->getBuffer();
		$this->assertNull($result);
		$this->assertEquals(1, count($buffer));
		$this->assertEquals('I am not buffered.', $buffer[0]);
	}

/**
 * test that writeScripts generates scripts inline.
 *
 * @return void
 */
	public function testWriteScriptsNoFile() {
		$this->_useMock();
		$this->Js->buffer('one = 1;');
		$this->Js->buffer('two = 2;');
		$result = $this->Js->writeBuffer(array('onDomReady' => false, 'cache' => false, 'clear' => false));
		$expected = array(
			'script' => array('type' => 'text/javascript'),
			$this->cDataStart,
			"one = 1;\ntwo = 2;",
			$this->cDataEnd,
			'/script',
		);
		$this->assertTags($result, $expected);

		$this->Js->TestJsEngine->expects($this->atLeastOnce())->method('domReady');
		$result = $this->Js->writeBuffer(array('onDomReady' => true, 'cache' => false, 'clear' => false));

		$this->View->expects($this->once())
			->method('append')
			->with('script', $this->matchesRegularExpression('/one\s\=\s1;\ntwo\s\=\s2;/'));
		$result = $this->Js->writeBuffer(array('onDomReady' => false, 'inline' => false, 'cache' => false));
	}

/**
 * test that writing the buffer with inline = false includes a script tag.
 *
 * @return void
 */
	public function testWriteBufferNotInline() {
		$this->Js->set('foo', 1);

		$this->View->expects($this->once())
			->method('append')
			->with('script', $this->matchesRegularExpression('#<script type="text\/javascript">window.app \= \{"foo"\:1\}\;<\/script>#'));

		$this->Js->writeBuffer(array('onDomReady' => false, 'inline' => false, 'safe' => false));
	}

/**
 * test that writeBuffer() sets domReady = false when the request is done by XHR.
 * Including a domReady() when in XHR can cause issues as events aren't triggered by some libraries
 *
 * @return void
 */
	public function testWriteBufferAndXhr() {
		$this->_useMock();
		$requestWith = null;
		if (isset($_SERVER['HTTP_X_REQUESTED_WITH'])) {
			$requestWith = $_SERVER['HTTP_X_REQUESTED_WITH'];
		}
		$_SERVER['HTTP_X_REQUESTED_WITH'] = 'XMLHttpRequest';

		$this->Js->buffer('alert("test");');
		$this->Js->TestJsEngine->expects($this->never())->method('domReady');
		$this->Js->writeBuffer();

		unset($_SERVER['HTTP_X_REQUESTED_WITH']);
		if ($requestWith !== null) {
			$_SERVER['HTTP_X_REQUESTED_WITH'] = $requestWith;
		}
	}

/**
 * test that writeScripts makes files, and puts the events into them.
 *
 * @return void
 */
	public function testWriteScriptsInFile() {
		$this->skipIf(!is_writable(JS), 'webroot/js is not Writable, script caching test has been skipped.');

		Configure::write('Cache.disable', false);
		$this->Js->request->webroot = '/';
		$this->Js->JsBaseEngine = new TestJsEngineHelper($this->View);
		$this->Js->buffer('one = 1;');
		$this->Js->buffer('two = 2;');
		$result = $this->Js->writeBuffer(array('onDomReady' => false, 'cache' => true));
		$expected = array(
			'script' => array('type' => 'text/javascript', 'src' => 'preg:/(.)*\.js/'),
		);
		$this->assertTags($result, $expected);
		preg_match('/src="(.*\.js)"/', $result, $filename);
		$this->assertTrue(file_exists(WWW_ROOT . $filename[1]));
		$contents = file_get_contents(WWW_ROOT . $filename[1]);
		$this->assertRegExp('/one\s=\s1;\ntwo\s=\s2;/', $contents);
		if (file_exists(WWW_ROOT . $filename[1])) {
			unlink(WWW_ROOT . $filename[1]);
		}

		Configure::write('Cache.disable', true);
		$this->Js->buffer('one = 1;');
		$this->Js->buffer('two = 2;');
		$result = $this->Js->writeBuffer(array('onDomReady' => false, 'cache' => true));
		$this->assertRegExp('/one\s=\s1;\ntwo\s=\s2;/', $result);
		$this->assertFalse(file_exists(WWW_ROOT . $filename[1]));
	}

/**
 * test link()
 *
 * @return void
 */
	public function testLinkWithMock() {
		$this->_useMock();

		$options = array('update' => '#content');

		$this->Js->TestJsEngine->expects($this->at(0))
			->method('get');

		$this->Js->TestJsEngine->expects($this->at(1))
			->method('request')
			->with('/posts/view/1', $options)
			->will($this->returnValue('--ajax code--'));

		$this->Js->TestJsEngine->expects($this->at(2))
			->method('event')
			->with('click', '--ajax code--', $options + array('buffer' => null));

		$result = $this->Js->link('test link', '/posts/view/1', $options);
		$expected = array(
			'a' => array('id' => 'preg:/link-\d+/', 'href' => '/posts/view/1'),
			'test link',
			'/a'
		);
		$this->assertTags($result, $expected);
	}

/**
 * test link with a mock and confirmation
 *
 * @return void
 */
	public function testLinkWithMockAndConfirm() {
		$this->_useMock();

		$options = array(
			'confirm' => 'Are you sure?',
			'update' => '#content',
			'class' => 'my-class',
			'id' => 'custom-id',
			'escape' => false
		);
		$this->Js->TestJsEngine->expects($this->once())
			->method('confirmReturn')
			->with($options['confirm'])
			->will($this->returnValue('--confirm script--'));

		$this->Js->TestJsEngine->expects($this->once())
			->method('request')
			->with('/posts/view/1');

		$this->Js->TestJsEngine->expects($this->once())
			->method('event')
			->with('click', '--confirm script--');

		$result = $this->Js->link('test link »', '/posts/view/1', $options);
		$expected = array(
			'a' => array('id' => $options['id'], 'class' => $options['class'], 'href' => '/posts/view/1'),
			'test link »',
			'/a'
		);
		$this->assertTags($result, $expected);
	}

/**
 * test link passing on htmlAttributes
 *
 * @return void
 */
	public function testLinkWithAribtraryAttributes() {
		$this->_useMock();

		$options = array('id' => 'something', 'htmlAttributes' => array('arbitrary' => 'value', 'batman' => 'robin'));
		$result = $this->Js->link('test link', '/posts/view/1', $options);
		$expected = array(
			'a' => array('id' => $options['id'], 'href' => '/posts/view/1', 'arbitrary' => 'value',
				'batman' => 'robin'),
			'test link',
			'/a'
		);
		$this->assertTags($result, $expected);
	}

/**
 * test that link() and no buffering returns an <a> and <script> tags.
 *
 * @return void
 */
	public function testLinkWithNoBuffering() {
		$this->_useMock();

		$this->Js->TestJsEngine->expects($this->at(1))
			->method('request')
			->with('/posts/view/1', array('update' => '#content'))
			->will($this->returnValue('ajax code'));

		$this->Js->TestJsEngine->expects($this->at(2))
			->method('event')
			->will($this->returnValue('-event handler-'));

		$options = array('update' => '#content', 'buffer' => false);
		$result = $this->Js->link('test link', '/posts/view/1', $options);
		$expected = array(
			'a' => array('id' => 'preg:/link-\d+/', 'href' => '/posts/view/1'),
			'test link',
			'/a',
			'script' => array('type' => 'text/javascript'),
			$this->cDataStart,
			'-event handler-',
			$this->cDataEnd,
			'/script'
		);
		$this->assertTags($result, $expected);
	}

/**
 * test link with buffering off and safe on.
 *
 * @return void
 */
	public function testLinkWithNoBufferingAndSafe() {
		$this->_useMock();

		$this->Js->TestJsEngine->expects($this->at(1))
			->method('request')
			->with('/posts/view/1', array('update' => '#content'))
			->will($this->returnValue('ajax code'));

		$this->Js->TestJsEngine->expects($this->at(2))
			->method('event')
			->will($this->returnValue('-event handler-'));

		$options = array('update' => '#content', 'buffer' => false, 'safe' => false);
		$result = $this->Js->link('test link', '/posts/view/1', $options);

		$expected = array(
			'a' => array('id' => 'preg:/link-\d+/', 'href' => '/posts/view/1'),
			'test link',
			'/a',
			'script' => array('type' => 'text/javascript'),
			'-event handler-',
			'/script'
		);
		$this->assertTags($result, $expected);
	}

/**
 * test submit() with a Mock to check Engine method calls
 *
 * @return void
 */
	public function testSubmitWithMock() {
		$this->_useMock();

		$options = array('update' => '#content', 'id' => 'test-submit', 'style' => 'margin: 0');

		$this->Js->TestJsEngine->expects($this->at(0))
			->method('get');

		$this->Js->TestJsEngine->expects($this->at(1))
			->method('serializeForm')
			->will($this->returnValue('serialize-code'));

		$this->Js->TestJsEngine->expects($this->at(2))
			->method('request')
			->will($this->returnValue('ajax-code'));

		$params = array(
			'update' => $options['update'], 'data' => 'serialize-code',
			'method' => 'post', 'dataExpression' => true, 'buffer' => null
		);

		$this->Js->TestJsEngine->expects($this->at(3))
			->method('event')
			->with('click', "ajax-code", $params);

		$result = $this->Js->submit('Save', $options);
		$expected = array(
			'div' => array('class' => 'submit'),
			'input' => array('type' => 'submit', 'id' => $options['id'], 'value' => 'Save', 'style' => 'margin: 0'),
			'/div'
		);
		$this->assertTags($result, $expected);
	}

/**
 * test submit() with a mock
 *
 * @return void
 */
	public function testSubmitWithMockRequestParams() {
		$this->_useMock();

		$this->Js->TestJsEngine->expects($this->at(0))
			->method('get');

		$this->Js->TestJsEngine->expects($this->at(1))
			->method('serializeForm')
			->will($this->returnValue('serialize-code'));

		$requestParams = array(
			'update' => '#content',
			'data' => 'serialize-code',
			'method' => 'post',
			'dataExpression' => true
		);

		$this->Js->TestJsEngine->expects($this->at(2))
			->method('request')
			->with('/custom/url', $requestParams)
			->will($this->returnValue('ajax-code'));

		$params = array(
			'update' => '#content', 'data' => 'serialize-code',
			'method' => 'post', 'dataExpression' => true, 'buffer' => null
		);

		$this->Js->TestJsEngine->expects($this->at(3))
			->method('event')
			->with('click', "ajax-code", $params);

		$options = array('update' => '#content', 'id' => 'test-submit', 'url' => '/custom/url');
		$result = $this->Js->submit('Save', $options);
		$expected = array(
			'div' => array('class' => 'submit'),
			'input' => array('type' => 'submit', 'id' => $options['id'], 'value' => 'Save'),
			'/div'
		);
		$this->assertTags($result, $expected);
	}

/**
 * test that no buffer works with submit() and that parameters are leaking into the script tag.
 *
 * @return void
 */
	public function testSubmitWithNoBuffer() {
		$this->_useMock();
		$options = array('update' => '#content', 'id' => 'test-submit', 'buffer' => false, 'safe' => false);

		$this->Js->TestJsEngine->expects($this->at(0))
			->method('get');

		$this->Js->TestJsEngine->expects($this->at(1))
			->method('serializeForm')
			->will($this->returnValue('serialize-code'));

		$this->Js->TestJsEngine->expects($this->at(2))
			->method('request')
			->will($this->returnValue('ajax-code'));

		$this->Js->TestJsEngine->expects($this->at(3))
			->method('event')
			->will($this->returnValue('event-handler'));

		$params = array(
			'update' => $options['update'], 'data' => 'serialize-code',
			'method' => 'post', 'dataExpression' => true, 'buffer' => false
		);

		$this->Js->TestJsEngine->expects($this->at(3))
			->method('event')
			->with('click', "ajax-code", $params);

		$result = $this->Js->submit('Save', $options);
		$expected = array(
			'div' => array('class' => 'submit'),
			'input' => array('type' => 'submit', 'id' => $options['id'], 'value' => 'Save'),
			'/div',
			'script' => array('type' => 'text/javascript'),
			'event-handler',
			'/script'
		);
		$this->assertTags($result, $expected);
	}

/**
 * Test that Object::Object() is not breaking json output in JsHelper
 *
 * @return void
 */
	public function testObjectPassThrough() {
		$result = $this->Js->object(array('one' => 'first', 'two' => 'second'));
		$expected = '{"one":"first","two":"second"}';
		$this->assertEquals($expected, $result);
	}

/**
 * Test that inherited Helper::value() is overwritten in JsHelper::value()
 * and calls JsBaseEngineHelper::value().
 *
 * @return void
 */
	public function testValuePassThrough() {
		$result = $this->Js->value('string "quote"', true);
		$expected = '"string \"quote\""';
		$this->assertEquals($expected, $result);
	}

/**
 * test set()'ing variables to the Javascript buffer and controlling the output var name.
 *
 * @return void
 */
	public function testSet() {
		$this->Js->set('loggedIn', true);
		$this->Js->set(array('height' => 'tall', 'color' => 'purple'));
		$result = $this->Js->getBuffer();
		$expected = 'window.app = {"loggedIn":true,"height":"tall","color":"purple"};';
		$this->assertEquals($expected, $result[0]);

		$this->Js->set('loggedIn', true);
		$this->Js->set(array('height' => 'tall', 'color' => 'purple'));
		$this->Js->setVariable = 'WICKED';
		$result = $this->Js->getBuffer();
		$expected = 'window.WICKED = {"loggedIn":true,"height":"tall","color":"purple"};';
		$this->assertEquals($expected, $result[0]);

		$this->Js->set('loggedIn', true);
		$this->Js->set(array('height' => 'tall', 'color' => 'purple'));
		$this->Js->setVariable = 'Application.variables';
		$result = $this->Js->getBuffer();
		$expected = 'Application.variables = {"loggedIn":true,"height":"tall","color":"purple"};';
		$this->assertEquals($expected, $result[0]);
	}

/**
 * test that vars set with Js->set() go to the top of the buffered scripts list.
 *
 * @return void
 */
	public function testSetVarsAtTopOfBufferedScripts() {
		$this->Js->set(array('height' => 'tall', 'color' => 'purple'));
		$this->Js->alert('hey you!', array('buffer' => true));
		$this->Js->confirm('Are you sure?', array('buffer' => true));
		$result = $this->Js->getBuffer(false);

		$expected = 'window.app = {"height":"tall","color":"purple"};';
		$this->assertEquals($expected, $result[0]);
		$this->assertEquals('alert("hey you!");', $result[1]);
		$this->assertEquals('confirm("Are you sure?");', $result[2]);
	}

}

/**
 * JsBaseEngine Class Test case
 *
 * @package       Cake.Test.Case.View.Helper
 */
class JsBaseEngineTest extends CakeTestCase {

/**
 * setUp method
 *
 * @return void
 */
	public function setUp() {
		parent::setUp();
		$controller = null;
		$this->View = $this->getMock('View', array('append'), array(&$controller));
		$this->JsEngine = new OptionEngineHelper($this->View);
	}

/**
 * tearDown method
 *
 * @return void
 */
	public function tearDown() {
		parent::tearDown();
		unset($this->JsEngine);
	}

/**
 * test escape string skills
 *
 * @return void
 */
	public function testEscaping() {
		$result = $this->JsEngine->escape('');
		$expected = '';
		$this->assertEquals($expected, $result);

		$result = $this->JsEngine->escape('CakePHP' . "\n" . 'Rapid Development Framework');
		$expected = 'CakePHP\\nRapid Development Framework';
		$this->assertEquals($expected, $result);

		$result = $this->JsEngine->escape('CakePHP' . "\r\n" . 'Rapid Development Framework' . "\r" . 'For PHP');
		$expected = 'CakePHP\\r\\nRapid Development Framework\\rFor PHP';
		$this->assertEquals($expected, $result);

		$result = $this->JsEngine->escape('CakePHP: "Rapid Development Framework"');
		$expected = 'CakePHP: \\"Rapid Development Framework\\"';
		$this->assertEquals($expected, $result);

		$result = $this->JsEngine->escape("CakePHP: 'Rapid Development Framework'");
		$expected = "CakePHP: 'Rapid Development Framework'";
		$this->assertEquals($expected, $result);

		$result = $this->JsEngine->escape('my \\"string\\"');
		$expected = 'my \\\\\\"string\\\\\\"';
		$this->assertEquals($expected, $result);
	}

/**
 * test prompt() creation
 *
 * @return void
 */
	public function testPrompt() {
		$result = $this->JsEngine->prompt('Hey, hey you', 'hi!');
		$expected = 'prompt("Hey, hey you", "hi!");';
		$this->assertEquals($expected, $result);

		$result = $this->JsEngine->prompt('"Hey"', '"hi"');
		$expected = 'prompt("\"Hey\"", "\"hi\"");';
		$this->assertEquals($expected, $result);
	}

/**
 * test alert generation
 *
 * @return void
 */
	public function testAlert() {
		$result = $this->JsEngine->alert('Hey there');
		$expected = 'alert("Hey there");';
		$this->assertEquals($expected, $result);

		$result = $this->JsEngine->alert('"Hey"');
		$expected = 'alert("\"Hey\"");';
		$this->assertEquals($expected, $result);
	}

/**
 * test confirm generation
 *
 * @return void
 */
	public function testConfirm() {
		$result = $this->JsEngine->confirm('Are you sure?');
		$expected = 'confirm("Are you sure?");';
		$this->assertEquals($expected, $result);

		$result = $this->JsEngine->confirm('"Are you sure?"');
		$expected = 'confirm("\"Are you sure?\"");';
		$this->assertEquals($expected, $result);
	}

/**
 * test Redirect
 *
 * @return void
 */
	public function testRedirect() {
		$result = $this->JsEngine->redirect(array('controller' => 'posts', 'action' => 'view', 1));
		$expected = 'window.location = "/posts/view/1";';
		$this->assertEquals($expected, $result);
	}

/**
 * testObject encoding with non-native methods.
 *
 * @return void
 */
	public function testObject() {
		$object = array('title' => 'New thing', 'indexes' => array(5, 6, 7, 8));
		$result = $this->JsEngine->object($object);
		$expected = '{"title":"New thing","indexes":[5,6,7,8]}';
		$this->assertEquals($expected, $result);

		$object = new JsEncodingObject();
		$object->title = 'New thing';
		$object->indexes = array(5,6,7,8);
		$result = $this->JsEngine->object($object);
		$this->assertEquals($expected, $result);

		$result = $this->JsEngine->object(array('default' => 0));
		$expected = '{"default":0}';
		$this->assertEquals($expected, $result);

		$result = $this->JsEngine->object(array(
			'2007' => array(
				'Spring' => array(
					'1' => array('id' => 1, 'name' => 'Josh'), '2' => array('id' => 2, 'name' => 'Becky')
				),
				'Fall' => array(
					'1' => array('id' => 1, 'name' => 'Josh'), '2' => array('id' => 2, 'name' => 'Becky')
				)
			),
			'2006' => array(
				'Spring' => array(
					'1' => array('id' => 1, 'name' => 'Josh'), '2' => array('id' => 2, 'name' => 'Becky')
				),
				'Fall' => array(
					'1' => array('id' => 1, 'name' => 'Josh'), '2' => array('id' => 2, 'name' => 'Becky')
				)
			)
		));
		$expected = '{"2007":{"Spring":{"1":{"id":1,"name":"Josh"},"2":{"id":2,"name":"Becky"}},"Fall":{"1":{"id":1,"name":"Josh"},"2":{"id":2,"name":"Becky"}}},"2006":{"Spring":{"1":{"id":1,"name":"Josh"},"2":{"id":2,"name":"Becky"}},"Fall":{"1":{"id":1,"name":"Josh"},"2":{"id":2,"name":"Becky"}}}}';
		$this->assertEquals($expected, $result);

		foreach (array('true' => true, 'false' => false, 'null' => null) as $expected => $data) {
			$result = $this->JsEngine->object($data);
			$this->assertEquals($expected, $result);
		}

		$object = array('title' => 'New thing', 'indexes' => array(5, 6, 7, 8), 'object' => array('inner' => array('value' => 1)));
		$result = $this->JsEngine->object($object, array('prefix' => 'PREFIX', 'postfix' => 'POSTFIX'));
		$this->assertRegExp('/^PREFIX/', $result);
		$this->assertRegExp('/POSTFIX$/', $result);
		$this->assertNotRegExp('/.PREFIX./', $result);
		$this->assertNotRegExp('/.POSTFIX./', $result);
	}

/**
 * test Mapping of options.
 *
 * @return void
 */
	public function testOptionMapping() {
		$JsEngine = new OptionEngineHelper($this->View);
		$result = $JsEngine->testMap();
		$this->assertSame(array(), $result);

		$result = $JsEngine->testMap(array('foo' => 'bar', 'baz' => 'sho'));
		$this->assertEquals(array('foo' => 'bar', 'baz' => 'sho'), $result);

		$result = $JsEngine->testMap(array('complete' => 'myFunc', 'type' => 'json', 'update' => '#element'));
		$this->assertEquals(array('success' => 'myFunc', 'dataType' => 'json', 'update' => '#element'), $result);

		$result = $JsEngine->testMap(array('success' => 'myFunc', 'dataType' => 'json', 'update' => '#element'));
		$this->assertEquals(array('success' => 'myFunc', 'dataType' => 'json', 'update' => '#element'), $result);
	}

/**
 * test that option parsing escapes strings and saves what is supposed to be saved.
 *
 * @return void
 */
	public function testOptionParsing() {
		$JsEngine = new OptionEngineHelper($this->View);

		$result = $JsEngine->testParseOptions(array('url' => '/posts/view/1', 'key' => 1));
		$expected = 'key:1, url:"\\/posts\\/view\\/1"';
		$this->assertEquals($expected, $result);

		$result = $JsEngine->testParseOptions(array('url' => '/posts/view/1', 'success' => 'doSuccess'), array('success'));
		$expected = 'success:doSuccess, url:"\\/posts\\/view\\/1"';
		$this->assertEquals($expected, $result);
	}

}
