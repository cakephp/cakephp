<?php
/**
 * CakeTestCaseTest file
 *
 * Test Case for CakeTestCase class
 *
 * PHP 5
 *
 * CakePHP : Rapid Development Framework (http://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP Project
 * @package       Cake.Test.Case.TestSuite
 * @since         CakePHP v 1.2.0.4487
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */

App::uses('Controller', 'Controller');
App::uses('CakeHtmlReporter', 'TestSuite/Reporter');

/**
 * CakeTestCaseTest
 *
 * @package       Cake.Test.Case.TestSuite
 */
class CakeTestCaseTest extends CakeTestCase {

/**
 * fixtures property
 *
 * @var array
 */
	public $fixtures = array('core.post', 'core.author', 'core.test_plugin_comment');

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

/**
 * test assertTextNotEquals()
 *
 * @return void
 */
	public function testAssertTextNotEquals() {
		$one = "\r\nOne\rTwooo";
		$two = "\nOne\nTwo";
		$this->assertTextNotEquals($one, $two);
	}

/**
 * test assertTextEquals()
 *
 * @return void
 */
	public function testAssertTextEquals() {
		$one = "\r\nOne\rTwo";
		$two = "\nOne\nTwo";
		$this->assertTextEquals($one, $two);
	}

/**
 * test assertTextStartsWith()
 *
 * @return void
 */
	public function testAssertTextStartsWith() {
		$stringDirty = "some\nstring\r\nwith\rdifferent\nline endings!";
		$stringClean = "some\nstring\nwith\ndifferent\nline endings!";

		$this->assertStringStartsWith("some\nstring", $stringDirty);
		$this->assertStringStartsNotWith("some\r\nstring\r\nwith", $stringDirty);
		$this->assertStringStartsNotWith("some\nstring\nwith", $stringDirty);

		$this->assertTextStartsWith("some\nstring\nwith", $stringDirty);
		$this->assertTextStartsWith("some\r\nstring\r\nwith", $stringDirty);
	}

/**
 * test assertTextStartsNotWith()
 *
 * @return void
 */
	public function testAssertTextStartsNotWith() {
		$stringDirty = "some\nstring\r\nwith\rdifferent\nline endings!";
		$stringClean = "some\nstring\nwith\ndifferent\nline endings!";

		$this->assertTextStartsNotWith("some\nstring\nwithout", $stringDirty);
	}

/**
 * test assertTextEndsWith()
 *
 * @return void
 */
	public function testAssertTextEndsWith() {
		$stringDirty = "some\nstring\r\nwith\rdifferent\nline endings!";
		$stringClean = "some\nstring\nwith\ndifferent\nline endings!";

		$this->assertTextEndsWith("string\nwith\r\ndifferent\rline endings!", $stringDirty);
		$this->assertTextEndsWith("string\r\nwith\ndifferent\nline endings!", $stringDirty);
	}

/**
 * test assertTextEndsNotWith()
 *
 * @return void
 */
	public function testAssertTextEndsNotWith() {
		$stringDirty = "some\nstring\r\nwith\rdifferent\nline endings!";
		$stringClean = "some\nstring\nwith\ndifferent\nline endings!";

		$this->assertStringEndsNotWith("different\nline endings", $stringDirty);
		$this->assertTextEndsNotWith("different\rline endings", $stringDirty);
	}

/**
 * test assertTextContains()
 *
 * @return void
 */
	public function testAssertTextContains() {
		$stringDirty = "some\nstring\r\nwith\rdifferent\nline endings!";
		$stringClean = "some\nstring\nwith\ndifferent\nline endings!";

		$this->assertContains("different", $stringDirty);
		$this->assertNotContains("different\rline", $stringDirty);

		$this->assertTextContains("different\rline", $stringDirty);
	}

/**
 * test assertTextNotContains()
 *
 * @return void
 */
	public function testAssertTextNotContains() {
		$stringDirty = "some\nstring\r\nwith\rdifferent\nline endings!";
		$stringClean = "some\nstring\nwith\ndifferent\nline endings!";

		$this->assertTextNotContains("different\rlines", $stringDirty);
	}

/**
 * test getMockForModel()
 *
 * @return void
 */
	public function testGetMockForModel() {
		App::build(array(
				'Model' => array(
					CAKE . 'Test' . DS . 'test_app' . DS . 'Model' . DS
				)
		), App::RESET);
		$Post = $this->getMockForModel('Post');

		$this->assertInstanceOf('Post', $Post);
		$this->assertNull($Post->save(array()));
		$this->assertNull($Post->find('all'));
		$this->assertEquals('posts', $Post->useTable);

		$Post = $this->getMockForModel('Post', array('save'));

		$this->assertNull($Post->save(array()));
		$this->assertInternalType('array', $Post->find('all'));
	}

/**
 * test getMockForModel() with plugin models
 *
 * @return void
 */
	public function testGetMockForModelWithPlugin() {
		App::build(array(
				'Plugin' => array(
					CAKE . 'Test' . DS . 'test_app' . DS . 'Plugin' . DS
				)
		), App::RESET);
		CakePlugin::load('TestPlugin');
		$this->getMockForModel('TestPlugin.TestPluginAppModel');
		$TestPluginComment = $this->getMockForModel('TestPlugin.TestPluginComment');

		$result = ClassRegistry::init('TestPlugin.TestPluginComment');
		$this->assertInstanceOf('TestPluginComment', $result);

		$TestPluginComment = $this->getMockForModel('TestPlugin.TestPluginComment', array('save'));

		$this->assertInstanceOf('TestPluginComment', $TestPluginComment);
		$TestPluginComment->expects($this->at(0))
			->method('save')
			->will($this->returnValue(true));
		$TestPluginComment->expects($this->at(1))
			->method('save')
			->will($this->returnValue(false));
		$this->assertTrue($TestPluginComment->save(array()));
		$this->assertFalse($TestPluginComment->save(array()));
	}

/**
 * testGetMockForModelModel
 *
 * @return void
 */
	public function testGetMockForModelModel() {
		$Mock = $this->getMockForModel('Model', array('save'), array('name' => 'Comment'));

		$result = ClassRegistry::init('Comment');
		$this->assertInstanceOf('Model', $result);

		$Mock->expects($this->at(0))
			->method('save')
			->will($this->returnValue(true));
		$Mock->expects($this->at(1))
			->method('save')
			->will($this->returnValue(false));

		$this->assertTrue($Mock->save(array()));
		$this->assertFalse($Mock->save(array()));
	}

/**
 * testGetMockForModelDoesNotExist
 *
 * @expectedException MissingModelException
 * @expectedExceptionMessage Model IDoNotExist could not be found
 * @return void
 */
	public function testGetMockForModelDoesNotExist() {
		$this->getMockForModel('IDoNotExist');
	}
}
