<?php
/**
 * CakeTestCase file
 *
 * PHP 5
 *
 * CakePHP(tm) Tests <http://book.cakephp.org/2.0/en/development/testing.html>
 * Copyright 2005-2012, Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice
 *
 * @copyright     Copyright 2005-2012, Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://book.cakephp.org/2.0/en/development/testing.html CakePHP(tm) Tests
 * @package       Cake.TestSuite
 * @since         CakePHP(tm) v 1.2.0.4667
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
App::uses('CakeFixtureManager', 'TestSuite/Fixture');
App::uses('CakeTestFixture', 'TestSuite/Fixture');

/**
 * CakeTestCase class
 *
 * @package       Cake.TestSuite
 */
abstract class CakeTestCase extends PHPUnit_Framework_TestCase {

/**
 * The class responsible for managing the creation, loading and removing of fixtures
 *
 * @var CakeFixtureManager
 */
	public $fixtureManager = null;

/**
 * By default, all fixtures attached to this class will be truncated and reloaded after each test.
 * Set this to false to handle manually
 *
 * @var array
 */
	public $autoFixtures = true;

/**
 * Set this to false to avoid tables to be dropped if they already exist
 *
 * @var boolean
 */
	public $dropTables = true;

/**
 * Configure values to restore at end of test.
 *
 * @var array
 */
	protected $_configure = array();

/**
 * Path settings to restore at the end of the test.
 *
 * @var array
 */
	protected $_pathRestore = array();

/**
 * Runs the test case and collects the results in a TestResult object.
 * If no TestResult object is passed a new one will be created.
 * This method is run for each test method in this class
 *
 * @param  PHPUnit_Framework_TestResult $result
 * @return PHPUnit_Framework_TestResult
 * @throws InvalidArgumentException
 */
	public function run(PHPUnit_Framework_TestResult $result = null) {
		if (!empty($this->fixtureManager)) {
			$this->fixtureManager->load($this);
		}
		$result = parent::run($result);
		if (!empty($this->fixtureManager)) {
			$this->fixtureManager->unload($this);
		}
		return $result;
	}

/**
 * Called when a test case method is about to start (to be overridden when needed.)
 *
 * @param string $method Test method about to get executed.
 * @return void
 */
	public function startTest($method) {
	}

/**
 * Called when a test case method has been executed (to be overridden when needed.)
 *
 * @param string $method Test method about that was executed.
 * @return void
 */
	public function endTest($method) {
	}

/**
 * Overrides SimpleTestCase::skipIf to provide a boolean return value
 *
 * @param boolean $shouldSkip
 * @param string $message
 * @return boolean
 */
	public function skipIf($shouldSkip, $message = '') {
		if ($shouldSkip) {
			$this->markTestSkipped($message);
		}
		return $shouldSkip;
	}

/**
 * Setup the test case, backup the static object values so they can be restored.
 * Specifically backs up the contents of Configure and paths in App if they have
 * not already been backed up.
 *
 * @return void
 */
	public function setUp() {
		parent::setUp();

		if (empty($this->_configure)) {
			$this->_configure = Configure::read();
		}
		if (empty($this->_pathRestore)) {
			$this->_pathRestore = App::paths();
		}
		if (class_exists('Router', false)) {
			Router::reload();
		}
	}

/**
 * teardown any static object changes and restore them.
 *
 * @return void
 */
	public function tearDown() {
		parent::tearDown();
		App::build($this->_pathRestore, App::RESET);
		if (class_exists('ClassRegistry', false)) {
			ClassRegistry::flush();
		}
		Configure::write($this->_configure);
		if (isset($_GET['debug']) && $_GET['debug']) {
			ob_flush();
		}
	}

/**
 * See CakeTestSuiteDispatcher::date()
 *
 * @param string $format format to be used.
 * @return string
 */
	public static function date($format = 'Y-m-d H:i:s') {
		return CakeTestSuiteDispatcher::date($format);
	}

// @codingStandardsIgnoreStart PHPUnit overrides don't match CakePHP

/**
 * Announces the start of a test.
 *
 * @param string $method Test method just started.
 * @return void
 */
	protected function assertPreConditions() {
		parent::assertPreConditions();
		$this->startTest($this->getName());
	}

/**
 * Announces the end of a test.
 *
 * @param string $method Test method just finished.
 * @return void
 */
	protected function assertPostConditions() {
		parent::assertPostConditions();
		$this->endTest($this->getName());
	}

// @codingStandardsIgnoreEnd

/**
 * Chooses which fixtures to load for a given test
 *
 * @param string $fixture Each parameter is a model name that corresponds to a
 *                        fixture, i.e. 'Post', 'Author', etc.
 * @return void
 * @see CakeTestCase::$autoFixtures
 * @throws Exception when no fixture manager is available.
 */
	public function loadFixtures() {
		if (empty($this->fixtureManager)) {
			throw new Exception(__d('cake_dev', 'No fixture manager to load the test fixture'));
		}
		$args = func_get_args();
		foreach ($args as $class) {
			$this->fixtureManager->loadSingle($class);
		}
	}

/**
 * Assert text equality, ignoring differences in newlines.
 * Helpful for doing cross platform tests of blocks of text.
 *
 * @param string $expected The expected value.
 * @param string $result The actual value.
 * @param message The message to use for failure.
 * @return boolean
 */
	public function assertTextNotEquals($expected, $result, $message = '') {
		$expected = str_replace(array("\r\n", "\r"), "\n", $expected);
		$result = str_replace(array("\r\n", "\r"), "\n", $result);
		return $this->assertNotEquals($expected, $result, $message);
	}

/**
 * Assert text equality, ignoring differences in newlines.
 * Helpful for doing cross platform tests of blocks of text.
 *
 * @param string $expected The expected value.
 * @param string $result The actual value.
 * @param message The message to use for failure.
 * @return boolean
 */
	public function assertTextEquals($expected, $result, $message = '') {
		$expected = str_replace(array("\r\n", "\r"), "\n", $expected);
		$result = str_replace(array("\r\n", "\r"), "\n", $result);
		return $this->assertEquals($expected, $result, $message);
	}

/**
 * Asserts that a string starts with a given prefix, ignoring differences in newlines.
 * Helpful for doing cross platform tests of blocks of text.
 *
 * @param string $prefix
 * @param string $string
 * @param string $message
 * @return boolean
 */
	public function assertTextStartsWith($prefix, $string, $message = '') {
		$prefix = str_replace(array("\r\n", "\r"), "\n", $prefix);
		$string = str_replace(array("\r\n", "\r"), "\n", $string);
		return $this->assertStringStartsWith($prefix, $string, $message);
	}

/**
 * Asserts that a string starts not with a given prefix, ignoring differences in newlines.
 * Helpful for doing cross platform tests of blocks of text.
 *
 * @param string $prefix
 * @param string $string
 * @param string $message
 * @return boolean
 */
	public function assertTextStartsNotWith($prefix, $string, $message = '') {
		$prefix = str_replace(array("\r\n", "\r"), "\n", $prefix);
		$string = str_replace(array("\r\n", "\r"), "\n", $string);
		return $this->assertStringStartsNotWith($prefix, $string, $message);
	}

/**
 * Asserts that a string ends with a given prefix, ignoring differences in newlines.
 * Helpful for doing cross platform tests of blocks of text.
 *
 * @param string $suffix
 * @param string $string
 * @param string $message
 * @return boolean
 */
	public function assertTextEndsWith($suffix, $string, $message = '') {
		$suffix = str_replace(array("\r\n", "\r"), "\n", $suffix);
		$string = str_replace(array("\r\n", "\r"), "\n", $string);
		return $this->assertStringEndsWith($suffix, $string, $message);
	}

/**
 * Asserts that a string ends not with a given prefix, ignoring differences in newlines.
 * Helpful for doing cross platform tests of blocks of text.
 *
 * @param string $suffix
 * @param string $string
 * @param string $message
 * @return boolean
 */
	public function assertTextEndsNotWith($suffix, $string, $message = '') {
		$suffix = str_replace(array("\r\n", "\r"), "\n", $suffix);
		$string = str_replace(array("\r\n", "\r"), "\n", $string);
		return $this->assertStringEndsNotWith($suffix, $string, $message);
	}

/**
 * Assert that a string contains another string, ignoring differences in newlines.
 * Helpful for doing cross platform tests of blocks of text.
 *
 * @param string $needle
 * @param string $haystack
 * @param string $message
 * @param boolean $ignoreCase
 * @return boolean
 */
	public function assertTextContains($needle, $haystack, $message = '', $ignoreCase = false) {
		$needle = str_replace(array("\r\n", "\r"), "\n", $needle);
		$haystack = str_replace(array("\r\n", "\r"), "\n", $haystack);
		return $this->assertContains($needle, $haystack, $message, $ignoreCase);
	}

/**
 * Assert that a text doesn't contain another text, ignoring differences in newlines.
 * Helpful for doing cross platform tests of blocks of text.
 *
 * @param string $needle
 * @param string $haystack
 * @param string $message
 * @param boolean $ignoreCase
 * @return boolean
 */
	public function assertTextNotContains($needle, $haystack, $message = '', $ignoreCase = false) {
		$needle = str_replace(array("\r\n", "\r"), "\n", $needle);
		$haystack = str_replace(array("\r\n", "\r"), "\n", $haystack);
		return $this->assertNotContains($needle, $haystack, $message, $ignoreCase);
	}

/**
 * Takes an array $expected and generates a regex from it to match the provided $string.
 * Samples for $expected:
 *
 * Checks for an input tag with a name attribute (contains any non-empty value) and an id
 * attribute that contains 'my-input':
 * 	array('input' => array('name', 'id' => 'my-input'))
 *
 * Checks for two p elements with some text in them:
 * 	array(
 * 		array('p' => true),
 * 		'textA',
 * 		'/p',
 * 		array('p' => true),
 * 		'textB',
 * 		'/p'
 *	)
 *
 * You can also specify a pattern expression as part of the attribute values, or the tag
 * being defined, if you prepend the value with preg: and enclose it with slashes, like so:
 *	array(
 *  	array('input' => array('name', 'id' => 'preg:/FieldName\d+/')),
 *  	'preg:/My\s+field/'
 *	)
 *
 * Important: This function is very forgiving about whitespace and also accepts any
 * permutation of attribute order. It will also allow whitespace between specified tags.
 *
 * @param string $string An HTML/XHTML/XML string
 * @param array $expected An array, see above
 * @param string $message SimpleTest failure output string
 * @return boolean
 */
	public function assertTags($string, $expected, $fullDebug = false) {
		$regex = array();
		$normalized = array();
		foreach ((array)$expected as $key => $val) {
			if (!is_numeric($key)) {
				$normalized[] = array($key => $val);
			} else {
				$normalized[] = $val;
			}
		}
		$i = 0;
		foreach ($normalized as $tags) {
			if (!is_array($tags)) {
				$tags = (string)$tags;
			}
			$i++;
			if (is_string($tags) && $tags{0} == '<') {
				$tags = array(substr($tags, 1) => array());
			} elseif (is_string($tags)) {
				$tagsTrimmed = preg_replace('/\s+/m', '', $tags);

				if (preg_match('/^\*?\//', $tags, $match) && $tagsTrimmed !== '//') {
					$prefix = array(null, null);

					if ($match[0] == '*/') {
						$prefix = array('Anything, ', '.*?');
					}
					$regex[] = array(
						sprintf('%sClose %s tag', $prefix[0], substr($tags, strlen($match[0]))),
						sprintf('%s<[\s]*\/[\s]*%s[\s]*>[\n\r]*', $prefix[1], substr($tags,  strlen($match[0]))),
						$i,
					);
					continue;
				}
				if (!empty($tags) && preg_match('/^preg\:\/(.+)\/$/i', $tags, $matches)) {
					$tags = $matches[1];
					$type = 'Regex matches';
				} else {
					$tags = preg_quote($tags, '/');
					$type = 'Text equals';
				}
				$regex[] = array(
					sprintf('%s "%s"', $type, $tags),
					$tags,
					$i,
				);
				continue;
			}
			foreach ($tags as $tag => $attributes) {
				$regex[] = array(
					sprintf('Open %s tag', $tag),
					sprintf('[\s]*<%s', preg_quote($tag, '/')),
					$i,
				);
				if ($attributes === true) {
					$attributes = array();
				}
				$attrs = array();
				$explanations = array();
				$i = 1;
				foreach ($attributes as $attr => $val) {
					if (is_numeric($attr) && preg_match('/^preg\:\/(.+)\/$/i', $val, $matches)) {
						$attrs[] = $matches[1];
						$explanations[] = sprintf('Regex "%s" matches', $matches[1]);
						continue;
					} else {
						$quotes = '["\']';
						if (is_numeric($attr)) {
							$attr = $val;
							$val = '.+?';
							$explanations[] = sprintf('Attribute "%s" present', $attr);
						} elseif (!empty($val) && preg_match('/^preg\:\/(.+)\/$/i', $val, $matches)) {
							$quotes = '["\']?';
							$val = $matches[1];
							$explanations[] = sprintf('Attribute "%s" matches "%s"', $attr, $val);
						} else {
							$explanations[] = sprintf('Attribute "%s" == "%s"', $attr, $val);
							$val = preg_quote($val, '/');
						}
						$attrs[] = '[\s]+' . preg_quote($attr, '/') . '=' . $quotes . $val . $quotes;
					}
					$i++;
				}
				if ($attrs) {
					$permutations = $this->_arrayPermute($attrs);

					$permutationTokens = array();
					foreach ($permutations as $permutation) {
						$permutationTokens[] = implode('', $permutation);
					}
					$regex[] = array(
						sprintf('%s', implode(', ', $explanations)),
						$permutationTokens,
						$i,
					);
				}
				$regex[] = array(
					sprintf('End %s tag', $tag),
					'[\s]*\/?[\s]*>[\n\r]*',
					$i,
				);
			}
		}
		foreach ($regex as $i => $assertation) {
			list($description, $expressions, $itemNum) = $assertation;
			$matches = false;
			foreach ((array)$expressions as $expression) {
				if (preg_match(sprintf('/^%s/s', $expression), $string, $match)) {
					$matches = true;
					$string = substr($string, strlen($match[0]));
					break;
				}
			}
			if (!$matches) {
				$this->assertTrue(false, sprintf('Item #%d / regex #%d failed: %s', $itemNum, $i, $description));
				if ($fullDebug) {
					debug($string, true);
					debug($regex, true);
				}
				return false;
			}
		}

		$this->assertTrue(true, '%s');
		return true;
	}

/**
 * Generates all permutation of an array $items and returns them in a new array.
 *
 * @param array $items An array of items
 * @return array
 */
	protected function _arrayPermute($items, $perms = array()) {
		static $permuted;
		if (empty($perms)) {
			$permuted = array();
		}

		if (empty($items)) {
			$permuted[] = $perms;
		} else {
			$numItems = count($items) - 1;
			for ($i = $numItems; $i >= 0; --$i) {
				$newItems = $items;
				$newPerms = $perms;
				list($tmp) = array_splice($newItems, $i, 1);
				array_unshift($newPerms, $tmp);
				$this->_arrayPermute($newItems, $newPerms);
			}
			return $permuted;
		}
	}

// @codingStandardsIgnoreStart

/**
 * Compatibility wrapper function for assertEquals
 *
 *
 * @param mixed $result
 * @param mixed $expected
 * @param string $message the text to display if the assertion is not correct
 * @return void
 */
	protected static function assertEqual($result, $expected, $message = '') {
		return self::assertEquals($expected, $result, $message);
	}

/**
 * Compatibility wrapper function for assertNotEquals
 *
 * @param mixed $result
 * @param mixed $expected
 * @param string $message the text to display if the assertion is not correct
 * @return void
 */
	protected static function assertNotEqual($result, $expected, $message = '') {
		return self::assertNotEquals($expected, $result, $message);
	}

/**
 * Compatibility wrapper function for assertRegexp
 *
 * @param mixed $pattern a regular expression
 * @param string $string the text to be matched
 * @param string $message the text to display if the assertion is not correct
 * @return void
 */
	protected static function assertPattern($pattern, $string, $message = '') {
		return self::assertRegExp($pattern, $string, $message);
	}

/**
 * Compatibility wrapper function for assertEquals
 *
 * @param mixed $actual
 * @param mixed $expected
 * @param string $message the text to display if the assertion is not correct
 * @return void
 */
	protected static function assertIdentical($actual, $expected, $message = '') {
		return self::assertSame($expected, $actual, $message);
	}

/**
 * Compatibility wrapper function for assertNotEquals
 *
 * @param mixed $actual
 * @param mixed $expected
 * @param string $message the text to display if the assertion is not correct
 * @return void
 */
	protected static function assertNotIdentical($actual, $expected, $message = '') {
		return self::assertNotSame($expected, $actual, $message);
	}

/**
 * Compatibility wrapper function for assertNotRegExp
 *
 * @param mixed $pattern a regular expression
 * @param string $string the text to be matched
 * @param string $message the text to display if the assertion is not correct
 * @return void
 */
	protected static function assertNoPattern($pattern, $string, $message = '') {
		return self::assertNotRegExp($pattern, $string, $message);
	}

	protected function assertNoErrors() {
	}

/**
 * Compatibility wrapper function for setExpectedException
 *
 * @param mixed $expected the name of the Exception or error
 * @param string $message the text to display if the assertion is not correct
 * @return void
 */
	protected function expectError($expected = false, $message = '') {
		if (!$expected) {
			$expected = 'Exception';
		}
		$this->setExpectedException($expected, $message);
	}

/**
 * Compatibility wrapper function for setExpectedException
 *
 * @param mixed $expected the name of the Exception
 * @param string $message the text to display if the assertion is not correct
 * @return void
 */
	protected function expectException($name = 'Exception', $message = '') {
		$this->setExpectedException($name, $message);
	}

/**
 * Compatibility wrapper function for assertSame
 *
 * @param mixed $first
 * @param mixed $second
 * @param string $message the text to display if the assertion is not correct
 * @return void
 */
	protected static function assertReference(&$first, &$second, $message = '') {
		return self::assertSame($first, $second, $message);
	}

/**
 * Compatibility wrapper for assertIsA
 *
 * @param string $object
 * @param string $type
 * @param string $message
 * @return void
 */
	protected static function assertIsA($object, $type, $message = '') {
		return self::assertInstanceOf($type, $object, $message);
	}

/**
 * Compatibility function to test if value is between an acceptable range
 *
 * @param mixed $result
 * @param mixed $expected
 * @param mixed $margin the rage of acceptation
 * @param string $message the text to display if the assertion is not correct
 * @return void
 */
	protected static function assertWithinMargin($result, $expected, $margin, $message = '') {
		$upper = $result + $margin;
		$lower = $result - $margin;
		return self::assertTrue((($expected <= $upper) && ($expected >= $lower)), $message);
	}

/**
 * Compatibility function for skipping.
 *
 * @param boolean $condition Condition to trigger skipping
 * @param string $message Message for skip
 * @return boolean
 */
	protected function skipUnless($condition, $message = '') {
		if (!$condition) {
			$this->markTestSkipped($message);
		}
		return $condition;
	}
	// @codingStandardsIgnoreStop

}
