<?php

class CakeFixtureManager {
	protected $_initialized = false;
	protected $_db = null;
	protected $_loaded = array();
	protected $_fixtureMap = array();

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
		}

		// Try for default DB
		if (!$testDbAvailable) {
			$db = ConnectionManager::getDataSource('default');
			$_prefix = $db->config['prefix'];
			$db->config['prefix'] = 'test_suite_';
		}

		ConnectionManager::create('test_suite', $db->config);
		$db->config['prefix'] = $_prefix;

		// Get db connection
		$this->_db = ConnectionManager::getDataSource('test_suite');
		$this->_db->cacheSources  = false;

		ClassRegistry::config(array('ds' => 'test_suite'));
		$this->_initialized = true;
	}

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

	protected function _setupTable($fixture, $db = null, $drop = true) {
		if (!empty($fixture->created)) {
			return;
		}
		if (!$db) {
			$db = $this->_db;
		}

		$cacheSources = $db->cacheSources;
		$db->cacheSources = false;
		$db->cacheSources = $cacheSources;
		$sources = $db->listSources();
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

	public function load(CakeTestCase $test) {
		if (empty($test->fixtures)) {
			return;
		}
		$fixtures = $test->fixtures;
		if (empty($fixtures) || $test->autoFixtures == false) {
			return;
		}

		foreach ($fixtures as $f) {
			if (!empty($this->_loaded[$f])) {
				$fixture = $this->_loaded[$f];
				$this->_setupTable($fixture, $test->db, $test->dropTables);
				$fixture->insert($test->db);
			}
		}
	}

	public function unload(CakeTestCase $test) {
		if (empty($test->fixtures)) {
			return;
		}
		$fixtures = $test->fixtures;
		if (empty($fixtures)) {
			return;
		}
		foreach ($fixtures as $f) {
			if (isset($this->_loaded[$f])) {
				$fixture = $this->_loaded[$f];
				if (!empty($fixture->created)) {
					$fixture->truncate($test->db);
				}
			}
		}
	}

	public function loadSingle($name, $db = null) {
		$name .= 'Fixture';
		if (isset($this->_fixtureMap[$name])) {
			if (!$db) {
				$db = $this->_db;
			}
			$fixture = $this->_fixtureMap[$name];
			$fixture->truncate($db);
			$fixture->insert($db);
		} else {
			throw new UnexpectedValueException(sprintf(__('Referenced fixture class %s not found'), $name));
		}
	}

	public function shutDown() {
		foreach ($this->_loaded as $fixture) {
			if (!empty($fixture->created)) {
				$fixture->drop($this->_db);
			}
		}
	}
}