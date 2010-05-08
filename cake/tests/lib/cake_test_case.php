<?php
/**
 * CakeTestCase file
 *
 * PHP versions 4 and 5
 *
 * CakePHP(tm) Tests <https://trac.cakephp.org/wiki/Developement/TestSuite>
 * Copyright 2005-2010, Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 *  Licensed under The Open Group Test Suite License
 *  Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright 2005-2010, Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          https://trac.cakephp.org/wiki/Developement/TestSuite CakePHP(tm) Tests
 * @package       cake
 * @subpackage    cake.cake.tests.libs
 * @since         CakePHP(tm) v 1.2.0.4667
 * @license       http://www.opensource.org/licenses/opengroup.php The Open Group Test Suite License
 */

require_once CAKE_TESTS_LIB . 'cake_fixture_manager.php';
require_once CAKE_TESTS_LIB . 'cake_test_model.php';
require_once CAKE_TESTS_LIB . 'cake_test_fixture.php';

/**
 * CakeTestCase class
 *
 * @package       cake
 * @subpackage    cake.cake.tests.lib
 */
class CakeTestCase extends PHPUnit_Framework_TestCase {

/**
 * Methods used internally.
 *
 * @var array
 * @access public
 */
	public $methods = array('start', 'end', 'startcase', 'endcase', 'starttest', 'endtest');

/**
 * By default, all fixtures attached to this class will be truncated and reloaded after each test.
 * Set this to false to handle manually
 *
 * @var array
 * @access public
 */
	public $autoFixtures = true;

/**
 * Set this to false to avoid tables to be dropped if they already exist
 *
 * @var boolean
 * @access public
 */
	public $dropTables = true;

/**
 * Maps fixture class names to fixture identifiers as included in CakeTestCase::$fixtures
 *
 * @var array
 * @access protected
 */
	protected $_fixtureClassMap = array();

/**
 * truncated property
 *
 * @var boolean
 * @access private
 */
	private $__truncated = true;

/**
 * savedGetData property
 *
 * @var array
 * @access private
 */
	private $__savedGetData = array();

	public function __construct($name = null, array $data = array(), $dataName = '') {
		parent::__construct($name, $data, $dataName);
		if (!empty($this->fixtures)) {
			CakeFixtureManager::fixturize($this);
		}
	}
/**
 * Called when a test case (group of methods) is about to start (to be overriden when needed.)
 *
 * @param string $method Test method about to get executed.
 * @return void
 */
	public function startCase() {
	}

/**
 * Called when a test case (group of methods) has been executed (to be overriden when needed.)
 *
 * @param string $method Test method about that was executed.
 * @return void
 */
	public function endCase() {
	}

/**
 * Called when a test case method is about to start (to be overriden when needed.)
 *
 * @param string $method Test method about to get executed.
 * @return void
 */
	public function startTest($method) {
	}

/**
 * Called when a test case method has been executed (to be overriden when needed.)
 *
 * @param string $method Test method about that was executed.
 * @return void
 */
	public function endTest($method) {
	}

/**
 * Overrides SimpleTestCase::assert to enable calling of skipIf() from within tests
 *
 * @param Expectation $expectation
 * @param mixed $compare
 * @param string $message
 * @return boolean|null
 */
	public function assert(&$expectation, $compare, $message = '%s') {
		if ($this->_should_skip) {
			return;
		}
		return parent::assert($expectation, $compare, $message);
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
 * Announces the start of a test.
 *
 * @param string $method Test method just started.
 * @return void
 */
	protected function assertPreConditions() {
		parent::assertPreConditions();
		CakeFixtureManager::load($this);
		if (!in_array(strtolower($this->getName()), $this->methods)) {
			$this->startTest($this->getName());
		}
	}

/**
 * Runs as last test to drop tables.
 *
 * @return void
 */
	public function end() {
		if (isset($this->_fixtures) && isset($this->db)) {
			if ($this->dropTables) {
				foreach (array_reverse($this->_fixtures) as $fixture) {
					$fixture->drop($this->db);
				}
			}
			$this->db->sources(true);
			Configure::write('Cache.disable', false);
		}

		if (class_exists('ClassRegistry')) {
			ClassRegistry::flush();
		}
	}

/**
 * Announces the end of a test.
 *
 * @param string $method Test method just finished.
 * @return void
 */
	protected function assertPostConditions() {
		parent::assertPostConditions();
		CakeFixtureManager::unload($this);
		if (!in_array(strtolower($this->getName()), $this->methods)) {
			$this->endTest($this->getName());
		}
	}

/**
 * Gets a list of test names. Normally that will be all internal methods that start with the
 * name "test". This method should be overridden if you want a different rule.
 *
 * @return array List of test names.
 */
	public function getTests() {
		return array_merge(
			array('start', 'startCase'),
			array_diff(parent::getTests(), array('testAction')),
			array('endCase', 'end')
		);
	}

/**
 * Chooses which fixtures to load for a given test
 *
 * @param string $fixture Each parameter is a model name that corresponds to a
 *                        fixture, i.e. 'Post', 'Author', etc.
 * @return void
 * @access public
 * @see CakeTestCase::$autoFixtures
 */
	function loadFixtures() {
		$args = func_get_args();
		foreach ($args as $class) {
			CakeFixtureManager::loadSingle($class);
		}
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
 * permutation of attribute order. It will also allow whitespaces between specified tags.
 *
 * @param string $string An HTML/XHTML/XML string
 * @param array $expected An array, see above
 * @param string $message SimpleTest failure output string
 * @return boolean
 */
	public function assertTags($string, $expected, $fullDebug = false) {
		$regex = array();
		$normalized = array();
		foreach ((array) $expected as $key => $val) {
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
					$permutations = $this->__array_permute($attrs);

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
				$this->assert(new TrueExpectation(), false, sprintf('Item #%d / regex #%d failed: %s', $itemNum, $i, $description));
				if ($fullDebug) {
					debug($string, true);
					debug($regex, true);
				}
				return false;
			}
		}
		return $this->assertTrue(true, '%s');
	}

/**
 * Generates all permutation of an array $items and returns them in a new array.
 *
 * @param array $items An array of items
 * @return array
 * @access private
 */
	private function __array_permute($items, $perms = array()) {
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
				$this->__array_permute($newItems, $newPerms);
			}
			return $permuted;
		}
	}

	protected function assertEqual($a, $b) {
		return $this->assertEquals($a, $b);
	}

	protected function assertPattern($pattern, $string, $message = '') {
		return $this->assertRegExp($pattern, $string, $message);
	}

	protected function assertIdentical($expected, $actual, $message = '') {
		return $this->assertSame($expected, $actual, $message);
	}

	protected function assertNoPattern($pattern, $string, $message = '') {
		return $this->assertNotRegExp($pattern, $string, $message);
	}

	protected function assertNoErrors() {
	}

	protected function expectException($name = null) {
		$this->setExpectedException($name);
	}
}
?>