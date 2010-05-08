<?php

class CakeFixtureManager {
	protected static $_initialized = false;
	protected static $_db;
	protected static $_loaded = array();
	protected static $_fixtureMap = array();

	public static function fixturize(CakeTestCase $test) {
		if (empty($test->fixtures)) {
			return;
		}
		if (!self::$_initialized) {
			self::_initDb();
			if (!empty(self::$_db)) {
				$test->db = self::$_db;
			}
		}
		if (!is_array($test->fixtures)) {
			$test->fixtures = array_map('trim', explode(',', $test->fixtures));
		}
		if (isset($test->fixtures)) {
			self::_loadFixtures($test->fixtures);
		}
	}

	protected static function _initDb() {
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
		self::$_db = ConnectionManager::getDataSource('test_suite');
		self::$_db->cacheSources  = false;

		ClassRegistry::config(array('ds' => 'test_suite'));
	}

	protected static function _loadFixtures($fixtures) {
		foreach ($fixtures as $index => $fixture) {
			$fixtureFile = null;
			$fixtureIndex = $fixture;
			if (isset(self::$_loaded[$fixture])) {
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
				self::$_loaded[$fixtureIndex] = new $fixtureClass(self::$_db);
				self::$_fixtureMap[$fixtureClass] = self::$_loaded[$fixtureIndex];
			}
		}
	}

	protected static function setupTable($fixture, $db = null, $drop = true) {
		if (!empty($fixture->created)) {
			return;
		}
		if (!$db) {
			$db = self::$_db;
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

	public static function load(CakeTestCase $test) {
		if (empty($test->fixtures)) {
			return;
		}
		$fixtures = $test->fixtures;
		if (empty($fixtures) || $test->autoFixtures == false) {
			return;
		}

		foreach ($fixtures as $f) {
			if (!empty(self::$_loaded[$f])) {
				$fixture = self::$_loaded[$f];
				self::setupTable($fixture, $test->db, $test->dropTables);
				$fixture->insert($test->db);
			}
		}
	}

	public static function unload(CakeTestCase $test) {
		if (empty($test->fixtures)) {
			return;
		}
		$fixtures = $test->fixtures;
		if (empty($fixtures)) {
			return;
		}
		foreach ($fixtures as $f) {
			if (isset(self::$_loaded[$f])) {
				$fixture = self::$_loaded[$f];
				if (!empty($fixture->created)) {
					$fixture->truncate($test->db);
				}
			}
		}
	}

	public static function loadSingle($name, $db = null) {
		$name .= 'Fixture';
		if (isset(self::$_fixtureMap[$name])) {
			if (!$db) {
				$db = self::$_db;
			}
			$fixture = self::$_fixtureMap[$name];
			$fixture->truncate($db);
			$fixture->insert($db);
		} else {
			throw new UnexpectedValueException(sprintf(__('Referenced fixture class %s not found'), $name));
		}
	}
}