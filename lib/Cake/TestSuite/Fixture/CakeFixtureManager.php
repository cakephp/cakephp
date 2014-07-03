<?php
/**
 * A factory class to manage the life cycle of test fixtures
 *
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @package       Cake.TestSuite.Fixture
 * @since         CakePHP(tm) v 2.0
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */

App::uses('ConnectionManager', 'Model');
App::uses('ClassRegistry', 'Utility');

/**
 * A factory class to manage the life cycle of test fixtures
 *
 * @package       Cake.TestSuite.Fixture
 */
class CakeFixtureManager {

/**
 * Was this class already initialized?
 *
 * @var bool
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
 * Holds the fixture classes that where instantiated indexed by class name
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
	public function fixturize($test) {
		if (!$this->_initialized) {
			ClassRegistry::config(array('ds' => 'test', 'testing' => true));
		}
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
		$db = ConnectionManager::getDataSource('test');
		$db->cacheSources = false;
		$this->_db = $db;
		$this->_initialized = true;
	}

/**
 * Parse the fixture path included in test cases, to get the fixture class name, and the
 * real fixture path including sub-directories
 * 
 * @param string $fixturePath the fixture path to parse
 * @return array containing fixture class name and optional additional path
 */
	protected function _parseFixturePath($fixturePath) {
		$pathTokenArray = explode('/', $fixturePath);
		$fixture = array_pop($pathTokenArray);
		$additionalPath = '';
		foreach ($pathTokenArray as $pathToken) {
			$additionalPath .= DS . $pathToken;
		}
		return array('fixture' => $fixture, 'additionalPath' => $additionalPath);
	}

/**
 * Looks for fixture files and instantiates the classes accordingly
 *
 * @param array $fixtures the fixture names to load using the notation {type}.{name}
 * @return void
 * @throws UnexpectedValueException when a referenced fixture does not exist.
 */
	protected function _loadFixtures($fixtures) {
		foreach ($fixtures as $fixture) {
			$fixtureFile = null;
			$fixtureIndex = $fixture;
			if (isset($this->_loaded[$fixture])) {
				continue;
			}

			if (strpos($fixture, 'core.') === 0) {
				$fixture = substr($fixture, strlen('core.'));
				$fixturePaths[] = CAKE . 'Test' . DS . 'Fixture';
			} elseif (strpos($fixture, 'app.') === 0) {
				$fixturePrefixLess = substr($fixture, strlen('app.'));
				$fixtureParsedPath = $this->_parseFixturePath($fixturePrefixLess);
				$fixture = $fixtureParsedPath['fixture'];
				$fixturePaths = array(
					TESTS . 'Fixture' . $fixtureParsedPath['additionalPath']
				);
			} elseif (strpos($fixture, 'plugin.') === 0) {
				$explodedFixture = explode('.', $fixture, 3);
				$pluginName = $explodedFixture[1];
				$fixtureParsedPath = $this->_parseFixturePath($explodedFixture[2]);
				$fixture = $fixtureParsedPath['fixture'];
				$fixturePaths = array(
					CakePlugin::path(Inflector::camelize($pluginName)) . 'Test' . DS . 'Fixture' . $fixtureParsedPath['additionalPath'],
					TESTS . 'Fixture' . $fixtureParsedPath['additionalPath']
				);
			} else {
				$fixturePaths = array(
					TESTS . 'Fixture',
					CAKE . 'Test' . DS . 'Fixture'
				);
			}

			$loaded = false;
			foreach ($fixturePaths as $path) {
				$className = Inflector::camelize($fixture);
				if (is_readable($path . DS . $className . 'Fixture.php')) {
					$fixtureFile = $path . DS . $className . 'Fixture.php';
					require_once $fixtureFile;
					$fixtureClass = $className . 'Fixture';
					$this->_loaded[$fixtureIndex] = new $fixtureClass();
					$this->_fixtureMap[$fixtureClass] = $this->_loaded[$fixtureIndex];
					$loaded = true;
					break;
				}
			}

			if (!$loaded) {
				$firstPath = str_replace(array(APP, CAKE_CORE_INCLUDE_PATH, ROOT), '', $fixturePaths[0] . DS . $className . 'Fixture.php');
				throw new UnexpectedValueException(__d('cake_dev', 'Referenced fixture class %s (%s) not found', $className, $firstPath));
			}
		}
	}

/**
 * Runs the drop and create commands on the fixtures if necessary.
 *
 * @param CakeTestFixture $fixture the fixture object to create
 * @param DataSource $db the datasource instance to use
 * @param bool $drop whether drop the fixture if it is already created or not
 * @return void
 */
	protected function _setupTable($fixture, $db = null, $drop = true) {
		if (!$db) {
			if (!empty($fixture->useDbConfig)) {
				$db = ConnectionManager::getDataSource($fixture->useDbConfig);
			} else {
				$db = $this->_db;
			}
		}
		if (!empty($fixture->created) && in_array($db->configKeyName, $fixture->created)) {
			return;
		}

		$sources = (array)$db->listSources();
		$table = $db->config['prefix'] . $fixture->table;
		$exists = in_array($table, $sources);

		if ($drop && $exists) {
			$fixture->drop($db);
			$fixture->create($db);
		} elseif (!$exists) {
			$fixture->create($db);
		} else {
			$fixture->created[] = $db->configKeyName;
		}
	}

/**
 * Creates the fixtures tables and inserts data on them.
 *
 * @param CakeTestCase $test the test to inspect for fixture loading
 * @return void
 */
	public function load(CakeTestCase $test) {
		if (empty($test->fixtures)) {
			return;
		}
		$fixtures = $test->fixtures;
		if (empty($fixtures) || !$test->autoFixtures) {
			return;
		}

		foreach ($fixtures as $f) {
			if (!empty($this->_loaded[$f])) {
				$fixture = $this->_loaded[$f];
				$db = ConnectionManager::getDataSource($fixture->useDbConfig);
				$db->begin();
				$this->_setupTable($fixture, $db, $test->dropTables);
				$fixture->truncate($db);
				$fixture->insert($db);
				$db->commit();
			}
		}
	}

/**
 * Truncates the fixtures tables
 *
 * @param CakeTestCase $test the test to inspect for fixture unloading
 * @return void
 */
	public function unload(CakeTestCase $test) {
		$fixtures = !empty($test->fixtures) ? $test->fixtures : array();
		foreach (array_reverse($fixtures) as $f) {
			if (isset($this->_loaded[$f])) {
				$fixture = $this->_loaded[$f];
				if (!empty($fixture->created)) {
					foreach ($fixture->created as $ds) {
						$db = ConnectionManager::getDataSource($ds);
						$fixture->truncate($db);
					}
				}
			}
		}
	}

/**
 * Creates a single fixture table and loads data into it.
 *
 * @param string $name of the fixture
 * @param DataSource $db DataSource instance or leave null to get DataSource from the fixture
 * @param bool $dropTables Whether or not tables should be dropped and re-created.
 * @return void
 * @throws UnexpectedValueException if $name is not a previously loaded class
 */
	public function loadSingle($name, $db = null, $dropTables = true) {
		$name .= 'Fixture';
		if (isset($this->_fixtureMap[$name])) {
			$fixture = $this->_fixtureMap[$name];
			if (!$db) {
				$db = ConnectionManager::getDataSource($fixture->useDbConfig);
			}
			$this->_setupTable($fixture, $db, $dropTables);
			$fixture->truncate($db);
			$fixture->insert($db);
		} else {
			throw new UnexpectedValueException(__d('cake_dev', 'Referenced fixture class %s not found', $name));
		}
	}

/**
 * Drop all fixture tables loaded by this class
 *
 * This will also close the session, as failing to do so will cause
 * fatal errors with database sessions.
 *
 * @return void
 */
	public function shutDown() {
		if (session_id()) {
			session_write_close();
		}
		foreach ($this->_loaded as $fixture) {
			if (!empty($fixture->created)) {
				foreach ($fixture->created as $ds) {
					$db = ConnectionManager::getDataSource($ds);
					$fixture->drop($db);
				}
			}
		}
	}

}
