<?php
/**
 * CakeTestCaseTest file
 *
 * Test Case for CakeTestCase class
 *
 * PHP version 5
 *
 * CakePHP : Rapid Development Framework (http://cakephp.org)
 * Copyright 2006-2010, Cake Software Foundation, Inc.
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright 2006-2010, Cake Software Foundation, Inc.
 * @link          http://cakephp.org CakePHP Project
 * @package       cake.libs.
 * @since         CakePHP v 1.2.0.4487
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
App::import('Controller', 'Controller', false);
require_once TEST_CAKE_CORE_INCLUDE_PATH  . 'tests' . DS . 'lib' . DS . 'reporter' . DS . 'cake_html_reporter.php';

if (!class_exists('AppController')) {
	require_once LIBS . 'controller' . DS . 'app_controller.php';
} elseif (!defined('APP_CONTROLLER_EXISTS')) {
	define('APP_CONTROLLER_EXISTS', true);
}

/**
 * CakeTestCaseTest
 *
 * @package       cake.tests.cases.libs
 */
class CakeTestCaseTest extends CakeTestCase {

	public static function setUpBeforeClass() {
		require_once TEST_CAKE_CORE_INCLUDE_PATH . DS . 'tests' . DS . 'fixtures' . DS . 'assert_tags_test_case.php';
		require_once TEST_CAKE_CORE_INCLUDE_PATH . DS . 'tests' . DS . 'fixtures' . DS . 'fixturized_test_case.php';
	}

/**
 * setUp
 *
 * @access public
 * @return void
 */
	function setUp() {
		$this->_debug = Configure::read('debug');
		$this->Reporter = $this->getMock('CakeHtmlReporter');
	}

/**
 * tearDown
 *
 * @access public
 * @return void
 */
	function tearDown() {
		Configure::write('debug', $this->_debug);
		unset($this->Result);
		unset($this->Reporter);
	}

/**
 * testAssertGoodTags
 *
 * @access public
 * @return void
 */
	function testAssertTagsQuotes() {
		$test = new AssertTagsTestCase('testAssertTagsQuotes');
		$result = $test->run();
		$this->assertEquals(0, $result->errorCount());
		$this->assertTrue($result->wasSuccessful());
		$this->assertEquals(0, $result->failureCount());

		$input = '<a href="/test.html" class="active">My link</a>';
		$pattern = array(
			'a' => array('href' => '/test.html', 'class' => 'active'),
			'My link',
			'/a'
		);
		$this->assertTrue($test->assertTags($input, $pattern), 'Double quoted attributes %s');

		$input = "<a href='/test.html' class='active'>My link</a>";
		$pattern = array(
			'a' => array('href' => '/test.html', 'class' => 'active'),
			'My link',
			'/a'
		);
		$this->assertTrue($test->assertTags($input, $pattern), 'Single quoted attributes %s');

		$input = "<a href='/test.html' class='active'>My link</a>";
		$pattern = array(
			'a' => array('href' => 'preg:/.*\.html/', 'class' => 'active'),
			'My link',
			'/a'
		);
		$this->assertTrue($test->assertTags($input, $pattern), 'Single quoted attributes %s');
	}

/**
 * testNumericValuesInExpectationForAssertTags
 *
 * @access public
 * @return void
 */
	function testNumericValuesInExpectationForAssertTags() {
		$test = new AssertTagsTestCase('testNumericValuesInExpectationForAssertTags');
		$result = $test->run();
		$this->assertEquals(0, $result->errorCount());
		$this->assertTrue($result->wasSuccessful());
		$this->assertEquals(0, $result->failureCount());
	}

/**
 * testBadAssertTags
 *
 * @access public
 * @return void
 */
	function testBadAssertTags() {
		$test = new AssertTagsTestCase('testBadAssertTags');
		$result = $test->run();
		$this->assertEquals(0, $result->errorCount());
		$this->assertFalse($result->wasSuccessful());
		$this->assertEquals(1, $result->failureCount());

		$test = new AssertTagsTestCase('testBadAssertTags2');
		$result = $test->run();
		$this->assertEquals(0, $result->errorCount());
		$this->assertFalse($result->wasSuccessful());
		$this->assertEquals(1, $result->failureCount());
	}

/**
 * testLoadFixtures
 *
 * @access public
 * @return void
 */
	function testLoadFixtures() {
		$test = new FixturizedTestCase('testFixturePresent');
		$manager = $this->getMock('CakeFixtureManager');
		$manager->fixturize($test);
		$test->fixtureManager = $manager;
		$manager->expects($this->once())->method('load');
		$manager->expects($this->once())->method('unload');
		$result = $test->run();
		$this->assertEquals(0, $result->errorCount());
		$this->assertTrue($result->wasSuccessful());
		$this->assertEquals(0, $result->failureCount());
	}

/**
 * testLoadFixturesOnDemand
 *
 * @access public
 * @return void
 */
	function testLoadFixturesOnDemand() {
		$test = new FixturizedTestCase('testFixtureLoadOnDemand');
		$test->autoFixtures = false;
		$manager = $this->getMock('CakeFixtureManager');
		$manager->fixturize($test);
		$test->fixtureManager = $manager;
		$manager->expects($this->once())->method('loadSingle');
		$result = $test->run();
		$this->assertEquals(0, $result->errorCount());
	}

/**
 * testLoadFixturesOnDemand
 *
 * @access public
 * @return void
 */
	function testUnoadFixturesAfterFailure() {
		$test = new FixturizedTestCase('testFixtureLoadOnDemand');
		$test->autoFixtures = false;
		$manager = $this->getMock('CakeFixtureManager');
		$manager->fixturize($test);
		$test->fixtureManager = $manager;
		$manager->expects($this->once())->method('loadSingle');
		$result = $test->run();
		$this->assertEquals(0, $result->errorCount());
	}

/**
 * testThrowException
 *
 * @access public
 * @return void
 */
	function testThrowException() {
		$test = new FixturizedTestCase('testThrowException');
		$test->autoFixtures = false;
		$manager = $this->getMock('CakeFixtureManager');
		$manager->fixturize($test);
		$test->fixtureManager = $manager;
		$manager->expects($this->once())->method('unload');
		$result = $test->run();
		$this->assertEquals(1, $result->errorCount());

	}
/**
 * testSkipIf
 *
 * @return void
 */
	function testSkipIf() {
		$test = new FixturizedTestCase('testSkipIfTrue');
		$result = $test->run();
		$this->assertEquals(1, $result->skippedCount());

		$test = new FixturizedTestCase('testSkipIfFalse');
		$result = $test->run();
		$this->assertEquals(0, $result->skippedCount());
	}
}
