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
 * @since         CakePHP(tm) v 2.0
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\TestSuite\Fixture;

use Cake\Core\App;
use Cake\Core\Configure;
use Cake\Core\Plugin;
use Cake\Database\Connection;
use Cake\Database\ConnectionManager;
use Cake\Error;
use Cake\TestSuite\Fixture\TestFixture;
use Cake\TestSuite\TestCase;
use Cake\Utility\Inflector;

/**
 * A factory class to manage the life cycle of test fixtures
 *
 */
class FixtureManager {

/**
 * Was this class already initialized?
 *
 * @var boolean
 */
	protected $_initialized = false;

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
 * @param Cake\TestSuite\TestCase $test the test case to inspect
 * @return void
 */
	public function fixturize($test) {
		$this->_initDb();
		if (empty($test->fixtures) || !empty($this->_processed[get_class($test)])) {
			return;
		}
		$test->db = ConnectionManager::get('test', false);
		if (!is_array($test->fixtures)) {
			$test->fixtures = array_map('trim', explode(',', $test->fixtures));
		}
		if (isset($test->fixtures)) {
			$this->_loadFixtures($test->fixtures);
		}

		$this->_processed[get_class($test)] = true;
	}

/**
 * Add aliaes for all non test prefixed connections.
 *
 * This allows models to use the test connections without
 * a pile of configuration work.
 *
 * @return void
 */
	protected function _aliasConnections() {
		$connections = ConnectionManager::configured();
		ConnectionManager::alias('test', 'default');
		$map = [
			'test' => 'default',
		];
		foreach ($connections as $connection) {
			if (isset($map[$connection])) {
				continue;
			}
			if (strpos($connection, 'test_') === 0) {
				$map[$connection] = substr($connection, 5);
			} else {
				$map['test_' . $connection] = $connection;
			}
		}
		foreach ($map as $alias => $connection) {
			ConnectionManager::alias($alias, $connection);
		}
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
		$this->_aliasConnections();
		$this->_initialized = true;
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
				list($core, $base) = explode('.', $fixture, 2);
				$baseNamespace = 'Cake';
			} elseif (strpos($fixture, 'app.') === 0) {
				list($app, $base) = explode('.', $fixture, 2);
				$baseNamespace = Configure::read('App.namespace');
			} elseif (strpos($fixture, 'plugin.') === 0) {
				list($p, $plugin, $base) = explode('.', $fixture);
				$baseNamespace = Plugin::getNamespace($plugin);
			} else {
				$base = $fixture;
			}
			$base = Inflector::camelize($base);
			$className = implode('\\', array($baseNamespace, 'Test\Fixture', $base . 'Fixture'));

			if (class_exists($className)) {
				$this->_loaded[$fixture] = new $className();
				$this->_fixtureMap[$base] = $this->_loaded[$fixture];
			} else {
				throw new \UnexpectedValueException(sprintf('Referenced fixture class %s not found', $className));
			}
		}
	}

/**
 * Runs the drop and create commands on the fixtures if necessary.
 *
 * @param Cake\TestSuite\Fixture\TestFixture $fixture the fixture object to create
 * @param Connection $db the datasource instance to use
 * @param boolean $drop whether drop the fixture if it is already created or not
 * @return void
 */
	protected function _setupTable(TestFixture $fixture, Connection $db = null, $drop = true) {
		if (!$db) {
			if (!empty($fixture->connection)) {
				$db = ConnectionManager::get($fixture->connection, false);
			}
			if (!$db) {
				$db = ConnectionManager::get('test', false);
			}
		}
		if (!empty($fixture->created) && in_array($db->configName(), $fixture->created)) {
			return;
		}

		$schemaCollection = $db->schemaCollection();
		$sources = (array)$schemaCollection->listTables();
		$table = $fixture->table;
		$exists = in_array($table, $sources);

		if ($drop && $exists) {
			$fixture->drop($db);
			$fixture->create($db);
		} elseif (!$exists) {
			$fixture->create($db);
		} else {
			$fixture->created[] = $db->configName();
		}
	}

/**
 * Creates the fixtures tables and inserts data on them.
 *
 * @param Cake\TestSuite\TestCase $test the test to inspect for fixture loading
 * @return void
 * @throws Cake\Error\Exception When fixture records cannot be inserted.
 */
	public function load(TestCase $test) {
		if (empty($test->fixtures)) {
			return;
		}

		$fixtures = $test->fixtures;
		if (empty($fixtures) || !$test->autoFixtures) {
			return;
		}

		$dbs = [];
		foreach ($fixtures as $f) {
			if (!empty($this->_loaded[$f])) {
				$fixture = $this->_loaded[$f];
				$dbs[$fixture->connection][$f] = $fixture;
			}
		}
		try {
			foreach ($dbs as $db => $fixtures) {
				$db = ConnectionManager::get($fixture->connection, false);
				$db->transactional(function($db) use ($fixtures, $test) {
					foreach ($fixtures as $fixture) {
						$this->_setupTable($fixture, $db, $test->dropTables);
						$fixture->truncate($db);
						$fixture->insert($db);
					}
				});
			}
		} catch (\PDOException $e) {
			$msg = sprintf('Unable to insert fixtures for "%s" test case. %s', get_class($test), $e->getMessage());
			throw new Error\Exception($msg);
		}
	}

/**
 * Truncates the fixtures tables
 *
 * @param Cake\TestSuite\TestCase $test the test to inspect for fixture unloading
 * @return void
 */
	public function unload(TestCase $test) {
		$fixtures = !empty($test->fixtures) ? $test->fixtures : array();
		foreach (array_reverse($fixtures) as $f) {
			if (isset($this->_loaded[$f])) {
				$fixture = $this->_loaded[$f];
				if (!empty($fixture->created)) {
					foreach ($fixture->created as $ds) {
						$db = ConnectionManager::get($ds);
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
 * @param boolean $dropTables Whether or not tables should be dropped and re-created.
 * @return void
 * @throws UnexpectedValueException if $name is not a previously loaded class
 */
	public function loadSingle($name, $db = null, $dropTables = true) {
		if (isset($this->_fixtureMap[$name])) {
			$fixture = $this->_fixtureMap[$name];
			if (!$db) {
				$db = ConnectionManager::get($fixture->connection);
			}
			$this->_setupTable($fixture, $db, $dropTables);
			$fixture->truncate($db);
			$fixture->insert($db);
		} else {
			throw new \UnexpectedValueException(sprintf('Referenced fixture class %s not found', $name));
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
					$db = ConnectionManager::get($ds);
					$fixture->drop($db);
				}
			}
		}
	}

}
