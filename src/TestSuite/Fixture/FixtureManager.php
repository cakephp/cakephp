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
use Cake\Database\ConstraintsInterface;
use Cake\Database\Driver\Sqlite;
use Cake\Datasource\ConnectionInterface;
use Cake\Datasource\ConnectionManager;
use Cake\Datasource\FixtureInterface;
use Cake\TestSuite\TestCase;
use Closure;
use PDOException;
use RuntimeException;

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
    protected $fixtures = [];

    /**
     * @var \Cake\Datasource\FixtureInterface[]
     */
    protected $loadedFixtures = [];

    /**
     * @var \Cake\TestSuite\TestCase
     */
    protected $test;

    /**
     * @var \Cake\Datasource\FixtureInterface[]
     */
    protected $testFixtures = [];

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
        $this->initFixtures($test);
    }

    /**
     * Get the loaded fixtures.
     *
     * @return \Cake\Datasource\FixtureInterface[]
     */
    public function loaded(): array
    {
        return $this->fixtures;
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
     * Initializes fixtures listed in TestCase::$fixtures.
     *
     * Does not create schema or load records.
     *
     * @param \Cake\TestSuite\TestCase $test Running test case
     * @return void
     * @throws \RuntimeException When unable to initialize fixture.
     */
    protected function initFixtures(TestCase $test): void
    {
        $this->test = $test;
        $this->testFixtures = [];
        foreach ($test->getFixtures() as $fixturePath) {
            if (strpos($fixturePath, '.')) {
                [$type, $pathName] = explode('.', $fixturePath, 2);
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
                    $name = $fixturePath;
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
                $className = $fixturePath;
                /** @psalm-suppress PossiblyFalseArgument */
                $name = preg_replace('/Fixture\z/', '', substr(strrchr($fixturePath, '\\'), 1));
            }

            if (isset($this->fixtures[$name])) {
                if (!$this->fixtures[$name] instanceof $className) {
                    throw new RuntimeException(sprintf(
                        'Could not init fixture `%s` in test case `%s`. Already created with type `%s`.',
                        $name,
                        get_class($this->test),
                        get_class($this->fixtures[$name])
                    ));
                }

                $this->testFixtures[] = $this->fixtures[$name];
                continue;
            }

            if (!class_exists($className)) {
                throw new RuntimeException(sprintf(
                    'Could not init fixture `%s` in test case `%s`. Class `%s` not found.',
                    $name,
                    get_class($this->test),
                    $className
                ));
            }

            $fixture = new $className();
            $this->fixtures[$name] = $fixture;
            $this->testFixtures[] = $fixture;
        }
    }

    /**
     * Creates fixtures tables and inserts records.
     *
     * @param \Cake\TestSuite\TestCase $test Running test case
     * @return void
     */
    public function load(TestCase $test): void
    {
        if ($test !== $this->test) {
            throw new RuntimeException(sprintf(
                'Cannot call `unload()` before calling `fixturize()` in test case `%s`.',
                get_class($test)
            ));
        }

        if ($test->autoFixtures) {
            $this->loadFixtures($this->testFixtures, $test->dropTables);
        }
    }

    /**
     * Handles setting up a single fixture for a test.
     *
     * @param string $name of the fixture
     * @param \Cake\Datasource\ConnectionInterface|null $db Unused. Connection must be set in fixture.
     * @param bool $dropTables Whether tables are dropped and re-created if they exist.
     * @return void
     */
    public function loadSingle(string $name, ?ConnectionInterface $db = null, bool $dropTables = true): void
    {
        if (!$this->test) {
            throw new RuntimeException('Cannot call `loadSingle()` before calling `fixturize()`.');
        }

        $fixture = $this->fixtures[$name] ?? null;
        if (!$fixture) {
            throw new RuntimeException(sprintf(
                'Could not find fixture `%s` for test case `%s`. Missing from `$fixtures`.',
                $name,
                get_class($this->test)
            ));
        }

        $this->loadFixtures([$fixture], $dropTables);
    }

    /**
     * Handles setting up the fixture for each test.
     *
     * @param \Cake\Datasource\FixtureInterface[] $fixtures Fixtures to load
     * @param bool $dropTables Whether to drop tables if they exist before re-creating
     * @return void
     */
    protected function loadFixtures(array $fixtures, bool $dropTables): void
    {
        /*
        $this->runPerConnectionTransactional($fixtures, function ($connection, $fixtures, $tableCache) {
            foreach ($fixtures as $fixture) {
                if (in_array($fixture->sourceName(), $tableCache, true)) {
                    try {
                        $fixture->truncate($connection);
                    } catch (PDOException $e) {
                        triggerWarning(sprintf(
                            'Unable to truncate fixture `%s` in test case `%s`.',
                            get_class($fixture),
                            get_class($this->test)
                        ));
                        throw $e;
                    }
                }
            }
        });
        */

        $this->runPerFixture($fixtures, function ($connection, FixtureInterface $fixture, $tableCache) {
            if (in_array($fixture->sourceName(), $tableCache, true)) {
                try {
                    $fixture->drop($connection);
                } catch (PDOException $e) {
                    triggerWarning(sprintf(
                        'Unable to drop fixture `%s` in test case `%s` when loading.',
                        get_class($fixture),
                        get_class($this->test)
                    ));
                    throw $e;
                }
            }

            try {
                $fixture->create($connection);
                $this->loadedFixtures[] = $fixture;
            } catch (PDOException $e) {
                triggerWarning(sprintf(
                    'Unable to create fixture `%s` in test case `%s` when loading.',
                    get_class($fixture),
                    get_class($this->test)
                ));
                throw $e;
            }
        });

        $this->runPerFixture($fixtures, function ($connection, FixtureInterface $fixture, $tableCache) {
            if ($fixture instanceof ConstraintsInterface) {
                try {
                    $fixture->createConstraints($connection);
                } catch (PDOException $e) {
                    triggerWarning(sprintf(
                        'Unable to create constraints for fixture `%s` in test case `%s` when loading.',
                        get_class($fixture),
                        get_class($this->test)
                    ));
                    throw $e;
                }
            }
        });

        $this->runPerFixtureTransactional($fixtures, function ($connection, $fixture, $tableCache) {
            try {
                $fixture->insert($connection);
            } catch (PDOException $e) {
                triggerWarning(sprintf(
                    'Unable to insert records for fixture `%s` in test case `%s`.',
                    get_class($fixture),
                    get_class($this->test)
                ));
                throw $e;
            }
        });
    }

    /**
     * Handles updating the fixtures after each test completes.
     *
     * @param \Cake\TestSuite\TestCase $test Running test case
     * @return void
     */
    public function unload(TestCase $test): void
    {
        if ($test !== $this->test) {
            throw new RuntimeException(sprintf(
                'Cannot call `unload()` before calling `fixturize()` in test case `%s`.',
                get_class($test)
            ));
        }

        $this->runPerFixture($this->testFixtures, function ($connection, FixtureInterface $fixture, $tableCache) {
            if (
                $fixture instanceof ConstraintsInterface &&
                in_array($fixture->sourceName(), $tableCache, true)
            ) {
                try {
                    $fixture->dropConstraints($connection);
                } catch (PDOException $e) {
                    triggerWarning(sprintf(
                        'Unable to drop constraints for  fixture `%s` when unloading test case `%s`.',
                        get_class($fixture),
                        get_class($this->test)
                    ));
                    throw $e;
                }
            }
        });

        $this->testFixtures = [];
    }

    /**
     * Drop all fixture tables loaded by this class
     *
     * @return void
     */
    public function shutDown(): void
    {
        $this->runPerFixture($this->fixtures, function ($connection, FixtureInterface $fixture, $tableCache) {
            if (
                $fixture instanceof ConstraintsInterface &&
                in_array($fixture->sourceName(), $tableCache, true)
            ) {
                try {
                    $fixture->dropConstraints($connection);
                } catch (PDOException $e) {
                    triggerWarning(sprintf(
                        'Unable to drop constraints for fixture `%s` during shutdown.',
                        get_class($fixture)
                    ));
                    throw $e;
                }
            }
        });

        $this->runPerFixture($this->fixtures, function ($connection, $fixture, $tableCache) {
            if (in_array($fixture->sourceName(), $tableCache, true)) {
                try {
                    $fixture->drop($connection);
                } catch (PDOException $e) {
                    triggerWarning(sprintf(
                        'Unable to drop fixture `%s` during shutdown.',
                        get_class($fixture)
                    ));
                    throw $e;
                }
            }
        });

        $this->fixtures = [];
        $this->loadedFixtures = [];
        $this->testFixtures = [];
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
        return in_array($fixture, $this->loadedFixtures, true);
    }

    /**
     * Runs callback for each fixture.
     *
     * @param \Cake\Datasource\FixtureInterface[] $fixtures Test case fixtures
     * @param \Closure $callback Callback to run
     * @return void
     */
    protected function runPerFixture(array $fixtures, Closure $callback): void
    {
        foreach ($this->groupByConnection($fixtures) as $connectionName => $fixtures) {
            $connection = ConnectionManager::get($connectionName);

            $wasLogging = $connection->isQueryLoggingEnabled();
            if ($wasLogging && !$this->_debug) {
                $connection->disableQueryLogging();
            }

            $tableCache = $connection->getSchemaCollection()->listTables();
            foreach ($fixtures as $fixture) {
                $callback($connection, $fixture, $tableCache);
            }

            if ($wasLogging) {
                $connection->enableQueryLogging(true);
            }
        }
    }

    /**
     * Runs callback for each fixture in a transaction.
     *
     * @param \Cake\Datasource\FixtureInterface[] $fixtures Test case fixtures
     * @param \Closure $callback Callback to run
     * @return void
     */
    protected function runPerFixtureTransactional(array $fixtures, Closure $callback): void
    {
        foreach ($this->groupByConnection($fixtures) as $connectionName => $fixtures) {
            $connection = ConnectionManager::get($connectionName);

            $wasLogging = $connection->isQueryLoggingEnabled();
            if ($wasLogging && !$this->_debug) {
                $connection->disableQueryLogging();
            }

            $tableCache = $connection->getSchemaCollection()->listTables();
            if ($connection->getDriver() instanceof Sqlite) {
                $connection->disableConstraints(function ($connection) use ($callback, $fixtures, $tableCache) {
                    $connection->transactional(function ($connection) use ($callback, $fixtures, $tableCache) {
                        foreach ($fixtures as $fixture) {
                            $callback($connection, $fixture, $tableCache);
                        }
                    });
                });
            } else {
                $connection->transactional(function ($connection) use ($callback, $fixtures, $tableCache) {
                    $connection->disableConstraints(function ($connection) use ($callback, $fixtures, $tableCache) {
                        foreach ($fixtures as $fixture) {
                            $callback($connection, $fixture, $tableCache);
                        }
                    });
                });
            }

            if ($wasLogging) {
                $connection->enableQueryLogging(true);
            }
        }
    }

    /**
     * Groups fixtures by connection.
     *
     * This is a separate function mainly to allow mocks to override.
     *
     * @param \Cake\Datasource\FixtureInterface[] $fixtures Test case fixtures
     * @return array
     */
    protected function groupByConnection(array $fixtures): array
    {
        $grouped = [];
        foreach ($fixtures as $fixture) {
            $grouped[$fixture->connection()][] = $fixture;
        }

        return $grouped;
    }
}
