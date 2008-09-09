<?php
/* SVN FILE: $Id$ */
/**
 * Short description for file.
 *
 * Long description for file
 *
 * PHP versions 4 and 5
 *
 * CakePHP(tm) Tests <https://trac.cakephp.org/wiki/Developement/TestSuite>
 * Copyright 2005-2008, Cake Software Foundation, Inc.
 *								1785 E. Sahara Avenue, Suite 490-204
 *								Las Vegas, Nevada 89104
 *
 *  Licensed under The Open Group Test Suite License
 *  Redistributions of files must retain the above copyright notice.
 *
 * @filesource
 * @copyright		Copyright 2005-2008, Cake Software Foundation, Inc.
 * @link				https://trac.cakephp.org/wiki/Developement/TestSuite CakePHP(tm) Tests
 * @package			cake
 * @subpackage		cake.cake.tests.libs
 * @since			CakePHP(tm) v 1.2.0.4667
 * @version			$Revision$
 * @modifiedby		$LastChangedBy$
 * @lastmodified	$Date$
 * @license			http://www.opensource.org/licenses/opengroup.php The Open Group Test Suite License
 */
if (!class_exists('dispatcher')) {
	require CAKE . 'dispatcher.php';
}
require_once CAKE_TESTS_LIB . 'cake_test_model.php';
require_once CAKE_TESTS_LIB . 'cake_test_fixture.php';
App::import('Vendor', 'simpletest' . DS . 'unit_tester');
/**
 * Short description for class.
 *
 * @package    cake
 * @subpackage cake.cake.tests.lib
 */
class CakeTestDispatcher extends Dispatcher {
	var $controller;
	var $testCase;

	function testCase(&$testCase) {
		$this->testCase =& $testCase;
	}

	function _invoke (&$controller, $params, $missingAction = false) {
		$this->controller =& $controller;

		if (isset($this->testCase) && method_exists($this->testCase, 'startController')) {
			$this->testCase->startController($this->controller, $params);
		}

		$result = parent::_invoke($this->controller, $params, $missingAction);

		if (isset($this->testCase) && method_exists($this->testCase, 'endController')) {
			$this->testCase->endController($this->controller, $params);
		}

		return $result;
	}
}
/**
 * Short description for class.
 *
 * @package    cake
 * @subpackage cake.cake.tests.lib
 */
class CakeTestCase extends UnitTestCase {
/**
 * Methods used internally.
 *
 * @var array
 * @access private
 */
	var $methods = array('start', 'end', 'startcase', 'endcase', 'starttest', 'endtest');
	var $__truncated = true;
	var $__savedGetData = array();
/**
 * By default, all fixtures attached to this class will be truncated and reloaded after each test.
 * Set this to false to handle manually
 *
 * @var array
 * @access public
 */
	var $autoFixtures = true;
/**
 * Set this to false to avoid tables to be dropped if they already exist
 *
 * @var boolean
 * @access public
 */
	var $dropTables = true;
/**
 * Maps fixture class names to fixture identifiers as included in CakeTestCase::$fixtures
 *
 * @var array
 * @access protected
 */
	var $_fixtureClassMap = array();
/**
 * Called when a test case (group of methods) is about to start (to be overriden when needed.)
 *
 * @param string $method	Test method about to get executed.
 *
 * @access protected
 */
	function startCase() {
	}
/**
 * Called when a test case (group of methods) has been executed (to be overriden when needed.)
 *
 * @param string $method	Test method about that was executed.
 *
 * @access protected
 */
	function endCase() {
	}
/**
 * Called when a test case method is about to start (to be overriden when needed.)
 *
 * @param string $method	Test method about to get executed.
 *
 * @access protected
 */
	function startTest($method) {
	}
/**
 * Called when a test case method has been executed (to be overriden when needed.)
 *
 * @param string $method	Test method about that was executed.
 *
 * @access protected
 */
	function endTest($method) {
	}
/**
 * Overrides SimpleTestCase::assert to enable calling of skipIf() from within tests
 */
	function assert(&$expectation, $compare, $message = '%s') {
		if ($this->_should_skip) {
			return;
		}
		return parent::assert($expectation, $compare, $message);
	}
/**
 * Overrides SimpleTestCase::skipIf to provide a boolean return value
 */
	function skipIf($shouldSkip, $message = '%s') {
		parent::skipIf($shouldSkip, $message);
		return $shouldSkip;
	}
/**
 * Callback issued when a controller's action is about to be invoked through testAction().
 *
 * @param Controller $controller	Controller that's about to be invoked.
 * @param array $params	Additional parameters as sent by testAction().
 */
	function startController(&$controller, $params = array()) {
		if (isset($params['fixturize']) && ((is_array($params['fixturize']) && !empty($params['fixturize'])) || $params['fixturize'] === true)) {
			if (!isset($this->db)) {
				$this->_initDb();
			}

			if ($controller->uses === false) {
				$list = array($controller->modelClass);
			} else {
				$list = is_array($controller->uses) ? $controller->uses : array($controller->uses);
			}

			$models = array();
			ClassRegistry::config(array('ds' => $params['connection']));

			foreach ($list as $name) {
				if ((is_array($params['fixturize']) && in_array($name, $params['fixturize'])) || $params['fixturize'] === true) {
					if (class_exists($name) || App::import('Model', $name)) {
						$object =& ClassRegistry::init($name);
						//switch back to specified datasource.
						$object->setDataSource($params['connection']);
						$db =& ConnectionManager::getDataSource($object->useDbConfig);						
						$db->cacheSources = false;

						$models[$object->alias] = array(
							'table' => $object->table,
							'model' => $object->alias,
							'key' => strtolower($name),
						);
					}
				}
			}
			ClassRegistry::config(array('ds' => 'test_suite'));

			if (!empty($models) && isset($this->db)) {
				$this->_actionFixtures = array();

				foreach ($models as $model) {
					$fixture =& new CakeTestFixture($this->db);

					$fixture->name = $model['model'] . 'Test';
					$fixture->table = $model['table'];
					$fixture->import = array('model' => $model['model'], 'records' => true);
					$fixture->init();

					$fixture->create($this->db);
					$fixture->insert($this->db);
					$this->_actionFixtures[] =& $fixture;
				}

				foreach ($models as $model) {
					$object =& ClassRegistry::getObject($model['key']);
					if ($object !== false) {
						$object->setDataSource('test_suite');
						$object->cacheSources = false;
					}
				}
			}
		}
	}
/**
 * Callback issued when a controller's action has been invoked through testAction().
 *
 * @param Controller $controller	Controller that has been invoked.
 * * @param array $params	Additional parameters as sent by testAction().
 */
	function endController(&$controller, $params = array()) {
		if (isset($this->db) && isset($this->_actionFixtures) && !empty($this->_actionFixtures)) {
			foreach ($this->_actionFixtures as $fixture) {
				$fixture->drop($this->db);
			}
		}
	}
/**
 * Executes a Cake URL, and can get (depending on the $params['return'] value):
 *
 * 1. 'result': Whatever the action returns (and also specifies $this->params['requested'] for controller)
 * 2. 'view': The rendered view, without the layout
 * 3. 'contents': The rendered view, within the layout.
 * 4. 'vars': the view vars
 *
 * @param string $url	Cake URL to execute (e.g: /articles/view/455)
 * @param array $params	Parameters, or simply a string of what to return
 * @return mixed Whatever is returned depending of requested result
 * @access public
 */
	function testAction($url, $params = array()) {
		$default = array(
			'return' => 'result',
			'fixturize' => false,
			'data' => array(),
			'method' => 'post',
			'connection' => 'default'
		);

		if (is_string($params)) {
			$params = array('return' => $params);
		}

		$params = array_merge($default, $params);

		$toSave = array(
			'case' => null,
			'group' => null,
			'app' => null,
			'output' => null,
			'show' => null,
			'plugin' => null
		);
		$this->__savedGetData = (empty($this->__savedGetData))
				? array_intersect_key($_GET, $toSave)
				: $this->__savedGetData;

		$data = (!empty($params['data']))
					? $params['data']
					: array();

		if (strtolower($params['method']) == 'get') {
			$_GET = array_merge($this->__savedGetData, $data);
			$_POST = array();
		} else {
			$_POST = array('data' => $data);
			$_GET = $this->__savedGetData;
		}

		$return = $params['return'];
		$params = array_diff_key($params, array('data' => null, 'method' => null, 'return' => null));

		$dispatcher =& new CakeTestDispatcher();
		$dispatcher->testCase($this);

		if ($return != 'result') {
			if ($return != 'contents') {
				$params['layout'] = false;
			}

			ob_start();
			@$dispatcher->dispatch($url, $params);
			$result = ob_get_clean();

			if ($return == 'vars') {
				$view =& ClassRegistry::getObject('view');
				$viewVars = $view->getVars();

				$result = array();

				foreach ($viewVars as $var) {
					$result[$var] = $view->getVar($var);
				}

				if (!empty($view->pageTitle)) {
					$result = array_merge($result, array('title' => $view->pageTitle));
				}
			}
		} else {
			$params['return'] = 1;
			$params['bare'] = 1;
			$params['requested'] = 1;

			$result = @$dispatcher->dispatch($url, $params);
		}

		if (isset($this->_actionFixtures)) {
			unset($this->_actionFixtures);
		}
		ClassRegistry::flush();

		return $result;
	}
/**
 * Announces the start of a test.
 *
 * @param string $method Test method just started.
 *
 * @access public
 */
	function before($method) {
		parent::before($method);

		if (isset($this->fixtures) && (!is_array($this->fixtures) || empty($this->fixtures))) {
			unset($this->fixtures);
		}

		// Set up DB connection
		if (isset($this->fixtures) && strtolower($method) == 'start') {
			$this->_initDb();
			$this->_loadFixtures();
		}

		// Create records
		if (isset($this->_fixtures) && isset($this->db) && !in_array(strtolower($method), array('start', 'end')) && $this->__truncated && $this->autoFixtures == true) {
			foreach ($this->_fixtures as $fixture) {
				$inserts = $fixture->insert($this->db);
			}
		}

		if (!in_array(strtolower($method), $this->methods)) {
			$this->startTest($method);
		}
	}
/**
 * Runs as first test to create tables.
 *
 * @access public
 */
	function start() {
		if (isset($this->_fixtures) && isset($this->db)) {
			Configure::write('Cache.disable', true);
			$cacheSources = $this->db->cacheSources;
			$this->db->cacheSources = false;
			$sources = $this->db->listSources();
			$this->db->cacheSources = $cacheSources;

			if (!$this->dropTables) {
				return;
			}
			foreach ($this->_fixtures as $fixture) {
				if (in_array($fixture->table, $sources)) {
					$fixture->drop($this->db);
				} elseif (!in_array($fixture->table, $sources)) {
					$fixture->create($this->db);
				}
			}
		}
	}
/**
 * Runs as last test to drop tables.
 *
 * @access public
 */
	function end() {
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
 *
 * @access public
 */
	function after($method) {
		if (isset($this->_fixtures) && isset($this->db) && !in_array(strtolower($method), array('start', 'end'))) {
			foreach ($this->_fixtures as $fixture) {
				$fixture->truncate($this->db);
			}
			$this->__truncated = true;
		} else {
			$this->__truncated = false;
		}

		if (!in_array(strtolower($method), $this->methods)) {
			$this->endTest($method);
		}
		$this->_should_skip = false;

		parent::after($method);
	}
/**
 * Gets a list of test names. Normally that will be all internal methods that start with the
 * name "test". This method should be overridden if you want a different rule.
 *
 * @return array	List of test names.
 *
 * @access public
 */
	function getTests() {
		$methods = array_diff(parent::getTests(), array('testAction', 'testaction'));
		$methods = array_merge(array_merge(array('start', 'startCase'), $methods), array('endCase', 'end'));
		return $methods;
	}
/**
 * Chooses which fixtures to load for a given test
 *
 * @param string $fixture Each parameter is a model name that corresponds to a fixture, i.e. 'Post', 'Author', etc.
 * @access public
 * @see CakeTestCase::$autoFixtures
 */
	function loadFixtures() {
		$args = func_get_args();
		foreach ($args as $class) {
			if (isset($this->_fixtureClassMap[$class])) {
				$fixture = $this->_fixtures[$this->_fixtureClassMap[$class]];

				$fixture->truncate($this->db);
				$fixture->insert($this->db);
			} else {
				trigger_error("Non-existent fixture class {$class} referenced in test", E_USER_WARNING);
			}
		}
	}
/**
 * Takes an array $expected and generates a regex from it to match the provided $string. Samples for $expected:
 *
 * Checks for an input tag with a name attribute (contains any value) and an id attribute that contains 'my-input':
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
 * You can also specify a pattern expression as part of the attribute values, or the tag being defined,
 * if you prepend the value with preg: and enclose it with slashes, like so:
 *	array(
 *  	array('input' => array('name', 'id' => 'preg:/FieldName\d+/')),
 *  	'preg:/My\s+field/'
 *	)
 *
 * Important: This function is very forgiving about whitespace and also accepts any permutation of attribute order.
 * It will also allow whitespaces between specified tags.
 *
 * @param string $string An HTML/XHTML/XML string
 * @param array $expected An array, see above
 * @param string $message SimpleTest failure output string
 * @access public
 */
	function assertTags($string, $expected, $fullDebug = false) {
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
			$i++;
			if (is_string($tags) && $tags{0} == '<') {
				$tags = array(substr($tags, 1) => array());
			} elseif (is_string($tags)) {
				if (preg_match('/^\*?\//', $tags, $match)) {
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
				foreach ($attributes as $attr => $val) {
					if (is_numeric($attr) && preg_match('/^preg\:\/(.+)\/$/i', $val, $matches)) {
						$attrs[] = $matches[1];
						$explanations[] = sprintf('Regex "%s" matches', $matches[1]);
						continue;
					} else {
						$quotes = '"';
						if (is_numeric($attr)) {
							$attr = $val;
							$val = '.+?';
							$explanations[] = sprintf('Attribute "%s" present', $attr);
						} else if (!empty($val) && preg_match('/^preg\:\/(.+)\/$/i', $val, $matches)) {
							$quotes = '"?';
							$val = $matches[1];
							$explanations[] = sprintf('Attribute "%s" matches "%s"', $attr, $val);
						} else {
							$explanations[] = sprintf('Attribute "%s" == "%s"', $attr, $val);
							$val = preg_quote($val, '/');
						}
						$attrs[] = '[\s]+'.preg_quote($attr, '/').'='.$quotes.$val.$quotes;
					}
				}
				if ($attrs) {
					$permutations = $this->__array_permute($attrs);
					$permutationTokens = array();
					foreach ($permutations as $permutation) {
						$permutationTokens[] = join('', $permutation);
					}
					$regex[] = array(
						sprintf('%s', join(', ', $explanations)),
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
		return $this->assert(new TrueExpectation(), true, '%s');
	}
/**
 * Generates all permutation of an array $items and returns them in a new array.
 *
 * @param array $items An array of items
 * @return array
 * @access public
 */
	function __array_permute($items, $perms = array()) {
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
/**
 * Initialize DB connection.
 *
 * @access protected
 */
	function _initDb() {
		$testDbAvailable = in_array('test', array_keys(ConnectionManager::enumConnectionObjects()));

		$_prefix = null;

		if ($testDbAvailable) {
			// Try for test DB
			restore_error_handler();
			@$db =& ConnectionManager::getDataSource('test');
			set_error_handler('simpleTestErrorHandler');
			$testDbAvailable = $db->isConnected();
		}

		// Try for default DB
		if (!$testDbAvailable) {
			$db =& ConnectionManager::getDataSource('default');
			$_prefix = $db->config['prefix'];
			$db->config['prefix'] = 'test_suite_';
		}

		ConnectionManager::create('test_suite', $db->config);
		$db->config['prefix'] = $_prefix;

		// Get db connection
		$this->db =& ConnectionManager::getDataSource('test_suite');
		$this->db->cacheSources  = false;

		ClassRegistry::config(array('ds' => 'test_suite'));
	}
/**
 * Load fixtures specified in var $fixtures.
 *
 * @access private
 */
	function _loadFixtures() {
		if (!isset($this->fixtures) || empty($this->fixtures)) {
			return;
		}

		if (!is_array($this->fixtures)) {
			$this->fixtures = array_map('trim', explode(',', $this->fixtures));
		}

		$this->_fixtures = array();

		foreach ($this->fixtures as $index => $fixture) {
			$fixtureFile = null;

			if (strpos($fixture, 'core.') === 0) {
				$fixture = substr($fixture, strlen('core.'));
				foreach (Configure::corePaths('cake') as $key => $path) {
					$fixturePaths[] = $path . DS . 'tests' . DS . 'fixtures';
				}
			} elseif (strpos($fixture, 'app.') === 0) {
				$fixture = substr($fixture, strlen('app.'));
				$fixturePaths = array(
					TESTS . 'fixtures',
					VENDORS . 'tests' . DS . 'fixtures'
				);
			} elseif (strpos($fixture, 'plugin.') === 0) {
				$parts = explode('.', $fixture, 3);
				$pluginName = $parts[1];
				$fixture = $parts[2];
				$fixturePaths = array(
					APP . 'plugins' . DS . $pluginName . DS . 'tests' . DS . 'fixtures',
					TESTS . 'fixtures',
					VENDORS . 'tests' . DS . 'fixtures'
				);
				$pluginPaths = Configure::read('pluginPaths');
				foreach ($pluginPaths as $path) {
					if (file_exists($path . $pluginName . DS . 'tests' . DS. 'fixtures')) {
						$fixturePaths[0] = $path . $pluginName . DS . 'tests' . DS. 'fixtures';
						break;
					}
				}
			} else {
				$fixturePaths = array(
					TESTS . 'fixtures',
					VENDORS . 'tests' . DS . 'fixtures',
					TEST_CAKE_CORE_INCLUDE_PATH . DS . 'cake' . DS . 'tests' . DS . 'fixtures'
				);
			}

			foreach ($fixturePaths as $path) {
				if (is_readable($path . DS . $fixture . '_fixture.php')) {
					$fixtureFile = $path . DS . $fixture . '_fixture.php';
					break;
				}
			}

			if (isset($fixtureFile)) {
				require_once($fixtureFile);
				$fixtureClass = Inflector::camelize($fixture) . 'Fixture';
				$this->_fixtures[$this->fixtures[$index]] =& new $fixtureClass($this->db);
				$this->_fixtureClassMap[Inflector::camelize($fixture)] = $this->fixtures[$index];
			}
		}

		if (empty($this->_fixtures)) {
			unset($this->_fixtures);
		}
	}
}
?>
