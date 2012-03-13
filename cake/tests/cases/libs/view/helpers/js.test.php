<?php
/**
 * JsHelper Test Case
 *
 * TestCase for the JsHelper
 *
 * PHP versions 4 and 5
 *
 * CakePHP(tm) Tests <http://book.cakephp.org/view/1196/Testing>
 * Copyright 2005-2012, Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 *  Licensed under The Open Group Test Suite License
 *  Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright 2005-2012, Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://book.cakephp.org/view/1196/Testing CakePHP(tm) Tests
 * @package       cake
 * @subpackage    cake.tests.cases.libs.view.helpers
 * @since         CakePHP(tm) v 1.3
 * @license       http://www.opensource.org/licenses/opengroup.php The Open Group Test Suite License
 */
App::import('Helper', array('Js', 'Html', 'Form'));
App::import('Core', array('View', 'ClassRegistry'));

Mock::generate('JsBaseEngineHelper', 'TestJsEngineHelper', array('methodOne'));
Mock::generate('View', 'JsHelperMockView');

class OptionEngineHelper extends JsBaseEngineHelper {
	var $_optionMap = array(
		'request' => array(
			'complete' => 'success',
			'request' => 'beforeSend',
			'type' => 'dataType'
		)
	);

/**
 * test method for testing option mapping
 *
 * @return array
 */
	function testMap($options = array()) {
		return $this->_mapOptions('request', $options);
	}
/**
 * test method for option parsing
 *
 * @return void
 */
	function testParseOptions($options, $safe = array()) {
		return $this->_parseOptions($options, $safe);
	}
}

/**
 * JsHelper TestCase.
 *
 * @package       cake
 * @subpackage    cake.tests.cases.libs.view.helpers
 */
class JsHelperTestCase extends CakeTestCase {
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
 * startTest method
 *
 * @access public
 * @return void
 */
	function startTest() {
		$this->_asset = Configure::read('Asset.timestamp');
		Configure::write('Asset.timestamp', false);

		$this->Js =& new JsHelper('JsBase');
		$this->Js->Html =& new HtmlHelper();
		$this->Js->Form =& new FormHelper();
		$this->Js->Form->Html =& new HtmlHelper();
		$this->Js->JsBaseEngine =& new JsBaseEngineHelper();

		$view =& new JsHelperMockView();
		ClassRegistry::addObject('view', $view);
	}

/**
 * endTest method
 *
 * @access public
 * @return void
 */
	function endTest() {
		Configure::write('Asset.timestamp', $this->_asset);
		ClassRegistry::removeObject('view');
		unset($this->Js);
	}

/**
 * Switches $this->Js to a mocked engine.
 *
 * @return void
 */
	function _useMock() {
		$this->Js =& new JsHelper(array('TestJs'));
		$this->Js->TestJsEngine =& new TestJsEngineHelper($this);
		$this->Js->Html =& new HtmlHelper();
		$this->Js->Form =& new FormHelper();
		$this->Js->Form->Html =& new HtmlHelper();
	}

/**
 * test object construction
 *
 * @return void
 */
	function testConstruction() {
		$js =& new JsHelper();
		$this->assertEqual($js->helpers, array('Html', 'Form', 'JqueryEngine'));

		$js =& new JsHelper(array('mootools'));
		$this->assertEqual($js->helpers, array('Html', 'Form', 'mootoolsEngine'));

		$js =& new JsHelper('prototype');
		$this->assertEqual($js->helpers, array('Html', 'Form', 'prototypeEngine'));

		$js =& new JsHelper('MyPlugin.Dojo');
		$this->assertEqual($js->helpers, array('Html', 'Form', 'MyPlugin.DojoEngine'));
	}

/**
 * test that methods dispatch internally and to the engine class
 *
 * @return void
 */
	function testMethodDispatching() {
		$this->_useMock();
		$this->Js->TestJsEngine->expectOnce('dispatchMethod', array(new PatternExpectation('/methodOne/i'), array()));

		$this->Js->methodOne();

		$this->Js->TestEngine =& new StdClass();
		$this->expectError();
		$this->Js->someMethodThatSurelyDoesntExist();
	}

/**
 * Test that method dispatching respects buffer parameters and bufferedMethods Lists.
 *
 * @return void
 */
	function testMethodDispatchWithBuffering() {
		$this->_useMock();

		$this->Js->TestJsEngine->bufferedMethods = array('event', 'sortables');
		$this->Js->TestJsEngine->setReturnValue('dispatchMethod', 'This is an event call', array('event', '*'));

		$this->Js->event('click', 'foo');
		$result = $this->Js->getBuffer();
		$this->assertEqual(count($result), 1);
		$this->assertEqual($result[0], 'This is an event call');

		$result = $this->Js->event('click', 'foo', array('buffer' => false));
		$buffer = $this->Js->getBuffer();
		$this->assertTrue(empty($buffer));
		$this->assertEqual($result, 'This is an event call');

		$result = $this->Js->event('click', 'foo', false);
		$buffer = $this->Js->getBuffer();
		$this->assertTrue(empty($buffer));
		$this->assertEqual($result, 'This is an event call');

		$this->Js->TestJsEngine->setReturnValue('dispatchMethod', 'I am not buffered.', array('effect', '*'));

		$result = $this->Js->effect('slideIn');
		$buffer = $this->Js->getBuffer();
		$this->assertTrue(empty($buffer));
		$this->assertEqual($result, 'I am not buffered.');

		$result = $this->Js->effect('slideIn', true);
		$buffer = $this->Js->getBuffer();
		$this->assertNull($result);
		$this->assertEqual(count($buffer), 1);
		$this->assertEqual($buffer[0], 'I am not buffered.');

		$result = $this->Js->effect('slideIn', array('speed' => 'slow'), true);
		$buffer = $this->Js->getBuffer();
		$this->assertNull($result);
		$this->assertEqual(count($buffer), 1);
		$this->assertEqual($buffer[0], 'I am not buffered.');

		$result = $this->Js->effect('slideIn', array('speed' => 'slow', 'buffer' => true));
		$buffer = $this->Js->getBuffer();
		$this->assertNull($result);
		$this->assertEqual(count($buffer), 1);
		$this->assertEqual($buffer[0], 'I am not buffered.');
	}

/**
 * test that writeScripts generates scripts inline.
 *
 * @return void
 */
	function testWriteScriptsNoFile() {
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
		$this->assertTags($result, $expected, true);

		$this->Js->TestJsEngine->expectAtLeastOnce('domReady');
		$result = $this->Js->writeBuffer(array('onDomReady' => true, 'cache' => false, 'clear' => false));

		ClassRegistry::removeObject('view');
		$view =& new JsHelperMockView();
		ClassRegistry::addObject('view', $view);

		$view->expectCallCount('addScript', 1);
		$view->expectAt(0, 'addScript', array(new PatternExpectation('/one\s\=\s1;\ntwo\s\=\s2;/')));
		$result = $this->Js->writeBuffer(array('onDomReady' => false, 'inline' => false, 'cache' => false));
	}

/**
 * test that writing the buffer with inline = false includes a script tag.
 *
 * @return void
 */
	function testWriteBufferNotInline() {
		$this->Js->set('foo', 1);

		$view =& new JsHelperMockView();
		ClassRegistry::removeObject('view');
		ClassRegistry::addObject('view', $view);
		$view->expectCallCount('addScript', 1);

		$pattern = new PatternExpectation('#<script type="text\/javascript">window.app \= \{"foo"\:1\}\;<\/script>#');
		$view->expectAt(0, 'addScript', array($pattern));

		$result = $this->Js->writeBuffer(array('onDomReady' => false, 'inline' => false, 'safe' => false));
	}

/**
 * test that writeBuffer() sets domReady = false when the request is done by XHR.
 * Including a domReady() when in XHR can cause issues as events aren't triggered by some libraries
 *
 * @return void
 */
	function testWriteBufferAndXhr() {
		$this->_useMock();
		$this->Js->params['isAjax'] = true;
		$this->Js->buffer('alert("test");');
		$this->Js->TestJsEngine->expectCallCount('dispatchMethod', 0);
		$result = $this->Js->writeBuffer();
	}

/**
 * test that writeScripts makes files, and puts the events into them.
 *
 * @return void
 */
	function testWriteScriptsInFile() {
		if ($this->skipIf(!is_writable(JS), 'webroot/js is not Writable, script caching test has been skipped')) {
			return;
		}
		$this->Js->JsBaseEngine = new TestJsEngineHelper();
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
		$this->assertPattern('/one\s=\s1;\ntwo\s=\s2;/', $contents);

		@unlink(WWW_ROOT . $filename[1]);
	}

/**
 * test link()
 *
 * @return void
 */
	function testLinkWithMock() {
		$this->_useMock();
		$options = array('update' => '#content');

		$this->Js->TestJsEngine->setReturnValue('dispatchMethod', 'ajax code', array('request', '*'));
		$this->Js->TestJsEngine->expectAt(0, 'dispatchMethod', array('get', new AnythingExpectation()));
		$this->Js->TestJsEngine->expectAt(1, 'dispatchMethod', array(
			'request', array('/posts/view/1', $options)
		));
		$this->Js->TestJsEngine->expectAt(2, 'dispatchMethod', array(
			'event', array('click', 'ajax code', $options + array('buffer' => null))
		));

		$result = $this->Js->link('test link', '/posts/view/1', $options);
		$expected = array(
			'a' => array('id' => 'preg:/link-\d+/', 'href' => '/posts/view/1'),
			'test link',
			'/a'
		);
		$this->assertTags($result, $expected);

		$options = array(
			'confirm' => 'Are you sure?',
			'update' => '#content',
			'class' => 'my-class',
			'id' => 'custom-id',
			'escape' => false
		);
		$this->Js->TestJsEngine->expectAt(0, 'confirm', array($options['confirm']));
		$this->Js->TestJsEngine->expectAt(1, 'request', array('/posts/view/1', '*'));
$code = <<<CODE
var _confirm = confirm("Are you sure?");
if (!_confirm) {
	return false;
}
CODE;
		$this->Js->TestJsEngine->expectAt(1, 'event', array('click', $code));
		$result = $this->Js->link('test link »', '/posts/view/1', $options);
		$expected = array(
			'a' => array('id' => $options['id'], 'class' => $options['class'], 'href' => '/posts/view/1'),
			'test link »',
			'/a'
		);
		$this->assertTags($result, $expected);

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
	function testLinkWithNoBuffering() {
		$this->_useMock();
		$this->Js->TestJsEngine->setReturnValue('dispatchMethod', 'ajax code', array(
			'request', array('/posts/view/1', array('update' => '#content'))
		));
		$this->Js->TestJsEngine->setReturnValue('dispatchMethod', '-event handler-', array('event', '*'));

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
	function testSubmitWithMock() {
		$this->_useMock();

		$options = array('update' => '#content', 'id' => 'test-submit', 'style' => 'margin: 0');
		$this->Js->TestJsEngine->setReturnValue('dispatchMethod', 'serialize-code', array('serializeform', '*'));
		$this->Js->TestJsEngine->setReturnValue('dispatchMethod', 'serialize-code', array('serializeForm', '*'));
		$this->Js->TestJsEngine->setReturnValue('dispatchMethod', 'ajax-code', array('request', '*'));

		$this->Js->TestJsEngine->expectAt(0, 'dispatchMethod', array('get', '*'));
		$this->Js->TestJsEngine->expectAt(1, 'dispatchMethod', array(new PatternExpectation('/serializeForm/i'), '*'));
		$this->Js->TestJsEngine->expectAt(2, 'dispatchMethod', array('request', '*'));

		$params = array(
			'update' => $options['update'], 'data' => 'serialize-code',
			'method' => 'post', 'dataExpression' => true, 'buffer' => null
		);
		$this->Js->TestJsEngine->expectAt(3, 'dispatchMethod', array(
			'event', array('click', "ajax-code", $params)
		));

		$result = $this->Js->submit('Save', $options);
		$expected = array(
			'div' => array('class' => 'submit'),
			'input' => array('type' => 'submit', 'id' => $options['id'], 'value' => 'Save', 'style' => 'margin: 0'),
			'/div'
		);
		$this->assertTags($result, $expected);


		$this->Js->TestJsEngine->expectAt(4, 'dispatchMethod', array('get', '*'));
		$this->Js->TestJsEngine->expectAt(5, 'dispatchMethod', array(new PatternExpectation('/serializeForm/i'), '*'));
		$requestParams = array(
			'/custom/url', array(
				'update' => '#content',
				'data' => 'serialize-code',
				'method' => 'post',
				'dataExpression' => true
			)
		);
		$this->Js->TestJsEngine->expectAt(6, 'dispatchMethod', array('request', $requestParams));

		$params = array(
			'update' => '#content', 'data' => 'serialize-code',
			'method' => 'post', 'dataExpression' => true, 'buffer' => null
		);
		$this->Js->TestJsEngine->expectAt(7, 'dispatchMethod', array(
			'event', array('click', "ajax-code", $params)
		));

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
	function testSubmitWithNoBuffer() {
		$this->_useMock();
		$options = array('update' => '#content', 'id' => 'test-submit', 'buffer' => false, 'safe' => false);
		$this->Js->TestJsEngine->setReturnValue('dispatchMethod', 'serialize-code', array('serializeform', '*'));
		$this->Js->TestJsEngine->setReturnValue('dispatchMethod', 'serialize-code', array('serializeForm', '*'));
		$this->Js->TestJsEngine->setReturnValue('dispatchMethod', 'ajax-code', array('request', '*'));
		$this->Js->TestJsEngine->setReturnValue('dispatchMethod', 'event-handler', array('event', '*'));

		$this->Js->TestJsEngine->expectAt(0, 'dispatchMethod', array('get', '*'));
		$this->Js->TestJsEngine->expectAt(1, 'dispatchMethod', array(new PatternExpectation('/serializeForm/i'), '*'));
		$this->Js->TestJsEngine->expectAt(2, 'dispatchMethod', array('request', array(
			'', array('update' => $options['update'], 'data' => 'serialize-code', 'method' => 'post', 'dataExpression' => true)
		)));

		$params = array(
			'update' => $options['update'], 'data' => 'serialize-code',
			'method' => 'post', 'dataExpression' => true, 'buffer' => false
		);
		$this->Js->TestJsEngine->expectAt(3, 'dispatchMethod', array(
			'event', array('click', "ajax-code", $params)
		));

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
	function testObjectPassThrough() {
		$result = $this->Js->object(array('one' => 'first', 'two' => 'second'));
		$expected = '{"one":"first","two":"second"}';
		$this->assertEqual($result, $expected);
	}

/**
 * Test that inherited Helper::value() is overwritten in JsHelper::value()
 * and calls JsBaseEngineHelper::value().
 *
 * @return void
 */
	function testValuePassThrough() {
		$result = $this->Js->value('string "quote"', true);
		$expected = '"string \"quote\""';
		$this->assertEqual($result, $expected);
	}

/**
 * test set()'ing variables to the Javascript buffer and controlling the output var name.
 *
 * @return void
 */
	function testSet() {
		$this->Js->set('loggedIn', true);
		$this->Js->set(array('height' => 'tall', 'color' => 'purple'));
		$result = $this->Js->getBuffer();
		$expected = 'window.app = {"loggedIn":true,"height":"tall","color":"purple"};';
		$this->assertEqual($result[0], $expected);

		$this->Js->set('loggedIn', true);
		$this->Js->set(array('height' => 'tall', 'color' => 'purple'));
		$this->Js->setVariable = 'WICKED';
		$result = $this->Js->getBuffer();
		$expected = 'window.WICKED = {"loggedIn":true,"height":"tall","color":"purple"};';
		$this->assertEqual($result[0], $expected);

		$this->Js->set('loggedIn', true);
		$this->Js->set(array('height' => 'tall', 'color' => 'purple'));
		$this->Js->setVariable = 'Application.variables';
		$result = $this->Js->getBuffer();
		$expected = 'Application.variables = {"loggedIn":true,"height":"tall","color":"purple"};';
		$this->assertEqual($result[0], $expected);
	}

/**
 * test that vars set with Js->set() go to the top of the buffered scripts list.
 *
 * @return void
 */
	function testSetVarsAtTopOfBufferedScripts() {
		$this->Js->set(array('height' => 'tall', 'color' => 'purple'));
		$this->Js->alert('hey you!', array('buffer' => true));
		$this->Js->confirm('Are you sure?', array('buffer' => true));
		$result = $this->Js->getBuffer(false);

		$expected = 'window.app = {"height":"tall","color":"purple"};';
		$this->assertEqual($result[0], $expected);
		$this->assertEqual($result[1], 'alert("hey you!");');
		$this->assertEqual($result[2], 'confirm("Are you sure?");');
	}
}

/**
 * JsBaseEngine Class Test case
 *
 * @package cake.tests.view.helpers
 */
class JsBaseEngineTestCase extends CakeTestCase {
/**
 * startTest method
 *
 * @access public
 * @return void
 */
	function startTest() {
		$this->JsEngine = new JsBaseEngineHelper();
	}
/**
 * endTest method
 *
 * @access public
 * @return void
 */
	function endTest() {
		ClassRegistry::removeObject('view');
		unset($this->JsEngine);
	}

/**
 * test escape string skills
 *
 * @return void
 */
	function testEscaping() {
		$result = $this->JsEngine->escape('');
		$expected = '';
		$this->assertEqual($result, $expected);

		$result = $this->JsEngine->escape('CakePHP' . "\n" . 'Rapid Development Framework');
		$expected = 'CakePHP\\nRapid Development Framework';
		$this->assertEqual($result, $expected);

		$result = $this->JsEngine->escape('CakePHP' . "\r\n" . 'Rapid Development Framework' . "\r" . 'For PHP');
		$expected = 'CakePHP\\r\\nRapid Development Framework\\rFor PHP';
		$this->assertEqual($result, $expected);

		$result = $this->JsEngine->escape('CakePHP: "Rapid Development Framework"');
		$expected = 'CakePHP: \\"Rapid Development Framework\\"';
		$this->assertEqual($result, $expected);

		$result = $this->JsEngine->escape("CakePHP: 'Rapid Development Framework'");
		$expected = "CakePHP: 'Rapid Development Framework'";
		$this->assertEqual($result, $expected);

		$result = $this->JsEngine->escape('my \\"string\\"');
		$expected = 'my \\\\\\"string\\\\\\"';
		$this->assertEqual($result, $expected);
	}

/**
 * test prompt() creation
 *
 * @return void
 */
	function testPrompt() {
		$result = $this->JsEngine->prompt('Hey, hey you', 'hi!');
		$expected = 'prompt("Hey, hey you", "hi!");';
		$this->assertEqual($result, $expected);

		$result = $this->JsEngine->prompt('"Hey"', '"hi"');
		$expected = 'prompt("\"Hey\"", "\"hi\"");';
		$this->assertEqual($result, $expected);
	}

/**
 * test alert generation
 *
 * @return void
 */
	function testAlert() {
		$result = $this->JsEngine->alert('Hey there');
		$expected = 'alert("Hey there");';
		$this->assertEqual($result, $expected);

		$result = $this->JsEngine->alert('"Hey"');
		$expected = 'alert("\"Hey\"");';
		$this->assertEqual($result, $expected);
	}

/**
 * test confirm generation
 *
 * @return void
 */
	function testConfirm() {
		$result = $this->JsEngine->confirm('Are you sure?');
		$expected = 'confirm("Are you sure?");';
		$this->assertEqual($result, $expected);

		$result = $this->JsEngine->confirm('"Are you sure?"');
		$expected = 'confirm("\"Are you sure?\"");';
		$this->assertEqual($result, $expected);
	}

/**
 * test Redirect
 *
 * @return void
 */
	function testRedirect() {
		$result = $this->JsEngine->redirect(array('controller' => 'posts', 'action' => 'view', 1));
		$expected = 'window.location = "/posts/view/1";';
		$this->assertEqual($result, $expected);
	}

/**
 * testObject encoding with non-native methods.
 *
 * @return void
 */
	function testObject() {
		$this->JsEngine->useNative = false;

		$object = array('title' => 'New thing', 'indexes' => array(5, 6, 7, 8));
		$result = $this->JsEngine->object($object);
		$expected = '{"title":"New thing","indexes":[5,6,7,8]}';
		$this->assertEqual($result, $expected);

		$result = $this->JsEngine->object(array('default' => 0));
		$expected = '{"default":0}';
		$this->assertEqual($result, $expected);

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
		$this->assertEqual($result, $expected);

		foreach (array('true' => true, 'false' => false, 'null' => null) as $expected => $data) {
			$result = $this->JsEngine->object($data);
			$this->assertEqual($result, $expected);
		}

		$object = array('title' => 'New thing', 'indexes' => array(5, 6, 7, 8), 'object' => array('inner' => array('value' => 1)));
		$result = $this->JsEngine->object($object, array('prefix' => 'PREFIX', 'postfix' => 'POSTFIX'));
		$this->assertPattern('/^PREFIX/', $result);
		$this->assertPattern('/POSTFIX$/', $result);
		$this->assertNoPattern('/.PREFIX./', $result);
		$this->assertNoPattern('/.POSTFIX./', $result);
	}

/**
 * test compatibility of JsBaseEngineHelper::object() vs. json_encode()
 *
 * @return void
 */
	function testObjectAgainstJsonEncode() {
		$skip = $this->skipIf(!function_exists('json_encode'), 'json_encode() not found, comparison tests skipped. %s');
		if ($skip) {
			return;
		}
		$this->JsEngine->useNative = false;
		$data = array();
		$data['mystring'] = "simple string";
		$this->assertEqual(json_encode($data), $this->JsEngine->object($data));

		$data['mystring'] = "strÃ¯ng with spÃ©cial chÃ¢rs";
		$this->assertEqual(json_encode($data), $this->JsEngine->object($data));

		$data['mystring'] = "a two lines\nstring";
		$this->assertEqual(json_encode($data), $this->JsEngine->object($data));

		$data['mystring'] = "a \t tabbed \t string";
		$this->assertEqual(json_encode($data), $this->JsEngine->object($data));

		$data['mystring'] = "a \"double-quoted\" string";
		$this->assertEqual(json_encode($data), $this->JsEngine->object($data));

		$data['mystring'] = 'a \\"double-quoted\\" string';
		$this->assertEqual(json_encode($data), $this->JsEngine->object($data));

		unset($data['mystring']);
		$data[3] = array(1, 2, 3);
		$this->assertEqual(json_encode($data), $this->JsEngine->object($data));

		unset($data[3]);
		$data = array('mystring' => null, 'bool' => false, 'array' => array(1, 44, 66));
		$this->assertEqual(json_encode($data), $this->JsEngine->object($data));
	}

/**
 * test that JSON made with JsBaseEngineHelper::object() against json_decode()
 *
 * @return void
 */
	function testObjectAgainstJsonDecode() {
		$skip = $this->skipIf(!function_exists('json_encode'), 'json_encode() not found, comparison tests skipped. %s');
		if ($skip) {
			return;
		}
		$this->JsEngine->useNative = false;

		$data = array("simple string");
		$result = $this->JsEngine->object($data);
		$this->assertEqual(json_decode($result), $data);

		$data = array('my "string"');
		$result = $this->JsEngine->object($data);
		$this->assertEqual(json_decode($result), $data);

		$data = array('my \\"string\\"');
		$result = $this->JsEngine->object($data);
		$this->assertEqual(json_decode($result), $data);
	}

/**
 * test Mapping of options.
 *
 * @return void
 */
	function testOptionMapping() {
		$JsEngine = new OptionEngineHelper();
		$result = $JsEngine->testMap();
		$this->assertEqual($result, array());

		$result = $JsEngine->testMap(array('foo' => 'bar', 'baz' => 'sho'));
		$this->assertEqual($result, array('foo' => 'bar', 'baz' => 'sho'));

		$result = $JsEngine->testMap(array('complete' => 'myFunc', 'type' => 'json', 'update' => '#element'));
		$this->assertEqual($result, array('success' => 'myFunc', 'dataType' => 'json', 'update' => '#element'));

		$result = $JsEngine->testMap(array('success' => 'myFunc', 'dataType' => 'json', 'update' => '#element'));
		$this->assertEqual($result, array('success' => 'myFunc', 'dataType' => 'json', 'update' => '#element'));
	}

/**
 * test that option parsing escapes strings and saves what is supposed to be saved.
 *
 * @return void
 */
	function testOptionParsing() {
		$JsEngine = new OptionEngineHelper();

		$result = $JsEngine->testParseOptions(array('url' => '/posts/view/1', 'key' => 1));
		$expected = 'key:1, url:"\\/posts\\/view\\/1"';
		$this->assertEqual($result, $expected);

		$result = $JsEngine->testParseOptions(array('url' => '/posts/view/1', 'success' => 'doSuccess'), array('success'));
		$expected = 'success:doSuccess, url:"\\/posts\\/view\\/1"';
		$this->assertEqual($result, $expected);
	}

}
