<?php
/**
 * CakeTestCaseTest file
 *
 * Test Case for CakeTestCase class
 *
 * PHP versions 4 and 5
 *
 * CakePHP : Rapid Development Framework (http://cakephp.org)
 * Copyright 2006-2010, Cake Software Foundation, Inc.
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright 2006-2010, Cake Software Foundation, Inc.
 * @link          http://cakephp.org CakePHP Project
 * @package       cake
 * @subpackage    cake.cake.libs.
 * @since         CakePHP v 1.2.0.4487
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
App::import('Core', 'CakeTestCase');

if (!class_exists('AppController')) {
	require_once LIBS . 'controller' . DS . 'app_controller.php';
} elseif (!defined('APP_CONTROLLER_EXISTS')) {
	define('APP_CONTROLLER_EXISTS', true);
}

//Mock::generate('CakeHtmlReporter');
//Mock::generate('CakeTestCase', 'CakeDispatcherMockTestCase');

//SimpleTest::ignore('SubjectCakeTestCase');
//SimpleTest::ignore('CakeDispatcherMockTestCase');

/**
 * SubjectCakeTestCase
 *
 * @package       cake
 * @subpackage    cake.tests.cases.libs
 */
class SubjectCakeTestCase extends CakeTestCase {

/**
 * testDummy method
 *
 * @return void
 */
	public function testDummy() {
	}
}

/**
 * CakeTestCaseTest
 *
 * @package       cake
 * @subpackage    cake.tests.cases.libs
 */
class CakeTestCaseTest extends CakeTestCase {

/**
 * setUp
 *
 * @access public
 * @return void
 */
	function setUp() {
		$this->_debug = Configure::read('debug');
		$this->Case = new SubjectCakeTestCase();
		$this->Result = new PHPUnit_Framework_TestResult;
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
		unset($this->Case);
		unset($this->Result);
		unset($this->Reporter);
	}

/**
 * endTest
 *
 * @access public
 * @return void
 */
	function endTest() {
		App::build();
	}

/**
 * testAssertGoodTags
 *
 * @access public
 * @return void
 */
	function testAssertGoodTags() {
		$this->Reporter->expects($this->atLeastOnce())->method('paintPass');

		$input = '<p>Text</p>';
		$pattern = array(
			'<p',
			'Text',
			'/p',
		);
		$this->Case->assertTags($input, $pattern);

		$input = '<a href="/test.html" class="active">My link</a>';
		$pattern = array(
			'a' => array('href' => '/test.html', 'class' => 'active'),
			'My link',
			'/a'
		);
		$this->assertTrue($this->Case->assertTags($input, $pattern));

		$pattern = array(
			'a' => array('class' => 'active', 'href' => '/test.html'),
			'My link',
			'/a'
		);
		$this->assertTrue($this->Case->assertTags($input, $pattern), 'Attributes in wrong order. %s');

		$input = "<a    href=\"/test.html\"\t\n\tclass=\"active\"\tid=\"primary\">\t<span>My link</span></a>";
		$pattern = array(
			'a' => array('id' => 'primary', 'href' => '/test.html', 'class' => 'active'),
			'<span',
			'My link',
			'/span',
			'/a'
		);
		$this->assertTrue($this->Case->assertTags($input, $pattern), 'Whitespace consumption %s');

		$input = '<p class="info"><a href="/test.html" class="active"><strong onClick="alert(\'hey\');">My link</strong></a></p>';
		$pattern = array(
			'p' => array('class' => 'info'),
			'a' => array('class' => 'active', 'href' => '/test.html' ),
			'strong' => array('onClick' => 'alert(\'hey\');'),
			'My link',
			'/strong',
			'/a',
			'/p'
		);
		$this->assertTrue($this->Case->assertTags($input, $pattern));
	}

/**
 * test that assertTags knows how to handle correct quoting.
 *
 * @return void
 */
	function testAssertTagsQuotes() {
		$input = '<a href="/test.html" class="active">My link</a>';
		$pattern = array(
			'a' => array('href' => '/test.html', 'class' => 'active'),
			'My link',
			'/a'
		);
		$this->assertTrue($this->Case->assertTags($input, $pattern), 'Double quoted attributes %s');

		$input = "<a href='/test.html' class='active'>My link</a>";
		$pattern = array(
			'a' => array('href' => '/test.html', 'class' => 'active'),
			'My link',
			'/a'
		);
		$this->assertTrue($this->Case->assertTags($input, $pattern), 'Single quoted attributes %s');
		
		$input = "<a href='/test.html' class='active'>My link</a>";
		$pattern = array(
			'a' => array('href' => 'preg:/.*\.html/', 'class' => 'active'),
			'My link',
			'/a'
		);
		$this->assertTrue($this->Case->assertTags($input, $pattern), 'Single quoted attributes %s');
	}

/**
 * testNumericValuesInExpectationForAssertTags
 *
 * @access public
 * @return void
 */
	function testNumericValuesInExpectationForAssertTags() {
		$value = 220985;

		$input = '<p><strong>' . $value . '</strong></p>';
		$pattern = array(
			'<p',
				'<strong',
					$value,
				'/strong',
			'/p'
		);
		$this->assertTrue($this->Case->assertTags($input, $pattern));

		$input = '<p><strong>' . $value . '</strong></p><p><strong>' . $value . '</strong></p>';
		$pattern = array(
			'<p',
				'<strong',
					$value,
				'/strong',
			'/p',
			'<p',
				'<strong',
					$value,
				'/strong',
			'/p',
		);
		$this->assertTrue($this->Case->assertTags($input, $pattern));

		$input = '<p><strong>' . $value . '</strong></p><p id="' . $value . '"><strong>' . $value . '</strong></p>';
		$pattern = array(
			'<p',
				'<strong',
					$value,
				'/strong',
			'/p',
			'p' => array('id' => $value),
				'<strong',
					$value,
				'/strong',
			'/p',
		);
		$this->assertTrue($this->Case->assertTags($input, $pattern));
	}

 /**
 * testBadAssertTags
 *
 * @access public
 * @return void
 */
	function testBadAssertTags() {
//		$this->Reporter->expectAtLeastOnce('paintFail');
//		$this->Reporter->expectNever('paintPass');

		$input = '<a href="/test.html" class="active">My link</a>';
		$pattern = array(
			'a' => array('hRef' => '/test.html', 'clAss' => 'active'),
			'My link',
			'/a'
		);
		$this->assertFalse($this->Case->assertTags($input, $pattern));

		$input = '<a href="/test.html" class="active">My link</a>';
		$pattern = array(
			'<a' => array('href' => '/test.html', 'class' => 'active'),
			'My link',
			'/a'
		);
		$this->assertFalse($this->Case->assertTags($input, $pattern));
	}

/**
 * testBefore
 *
 * @access public
 * @return void
 */
	function testBefore() {
		$this->Case->before('testDummy');
		$this->assertFalse(isset($this->Case->db));

		$this->Case->fixtures = array('core.post');
		$this->Case->before('start');
		$this->assertTrue(isset($this->Case->db));
		$this->assertTrue(isset($this->Case->_fixtures['core.post']));
		$this->assertTrue(is_a($this->Case->_fixtures['core.post'], 'CakeTestFixture'));
		$this->assertEqual($this->Case->_fixtureClassMap['Post'], 'core.post');
	}

/**
 * testAfter
 *
 * @access public
 * @return void
 */
	function testAfter() {
		$this->Case->after('testDummy');
		$this->assertFalse($this->Case->getTruncated());

		$this->Case->fixtures = array('core.post');
		$this->Case->before('start');
		$this->Case->start();
		$this->Case->after('testDummy');
		$this->assertTrue($this->Case->getTruncated());
	}

/**
 * testLoadFixtures
 *
 * @access public
 * @return void
 */
	function testLoadFixtures() {
		$this->Case->fixtures = array('core.post');
		$this->Case->autoFixtures = false;
		$this->Case->before('start');
		$this->expectError();
		$this->Case->loadFixtures('Wrong!');
		$this->Case->end();
	}

/**
 * testGetTests Method
 *
 * @return void
 */
	public function testGetTests() {
		$result = $this->Case->getTests();
		$this->assertEqual(array_slice($result, 0, 2), array('start', 'startCase'));
		$this->assertEqual(array_slice($result, -2), array('endCase', 'end'));
	}

/**
 * testSkipIf
 *
 * @return void
 */
	function testSkipIf() {
		$this->assertTrue($this->Case->skipIf(true));
		$this->assertFalse($this->Case->skipIf(false));
	}
}
?>