<?php
declare(strict_types=1);

/**
 * CakePHP(tm) : Rapid Development Framework (https://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 * @link          https://cakephp.org CakePHP(tm) Project
 * @since         2.0.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\TestSuite\Fixture;

use Cake\Core\Configure;
use Cake\Core\Exception\Exception;
use Cake\Database\ConstraintsInterface;
use Cake\Database\Schema\TableSchema;
use Cake\Database\Schema\TableSchemaAwareInterface;
use Cake\Datasource\ConnectionInterface;
use Cake\Datasource\ConnectionManager;
use Cake\Datasource\FixtureInterface;
use Cake\TestSuite\TestCase;
use PDOException;
use RuntimeException;
use UnexpectedValueException;

/**
 * A factory class to manage the life cycle of test fixtures
 */
class FixtureManager
{
    /**
     * Was this instance already initialized?
     *
     * @var bool
     */
    protected $_initialized = false;

    /**
     * Holds the fixture classes that where instantiated
     *
     * @var \Cake\Datasource\FixtureInterface[]
     */
    protected $_loaded = [];

    /**
     * Holds the fixture classes that where instantiated indexed by class name
     *
     * @var \Cake\Datasource\FixtureInterface[]
     */
    protected $_fixtureMap = [];

    /**
     * A map of connection names and the fixture currently in it.
     *
     * @var array
     */
    protected $_insertionMap = [];

    /**
     * List of TestCase class name that have been processed
     *
     * @var array
     */
    protected $_processed = [];

    /**
     * Is the test runner being run with `--debug` enabled.
     * When true, fixture SQL will also be logged.
     *
     * @var bool
     */
    protected $_debug = false;

    /**
     * Modify the debug mode.
     *
     * @param bool $debug Whether or not fixture debug mode is enabled.
     * @return void
     */
    public function setDebug(bool $debug): void
    {
        $this->_debug = $debug;
    }

    /**
     * Inspects the test to look for unloaded fixtures and loads them
     *
     * @param \Cake\TestSuite\TestCase $test The test case to inspect.
     * @return void
     */
    public function fixturize(TestCase $test): void
    {
        $this->_initDb();
        if (!$test->getFixtures() || !empty($this->_processed[get_class($test)])) {
            return;
        }
        $this->_loadFixtures($test);
        $this->_processed[get_class($test)] = true;
    }

    /**
     * Get the loaded fixtures.
     *
     * @return \Cake\Datasource\FixtureInterface[]
     */
    public function loaded(): array
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
    protected function _aliasConnections(): void
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
        foreach ($map as $testConnection => $normal) {
            ConnectionManager::alias($testConnection, $normal);
        }
    }

    /**
     * Initializes this class with a DataSource object to use as default for all fixtures
     *
     * @return void
     */
    protected function _initDb(): void
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
    protected function _loadFixtures(TestCase $test): void
    {
        $fixtures = $test->getFixtures();
        if (!$fixtures) {
            return;
        }
        foreach ($fixtures as $fixture) {
            if (isset($this->_loaded[$fixture])) {
                continue;
            }

            if (strpos($fixture, '.')) {
                [$type, $pathName] = explode('.', $fixture, 2);
                $path = explode('/', $pathName);
                $name = array_pop($path);
                $additionalPath = implode('\\', $path);

                if ($type === 'core') {
                    $baseNamespace = 'Cake';
                } elseif ($type === 'app') {
                    $baseNamespace = Configure::read('App.namespace');
                } elseif ($type === 'plugin') {
                    [$plugin, $name] = explode('.', $pathName);
                    $baseNamespace = str_replace('/', '\\', $plugin);
                    $additionalPath = null;
                } else {
                    $baseNamespace = '';
                    $name = $fixture;
                }

                if (strpos($name, '/') > 0) {
                    $name = str_replace('/', '\\', $name);
                }

                $nameSegments = [
                    $baseNamespace,
                    'Test\Fixture',
                    $additionalPath,
                    $name . 'Fixture',
                ];
                /** @psalm-var class-string<\Cake\Datasource\FixtureInterface> */
                $className = implode('\\', array_filter($nameSegments));
            } else {
                /** @psalm-var class-string<\Cake\Datasource\FixtureInterface> */
                $className = $fixture;
                /** @psalm-suppress PossiblyFalseArgument */
                $name = preg_replace('/Fixture\z/', '', substr(strrchr($fixture, '\\'), 1));
            }

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
                throw new UnexpectedValueException($msg);
            }
        }
    }

    /**
     * Runs the drop and create commands on the fixtures if necessary.
     *
     * @param \Cake\Datasource\FixtureInterface $fixture the fixture object to create
     * @param \Cake\Datasource\ConnectionInterface $db The Connection object instance to use
     * @param string[] $sources The existing tables in the datasource.
     * @param bool $drop whether drop the fixture if it is already created or not
     * @return void
     */
    protected function _setupTable(
        FixtureInterface $fixture,
        ConnectionInterface $db,
        array $sources,
        bool $drop = true
    ): void {
        $configName = $db->configName();
        $isFixtureSetup = $this->isFixtureSetup($configName, $fixture);
        if ($isFixtureSetup) {
            return;
        }

        $table = $fixture->sourceName();
        $exists = in_array($table, $sources, true);

        $hasSchema = $fixture instanceof TableSchemaAwareInterface && $fixture->getTableSchema() instanceof TableSchema;

        if (($drop && $exists) || ($exists && $hasSchema)) {
            $fixture->drop($db);
            $fixture->create($db);
        } elseif (!$exists) {
            $fixture->create($db);
        } else {
            $fixture->truncate($db);
        }

        $this->_insertionMap[$configName][] = $fixture;
    }

    /**
     * Creates the fixtures tables and inserts data on them.
     *
     * @param \Cake\TestSuite\TestCase $test The test to inspect for fixture loading.
     * @return void
     * @throws \Cake\Core\Exception\Exception When fixture records cannot be inserted.
     * @throws \RuntimeException
     */
    public function load(TestCase $test): void
    {
        $fixtures = $test->getFixtures();
        if (!$fixtures || !$test->autoFixtures) {
            return;
        }

        try {
            $createTables = function (ConnectionInterface $db, array $fixtures) use ($test): void {
                /** @var \Cake\Datasource\FixtureInterface[] $fixtures */
                $tables = $db->getSchemaCollection()->listTables();
                $configName = $db->configName();
                if (!isset($this->_insertionMap[$configName])) {
                    $this->_insertionMap[$configName] = [];
                }

                foreach ($fixtures as $fixture) {
                    if (!$fixture instanceof ConstraintsInterface) {
                        continue;
                    }

                    if (in_array($fixture->sourceName(), $tables, true)) {
                        try {
                            $fixture->dropConstraints($db);
                        } catch (PDOException $e) {
                            $msg = sprintf(
                                'Unable to drop constraints for fixture "%s" in "%s" test case: ' . "\n" . '%s',
                                get_class($fixture),
                                get_class($test),
                                $e->getMessage()
                            );
                            throw new Exception($msg, null, $e);
                        }
                    }
                }

                foreach ($fixtures as $fixture) {
                    if (!in_array($fixture, $this->_insertionMap[$configName], true)) {
                        $this->_setupTable($fixture, $db, $tables, $test->dropTables);
                    } else {
                        $fixture->truncate($db);
                    }
                }

                foreach ($fixtures as $fixture) {
                    if (!$fixture instanceof ConstraintsInterface) {
                        continue;
                    }

                    try {
                        $fixture->createConstraints($db);
                    } catch (PDOException $e) {
                        $msg = sprintf(
                            'Unable to create constraints for fixture "%s" in "%s" test case: ' . "\n" . '%s',
                            get_class($fixture),
                            get_class($test),
                            $e->getMessage()
                        );
                        throw new Exception($msg, null, $e);
                    }
                }
            };
            $this->_runOperation($fixtures, $createTables);

            // Use a separate transaction because of postgres.
            $insert = function (ConnectionInterface $db, array $fixtures) use ($test): void {
                foreach ($fixtures as $fixture) {
                    try {
                        $fixture->insert($db);
                    } catch (PDOException $e) {
                        $msg = sprintf(
                            'Unable to insert fixture "%s" in "%s" test case: ' . "\n" . '%s',
                            get_class($fixture),
                            get_class($test),
                            $e->getMessage()
                        );
                        throw new Exception($msg, null, $e);
                    }
                }
            };
            $this->_runOperation($fixtures, $insert);
        } catch (PDOException $e) {
            $msg = sprintf(
                'Unable to insert fixtures for "%s" test case. %s',
                get_class($test),
                $e->getMessage()
            );
            throw new RuntimeException($msg, 0, $e);
        }
    }

    /**
     * Run a function on each connection and collection of fixtures.
     *
     * @param string[] $fixtures A list of fixtures to operate on.
     * @param callable $operation The operation to run on each connection + fixture set.
     * @return void
     */
    protected function _runOperation(array $fixtures, callable $operation): void
    {
        $dbs = $this->_fixtureConnections($fixtures);
        foreach ($dbs as $connection => $fixtures) {
            $db = ConnectionManager::get($connection);
            $logQueries = $db->isQueryLoggingEnabled();

            if ($logQueries && !$this->_debug) {
                $db->disableQueryLogging();
            }
            $db->transactional(function (ConnectionInterface $db) use ($fixtures, $operation): void {
                $db->disableConstraints(function (ConnectionInterface $db) use ($fixtures, $operation): void {
                    $operation($db, $fixtures);
                });
            });
            if ($logQueries) {
                $db->enableQueryLogging(true);
            }
        }
    }

    /**
     * Get the unique list of connections that a set of fixtures contains.
     *
     * @param string[] $fixtures The array of fixtures a list of connections is needed from.
     * @return array An array of connection names.
     */
    protected function _fixtureConnections(array $fixtures): array
    {
        $dbs = [];
        foreach ($fixtures as $name) {
            if (!empty($this->_loaded[$name])) {
                $fixture = $this->_loaded[$name];
                $dbs[$fixture->connection()][$name] = $fixture;
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
    public function unload(TestCase $test): void
    {
        $fixtures = $test->getFixtures();
        if (!$fixtures) {
            return;
        }
        $truncate = function (ConnectionInterface $db, array $fixtures): void {
            $configName = $db->configName();

            foreach ($fixtures as $name => $fixture) {
                if (
                    $this->isFixtureSetup($configName, $fixture)
                    && $fixture instanceof ConstraintsInterface
                ) {
                    $fixture->dropConstraints($db);
                }
            }
        };
        $this->_runOperation($fixtures, $truncate);
    }

    /**
     * Creates a single fixture table and loads data into it.
     *
     * @param string $name of the fixture
     * @param \Cake\Datasource\ConnectionInterface|null $db Connection instance or null
     *  to get a Connection from the fixture.
     * @param bool $dropTables Whether or not tables should be dropped and re-created.
     * @return void
     * @throws \UnexpectedValueException if $name is not a previously loaded class
     */
    public function loadSingle(string $name, ?ConnectionInterface $db = null, bool $dropTables = true): void
    {
        if (!isset($this->_fixtureMap[$name])) {
            throw new UnexpectedValueException(sprintf('Referenced fixture class %s not found', $name));
        }

        $fixture = $this->_fixtureMap[$name];
        if (!$db) {
            $db = ConnectionManager::get($fixture->connection());
        }

        if (!$this->isFixtureSetup($db->configName(), $fixture)) {
            $sources = $db->getSchemaCollection()->listTables();
            $this->_setupTable($fixture, $db, $sources, $dropTables);
        }

        if (!$dropTables) {
            if ($fixture instanceof ConstraintsInterface) {
                $fixture->dropConstraints($db);
            }
            $fixture->truncate($db);
        }

        if ($fixture instanceof ConstraintsInterface) {
            $fixture->createConstraints($db);
        }
        $fixture->insert($db);
    }

    /**
     * Drop all fixture tables loaded by this class
     *
     * @return void
     */
    public function shutDown(): void
    {
        $shutdown = function (ConnectionInterface $db, array $fixtures): void {
            $connection = $db->configName();
            /** @var \Cake\Datasource\FixtureInterface $fixture */
            foreach ($fixtures as $fixture) {
                if ($this->isFixtureSetup($connection, $fixture)) {
                    $fixture->drop($db);
                    $index = array_search($fixture, $this->_insertionMap[$connection], true);
                    unset($this->_insertionMap[$connection][$index]);
                }
            }
        };
        $this->_runOperation(array_keys($this->_loaded), $shutdown);
    }

    /**
     * Check whether or not a fixture has been inserted in a given connection name.
     *
     * @param string $connection The connection name.
     * @param \Cake\Datasource\FixtureInterface $fixture The fixture to check.
     * @return bool
     */
    public function isFixtureSetup(string $connection, FixtureInterface $fixture): bool
    {
        return isset($this->_insertionMap[$connection]) && in_array($fixture, $this->_insertionMap[$connection], true);
    }
}
