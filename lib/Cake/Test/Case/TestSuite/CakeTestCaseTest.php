<?php
/**
 * CakeTestCaseTest file
 *
 * Test Case for CakeTestCase class
 *
 * PHP version 5
 *
 * CakePHP : Rapid Development Framework (http://cakephp.org)
 * Copyright 2005-2011, Cake Software Foundation, Inc.
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright 2005-2011, Cake Software Foundation, Inc.
 * @link          http://cakephp.org CakePHP Project
 * @package       Cake.Test.Case.TestSuite
 * @since         CakePHP v 1.2.0.4487
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */

App::uses('Controller', 'Controller');
App::uses('CakeHtmlReporter', 'TestSuite/Reporter');

if (!class_exists('AppController', false)) {
	require_once CAKE . 'Controller' . DS . 'AppController.php';
} elseif (!defined('APP_CONTROLLER_EXISTS')) {
	define('APP_CONTROLLER_EXISTS', true);
}

/**
 * CakeTestCaseTest
 *
 * @package       Cake.Test.Case.TestSuite
 */
class CakeTestCaseTest extends CakeTestCase {

	public static function setUpBeforeClass() {
		require_once CAKE . 'Test' . DS . 'Fixture' . DS . 'AssertTagsTestCase.php';
		require_once CAKE . 'Test' . DS . 'Fixture' . DS . 'FixturizedTestCase.php';
	}

/**
 * setUp
 *
 * @return void
 */
	public function setUp() {
		parent::setUp();
		$this->Reporter = $this->getMock('CakeHtmlReporter');
	}

/**
 * tearDown
 *
 * @return void
 */
	public function tearDown() {
		parent::tearDown();
		unset($this->Result);
		unset($this->Reporter);
	}

/**
 * testAssertGoodTags
 *
 * @return void
 */
	public function testAssertTagsQuotes() {
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

		$input = "<span><strong>Text</strong></span>";
		$pattern = array(
			'<span',
			'<strong',
			'Text',
			'/strong',
			'/span'
		);
		$this->assertTrue($test->assertTags($input, $pattern), 'Tags with no attributes');

		$input = "<span class='active'><strong>Text</strong></span>";
		$pattern = array(
			'span' => array('class'),
			'<strong',
			'Text',
			'/strong',
			'/span'
		);
		$this->assertTrue($test->assertTags($input, $pattern), 'Test attribute presence');
	}

/**
 * testNumericValuesInExpectationForAssertTags
 *
 * @return void
 */
	public function testNumericValuesInExpectationForAssertTags() {
		$test = new AssertTagsTestCase('testNumericValuesInExpectationForAssertTags');
		$result = $test->run();
		$this->assertEquals(0, $result->errorCount());
		$this->assertTrue($result->wasSuccessful());
		$this->assertEquals(0, $result->failureCount());
	}

/**
 * testBadAssertTags
 *
 * @return void
 */
	public function testBadAssertTags() {
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
 * @return void
 */
	public function testLoadFixtures() {
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
 * @return void
 */
	public function testLoadFixturesOnDemand() {
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
 * @return void
 */
	public function testUnoadFixturesAfterFailure() {
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
 * @return void
 */
	public function testThrowException() {
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
	public function testSkipIf() {
		$test = new FixturizedTestCase('testSkipIfTrue');
		$result = $test->run();
		$this->assertEquals(1, $result->skippedCount());

		$test = new FixturizedTestCase('testSkipIfFalse');
		$result = $test->run();
		$this->assertEquals(0, $result->skippedCount());
	}

/**
 * Test that CakeTestCase::setUp() backs up values.
 *
 * @return void
 */
	public function testSetupBackUpValues() {
		$this->assertArrayHasKey('debug', $this->_configure);
		$this->assertArrayHasKey('Plugin', $this->_pathRestore);
	}
}
