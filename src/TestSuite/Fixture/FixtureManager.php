<?php
/**
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @since         2.0.0
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\TestSuite\Fixture;

use Cake\Core\Configure;
use Cake\Core\Exception\Exception;
use Cake\Database\Connection;
use Cake\Datasource\ConnectionManager;
use Cake\Utility\Inflector;

/**
 * A factory class to manage the life cycle of test fixtures
 *
 */
class FixtureManager
{

    /**
     * Was this class already initialized?
     *
     * @var bool
     */
    protected $_initialized = false;

    /**
     * Holds the fixture classes that where instantiated
     *
     * @var array
     */
    protected $_loaded = [];

    /**
     * Holds the fixture classes that where instantiated indexed by class name
     *
     * @var array
     */
    protected $_fixtureMap = [];

    /**
     * Inspects the test to look for unloaded fixtures and loads them
     *
     * @param \Cake\TestSuite\TestCase $test The test case to inspect.
     * @return void
     */
    public function fixturize($test)
    {
        $this->_initDb();
        if (empty($test->fixtures) || !empty($this->_processed[get_class($test)])) {
            return;
        }
        if (!is_array($test->fixtures)) {
            $test->fixtures = array_map('trim', explode(',', $test->fixtures));
        }
        $this->_loadFixtures($test);
        $this->_processed[get_class($test)] = true;
    }

    /**
     * Get the loaded fixtures.
     *
     * @return array
     */
    public function loaded()
    {
        return $this->_loaded;
    }

    /**
     * Add aliases for all non test prefixed connections.
     *
     * This allows models to use the test connections without
     * a pile of configuration work.
     *
     * @return void
     */
    protected function _aliasConnections()
    {
        $connections = ConnectionManager::configured();
        ConnectionManager::alias('test', 'default');
        $map = [];
        foreach ($connections as $connection) {
            if ($connection === 'test' || $connection === 'default') {
                continue;
            }
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
    protected function _initDb()
    {
        if ($this->_initialized) {
            return;
        }
        $this->_aliasConnections();
        $this->_initialized = true;
    }

    /**
     * Looks for fixture files and instantiates the classes accordingly
     *
     * @param \Cake\TestSuite\TestCase $test The test suite to load fixtures for.
     * @return void
     * @throws \UnexpectedValueException when a referenced fixture does not exist.
     */
    protected function _loadFixtures($test)
    {
        if (empty($test->fixtures)) {
            return;
        }
        foreach ($test->fixtures as $fixture) {
            if (isset($this->_loaded[$fixture])) {
                continue;
            }

            list($type, $pathName) = explode('.', $fixture, 2);
            $path = explode('/', $pathName);
            $name = array_pop($path);
            $additionalPath = implode('\\', $path);

            if ($type === 'core') {
                $baseNamespace = 'Cake';
            } elseif ($type === 'app') {
                $baseNamespace = Configure::read('App.namespace');
            } elseif ($type === 'plugin') {
                list($plugin, $name) = explode('.', $pathName);
                $path = implode('\\', explode('/', $plugin));
                $baseNamespace = Inflector::camelize(str_replace('\\', '\ ', $path));
                $additionalPath = null;
            } else {
                $baseNamespace = '';
                $name = $fixture;
            }
            $name = Inflector::camelize($name);
            $nameSegments = [
                $baseNamespace,
                'Test\Fixture',
                $additionalPath,
                $name . 'Fixture'
            ];
            $className = implode('\\', array_filter($nameSegments));

            if (class_exists($className)) {
                $this->_loaded[$fixture] = new $className();
                $this->_fixtureMap[$name] = $this->_loaded[$fixture];
            } else {
                $msg = sprintf(
                    'Referenced fixture class "%s" not found. Fixture "%s" was referenced in test case "%s".',
                    $className,
                    $fixture,
                    get_class($test)
                );
                throw new \UnexpectedValueException($msg);
            }
        }
    }

    /**
     * Runs the drop and create commands on the fixtures if necessary.
     *
     * @param \Cake\TestSuite\Fixture\TestFixture $fixture the fixture object to create
     * @param Connection $db the datasource instance to use
     * @param array $sources The existing tables in the datasource.
     * @param bool $drop whether drop the fixture if it is already created or not
     * @return void
     */
    protected function _setupTable($fixture, $db, array $sources, $drop = true)
    {
        if (!empty($fixture->created) && in_array($db->configName(), $fixture->created)) {
            return;
        }

        $table = $fixture->table;
        $exists = in_array($table, $sources);

        if ($drop && $exists) {
            $fixture->drop($db);
            $fixture->create($db);
        } elseif (!$exists) {
            $fixture->create($db);
        } else {
            $fixture->created[] = $db->configName();
            $fixture->truncate($db);
        }
    }

    /**
     * Creates the fixtures tables and inserts data on them.
     *
     * @param \Cake\TestSuite\TestCase $test The test to inspect for fixture loading.
     * @return void
     * @throws \Cake\Core\Exception\Exception When fixture records cannot be inserted.
     */
    public function load($test)
    {
        if (empty($test->fixtures)) {
            return;
        }

        $fixtures = $test->fixtures;
        if (empty($fixtures) || !$test->autoFixtures) {
            return;
        }

        try {
            $createTables = function ($db, $fixtures) use ($test) {
                $tables = $db->schemaCollection()->listTables();
                foreach ($fixtures as $fixture) {
                    if (!in_array($db->configName(), (array)$fixture->created)) {
                        $this->_setupTable($fixture, $db, $tables, $test->dropTables);
                    } else {
                        $fixture->truncate($db);
                    }
                }
            };
            $this->_runOperation($fixtures, $createTables);

            // Use a separate transaction because of postgres.
            $insert = function ($db, $fixtures) {
                foreach ($fixtures as $fixture) {
                    $fixture->insert($db);
                }
            };
            $this->_runOperation($fixtures, $insert);
        } catch (\PDOException $e) {
            $msg = sprintf('Unable to insert fixtures for "%s" test case. %s', get_class($test), $e->getMessage());
            throw new Exception($msg);
        }
    }

    /**
     * Run a function on each connection and collection of fixtures.
     *
     * @param array $fixtures A list of fixtures to operate on.
     * @param callable $operation The operation to run on each connection + fixture set.
     * @return void
     */
    protected function _runOperation($fixtures, $operation)
    {
        $dbs = $this->_fixtureConnections($fixtures);
        foreach ($dbs as $connection => $fixtures) {
            $db = ConnectionManager::get($connection, false);
            $logQueries = $db->logQueries();
            if ($logQueries) {
                $db->logQueries(false);
            }
            $db->transactional(function ($db) use ($fixtures, $operation) {
                $db->disableForeignKeys();
                $operation($db, $fixtures);
                $db->enableForeignKeys();
            });
            if ($logQueries) {
                $db->logQueries(true);
            }
        }
    }

    /**
     * Get the unique list of connections that a set of fixtures contains.
     *
     * @param array $fixtures The array of fixtures a list of connections is needed from.
     * @return array An array of connection names.
     */
    protected function _fixtureConnections($fixtures)
    {
        $dbs = [];
        foreach ($fixtures as $f) {
            if (!empty($this->_loaded[$f])) {
                $fixture = $this->_loaded[$f];
                $dbs[$fixture->connection][$f] = $fixture;
            }
        }
        return $dbs;
    }

    /**
     * Truncates the fixtures tables
     *
     * @param \Cake\TestSuite\TestCase $test The test to inspect for fixture unloading.
     * @return void
     */
    public function unload($test)
    {
        if (empty($test->fixtures)) {
            return;
        }
        $truncate = function ($db, $fixtures) {
            $connection = $db->configName();
            foreach ($fixtures as $fixture) {
                if (!empty($fixture->created) && in_array($connection, $fixture->created)) {
                    $fixture->truncate($db);
                }
            }
        };
        $this->_runOperation($test->fixtures, $truncate);
    }

    /**
     * Creates a single fixture table and loads data into it.
     *
     * @param string $name of the fixture
     * @param \Cake\Database\Connection $db Connection instance or leave null to get a Connection from the fixture
     * @param bool $dropTables Whether or not tables should be dropped and re-created.
     * @return void
     * @throws \UnexpectedValueException if $name is not a previously loaded class
     */
    public function loadSingle($name, $db = null, $dropTables = true)
    {
        if (isset($this->_fixtureMap[$name])) {
            $fixture = $this->_fixtureMap[$name];
            if (!$db) {
                $db = ConnectionManager::get($fixture->connection);
            }

            if (!in_array($db->configName(), (array)$fixture->created)) {
                $sources = $db->schemaCollection()->listTables();
                $this->_setupTable($fixture, $db, $sources, $dropTables);
            }
            if (!$dropTables) {
                $fixture->truncate($db);
            }
            $fixture->insert($db);
        } else {
            throw new \UnexpectedValueException(sprintf('Referenced fixture class %s not found', $name));
        }
    }

    /**
     * Drop all fixture tables loaded by this class
     *
     * @return void
     */
    public function shutDown()
    {
        $shutdown = function ($db, $fixtures) {
            $connection = $db->configName();
            foreach ($fixtures as $fixture) {
                if (!empty($fixture->created) && in_array($connection, $fixture->created)) {
                    $fixture->drop($db);
                }
            }
        };
        $this->_runOperation(array_keys($this->_loaded), $shutdown);
    }
}
