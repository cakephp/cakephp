<?php
/**
 * A factory class to manage the life cycle of test fixtures
 *
 * PHP 5
 *
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright 2005-2010, Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright 2005-2010, Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @package       cake.tests.libs
 * @since         CakePHP(tm) v 2.0
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
PHP_CodeCoverage_Filter::getInstance()->addFileToBlacklist(__FILE__, 'DEFAULT');

class CakeFixtureManager {

/**
 * Was this class already initialized?
 *
 * @var boolean
 */
	protected $_initialized = false;

/**
 * Default datasource to use
 *
 * @var DataSource
 */
	protected $_db = null;

/**
 * Holds the fixture classes that where instantiated
 *
 * @var array
 */
	protected $_loaded = array();

/**
 * Holds the fixture classes that where ins	tantiated indexed by class name
 *
 * @var array
 */
	protected $_fixtureMap = array();

/**
 * Inspects the test to look for unloaded fixtures and loads them
 *
 * @param CakeTestCase $test the test case to inspect
 * @return void
 */
	public function fixturize(CakeTestCase $test) {
		if (empty($test->fixtures) || !empty($this->_processed[get_class($test)])) {
			$test->db = $this->_db;
			return;
		}
		$this->_initDb();
		$test->db = $this->_db;
		if (!is_array($test->fixtures)) {
			$test->fixtures = array_map('trim', explode(',', $test->fixtures));
		}
		if (isset($test->fixtures)) {
			$this->_loadFixtures($test->fixtures);
		}

		$this->_processed[get_class($test)] = true;
	}

/**
 * Initializes this class with a DataSource object to use as default for all fixtures
 *
 * @return void
 */
	protected function _initDb() {
		if ($this->_initialized) {
			return;
		}
		$testDbAvailable = in_array('test', array_keys(ConnectionManager::enumConnectionObjects()));

		$_prefix = null;

		if ($testDbAvailable) {
			// Try for test DB
			@$db = ConnectionManager::getDataSource('test');
			$testDbAvailable = $db->isConnected();
		} else {
			throw new MissingConnectionException(__('You need to create a $test datasource connection to start using fixtures'));
		}

		if (!$testDbAvailable) {
			throw new MissingConnectionException(__('Unable to connect to the $test datasource'));
		}

		$this->_db = $db;
		ClassRegistry::config(array('ds' => 'test'));
		$this->_initialized = true;
	}

/**
 * Looks for fixture files and instantiates the classes accordingly
 *
 * @param array $fixtures the fixture names to load using the notation {type}.{name}
 * @return void
 */
	protected function _loadFixtures($fixtures) {
		foreach ($fixtures as $index => $fixture) {
			$fixtureFile = null;
			$fixtureIndex = $fixture;
			if (isset($this->_loaded[$fixture])) {
				continue;
			}
			if (strpos($fixture, 'core.') === 0) {
				$fixture = substr($fixture, strlen('core.'));
				foreach (App::core('cake') as $key => $path) {
					$fixturePaths[] = $path . 'tests' . DS . 'fixtures';
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
					App::pluginPath($pluginName) . 'tests' . DS . 'fixtures',
					TESTS . 'fixtures',
					VENDORS . 'tests' . DS . 'fixtures'
				);
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
				$this->_loaded[$fixtureIndex] = new $fixtureClass($this->_db);
				$this->_fixtureMap[$fixtureClass] = $this->_loaded[$fixtureIndex];
			}
		}
	}

/**
 * Runs the drop and create commands on the fixtures if necessary
 *
 * @param CakeTestFixture $fixture the fixture object to create
 * @param DataSource $db the datasource instance to use
 * @param boolean $drop whether drop the fixture if it is already created or not
 * @return void
 */
	protected function _setupTable($fixture, $db = null, $drop = true) {
		if (!empty($fixture->created)) {
			return;
		}
		if (!$db) {
			$db = $this->_db;
		}

		$cacheSources = $db->cacheSources;
		$db->cacheSources = false;
		$sources = $db->listSources();
		$db->cacheSources = $cacheSources;
		$table = $db->config['prefix'] . $fixture->table;

		if ($drop && in_array($table, $sources)) {
			$fixture->drop($db);
			$fixture->create($db);
			$fixture->created = true;
		} elseif (!in_array($table, $sources)) {
			$fixture->create($db);
			$fixture->created = true;
		}
	}

/**
 * Crates the fixtures tables and inserts data on them
 *
 * @param CakeTestCase $test the test to inspect for fixture loading
 * @return void
 */
	public function load(CakeTestCase $test) {
		if (empty($test->fixtures)) {
			return;
		}
		$fixtures = $test->fixtures;
		if (empty($fixtures) || $test->autoFixtures == false) {
			return;
		}

		$test->db->begin();
		foreach ($fixtures as $f) {
			if (!empty($this->_loaded[$f])) {
				$fixture = $this->_loaded[$f];
				$this->_setupTable($fixture, $test->db, $test->dropTables);
				$fixture->insert($test->db);
			}
		}
		$test->db->commit();
	}

/**
 * Trucantes the fixtures tables
 *
 * @param CakeTestCase $test the test to inspect for fixture unloading
 * @return void
 */
	public function unload(CakeTestCase $test) {
		$fixtures = !empty($test->fixtures) ? $test->fixtures : array();
		foreach ($fixtures as $f) {
			if (isset($this->_loaded[$f])) {
				$fixture = $this->_loaded[$f];
				if (!empty($fixture->created)) {
					$fixture->truncate($test->db);
				}
			}
		}
	}

/**
 * Trucantes the fixtures tables
 *
 * @param CakeTestCase $test the test to inspect for fixture unloading
 * @return void
 * @throws UnexpectedValueException if $name is not a previously loaded class
 */
	public function loadSingle($name, $db = null) {
		$name .= 'Fixture';
		if (isset($this->_fixtureMap[$name])) {
			if (!$db) {
				$db = $this->_db;
			}
			$fixture = $this->_fixtureMap[$name];
			$this->_setupTable($fixture, $db);
			$fixture->truncate($db);
			$fixture->insert($db);
		} else {
			throw new UnexpectedValueException(__('Referenced fixture class %s not found', $name));
		}
	}

/**
 * Drop all fixture tables loaded by this class
 *
 * @return void
 */
	public function shutDown() {
		foreach ($this->_loaded as $fixture) {
			if (!empty($fixture->created)) {
				$fixture->drop($this->_db);
			}
		}
	}
}