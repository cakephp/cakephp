<?php

require_once CAKE . 'tests' . DS . 'lib' . DS . 'cake_test_model.php';
require_once CAKE . 'tests' . DS . 'lib' . DS . 'cake_test_fixture.php';

vendor('simpletest'.DS.'unit_tester');

class CakeTestCase extends UnitTestCase {
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
		$methods = parent::getTests();
		
		if (isset($this->fixtures)) {
			$methods = am(am(array('start'), $methods), array('end'));
		}
		
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