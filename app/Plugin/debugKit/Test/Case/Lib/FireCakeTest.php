<?php
/**
 * CakeFirePHP Test Case
 *
 * PHP 5
 *
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @since         DebugKit 0.1
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 **/

App::uses('FireCake', 'DebugKit.Lib');
require_once CakePlugin::path('DebugKit') . 'Test' . DS . 'Case' . DS . 'TestFireCake.php';

/**
 * Test Case For FireCake
 *
 * @since         DebugKit 0.1
 */
class FireCakeTestCase extends CakeTestCase {

/**
 * setup test
 *
 * Fill FireCake with TestFireCake instance.
 *
 * @return void
 */
	public function setUp() {
		$this->firecake = FireCake::getInstance('TestFireCake');
		TestFireCake::reset();
	}

/**
 * Reset the FireCake counters and headers.
 *
 * @return void
 */
	public function tearDown() {
		TestFireCake::reset();
	}

/**
 * Test getInstance cheat.
 *
 * If this fails the rest of the test is going to fail too.
 *
 * @return void
 */
	public function testGetInstanceOverride() {
		$instance = FireCake::getInstance();
		$instance2 = FireCake::getInstance();
		$this->assertReference($instance, $instance2);
		$this->assertIsA($instance, 'FireCake');
		$this->assertIsA($instance, 'TestFireCake', 'Stored instance is not a copy of TestFireCake, test case is broken.');
	}

/**
 * Test setOptions
 *
 * @return void
 */
	public function testSetOptions() {
		FireCake::setOptions(array('includeLineNumbers' => false));
		$this->assertEquals($this->firecake->options['includeLineNumbers'], false);
	}

/**
 * Test Log()
 *
 * @return void
 */
	public function testLog() {
		FireCake::setOptions(array('includeLineNumbers' => false));
		FireCake::log('Testing');
		$this->assertTrue(isset($this->firecake->sentHeaders['X-Wf-Protocol-1']));
		$this->assertTrue(isset($this->firecake->sentHeaders['X-Wf-1-Plugin-1']));
		$this->assertTrue(isset($this->firecake->sentHeaders['X-Wf-1-Structure-1']));
		$this->assertEquals($this->firecake->sentHeaders['X-Wf-1-Index'], 1);
		$this->assertEquals($this->firecake->sentHeaders['X-Wf-1-1-1-1'], '26|[{"Type":"LOG"},"Testing"]|');

		FireCake::log('Testing', 'log-info');
		$this->assertEquals($this->firecake->sentHeaders['X-Wf-1-1-1-2'], '45|[{"Type":"LOG","Label":"log-info"},"Testing"]|');
	}

/**
 * Test info()
 *
 * @return void
 */
	public function testInfo() {
		FireCake::setOptions(array('includeLineNumbers' => false));
		FireCake::info('I have information');
		$this->assertTrue(isset($this->firecake->sentHeaders['X-Wf-Protocol-1']));
		$this->assertTrue(isset($this->firecake->sentHeaders['X-Wf-1-Plugin-1']));
		$this->assertTrue(isset($this->firecake->sentHeaders['X-Wf-1-Structure-1']));
		$this->assertEquals($this->firecake->sentHeaders['X-Wf-1-Index'], 1);
		$this->assertEquals($this->firecake->sentHeaders['X-Wf-1-1-1-1'], '38|[{"Type":"INFO"},"I have information"]|');

		FireCake::info('I have information', 'info-label');
		$this->assertEquals($this->firecake->sentHeaders['X-Wf-1-1-1-2'], '59|[{"Type":"INFO","Label":"info-label"},"I have information"]|');
	}

/**
 * Test info()
 *
 * @return void
 */
	public function testWarn() {
		FireCake::setOptions(array('includeLineNumbers' => false));
		FireCake::warn('A Warning');
		$this->assertTrue(isset($this->firecake->sentHeaders['X-Wf-Protocol-1']));
		$this->assertTrue(isset($this->firecake->sentHeaders['X-Wf-1-Plugin-1']));
		$this->assertTrue(isset($this->firecake->sentHeaders['X-Wf-1-Structure-1']));
		$this->assertEquals($this->firecake->sentHeaders['X-Wf-1-Index'], 1);
		$this->assertEquals($this->firecake->sentHeaders['X-Wf-1-1-1-1'], '29|[{"Type":"WARN"},"A Warning"]|');

		FireCake::warn('A Warning', 'Bzzz');
		$this->assertEquals($this->firecake->sentHeaders['X-Wf-1-1-1-2'], '44|[{"Type":"WARN","Label":"Bzzz"},"A Warning"]|');
	}

/**
 * Test error()
 *
 * @return void
 */
	public function testError() {
		FireCake::setOptions(array('includeLineNumbers' => false));
		FireCake::error('An error');
		$this->assertTrue(isset($this->firecake->sentHeaders['X-Wf-Protocol-1']));
		$this->assertTrue(isset($this->firecake->sentHeaders['X-Wf-1-Plugin-1']));
		$this->assertTrue(isset($this->firecake->sentHeaders['X-Wf-1-Structure-1']));
		$this->assertEquals($this->firecake->sentHeaders['X-Wf-1-Index'], 1);
		$this->assertEquals($this->firecake->sentHeaders['X-Wf-1-1-1-1'], '29|[{"Type":"ERROR"},"An error"]|');

		FireCake::error('An error', 'wonky');
		$this->assertEquals($this->firecake->sentHeaders['X-Wf-1-1-1-2'], '45|[{"Type":"ERROR","Label":"wonky"},"An error"]|');
	}

/**
 * Test dump()
 *
 * @return void
 */
	public function testDump() {
		FireCake::dump('mydump', array('one' => 1, 'two' => 2));
		$this->assertEquals($this->firecake->sentHeaders['X-Wf-1-2-1-1'], '28|{"mydump":{"one":1,"two":2}}|');
		$this->assertTrue(isset($this->firecake->sentHeaders['X-Wf-1-Structure-2']));
	}

/**
 * Test table() generation
 *
 * @return void
 */
	public function testTable() {
		$table[] = array('Col 1 Heading','Col 2 Heading');
		$table[] = array('Row 1 Col 1','Row 1 Col 2');
		$table[] = array('Row 2 Col 1','Row 2 Col 2');
		$table[] = array('Row 3 Col 1','Row 3 Col 2');
		FireCake::table('myTrace', $table);
		$expected = '162|[{"Type":"TABLE","Label":"myTrace"},[["Col 1 Heading","Col 2 Heading"],["Row 1 Col 1","Row 1 Col 2"],["Row 2 Col 1","Row 2 Col 2"],["Row 3 Col 1","Row 3 Col 2"]]]|';
		$this->assertEquals($this->firecake->sentHeaders['X-Wf-1-1-1-1'], $expected);
	}

/**
 * TestStringEncoding
 *
 * @return void
 */
	public function testStringEncode() {
		$vars = array(1,2,3);
		$result = $this->firecake->stringEncode($vars);
		$this->assertEquals($result, array(1,2,3));

		$this->firecake->setOptions(array('maxArrayDepth' => 3));
		$deep = array(1 => array(2 => array(3)));
		$result = $this->firecake->stringEncode($deep);
		$this->assertEquals($result, array(1 => array(2 => '** Max Array Depth (3) **')));
	}

/**
 * Test object encoding
 *
 * @return void
 */
	public function testStringEncodeObjects() {
		$obj = FireCake::getInstance();
		$result = $this->firecake->stringEncode($obj);

		$this->assertTrue(is_array($result));
		$this->assertEquals($result['_defaultOptions']['useNativeJsonEncode'], true);
		$this->assertEquals($result['_encodedObjects'][0], '** Recursion (TestFireCake) **');
	}

/**
 * Test trace()
 *
 * @return void
 */
	public function testTrace() {
		FireCake::trace('myTrace');
		$this->assertTrue(isset($this->firecake->sentHeaders['X-Wf-Protocol-1']));
		$this->assertTrue(isset($this->firecake->sentHeaders['X-Wf-1-Plugin-1']));
		$this->assertTrue(isset($this->firecake->sentHeaders['X-Wf-1-Structure-1']));
		$dump = $this->firecake->sentHeaders['X-Wf-1-1-1-1'];
		$this->assertPattern('/"Message":"myTrace"/', $dump);
		$this->assertPattern('/"Trace":\[/', $dump);
	}

/**
 * Test enabling and disabling of FireCake output
 *
 * @return void
 */
	public function testEnableDisable() {
		FireCake::disable();
		FireCake::trace('myTrace');
		$this->assertTrue(empty($this->firecake->sentHeaders));

		FireCake::enable();
		FireCake::trace('myTrace');
		$this->assertFalse(empty($this->firecake->sentHeaders));
	}

/**
 * Test correct line continuation markers on multi line headers.
 *
 * @return void
 */
	public function testMultiLineOutput() {
		FireCake::trace('myTrace');
		$this->assertGreaterThan(1, $this->firecake->sentHeaders['X-Wf-1-Index']);
		$header = $this->firecake->sentHeaders['X-Wf-1-1-1-1'];
		$this->assertEquals(substr($header, -2), '|\\');

		$endIndex = $this->firecake->sentHeaders['X-Wf-1-Index'];
		$header = $this->firecake->sentHeaders['X-Wf-1-1-1-' . $endIndex];
		$this->assertEquals(substr($header, -1), '|');
	}

/**
 * Test inclusion of line numbers
 *
 * @return void
 */
	public function testIncludeLineNumbers() {
		FireCake::setOptions(array('includeLineNumbers' => true));
		FireCake::info('Testing');
		$result = $this->firecake->sentHeaders['X-Wf-1-1-1-1'];
		$this->assertPattern('/"File"\:".*FireCakeTest.php/', $result);
		$this->assertPattern('/"Line"\:\d+/', $result);
	}

/**
 * Test Group messages
 *
 * @return void
 */
	public function testGroup() {
		FireCake::setOptions(array('includeLineNumbers' => false));
		FireCake::group('test');
		FireCake::info('my info');
		FireCake::groupEnd();
		$this->assertEquals($this->firecake->sentHeaders['X-Wf-1-1-1-1'], '63|[{"Collapsed":"true","Type":"GROUP_START","Label":"test"},null]|');
		$this->assertEquals($this->firecake->sentHeaders['X-Wf-1-1-1-3'], '27|[{"Type":"GROUP_END"},null]|');
		$this->assertEquals($this->firecake->sentHeaders['X-Wf-1-Index'], 3);
	}

/**
 * Test fb() parameter parsing
 *
 * @return void
 */
	public function testFbParameterParsing() {
		FireCake::setOptions(array('includeLineNumbers' => false));
		FireCake::fb('Test');
		$this->assertEquals($this->firecake->sentHeaders['X-Wf-1-1-1-1'], '23|[{"Type":"LOG"},"Test"]|');

		FireCake::fb('Test', 'warn');
		$this->assertEquals($this->firecake->sentHeaders['X-Wf-1-1-1-2'], '24|[{"Type":"WARN"},"Test"]|');

		FireCake::fb('Test', 'Custom label', 'warn');
		$this->assertEquals($this->firecake->sentHeaders['X-Wf-1-1-1-3'], '47|[{"Type":"WARN","Label":"Custom label"},"Test"]|');

		$this->expectError('PHPUnit_Framework_Error');
		$this->assertFalse(FireCake::fb('Test', 'Custom label', 'warn', 'more parameters'));

		$this->assertEquals($this->firecake->sentHeaders['X-Wf-1-Index'], 3);
	}

/**
 * Test defaulting to log if incorrect message type is used
 *
 * @return void
 */
	public function testIncorrectMessageType() {
		FireCake::setOptions(array('includeLineNumbers' => false));
		FireCake::fb('Hello World', 'foobared');
		$this->assertEquals($this->firecake->sentHeaders['X-Wf-1-1-1-1'], '30|[{"Type":"LOG"},"Hello World"]|');
	}

/**
 * Test DetectClientExtension.
 *
 * @return void
 */
	public function testDetectClientExtension() {
		$back = env('HTTP_USER_AGENT');
		$_SERVER['HTTP_USER_AGENT'] = 'Mozilla/5.0 (Macintosh; U; Intel Mac OS X 10.5; en-US; rv:1.9.0.4) Gecko/2008102920 Firefox/3.0.4 FirePHP/0.2.1';
		$this->assertTrue(FireCake::detectClientExtension());

		$_SERVER['HTTP_USER_AGENT'] = 'Mozilla/5.0 (Macintosh; U; Intel Mac OS X 10.5; en-US; rv:1.9.0.4) Gecko/2008102920 Firefox/3.0.4 FirePHP/0.0.4';
		$this->assertFalse(FireCake::detectClientExtension());

		$_SERVER['HTTP_USER_AGENT'] = 'Mozilla/5.0 (Macintosh; U; Intel Mac OS X 10.5; en-US; rv:1.9.0.4) Gecko/2008102920 Firefox/3.0.4';
		$this->assertFalse(FireCake::detectClientExtension());
		$_SERVER['HTTP_USER_AGENT'] = $back;
	}

/**
 * Test of Non Native JSON encoding.
 *
 * @return void
 */
	public function testNonNativeEncoding() {
		FireCake::setOptions(array('useNativeJsonEncode' => false));
		$json = FireCake::jsonEncode(array('one' => 1, 'two' => 2));
		$this->assertEquals($json, '{"one":1,"two":2}');

		$json = FireCake::jsonEncode(array(1,2,3));
		$this->assertEquals($json, '[1,2,3]');

		$json = FireCake::jsonEncode(FireCake::getInstance());
		$this->assertPattern('/"options"\:\{"maxObjectDepth"\:\d*,/', $json);
	}

}
