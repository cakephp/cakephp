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
 * @since         4.3.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\TestSuite\Fixture;

use Cake\Core\Configure;
use Cake\Core\Exception\CakeException;
use Cake\Datasource\ConnectionInterface;
use Cake\Datasource\ConnectionManager;
use Cake\TestSuite\TestCase;
use PDOException;
use RuntimeException;
use UnexpectedValueException;

/**
 * Data only fixture manager
 *
 * Part of the data-only fixture system. Data-only fixtures assume that
 * you have created the schema for the testsuite *before* running tests.
 * They also assume that any mutations to your schema are reverted by you.
 *
 * This class implements a common interface with the Cake\TestSuite\FixtureManager so that
 * test cases only need to interact with a single interface.
 */
class DataFixtureManager
{
    /**
     * A mapping between the fixture name (including the prefix) and the object.
     *
     * @var \Cake\Datasource\FixtureInterface[]
     */
    protected $loaded = [];

    /**
     * A map between a fixture name without the plugin prefix and the object.
     *
     * @var \Cake\Datasource\FixtureInterface[]
     */
    protected $fixtureMap = [];

    /**
     * Looks for fixture files and instantiates the classes accordingly
     *
     * @param \Cake\TestSuite\TestCase $test The test suite to load fixtures for.
     * @return void
     * @throws \UnexpectedValueException when a referenced fixture does not exist.
     */
    protected function loadFixtures(TestCase $test): void
    {
        $fixtures = $test->getFixtures();
        if (!$fixtures) {
            return;
        }
        foreach ($fixtures as $fixture) {
            if (isset($this->loaded[$fixture])) {
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
                $this->loaded[$fixture] = new $className();
                $this->fixtureMap[$name] = $this->loaded[$fixture];
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
     * Loads the data for a single fixture.
     *
     * @param string $name of the fixture
     * @param \Cake\Datasource\ConnectionInterface|null $connection Connection instance or null
     *  to get a Connection from the fixture.
     * @return void
     * @throws \UnexpectedValueException if $name is not a previously loaded class
     */
    public function loadSingle(string $name, ?ConnectionInterface $connection = null): void
    {
        if (!isset($this->fixtureMap[$name])) {
            throw new UnexpectedValueException(sprintf('Referenced fixture class %s not found', $name));
        }

        $fixture = $this->fixtureMap[$name];
        if (!$connection) {
            $connection = ConnectionManager::get($fixture->connection());
        }

        $fixture->insert($connection);
    }

    /**
     * Creates records defined in a test case's fixtures.
     *
     * @param \Cake\TestSuite\TestCase $test The test to inspect for fixture loading.
     * @return void
     * @throws \Cake\Core\Exception\CakeException When fixture records cannot be inserted.
     * @throws \RuntimeException
     */
    public function load(TestCase $test): void
    {
        $fixtures = $test->getFixtures();
        if (!$fixtures || !$test->autoFixtures) {
            return;
        }

        try {
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
                        throw new CakeException($msg, null, $e);
                    }
                }
            };
            $this->runOperation($fixtures, $insert);
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
    protected function runOperation(array $fixtures, callable $operation): void
    {
        $dbs = $this->fixtureConnections($fixtures);
        foreach ($dbs as $connection => $fixtures) {
            $db = ConnectionManager::get($connection);
            $db->transactional(function (ConnectionInterface $db) use ($fixtures, $operation): void {
                $db->disableConstraints(function (ConnectionInterface $db) use ($fixtures, $operation): void {
                    $operation($db, $fixtures);
                });
            });
        }
    }

    /**
     * Get the unique list of connections that a set of fixtures contains.
     *
     * @param string[] $fixtures The array of fixtures a list of connections is needed from.
     * @return array An array of connection names.
     */
    protected function fixtureConnections(array $fixtures): array
    {
        $dbs = [];
        foreach ($fixtures as $name) {
            if (!empty($this->loaded[$name])) {
                $fixture = $this->loaded[$name];
                $dbs[$fixture->connection()][$name] = $fixture;
            }
        }

        return $dbs;
    }

    /**
     * Inspects the test to to load fixture classes.
     *
     * @param \Cake\TestSuite\TestCase $test The test case to inspect.
     * @return void
     */
    public function fixturize(TestCase $test): void
    {
        if (!$test->getFixtures()) {
            return;
        }
        $this->loadFixtures($test);
    }

    /**
     * Get the loaded fixtures.
     *
     * @return \Cake\Datasource\FixtureInterface[]
     */
    public function loaded(): array
    {
        return $this->loaded;
    }
}
