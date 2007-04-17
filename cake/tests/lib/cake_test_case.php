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
class CakeTestCase extends UnitTestCase {
/**
 * Methods used internally.
 *
 * @var array
 * @access private
 */
	var $methods = array('start', 'end', 'startcase', 'endcase', 'starttest', 'endtest');
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
 * Executes a Cake URL, optionally getting the view rendering or what is returned
 * when params['requested'] is set.
 *
 * @param string $url	Cake URL to execute (e.g: /articles/view/455)
 * @param string $requested	Set to true if params['requested'] should be set, false otherwise
 * @param array $data	Data that will be sent to controller. E.g: array('Article' => array('id'=>4))
 * @param string $method	Method to simulate posting of data to controller ('get' or 'post')
 * 
 * @return mixed	What is returned from action (if $requested is true), or view rendered html
 * 
 * @access protected
 */
	function requestAction($url, $requested = true, $data = null, $method = 'post') {
		$params = array();
		
		if (!$requested) {
			$params['return'] = true;
		}
		
		if (is_array($data) && !empty($data)) {
			$data = array('data' => $data);
			
			if (low($method) == 'get') {
				$_GET = $data;
			} else {
				$_POST = $data;
			}
		}
		
		return @Object::requestAction($url, $params);
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
			// Try for test DB
			restore_error_handler();
			@$db =& ConnectionManager::getDataSource('test');
			set_error_handler('simpleTestErrorHandler');

			// Try for default DB
			if (!$db->isConnected()) {
				$db =& ConnectionManager::getDataSource('default');
			}

			// Add test prefix
			$config = $db->config;
			$config['prefix'] .= 'test_suite_';

	 		// Set up db connection
			ConnectionManager::create('test_suite', $config);

			// Get db connection
			$this->db =& ConnectionManager::getDataSource('test_suite');
			$this->db->fullDebug = false;

			$this->_loadFixtures();
		}
		
		// Create records
		if (isset($this->_fixtures) && isset($this->db) && !in_array(low($method), array('start', 'end'))) {
			foreach($this->_fixtures as $fixture) {
				$inserts = $fixture->insert();

				if (isset($inserts) && !empty($inserts)) {
					foreach($inserts as $query) {
						if (isset($query) && $query !== false) {
							$this->db->_execute($query);
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
			foreach($this->_fixtures as $fixture) {
				$query = $fixture->create();

				if (isset($query) && $query !== false) {
					$this->db->_execute($query);
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
			foreach(array_reverse($this->_fixtures) as $fixture) {
				$query = $fixture->drop();

				if (isset($query) && $query !== false) {
					$this->db->_execute($query);
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
			foreach($this->_fixtures as $fixture) {
				$query = $fixture->truncate();

				if (isset($query) && $query !== false) {
					$this->db->_execute($query);
				}
			}
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
		$methods = am(am(array('start', 'startCase'), parent::getTests()), array('endCase', 'end'));

		return $methods;
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

		foreach($this->fixtures as $index => $fixture) {
			$fixtureFile = null;

			if (strpos($fixture, 'core.') === 0) {
				$fixture = substr($fixture, strlen('core.'));
				$fixturePaths = array(
					CAKE_CORE_INCLUDE_PATH . DS . 'cake' . DS . 'tests' . DS . 'fixtures'
				);
			} else if (strpos($fixture, 'app.') === 0) {
				$fixture = substr($fixture, strlen('app.'));
				$fixturePaths = array(
					TESTS . 'fixtures'
				);
			} else {
				$fixturePaths = array(
					TESTS . 'fixtures',
					CAKE_CORE_INCLUDE_PATH . DS . 'cake' . DS . 'tests' . DS . 'fixtures'
				);
			}

			foreach($fixturePaths as $path) {
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