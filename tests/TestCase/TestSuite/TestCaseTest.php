<?php
/**
 * CakeTestCaseTest file
 *
 * Test Case for CakeTestCase class
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
 * @since         1.2.0
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Test\TestCase\TestSuite;

use Cake\Controller\Controller;
use Cake\Core\App;
use Cake\Core\Configure;
use Cake\Core\Plugin;
use Cake\ORM\TableRegistry;
use Cake\TestSuite\TestCase;
use Cake\Test\Fixture\AssertTagsTestCase;
use Cake\Test\Fixture\FixturizedTestCase;

/**
 * TestCaseTest
 *
 */
class TestCaseTest extends TestCase {

/**
 * setUp
 *
 * @return void
 */
	public function setUp() {
		parent::setUp();
		$this->Reporter = $this->getMock('Cake\TestSuite\Reporter\HtmlReporter');
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
 * testAssertTags
 *
 * @return void
 */
	public function testAssertTagsBasic() {
		$test = new AssertTagsTestCase('testAssertTagsQuotes');
		$result = $test->run();
		$this->assertEquals(0, $result->errorCount());
		$this->assertTrue($result->wasSuccessful());
		$this->assertEquals(0, $result->failureCount());
	}

/**
 * test assertTags works with single and double quotes
 *
 * @return void
 */
	public function testAssertTagsQuoting() {
		$input = '<a href="/test.html" class="active">My link</a>';
		$pattern = array(
			'a' => array('href' => '/test.html', 'class' => 'active'),
			'My link',
			'/a'
		);
		$this->assertTags($input, $pattern);

		$input = "<a href='/test.html' class='active'>My link</a>";
		$pattern = array(
			'a' => array('href' => '/test.html', 'class' => 'active'),
			'My link',
			'/a'
		);
		$this->assertTags($input, $pattern);

		$input = "<a href='/test.html' class='active'>My link</a>";
		$pattern = array(
			'a' => array('href' => 'preg:/.*\.html/', 'class' => 'active'),
			'My link',
			'/a'
		);
		$this->assertTags($input, $pattern);

		$input = "<span><strong>Text</strong></span>";
		$pattern = array(
			'<span',
			'<strong',
			'Text',
			'/strong',
			'/span'
		);
		$this->assertTags($input, $pattern);

		$input = "<span class='active'><strong>Text</strong></span>";
		$pattern = array(
			'span' => array('class'),
			'<strong',
			'Text',
			'/strong',
			'/span'
		);
		$this->assertTags($input, $pattern);
	}

/**
 * Test that assertTags runs quickly.
 *
 * @return void
 */
	public function testAssertTagsRuntimeComplexity() {
		$pattern = array(
			'div' => array(
				'attr1' => 'val1',
				'attr2' => 'val2',
				'attr3' => 'val3',
				'attr4' => 'val4',
				'attr5' => 'val5',
				'attr6' => 'val6',
				'attr7' => 'val7',
				'attr8' => 'val8',
			),
			'My div',
			'/div'
		);
		$input = '<div attr8="val8" attr6="val6" attr4="val4" attr2="val2"' .
			' attr1="val1" attr3="val3" attr5="val5" attr7="val7" />' .
			'My div' .
			'</div>';
		$this->assertTags($input, $pattern);
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
 * testLoadFixturesOnDemand
 *
 * @return void
 */
	public function testLoadFixturesOnDemand() {
		$this->markTestIncomplete('Cannot work until fixtures are fixed');

		$test = new FixturizedTestCase('testFixtureLoadOnDemand');
		$test->autoFixtures = false;
		$manager = $this->getMock('Cake\TestSuite\Fixture\FixtureManager');
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
		$this->markTestIncomplete('Cannot work until fixtures are fixed');
		$test = new FixturizedTestCase('testFixtureLoadOnDemand');
		$test->autoFixtures = false;
		$manager = $this->getMock('Cake\TestSuite\Fixture\FixtureManager');
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
		$this->markTestIncomplete('Cannot work until fixtures are fixed');
		$test = new FixturizedTestCase('testThrowException');
		$test->autoFixtures = false;
		$manager = $this->getMock('Cake\TestSuite\Fixture\FixtureManager');
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
		$this->markTestIncomplete('Cannot work until fixtures are fixed');
		$test = new FixturizedTestCase('testSkipIfTrue');
		$result = $test->run();
		$this->assertEquals(1, $result->skippedCount());

		$test = new FixturizedTestCase('testSkipIfFalse');
		$result = $test->run();
		$this->assertEquals(0, $result->skippedCount());
	}

/**
 * Test that TestCase::setUp() backs up values.
 *
 * @return void
 */
	public function testSetupBackUpValues() {
		$this->assertArrayHasKey('debug', $this->_configure);
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
		Configure::write('App.namespace', 'TestApp');
		$Posts = $this->getMockForModel('Posts');
		$entity = new \Cake\ORM\Entity(array());

		$this->assertInstanceOf('TestApp\Model\Table\PostsTable', $Posts);
		$this->assertNull($Posts->save($entity));
		$this->assertNull($Posts->table());

		$Posts = $this->getMockForModel('Posts', array('save'));

		$this->assertNull($Posts->save($entity));

		$Posts->expects($this->at(0))
			->method('save')
			->will($this->returnValue(true));
		$Posts->expects($this->at(1))
			->method('save')
			->will($this->returnValue(false));
		$this->assertTrue($Posts->save($entity));
		$this->assertFalse($Posts->save($entity));
	}

/**
 * test getMockForModel() with plugin models
 *
 * @return void
 */
	public function testGetMockForModelWithPlugin() {
		Configure::write('App.namespace', 'TestApp');
		Plugin::load('TestPlugin');
		$TestPluginComment = $this->getMockForModel('TestPlugin.TestPluginComments');

		$result = TableRegistry::get('TestPlugin.TestPluginComments');
		$this->assertInstanceOf('\TestPlugin\Model\Table\TestPluginCommentsTable', $result);

		$TestPluginComment = $this->getMockForModel('TestPlugin.TestPluginComments', array('save'));

		$this->assertInstanceOf('\TestPlugin\Model\Table\TestPluginCommentsTable', $TestPluginComment);
		$TestPluginComment->expects($this->at(0))
			->method('save')
			->will($this->returnValue(true));
		$TestPluginComment->expects($this->at(1))
			->method('save')
			->will($this->returnValue(false));

		$entity = new \Cake\ORM\Entity(array());
		$this->assertTrue($TestPluginComment->save($entity));
		$this->assertFalse($TestPluginComment->save($entity));
	}

/**
 * testGetMockForModelTable
 *
 * @return void
 */
	public function testGetMockForModelTable() {
		$Mock = $this->getMockForModel(
			'Table',
			array('save'),
			array('alias' => 'Comments', 'className' => '\Cake\ORM\Table')
		);

		$result = TableRegistry::get('Comments');
		$this->assertInstanceOf('Cake\ORM\Table', $result);
		$this->assertEquals('Comments', $Mock->alias());

		$Mock->expects($this->at(0))
			->method('save')
			->will($this->returnValue(true));
		$Mock->expects($this->at(1))
			->method('save')
			->will($this->returnValue(false));

		$entity = new \Cake\ORM\Entity(array());
		$this->assertTrue($Mock->save($entity));
		$this->assertFalse($Mock->save($entity));
	}

}
