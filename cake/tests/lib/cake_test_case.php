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
 * Copyright 2005-2007, Cake Software Foundation, Inc.
 *								1785 E. Sahara Avenue, Suite 490-204
 *								Las Vegas, Nevada 89104
 *
 *  Licensed under The Open Group Test Suite License
 *  Redistributions of files must retain the above copyright notice.
 *
 * @filesource
 * @copyright		Copyright 2005-2007, Cake Software Foundation, Inc.
 * @link				https://trac.cakephp.org/wiki/Developement/TestSuite CakePHP(tm) Tests
 * @package			cake
 * @subpackage		cake.cake.tests.libs
 * @since			CakePHP(tm) v 1.2.0.4667
 * @version			$Revision$
 * @modifiedby		$LastChangedBy$
 * @lastmodified	$Date$
 * @license			http://www.opensource.org/licenses/opengroup.php The Open Group Test Suite License
 */
require_once CAKE . 'tests' . DS . 'lib' . DS . 'cake_test_model.php';
require_once CAKE . 'tests' . DS . 'lib' . DS . 'cake_test_fixture.php';
vendor('simpletest'.DS.'unit_tester');
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

			$classRegistry =& ClassRegistry::getInstance();
			$models = array();

			foreach ($classRegistry->__map as $key => $name) {
				$object =& $classRegistry->getObject(Inflector::camelize($key));
				if (is_subclass_of($object, 'Model') && ((is_array($params['fixturize']) && in_array($object->alias, $params['fixturize'])) || $params['fixturize'] === true)) {
					$models[$object->alias] = array (
						'table' => $object->table,
						'model' => $object->alias,
						'key' => Inflector::camelize($key)
					);
				}
			}

			if (!empty($models) && isset($this->db)) {
				$this->_queries = array(
					'create' => array(),
					'insert' => array(),
					'drop' => array()
				);

				foreach ($models as $model) {
					$fixture =& new CakeTestFixture($this->db);

					$fixture->name = $model['model'] . 'Test';
					$fixture->table = $model['table'];
					$fixture->import = array('model' => $model['model'], 'records' => true);

					$fixture->init();

					$createFixture = $fixture->create();
					$insertsFixture = $fixture->insert();
					$dropFixture = $fixture->drop();

					if (!empty($createFixture)) {
						$this->_queries['create'] = am($this->_queries['create'], array($createFixture));
					}
					if (!empty($insertsFixture)) {
						$this->_queries['insert'] = am($this->_queries['insert'], $insertsFixture);
					}
					if (!empty($dropFixture)) {
						$this->_queries['drop'] = am($this->_queries['drop'], array($dropFixture));
					}
				}

				foreach ($this->_queries['create'] as $query) {
					if (isset($query) && $query !== false) {
						$this->db->execute($query);
					}
				}

				foreach ($this->_queries['insert'] as $query) {
					if (isset($query) && $query !== false) {
						$this->db->execute($query);
					}
				}

				foreach ($models as $model) {
					$object =& $classRegistry->getObject($model['key']);
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
		if (isset($this->db) && isset($this->_queries) && !empty($this->_queries) && !empty($this->_queries['drop'])) {
			foreach ($this->_queries['drop'] as $query) {
				if (isset($query) && $query !== false) {
					$this->db->execute($query);
				}
			}
		}
	}
/**
 * Executes a Cake URL, optionally getting the view rendering or what is returned
 * when params['requested'] is set.
 *
 * @param string $url	Cake URL to execute (e.g: /articles/view/455)
 * @param array $params	Parameters
 *
 * @return mixed	What is returned from action (if $requested is true), or view rendered html
 *
 * @access protected
 */
	function testAction($url, $params = array()) {
		$default = array(
			'return' => 'result',
			'fixturize' => false,
			'data' => array(),
			'method' => 'post'
		);

		$params = am($default, $params);

		if (!empty($params['data'])) {
			$data = array('data' => $params['data']);

			if (low($params['method']) == 'get') {
				$_GET = $data;
			} else {
				$_POST = $data;
			}
		}

		$return = $params['return'];

		unset($params['data']);
		unset($params['method']);
		unset($params['return']);

		$dispatcher =& new CakeTestDispatcher();
		$dispatcher->testCase($this);

		if (low($return) != 'result') {
			$params['return'] = 0;

			ob_start();
			@$dispatcher->dispatch($url, $params);
			$result = ob_get_clean();

			if (low($return) == 'vars') {
				$view =& ClassRegistry::getObject('view');
				$viewVars = $view->getVars();

				$result = array();

				foreach ($viewVars as $var) {
					$result[$var] = $view->getVar($var);
				}

				if (!empty($view->pageTitle)) {
					$result = am($result, array('title' => $view->pageTitle));
				}
			}
		} else {
			$params['return'] = 1;
			$params['bare'] = 1;
			$params['requested'] = 1;

			$result = @$dispatcher->dispatch($url, $params);
		}

		$classRegistry =& ClassRegistry::getInstance();
		$keys = array_keys($classRegistry->__objects);
		foreach ($keys as $key) {
			$key = Inflector::camelize($key);
			$classRegistry->removeObject($key);
		}
		$classRegistry->__map = array();

		if (isset($this->_queries)) {
			unset($this->_queries);
		}

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
		if (isset($this->fixtures) && low($method) == 'start') {
			$this->_initDb();
			$this->_loadFixtures();
		}

		// Create records
		if (isset($this->_fixtures) && isset($this->db) && !in_array(low($method), array('start', 'end')) && $this->__truncated) {
			foreach ($this->_fixtures as $fixture) {
				$inserts = $fixture->insert();

				if (isset($inserts) && !empty($inserts)) {
					foreach ($inserts as $query) {
						if (isset($query) && $query !== false) {
							$this->db->execute($query);
						}
					}
				}
			}
		}

		if (!in_array(low($method), $this->methods)) {
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
			foreach ($this->_fixtures as $fixture) {
				$query = $fixture->create();
				if (isset($query) && $query !== false) {
					$this->db->execute($query);
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
			foreach (array_reverse($this->_fixtures) as $fixture) {
				$query = $fixture->drop();
				if (isset($query) && $query !== false) {
					$this->db->execute($query);
				}
			}
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
		if (isset($this->_fixtures) && isset($this->db) && !in_array(low($method), array('start', 'end'))) {
			foreach ($this->_fixtures as $fixture) {
				$this->db->truncate($fixture->table);
			}
			$this->__truncated = true;
		} else {
			$this->__truncated = false;
		}

		if (!in_array(low($method), $this->methods)) {
			$this->endTest($method);
		}

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
		$methods = am(am(array('start', 'startCase'), $methods), array('endCase', 'end'));
		return $methods;
	}
/**
 * Initialize DB connection.
 *
 */
	function _initDb() {
		$testDbAvailable = false;

		if (class_exists('DATABASE_CONFIG')) {
			$dbConfig =& new DATABASE_CONFIG();
			$testDbAvailable = isset($dbConfig->test);
		}

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
		}

		$db->config['prefix'] = 'test_suite_';

		ConnectionManager::create('test_suite', $db->config);
		// Get db connection
		$this->db =& ConnectionManager::getDataSource('test_suite');
		$this->db->cacheSources  = false;
		$this->db->fullDebug = false;
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
			$this->fixtures = array( $this->fixtures );
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
					TESTS . DS . 'fixtures',
					VENDORS . 'tests' . DS . 'fixtures'
				);
			} else {
				$fixturePaths = array(
					TESTS . 'fixtures',
					VENDORS . 'tests' . DS . 'fixtures',
					CAKE_CORE_INCLUDE_PATH . DS . 'cake' . DS . 'tests' . DS . 'fixtures'
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
			}
		}

		if (empty($this->_fixtures)) {
			unset($this->_fixtures);
		}
	}
}
?>